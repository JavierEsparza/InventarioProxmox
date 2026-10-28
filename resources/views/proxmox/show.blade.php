@extends('layouts.app')

@section('content')
@php
    use Carbon\Carbon;
    Carbon::setLocale('es');

    $formatBytes = function($bytes, $precision = 2) {
        if (!$bytes || $bytes <= 0) return '0 GB';
        $tb = $bytes / 1099511627776;
        $gb = $bytes / 1073741824;
        return $tb >= 1 ? number_format($tb, $precision) . ' TB' : number_format($gb, $precision) . ' GB';
    };
    $barClass = function($pct, $warn = 75, $crit = 90) {
        return $pct >= $crit ? 'danger' : ($pct >= $warn ? 'warn' : 'ok');
    };
    $statusBadge = function($status) {
        return strtolower((string)$status) === 'online' || strtolower((string)$status) === 'running' ? 'ok' : 'danger';
    };

    $runningQemus = $allQemusList->where('status', 'running')->count();
    $offlineNodes = $nodes->filter(fn($n) => strtolower((string)$n->status) !== 'online')->count();
    $standaloneNodes = $nodes->filter(fn($n) => empty($n->cluster_name))->count();
    $avgVmPerNode = $totalNodes > 0 ? round($totalQemus / $totalNodes, 1) : 0;
    $criticalNodeCount = $nodes->filter(function($n){
        $cpu = ($n->cpu ?? 0) * 100;
        $ram = ($n->maxmem > 0) ? (($n->mem / $n->maxmem) * 100) : 0;
        $disk = ($n->maxdisk > 0) ? (($n->disk / $n->maxdisk) * 100) : 0;
        return $cpu >= 80 || $ram >= 80 || $disk >= 80 || strtolower((string)$n->status) !== 'online';
    })->count();
    $topNodeLoad = $nodes->map(function($n) {
        $cpu = ($n->cpu ?? 0) * 100;
        $ram = ($n->maxmem > 0) ? (($n->mem / $n->maxmem) * 100) : 0;
        $disk = ($n->maxdisk > 0) ? (($n->disk / $n->maxdisk) * 100) : 0;
        $n->health_score = max($cpu, $ram, $disk);
        return $n;
    })->sortByDesc('health_score')->take(8);
    $topStorage = $globalStoragesList->sortByDesc('usedPercent')->take(8);
    $unsupportedQemus = $allQemusList->filter(function($qemu) {
        $os = strtolower(trim((string)($qemu->os_name ?? '')));
        if ($os === '') return false;
        return preg_match('/(?:debian\s+(?:[0-9]|10))(?:[.\s]|$)/i', $os)
            || preg_match('/ubuntu\s+(?:16|18|20\.04)(?:[.\s]|$)/i', $os)
            || preg_match('/centos(?: linux)?\s+(?:7|8)(?:[.\s]|$)/i', $os)
            || preg_match('/opensuse(?: leap)?\s+15\.(?:1|3)(?:[.\s]|$)/i', $os)
            || preg_match('/windows? server\s+(?:2003|2008|2012)(?:[.\s]|$)/i', $os)
            || preg_match('/windows\s+2003(?:[.\s]|$)/i', $os);
    })->sortBy(function($qemu) {
        return strtolower(($qemu->os_name ?? '') . ' ' . ($qemu->name ?? ''));
    })->values();
    $clusterSummary = $clusters->map(function($cluster) use ($nodes, $allQemusList) {
        $clusterNodes = $nodes->where('cluster_name', $cluster->name);
        $clusterVms = $allQemusList->where('cluster_name', $cluster->name);
        $cluster->vm_count = $clusterVms->count();
        $cluster->running_count = $clusterVms->where('status', 'running')->count();
        $cluster->offline_nodes = $clusterNodes->filter(fn($n) => strtolower((string)$n->status) !== 'online')->count();
        return $cluster;
    })->sortBy('name');
    $nodeChartLabels = $topNodeLoad->pluck('node')->values();
    $nodeChartData = $topNodeLoad->pluck('health_score')->map(fn($v) => round($v, 1))->values();
@endphp

