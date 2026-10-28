@extends('layouts.app')

@section('content')
<style>
    /* Alinea números a la derecha y usa fuente monoespaciada */
    .table-numeric {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .table-progress { min-width: 150px; }
    .table.align-middle th, .table.align-middle td { vertical-align: middle; }

    /* Estilos para las tarjetas de nodo local */
    .node-card {
        background-color: #2c3034;
        border: 1px solid #454d55;
        border-radius: .25rem;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    .node-card-title {
        font-size: 1.2rem;
        font-weight: bold;
        color: white;
        border-bottom: 1px solid #454d55;
        padding-bottom: .5rem;
        margin-bottom: 1rem;
        text-align: center; /* Título del nodo centrado */
    }
    .local-gauge-card {
        text-align: center;
        color: white;
    }
    .local-gauge-wrapper {
        position: relative;
        width: 100px;
        height: 100px;
        margin: 0 auto .5rem auto;
    }
    .local-gauge-percentage {
        position: absolute;
        top: 45%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 1.2rem;
        font-weight: bold;
    }
    .local-gauge-label {
        font-weight: bold;
        font-size: 0.8rem;
    }
    .local-gauge-stats {
        font-size: 0.75rem;
        opacity: 0.7;
    }
    .cluster-hero {
        background: linear-gradient(135deg, #101820 0%, #172330 55%, #0f5132 160%);
        border: 1px solid #2f3b49;
        border-radius: 18px;
        padding: 1.25rem;
        box-shadow: 0 16px 35px rgba(0, 0, 0, .28);
    }
    .cluster-title {
        font-size: clamp(1.8rem, 3vw, 2.8rem);
        font-weight: 800;
        letter-spacing: -.03em;
        margin: 0;
    }
    .cluster-subtitle { color: #aab7c4; font-size: .95rem; }
    .cluster-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border: 1px solid #344455;
        background: rgba(255,255,255,.06);
        color: #d7e2ec;
        border-radius: 999px;
        padding: .35rem .65rem;
        font-size: .82rem;
        font-weight: 700;
    }
    .cluster-pill.success { border-color: rgba(25,135,84,.55); background: rgba(25,135,84,.18); color: #8fe3b3; }
    .cluster-pill.warn { border-color: rgba(255,193,7,.55); background: rgba(255,193,7,.14); color: #ffd86b; }
    .metric-card {
        background: #151d26;
        border: 1px solid #2b3846;
        border-radius: 16px;
        padding: 1rem;
        height: 100%;
        color: #fff;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.03);
    }
    .metric-label { color: #96a6b5; font-size: .74rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; }
    .metric-value { font-size: 2rem; font-weight: 850; line-height: 1; margin-top: .35rem; }
    .metric-foot { color: #8d9baa; font-size: .82rem; margin-top: .5rem; }
    .resource-card {
        background: #151d26;
        border: 1px solid #2b3846;
        border-radius: 16px;
        padding: 1rem;
        height: 100%;
    }
    .resource-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:.75rem; }
    .resource-title { font-weight:800; color:#fff; }
    .resource-percent { font-weight:850; color:#fff; }
    .progress.cluster-progress { height: 12px; background:#273240; border-radius:999px; overflow:hidden; }
    .storage-empty {
        border: 1px dashed #4a5663;
        border-radius: 14px;
        padding: 1.25rem;
        color: #aeb9c5;
        text-align: center;
        background: rgba(255,255,255,.03);
    }
    .section-titlebar { display:flex; justify-content:space-between; align-items:end; gap:1rem; margin: 1.5rem 0 1rem; color:#fff; }
    .section-titlebar h2 { margin:0; font-weight:850; letter-spacing:-.02em; }
    .section-subtitle { color:#91a0ae; font-size:.9rem; }
    .modern-node-card { background:#151d26; border:1px solid #2b3846; border-radius:16px; padding:1rem; height:100%; color:#fff; }
    .modern-node-head { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; margin-bottom:1rem; }
    .modern-node-name { font-size:1.25rem; font-weight:850; }
    .modern-node-meta { color:#9aa8b5; font-size:.86rem; margin-top:.2rem; }
    .mini-stat-grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:.65rem; margin:.75rem 0; }
    .mini-stat { background:rgba(255,255,255,.04); border:1px solid #2d3947; border-radius:12px; padding:.7rem; }
    .mini-stat-label { color:#93a2b1; font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
    .mini-stat-value { font-weight:850; margin-top:.25rem; }
    .node-resource-row { margin-top:.75rem; }
    .node-resource-row .label-line { display:flex; justify-content:space-between; color:#cbd6e0; font-size:.86rem; margin-bottom:.25rem; }
    .modern-panel { background:#111922; border:1px solid #293645; border-radius:18px; padding:1rem; color:#fff; }
    .vm-toolbar { display:flex; flex-wrap:wrap; justify-content:space-between; gap:.75rem; margin-bottom:1rem; }
    .vm-search { background:#0d141c; border:1px solid #324254; color:#e8eef5; border-radius:12px; padding:.65rem .85rem; min-width:260px; }
    .vm-filter { border:1px solid #344455; background:rgba(255,255,255,.04); color:#dce6ef; border-radius:999px; padding:.45rem .8rem; font-weight:750; font-size:.84rem; }
    .vm-filter.active { background:rgba(25,135,84,.25); border-color:rgba(25,135,84,.7); color:#94ebb9; }
    .modern-table { --bs-table-bg:#111922; --bs-table-border-color:#293645; border-radius:14px; overflow:hidden; }
    .modern-table thead th { color:#94a5b5; font-size:.74rem; text-transform:uppercase; letter-spacing:.07em; border-bottom:1px solid #344455; }
    .modern-table tbody tr.vm-stopped { opacity:.62; }
    .vm-name-main { font-weight:800; color:#fff; }
    .vm-name-sub { color:#8c9baa; font-size:.78rem; }
    .badge-soft { border:1px solid #344455; background:rgba(255,255,255,.06); color:#dbe5ee; border-radius:999px; padding:.3rem .55rem; }
    .badge-soft.running { border-color:rgba(25,135,84,.65); background:rgba(25,135,84,.18); color:#91e7b4; }
    .badge-soft.stopped { border-color:rgba(108,117,125,.65); background:rgba(108,117,125,.18); color:#c9d0d6; }
    .vm-empty-filter { display:none; color:#aeb9c5; text-align:center; padding:1rem; }
    .node-compact-table td, .node-compact-table th { white-space: nowrap; }
    .node-compact-table .progress.cluster-progress { min-width: 105px; }
    .compact-resource { min-width: 145px; }
    .compact-resource .label-line { color:#cbd6e0; font-size:.78rem; margin-bottom:.2rem; }
    .storage-section-grid { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:1rem; }
    @media (max-width: 991.98px) { .storage-section-grid { grid-template-columns: 1fr; } }
    .storage-list { display:flex; flex-direction:column; gap:.7rem; }
    .storage-row-card { background:#151d26; border:1px solid #2b3846; border-radius:14px; padding:.85rem; color:#fff; }
    .storage-row-head { display:flex; justify-content:space-between; align-items:start; gap:.75rem; margin-bottom:.55rem; }
    .storage-name { font-weight:850; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .storage-meta { color:#8f9eac; font-size:.78rem; margin-top:.15rem; }
    .storage-percent { font-size:1.1rem; font-weight:850; }
    .storage-usage-line { display:flex; justify-content:space-between; color:#aeb9c5; font-size:.82rem; margin-top:.35rem; }
    .local-storage-node { background:#111922; border:1px solid #293645; border-radius:16px; padding:1rem; color:#fff; height:100%; }
    .local-storage-node-title { font-weight:850; margin-bottom:.75rem; }
    .local-storage-item { padding:.55rem 0; border-top:1px solid #253241; }
    .local-storage-item:first-of-type { border-top:0; padding-top:0; }

    /* Pulido visual: fondos más oscuros, encabezados coherentes y porcentajes centrados */
    .section-titlebar h2 { color:#eaf2f8; text-shadow:0 1px 0 rgba(0,0,0,.25); }
    .section-subtitle { color:#718397; }
    .modern-panel { background:#0f1821; border-color:#1f3345; box-shadow:0 12px 28px rgba(0,0,0,.18); }
    .modern-table { --bs-table-bg:#0f1821; --bs-table-striped-bg:#101c26; --bs-table-hover-bg:#142536; --bs-table-color:#e5edf4; }
    .modern-table tbody td { background:#0f1821 !important; border-color:#223346; }
    .modern-table tbody tr:nth-child(even) td { background:#101a24 !important; }
    .modern-table tbody tr:hover td { background:#142536 !important; }
    .modern-table thead th { background:#0c151d !important; color:#8fa3b7; }
    .modern-table .label-line { display:flex; justify-content:center; color:#d7e3ee; font-size:.82rem; margin-bottom:.22rem; text-align:center; }
    .modern-table .progress.cluster-progress { margin-left:auto; margin-right:auto; }
    .modern-table td .vm-name-sub { text-align:center; }
    .modern-table td:first-child .vm-name-sub { text-align:left; }
    .modern-table td:first-child { background:#101b25 !important; }
    .modern-table tbody tr:hover td:first-child { background:#15283a !important; }
    .storage-row-card { background:#0f1821; border-color:#223346; }
    .storage-row-head { display:grid; grid-template-columns: minmax(0,1fr) 80px; align-items:center; }
    .storage-percent { justify-self:center; text-align:center; min-width:72px; padding:.25rem .55rem; border-radius:999px; background:rgba(255,255,255,.045); border:1px solid #2d4053; }
    .storage-row-card .progress.cluster-progress, .local-storage-item .progress.cluster-progress { height:10px; }
    .storage-usage-line { color:#8798a9; }
    .local-storage-node { background:#0f1821; border-color:#223346; }
    .local-storage-node-title { color:#eaf2f8; }
    .local-storage-item { border-color:#203247; }
    .badge-soft { background:#172330; border-color:#33475b; }

</style>

@php
    // Divisores Base-1000 para consistencia con la UI de Proxmox
    $GB_DIVISOR = 1000 * 1000 * 1000;
    $TB_DIVISOR = 1000 * 1000 * 1000 * 1000;
@endphp

<div class="container-fluid mt-4">
    @php
        $runningQemus = max($totalQemu - $totalStoppedQemus, 0);
        $clusterTypeLabel = $nodes->count() <= 1 ? 'Proxmox standalone' : 'Cluster Proxmox';
        $firstNode = $nodes->first();
        $lastSyncAt = collect([$cluster->updated_at, optional($firstNode)->updated_at])->filter()->max();
        $cpuBarColor = $cpuUsagePercentage >= 85 ? 'bg-danger' : ($cpuUsagePercentage >= 70 ? 'bg-warning' : 'bg-success');
        $memoryBarColor = $memoryUsagePercentage >= 85 ? 'bg-danger' : ($memoryUsagePercentage >= 70 ? 'bg-warning' : 'bg-success');
        $hasStorageTotals = (float)$totalMaxStorage > 0;
        $storageBarColor = $storageUsagePercentage >= 85 ? 'bg-danger' : ($storageUsagePercentage >= 70 ? 'bg-warning' : 'bg-success');
    @endphp

    <div class="cluster-hero text-white mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="cluster-pill success">● Online</span>
                    <span class="cluster-pill">{{ $clusterTypeLabel }}</span>
                    @if($firstNode && $firstNode->ip)
                        <span class="cluster-pill">IP gestión: {{ $firstNode->ip }}</span>
                    @endif
                </div>
                <h1 class="cluster-title">{{ $cluster->name }}</h1>
                <div class="cluster-subtitle mt-2">
                    {{ $nodes->count() }} nodo(s) · {{ $totalQemu }} VM(s) · Última sync:
                    {{ $lastSyncAt ? \Carbon\Carbon::parse($lastSyncAt)->format('d/m/Y H:i') : 'sin registro' }}
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('proxmox.cluster') }}" class="btn btn-outline-light">← Volver</a>
                <a href="{{ route('proxmox.sync.cluster', ['name' => $cluster->name]) }}" class="btn btn-success">Sincronizar cluster</a>
                <a href="{{ route('proxmox.qemuByCluster.export', ['name' => $cluster->name]) }}" class="btn btn-outline-info">Exportar CSV</a>
            </div>
        </div>
    </div>

    {{-- ====================================================== --}}
    {{-- Fila de Tarjetas de Resumen --}}
    {{-- ====================================================== --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="metric-card">
                <div class="metric-label">Nodos</div>
                <div class="metric-value">{{ $nodes->count() }}</div>
                <div class="metric-foot">{{ $clusterTypeLabel }}</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="metric-card">
                <div class="metric-label">VMs totales</div>
                <div class="metric-value">{{ $totalQemu }}</div>
                <div class="metric-foot">Inventario sincronizado</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="metric-card">
                <div class="metric-label">Running</div>
                <div class="metric-value text-success">{{ $runningQemus }}</div>
                <div class="metric-foot">VMs encendidas</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="metric-card">
                <div class="metric-label">Apagadas</div>
                <div class="metric-value {{ $totalStoppedQemus > 0 ? 'text-danger' : 'text-success' }}">{{ $totalStoppedQemus }}</div>
                <div class="metric-foot">VMs detenidas</div>
            </div>
        </div>
        <div class="col-xl-4 col-md-8">
            <div class="metric-card">
                <div class="metric-label">Última sincronización</div>
                <div class="metric-value" style="font-size:1.35rem;">{{ $lastSyncAt ? \Carbon\Carbon::parse($lastSyncAt)->format('d/m/Y H:i') : 'Sin registro' }}</div>
                <div class="metric-foot">Canal dedicado: storage/logs/proxmox-sync-{{ now()->format('Y-m-d') }}.log</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="resource-card text-white">
                <div class="resource-head"><span class="resource-title">CPU</span><span class="resource-percent">{{ $cpuUsagePercentage }}%</span></div>
                <div class="progress cluster-progress"><div class="progress-bar {{ $cpuBarColor }}" style="width: {{ min($cpuUsagePercentage, 100) }}%"></div></div>
                <div class="metric-foot">Uso promedio sobre {{ $totalMaxCPU }} CPU(s)</div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="resource-card text-white">
                <div class="resource-head"><span class="resource-title">RAM</span><span class="resource-percent">{{ $memoryUsagePercentage }}%</span></div>
                <div class="progress cluster-progress"><div class="progress-bar {{ $memoryBarColor }}" style="width: {{ min($memoryUsagePercentage, 100) }}%"></div></div>
                <div class="metric-foot">{{ $totalUsedRAM }} GiB usados de {{ $totalMaxRAM }} GiB</div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="resource-card text-white">
                <div class="resource-head"><span class="resource-title">Almacenamiento total</span><span class="resource-percent">{{ $hasStorageTotals ? $storageUsagePercentage . '%' : 'S/D' }}</span></div>
                @if($hasStorageTotals)
                    <div class="progress cluster-progress"><div class="progress-bar {{ $storageBarColor }}" style="width: {{ min($storageUsagePercentage, 100) }}%"></div></div>
                    <div class="metric-foot">{{ $totalUsedStorage }} TB usados de {{ $totalMaxStorage }} TB</div>
                @else
                    <div class="storage-empty">Sin datos de storage medible reportados por API.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ================================================== --}}
    {{-- Nodos modernizados --}}
    {{-- ================================================== --}}
    <div class="section-titlebar">
        <div>
            <h2>Nodos</h2>
            <div class="section-subtitle">Estado operativo, recursos y última actualización por nodo.</div>
        </div>
        <span class="cluster-pill">{{ $nodes->count() }} nodo(s)</span>
    </div>
    @if($nodes->count() <= 3)
        <div class="row g-3 mb-4">
            @forelse ($nodes as $node)
                @php
                    $nodeRamUsage = ($node->maxmem > 0) ? round(($node->mem / $node->maxmem) * 100, 1) : 0;
                    $nodeCpuUsage = round($node->cpu * 100, 1);
                    $nodeDiskUsage = ($node->maxdisk > 0) ? round(($node->disk / $node->maxdisk) * 100, 1) : 0;
                    $nodeRamColor = $nodeRamUsage >= 85 ? 'bg-danger' : ($nodeRamUsage >= 70 ? 'bg-warning' : 'bg-success');
                    $nodeCpuColor = $nodeCpuUsage >= 85 ? 'bg-danger' : ($nodeCpuUsage >= 70 ? 'bg-warning' : 'bg-success');
                    $nodeDiskColor = $nodeDiskUsage >= 85 ? 'bg-danger' : ($nodeDiskUsage >= 70 ? 'bg-warning' : 'bg-success');
                @endphp
                <div class="col-xl-4 col-lg-6">
                    <div class="modern-node-card">
                        <div class="modern-node-head">
                            <div>
                                <div class="modern-node-name">{{ $node->node }}</div>
                                <div class="modern-node-meta">{{ $node->ip }} · {{ $node->maxcpu }} cores · uptime {{ floor($node->uptime / 86400) }} días</div>
                            </div>
                            @if($node->status == 'online')
                                <span class="badge-soft running">Online</span>
                            @else
                                <span class="badge-soft stopped">{{ $node->status }}</span>
                            @endif
                        </div>
                        <div class="mini-stat-grid">
                            <div class="mini-stat"><div class="mini-stat-label">RAM total</div><div class="mini-stat-value">{{ round($node->maxmem / (1024*1024*1024), 2) }} GB</div></div>
                            <div class="mini-stat"><div class="mini-stat-label">Disco</div><div class="mini-stat-value">{{ round($node->maxdisk / (1024*1024*1024), 2) }} GB</div></div>
                            <div class="mini-stat"><div class="mini-stat-label">Sync</div><div class="mini-stat-value">{{ \Carbon\Carbon::parse($node->updated_at)->format('H:i') }}</div></div>
                        </div>
                        <div class="node-resource-row">
                            <div class="label-line"><span>CPU</span><strong>{{ $nodeCpuUsage }}%</strong></div>
                            <div class="progress cluster-progress"><div class="progress-bar {{ $nodeCpuColor }}" style="width: {{ min($nodeCpuUsage, 100) }}%"></div></div>
                        </div>
                        <div class="node-resource-row">
                            <div class="label-line"><span>RAM</span><strong>{{ $nodeRamUsage }}%</strong></div>
                            <div class="progress cluster-progress"><div class="progress-bar {{ $nodeRamColor }}" style="width: {{ min($nodeRamUsage, 100) }}%"></div></div>
                            <div class="metric-foot">{{ round($node->mem / (1024*1024*1024), 2) }} GB / {{ round($node->maxmem / (1024*1024*1024), 2) }} GB</div>
                        </div>
                        <div class="node-resource-row">
                            <div class="label-line"><span>Disco root</span><strong>{{ $nodeDiskUsage }}%</strong></div>
                            <div class="progress cluster-progress"><div class="progress-bar {{ $nodeDiskColor }}" style="width: {{ min($nodeDiskUsage, 100) }}%"></div></div>
                            <div class="metric-foot">{{ round($node->disk / (1024*1024*1024), 2) }} GB / {{ round($node->maxdisk / (1024*1024*1024), 2) }} GB</div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="storage-empty">No se encontraron nodos para este cluster.</div></div>
            @endforelse
        </div>
    @else
        <div class="modern-panel mb-4">
            <div class="table-responsive">
                <table class="table table-dark table-hover table-bordered align-middle modern-table node-compact-table mb-0">
                    <thead class="text-center">
                        <tr>
                            <th>Nodo</th>
                            <th>Estado</th>
                            <th>IP / SO</th>
                            <th>Cores</th>
                            <th>CPU</th>
                            <th>RAM</th>
                            <th>Disco root</th>
                            <th>Uptime</th>
                            <th>Sync</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($nodes as $node)
                            @php
                                $nodeRamUsage = ($node->maxmem > 0) ? round(($node->mem / $node->maxmem) * 100, 1) : 0;
                                $nodeCpuUsage = round($node->cpu * 100, 1);
                                $nodeDiskUsage = ($node->maxdisk > 0) ? round(($node->disk / $node->maxdisk) * 100, 1) : 0;
                                $nodeRamColor = $nodeRamUsage >= 85 ? 'bg-danger' : ($nodeRamUsage >= 70 ? 'bg-warning' : 'bg-success');
                                $nodeCpuColor = $nodeCpuUsage >= 85 ? 'bg-danger' : ($nodeCpuUsage >= 70 ? 'bg-warning' : 'bg-success');
                                $nodeDiskColor = $nodeDiskUsage >= 85 ? 'bg-danger' : ($nodeDiskUsage >= 70 ? 'bg-warning' : 'bg-success');
                            @endphp
                            <tr>
                                <td><div class="vm-name-main">{{ $node->node }}</div></td>
                                <td class="text-center">
                                    @if($node->status == 'online')
                                        <span class="badge-soft running">Online</span>
                                    @else
                                        <span class="badge-soft stopped">{{ $node->status }}</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $node->ip }}</td>
                                <td class="text-center">{{ $node->maxcpu }}</td>
                                <td class="compact-resource">
                                    <div class="label-line"><span></span><strong>{{ $nodeCpuUsage }}%</strong></div>
                                    <div class="progress cluster-progress"><div class="progress-bar {{ $nodeCpuColor }}" style="width: {{ min($nodeCpuUsage, 100) }}%"></div></div>
                                </td>
                                <td class="compact-resource">
                                    <div class="label-line"><span></span><strong>{{ $nodeRamUsage }}%</strong></div>
                                    <div class="progress cluster-progress"><div class="progress-bar {{ $nodeRamColor }}" style="width: {{ min($nodeRamUsage, 100) }}%"></div></div>
                                    <div class="vm-name-sub">{{ round($node->mem / (1024*1024*1024), 1) }} / {{ round($node->maxmem / (1024*1024*1024), 1) }} GB</div>
                                </td>
                                <td class="compact-resource">
                                    <div class="label-line"><span></span><strong>{{ $nodeDiskUsage }}%</strong></div>
                                    <div class="progress cluster-progress"><div class="progress-bar {{ $nodeDiskColor }}" style="width: {{ min($nodeDiskUsage, 100) }}%"></div></div>
                                    <div class="vm-name-sub">{{ round($node->disk / (1024*1024*1024), 1) }} / {{ round($node->maxdisk / (1024*1024*1024), 1) }} GB</div>
                                </td>
                                <td class="text-center">{{ floor($node->uptime / 86400) }} días</td>
                                <td class="text-center vm-name-sub">{{ \Carbon\Carbon::parse($node->updated_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center">No se encontraron nodos para este cluster.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- Máquinas Virtuales modernizadas --}}
    {{-- ============================================================ --}}
    <div class="section-titlebar">
        <div>
            <h2>Máquinas Virtuales</h2>
            <div class="section-subtitle">{{ $totalQemu }} VMs · {{ $runningQemus }} running · {{ $totalStoppedQemus }} apagadas</div>
        </div>
    </div>
    <div class="modern-panel mb-4">
        <div class="vm-toolbar">
            <input id="vmSearch" type="search" class="vm-search" placeholder="Buscar por nombre, VMID, IP, SO, nodo o storage...">
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="vm-filter active" data-filter="all">Todas</button>
                <button type="button" class="vm-filter" data-filter="running">Running</button>
                <button type="button" class="vm-filter" data-filter="stopped">Apagadas</button>
                <button type="button" class="vm-filter" data-filter="highcpu">CPU alta</button>
                <button type="button" class="vm-filter" data-filter="highram">RAM alta</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-hover table-bordered align-middle modern-table mb-0">
                <thead class="text-center">
                    <tr>
                        <th>VMID</th>
                        <th>VM</th>
                        <th>IP</th>
                        <th>Estado</th>
                        <th>Nodo</th>
                        <th>vCPU</th>
                        <th>CPU</th>
                        <th>RAM</th>
                        <th>Disco</th>
                        <th>Storage</th>
                        <th>Actualizado</th>
                    </tr>
                </thead>
                <tbody id="vmTableBody">
                    @forelse ($qemus as $qemu)
                        @php
                            $vmCpuUsage = round($qemu->cpu * 100, 1);
                            $vmCpuColor = $vmCpuUsage >= 85 ? 'bg-danger' : ($vmCpuUsage >= 70 ? 'bg-warning' : 'bg-success');
                            $vmRamUsage = ($qemu->maxmem > 0) ? round(($qemu->mem / $qemu->maxmem) * 100, 1) : 0;
                            $vmRamColor = $vmRamUsage >= 85 ? 'bg-danger' : ($vmRamUsage >= 70 ? 'bg-warning' : 'bg-success');
                            $provisionedSize = $qemu->size;
                            $provisionedUnit = 'GB';
                            $provisionedValue = round($provisionedSize / $GB_DIVISOR, 2);
                            if ($provisionedValue > 1000) {
                                $provisionedValue = round($provisionedSize / $TB_DIVISOR, 2);
                                $provisionedUnit = 'TB';
                            }
                            $vmStatusFilter = strtolower($qemu->status) === 'running' ? 'running' : 'stopped';
                            $vmIp = trim((string)($qemu->ip_address ?? ''));
                            $vmOs = trim((string)($qemu->os_name ?? ''));
                            $vmSearchText = strtolower(($qemu->name ?? '') . ' ' . $qemu->vmid . ' ' . $vmIp . ' ' . $vmOs . ' ' . str_replace('node/', '', $qemu->node_id) . ' ' . ($qemu->storageName ?? ''));
                        @endphp
                        <tr class="vm-row {{ $vmStatusFilter === 'stopped' ? 'vm-stopped' : '' }}"
                            data-status="{{ $vmStatusFilter }}"
                            data-cpu="{{ $vmCpuUsage }}"
                            data-ram="{{ $vmRamUsage }}"
                            data-search="{{ e($vmSearchText) }}">
                            <td class="text-center"><span class="badge-soft font-monospace">{{ $qemu->vmid }}</span></td>
                            <td><div class="vm-name-main">{{ $qemu->name }}</div></td>
                            <td class="text-center">
                                <div class="fw-bold font-monospace text-white">{{ $vmIp !== '' ? $vmIp : '-' }}</div>
                                <div class="vm-name-sub mt-1">{{ $vmOs !== '' ? $vmOs : '-' }}</div>
                            </td>
                            <td class="text-center"><span class="badge-soft {{ $vmStatusFilter }}">{{ $qemu->status }}</span></td>
                            <td class="text-center">{{ str_replace('node/', '', $qemu->node_id) }}</td>
                            <td class="text-center">{{ $qemu->maxcpu }}</td>
                            <td style="min-width: 145px;">
                                <div class="label-line"><span></span><strong>{{ $vmCpuUsage }}%</strong></div>
                                <div class="progress cluster-progress"><div class="progress-bar {{ $vmCpuColor }}" style="width: {{ min($vmCpuUsage, 100) }}%"></div></div>
                            </td>
                            <td style="min-width: 170px;">
                                <div class="label-line"><span></span><strong>{{ $vmRamUsage }}%</strong></div>
                                <div class="progress cluster-progress"><div class="progress-bar {{ $vmRamColor }}" style="width: {{ min($vmRamUsage, 100) }}%"></div></div>
                                <div class="vm-name-sub">{{ round($qemu->mem / (1024*1024*1024), 2) }} / {{ round($qemu->maxmem / (1024*1024*1024), 2) }} GB</div>
                            </td>
                            <td class="table-numeric">{{ $provisionedValue }} {{ $provisionedUnit }}</td>
                            <td class="text-center"><span class="badge-soft">{{ $qemu->storageName ?: 'S/D' }}</span></td>
                            <td class="text-center vm-name-sub">{{ \Carbon\Carbon::parse($qemu->updated_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center">No se encontraron máquinas virtuales para este cluster.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div id="vmEmptyFilter" class="vm-empty-filter">No hay VMs que coincidan con el filtro aplicado.</div>
        </div>
    </div>
    {{-- ============================================================== --}}
    {{-- Almacenamiento modernizado --}}
    {{-- ============================================================== --}}
    <div class="section-titlebar">
        <div>
            <h2>Almacenamiento</h2>
            <div class="section-subtitle">Volúmenes de datos, backup y storage local en formato compacto.</div>
        </div>
        <span class="cluster-pill">{{ count($dataStorages) + count($backupStorages) }} storage(s)</span>
    </div>

    <div class="storage-section-grid mb-4">
        <div class="modern-panel">
            <div class="section-titlebar" style="margin-top:0;">
                <div>
                    <h2 style="font-size:1.25rem;">Volúmenes de Datos</h2>
                    <div class="section-subtitle">Storage productivo del cluster.</div>
                </div>
            </div>
            <div class="storage-list">
                @forelse ($dataStorages as $storage)
                    @php
                        $percentage = round($storage->used * 100, 1);
                        $storageColor = $percentage >= 85 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success');
                        $used = ($storage->maxdisk < $TB_DIVISOR) ? round($storage->disk / $GB_DIVISOR, 2) . " GB" : round($storage->disk / $TB_DIVISOR, 2) . " TB";
                        $total = ($storage->maxdisk < $TB_DIVISOR) ? round($storage->maxdisk / $GB_DIVISOR, 2) . " GB" : round($storage->maxdisk / $TB_DIVISOR, 2) . " TB";
                        $hasCapacity = $storage->maxdisk > 0;
                    @endphp
                    <div class="storage-row-card">
                        <div class="storage-row-head">
                            <div style="min-width:0;">
                                <div class="storage-name" title="{{ $storage->storage }}">{{ $storage->storage }}</div>
                                <div class="storage-meta"><span class="badge-soft">{{ $storage->plugintype }}</span></div>
                            </div>
                            <div class="storage-percent">{{ $hasCapacity ? $percentage . '%' : 'S/D' }}</div>
                        </div>
                        @if($hasCapacity)
                            <div class="progress cluster-progress"><div class="progress-bar {{ $storageColor }}" style="width: {{ min($percentage, 100) }}%"></div></div>
                            <div class="storage-usage-line"><span>{{ $used }}</span><span>{{ $total }}</span></div>
                        @else
                            <div class="storage-empty" style="padding:.7rem;">Sin capacidad reportada por API.</div>
                        @endif
                    </div>
                @empty
                    <div class="storage-empty">No se encontraron volúmenes de datos.</div>
                @endforelse
            </div>
        </div>

        <div class="modern-panel">
            <div class="section-titlebar" style="margin-top:0;">
                <div>
                    <h2 style="font-size:1.25rem;">Almacenamiento de Backup</h2>
                    <div class="section-subtitle">Repositorios y destinos de respaldo.</div>
                </div>
            </div>
            <div class="storage-list">
                @forelse ($backupStorages as $storage)
                    @php
                        $percentage = round($storage->used * 100, 1);
                        $storageColor = $percentage >= 85 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success');
                        $used = ($storage->maxdisk < $TB_DIVISOR) ? round($storage->disk / $GB_DIVISOR, 2) . " GB" : round($storage->disk / $TB_DIVISOR, 2) . " TB";
                        $total = ($storage->maxdisk < $TB_DIVISOR) ? round($storage->maxdisk / $GB_DIVISOR, 2) . " GB" : round($storage->maxdisk / $TB_DIVISOR, 2) . " TB";
                        $hasCapacity = $storage->maxdisk > 0;
                    @endphp
                    <div class="storage-row-card">
                        <div class="storage-row-head">
                            <div style="min-width:0;">
                                <div class="storage-name" title="{{ $storage->storage }}">{{ $storage->storage }}</div>
                                <div class="storage-meta"><span class="badge-soft">{{ $storage->plugintype }}</span></div>
                            </div>
                            <div class="storage-percent">{{ $hasCapacity ? $percentage . '%' : 'S/D' }}</div>
                        </div>
                        @if($hasCapacity)
                            <div class="progress cluster-progress"><div class="progress-bar {{ $storageColor }}" style="width: {{ min($percentage, 100) }}%"></div></div>
                            <div class="storage-usage-line"><span>{{ $used }}</span><span>{{ $total }}</span></div>
                        @else
                            <div class="storage-empty" style="padding:.7rem;">Sin capacidad reportada por API.</div>
                        @endif
                    </div>
                @empty
                    <div class="storage-empty">No se encontraron almacenamientos de backup.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="section-titlebar">
        <div>
            <h2>Almacenamiento Local</h2>
            <div class="section-subtitle">Storage local agrupado por nodo.</div>
        </div>
    </div>
    <div class="row g-3">
        @forelse ($localStoragesByNode as $nodeName => $storages)
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="local-storage-node">
                    <div class="local-storage-node-title">{{ str_replace('node/', '', $nodeName) }}</div>
                    @foreach ($storages as $storage)
                        @php
                            $percentage = round($storage->used * 100, 1);
                            $storageColor = $percentage >= 85 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success');
                            $used = ($storage->maxdisk < $TB_DIVISOR) ? round($storage->disk / $GB_DIVISOR, 2) . " GB" : round($storage->disk / $TB_DIVISOR, 2) . " TB";
                            $total = ($storage->maxdisk < $TB_DIVISOR) ? round($storage->maxdisk / $GB_DIVISOR, 2) . " GB" : round($storage->maxdisk / $TB_DIVISOR, 2) . " TB";
                            $hasCapacity = $storage->maxdisk > 0;
                        @endphp
                        <div class="local-storage-item">
                            <div class="storage-row-head" style="margin-bottom:.35rem;">
                                <div class="storage-name" title="{{ $storage->storage }}">{{ $storage->storage }}</div>
                                <div class="storage-percent">{{ $hasCapacity ? $percentage . '%' : 'S/D' }}</div>
                            </div>
                            @if($hasCapacity)
                                <div class="progress cluster-progress"><div class="progress-bar {{ $storageColor }}" style="width: {{ min($percentage, 100) }}%"></div></div>
                                <div class="storage-usage-line"><span>{{ $used }}</span><span>{{ $total }}</span></div>
                            @else
                                <div class="vm-name-sub">Sin capacidad reportada.</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="col-12"><div class="storage-empty">No se encontraron almacenamientos locales.</div></div>
        @endforelse
    </div>
</div>
@endsection

{{-- ============================================================== --}}
{{-- SECCIÓN DE SCRIPTS UNIFICADA (SOLO HAY UNA) --}}
{{-- ============================================================== --}}
@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {

        // --- FUNCIÓN DE COLOR (Definida una sola vez) ---
        function getColorByPercentage(percentage) {
            // Asegurarse de que el porcentaje sea un número
            percentage = parseFloat(percentage) || 0;

            if (percentage <= 50) {
                return '#28a745'; // Verde
            } else if (percentage <= 79) {
                return '#ffc107'; // Amarillo
            } else {
                return '#dc3545'; // Rojo
            }
        }

        // --- GRÁFICOS PRINCIPALES (CPU, RAM, Storage Total) ---
        const mainChartOptions = {
            cutout: '80%',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            }
        };

        // --- Gráfico de CPU ---
        const cpuCanvas = document.getElementById('cpuChart');
        if (cpuCanvas) {
            const cpuPercentage = {{ (float)$cpuUsagePercentage }};
            const cpuColor = getColorByPercentage(cpuPercentage);
            new Chart(cpuCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [cpuPercentage, 100 - cpuPercentage],
                        backgroundColor: [cpuColor, '#343a40'],
                        borderColor: ['#2c3034', '#2c3034'],
                        borderWidth: 2
                    }]
                },
                options: mainChartOptions
            });
        }

        // --- Gráfico de Memoria ---
        const memoryCanvas = document.getElementById('memoryChart');
        if (memoryCanvas) {
            const memoryPercentage = {{ (float)$memoryUsagePercentage }};
            const memoryColor = getColorByPercentage(memoryPercentage);
            new Chart(memoryCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [memoryPercentage, 100 - memoryPercentage],
                        backgroundColor: [memoryColor, '#343a40'],
                        borderColor: ['#2c3034', '#2c3034'],
                        borderWidth: 2
                    }]
                },
                options: mainChartOptions
            });
        }

        // --- Gráfico de Almacenamiento Total ---
        const storageCanvas = document.getElementById('storageChart');
        if (storageCanvas) {
            const storagePercentage = {{ (float)$storageUsagePercentage }};
            const storageColor = getColorByPercentage(storagePercentage);
            new Chart(storageCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [storagePercentage, 100 - storagePercentage],
                        backgroundColor: [storageColor, '#343a40'],
                        borderColor: ['#2c3034', '#2c3034'],
                        borderWidth: 2
                    }]
                },
                options: mainChartOptions
            });
        }

        // --- Filtro client-side de VMs ---
        const vmSearch = document.getElementById('vmSearch');
        const vmFilters = document.querySelectorAll('.vm-filter');
        const vmRows = document.querySelectorAll('.vm-row');
        const vmEmptyFilter = document.getElementById('vmEmptyFilter');
        let activeVmFilter = 'all';

        function applyVmFilters() {
            const query = (vmSearch?.value || '').toLowerCase().trim();
            let visible = 0;
            vmRows.forEach(row => {
                const matchesSearch = !query || (row.dataset.search || '').includes(query);
                const status = row.dataset.status;
                const cpu = parseFloat(row.dataset.cpu || '0');
                const ram = parseFloat(row.dataset.ram || '0');
                let matchesFilter = true;
                if (activeVmFilter === 'running') matchesFilter = status === 'running';
                if (activeVmFilter === 'stopped') matchesFilter = status === 'stopped';
                if (activeVmFilter === 'highcpu') matchesFilter = cpu >= 70;
                if (activeVmFilter === 'highram') matchesFilter = ram >= 70;
                const show = matchesSearch && matchesFilter;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            if (vmEmptyFilter) vmEmptyFilter.style.display = visible === 0 && vmRows.length > 0 ? 'block' : 'none';
        }

        if (vmSearch) vmSearch.addEventListener('input', applyVmFilters);
        vmFilters.forEach(btn => {
            btn.addEventListener('click', () => {
                vmFilters.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                activeVmFilter = btn.dataset.filter || 'all';
                applyVmFilters();
            });
        });

        // ==============================================================
        // === CÓDIGO PARA LOS GRÁFICOS DE ALMACENAMIENTO (GAUGES) ===
        // ==============================================================

        // Opciones para los gauges grandes (Datos y Backup)
        const gaugeOptions = {
            cutout: '80%',
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            }
        };

        // Opciones para los gauges pequeños de los nodos locales
        const localGaugeOptions = {
            cutout: '75%', // Un poco más grueso
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            }
        };

        // Selecciona TODOS los canvas con la clase .storage-gauge
        const storageCanvases = document.querySelectorAll('.storage-gauge');

        storageCanvases.forEach(canvas => {
            const percentage = parseFloat(canvas.dataset.percentage);
            const color = getColorByPercentage(percentage);

            // Determina si es un gráfico local pequeño o uno grande
            const options = canvas.closest('.local-gauge-card') ? localGaugeOptions : gaugeOptions;

            new Chart(canvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [percentage, 100 - percentage],
                        backgroundColor: [color, '#343a40'],
                        borderColor: ['#2c3034', '#2c3034'],
                        borderWidth: 2
                    }]
                },
                options: options
            });
        });

    }); // <-- FIN del addEventListener 'DOMContentLoaded'
</script>
@endsection
