@extends('layouts.app')

@section('content')
<style>
    .page-hero { background:linear-gradient(135deg,#101820 0%,#172330 58%,#0f5132 155%); border:1px solid #2f3b49; border-radius:18px; padding:1.25rem; color:#fff; box-shadow:0 16px 35px rgba(0,0,0,.28); }
    .page-title { font-size:clamp(1.7rem,2.6vw,2.45rem); font-weight:850; letter-spacing:-.03em; margin:0; }
    .page-subtitle { color:#aab7c4; font-size:.95rem; margin-top:.4rem; }
    .cluster-pill { display:inline-flex; align-items:center; gap:.35rem; border:1px solid #344455; background:rgba(255,255,255,.06); color:#d7e2ec; border-radius:999px; padding:.35rem .65rem; font-size:.82rem; font-weight:750; }
    .cluster-pill.success { border-color:rgba(25,135,84,.6); background:rgba(25,135,84,.18); color:#91e7b4; }
    .cluster-pill.warn { border-color:rgba(255,193,7,.62); background:rgba(255,193,7,.14); color:#ffe08a; }
    .cluster-pill.danger { border-color:rgba(220,53,69,.65); background:rgba(220,53,69,.16); color:#ff9ca8; }
    .metric-card { background:#151d26; border:1px solid #2b3846; border-radius:16px; padding:1rem; color:#fff; height:100%; box-shadow:inset 0 1px 0 rgba(255,255,255,.03); }
    .metric-label { color:#96a6b5; font-size:.74rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
    .metric-value { font-size:2rem; font-weight:850; line-height:1; margin-top:.35rem; }
    .metric-foot { color:#8d9baa; font-size:.82rem; margin-top:.5rem; }
    .modern-panel { background:#0f1821; border:1px solid #1f3345; border-radius:18px; padding:1rem; color:#fff; box-shadow:0 12px 28px rgba(0,0,0,.18); }
    .storage-toolbar { display:flex; flex-wrap:wrap; justify-content:space-between; gap:.75rem; margin-bottom:1rem; }
    .storage-search { background:#0d141c; border:1px solid #324254; color:#e8eef5; border-radius:12px; padding:.65rem .85rem; min-width:360px; }
    .storage-search::placeholder { color:#7f8d9a; }
    .storage-filter { border:1px solid #344455; background:rgba(255,255,255,.04); color:#dce6ef; border-radius:999px; padding:.45rem .8rem; font-weight:750; font-size:.84rem; }
    .storage-filter.active { background:rgba(25,135,84,.25); border-color:rgba(25,135,84,.7); color:#94ebb9; }
    .cluster-accordion .accordion-item { background:#0f1821; border:1px solid #223346; border-radius:16px; overflow:hidden; margin-bottom:.85rem; }
    .cluster-accordion .accordion-button { background:#0c151d; color:#eaf2f8; box-shadow:none; padding:.85rem 1rem; }
    .cluster-accordion .accordion-button:not(.collapsed) { background:#102033; color:#fff; }
    .cluster-accordion .accordion-button::after { filter:invert(1); }
    .accordion-title { font-weight:850; }
    .modern-table { --bs-table-bg:#0f1821; --bs-table-border-color:#223346; --bs-table-color:#e5edf4; border-radius:14px; overflow:hidden; }
    .modern-table thead th { background:#0c151d !important; color:#8fa3b7; font-size:.72rem; text-transform:uppercase; letter-spacing:.07em; border-bottom:1px solid #344455; white-space:nowrap; }
    .modern-table tbody td { background:#0f1821 !important; border-color:#223346; vertical-align:middle; }
    .modern-table tbody tr:nth-child(even) td { background:#101a24 !important; }
    .modern-table tbody tr:hover td { background:#142536 !important; }
    .storage-name-main { font-weight:850; color:#fff; }
    .storage-name-sub { color:#8c9baa; font-size:.78rem; }
    .badge-soft { border:1px solid #344455; background:rgba(255,255,255,.06); color:#dbe5ee; border-radius:999px; padding:.3rem .55rem; font-size:.74rem; font-weight:800; }
    .badge-soft.running { border-color:rgba(25,135,84,.65); background:rgba(25,135,84,.18); color:#91e7b4; }
    .badge-soft.warn { border-color:rgba(255,193,7,.62); background:rgba(255,193,7,.14); color:#ffe08a; }
    .badge-soft.danger { border-color:rgba(220,53,69,.65); background:rgba(220,53,69,.18); color:#ff9ca8; }
    .progress.cluster-progress { height:10px; background:#273240; border-radius:999px; overflow:hidden; min-width:140px; }
    .resource-cell { min-width:190px; }
    .label-line { display:flex; justify-content:center; color:#d7e3ee; font-size:.82rem; margin-bottom:.22rem; text-align:center; }
    .storage-node-list { color:#a9b7c5; font-size:.78rem; line-height:1.22; }
    .storage-empty-filter { display:none; color:#aeb9c5; text-align:center; padding:1rem; }
</style>

@php
    $allStorages = $storagesByCluster->flatten(1);
    $totalStorages = $allStorages->count();
    $clustersCount = $storagesByCluster->count();
    $criticalStorages = $allStorages->filter(fn($s) => $s->maxdisk > 0 && (($s->disk / $s->maxdisk) * 100) >= 85)->count();
    $warningStorages = $allStorages->filter(fn($s) => $s->maxdisk > 0 && (($s->disk / $s->maxdisk) * 100) >= 70 && (($s->disk / $s->maxdisk) * 100) < 85)->count();
    $backupStorages = $allStorages->filter(fn($s) => stripos($s->storage, 'backup') !== false || stripos($s->plugintype, 'pbs') !== false)->count();
    $totalUsedBytes = $allStorages->sum('disk');
    $totalMaxBytes = $allStorages->sum('maxdisk');
    $globalUsedPercent = $totalMaxBytes > 0 ? round(($totalUsedBytes / $totalMaxBytes) * 100, 1) : 0;
    $formatBytes = function($bytes) {
        $tb = 1000 * 1000 * 1000 * 1000;
        $gb = 1000 * 1000 * 1000;
        return $bytes >= $tb ? round($bytes / $tb, 2) . ' TB' : round($bytes / $gb, 2) . ' GB';
    };
@endphp

<div class="container-fluid mt-4 px-4">
    <div class="page-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="cluster-pill success">● habilitados</span>
                    <span class="cluster-pill">{{ $clustersCount }} cluster(s)</span>
                    <span class="cluster-pill">{{ $totalStorages }} storage(s)</span>
                    @if($criticalStorages > 0)<span class="cluster-pill danger">{{ $criticalStorages }} críticos</span>@endif
                </div>
                <h1 class="page-title">Storages Proxmox</h1>
                <div class="page-subtitle">Vista global agrupada por cluster, con uso, capacidad disponible y nodos que ven cada volumen.</div>
            </div>
            <a href="{{ route('proxmox.exportStorage') }}" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Exportar a Excel</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Storages</div><div class="metric-value">{{ $totalStorages }}</div><div class="metric-foot">Compartidos/no locales</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Clusters</div><div class="metric-value">{{ $clustersCount }}</div><div class="metric-foot">Con storage visible</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Uso global</div><div class="metric-value">{{ $globalUsedPercent }}%</div><div class="metric-foot">{{ $formatBytes($totalUsedBytes) }} / {{ $formatBytes($totalMaxBytes) }}</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Backup</div><div class="metric-value">{{ $backupStorages }}</div><div class="metric-foot">PBS/NFS backup</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Advertencia</div><div class="metric-value {{ $warningStorages > 0 ? 'text-warning' : 'text-success' }}">{{ $warningStorages }}</div><div class="metric-foot">Uso ≥70%</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Críticos</div><div class="metric-value {{ $criticalStorages > 0 ? 'text-danger' : 'text-success' }}">{{ $criticalStorages }}</div><div class="metric-foot">Uso ≥85%</div></div></div>
    </div>

    <div class="modern-panel">
        <div class="storage-toolbar">
            <form action="{{ route('proxmox.storage') }}" method="GET" class="d-flex gap-2 flex-wrap">
                <input id="storageSearch" type="search" class="storage-search" placeholder="Buscar por cluster, storage, tipo o contenido..." name="search" value="{{ $searchQuery ?? '' }}">
                <button class="btn btn-outline-info" type="submit"><i class="fa fa-search"></i> Buscar</button>
                @if($searchQuery ?? false)<a class="btn btn-outline-light" href="{{ route('proxmox.storage') }}">Limpiar</a>@endif
            </form>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="storage-filter active" data-filter="all">Todos</button>
                <button type="button" class="storage-filter" data-filter="critical">Críticos</button>
                <button type="button" class="storage-filter" data-filter="warning">Advertencia</button>
                <button type="button" class="storage-filter" data-filter="backup">Backup</button>
                <button type="button" class="storage-filter" data-filter="data">Datos</button>
            </div>
        </div>

        <div class="accordion cluster-accordion" id="storageAccordion">
            @forelse ($storagesByCluster as $clusterName => $storages)
                @php
                    $clusterSlug = Str::slug($clusterName ?: 'sin-cluster');
                    $clusterUsed = $storages->sum('disk');
                    $clusterMax = $storages->sum('maxdisk');
                    $clusterPercent = $clusterMax > 0 ? round(($clusterUsed / $clusterMax) * 100, 1) : 0;
                    $clusterCritical = $storages->filter(fn($s) => $s->maxdisk > 0 && (($s->disk / $s->maxdisk) * 100) >= 85)->count();
                @endphp
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-{{ $clusterSlug }}">
                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $clusterSlug }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="collapse-{{ $clusterSlug }}">
                            <div class="d-flex flex-wrap align-items-center gap-2 w-100 pe-3">
                                <span class="accordion-title"><i class="bi bi-hdd-stack-fill me-2"></i>{{ $clusterName }}</span>
                                <span class="cluster-pill">{{ $storages->count() }} volúmenes</span>
                                <span class="cluster-pill {{ $clusterPercent >= 85 ? 'danger' : ($clusterPercent >= 70 ? 'warn' : 'success') }}">{{ $clusterPercent }}% uso</span>
                                @if($clusterCritical > 0)<span class="cluster-pill danger">{{ $clusterCritical }} crítico(s)</span>@endif
                            </div>
                        </button>
                    </h2>
                    <div id="collapse-{{ $clusterSlug }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" aria-labelledby="heading-{{ $clusterSlug }}" data-bs-parent="#storageAccordion">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-dark table-hover table-bordered align-middle modern-table mb-0">
                                    <thead class="text-center">
                                        <tr>
                                            <th>Storage</th><th>Uso</th><th>Utilizado</th><th>Total</th><th>Disponible</th><th>Contenido</th><th>Tipo</th><th>Nodos</th><th>Sync</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($storages as $storage)
                                        @php
                                            $usedPercent = $storage->maxdisk > 0 ? round(($storage->disk / $storage->maxdisk) * 100, 1) : 0;
                                            $availBytes = max(0, $storage->maxdisk - $storage->disk);
                                            $availPercent = $storage->maxdisk > 0 ? round(100 - $usedPercent, 1) : 0;
                                            $usageColor = $usedPercent >= 85 ? 'bg-danger' : ($usedPercent >= 70 ? 'bg-warning' : 'bg-success');
                                            $availColor = $availPercent <= 10 ? 'bg-danger' : ($availPercent <= 25 ? 'bg-warning' : 'bg-info');
                                            $severity = $usedPercent >= 85 ? 'critical' : ($usedPercent >= 70 ? 'warning' : 'normal');
                                            $kind = (stripos($storage->storage, 'backup') !== false || stripos($storage->plugintype, 'pbs') !== false) ? 'backup' : 'data';
                                            $storageSearchText = strtolower($clusterName . ' ' . $storage->storage . ' ' . $storage->plugintype . ' ' . $storage->content . ' ' . strip_tags(str_replace('<br>', ' ', $storage->nodes)));
                                        @endphp
                                        <tr class="storage-row" data-severity="{{ $severity }}" data-kind="{{ $kind }}" data-search="{{ e($storageSearchText) }}">
                                            <td><div class="storage-name-main">{{ $storage->storage }}</div><div class="storage-name-sub">{{ $kind === 'backup' ? 'Repositorio backup' : 'Volumen datos' }}</div></td>
                                            <td class="resource-cell">
                                                <div class="label-line"><strong>{{ $usedPercent }}%</strong></div>
                                                <div class="progress cluster-progress"><div class="progress-bar {{ $usageColor }}" style="width: {{ min($usedPercent, 100) }}%"></div></div>
                                                <div class="storage-name-sub text-center">{{ $formatBytes($storage->disk) }} / {{ $formatBytes($storage->maxdisk) }}</div>
                                            </td>
                                            <td class="text-center">{{ $formatBytes($storage->disk) }}</td>
                                            <td class="text-center">{{ $formatBytes($storage->maxdisk) }}</td>
                                            <td class="resource-cell">
                                                @if($storage->maxdisk > 0)
                                                    <div class="label-line"><strong>{{ $availPercent }}%</strong></div>
                                                    <div class="progress cluster-progress"><div class="progress-bar {{ $availColor }}" style="width: {{ max(min($availPercent, 100), 2) }}%"></div></div>
                                                    <div class="storage-name-sub text-center">{{ $formatBytes($availBytes) }} libre</div>
                                                @else
                                                    <div class="storage-name-sub text-center">Sin datos</div>
                                                @endif
                                            </td>
                                            <td class="text-center"><span class="badge-soft">{{ $storage->content ?: 'S/D' }}</span></td>
                                            <td class="text-center"><span class="badge-soft {{ $kind === 'backup' ? 'running' : '' }}">{{ $storage->plugintype }}</span></td>
                                            <td class="text-center"><div class="storage-node-list">{!! $storage->nodes !!}</div></td>
                                            <td class="text-center storage-name-sub">{{ \Carbon\Carbon::parse($storage->updated_at)->format('d/m H:i') }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-secondary text-center">No se encontraron almacenamientos compartidos habilitados.</div>
            @endforelse
        </div>
        <div id="storageEmptyFilter" class="storage-empty-filter">No hay storages que coincidan con el filtro aplicado.</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const storageSearch = document.getElementById('storageSearch');
    const storageFilters = document.querySelectorAll('.storage-filter');
    const storageRows = document.querySelectorAll('.storage-row');
    const storageEmptyFilter = document.getElementById('storageEmptyFilter');
    let activeFilter = 'all';

    function applyStorageFilters() {
        const query = (storageSearch?.value || '').toLowerCase().trim();
        let visible = 0;
        storageRows.forEach(row => {
            const matchesSearch = !query || (row.dataset.search || '').includes(query);
            let matchesFilter = true;
            if (activeFilter === 'critical') matchesFilter = row.dataset.severity === 'critical';
            if (activeFilter === 'warning') matchesFilter = row.dataset.severity === 'warning';
            if (activeFilter === 'backup') matchesFilter = row.dataset.kind === 'backup';
            if (activeFilter === 'data') matchesFilter = row.dataset.kind === 'data';
            const show = matchesSearch && matchesFilter;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (storageEmptyFilter) storageEmptyFilter.style.display = visible === 0 && storageRows.length > 0 ? 'block' : 'none';
    }
    if (storageSearch) storageSearch.addEventListener('input', applyStorageFilters);
    storageFilters.forEach(btn => btn.addEventListener('click', () => {
        storageFilters.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeFilter = btn.dataset.filter || 'all';
        applyStorageFilters();
    }));
});
</script>
@endsection