<style>
    .page-hero { background:linear-gradient(135deg,#101820 0%,#172330 58%,#0f5132 155%); border:1px solid #2f3b49; border-radius:18px; padding:1.25rem; color:#fff; box-shadow:0 16px 35px rgba(0,0,0,.28); }
    .page-title { font-size:clamp(1.75rem,2.8vw,2.55rem); font-weight:850; letter-spacing:-.03em; margin:0; }
    .page-subtitle { color:#aab7c4; font-size:.95rem; margin-top:.4rem; }
    .cluster-pill { display:inline-flex; align-items:center; gap:.35rem; border:1px solid #344455; background:rgba(255,255,255,.06); color:#d7e2ec; border-radius:999px; padding:.35rem .65rem; font-size:.82rem; font-weight:750; }
    .cluster-pill.ok { border-color:rgba(25,135,84,.65); background:rgba(25,135,84,.18); color:#91e7b4; }
    .cluster-pill.warn { border-color:rgba(255,193,7,.62); background:rgba(255,193,7,.14); color:#ffe08a; }
    .cluster-pill.danger { border-color:rgba(220,53,69,.65); background:rgba(220,53,69,.16); color:#ff9ca8; }
    .metric-card { background:#151d26; border:1px solid #2b3846; border-radius:16px; padding:1rem; color:#fff; height:100%; box-shadow:inset 0 1px 0 rgba(255,255,255,.03); }
    .metric-label { color:#96a6b5; font-size:.74rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
    .metric-value { font-size:2rem; font-weight:850; line-height:1; margin-top:.35rem; }
    .metric-foot { color:#8d9baa; font-size:.82rem; margin-top:.5rem; }
    .modern-panel { background:#0f1821; border:1px solid #1f3345; border-radius:18px; padding:1rem; color:#fff; box-shadow:0 12px 28px rgba(0,0,0,.18); }
    .modern-panel.tight { padding:0; overflow:hidden; }
    .panel-title { font-size:1.05rem; font-weight:850; margin:0; }
    .modern-table { --bs-table-bg:#0f1821; --bs-table-border-color:#223346; --bs-table-color:#e5edf4; border-radius:14px; overflow:hidden; }
    .modern-table thead th { background:#0c151d !important; color:#8fa3b7; font-size:.72rem; text-transform:uppercase; letter-spacing:.07em; border-bottom:1px solid #344455; white-space:nowrap; }
    .modern-table tbody td { background:#0f1821 !important; border-color:#223346; vertical-align:middle; }
    .modern-table tbody tr:nth-child(even) td { background:#101a24 !important; }
    .modern-table tbody tr:hover td { background:#142536 !important; }
    .badge-soft { border:1px solid #344455; background:rgba(255,255,255,.06); color:#dbe5ee; border-radius:999px; padding:.3rem .55rem; font-size:.74rem; font-weight:800; white-space:nowrap; }
    .badge-soft.ok { border-color:rgba(25,135,84,.65); background:rgba(25,135,84,.18); color:#91e7b4; }
    .badge-soft.warn { border-color:rgba(255,193,7,.62); background:rgba(255,193,7,.14); color:#ffe08a; }
    .badge-soft.danger { border-color:rgba(220,53,69,.65); background:rgba(220,53,69,.18); color:#ff9ca8; }
    .id-chip { font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; background:#07111a; border:1px solid #25384b; color:#b8e7ff; padding:.2rem .45rem; border-radius:8px; font-size:.78rem; }
    .resource-wrap { display:flex; align-items:center; gap:.45rem; min-width:135px; }
    .resource-pct { min-width:38px; text-align:right; color:#d6e1ea; font-size:.78rem; font-weight:800; }
    .resource-bar { position:relative; height:7px; flex:1; background:#243241; border-radius:999px; overflow:hidden; }
    .resource-fill { height:100%; border-radius:999px; }
    .resource-fill.ok { background:#198754; }
    .resource-fill.warn { background:#ffc107; }
    .resource-fill.danger { background:#dc3545; }
    .cluster-card-modern { background:#111b25; border:1px solid #24384b; border-radius:16px; padding:1rem; height:100%; color:#fff; transition:.18s ease; }
    .cluster-card-modern:hover { transform:translateY(-2px); border-color:#39715c; background:#122235; }
    .cluster-name { font-weight:850; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
    .cluster-number { font-size:1.7rem; font-weight:850; }
    .toolbar { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:.75rem; margin-bottom:1rem; }
    .dash-search { background:#0d141c; border:1px solid #324254; color:#e8eef5; border-radius:12px; padding:.65rem .85rem; min-width:320px; }
    .dash-filter { border:1px solid #344455; background:rgba(255,255,255,.04); color:#dce6ef; border-radius:999px; padding:.45rem .8rem; font-weight:750; font-size:.84rem; }
    .dash-filter.active { background:rgba(25,135,84,.25); border-color:rgba(25,135,84,.7); color:#94ebb9; }
    .chart-card { background:#0f1821; border:1px solid #223346; border-radius:18px; padding:1rem; color:#fff; height:100%; }
    .chart-box { height:280px; }
    .accordion-modern .accordion-item { background:#0f1821; border:1px solid #223346; border-radius:16px; overflow:hidden; margin-bottom:.85rem; }
    .accordion-modern .accordion-button { background:#0c151d; color:#eaf2f8; box-shadow:none; padding:.85rem 1rem; }
    .accordion-modern .accordion-button:not(.collapsed) { background:#102033; color:#fff; }
    .accordion-modern .accordion-button::after { filter:invert(1); }
    .nav-tabs-modern { border-color:#223346; }
    .nav-tabs-modern .nav-link { color:#9aabbc; background:#101a24; border-color:#223346; font-weight:800; }
    .nav-tabs-modern .nav-link.active { color:#fff; background:#152333; border-color:#2f465d #2f465d #152333; }
    .critical-panel { background:#190f18; border:1px solid rgba(220,53,69,.65); border-radius:18px; overflow:hidden; color:#fff; }
    .critical-head { background:linear-gradient(90deg,#dc3545 0%,#9f1b2a 100%); padding:.75rem 1rem; font-weight:850; }
    .critical-storage { background:#111b2a; border:1px solid rgba(220,53,69,.42); border-radius:14px; padding:.8rem; height:100%; }
    .empty-filter { display:none; color:#aeb9c5; text-align:center; padding:1rem; }
</style>

<div class="container-fluid px-4 py-4">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    @endif

    <div class="page-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="cluster-pill ok">● Resumen operativo</span>
                    <span class="cluster-pill">{{ $totalClusters }} cluster(s)</span>
                    <span class="cluster-pill {{ $offlineNodes > 0 ? 'danger' : 'ok' }}">{{ $offlineNodes }} nodo(s) offline</span>
                    <span class="cluster-pill {{ $criticalStorages->count() > 0 ? 'danger' : 'ok' }}">{{ $criticalStorages->count() }} storage(s) crítico(s)</span>
                </div>
                <h1 class="page-title">Dashboard Proxmox</h1>
                <div class="page-subtitle">Vista global de clusters, nodos, VMs y almacenamiento. Priorizado para operación rápida y detección de capacidad crítica.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('proxmox.cluster') }}" class="btn btn-outline-light"><i class="bi bi-diagram-3 me-1"></i> Clusters</a>
                <a href="{{ route('proxmox.node') }}" class="btn btn-outline-light"><i class="bi bi-hdd-network me-1"></i> Nodos</a>
                <a href="{{ route('proxmox.qemu') }}" class="btn btn-primary"><i class="bi bi-pc-display me-1"></i> VMs</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Clusters</div><div class="metric-value">{{ $totalClusters }}</div><div class="metric-foot">{{ $standaloneNodes }} nodo(s) sin cluster</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Nodos</div><div class="metric-value {{ $offlineNodes > 0 ? 'text-danger' : '' }}">{{ $totalNodes }}</div><div class="metric-foot">{{ $offlineNodes }} offline / alerta</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">VMs</div><div class="metric-value">{{ $totalQemus }}</div><div class="metric-foot">{{ $runningQemus }} running | {{ $totalStoppedQemus }} apagadas</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">CPU nodos</div><div class="metric-value">{{ $totalMaxCPU }}</div><div class="metric-foot">Uso promedio {{ $cpuUsagePercentage }}%</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">RAM nodos</div><div class="metric-value" style="font-size:1.55rem;">{{ $totalMaxRAM >= 1024 ? number_format($totalMaxRAM / 1024, 2).' TB' : number_format($totalMaxRAM, 2).' GB' }}</div><div class="metric-foot">{{ $totalUsedRAM }} GB usados | {{ $memoryUsagePercentage }}%</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Storage</div><div class="metric-value {{ $storageUsagePercentage >= 90 ? 'text-danger' : ($storageUsagePercentage >= 75 ? 'text-warning' : '') }}" style="font-size:1.55rem;">{{ $totalMaxStorage }} TB</div><div class="metric-foot">{{ $totalUsedStorage }} TB usados | {{ $storageUsagePercentage }}%</div></div></div>
    </div>

    @if ($criticalStorages->count() > 0)
        <div class="critical-panel mb-4">
            <div class="critical-head"><i class="bi bi-exclamation-triangle-fill me-2"></i>Storages críticos — {{ $criticalStorages->count() }} storage(s) sobre el 90% de uso</div>
            <div class="p-3">
                <div class="row g-3">
                    @foreach ($criticalStorages as $cs)
                        @php
                            $csPercent = min(100, $cs->usedPercent);
                            $csAvail = max(0, $cs->available);
                            $cls = $barClass($csPercent, 90, 95);
                        @endphp
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="critical-storage">
                                <div class="d-flex justify-content-between align-items-center mb-2"><strong><i class="bi bi-hdd-fill me-1 text-danger"></i>{{ $cs->storage }}</strong><span class="badge-soft danger">{{ round($csPercent, 1) }}%</span></div>
                                <div class="resource-wrap mb-2" style="min-width:100%;"><span class="resource-pct">{{ round($csPercent) }}%</span><div class="resource-bar"><div class="resource-fill {{ $cls }}" style="width: {{ $csPercent }}%"></div></div></div>
                                <div class="d-flex justify-content-between small text-secondary"><span>Usado {{ $formatBytes($cs->disk) }}</span><span>Total {{ $formatBytes($cs->maxdisk) }}</span></div>
                                <div class="mt-2 p-2 text-center rounded" style="background:#0f2031;border:1px dashed rgba(220,53,69,.5);"><span class="text-warning fw-bold"><i class="bi bi-arrow-down-circle-fill me-1"></i>Disponible {{ $formatBytes($csAvail) }}</span></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="modern-panel tight">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 border-bottom" style="border-color:#263747!important;">
                    <div><h5 class="panel-title mb-1">VMs con sistema operativo sin soporte</h5><div class="page-subtitle mb-0">Revisión por versión detectada en el inventario.</div></div>
                    <span class="badge-soft {{ $unsupportedQemus->count() > 0 ? 'danger' : 'ok' }}">{{ $unsupportedQemus->count() }} VM(s)</span>
                </div>
                <div class="table-responsive" style="max-height:340px;overflow-y:auto;">
                    <table class="table table-dark table-hover table-sm align-middle modern-table mb-0">
                        <thead class="text-center sticky-top"><tr><th>Cluster</th><th>VMID</th><th class="text-start">VM</th><th class="text-start">Sistema operativo</th><th>Estado</th></tr></thead>
                        <tbody class="text-center">
                            @forelse ($unsupportedQemus as $qemu)
                                <tr><td>{{ $qemu->cluster_name ?? 'N/A' }}</td><td><span class="id-chip">{{ $qemu->vmid }}</span></td><td class="text-start fw-bold">{{ $qemu->name }}</td><td class="text-start"><span class="badge-soft warn">{{ $qemu->os_name }}</span></td><td><span class="badge-soft {{ $qemu->status === 'running' ? 'ok' : 'danger' }}">{{ $qemu->status }}</span></td></tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-4">No se detectaron VMs con SO sin soporte.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5"><div class="chart-card"><div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Nodos con mayor presión</h5><span class="badge-soft warn">máximo CPU/RAM/Disco</span></div><div class="chart-box"><canvas id="nodePressureChart"></canvas></div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="modern-panel">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h5 class="panel-title">Resumen de clusters</h5><div class="page-subtitle">Nodos, VMs y estado operativo por cluster.</div></div><a href="{{ route('proxmox.cluster') }}" class="btn btn-sm btn-outline-light">Ver listado completo</a></div>
                <div class="row g-3">
                    @forelse ($clusterSummary as $cluster)
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="cluster-card-modern">
                                <div class="d-flex justify-content-between gap-2 mb-2"><div class="cluster-name" title="{{ $cluster->name }}">{{ $cluster->name }}</div><span class="badge-soft {{ $cluster->offline_nodes > 0 ? 'danger' : 'ok' }}">{{ $cluster->offline_nodes > 0 ? 'alerta' : 'ok' }}</span></div>
                                <div class="d-flex justify-content-between text-center mb-3">
                                    <div><div class="cluster-number">{{ $cluster->node_count }}</div><div class="metric-foot">Nodos</div></div>
                                    <div><div class="cluster-number">{{ $cluster->vm_count }}</div><div class="metric-foot">VMs</div></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center"><span class="metric-foot">{{ $cluster->running_count }} running</span><a href="{{ route('proxmox.cluster.show', $cluster->name) }}" class="btn btn-sm btn-primary">Ver</a></div>
                                <div class="metric-foot mt-2">Act: {{ $cluster->updated_at ? $cluster->updated_at->diffForHumans() : 'S/D' }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12"><div class="alert alert-secondary text-center">No se encontraron clusters.</div></div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="modern-panel h-100">
                <h5 class="panel-title mb-3">Top storages por uso</h5>
                <div class="table-responsive">
                    <table class="table table-dark table-hover table-bordered align-middle modern-table mb-0">
                        <thead class="text-center"><tr><th class="text-start">Storage</th><th>Uso</th><th>Disponible</th></tr></thead>
                        <tbody class="text-center">
                            @forelse($topStorage as $storage)
                                @php $cls = $barClass($storage->usedPercent, 75, 90); @endphp
                                <tr>
                                    <td class="text-start fw-bold">{{ $storage->storage }}<div class="text-secondary small">{{ $storage->plugintype }}</div></td>
                                    <td><div class="resource-wrap"><span class="resource-pct">{{ round($storage->usedPercent) }}%</span><div class="resource-bar"><div class="resource-fill {{ $cls }}" style="width: {{ min(100, $storage->usedPercent) }}%"></div></div></div><div class="small text-secondary mt-1">{{ $formatBytes($storage->disk) }} / {{ $formatBytes($storage->maxdisk) }}</div></td>
                                    <td><span class="badge-soft {{ $storage->usedPercent >= 90 ? 'danger' : ($storage->usedPercent >= 75 ? 'warn' : 'ok') }}">{{ $formatBytes($storage->available) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-secondary text-center">Sin storages.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="accordion accordion-modern" id="mainAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingNodes"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNodes" aria-expanded="true" aria-controls="collapseNodes"><strong>Nodos globales</strong><span class="cluster-pill ms-3">{{ $nodes->count() }} nodos</span><span class="cluster-pill {{ $criticalNodeCount > 0 ? 'warn' : 'ok' }} ms-2">{{ $criticalNodeCount }} con presión/alerta</span></button></h2>
            <div id="collapseNodes" class="accordion-collapse collapse show" aria-labelledby="headingNodes" data-bs-parent="#mainAccordion">
                <div class="accordion-body">
                    <div class="toolbar"><input id="nodeSearch" class="dash-search" type="search" placeholder="Filtrar nodos por cluster, nombre o IP..."><div class="d-flex flex-wrap gap-2"><button class="dash-filter active" data-node-filter="all">Todos</button><button class="dash-filter" data-node-filter="critical">Críticos</button><button class="dash-filter" data-node-filter="offline">Offline</button></div></div>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover table-bordered align-middle modern-table mb-0">
                            <thead class="text-center"><tr><th class="text-start">Cluster</th><th class="text-start">Nodo</th><th>Estado</th><th>CPU</th><th>RAM</th><th>Disco</th><th>IP</th><th>Uptime</th><th>Acciones</th></tr></thead>
                            <tbody class="text-center">
                            @foreach ($nodes as $node)
                                @php
                                    $cpuPercent = min(100, ($node->cpu ?? 0) * 100);
                                    $memPercent = ($node->maxmem > 0) ? min(100, ($node->mem / $node->maxmem) * 100) : 0;
                                    $diskPercent = ($node->maxdisk > 0) ? min(100, ($node->disk / $node->maxdisk) * 100) : 0;
                                    $isCritical = $cpuPercent >= 80 || $memPercent >= 80 || $diskPercent >= 80;
                                    $isOffline = strtolower((string)$node->status) !== 'online';
                                    $formattedDisk = $node->maxdisk > 0 ? $formatBytes($node->disk).' / '.$formatBytes($node->maxdisk) : 'Sin datos';
                                @endphp
                                <tr class="node-row" data-search="{{ e(strtolower(($node->cluster_name ?? 'sin cluster').' '.$node->node.' '.$node->ip)) }}" data-critical="{{ $isCritical ? '1' : '0' }}" data-offline="{{ $isOffline ? '1' : '0' }}">
                                    <td class="text-start"><span class="badge-soft">{{ $node->cluster_name ?? 'Sin cluster' }}</span></td>
                                    <td class="text-start fw-bold">{{ $node->node }}</td>
                                    <td><span class="badge-soft {{ $isOffline ? 'danger' : 'ok' }}">{{ $node->status }}</span></td>
                                    <td><div class="resource-wrap"><span class="resource-pct">{{ round($cpuPercent) }}%</span><div class="resource-bar"><div class="resource-fill {{ $barClass($cpuPercent, 50, 80) }}" style="width: {{ $cpuPercent }}%"></div></div></div></td>
                                    <td><div class="resource-wrap"><span class="resource-pct">{{ round($memPercent) }}%</span><div class="resource-bar"><div class="resource-fill {{ $barClass($memPercent, 50, 80) }}" style="width: {{ $memPercent }}%"></div></div></div></td>
                                    <td><div class="resource-wrap"><span class="resource-pct">{{ round($diskPercent) }}%</span><div class="resource-bar"><div class="resource-fill {{ $barClass($diskPercent, 50, 80) }}" style="width: {{ $diskPercent }}%"></div></div></div><div class="small text-secondary mt-1">{{ $formattedDisk }}</div></td>
                                    <td><span class="id-chip">{{ $node->ip }}</span></td>
                                    <td><small>{{ round($node->uptime / 86400) }}d</small></td>
                                    <td><a href="/proxmox/node/{{ $node->node }}" class="btn btn-sm btn-outline-light py-0">Ver</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div id="nodeEmptyFilter" class="empty-filter">No hay nodos que coincidan con el filtro.</div>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header" id="headingVms"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVms" aria-expanded="false" aria-controls="collapseVms"><strong>Máquinas virtuales</strong><span class="cluster-pill ms-3">{{ $totalQemus }} total</span><span class="cluster-pill ok ms-2">{{ $runningQemus }} running</span><span class="cluster-pill danger ms-2">{{ $totalStoppedQemus }} apagadas</span></button></h2>
            <div id="collapseVms" class="accordion-collapse collapse" aria-labelledby="headingVms" data-bs-parent="#mainAccordion">
                <div class="accordion-body">
                    <ul class="nav nav-tabs nav-tabs-modern" id="vmTab" role="tablist">
                        <li class="nav-item" role="presentation"><button class="nav-link active" id="stopped-tab" data-bs-toggle="tab" data-bs-target="#stopped" type="button" role="tab">VMs Apagadas <span class="badge-soft danger ms-1">{{ $stoppedQemusList->count() }}</span></button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" id="topram-tab" data-bs-toggle="tab" data-bs-target="#topram" type="button" role="tab">Top RAM</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" id="allvms-tab" data-bs-toggle="tab" data-bs-target="#allvms" type="button" role="tab">Todas <span class="badge-soft ms-1">{{ $allQemusList->count() }}</span></button></li>
                    </ul>
                    <div class="tab-content" id="vmTabContent">
                        <div class="tab-pane fade show active pt-3" id="stopped" role="tabpanel">
                            <div class="d-flex justify-content-end mb-2"><a href="{{ route('proxmox.exportQemuStopped') }}" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Exportar apagadas</a></div>
                            <div class="table-responsive"><table class="table table-dark table-hover table-bordered align-middle modern-table mb-0"><thead class="text-center"><tr><th>Cluster</th><th>Nodo</th><th>VMID</th><th class="text-start">Nombre</th><th>Estado</th><th>RAM Asignada</th></tr></thead><tbody class="text-center">
                                @forelse ($stoppedQemusList as $qemu)
                                    <tr><td>{{ $qemu->cluster_name ?? 'N/A' }}</td><td>{{ str_replace('node/', '', $qemu->node_id) }}</td><td><span class="id-chip">{{ $qemu->vmid }}</span></td><td class="text-start fw-bold">{{ $qemu->name }}</td><td><span class="badge-soft danger">{{ $qemu->status }}</span></td><td>{{ round($qemu->maxmem / 1073741824) }} GB</td></tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-4">No hay VMs apagadas.</td></tr>
                                @endforelse
                            </tbody></table></div>
                        </div>
                        <div class="tab-pane fade pt-3" id="topram" role="tabpanel">
                            <div class="table-responsive"><table class="table table-dark table-hover table-bordered align-middle modern-table mb-0"><thead class="text-center"><tr><th>Cluster</th><th>Nodo</th><th>VMID</th><th class="text-start">Nombre</th><th>RAM</th><th>% Uso</th></tr></thead><tbody class="text-center">
                                @forelse ($topRamQemus as $qemu)
                                    @php $ramPercent = ($qemu->maxmem > 0) ? min(100, ($qemu->mem / $qemu->maxmem) * 100) : 0; @endphp
                                    <tr><td>{{ $qemu->cluster_name ?? 'N/A' }}</td><td>{{ str_replace('node/', '', $qemu->node_id) }}</td><td><span class="id-chip">{{ $qemu->vmid }}</span></td><td class="text-start fw-bold">{{ $qemu->name }}</td><td><div class="resource-wrap"><span class="resource-pct">{{ round($ramPercent) }}%</span><div class="resource-bar"><div class="resource-fill {{ $barClass($ramPercent, 50, 80) }}" style="width: {{ $ramPercent }}%"></div></div></div><div class="small text-secondary mt-1">{{ $formatBytes($qemu->mem) }} / {{ $formatBytes($qemu->maxmem) }}</div></td><td class="fw-bold">{{ round($ramPercent) }}%</td></tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-4">No hay VMs corriendo para mostrar.</td></tr>
                                @endforelse
                            </tbody></table></div>
                        </div>
                        <div class="tab-pane fade pt-3" id="allvms" role="tabpanel">
                            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2"><input id="vmSearch" class="dash-search" type="search" placeholder="Filtrar VM por cluster, nodo, VMID o nombre..."><a href="{{ route('proxmox.exportQemu') }}" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Exportar todo</a></div>
                            <div class="table-responsive" style="max-height: 600px; overflow-y:auto;"><table class="table table-dark table-hover table-bordered table-sm align-middle modern-table mb-0"><thead class="text-center sticky-top"><tr><th>Cluster</th><th>Nodo</th><th>VMID</th><th class="text-start">Nombre</th><th>Estado</th><th>CPU</th><th>RAM</th><th>Disco</th></tr></thead><tbody class="text-center">
                                @forelse ($allQemusList as $qemu)
                                    @php $qCpu = ($qemu->maxcpu > 0) ? min(100, ($qemu->cpu / $qemu->maxcpu) * 100) : 0; $qRam = ($qemu->maxmem > 0) ? min(100, ($qemu->mem / $qemu->maxmem) * 100) : 0; @endphp
                                    <tr class="vm-row" data-search="{{ e(strtolower(($qemu->cluster_name ?? '').' '.str_replace('node/', '', $qemu->node_id).' '.$qemu->vmid.' '.$qemu->name.' '.$qemu->status)) }}"><td>{{ $qemu->cluster_name ?? 'N/A' }}</td><td>{{ str_replace('node/', '', $qemu->node_id) }}</td><td><span class="id-chip">{{ $qemu->vmid }}</span></td><td class="text-start fw-bold">{{ $qemu->name }}</td><td><span class="badge-soft {{ $qemu->status === 'running' ? 'ok' : 'danger' }}">{{ $qemu->status }}</span></td><td><div class="resource-wrap"><span class="resource-pct">{{ round($qCpu) }}%</span><div class="resource-bar"><div class="resource-fill {{ $barClass($qCpu, 50, 80) }}" style="width: {{ $qCpu }}%"></div></div></div></td><td><div class="resource-wrap"><span class="resource-pct">{{ round($qRam) }}%</span><div class="resource-bar"><div class="resource-fill {{ $barClass($qRam, 50, 80) }}" style="width: {{ $qRam }}%"></div></div></div></td><td>{{ $formatBytes($qemu->size) }}</td></tr>
                                @empty
                                    <tr><td colspan="8" class="text-center py-4">No se encontró ninguna VM.</td></tr>
                                @endforelse
                            </tbody></table></div><div id="vmEmptyFilter" class="empty-filter">No hay VMs que coincidan con el filtro.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header" id="headingStorage"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStorage" aria-expanded="false" aria-controls="collapseStorage"><strong>Almacenamiento global</strong><span class="cluster-pill ms-3">{{ $globalStoragesList->count() }} storage(s)</span><span class="cluster-pill info ms-2">sin local/local-lvm</span><span class="cluster-pill {{ $criticalStorages->count() > 0 ? 'danger' : 'ok' }} ms-2">{{ $criticalStorages->count() }} críticos</span></button></h2>
            <div id="collapseStorage" class="accordion-collapse collapse" aria-labelledby="headingStorage" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-0">
                    <div class="table-responsive"><table class="table table-dark table-hover table-bordered align-middle modern-table mb-0"><thead class="text-center"><tr><th class="text-start">Nombre Storage</th><th>Tipo</th><th>Uso</th><th>Utilizado</th><th>Total</th><th>Disponible</th><th class="text-start">Nodos</th></tr></thead><tbody class="text-center">
                        @forelse ($globalStoragesList as $storage)
                            @php $usedPercent = min(100, $storage->usedPercent); $availPercent = $storage->maxdisk > 0 ? max(0, 100 - $usedPercent) : 0; @endphp
                            <tr><td class="fw-bold text-start">{{ $storage->storage }}</td><td><span class="badge-soft">{{ $storage->plugintype }}</span></td><td><div class="resource-wrap"><span class="resource-pct">{{ round($usedPercent) }}%</span><div class="resource-bar"><div class="resource-fill {{ $barClass($usedPercent, 75, 90) }}" style="width: {{ $usedPercent }}%"></div></div></div></td><td>{{ $formatBytes($storage->disk) }}</td><td>{{ $formatBytes($storage->maxdisk) }}</td><td><span class="badge-soft {{ $availPercent <= 10 ? 'danger' : ($availPercent <= 25 ? 'warn' : 'ok') }}">{{ $formatBytes($storage->available) }} libre</span></td><td class="text-start small">
                                    <div class="d-flex flex-wrap gap-1 align-items-center">
                                        <span class="badge-soft">{{ $storage->node_count ?? 0 }} nodo(s)</span>
                                        @if(!empty($storage->first_nodes))
                                            <span class="text-secondary" title="{!! strip_tags($storage->nodes) !!}">{{ $storage->first_nodes }}{{ (($storage->node_count ?? 0) > 6) ? ', ...' : '' }}</span>
                                        @endif
                                    </div>
                                </td></tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4">No se encontraron almacenamientos globales.</td></tr>
                        @endforelse
                    </tbody></table></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartText = '#dce6ef';
    const chartGrid = 'rgba(255,255,255,.08)';
    new Chart(document.getElementById('nodePressureChart').getContext('2d'), {
        type:'bar',
        data:{ labels:@json($nodeChartLabels), datasets:[{ label:'Presión máxima %', data:@json($nodeChartData), backgroundColor:'rgba(220,53,69,.65)', borderColor:'#dc3545', borderWidth:1 }]},
        options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false, scales:{ x:{beginAtZero:true,max:100,ticks:{color:chartText},grid:{color:chartGrid}}, y:{ticks:{color:chartText},grid:{color:chartGrid}} }, plugins:{legend:{labels:{color:chartText,usePointStyle:true}}, tooltip:{backgroundColor:'rgba(0,0,0,.85)'}} }
    });

    const nodeSearch = document.getElementById('nodeSearch');
    const nodeRows = document.querySelectorAll('.node-row');
    const nodeFilters = document.querySelectorAll('[data-node-filter]');
    const nodeEmpty = document.getElementById('nodeEmptyFilter');
    let activeNodeFilter = 'all';
    function applyNodeFilter(){
        const q=(nodeSearch?.value||'').toLowerCase().trim(); let visible=0;
        nodeRows.forEach(row=>{ let ok=!q || (row.dataset.search||'').includes(q); if(activeNodeFilter==='critical') ok=ok && row.dataset.critical==='1'; if(activeNodeFilter==='offline') ok=ok && row.dataset.offline==='1'; row.style.display=ok?'':'none'; if(ok) visible++; });
        if(nodeEmpty) nodeEmpty.style.display = visible===0 && nodeRows.length>0 ? 'block' : 'none';
    }
    if(nodeSearch) nodeSearch.addEventListener('input', applyNodeFilter);
    nodeFilters.forEach(btn=>btn.addEventListener('click',()=>{nodeFilters.forEach(b=>b.classList.remove('active')); btn.classList.add('active'); activeNodeFilter=btn.dataset.nodeFilter||'all'; applyNodeFilter();}));

    const vmSearch = document.getElementById('vmSearch');
    const vmRows = document.querySelectorAll('.vm-row');
    const vmEmpty = document.getElementById('vmEmptyFilter');
    function applyVmFilter(){ const q=(vmSearch?.value||'').toLowerCase().trim(); let visible=0; vmRows.forEach(row=>{const ok=!q || (row.dataset.search||'').includes(q); row.style.display=ok?'':'none'; if(ok) visible++;}); if(vmEmpty) vmEmpty.style.display=visible===0 && vmRows.length>0?'block':'none'; }
    if(vmSearch) vmSearch.addEventListener('input', applyVmFilter);
});
</script>
@endsection
