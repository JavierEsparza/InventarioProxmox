#!/usr/bin/env python3
"""Auditoría de interfaces/IPs de VMs por SSH. Sólo lectura; no modifica hosts ni DB."""
from __future__ import annotations
import argparse, csv, getpass, ipaddress, json, socket, sys
import re
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

try:
    import paramiko
    from paramiko.ssh_exception import AuthenticationException, BadHostKeyException, NoValidConnectionsError
except ImportError:
    print("Falta paramiko. Instalar con: python3 -m pip install paramiko", file=sys.stderr)
    raise

DEFAULT_NETWORKS = ["192.168.100.0/24", "10.10.52.0/24", "10.10.51.0/24"]


def now():
    return datetime.now(timezone.utc).replace(microsecond=0).isoformat().replace("+00:00", "Z")


def load_hosts(path: Path) -> list[dict[str, str]]:
    with path.open(newline="", encoding="utf-8-sig") as fh:
        rows = list(csv.DictReader(fh))
    if not rows:
        raise ValueError("El CSV no contiene hosts")
    aliases = {"ip": "ansible_host", "host": "ansible_host", "hostname": "ansible_host"}
    result = []
    for n, row in enumerate(rows, 2):
        r = {str(k).strip(): (v or "").strip() for k, v in row.items() if k}
        for old, new in aliases.items():
            if not r.get("ansible_host") and r.get(old): r["ansible_host"] = r[old]
        if not r.get("ansible_host"):
            raise ValueError(f"Fila {n}: falta ansible_host/ip/host")
        r.setdefault("name", r.get("vm_name") or r.get("vmid") or r["ansible_host"])
        result.append(r)
    return result


def classify_error(exc: Exception) -> str:
    text = str(exc).lower()
    if isinstance(exc, AuthenticationException):
        return "CREDENCIAL_RECHAZADA"
    if isinstance(exc, BadHostKeyException): return "HOST_KEY_INVALIDA"
    if isinstance(exc, (socket.timeout, TimeoutError)) or "timed out" in text:
        return "TIMEOUT"
    if isinstance(exc, NoValidConnectionsError) or "refused" in text:
        return "SSH_NO_DISPONIBLE"
    if "name or service" in text or "nodename" in text:
        return "HOST_NO_RESUELVE"
    if "permission denied" in text:
        return "PERMISO_DENEGADO"
    return "ERROR_SSH"


