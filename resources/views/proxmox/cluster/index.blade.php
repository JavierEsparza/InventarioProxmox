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
    $barClass = function($pct, $warn = 70, $crit = 85) {
        return $pct >= $crit ? 'danger' : ($pct >= $warn ? 'warn' : 'ok');
    };

    $totalClusters = $clusters->count();
    $totalNodes = $clusters->sum('node_count');
    $totalVmCount = $clusters->sum('vm_count');
    $totalRunningVms = $clusters->sum('running_vms');
    $totalStoppedVms = $clusters->sum('stopped_vms');
    $totalOfflineNodes = $clusters->sum('offline_nodes');
    $largestCluster = $clusters->sortByDesc('node_count')->first();
    $largestVmCluster = $clusters->sortByDesc('vm_count')->first();
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
    .toolbar { display:flex; flex-wrap:wrap; justify-content:space-between; gap:.75rem; margin-bottom:1rem; }
    .cluster-search { background:#0d141c; border:1px solid #324254; color:#e8eef5; border-radius:12px; padding:.65rem .85rem; min-width:320px; }
    .cluster-filter { border:1px solid #344455; background:rgba(255,255,255,.04); color:#dce6ef; border-radius:999px; padding:.45rem .8rem; font-weight:750; font-size:.84rem; }
    .cluster-filter.active { background:rgba(25,135,84,.25); border-color:rgba(25,135,84,.7); color:#94ebb9; }
    .cluster-card-modern { background:#111b25; border:1px solid #24384b; border-radius:16px; padding:1rem; height:100%; color:#fff; transition:.18s ease; box-shadow:inset 0 1px 0 rgba(255,255,255,.03); }
    .cluster-card-modern:hover { transform:translateY(-2px); border-color:#39715c; background:#122235; }
    .cluster-name { font-weight:850; font-size:1.05rem; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
    .big-number { font-size:2.2rem; font-weight:900; line-height:1; }
    .mini-label { color:#91a2b3; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
    .badge-soft { border:1px solid #344455; background:rgba(255,255,255,.06); color:#dbe5ee; border-radius:999px; padding:.3rem .55rem; font-size:.74rem; font-weight:800; white-space:nowrap; }
    .badge-soft.ok { border-color:rgba(25,135,84,.65); background:rgba(25,135,84,.18); color:#91e7b4; }
    .badge-soft.warn { border-color:rgba(255,193,7,.62); background:rgba(255,193,7,.14); color:#ffe08a; }
    .badge-soft.danger { border-color:rgba(220,53,69,.65); background:rgba(220,53,69,.18); color:#ff9ca8; }
    .resource-wrap { display:flex; align-items:center; gap:.45rem; min-width:100%; }
    .resource-pct { min-width:38px; text-align:right; color:#d6e1ea; font-size:.78rem; font-weight:800; }
    .resource-bar { height:7px; flex:1; background:#243241; border-radius:999px; overflow:hidden; }
    .resource-fill { height:100%; border-radius:999px; }
    .resource-fill.ok { background:#198754; }
    .resource-fill.warn { background:#ffc107; }
    .resource-fill.danger { background:#dc3545; }
    .modern-table { --bs-table-bg:#0f1821; --bs-table-border-color:#223346; --bs-table-color:#e5edf4; border-radius:14px; overflow:hidden; }
    .modern-table thead th { background:#0c151d !important; color:#8fa3b7; font-size:.72rem; text-transform:uppercase; letter-spacing:.07em; border-bottom:1px solid #344455; white-space:nowrap; }
    .modern-table tbody td { background:#0f1821 !important; border-color:#223346; vertical-align:middle; }
    .modern-table tbody tr:nth-child(even) td { background:#101a24 !important; }
    .modern-table tbody tr:hover td { background:#142536 !important; }
    .id-chip { font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; background:#07111a; border:1px solid #25384b; color:#b8e7ff; padding:.2rem .45rem; border-radius:8px; font-size:.78rem; }
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
                    <span class="cluster-pill ok">● Inventario de clusters</span>
                    <span class="cluster-pill">{{ $totalClusters }} cluster(s)</span>
                    <span class="cluster-pill">{{ $totalNodes }} nodo(s)</span>
                    <span class="cluster-pill {{ $totalOfflineNodes > 0 ? 'danger' : 'ok' }}">{{ $totalOfflineNodes }} nodo(s) offline</span>
                </div>
                <h1 class="page-title">Clusters Proxmox</h1>
                <div class="page-subtitle">Resumen operativo por cluster, nodos, VMs, estado y uso de recursos.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('proxmox.exportCluster') }}" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i> Exportar Excel</a>
                <a href="{{ route('proxmox.index') }}" class="btn btn-outline-light"><i class="bi bi-speedometer2 me-1"></i> Resumen</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Clusters</div><div class="metric-value">{{ $totalClusters }}</div><div class="metric-foot">Total registrados</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Nodos</div><div class="metric-value">{{ $totalNodes }}</div><div class="metric-foot">{{ $totalOfflineNodes }} offline</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">VMs</div><div class="metric-value">{{ $totalVmCount }}</div><div class="metric-foot">{{ $totalRunningVms }} running | {{ $totalStoppedVms }} apagadas</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Más nodos</div><div class="metric-value">{{ $largestCluster->node_count ?? 0 }}</div><div class="metric-foot">{{ $largestCluster->name ?? 'S/D' }}</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Más VMs</div><div class="metric-value">{{ $largestVmCluster->vm_count ?? 0 }}</div><div class="metric-foot">{{ $largestVmCluster->name ?? 'S/D' }}</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Promedio</div><div class="metric-value">{{ $totalClusters > 0 ? round($totalNodes / $totalClusters, 1) : 0 }}</div><div class="metric-foot">nodos por cluster</div></div></div>
    </div>

    <div class="modern-panel mb-4">
        <div class="toolbar">
            <input id="clusterSearch" class="cluster-search" type="search" placeholder="Filtrar por cluster o nodo...">
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="cluster-filter active" data-filter="all">Todos</button>
                <button type="button" class="cluster-filter" data-filter="single">Standalone / 1 nodo</button>
                <button type="button" class="cluster-filter" data-filter="large">Más nodos</button>
                <button type="button" class="cluster-filter" data-filter="alerts">Con alerta</button>
            </div>
        </div>

        <div class="row g-3" id="clusterGrid">
            @forelse ($clusters as $cluster)
                @php
                    $cpuPct = min(100, $cluster->avg_cpu_percent ?? 0);
                    $ramPct = min(100, $cluster->ram_percent ?? 0);
                    $hasAlert = ($cluster->offline_nodes ?? 0) > 0 || $cpuPct >= 80 || $ramPct >= 80;
                    $kind = ($cluster->node_count <= 1) ? 'single' : (($cluster->node_count >= 5) ? 'large' : 'normal');
                    $search = strtolower($cluster->name.' '.$cluster->node_names->implode(' '));
                @endphp
                <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 cluster-tile" data-search="{{ e($search) }}" data-kind="{{ $kind }}" data-alert="{{ $hasAlert ? '1' : '0' }}">
                    <div class="cluster-card-modern">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div class="cluster-name" title="{{ $cluster->name }}">{{ $cluster->name }}</div>
                            <span class="badge-soft {{ $hasAlert ? 'warn' : 'ok' }}">{{ $hasAlert ? 'alerta' : 'ok' }}</span>
                        </div>

                        <div class="row text-center g-2 mb-3">
                            <div class="col-4"><div class="big-number">{{ $cluster->node_count }}</div><div class="mini-label">Nodos</div></div>
                            <div class="col-4"><div class="big-number">{{ $cluster->vm_count }}</div><div class="mini-label">VMs</div></div>
                            <div class="col-4"><div class="big-number">{{ $cluster->running_vms }}</div><div class="mini-label">Running</div></div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge-soft {{ $cluster->offline_nodes > 0 ? 'danger' : 'ok' }}">{{ $cluster->online_nodes }}/{{ $cluster->node_records ?: $cluster->node_count }} online</span>
                            <span class="badge-soft">{{ $cluster->total_cpu }} cores</span>
                            <span class="badge-soft">{{ $formatBytes($cluster->total_ram) }} RAM</span>
                        </div>

                        <div class="mb-2">
                            <div class="d-flex justify-content-between small text-secondary mb-1"><span>CPU prom.</span><span>{{ round($cpuPct) }}%</span></div>
                            <div class="resource-wrap"><div class="resource-bar"><div class="resource-fill {{ $barClass($cpuPct, 50, 80) }}" style="width: {{ $cpuPct }}%"></div></div></div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small text-secondary mb-1"><span>RAM nodos</span><span>{{ round($ramPct) }}%</span></div>
                            <div class="resource-wrap"><div class="resource-bar"><div class="resource-fill {{ $barClass($ramPct, 70, 85) }}" style="width: {{ $ramPct }}%"></div></div></div>
                        </div>

                        <div class="mb-3 small text-secondary" title="{{ $cluster->node_names->implode(', ') }}">
                            <i class="bi bi-hdd-network me-1"></i>{{ $cluster->node_names_preview }}{{ $cluster->node_names->count() > 5 ? ', ...' : '' }}
                        </div>

                        <div class="d-flex gap-2">
                            <a class="btn btn-primary btn-sm flex-fill" href="{{ route('proxmox.cluster.show', $cluster->name) }}"><i class="bi bi-eye me-1"></i> Ver</a>
                            @can('manage cluster')
                                <form action="{{ route('proxmox.cluster.destroy', $cluster->name) }}" method="POST" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Estás seguro de querer borrar este cluster?');" title="Borrar cluster"><i class="bi bi-trash"></i></button>
                                </form>
                            @endcan
                        </div>
                        <div class="metric-foot mt-2">Act: {{ Carbon::parse($cluster->updated_at)->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="alert alert-secondary text-center">No se encontraron clusters.</div></div>
            @endforelse
        </div>
        <div id="clusterEmptyFilter" class="empty-filter">No hay clusters que coincidan con el filtro.</div>
    </div>

    <div class="modern-panel">
        <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3">
            <div><h5 class="mb-0">Tabla compacta</h5><div class="page-subtitle">Mismo inventario en formato operativo para comparar rápido.</div></div>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-hover table-bordered align-middle modern-table mb-0">
                <thead class="text-center"><tr><th class="text-start">Cluster</th><th>Nodos</th><th>Online</th><th>VMs</th><th>Running</th><th>CPU prom.</th><th>RAM</th><th class="text-start">Nodos miembros</th><th>Actualizado</th><th>Acción</th></tr></thead>
                <tbody class="text-center">
                    @foreach($clusters as $cluster)
                        @php $cpuPct = min(100, $cluster->avg_cpu_percent ?? 0); $ramPct = min(100, $cluster->ram_percent ?? 0); @endphp
                        <tr>
                            <td class="text-start fw-bold">{{ $cluster->name }}</td>
                            <td>{{ $cluster->node_count }}</td>
                            <td><span class="badge-soft {{ $cluster->offline_nodes > 0 ? 'danger' : 'ok' }}">{{ $cluster->online_nodes }}/{{ $cluster->node_records ?: $cluster->node_count }}</span></td>
                            <td>{{ $cluster->vm_count }}</td>
                            <td><span class="badge-soft ok">{{ $cluster->running_vms }}</span></td>
                            <td><div class="resource-wrap"><span class="resource-pct">{{ round($cpuPct) }}%</span><div class="resource-bar"><div class="resource-fill {{ $barClass($cpuPct, 50, 80) }}" style="width: {{ $cpuPct }}%"></div></div></div></td>
                            <td><div class="resource-wrap"><span class="resource-pct">{{ round($ramPct) }}%</span><div class="resource-bar"><div class="resource-fill {{ $barClass($ramPct, 70, 85) }}" style="width: {{ $ramPct }}%"></div></div></div><div class="small text-secondary mt-1">{{ $formatBytes($cluster->used_ram) }} / {{ $formatBytes($cluster->total_ram) }}</div></td>
                            <td class="text-start"><span class="id-chip" title="{{ $cluster->node_names->implode(', ') }}">{{ $cluster->node_names_preview }}{{ $cluster->node_names->count() > 5 ? ', ...' : '' }}</span></td>
                            <td class="text-secondary">{{ Carbon::parse($cluster->updated_at)->format('d/m/Y H:i') }}</td>
                            <td><a class="btn btn-sm btn-outline-light py-0" href="{{ route('proxmox.cluster.show', $cluster->name) }}">Ver</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('clusterSearch');
    const filters = document.querySelectorAll('.cluster-filter');
    const tiles = document.querySelectorAll('.cluster-tile');
    const empty = document.getElementById('clusterEmptyFilter');
    let activeFilter = 'all';

    function applyFilters() {
        const query = (search?.value || '').toLowerCase().trim();
        let visible = 0;
        tiles.forEach(tile => {
            const matchesSearch = !query || (tile.dataset.search || '').includes(query);
            let matchesFilter = true;
            if (activeFilter === 'single') matchesFilter = tile.dataset.kind === 'single';
            if (activeFilter === 'large') matchesFilter = tile.dataset.kind === 'large';
            if (activeFilter === 'alerts') matchesFilter = tile.dataset.alert === '1';
            const show = matchesSearch && matchesFilter;
            tile.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (empty) empty.style.display = visible === 0 && tiles.length > 0 ? 'block' : 'none';
    }
    if (search) search.addEventListener('input', applyFilters);
    filters.forEach(btn => btn.addEventListener('click', () => {
        filters.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeFilter = btn.dataset.filter || 'all';
        applyFilters();
    }));
});
</script>
@endsection
