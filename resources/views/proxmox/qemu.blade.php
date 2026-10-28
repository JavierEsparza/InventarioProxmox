
<style>
.cluster-sync-info { display:flex; align-items:center; gap:.55rem; top:50%; transform:translateY(-50%); }
.cluster-sync-label { color:#8fa7bb; font-size:.68rem; white-space:nowrap; }
.cluster-sync-label i { color:#43baff; margin-right:.2rem; }
@media (max-width: 900px) { .cluster-sync-label { display:none; } }

.audit-cluster-btn { border:1px solid #00bfe8 !important; color:#78e6ff !important; background:rgba(0,191,232,.08) !important; border-radius:999px !important; font-size:.68rem !important; line-height:1.2 !important; padding:.28rem .62rem !important; white-space:nowrap; box-shadow:0 0 0 rgba(0,191,232,0); transition:all .18s ease; }
.audit-cluster-btn:hover { color:#06131b !important; background:#58ddf7 !important; border-color:#58ddf7 !important; box-shadow:0 0 12px rgba(0,191,232,.28); }
.audit-cluster-btn:disabled, .audit-cluster-btn.disabled { opacity:.55; pointer-events:none; }
.audit-vm-btn { border:1px solid #6f8cff !important; color:#b7c4ff !important; background:rgba(111,140,255,.08) !important; border-radius:999px !important; font-size:.68rem !important; line-height:1.2 !important; padding:.25rem .52rem !important; white-space:nowrap; }
.audit-vm-btn:hover { color:#fff !important; background:#526fe0 !important; border-color:#7893ff !important; }
.audit-vm-btn:disabled, .audit-vm-btn.disabled { opacity:.55; pointer-events:none; }
.audit-status-btn { border:1px solid #8a9bad !important; color:#d5e0ea !important; background:rgba(138,155,173,.10) !important; border-radius:999px !important; font-size:.68rem !important; line-height:1.2 !important; padding:.28rem .62rem !important; white-space:nowrap; }
.audit-status-btn:hover { color:#07131c !important; background:#b9c9d6 !important; }
.audit-monitor-backdrop { position:fixed; inset:0; z-index:2050; display:flex; align-items:center; justify-content:center; background:rgba(2,8,14,.78); padding:1rem; }
.audit-monitor-card { width:min(1180px,97vw); max-height:92vh; overflow:auto; background:#142a3a; color:#f3f8fb; border:1px solid #5d829a; border-radius:12px; box-shadow:0 18px 60px rgba(0,0,0,.65); font-size:.88rem; }
.audit-monitor-head { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:1rem 1.15rem; border-bottom:1px solid #527188; position:sticky; top:0; background:#183448; z-index:1; }
.audit-monitor-head strong { color:#ffffff; font-size:1rem; letter-spacing:.01em; }
.audit-monitor-summary { display:flex; flex-wrap:wrap; gap:.5rem; padding:1rem 1.15rem .65rem; }
.audit-monitor-pill { color:#f1f7fa; border:1px solid #628298; border-radius:999px; padding:.34rem .62rem; font-size:.78rem; background:#1e4055; }
.audit-monitor-pill.ok { color:#c8f8d8; border-color:#45a86d; background:#16462f; } .audit-monitor-pill.pending { color:#fff0b8; border-color:#c49b35; background:#554314; } .audit-monitor-pill.error { color:#ffd0d0; border-color:#c45b66; background:#5a252c; }
.audit-monitor-warning { margin:0 1.15rem .85rem; padding:.7rem .85rem; border:1px solid #c49b35; border-radius:7px; color:#fff1bd; background:#554314; font-size:.84rem; font-weight:600; }
.audit-monitor-table { width:100%; font-size:.82rem; color:#f3f8fb; border-collapse:separate; border-spacing:0; } .audit-monitor-table th,.audit-monitor-table td { padding:.55rem .7rem; border-bottom:1px solid #365669; vertical-align:top; color:#edf6fa; } .audit-monitor-table th { color:#ffffff; font-weight:700; background:#1b3b50; } .audit-monitor-table tbody tr:nth-child(even) { background:rgba(53,91,112,.22); } .audit-monitor-table tbody tr:hover { background:rgba(75,137,165,.28); }
.audit-monitor-result { display:inline-block; min-width:6.8rem; text-align:center; padding:.22rem .4rem; border-radius:5px; font-size:.73rem; font-weight:700; background:#334c5c; color:#eaf3f7; } .audit-monitor-result.ok { background:#17613c; color:#d2ffe0; } .audit-monitor-result.pending { background:#69551b; color:#fff0ad; } .audit-monitor-result.error { background:#73313a; color:#ffd7d7; }
.audit-monitor-close { border:0; background:transparent; color:#ffffff; font-size:1.45rem; opacity:.9; } .audit-monitor-stop { margin:.15rem 1.15rem 1rem; color:#fff !important; border-color:#ff7b86 !important; background:#7d2934 !important; font-weight:600; }
.cluster-audit-summary { color:#91a9b8; font-size:.62rem; white-space:nowrap; max-width:270px; overflow:hidden; text-overflow:ellipsis; }
@media (max-width:900px) { .cluster-audit-summary { display:none; } }

.cluster-accordion .accordion-header { display:flex; align-items:center; gap:.75rem; }
.cluster-accordion .accordion-button { flex:1 1 auto; min-width:0; padding-right:1rem !important; }
.cluster-accordion .cluster-sync-info { position:static !important; transform:none; margin:0 .75rem 0 0 !important; max-width:min(58%, 680px); flex:0 1 auto; z-index:4; display:flex; flex-wrap:wrap; align-items:center; justify-content:flex-end; gap:.35rem; row-gap:.35rem; }
.cluster-accordion .cluster-sync-label { max-width:190px; overflow:hidden; text-overflow:ellipsis; }
.cluster-accordion .cluster-audit-summary { max-width:180px; flex:0 1 180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
 .cluster-accordion .sync-btn, .cluster-accordion .audit-cluster-btn { flex:0 0 auto; white-space:nowrap; min-width:96px; }
.cluster-accordion .accordion-button { min-width:0; }
@media (min-width:701px) { .cluster-accordion .cluster-sync-info { max-width:none; flex:0 0 auto; flex-wrap:nowrap; white-space:nowrap; } }
@media (max-width:1100px) { .cluster-accordion .cluster-sync-info { max-width:46%; } .cluster-accordion .cluster-sync-label, .cluster-accordion .cluster-audit-summary { display:none; } }
@media (max-width:700px) { .cluster-accordion .accordion-header { display:block; } .cluster-accordion .cluster-sync-info { padding:.55rem .85rem .75rem; margin:0 !important; max-width:none; flex-wrap:wrap; } .cluster-accordion .accordion-button { width:100%; } }
    .qemu-inventory-table th.qemu-sort { cursor:pointer; user-select:none; }
    .qemu-data-age { display:inline-flex; align-items:center; gap:.25rem; white-space:nowrap; font-size:.68rem; padding:.25rem .38rem; border-radius:7px; border:1px solid transparent; }
    .qemu-data-age.fresh { color:#b8f3cc; background:#123b2a; border-color:#286b49; }
    .qemu-data-age.stale { color:#ffe5a0; background:#4a3a16; border-color:#806529; }
    .qemu-data-age.old { color:#ffc0c0; background:#4b2025; border-color:#873b45; }
    .qemu-data-age.unknown { color:#b8c4cf; background:#27333e; border-color:#4a5b69; }
    .qemu-inventory-table th.qemu-sort:hover { color:#8be9ff !important; }
    .qemu-inventory-table th.qemu-sort::after { content:'↕'; opacity:.55; margin-left:.3rem; }
    .qemu-inventory-table th.qemu-sort.sort-asc::after { content:'↑'; opacity:1; color:#8be9ff; }
    .qemu-inventory-table th.qemu-sort.sort-desc::after { content:'↓'; opacity:1; color:#8be9ff; }
</style>
@extends('layouts.app')

@section('content')
<style>
    .page-hero { background:linear-gradient(135deg,#101820 0%,#172330 58%,#0f5132 155%); border:1px solid #2f3b49; border-radius:18px; padding:1.25rem; color:#fff; box-shadow:0 16px 35px rgba(0,0,0,.28); }
    .page-title { font-size:clamp(1.7rem,2.6vw,2.45rem); font-weight:850; letter-spacing:-.03em; margin:0; }
    .page-subtitle { color:#aab7c4; font-size:.95rem; margin-top:.4rem; }
    .cluster-pill { display:inline-flex; align-items:center; gap:.35rem; border:1px solid #344455; background:rgba(255,255,255,.06); color:#d7e2ec; border-radius:999px; padding:.35rem .65rem; font-size:.82rem; font-weight:750; }
    .cluster-pill.success { border-color:rgba(25,135,84,.6); background:rgba(25,135,84,.18); color:#91e7b4; }
    .metric-card { background:#151d26; border:1px solid #2b3846; border-radius:16px; padding:1rem; color:#fff; height:100%; box-shadow:inset 0 1px 0 rgba(255,255,255,.03); }
    .metric-label { color:#96a6b5; font-size:.74rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
    .metric-value { font-size:2rem; font-weight:850; line-height:1; margin-top:.35rem; }
    .metric-foot { color:#8d9baa; font-size:.82rem; margin-top:.5rem; }
    .health-panel { background:linear-gradient(135deg,#101e2a,#142b3b); border:1px solid #35566b; border-radius:16px; padding:1rem 1.1rem; color:#eef7fb; box-shadow:0 12px 28px rgba(0,0,0,.18); }
    .health-title { font-size:1rem; font-weight:850; color:#fff; }
    .health-subtitle { color:#a9bdca; font-size:.78rem; }
    .health-item { background:rgba(255,255,255,.055); border:1px solid #345366; border-radius:11px; padding:.65rem .75rem; height:100%; }
    .health-item-label { color:#b3c5d0; font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; font-weight:800; }
    .health-item-value { color:#fff; font-weight:850; font-size:1.25rem; margin-top:.15rem; }
    .health-item-foot { color:#9eb2bf; font-size:.72rem; }
    .health-ok { color:#9af0bb !important; } .health-warn { color:#ffe29b !important; } .health-danger { color:#ffabb4 !important; }
    .audit-info-card { border:1px solid #36576b; background:rgba(43,84,105,.22); border-radius:11px; padding:.7rem .8rem; color:#eaf5fa; }
    .responsive-only-label { display:none; }
    @media (max-width: 768px) {
        .container-fluid { padding-left:.7rem !important; padding-right:.7rem !important; }
        .page-hero { padding:.9rem; }
        .page-hero .btn { width:100%; margin-top:.35rem; }
        .vm-toolbar { display:block; }
        .vm-toolbar form { display:flex !important; width:100%; }
        .vm-search { min-width:0; width:100%; font-size:.86rem; }
        .vm-toolbar > .d-flex { margin-top:.7rem; gap:.35rem !important; }
        .vm-filter { font-size:.72rem; padding:.38rem .55rem; }
        .cluster-accordion .cluster-sync-info { display:flex !important; position:static !important; justify-content:flex-start !important; padding:.45rem .65rem .65rem !important; gap:.3rem !important; }
        .cluster-accordion .cluster-sync-info .sync-btn, .cluster-accordion .cluster-sync-info .audit-cluster-btn, .cluster-accordion .cluster-sync-info .audit-status-btn { flex:1 1 auto; min-width:0; font-size:.68rem !important; padding:.35rem .42rem !important; }
        .cluster-accordion .accordion-header { display:block; }
        .cluster-accordion .accordion-button { width:100%; }
        .cluster-accordion .cluster-sync-info .cluster-audit-summary { display:none; }
        .qemu-inventory-table, .qemu-inventory-table tbody { display:block; min-width:0 !important; width:100%; }
        .qemu-inventory-table thead { display:none; }
        .qemu-inventory-table tr.vm-row { display:block; width:100%; margin:.65rem 0; border:1px solid #2d4b5e; border-radius:12px; background:#101e2a; overflow:hidden; }
        .qemu-inventory-table tbody tr.vm-row:nth-child(even) td, .qemu-inventory-table tbody tr.vm-row td { background:#101e2a !important; border:0; border-bottom:1px solid #243d4c; display:flex; align-items:center; justify-content:space-between; gap:.7rem; width:100% !important; min-height:2.15rem; padding:.48rem .65rem; text-align:right !important; }
        .qemu-inventory-table tbody tr.vm-row td:last-child { border-bottom:0; }
        .qemu-inventory-table tbody tr.vm-row td::before { content:attr(data-label); color:#91aabd; font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; text-align:left; flex:0 0 38%; }
        .qemu-inventory-table tbody tr.vm-row td > * { max-width:60%; }
        .qemu-inventory-table .vm-name-main, .qemu-inventory-table .vm-name-sub { text-align:right; }
        .qemu-inventory-table .agents-cell .agent-stack, .qemu-inventory-table .service-stack { justify-content:flex-end; max-width:60%; }
        .qemu-inventory-table .agents-cell .agent-stack { flex-wrap:wrap; }
        .qemu-inventory-table .network-info-btn, .qemu-inventory-table .action-btn { margin-left:auto; }
        .qemu-inventory-table .storage-cell .badge-soft { max-width:100%; }
        .qemu-inventory-table .ip-os-cell { display:flex !important; flex-wrap:wrap; }
        .qemu-inventory-table .ip-os-cell::before { align-self:center; }
        .qemu-inventory-table .services-cell { min-height:3rem; }
        .qemu-inventory-table .services-cell .service-stack { line-height:1.35; }
        .qemu-inventory-table .text-center { text-align:right !important; }
        .health-panel { padding:.8rem; }
        .health-item-value { font-size:1.05rem; }
    }
    @media (max-width: 430px) {
        .page-title { font-size:1.55rem; }
        .cluster-pill { font-size:.7rem; padding:.28rem .45rem; }
        .cluster-accordion .cluster-sync-info { flex-wrap:wrap; }
        .cluster-accordion .cluster-sync-info .sync-btn, .cluster-accordion .cluster-sync-info .audit-cluster-btn, .cluster-accordion .cluster-sync-info .audit-status-btn { flex:1 1 30%; }
        .qemu-inventory-table tbody tr.vm-row td::before { flex-basis:34%; font-size:.62rem; }
        .qemu-inventory-table tbody tr.vm-row td > * { max-width:64%; }
    }
    .modern-panel { background:#0f1821; border:1px solid #1f3345; border-radius:18px; padding:1rem; color:#fff; box-shadow:0 12px 28px rgba(0,0,0,.18); }
    .vm-toolbar { display:flex; flex-wrap:wrap; justify-content:space-between; gap:.75rem; margin-bottom:1rem; }
    .vm-search { background:#0d141c; border:1px solid #324254; color:#e8eef5; border-radius:12px; padding:.65rem .85rem; min-width:340px; }
    .vm-search::placeholder { color:#7f8d9a; }
    .vm-filter { border:1px solid #344455; background:rgba(255,255,255,.04); color:#dce6ef; border-radius:999px; padding:.45rem .8rem; font-weight:750; font-size:.84rem; }
    .vm-filter.active { background:rgba(25,135,84,.25); border-color:rgba(25,135,84,.7); color:#94ebb9; }
    .cluster-accordion .accordion-item { background:#0f1821; border:1px solid #223346; border-radius:16px; overflow:hidden; margin-bottom:.85rem; }
    .cluster-accordion .accordion-button { background:#0c151d; color:#eaf2f8; box-shadow:none; padding:.85rem 1rem; }
    .cluster-accordion .accordion-button:not(.collapsed) { background:#102033; color:#fff; }
    .cluster-accordion .accordion-button::after { filter:invert(1); }
    .accordion-title { font-weight:850; }
    .sync-btn { border-radius:999px; font-size:.78rem; padding:.22rem .7rem; }
    .modern-table { --bs-table-bg:#0f1821; --bs-table-border-color:#223346; --bs-table-color:#e5edf4; border-radius:14px; overflow:hidden; }
    .modern-table thead th { background:#0c151d !important; color:#8fa3b7; font-size:.72rem; text-transform:uppercase; letter-spacing:.07em; border-bottom:1px solid #344455; white-space:nowrap; }
    .modern-table tbody td { background:#0f1821 !important; border-color:#223346; vertical-align:middle; }
    .modern-table tbody tr:nth-child(even) td { background:#101a24 !important; }
    .modern-table tbody tr:hover td { background:#142536 !important; }
    .vm-name-main { font-weight:850; color:#fff; }
    .vm-name-sub { color:#8c9baa; font-size:.78rem; }
    .badge-soft { border:1px solid #344455; background:rgba(255,255,255,.06); color:#dbe5ee; border-radius:999px; padding:.3rem .55rem; font-size:.74rem; font-weight:800; }
    .badge-soft.running,.badge-soft.on { border-color:rgba(25,135,84,.65); background:rgba(25,135,84,.18); color:#91e7b4; }
    .badge-soft.stopped,.badge-soft.off { border-color:rgba(108,117,125,.65); background:rgba(108,117,125,.18); color:#c9d0d6; }
    .badge-soft.down { border-color:rgba(220,53,69,.65); background:rgba(220,53,69,.18); color:#ff9ca8; }
    .badge-soft.blue { border-color:rgba(13,110,253,.65); background:rgba(13,110,253,.18); color:#99c2ff; }
    .action-btn { border-radius:10px; font-weight:750; }
    .agent-stack { display:inline-flex; align-items:center; justify-content:center; gap:.35rem; flex-wrap:wrap; }
    .agent-sep { color:#536779; font-weight:900; }
    .vmid-pill { display:inline-flex; justify-content:center; min-width:54px; }
    .table-small-text { font-size:.78rem; color:#a7b4c1; }
    .vm-empty-filter { display:none; color:#aeb9c5; text-align:center; padding:1rem; }
    .modal-content.modern-modal { background:#0f1821; border:1px solid #2d4053; color:#fff; border-radius:16px; }
</style>
<style>
.qemu-inventory-table { font-size: .78rem; table-layout: fixed; min-width: 1420px; }
.qemu-inventory-table th, .qemu-inventory-table td { padding: .42rem .28rem; }
.qemu-inventory-table th:nth-child(1), .qemu-inventory-table td:nth-child(1) { width: 5%; }
.qemu-inventory-table th:nth-child(2), .qemu-inventory-table td:nth-child(2) { width: 14%; }
.qemu-inventory-table th:nth-child(3), .qemu-inventory-table td:nth-child(3) { width: 8%; }
.qemu-inventory-table th:nth-child(4), .qemu-inventory-table td:nth-child(4) { width: 15%; }
.qemu-inventory-table th:nth-child(5), .qemu-inventory-table td:nth-child(5) { width: 21%; }
.qemu-inventory-table th:nth-child(6), .qemu-inventory-table td:nth-child(6) { width: 7%; }
.qemu-inventory-table th:nth-child(7), .qemu-inventory-table td:nth-child(7) { width: 5%; }
.qemu-inventory-table th:nth-child(8), .qemu-inventory-table td:nth-child(8) { width: 7%; }
.qemu-inventory-table th:nth-child(9), .qemu-inventory-table td:nth-child(9) { width: 7%; }
.qemu-inventory-table th:nth-child(10), .qemu-inventory-table td:nth-child(10) { width: 11%; }
.qemu-inventory-table th:nth-child(11), .qemu-inventory-table td:nth-child(11) { width: 5%; }
.qemu-inventory-table th:nth-child(12), .qemu-inventory-table td:nth-child(12) { width: 5%; }
.network-info-btn { width: 28px; height: 28px; padding: 0; border-radius: 50%; font-weight: 800; flex:0 0 auto; }
.network-summary { color:#91a9b8; font-size:.72rem; }
.network-card { min-height: 118px; padding: .85rem; border: 1px solid #2d4053; border-radius: 14px; background: linear-gradient(145deg, rgba(255,255,255,.055), rgba(255,255,255,.018)); }
.network-card-secondary { border-color: rgba(67,186,255,.38); }
.network-card-backup { border-color: rgba(0,185,129,.38); }
.network-card-vip { border-color: rgba(255,193,7,.42); }
.network-card-icon { float:right; width:28px; height:28px; display:grid; place-items:center; border-radius:9px; background:rgba(255,255,255,.08); color:#8bdcff; }
.network-card-backup .network-card-icon { color:#8ff0bd; }
.network-card-vip .network-card-icon { color:#ffe29a; }
.network-card-title { color:#dce6ef; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; margin-bottom:.7rem; }
.network-list { display:flex; flex-wrap:wrap; gap:.35rem; min-height:24px; align-items:flex-start; }
.network-list .ip-chip { margin:0; }
.ip-extra-cell { vertical-align: middle; white-space: normal; }
.ip-chip { display: inline-block; padding: .18rem .35rem; margin: .08rem; border: 1px solid rgba(255,255,255,.18); border-radius: .35rem; font-size: .7rem; white-space: nowrap; }
.ip-chip.secondary { color: #d6e8ff; background: rgba(67,186,255,.10); }
.ip-chip.backup { color: #b9f4d6; background: rgba(0,185,129,.12); }
.ip-chip.vip { color: #ffe3a1; background: rgba(255,193,7,.12); }
.empty-ip { color: #718096; }
.agents-cell { white-space: normal !important; padding:.32rem .25rem !important; vertical-align:middle; }
.agents-cell .agent-stack { display:flex; width:100%; max-width:100%; gap:.2rem; transform:none; justify-content:center; align-content:center; flex-wrap:wrap; overflow:visible; }
.agents-cell .agent-sep { display:none; }
.agents-cell .badge-soft { font-size: .62rem; line-height:1.15; padding: .16rem .27rem; white-space: nowrap; margin:0; }
.qemu-inventory-table td:nth-child(10) .badge-soft { display:inline-block; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
@media (max-width: 1200px) { .qemu-inventory-table { min-width: 1420px; } }

.qemu-inventory-table { min-width: 1510px; }
.qemu-inventory-table th:nth-child(6), .qemu-inventory-table td:nth-child(6) { width: 11%; }

.qemu-inventory-table th:nth-child(7), .qemu-inventory-table td:nth-child(7) { width: 11%; }
.qemu-inventory-table th:nth-child(8), .qemu-inventory-table td:nth-child(8) { width: 6%; }
.qemu-inventory-table th:nth-child(9), .qemu-inventory-table td:nth-child(9) { width: 5%; }
.qemu-inventory-table th:nth-child(10), .qemu-inventory-table td:nth-child(10) { width: 7%; }
.qemu-inventory-table th:nth-child(11), .qemu-inventory-table td:nth-child(11) { width: 12%; }
.qemu-inventory-table th:nth-child(12), .qemu-inventory-table td:nth-child(12) { width: 7%; }
.qemu-inventory-table th:nth-child(13), .qemu-inventory-table td:nth-child(13) { width: 5%; }
.qemu-inventory-table th:nth-child(14), .qemu-inventory-table td:nth-child(14) { width: 7%; }
.qemu-inventory-table .storage-cell { overflow:hidden; }
.qemu-inventory-table .storage-cell .badge-soft { display:inline-block; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; vertical-align:middle; }

.services-cell { white-space:normal !important; padding:.35rem .3rem !important; }
.service-stack { display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:.22rem; min-height:28px; line-height:1.1; }
.service-stack .badge-soft { display:inline-flex; align-items:center; line-height:1.1; white-space:nowrap; margin:0; }
.qemu-inventory-table th:nth-child(5), .qemu-inventory-table td:nth-child(5) { width: 19%; }
.qemu-inventory-table th:nth-child(6), .qemu-inventory-table td:nth-child(6) { width: 15%; }
.qemu-inventory-table th:nth-child(7), .qemu-inventory-table td:nth-child(7) { width: 10%; }
</style>


@php
    $allVms = $groupedQemus->flatten(1);
    $totalVms = $allVms->count();
    $runningVms = $allVms->where('status', 'running')->count();
    $stoppedVms = max($totalVms - $runningVms, 0);
    $clustersCount = $groupedQemus->count();
    $qgaOn = $allVms->filter(function($vm) { return $vm->qga_enabled_config && $vm->status === 'running' && !empty($vm->ip_address); })->count();
    $zabbixOn = $allVms->whereIn('zabbix_status', ['active', 'running'])->count();
    $wazuhOn = $allVms->whereIn('wazuh_status', ['active', 'running'])->count();
    $phpInstalledVms = $allVms->filter(function ($vm) {
        $services = is_array($vm->infransible_services ?? null) ? $vm->infransible_services : (json_decode((string) ($vm->infransible_services ?? ''), true) ?: []);
        return !empty($services['php']['installed']);
    })->count();
    $osText = function ($vm) { return strtolower(trim((string) ($vm->os_name ?? ''))); };
    $windowsVms = $allVms->filter(function ($vm) use ($osText) { $os = $osText($vm); return $os !== '' && (str_contains($os, 'windows') || str_contains($os, 'window ') || str_contains($os, 'win ')); })->count();
    $linuxVms = $allVms->filter(function ($vm) use ($osText) { $os = $osText($vm); return $os !== '' && preg_match('/linux|debian|ubuntu|centos|rhel|rocky|alma|opensuse|suse|fedora/', $os); })->count();
    $obsoleteVms = $allVms->filter(function ($vm) use ($osText) {
        $os = $osText($vm);
        if ($os === '') return false;
        return preg_match('/\bwindows?\s+(?:server\s+)?(?:2003|2008|2012)(?:\s+r2)?\b/i', $os)
            || preg_match('/\bdebian(?:\s+gnu\/linux)?\s+(?:9|10)(?:\.|\s|$)/i', $os)
            || preg_match('/\bubuntu\s+(?:16|18)(?:\.|\s|$)/i', $os)
            || preg_match('/\bcentos(?:\s+linux)?\s+(?:6|7|8)(?:\.|\s|$)/i', $os)
            || preg_match('/\bopensuse(?:\s+leap)?\s+15\.(?:1|3)(?:\.|\s|$)/i', $os);
    })->count();
    $infransibleOk = $allVms->where('infransible_status', 'ok')->count();
    $withoutIp = $allVms->filter(fn($vm) => empty($vm->ip_address))->count();
    $withoutOs = $allVms->filter(fn($vm) => empty($vm->os_name))->count();
    $auditStale = $allVms->filter(function($vm) { return empty($vm->infransible_checked_at) || now()->diffInDays(\Carbon\Carbon::parse($vm->infransible_checked_at)) > 7; })->count();
    $infransibleErrors = $allVms->whereIn('infransible_status', ['error', 'auth_failed'])->count();
    $serviceErrors = $allVms->filter(function($vm) { $services = is_array($vm->infransible_services ?? null) ? $vm->infransible_services : (json_decode((string) ($vm->infransible_services ?? ''), true) ?: []); return collect($services)->contains(fn($service) => !empty($service['installed']) && ($service['status'] ?? '') === 'failed'); })->count();
@endphp

<div class="container-fluid mt-3 px-4">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    @endif
</div>

<div class="container-fluid mt-4 px-4">
    <div class="page-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="cluster-pill success">● {{ $runningVms }} running</span>
                    <span class="cluster-pill">{{ $clustersCount }} cluster(s)</span>
                    <span class="cluster-pill">{{ $totalVms }} VM(s)</span>
                </div>
                <h1 class="page-title">Máquinas Virtuales</h1>
                <div class="page-subtitle">Inventario global de VMs, agentes, recursos y última sincronización.</div>
            </div>
            <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-end">
                @can('view history')
                    <a href="{{ route('proxmox.history') }}" class="btn btn-outline-light"><i class="bi bi-clock-history"></i> Historial de cambios</a>
                @endcan
                @can('view vms')
                    <a href="{{ route('proxmox.exportQemu') }}" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Exportar a Excel</a>
                @endcan
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">VMs</div><div class="metric-value">{{ $totalVms }}</div><div class="metric-foot">Total inventariado</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Running</div><div class="metric-value text-success">{{ $runningVms }}</div><div class="metric-foot">En ejecución</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Apagadas</div><div class="metric-value {{ $stoppedVms > 0 ? 'text-warning' : 'text-success' }}">{{ $stoppedVms }}</div><div class="metric-foot">Stopped/offline</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">QGA OK</div><div class="metric-value">{{ $qgaOn }}</div><div class="metric-foot">Con IP reportada</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Zabbix ON</div><div class="metric-value">{{ $zabbixOn }}</div><div class="metric-foot">Agente activo</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Wazuh ON</div><div class="metric-value">{{ $wazuhOn }}</div><div class="metric-foot">Agente activo</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">PHP instalado</div><div class="metric-value text-info">{{ $phpInstalledVms }}</div><div class="metric-foot">Runtime detectado</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Infransible OK</div><div class="metric-value text-success">{{ $infransibleOk }}</div><div class="metric-foot">Autenticación válida</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Windows</div><div class="metric-value">{{ $windowsVms }}</div><div class="metric-foot">SO identificado</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Linux</div><div class="metric-value">{{ $linuxVms }}</div><div class="metric-foot">SO identificado</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">SO obsoleto</div><div class="metric-value text-warning">{{ $obsoleteVms }}</div><div class="metric-foot">Requiere revisión</div></div></div>
    </div>

    <div class="health-panel mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div><div class="health-title"><i class="bi bi-heart-pulse"></i> Panel de salud del inventario</div><div class="health-subtitle">Indicadores calculados sobre las VMs actualmente cargadas en esta vista.</div></div>
            <span class="health-subtitle">Auditoría de servicios independiente de la sincronización API</span>
        </div>
        <div class="row g-2">
            <div class="col-xl-2 col-md-4 col-6"><div class="health-item"><div class="health-item-label">Completitud IP</div><div class="health-item-value {{ $withoutIp === 0 ? 'health-ok' : 'health-warn' }}">{{ $totalVms - $withoutIp }}/{{ $totalVms }}</div><div class="health-item-foot">{{ $withoutIp }} sin IP</div></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="health-item"><div class="health-item-label">Completitud SO</div><div class="health-item-value {{ $withoutOs === 0 ? 'health-ok' : 'health-warn' }}">{{ $totalVms - $withoutOs }}/{{ $totalVms }}</div><div class="health-item-foot">{{ $withoutOs }} sin SO</div></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="health-item"><div class="health-item-label">Auditoría reciente</div><div class="health-item-value {{ $auditStale === 0 ? 'health-ok' : 'health-warn' }}">{{ $totalVms - $auditStale }}/{{ $totalVms }}</div><div class="health-item-foot">{{ $auditStale }} con más de 7 días</div></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="health-item"><div class="health-item-label">Infransible</div><div class="health-item-value {{ $infransibleErrors === 0 ? 'health-ok' : 'health-danger' }}">{{ $infransibleOk }}/{{ $totalVms }}</div><div class="health-item-foot">{{ $infransibleErrors }} con error</div></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="health-item"><div class="health-item-label">Servicios fallidos</div><div class="health-item-value {{ $serviceErrors === 0 ? 'health-ok' : 'health-danger' }}">{{ $serviceErrors }}</div><div class="health-item-foot">VMs con servicios failed</div></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="health-item"><div class="health-item-label">Operativas</div><div class="health-item-value {{ $runningVms > 0 ? 'health-ok' : 'health-warn' }}">{{ $runningVms }}/{{ $totalVms }}</div><div class="health-item-foot">VMs en running</div></div></div>
        </div>
    </div>

    <div class="modern-panel">
        <div class="vm-toolbar">
            <form action="{{ url('/proxmox/qemu') }}" method="GET" class="d-flex gap-2 flex-wrap">
                <input id="vmSearch" type="search" class="vm-search" placeholder="Buscar por nombre, VMID, nodo, IP, OS o storage..." name="search" value="{{ request('search') }}">
                <button class="btn btn-outline-info" type="submit">Buscar</button>
                @if(request('search'))<a class="btn btn-outline-light" href="{{ url('/proxmox/qemu') }}">Limpiar</a>@endif
            </form>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="vm-filter active" data-filter="all">Todas</button>
                <button type="button" class="vm-filter" data-filter="running">Running</button>
                <button type="button" class="vm-filter" data-filter="stopped">Apagadas</button>
                <button type="button" class="vm-filter" data-filter="qga">QGA OK</button>
                <button type="button" class="vm-filter" data-filter="zabbixoff">Zabbix OFF</button>
                <button type="button" class="vm-filter" data-filter="wazuhoff">Wazuh OFF</button>
                <button type="button" class="vm-filter" data-filter="noip">Sin IP</button>
                <button type="button" class="vm-filter" data-filter="noos">Sin SO</button>
                <button type="button" class="vm-filter" data-filter="infransbleno">Sin INF OK</button>
                <button type="button" class="vm-filter" data-filter="linux">Linux</button>
                <button type="button" class="vm-filter" data-filter="windows">Windows</button>
            </div>
        </div>

        <div class="accordion cluster-accordion" id="accordionQemus">
            @forelse ($groupedQemus as $clusterName => $vms)
                @php
                    $clusterSlug = Str::slug($clusterName ?: 'sin-cluster');
                    $clusterRunning = $vms->where('status', 'running')->count();
                    $clusterLastUpdate = ($clusterLastUpdates ?? collect())->get($clusterName);
                @endphp
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-{{ $clusterSlug }}">
                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $clusterSlug }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="collapse-{{ $clusterSlug }}">
                            <div class="d-flex flex-wrap align-items-center gap-2 w-100 pe-5">
                                <span class="accordion-title"><i class="bi bi-hdd-stack me-2"></i>{{ $clusterName ?: 'Sin Cluster' }}</span>
                                <span class="cluster-pill">{{ $vms->count() }} VMs</span>
                                <span class="cluster-pill success">{{ $clusterRunning }} running</span>
                            </div>
                        </button>
                        @if($clusterName)
                            <div class="cluster-sync-info position-absolute end-0 me-5">
                                <span class="cluster-sync-label"><i class="bi bi-clock-history"></i> Última actualización: {{ $clusterLastUpdate ? \Carbon\Carbon::parse($clusterLastUpdate)->format('d/m/Y H:i') : 'Sin dato' }}</span>
                                @can('run sync')
                                    <a href="{{ route('proxmox.sync.cluster', ['name' => $clusterName]) }}" class="btn btn-sm btn-outline-info sync-btn" onclick="event.preventDefault(); event.stopPropagation(); var btn=this; btn.innerHTML='<i class=\'bi bi-arrow-repeat spinner-border spinner-border-sm\'></i> Procesando...'; btn.classList.add('disabled'); btn.style.opacity='0.6'; window.location.href=btn.href;"><i class="bi bi-arrow-repeat"></i> Actualizar</a>
                                    <a href="#" role="button" class="btn btn-sm audit-cluster-btn" onclick="startClusterAudit(this, event); return false;" data-cluster="{{ $clusterName }}" data-start-url="{{ route('proxmox.audit.start', ['cluster' => '__CLUSTER__']) }}">
                                        <i class="bi bi-shield-check"></i> Auditar
                                    </a>
                                @endcan
                                @can('view vms')
                                    <a href="#" role="button" class="btn btn-sm audit-status-btn" onclick="showClusterAuditStatus(this, event); return false;" data-cluster="{{ $clusterName }}">
                                        <i class="bi bi-activity"></i> Estado
                                    </a>
                                @endcan
                                <span class="cluster-audit-summary" data-audit-summary="{{ $clusterSlug }}" title="Resumen de la última auditoría"></span>
                            </div>
                        @endif
                    </h2>
                    <div id="collapse-{{ $clusterSlug }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" aria-labelledby="heading-{{ $clusterSlug }}" data-bs-parent="#accordionQemus">
                        <div class="accordion-body p-0">
<div class="table-responsive">
                                <table class="table table-dark table-hover table-bordered align-middle modern-table qemu-inventory-table mb-0">
                                    <thead class="text-center">
                                        <tr>
                                            <th class="qemu-sort" data-sort-column="0">VMID</th><th class="qemu-sort" data-sort-column="1">Nombre</th><th class="qemu-sort" data-sort-column="2">Nodo</th><th class="qemu-sort" data-sort-column="3">IP / OS</th><th>Agentes</th><th>Servicios</th><th class="qemu-sort" data-sort-column="5">Estado</th><th class="qemu-sort" data-sort-column="6">vCPU</th><th class="qemu-sort" data-sort-column="7">RAM</th><th class="qemu-sort" data-sort-column="8">Disco</th><th class="qemu-sort" data-sort-column="9">Storage</th><th>Info</th><th>Auditoría</th><th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($vms as $qemu)
                                            @php
                                                $vmStatusFilter = strtolower($qemu->status) === 'running' ? 'running' : 'stopped';
                                                $qgaOk = $qemu->qga_enabled_config && $qemu->status === 'running' && !empty($qemu->ip_address);
                                                $auditAt = $qemu->infransible_checked_at ?? null;
                                                $dataAgeHours = $auditAt ? max(0, (int) \Carbon\Carbon::parse($auditAt)->diffInHours(now(), false)) : null;
                                                $dataAgeClass = $dataAgeHours === null ? 'unknown' : ($dataAgeHours <= 24 ? 'fresh' : ($dataAgeHours <= 168 ? 'stale' : 'old'));
                                                $dataAgeText = $dataAgeHours === null ? 'Sin dato' : ($dataAgeHours < 1 ? 'Ahora' : ($dataAgeHours < 24 ? 'Hace ' . $dataAgeHours . ' h' : 'Hace ' . floor($dataAgeHours / 24) . ' d'));
                                                $dataAgeTitle = $auditAt ? 'Última auditoría de servicios: ' . \Carbon\Carbon::parse($auditAt)->format('d/m/Y H:i:s') . '. No representa la sincronización de información por API.' : 'Sin fecha de auditoría de servicios registrada. No representa la sincronización de información por API.';
                                                $zabbixStatus = strtolower((string) ($qemu->zabbix_status_infransible ?? $qemu->zabbix_status ?? ''));
                                                $wazuhStatus = strtolower((string) ($qemu->wazuh_status_infransible ?? $qemu->wazuh_status ?? ''));
                                                $osLower = strtolower(trim((string) ($qemu->os_name ?? '')));
                                                    $osFamily = preg_match('/windows|window |win /', $osLower) ? 'windows' : (preg_match('/linux|debian|ubuntu|centos|rhel|rocky|alma|opensuse|suse|fedora/', $osLower) ? 'linux' : 'other');
                                                $compactVersion = function ($version) {
                                                    $version = trim((string) $version);
                                                    if ($version === '') return '';
                                                    if (preg_match('/(\d+)\.(\d+)\.(\d+)/', $version, $m)) return $m[1] . '.' . $m[2] . '.' . $m[3];
                                                    if (preg_match('/(\d+)\.(\d+)/', $version, $m)) return $m[1] . '.' . $m[2];
                                                    return $version;
                                                };
                                                $zabbixVersionShort = $compactVersion($qemu->zabbix_version_infransible ?? $qemu->zabbix_version ?? '');
                                                $wazuhVersionShort = $compactVersion($qemu->wazuh_version_infransible ?? $qemu->wazuh_version ?? '');
                                                 $infransibleServices = is_array($qemu->infransible_services ?? null) ? $qemu->infransible_services : (json_decode((string) ($qemu->infransible_services ?? ''), true) ?: []);
                                                $zabbixOk = in_array($zabbixStatus, ['active', 'running'], true);
                                                $wazuhOk = in_array($wazuhStatus, ['active', 'running'], true);
                                                $diskText = ($qemu->size >= 1099511627776) ? round($qemu->size / 1099511627776, 1) . ' TB' : round($qemu->size / 1073741824, 0) . ' GB';
                                                $ramText = round($qemu->maxmem / 1073741824, 0) . ' GB';
                                                $vmSearchText = strtolower(($qemu->name ?? '') . ' ' . $qemu->vmid . ' ' . str_replace('node/', '', $qemu->node_id) . ' ' . ($qemu->ip_address ?? '') . ' ' . ($qemu->os_name ?? '') . ' ' . ($qemu->storageName ?? '') . ' ' . ($clusterName ?? ''));
                                                $secondaryIps = $qemu->networkAddresses->where('network_type', 'secondary');
                                                $backupIps = $qemu->networkAddresses->whereIn('network_type', ['backup', 'veeam']);
                                                $vipIps = $qemu->networkAddresses->where('network_type', 'vip');
                                                $publicIps = $qemu->networkAddresses->where('network_type', 'public');
                                            @endphp
                                            <tr class="vm-row" data-status="{{ $vmStatusFilter }}" data-qga="{{ $qgaOk ? '1' : '0' }}" data-zabbix="{{ $zabbixOk ? '1' : '0' }}" data-wazuh="{{ $wazuhOk ? '1' : '0' }}" data-infransible="{{ $qemu->infransible_status === 'ok' ? '1' : '0' }}" data-noip="{{ empty($qemu->ip_address) ? '1' : '0' }}" data-noos="{{ empty($qemu->os_name) ? '1' : '0' }}" data-os-family="{{ $osFamily }}" data-search="{{ e($vmSearchText) }}">
                                                <td data-label="VMID" class="text-center"><span class="badge-soft vmid-pill">{{ $qemu->vmid }}</span></td>
                                                <td data-label="Nombre"><div class="vm-name-main">{{ $qemu->name }}</div></td>
                                                <td data-label="Nodo" class="text-center">{{ str_replace('node/', '', $qemu->node_id) }}</td>
                                                <td data-label="IP / SO" class="text-center ip-os-cell">
                                                    <div class="vm-name-main">{{ $qemu->ip_address ?: '-' }}</div>
                                                    <div class="vm-name-sub" title="{{ $qemu->os_name }}">{{ Str::limit($qemu->os_name ?: '-', 28) }}</div>
                                                </td>
                                                <td data-label="Agentes" class="text-center agents-cell">
                                                    <div class="agent-stack">
                                                        @if (!$qemu->qga_enabled_config)<span class="badge-soft off">QGA OFF</span>@elseif($qgaOk)<span class="badge-soft on">QGA ON</span>@elseif($qemu->status != 'running')<span class="badge-soft off">QGA Stop</span>@else<span class="badge-soft down">QGA Down</span>@endif
                                                        <span class="agent-sep">|</span>
                                                        @php($agentCheckedTitle = $auditAt ? 'Última auditoría Infransible: ' . \Carbon\Carbon::parse($auditAt)->format('d/m/Y H:i:s') : 'Sin fecha de auditoría Infransible')
                                                        @if($qemu->infransible_status === 'ok')<span class="badge-soft on" title="SSH autenticado. {{ $agentCheckedTitle }}">INF OK</span>@elseif(in_array($qemu->infransible_status, ['error', 'auth_failed'], true))<span class="badge-soft down" title="{{ $qemu->infransible_note ?: 'Falló la auditoría Infransible' }}. {{ $agentCheckedTitle }}">INF ERR</span>@else<span class="badge-soft off" title="{{ $agentCheckedTitle }}">INF ?</span>@endif
                                                        <span class="agent-sep">|</span>
                                                        @if($zabbixOk)<span class="badge-soft on" title="{{ $agentCheckedTitle }}">ZBX {{ $zabbixVersionShort ?: 'OK' }}</span>@elseif(in_array($zabbixStatus, ['inactive', 'stopped', 'installed_inactive'], true))<span class="badge-soft down" title="Zabbix instalado pero no activo. {{ $agentCheckedTitle }}">ZBX OFF</span>@elseif($zabbixStatus === 'failed')<span class="badge-soft down" title="Zabbix con fallo. {{ $agentCheckedTitle }}">ZBX FAIL</span>@elseif($zabbixStatus === 'not_installed')<span class="badge-soft off" title="Zabbix no instalado. {{ $agentCheckedTitle }}">ZBX -</span>@else<span class="badge-soft off" title="Sin estado actualizado. {{ $agentCheckedTitle }}">ZBX ?</span>@endif
                                                        <span class="agent-sep">|</span>
                                                        @if($wazuhOk)<span class="badge-soft blue" title="{{ $agentCheckedTitle }}">WZH {{ $wazuhVersionShort ?: 'OK' }}</span>@elseif(in_array($wazuhStatus, ['inactive', 'stopped', 'installed_inactive'], true))<span class="badge-soft down" title="Wazuh instalado pero no activo. {{ $agentCheckedTitle }}">WZH OFF</span>@elseif($wazuhStatus === 'failed')<span class="badge-soft down" title="Wazuh con fallo. {{ $agentCheckedTitle }}">WZH FAIL</span>@elseif($wazuhStatus === 'not_installed')<span class="badge-soft off" title="Wazuh no instalado. {{ $agentCheckedTitle }}">WZH -</span>@else<span class="badge-soft off" title="Sin estado actualizado. {{ $agentCheckedTitle }}">WZH ?</span>@endif
                                                        <span class="agent-sep">|</span>
                                                    </div>
                                                </td>
                                                <td data-label="Servicios" class="text-center services-cell">
                                                    @php($serviceNames = ['php' => 'PHP', 'apache' => 'Apache', 'nginx' => 'Nginx', 'php-fpm' => 'PHP-FPM', 'asterisk' => 'Asterisk', 'vicidial-manager' => 'VICIdial Manager', 'vicidial-autodial' => 'VICIdial Autodial', 'vicidial-remote' => 'VICIdial Remote', 'vicidial-adapt' => 'VICIdial Adapt', 'vicidial-hopper' => 'VICIdial Hopper', 'vicidial-cron' => 'VICIdial Cron', 'mysql' => 'MySQL', 'mariadb' => 'MariaDB', 'postgresql' => 'PostgreSQL', 'redis' => 'Redis', 'keepalived' => 'Keepalived', 'haproxy' => 'HAProxy', 'isc-dhcp-server' => 'ISC DHCP', 'kea-dhcp4-server' => 'Kea DHCPv4', 'kea-dhcp6-server' => 'Kea DHCPv6'])
                                                    @php($installedServices = collect($infransibleServices)->filter(fn($svc) => !empty($svc['installed']) && in_array($svc['status'] ?? 'not_installed', ['active', 'inactive', 'installed_inactive', 'failed'], true)) )
                                                    <div class="service-stack">
                                                        @forelse($installedServices as $serviceKey => $service)
                                                            @php($svcStatus = $service['status'] ?? 'unknown')
                                                            @php($svcSince = $service['active_since'] ?? null)
                                                            @php($svcUptime = $service['uptime_seconds'] ?? null)
                                                            @php($svcUptimeText = $svcUptime !== null ? \Carbon\CarbonInterval::seconds((int) $svcUptime)->cascade()->forHumans(['short' => true]) : null)
                                                            @php($svcLabel = $serviceNames[$serviceKey] ?? strtoupper($serviceKey))
                                                            @php($svcVersion = $service['version'] ?? null)
                                                            @php($svcClass = $svcStatus === 'active' ? 'on' : (in_array($svcStatus, ['failed', 'restarting'], true) ? 'down' : ($serviceKey === 'php' && !empty($service['installed']) ? 'blue' : 'off')))
                                                            <span class="badge-soft {{ $svcClass }}" title="{{ $svcLabel . ' · ' . $svcStatus . ($svcVersion ? ' · ' . $svcVersion : '') . ($svcSince ? ' · iniciado: ' . $svcSince : '') . ($svcUptimeText ? ' · activo hace: ' . $svcUptimeText : '') . ($serviceKey === 'keepalived' && !empty($service['virtual_router_ids']) ? ' · VRID: ' . implode(',', $service['virtual_router_ids']) : '') }}">{{ $svcLabel }}{{ $svcVersion ? ' ' . $svcVersion : '' }}</span>
                                                        @empty
                                                            <span class="badge-soft off" title="Sin servicios instalados detectados o sin auditoría de servicios">-</span>
                                                        @endforelse
                                                    </div>
                                                </td>
                                                <td data-label="Estado" class="text-center"><span class="badge-soft {{ $vmStatusFilter }}">{{ $qemu->status }}</span></td>
                                                <td data-label="vCPU" class="text-center">{{ $qemu->maxcpu }}</td>
                                                <td data-label="RAM" class="text-center">{{ $ramText }}</td>
                                                <td data-label="Disco" class="text-center">{{ $diskText }}</td>
                                                <td data-label="Storage" class="text-center storage-cell"><span class="badge-soft" title="{{ $qemu->storageName }}">{{ Str::limit($qemu->storageName ?: 'S/D', 22) }}</span></td>
                                                <td data-label="Info" class="text-center">
                                                    <button type="button" class="btn btn-outline-info btn-sm network-info-btn" title="Información de red" data-bs-toggle="modal" data-bs-target="#vmNetworkModal"
                                                        data-vm-name="{{ e($qemu->name) }}" data-vm-id="{{ $qemu->vmid }}" data-audit-age-text="{{ e($dataAgeText) }}" data-audit-age-title="{{ e($dataAgeTitle) }}"
                                                        data-secondary-ips="{{ $secondaryIps->merge($publicIps)->map(fn($a) => ['ip' => $a->ip_address, 'description' => $a->description ?: 'IP secundaria'])->values()->toJson() }}"
                                                        data-backup-ips="{{ $backupIps->map(fn($a) => ['ip' => $a->ip_address, 'description' => $a->description ?: 'Red de backup Veeam'])->values()->toJson() }}"
                                                        data-vip-ips="{{ $vipIps->map(fn($a) => ['ip' => $a->ip_address, 'description' => $a->description ?: 'VIP compartida'])->values()->toJson() }}"><i class="bi bi-info-lg"></i></button>
                                                </td>
                                                <td data-label="Auditoría" class="text-center">@can('run sync')<a href="#" role="button" class="btn btn-sm audit-vm-btn" onclick="startVmAudit(this, event); return false;" data-start-url="{{ route('proxmox.audit.vm.start', ['qemu' => $qemu->id]) }}"><i class="bi bi-shield-check"></i> VM</a>@else<span class="text-muted">-</span>@endcan</td>
                                                <td data-label="Acción" class="text-center">@can('manage cluster')<button type="button" class="btn btn-warning btn-sm action-btn" data-bs-toggle="modal" data-bs-target="#editIpModal" data-qemu-id="{{ $qemu->id }}" data-qemu-name="{{ $qemu->name }}" data-ip-address="{{ $qemu->ip_address }}" data-os-name="{{ $qemu->os_name }}" data-ssh-port="{{ (int) ($qemu->ssh_port ?: 22) }}" data-ip-source="{{ $qemu->ip_os_source }}"><i class="bi bi-pencil"></i></button>@else<span class="text-muted">-</span>@endcan</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-warning text-center">No se encontraron máquinas virtuales.</div>
            @endforelse
        </div>
        <div id="vmEmptyFilter" class="vm-empty-filter">No hay VMs que coincidan con el filtro aplicado.</div>
    </div>
</div>

<div class="modal fade" id="vmNetworkModal" tabindex="-1" aria-labelledby="vmNetworkModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content modern-modal">
      <div class="modal-header border-secondary"><h5 class="modal-title" id="vmNetworkModalLabel">Información de red</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body">
        <div class="mb-3"><strong id="networkVmName"></strong> <span class="network-summary" id="networkVmId"></span></div>
        <div class="audit-info-card mb-3"><div class="d-flex align-items-center gap-2"><i class="bi bi-clock-history"></i><strong>Auditoría de servicios</strong><span id="auditAgeText" class="qemu-data-age unknown">Sin dato</span></div><div id="auditAgeTitle" class="network-summary mt-1">La antigüedad corresponde a la última auditoría de servicios; no representa la sincronización de información por API.</div></div>
        <div class="row g-3">
          <div class="col-md-4"><div class="network-card network-card-secondary"><div class="network-card-icon"><i class="bi bi-diagram-3"></i></div><div class="network-card-title">IPs secundarias</div><div id="secondaryIpList" class="network-list"></div></div></div>
          <div class="col-md-4"><div class="network-card network-card-backup"><div class="network-card-icon"><i class="bi bi-shield-check"></i></div><div class="network-card-title">IP Veeam</div><div id="backupIpList" class="network-list"></div></div></div>
          <div class="col-md-4"><div class="network-card network-card-vip"><div class="network-card-icon"><i class="bi bi-share"></i></div><div class="network-card-title">IP VIP</div><div id="vipIpList" class="network-list"></div></div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editIpModal" tabindex="-1" aria-labelledby="editIpModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content modern-modal">
      <div class="modal-header border-secondary"><h5 class="modal-title" id="editIpModalLabel">Editar Máquina Virtual</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <form id="editIpForm" method="POST" action="">@csrf
        <div class="modal-body">
            <p>Máquina Virtual: <strong id="modalVmName"></strong></p>
            <div class="mb-3"><label for="ip_address" class="form-label">Dirección IP</label><input type="text" class="form-control" id="ip_address" name="ip_address" placeholder="Ej: 192.168.1.10"></div>
            <div class="mb-3"><label for="os_name" class="form-label">Sistema Operativo</label><input type="text" class="form-control" id="os_name" name="os_name" placeholder="Ej: Ubuntu 22.04 LTS / Windows Server 2022"></div>
            <div class="mb-3"><label for="ssh_port" class="form-label">Puerto SSH para Infransible/Wazuh</label><input type="number" class="form-control" id="ssh_port" name="ssh_port" min="1" max="65535" value="22" required><small class="text-muted">Por defecto es 22. Solo cambiar si esta VM escucha SSH en otro puerto.</small></div>
            <small class="text-muted">Se guardará como fuente Manual. Si QGA entrega datos en una próxima sincronización, pasará automáticamente a QGA.</small>
        </div>
        <div class="modal-footer border-secondary"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const vmNetworkModal = document.getElementById('vmNetworkModal');
    if (vmNetworkModal) {
        vmNetworkModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('networkVmName').textContent = button.dataset.vmName || 'Máquina virtual';
            document.getElementById('networkVmId').textContent = button.dataset.vmId ? '· VMID ' + button.dataset.vmId : '';
            const auditAge = document.getElementById('auditAgeText');
            auditAge.textContent = button.dataset.auditAgeText || 'Sin dato';
            auditAge.title = button.dataset.auditAgeTitle || '';
            auditAge.className = 'qemu-data-age ' + (button.dataset.auditAgeText === 'Sin dato' ? 'unknown' : 'fresh');
            document.getElementById('auditAgeTitle').textContent = button.dataset.auditAgeTitle || 'Sin fecha de auditoría de servicios registrada. No representa la sincronización de información por API.';
            const renderList = (id, raw, type) => {
                const target = document.getElementById(id);
                target.replaceChildren();
                let values = [];
                try { values = JSON.parse(raw || '[]'); } catch (e) { values = []; }
                if (!values.length) { const empty = document.createElement('span'); empty.className = 'empty-ip'; empty.textContent = 'Sin datos'; target.appendChild(empty); return; }
                values.forEach(item => { const chip = document.createElement('span'); chip.className = 'ip-chip ' + type; chip.title = item.description || ''; chip.textContent = item.ip || '-'; target.appendChild(chip); });
            };
            renderList('secondaryIpList', button.dataset.secondaryIps, 'secondary');
            renderList('backupIpList', button.dataset.backupIps, 'backup');
            renderList('vipIpList', button.dataset.vipIps, 'vip');
        });
    }

    const editIpModal = document.getElementById('editIpModal');
    if(editIpModal) {
        editIpModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const qemuId = button.getAttribute('data-qemu-id');
            const qemuName = button.getAttribute('data-qemu-name');
            const ipAddress = button.getAttribute('data-ip-address');
            const osName = button.getAttribute('data-os-name');
            const sshPort = button.getAttribute('data-ssh-port') || '22';
            const modalForm = document.getElementById('editIpForm');
            document.getElementById('modalVmName').textContent = qemuName;
            document.getElementById('ip_address').value = ipAddress === 'N/A' ? '' : (ipAddress || '');
            document.getElementById('os_name').value = osName === 'N/A' ? '' : (osName || '');
            document.getElementById('ssh_port').value = sshPort;
            let url = '{{ route("proxmox.qemu.update_ip", ["qemuId" => ":id"]) }}';
            modalForm.setAttribute('action', url.replace(':id', qemuId));
        });
    }

    const vmSearch = document.getElementById('vmSearch');
    const vmFilters = document.querySelectorAll('.vm-filter');
    const vmRows = document.querySelectorAll('.vm-row');
    const vmEmptyFilter = document.getElementById('vmEmptyFilter');
    const activeVmFilters = new Set();


    function applyVmFilters() {
        const query = (vmSearch?.value || '').toLowerCase().trim();
        let visible = 0;
        vmRows.forEach(row => {
            const matchesSearch = !query || (row.dataset.search || '').includes(query);
            const matchesFilter = [...activeVmFilters].every(filter => {
                if (filter === 'running') return row.dataset.status === 'running';
                if (filter === 'stopped') return row.dataset.status === 'stopped';
                if (filter === 'qga') return row.dataset.qga === '1';
                if (filter === 'zabbixoff') return row.dataset.zabbix !== '1';
                if (filter === 'wazuhoff') return row.dataset.wazuh !== '1';
                if (filter === 'noip') return row.dataset.noip === '1';
                if (filter === 'noos') return row.dataset.noos === '1';
                if (filter === 'infransbleno') return row.dataset.infransible !== '1';
                if (filter === 'linux' || filter === 'windows') return row.dataset.osFamily === filter;
                return true;
            });
            const show = matchesSearch && matchesFilter;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (vmEmptyFilter) vmEmptyFilter.style.display = visible === 0 && vmRows.length > 0 ? 'block' : 'none';
    }
    if (vmSearch) vmSearch.addEventListener('input', applyVmFilters);


    document.querySelectorAll('.qemu-inventory-table').forEach(table => {
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        const rows = Array.from(tbody.querySelectorAll('tr.vm-row'));
        table.querySelectorAll('th.qemu-sort').forEach(header => header.addEventListener('click', () => {
            const column = Number(header.dataset.sortColumn);
            const direction = header.dataset.sortDirection === 'asc' ? 'desc' : 'asc';
            table.querySelectorAll('th.qemu-sort').forEach(item => {
                item.dataset.sortDirection = '';
                item.classList.remove('sort-asc', 'sort-desc');
            });
            header.dataset.sortDirection = direction;
            header.classList.add(direction === 'asc' ? 'sort-asc' : 'sort-desc');
            rows.sort((a, b) => {
                const left = a.cells[column]?.textContent.trim() || '';
                const right = b.cells[column]?.textContent.trim() || '';
                const numericLeft = parseFloat(left.replace(',', '.'));
                const numericRight = parseFloat(right.replace(',', '.'));
                const result = Number.isNaN(numericLeft) || Number.isNaN(numericRight)
                    ? left.localeCompare(right, 'es', { numeric: true, sensitivity: 'base' })
                    : numericLeft - numericRight;
                return (direction === 'asc' ? 1 : -1) * result;
            });
            rows.forEach(row => tbody.appendChild(row));
        }));
    });
    vmFilters.forEach(btn => btn.addEventListener('click', () => {
        const filter = btn.dataset.filter || 'all';
        if (filter === 'all') {
            activeVmFilters.clear();
            vmFilters.forEach(b => b.classList.toggle('active', b.dataset.filter === 'all'));
        } else {
            activeVmFilters.has(filter) ? activeVmFilters.delete(filter) : activeVmFilters.add(filter);
            vmFilters.forEach(b => b.classList.toggle('active', b.dataset.filter === 'all' ? activeVmFilters.size === 0 : activeVmFilters.has(b.dataset.filter)));
        }
        applyVmFilters();
    }));
});
</script>

<script>
window.showClusterAuditStatus = async function(btn, event) {
    if (event) { event.preventDefault(); event.stopPropagation(); }
    const cluster = btn.dataset.cluster;
    const base = '{{ url('/proxmox/qemu/audit') }}';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const esc = value => String(value ?? '-').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    let modal = document.getElementById('clusterAuditMonitor');
    if (!modal) { modal = document.createElement('div'); modal.id='clusterAuditMonitor'; modal.className='audit-monitor-backdrop'; document.body.appendChild(modal); }
    modal.innerHTML = '<div class="audit-monitor-card"><div class="audit-monitor-head"><strong><i class="bi bi-shield-check"></i> Auditoría: '+esc(cluster)+'</strong><button class="audit-monitor-close" type="button" aria-label="Cerrar">&times;</button></div><div class="audit-monitor-body p-3">Cargando estado...</div></div>';
    modal.querySelector('.audit-monitor-close').onclick = () => { modal.remove(); };
    modal.onclick = e => { if (e.target === modal) modal.remove(); };
    const body = modal.querySelector('.audit-monitor-body');
    let timer = null;
    const render = data => {
        const run=data.run, counts=data.counts||{}, rows=data.rows||[];
        if (!run) { body.innerHTML='<div class="text-secondary">No hay auditorías registradas para este clúster.</div>'; return; }
        const processed=Number(run.processed||0), total=Number(run.total||0), pending=Number(counts.pending||Math.max(total-processed,0));
        const updated=run.updated_at ? new Date(String(run.updated_at).replace(' ','T')).getTime() : 0;
        const stale=run.status==='running' && updated && (Date.now()-updated)>300000;
        const statusLabel={running:'En ejecución',finished:'Finalizada',cancelled:'Detenida'}[run.status]||run.status;
        const resultLabel = value => ({ok:'OK',pending:'Pendiente',ssh_unavailable:'SSH no disponible',credential_error:'Error de credenciales'}[value] || value || 'Sin resultado');
        const resultClass = value => value === 'ok' ? 'ok' : (value === 'pending' ? 'pending' : 'error');
        const duplicateVrrp = data.vrrp_duplicates || {};
        const duplicateVrrpHtml = Object.keys(duplicateVrrp).length ? '<div class=\"audit-monitor-warning\"><i class=\"bi bi-exclamation-triangle\"></i> VRID duplicados detectados: '+Object.entries(duplicateVrrp).map(([id, hosts]) => '<strong>'+esc(id)+'</strong> ('+hosts.map(h => esc(h.host)+' / '+esc(h.instance || '-')).join(', ')+')').join('; ')+'</div>' : '';
        const rowHtml=rows.map(row => '<tr><td><strong>'+esc(row.server_name)+'</strong></td><td>'+esc(row.host)+'</td><td><span class=\"audit-monitor-result '+resultClass(row.result)+'\">'+esc(resultLabel(row.result))+'</span></td><td>'+esc(row.detail||'-')+'</td><td>'+esc(row.audited_at||'-')+'</td></tr>').join('');
        body.innerHTML=duplicateVrrpHtml+'<div class="audit-monitor-summary"><span class="audit-monitor-pill">Estado: '+esc(statusLabel)+'</span><span class="audit-monitor-pill">Progreso: '+processed+'/'+total+'</span><span class="audit-monitor-pill pending">Pendientes: '+pending+'</span><span class="audit-monitor-pill ok">OK: '+(counts.ok||0)+'</span><span class="audit-monitor-pill error">Errores: '+((counts.ssh_unavailable||0)+(counts.credential_error||0))+'</span><span class="audit-monitor-pill">Inicio: '+esc(run.started_at||'-')+'</span><span class="audit-monitor-pill">Último avance: '+esc(run.updated_at||'-')+'</span></div>'+(stale?'<div class="audit-monitor-warning"><i class="bi bi-exclamation-triangle"></i> No se detecta avance hace más de 5 minutos. La auditoría podría estar detenida o truncada.</div>':'')+(run.status==='running'?'<button type="button" class="btn btn-sm btn-outline-danger audit-monitor-stop"><i class="bi bi-stop-circle"></i> Detener auditoría</button>':'')+'<div class="table-responsive"><table class="audit-monitor-table"><thead><tr><th>Host</th><th>IP</th><th>Resultado</th><th>Detalle</th><th>Auditado</th></tr></thead><tbody>'+(rowHtml||'<tr><td colspan="5">Sin hosts registrados.</td></tr>')+'</tbody></table></div>';
        const stop=body.querySelector('.audit-monitor-stop');
        if (stop) stop.onclick=async()=>{ if(!window.confirm('¿Detener la auditoría de '+cluster+'?')) return; stop.disabled=true; stop.textContent='Deteniendo...'; const r=await fetch(base+'/'+run.id+'/stop',{method:'POST',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}}); const d=await r.json(); if(!r.ok) { stop.disabled=false; stop.textContent='Error al detener'; return; } render(d); };
        if (run.status==='running') timer=setTimeout(load,3000);
    };
    const load=async()=>{ try { const response=await fetch(base+'/'+encodeURIComponent(cluster)+'/latest',{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}}); const data=await response.json(); if(!response.ok) throw new Error(data.error||('HTTP '+response.status)); render(data); } catch(e) { body.innerHTML='<div class="text-danger">No se pudo cargar el estado: '+esc(e.message)+'</div>'; } };
    modal.querySelector('.audit-monitor-close').addEventListener('click',()=>{if(timer)clearTimeout(timer);});
    await load();
    return false;
};

window.startClusterAudit = async function(btn, event) {
    if (event) { event.preventDefault(); event.stopPropagation(); }
    if (btn.dataset.running === '1') return false;
    btn.dataset.running = '1';
    btn.classList.add('disabled');
    btn.innerHTML = '<i class="bi bi-arrow-repeat spinner-border spinner-border-sm"></i> Iniciando';
    const cluster = btn.dataset.cluster;
    const startUrl = btn.dataset.startUrl.replace('__CLUSTER__', encodeURIComponent(cluster));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const item = btn.closest('.accordion-item');
    const badge = item ? item.querySelector('.cluster-audit-summary') : null;
    const base = '{{ url('/proxmox/qemu/audit') }}';
    const reset = () => { btn.innerHTML='<i class="bi bi-shield-check"></i> Auditar'; btn.classList.remove('disabled'); btn.dataset.running='0'; };
    try {
        const response = await fetch(startUrl, { method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || ('HTTP '+response.status));
        btn.innerHTML='<i class="bi bi-hourglass-split"></i> Auditando';
        async function processNext() {
            const next = await fetch(base+'/'+data.run_id+'/next', {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
            const result = await next.json();
            if (!next.ok) throw new Error(result.error || ('HTTP '+next.status));
            const run=result.run||{}, done=run.processed||0, total=run.total||data.total||0, counts=result.counts||{};
            if (badge) badge.textContent='Auditoría '+done+'/'+total+' · OK '+(counts.ok||0)+' · SSH '+(counts.ssh_unavailable||0)+' · Cred '+(counts.credential_error||0);
            if (run.status === 'finished' || done >= total) { reset(); setTimeout(() => window.location.reload(), 350); return; }
            setTimeout(processNext, 900);
        }
        await processNext();
    } catch (error) {
        reset();
        window.alert('No se pudo iniciar la auditoría: '+error.message);
    }
    return false;
};
window.startVmAudit = async function(btn, event) {
    if (event) { event.preventDefault(); event.stopPropagation(); }
    if (btn.dataset.running === '1') return false;
    btn.dataset.running = '1';
    btn.classList.add('disabled');
    btn.innerHTML = '<i class="bi bi-arrow-repeat spinner-border spinner-border-sm"></i> ...';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const reset = () => { btn.innerHTML='<i class="bi bi-shield-check"></i> VM'; btn.classList.remove('disabled'); btn.dataset.running='0'; };
    try {
        const response = await fetch(btn.dataset.startUrl, { method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || ('HTTP '+response.status));
        btn.innerHTML='<i class="bi bi-hourglass-split"></i> 0/1';
        const next = await fetch('{{ url('/proxmox/qemu/audit') }}/'+data.run_id+'/next', {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
        const result = await next.json();
        if (!next.ok) throw new Error(result.error || ('HTTP '+next.status));
        const run = result.run || {};
        btn.innerHTML = '<i class="bi bi-check2-circle"></i> '+(run.processed || 0)+'/1';
        setTimeout(() => window.location.reload(), 450);
    } catch (error) {
        reset();
        window.alert('No se pudo auditar la VM: '+error.message);
    }
    return false;
};
</script>
@endsection