def collect(host: dict[str, str], username: str, password: str, port: int, timeout: int,
            known_networks: list[ipaddress._BaseNetwork], expected_unreachable: list[ipaddress._BaseNetwork],
            accept_unknown: bool) -> dict[str, Any]:
    target = host["ansible_host"]
    result: dict[str, Any] = {"name": host.get("name", target), "target": target,
                              "vmid": host.get("vmid", ""), "cluster_name": host.get("cluster_name", ""),
                              "status": "", "collected_at": now(), "interfaces": [], "error": "",
                              "os_name": "", "collection_method": ""}
    try:
        target_ip = ipaddress.ip_address(target)
        if any(target_ip in net for net in expected_unreachable):
            result["status"] = "FUERA_DE_ALCANCE_ESPERADO"
            result["error"] = "red excluida antes de conexión SSH"
            return result
    except ValueError:
        pass
    try:
        with socket.create_connection((target, port), timeout=min(timeout, 3)):
            pass
    except Exception as exc:
        result["status"] = "SSH_NO_DISPONIBLE"
        result["error"] = str(exc)
        return result
    client = paramiko.SSHClient()
    client.load_system_host_keys()
    if accept_unknown: client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        client.connect(target, port=port, username=username, password=password,
                       timeout=timeout, banner_timeout=timeout, auth_timeout=timeout,
                       look_for_keys=False, allow_agent=False)
        command = "sh -c 'if ip -j -4 addr show scope global >/tmp/.vm_audit_ipjson 2>/tmp/.vm_audit_iperr; then printf \"__JSON__\\n\"; cat /tmp/.vm_audit_ipjson; else printf \"__FALLBACK__\\n\"; hostname; cat /etc/os-release 2>/dev/null; uname -srm; ip -4 addr show 2>&1; fi'"
        stdin, stdout, stderr = client.exec_command(command, timeout=timeout)
        raw = stdout.read().decode(errors="replace").strip()
        err = stderr.read().decode(errors="replace").strip()
        if not raw:
            result.update(status="ERROR_CONSULTA", error=err or "sin salida")
            return result
        if raw.startswith("__JSON__"):
            data = json.loads(raw.split("\n", 1)[1])
            result["collection_method"] = "ip-json"
            for iface in data:
                name, mac = iface.get("ifname", ""), iface.get("address", "")
                for addr in iface.get("addr_info", []):
                    if addr.get("family") != "inet": continue
                    ip = addr.get("local", "")
                    try: network = ipaddress.ip_interface(f"{ip}/{addr.get('prefixlen', '')}").network
                    except ValueError: network = None
                    known = bool(network and any(ipaddress.ip_address(ip) in net for net in known_networks))
                    result["interfaces"].append({"interface": name, "mac": mac, "ip_address": ip,
                        "cidr": f"{ip}/{addr.get('prefixlen', '')}", "scope": addr.get("scope", ""),
                        "known_network": known})
        elif raw.startswith("__FALLBACK__"):
            result["collection_method"] = "ip-text-fallback"
            os_lines = [x for x in raw.splitlines() if x.startswith("PRETTY_NAME=") or x.startswith("NAME=")]
            if os_lines:
                result["os_name"] = os_lines[0].split("=", 1)[1].strip().strip('"')
            for line in raw.splitlines():
                match = re.search(r"inet (\d+\.\d+\.\d+\.\d+)/(\d+) .* (?:scope \w+ )?", line)
                if not match: continue
                ip, prefix = match.group(1), match.group(2)
                name_match = re.search(r"^\d+: ([^:]+):", line)
                name = name_match.group(1) if name_match else ""
                try: network = ipaddress.ip_interface(f"{ip}/{prefix}").network
                except ValueError: network = None
                known = bool(network and any(ipaddress.ip_address(ip) in net for net in known_networks))
                result["interfaces"].append({"interface": name, "mac": "", "ip_address": ip,
                    "cidr": f"{ip}/{prefix}", "scope": "", "known_network": known})
        else:
            raise ValueError("respuesta no reconocida")
        result["status"] = "OK"
        if not result["interfaces"]: result["status"] = "SIN_IP_DETECTADA"
        if result["collection_method"] == "ip-text-fallback" and result["status"] == "OK":
            result["status"] = "OK_FALLBACK_LEGACY"
    except Exception as exc:
        result["status"], result["error"] = classify_error(exc), str(exc)
        try:
            target_ip = ipaddress.ip_address(target)
            if result["status"] in {"SSH_NO_DISPONIBLE", "TIMEOUT", "HOST_NO_RESUELVE"} and any(target_ip in net for net in expected_unreachable):
                result["status"] = "FUERA_DE_ALCANCE_ESPERADO"
        except ValueError:
            pass
    finally:
        client.close()
    return result


