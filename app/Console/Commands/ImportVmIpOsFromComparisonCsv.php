<?php

namespace App\Console\Commands;

use App\Models\qemu;
use Illuminate\Console\Command;

class ImportVmIpOsFromComparisonCsv extends Command
{
    protected $signature = 'qemu:import-ip-os-pdf {path} {--dry-run : Solo muestra resultados, no actualiza}';
    protected $description = 'Importa IP/SO desde CSV de comparación PDF aplicando match exacto o excepción dnsufinet01.';

    public function handle(): int
    {
        $path = $this->argument('path');
        if (!is_file($path)) {
            $this->error("Archivo no encontrado: {$path}");
            return self::FAILURE;
        }
        $fh = fopen($path, 'r');
        $header = fgets($fh);
        if ($header === false) {
            $this->error('CSV vacío');
            return self::FAILURE;
        }
        $header = preg_replace('/^\xEF\xBB\xBF/', '', trim($header));
        $delimiter = str_contains($header, ';') ? ';' : ',';
        $columns = str_getcsv($header, $delimiter);
        $stats = ['rows'=>0,'imported'=>0,'exact'=>0,'dnsufinet01_exception'=>0,'skipped'=>0,'no_ip_os'=>0,'qga_preserved'=>0,'ambiguous'=>0];
        while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
            if (count($row) < count($columns)) continue;
            $data = array_combine($columns, array_slice($row, 0, count($columns)));
            $stats['rows']++;
            $pdfCluster = trim((string)($data['pdf_cluster'] ?? ''));
            $vmid = trim((string)($data['pdf_vmid'] ?? ''));
            $name = trim((string)($data['pdf_nombre'] ?? ''));
            $ip = $this->cleanIp((string)($data['pdf_ip'] ?? ''));
            $os = trim((string)($data['pdf_so'] ?? ''));
            if ($vmid === '' || $name === '' || ($ip === '' && $os === '')) { $stats['no_ip_os']++; continue; }

            $matchType = null;
            $qemu = qemu::where('vmid', $vmid)->where('name', $name)->where('cluster_name', $pdfCluster)->first();
            if ($qemu) {
                $matchType = 'exact';
            } else {
                $candidates = qemu::where('vmid', $vmid)->where('name', $name)->get()->filter(function ($vm) {
                    $nodeName = strtolower((string) \Illuminate\Support\Facades\DB::table('nodes')->where('id', $vm->id_node)->value('node'));
                    $clusterName = strtolower((string) $vm->cluster_name);
                    return $nodeName === 'dnsufinet01' || $clusterName === 'dnsufinet01';
                })->values();
                if ($candidates->count() === 1) {
                    $qemu = $candidates->first();
                    $matchType = 'dnsufinet01_exception';
                } elseif ($candidates->count() > 1) {
                    $stats['ambiguous']++;
                    continue;
                }
            }
            if (!$qemu) { $stats['skipped']++; continue; }

            if (($qemu->ip_os_source ?? null) === 'qga' || ((int)($qemu->qga_enabled_config ?? 0) === 1 && ($qemu->status ?? '') === 'running')) {
                $stats['qga_preserved']++;
                continue;
            }
            if (!$this->option('dry-run')) {
                $qemu->ip_address_manual = $ip ?: $qemu->ip_address_manual;
                $qemu->os_name_manual = $os ?: $qemu->os_name_manual;
                $qemu->ip_address = $ip ?: $qemu->ip_address;
                $qemu->os_name = $os ?: $qemu->os_name;
                $qemu->ip_os_source = 'pdf';
                $qemu->manual_inventory_note = $matchType === 'dnsufinet01_exception' ? 'PDF import: excepción dnsufinet01 por VMID+nombre' : 'PDF import: cluster+VMID+nombre';
                $qemu->manual_inventory_updated_at = now();
                $qemu->save();
            }
            $stats['imported']++;
            $stats[$matchType]++;
        }
        fclose($fh);
        foreach ($stats as $k => $v) $this->line("{$k}={$v}");
        return self::SUCCESS;
    }

    private function cleanIp(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value === '-' || strtoupper($value) === 'N/A') return '';
        $value = preg_split('/\s+/', $value)[0] ?? '';
        $value = preg_replace('#/\d+$#', '', $value);
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $value : '';
    }
}
