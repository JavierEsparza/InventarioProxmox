@extends('layouts.app')

@section('content')
<style>
    .page-hero {
        background: linear-gradient(135deg, #101820 0%, #172330 58%, #0f5132 155%);
        border: 1px solid #2f3b49;
        border-radius: 18px;
        padding: 1.25rem;
        color: #fff;
        box-shadow: 0 16px 35px rgba(0,0,0,.28);
    }
    .page-title { font-size: clamp(1.7rem, 2.6vw, 2.45rem); font-weight: 850; letter-spacing: -.03em; margin: 0; }
    .page-subtitle { color: #aab7c4; font-size: .95rem; margin-top: .4rem; }
    .cluster-pill { display:inline-flex; align-items:center; gap:.35rem; border:1px solid #344455; background:rgba(255,255,255,.06); color:#d7e2ec; border-radius:999px; padding:.35rem .65rem; font-size:.82rem; font-weight:750; }
    .cluster-pill.success { border-color:rgba(25,135,84,.6); background:rgba(25,135,84,.18); color:#91e7b4; }
    .metric-card { background:#151d26; border:1px solid #2b3846; border-radius:16px; padding:1rem; color:#fff; height:100%; box-shadow:inset 0 1px 0 rgba(255,255,255,.03); }
    .metric-label { color:#96a6b5; font-size:.74rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
    .metric-value { font-size:2rem; font-weight:850; line-height:1; margin-top:.35rem; }
    .metric-foot { color:#8d9baa; font-size:.82rem; margin-top:.5rem; }
    .modern-panel { background:#0f1821; border:1px solid #1f3345; border-radius:18px; padding:1rem; color:#fff; box-shadow:0 12px 28px rgba(0,0,0,.18); }
    .node-toolbar { display:flex; flex-wrap:wrap; justify-content:space-between; gap:.75rem; margin-bottom:1rem; }
    .node-search { background:#0d141c; border:1px solid #324254; color:#e8eef5; border-radius:12px; padding:.65rem .85rem; min-width:320px; }
    .node-search::placeholder { color:#7f8d9a; }
    .node-filter { border:1px solid #344455; background:rgba(255,255,255,.04); color:#dce6ef; border-radius:999px; padding:.45rem .8rem; font-weight:750; font-size:.84rem; }
    .node-filter.active { background:rgba(25,135,84,.25); border-color:rgba(25,135,84,.7); color:#94ebb9; }
    .modern-table { --bs-table-bg:#0f1821; --bs-table-border-color:#223346; --bs-table-color:#e5edf4; border-radius:14px; overflow:hidden; }
    .modern-table thead th { background:#0c151d !important; color:#8fa3b7; font-size:.74rem; text-transform:uppercase; letter-spacing:.07em; border-bottom:1px solid #344455; white-space:nowrap; }
    .modern-table tbody td { background:#0f1821 !important; border-color:#223346; vertical-align:middle; }
    .modern-table tbody tr:nth-child(even) td { background:#101a24 !important; }
    .modern-table tbody tr:hover td { background:#142536 !important; }
    .node-name-main { font-weight:850; color:#fff; }
    .node-name-sub { color:#8c9baa; font-size:.78rem; }
    .badge-soft { border:1px solid #344455; background:rgba(255,255,255,.06); color:#dbe5ee; border-radius:999px; padding:.3rem .55rem; }
    .badge-soft.running { border-color:rgba(25,135,84,.65); background:rgba(25,135,84,.18); color:#91e7b4; }
    .badge-soft.stopped { border-color:rgba(108,117,125,.65); background:rgba(108,117,125,.18); color:#c9d0d6; }
    .progress.cluster-progress { height:10px; background:#273240; border-radius:999px; overflow:hidden; min-width:110px; }
    .resource-cell { min-width:155px; }
    .label-line { display:flex; justify-content:center; color:#d7e3ee; font-size:.82rem; margin-bottom:.22rem; text-align:center; }
    .table-numeric { text-align:right; font-variant-numeric:tabular-nums; }
    .action-btn { border-radius:10px; font-weight:750; }
    .node-empty-filter { display:none; color:#aeb9c5; text-align:center; padding:1rem; }
</style>

@php
    $totalNodes = $nodes->count();
    $onlineNodes = $nodes->filter(function($node) { return $node->online || $node->status === 'online'; })->count();
    $offlineNodes = max($totalNodes - $onlineNodes, 0);
    $totalCores = $nodes->sum('maxcpu');
    $clustersCount = $nodes->pluck('cluster_name')->filter()->unique()->count();
    $avgCpu = $nodes->count() ? round($nodes->avg(function($node) { return $node->cpu * 100; }), 1) : 0;
    $totalMem = $nodes->sum('maxmem');
    $usedMem = $nodes->sum('mem');
    $avgRam = $totalMem > 0 ? round(($usedMem / $totalMem) * 100, 1) : 0;
@endphp

<div class="container-fluid mt-4 px-4">
    <div class="page-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="cluster-pill success">● {{ $onlineNodes }} online</span>
                    <span class="cluster-pill">{{ $clustersCount }} cluster(s)</span>
                    <span class="cluster-pill">{{ $totalCores }} cores</span>
                </div>
                <h1 class="page-title">Nodos Proxmox</h1>
                <div class="page-subtitle">Inventario global de nodos, recursos y última sincronización.</div>
            </div>
            <a href="{{ route('proxmox.exportNode') }}" class="btn btn-success">Exportar a Excel</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Nodos</div><div class="metric-value">{{ $totalNodes }}</div><div class="metric-foot">Total inventariado</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Online</div><div class="metric-value text-success">{{ $onlineNodes }}</div><div class="metric-foot">Operativos</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Offline</div><div class="metric-value {{ $offlineNodes > 0 ? 'text-danger' : 'text-success' }}">{{ $offlineNodes }}</div><div class="metric-foot">Sin servicio/API</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Clusters</div><div class="metric-value">{{ $clustersCount }}</div><div class="metric-foot">Con nodos asociados</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">CPU prom.</div><div class="metric-value">{{ $avgCpu }}%</div><div class="metric-foot">Carga media</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">RAM global</div><div class="metric-value">{{ $avgRam }}%</div><div class="metric-foot">Uso agregado</div></div></div>
    </div>

    <div class="modern-panel">
        <div class="node-toolbar">
            <form action="{{ route('proxmox.searchNode') }}" method="GET" class="d-flex gap-2 flex-wrap">
                <input id="nodeSearch" type="search" class="node-search" placeholder="Buscar por cluster, nodo o IP..." name="search" value="{{ request('search') }}">
                <button class="btn btn-outline-info" type="submit">Buscar</button>
                @if(request('search'))
                    <a class="btn btn-outline-light" href="{{ route('proxmox.node') }}">Limpiar</a>
                @endif
            </form>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="node-filter active" data-filter="all">Todos</button>
                <button type="button" class="node-filter" data-filter="online">Online</button>
                <button type="button" class="node-filter" data-filter="offline">Offline</button>
                <button type="button" class="node-filter" data-filter="highcpu">CPU alta</button>
                <button type="button" class="node-filter" data-filter="highram">RAM alta</button>
                <button type="button" class="node-filter" data-filter="highdisk">Disco alto</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover table-bordered align-middle modern-table mb-0">
                <thead class="text-center">
                    <tr>
                        <th>Cluster / Nodo</th>
                        <th>Estado</th>
                        <th>CPU</th>
                        <th>RAM</th>
                        <th>Disco root</th>
                        <th>IP</th>
                        <th>Uptime</th>
                        <th>Última sync</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="nodeTableBody">
                    @forelse ($nodes as $node)
                        @php
                            $cpuPercent = round($node->cpu * 100, 1);
                            $memPercent = ($node->maxmem > 0) ? round(($node->mem / $node->maxmem) * 100, 1) : 0;
                            $diskPercent = ($node->maxdisk > 0) ? round(($node->disk / $node->maxdisk) * 100, 1) : 0;
                            $cpuColorClass = $cpuPercent >= 85 ? 'bg-danger' : ($cpuPercent >= 70 ? 'bg-warning' : 'bg-success');
                            $memColorClass = $memPercent >= 85 ? 'bg-danger' : ($memPercent >= 70 ? 'bg-warning' : 'bg-success');
                            $diskColorClass = $diskPercent >= 85 ? 'bg-danger' : ($diskPercent >= 70 ? 'bg-warning' : 'bg-success');
                            $formattedDisk = 'S/D';
                            if ($node->maxdisk > 0) {
                                if ($node->maxdisk >= 1099511627776) {
                                    $formattedDisk = round($node->disk / 1099511627776, 2) . ' / ' . round($node->maxdisk / 1099511627776, 2) . ' TB';
                                } else {
                                    $formattedDisk = round($node->disk / 1073741824, 2) . ' / ' . round($node->maxdisk / 1073741824, 2) . ' GB';
                                }
                            }
                            $formattedMem = ($node->maxmem > 0) ? round($node->mem / 1073741824, 1) . ' / ' . round($node->maxmem / 1073741824, 1) . ' GB' : 'S/D';
                            $uptimeDays = floor($node->uptime / 86400);
                            $nodeOnline = ($node->online || $node->status === 'online') ? 'online' : 'offline';
                            $nodeSearchText = strtolower(($node->cluster_name ?? '') . ' ' . ($node->node ?? '') . ' ' . ($node->ip ?? ''));
                        @endphp
                        <tr class="node-row" data-status="{{ $nodeOnline }}" data-cpu="{{ $cpuPercent }}" data-ram="{{ $memPercent }}" data-disk="{{ $diskPercent }}" data-search="{{ e($nodeSearchText) }}">
                            <td>
                                <div class="node-name-main">{{ $node->node }}</div>
                                <div class="node-name-sub">{{ $node->cluster_name ?? 'Sin cluster' }} · {{ $node->maxcpu }} cores</div>
                            </td>
                            <td class="text-center">
                                <span class="badge-soft {{ $nodeOnline === 'online' ? 'running' : 'stopped' }}">{{ ucfirst($nodeOnline) }}</span>
                            </td>
                            <td class="resource-cell">
                                <div class="label-line"><strong>{{ $cpuPercent }}%</strong></div>
                                <div class="progress cluster-progress"><div class="progress-bar {{ $cpuColorClass }}" style="width: {{ min($cpuPercent, 100) }}%"></div></div>
                            </td>
                            <td class="resource-cell">
                                <div class="label-line"><strong>{{ $memPercent }}%</strong></div>
                                <div class="progress cluster-progress"><div class="progress-bar {{ $memColorClass }}" style="width: {{ min($memPercent, 100) }}%"></div></div>
                                <div class="node-name-sub text-center">{{ $formattedMem }}</div>
                            </td>
                            <td class="resource-cell">
                                <div class="label-line"><strong>{{ $diskPercent }}%</strong></div>
                                <div class="progress cluster-progress"><div class="progress-bar {{ $diskColorClass }}" style="width: {{ min($diskPercent, 100) }}%"></div></div>
                                <div class="node-name-sub text-center">{{ $formattedDisk }}</div>
                            </td>
                            <td class="text-center">{{ $node->ip }}</td>
                            <td class="text-center">{{ $uptimeDays }} días</td>
                            <td class="text-center node-name-sub">{{ \Carbon\Carbon::parse($node->updated_at)->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="d-flex justify-content-center">
                                    <form action="{{ route('proxmox.cluster.node.destroy', $node->node) }}" method="POST" class="m-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm action-btn" onclick="return confirm('¿Estás seguro de querer borrar este nodo?');">Borrar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center py-4">No se encontraron nodos.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div id="nodeEmptyFilter" class="node-empty-filter">No hay nodos que coincidan con el filtro aplicado.</div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const nodeSearch = document.getElementById('nodeSearch');
    const nodeFilters = document.querySelectorAll('.node-filter');
    const nodeRows = document.querySelectorAll('.node-row');
    const nodeEmptyFilter = document.getElementById('nodeEmptyFilter');
    let activeNodeFilter = 'all';

    function applyNodeFilters() {
        const query = (nodeSearch?.value || '').toLowerCase().trim();
        let visible = 0;
        nodeRows.forEach(row => {
            const matchesSearch = !query || (row.dataset.search || '').includes(query);
            const status = row.dataset.status;
            const cpu = parseFloat(row.dataset.cpu || '0');
            const ram = parseFloat(row.dataset.ram || '0');
            const disk = parseFloat(row.dataset.disk || '0');
            let matchesFilter = true;
            if (activeNodeFilter === 'online') matchesFilter = status === 'online';
            if (activeNodeFilter === 'offline') matchesFilter = status === 'offline';
            if (activeNodeFilter === 'highcpu') matchesFilter = cpu >= 70;
            if (activeNodeFilter === 'highram') matchesFilter = ram >= 70;
            if (activeNodeFilter === 'highdisk') matchesFilter = disk >= 70;
            const show = matchesSearch && matchesFilter;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (nodeEmptyFilter) nodeEmptyFilter.style.display = visible === 0 && nodeRows.length > 0 ? 'block' : 'none';
    }

    if (nodeSearch) nodeSearch.addEventListener('input', applyNodeFilters);
    nodeFilters.forEach(btn => {
        btn.addEventListener('click', () => {
            nodeFilters.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeNodeFilter = btn.dataset.filter || 'all';
            applyNodeFilters();
        });
    });
});
</script>
@endsection