def write_reports(results: list[dict[str, Any]], out: Path):
    out.mkdir(parents=True, exist_ok=True)
    (out / "all-hosts.json").write_text(json.dumps(results, ensure_ascii=False, indent=2), encoding="utf-8")
    rows = []
    for r in results:
        for i in r["interfaces"]:
            rows.append({"name": r["name"], "vmid": r["vmid"], "cluster_name": r["cluster_name"],
                         "target": r["target"], "status": r["status"], **i})
        if not r["interfaces"]:
            rows.append({"name": r["name"], "vmid": r["vmid"], "cluster_name": r["cluster_name"],
                         "target": r["target"], "status": r["status"], "error": r["error"]})
    fields = sorted({k for row in rows for k in row})
    with (out / "network-inventory.csv").open("w", newline="", encoding="utf-8") as fh:
        writer = csv.DictWriter(fh, fieldnames=fields); writer.writeheader(); writer.writerows(rows)
    errors = [r for r in results if not r["status"].startswith("OK") and r["status"] != "FUERA_DE_ALCANCE_ESPERADO"]
    with (out / "ssh-errors.csv").open("w", newline="", encoding="utf-8") as fh:
        writer = csv.DictWriter(fh, fieldnames=["name", "vmid", "cluster_name", "target", "status", "error"])
        writer.writeheader(); writer.writerows({k: r.get(k, "") for k in writer.fieldnames} for r in errors)
    expected = [r for r in results if r["status"] == "FUERA_DE_ALCANCE_ESPERADO"]
    with (out / "expected-unreachable.csv").open("w", newline="", encoding="utf-8") as fh:
        writer = csv.DictWriter(fh, fieldnames=["name", "vmid", "cluster_name", "target", "status", "error"])
        writer.writeheader(); writer.writerows({k: r.get(k, "") for k in writer.fieldnames} for r in expected)
    ips = [row for row in rows if row.get("ip_address") and not row.get("known_network")]
    with (out / "additional-ips.csv").open("w", newline="", encoding="utf-8") as fh:
        fields = ["name", "vmid", "cluster_name", "target", "interface", "mac", "ip_address", "cidr"]
        writer = csv.DictWriter(fh, fieldnames=fields); writer.writeheader(); writer.writerows({k: r.get(k, "") for k in fields} for r in ips)
    summary = {}
    for r in results: summary[r["status"]] = summary.get(r["status"], 0) + 1
    (out / "summary.json").write_text(json.dumps({"collected_at": now(), "hosts": len(results), "statuses": summary,
        "interfaces": sum(len(r["interfaces"]) for r in results), "additional_ips": len(ips)}, indent=2), encoding="utf-8")
    return summary, len(rows), len(ips)


def main():
    ap = argparse.ArgumentParser(description="Audita IPs/interfaces de VMs por SSH; no aplica cambios")
    ap.add_argument("--input", required=True, type=Path, help="CSV con ansible_host o ip; opcionales name,vmid,cluster_name")
    ap.add_argument("--output", default="network-audit", type=Path)
    ap.add_argument("--user", default="infransible")
    ap.add_argument("--port", default=22, type=int); ap.add_argument("--timeout", default=10, type=int)
    ap.add_argument("--workers", default=10, type=int)
    ap.add_argument("--network", action="append", dest="networks", default=None, help="CIDR conocida; repetible")
    ap.add_argument("--expected-unreachable", action="append", dest="expected_unreachable", default=None,
                    help="CIDR que no debe considerarse error de conectividad; repetible")
    ap.add_argument("--password-stdin", action="store_true", help="lee la contraseña SSH desde stdin")
    ap.add_argument("--accept-unknown-host-keys", action="store_true")
    args = ap.parse_args()
    hosts = load_hosts(args.input)
    password = sys.stdin.readline().rstrip("\r\n") if args.password_stdin else getpass.getpass(f"Contraseña SSH de {args.user}: ")
    networks = [ipaddress.ip_network(x) for x in (args.networks or DEFAULT_NETWORKS)]
    expected_unreachable = [ipaddress.ip_network(x) for x in (args.expected_unreachable or ["192.168.11.0/24", "192.168.12.0/24", "172.0.0.0/8"])]
    results = []
    with ThreadPoolExecutor(max_workers=max(1, args.workers)) as pool:
        jobs = [pool.submit(collect, h, args.user, password, args.port, args.timeout, networks, expected_unreachable, args.accept_unknown_host_keys) for h in hosts]
        for job in as_completed(jobs):
            r = job.result(); results.append(r); print(f"{r['status']:22} {r['name']} ({r['target']})")
    results.sort(key=lambda x: (x["name"], x["target"]))
    summary, rows, extra = write_reports(results, args.output)
    print(json.dumps({"output": str(args.output), "hosts": len(results), "rows": rows, "additional_ips": extra, "statuses": summary}, ensure_ascii=False))

if __name__ == "__main__": main()
