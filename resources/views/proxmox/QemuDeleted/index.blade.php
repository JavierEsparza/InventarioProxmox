@extends('layouts.app')

@section('content')
@php
    use Carbon\Carbon;
    Carbon::setLocale('es');

    $formatBytes = function($bytes) {
        if (!$bytes || $bytes <= 0) return '0 GB';
        $gb = $bytes / 1073741824;
        return $gb >= 1024 ? number_format($gb / 1024, 2) . ' TB' : number_format($gb, 2) . ' GB';
    };
    $percentColor = function($value) {
        return $value >= 80 ? 'danger' : ($value > 50 ? 'warn' : 'ok');
    };
    $totalDeleted = $qemus->count();
    $totalCpu = $qemus->sum('maxcpu');
    $totalRam = $qemus->sum('maxmem');
    $totalDisk = $qemus->sum('maxdisk');
    $lastDeletedAt = optional($qemus->first())->updated_at;
    $currentMonthKey = Carbon::now()->format('Y-m');
    $currentMonthDeleted = optional($monthlyStats->firstWhere('month', $currentMonthKey))->count ?? 0;
@endphp

<style>
    .page-hero { background:linear-gradient(135deg,#111923 0%,#182635 58%,#4b1119 155%); border:1px solid #2f3b49; border-radius:18px; padding:1.25rem; color:#fff; box-shadow:0 16px 35px rgba(0,0,0,.28); }
    .page-title { font-size:clamp(1.7rem,2.6vw,2.45rem); font-weight:850; letter-spacing:-.03em; margin:0; }
    .page-subtitle { color:#aab7c4; font-size:.95rem; margin-top:.4rem; }
    .cluster-pill { display:inline-flex; align-items:center; gap:.35rem; border:1px solid #344455; background:rgba(255,255,255,.06); color:#d7e2ec; border-radius:999px; padding:.35rem .65rem; font-size:.82rem; font-weight:750; }
    .cluster-pill.danger { border-color:rgba(220,53,69,.65); background:rgba(220,53,69,.16); color:#ff9ca8; }
    .cluster-pill.warn { border-color:rgba(255,193,7,.62); background:rgba(255,193,7,.14); color:#ffe08a; }
    .cluster-pill.info { border-color:rgba(13,202,240,.55); background:rgba(13,202,240,.12); color:#9beaf7; }
    .metric-card { background:#151d26; border:1px solid #2b3846; border-radius:16px; padding:1rem; color:#fff; height:100%; box-shadow:inset 0 1px 0 rgba(255,255,255,.03); }
    .metric-label { color:#96a6b5; font-size:.74rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
    .metric-value { font-size:2rem; font-weight:850; line-height:1; margin-top:.35rem; }
    .metric-foot { color:#8d9baa; font-size:.82rem; margin-top:.5rem; }
    .modern-panel { background:#0f1821; border:1px solid #1f3345; border-radius:18px; padding:1rem; color:#fff; box-shadow:0 12px 28px rgba(0,0,0,.18); }
    .modern-table { --bs-table-bg:#0f1821; --bs-table-border-color:#223346; --bs-table-color:#e5edf4; border-radius:14px; overflow:hidden; }
    .modern-table thead th { background:#0c151d !important; color:#8fa3b7; font-size:.72rem; text-transform:uppercase; letter-spacing:.07em; border-bottom:1px solid #344455; white-space:nowrap; }
    .modern-table tbody td { background:#0f1821 !important; border-color:#223346; vertical-align:middle; }
    .modern-table tbody tr:nth-child(even) td { background:#101a24 !important; }
    .modern-table tbody tr:hover td { background:#142536 !important; }
    .badge-soft { border:1px solid #344455; background:rgba(255,255,255,.06); color:#dbe5ee; border-radius:999px; padding:.3rem .55rem; font-size:.74rem; font-weight:800; }
    .badge-soft.danger { border-color:rgba(220,53,69,.65); background:rgba(220,53,69,.18); color:#ff9ca8; }
    .badge-soft.warn { border-color:rgba(255,193,7,.62); background:rgba(255,193,7,.14); color:#ffe08a; }
    .badge-soft.ok { border-color:rgba(25,135,84,.65); background:rgba(25,135,84,.18); color:#91e7b4; }
    .id-chip { font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; background:#07111a; border:1px solid #25384b; color:#b8e7ff; padding:.2rem .45rem; border-radius:8px; font-size:.78rem; }
    .resource-wrap { display:flex; align-items:center; gap:.45rem; min-width:135px; }
    .resource-pct { min-width:34px; text-align:right; color:#d6e1ea; font-size:.78rem; font-weight:800; }
    .resource-bar { position:relative; height:7px; flex:1; background:#243241; border-radius:999px; overflow:hidden; }
    .resource-fill { height:100%; border-radius:999px; }
    .resource-fill.ok { background:#198754; }
    .resource-fill.warn { background:#ffc107; }
    .resource-fill.danger { background:#dc3545; }
    .toolbar { display:flex; flex-wrap:wrap; justify-content:space-between; gap:.75rem; margin-bottom:1rem; }
    .deleted-search { background:#0d141c; border:1px solid #324254; color:#e8eef5; border-radius:12px; padding:.65rem .85rem; min-width:320px; }
    .deleted-filter { border:1px solid #344455; background:rgba(255,255,255,.04); color:#dce6ef; border-radius:999px; padding:.45rem .8rem; font-weight:750; font-size:.84rem; }
    .deleted-filter.active { background:rgba(220,53,69,.2); border-color:rgba(220,53,69,.75); color:#ffb4bd; }
    .deleted-accordion .accordion-item { background:#0f1821; border:1px solid #223346; border-radius:16px; overflow:hidden; margin-bottom:.85rem; }
    .deleted-accordion .accordion-button { background:#0c151d; color:#eaf2f8; box-shadow:none; padding:.85rem 1rem; }
    .deleted-accordion .accordion-button:not(.collapsed) { background:#22151b; color:#fff; }
    .deleted-accordion .accordion-button::after { filter:invert(1); }
    .chart-card { background:#0f1821; border:1px solid #223346; border-radius:18px; padding:1rem; color:#fff; height:100%; }
    .chart-box { height:320px; }
    .empty-filter { display:none; color:#aeb9c5; text-align:center; padding:1rem; }
</style>

<div class="container-fluid mt-4 px-4">
    <div class="page-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="cluster-pill danger">● VMs eliminadas</span>
                    <span class="cluster-pill">{{ $totalDeleted }} registros</span>
                    <span class="cluster-pill info">{{ $monthlyStats->count() }} mes(es)</span>
                    <span class="cluster-pill warn">Vinculado por updated_at al histórico mensual</span>
                </div>
                <h1 class="page-title">VMs Eliminadas</h1>
                <div class="page-subtitle">Inventario histórico de servidores eliminados, agrupado por fecha real de eliminación, nodo origen, cluster y recursos asignados liberados.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ url('/proxmox/history') }}" class="btn btn-outline-light"><i class="bi bi-graph-up me-1"></i> Ver Histórico</a>
                @if ($groupedDeleted->isNotEmpty())
                    @can('manage cluster')
                        <form action="{{ route('proxmox.qemu.destroy') }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas vaciar TODO el historial de eliminadas? Esta acción no se puede deshacer.');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger" type="submit"><i class="bi bi-trash-fill me-1"></i> Vaciar</button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Eliminadas total</div><div class="metric-value text-danger">{{ $totalDeleted }}</div><div class="metric-foot">Registros guardados</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Este mes</div><div class="metric-value">{{ $currentMonthDeleted }}</div><div class="metric-foot">{{ Carbon::now()->translatedFormat('F Y') }}</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">vCPU liberada</div><div class="metric-value">{{ $totalCpu }}</div><div class="metric-foot">Suma histórica</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">RAM liberada</div><div class="metric-value" style="font-size:1.55rem;">{{ $formatBytes($totalRam) }}</div><div class="metric-foot">Asignada a VMs borradas</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Disco asignado</div><div class="metric-value" style="font-size:1.55rem;">{{ $formatBytes($totalDisk) }}</div><div class="metric-foot">Disco asignado histórico</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Última baja</div><div class="metric-value" style="font-size:1.25rem;">{{ $lastDeletedAt ? Carbon::parse($lastDeletedAt)->format('d/m H:i') : 'S/D' }}</div><div class="metric-foot">Fecha eliminación</div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7"><div class="chart-card"><div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Eliminadas por mes</h5><span class="badge-soft danger">mismo mes usado por histórico</span></div><div class="chart-box"><canvas id="deletedMonthlyChart"></canvas></div></div></div>
        <div class="col-lg-5"><div class="chart-card"><div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Top nodos origen</h5><span class="badge-soft">servidores eliminados</span></div><div class="chart-box"><canvas id="deletedNodeChart"></canvas></div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="modern-panel h-100">
                <h5 class="mb-3">Resumen por nodo origen</h5>
                <div class="table-responsive">
                    <table class="table table-dark table-hover table-bordered align-middle modern-table mb-0">
                        <thead class="text-center"><tr><th class="text-start">Nodo</th><th>Cluster</th><th>VMs</th><th>vCPU</th><th>RAM</th><th>Disco</th><th>Última baja</th></tr></thead>
                        <tbody class="text-center">
                        @forelse($topDeletedNodes as $node)
                            <tr>
                                <td class="text-start fw-bold">{{ $node->node }}</td>
                                <td><span class="badge-soft">{{ $node->cluster }}</span></td>
                                <td><span class="badge-soft danger">{{ $node->count }}</span></td>
                                <td>{{ $node->cpu }}</td>
                                <td>{{ $formatBytes($node->memory) }}</td>
                                <td>{{ $formatBytes($node->disk) }}</td>
                                <td class="text-secondary">{{ $node->last_deleted_at ? Carbon::parse($node->last_deleted_at)->format('d/m/Y H:i') : 'S/D' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-secondary">Sin nodos eliminados.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="modern-panel h-100">
                <h5 class="mb-3">Resumen por cluster/storage</h5>
                <div class="table-responsive mb-3">
                    <table class="table table-dark table-hover table-bordered align-middle modern-table mb-0">
                        <thead class="text-center"><tr><th class="text-start">Cluster</th><th>VMs</th><th>Nodos</th><th>Disco</th></tr></thead>
                        <tbody class="text-center">
                            @foreach($topDeletedClusters as $cluster)
                                <tr><td class="text-start fw-bold">{{ $cluster->cluster }}</td><td><span class="badge-soft danger">{{ $cluster->count }}</span></td><td>{{ $cluster->nodes }}</td><td>{{ $formatBytes($cluster->disk) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($storageStats as $storage)
                        <span class="cluster-pill">{{ $storage->storage }}: {{ $storage->count }} / {{ $formatBytes($storage->disk) }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="modern-panel">
        <div class="toolbar">
            <input id="deletedSearch" class="deleted-search" type="search" placeholder="Filtrar por VM, VMID, nodo, cluster o storage...">
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="deleted-filter active" data-filter="all">Todos</button>
                <button type="button" class="deleted-filter" data-filter="current">Mes actual</button>
                <button type="button" class="deleted-filter" data-filter="running">Estado running</button>
                <button type="button" class="deleted-filter" data-filter="highram">RAM final alta</button>
            </div>
        </div>

        <div class="accordion deleted-accordion" id="deletedAccordion">
            @forelse ($groupedDeleted as $month => $vms)
                @php
                    $monthDate = Carbon::createFromFormat('Y-m', $month);
                    $monthLabel = $monthDate->translatedFormat('F Y');
                    $monthStats = $monthlyStats->firstWhere('month', $month);
                    $monthSearch = strtolower($monthLabel.' '.$vms->pluck('name')->implode(' ').' '.$vms->pluck('node_name')->implode(' ').' '.$vms->pluck('cluster_name')->implode(' ').' '.$vms->pluck('storageName')->implode(' '));
                @endphp
                <div class="accordion-item deleted-month" data-month="{{ $month }}" data-search="{{ e($monthSearch) }}">
                    <h2 class="accordion-header" id="heading-{{ $loop->iteration }}">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $loop->iteration }}" aria-expanded="false" aria-controls="collapse-{{ $loop->iteration }}">
                            <div class="d-flex flex-wrap align-items-center gap-2 w-100 pe-3">
                                <span class="fw-bold"><i class="bi bi-calendar-x me-2"></i>{{ ucfirst($monthLabel) }}</span>
                                <span class="cluster-pill danger">{{ $vms->count() }} VMs</span>
                                <span class="cluster-pill">{{ $monthStats->nodes ?? $vms->pluck('node_name')->unique()->count() }} nodo(s)</span>
                                <span class="cluster-pill info">{{ $monthStats->clusters ?? $vms->pluck('cluster_name')->unique()->count() }} cluster(s)</span>
                                <span class="cluster-pill warn">{{ $formatBytes($monthStats->disk ?? $vms->sum('maxdisk')) }} disco</span>
                            </div>
                        </button>
                    </h2>
                    <div id="collapse-{{ $loop->iteration }}" class="accordion-collapse collapse" aria-labelledby="heading-{{ $loop->iteration }}" data-bs-parent="#deletedAccordion">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-dark table-hover table-bordered align-middle modern-table mb-0">
                                    <thead class="text-center">
                                        <tr>
                                            <th>Nodo origen</th><th>Cluster</th><th>VMID</th><th class="text-start">Nombre</th><th>Estado final</th><th>vCPU</th><th>RAM Asig.</th><th>Disco Asig.</th><th>RAM usada final</th><th>CPU final</th><th>Storage</th><th>Fecha eliminación</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-center">
                                    @foreach ($vms as $vm)
                                        @php
                                            $ramPercent = ($vm->maxmem > 0) ? min(100, ($vm->mem / $vm->maxmem) * 100) : 0;
                                            $cpuPercent = min(100, max(0, $vm->cpu * 100));
                                            $ramClass = $percentColor($ramPercent);
                                            $cpuClass = $percentColor($cpuPercent);
                                        @endphp
                                        <tr class="deleted-row" data-current="{{ $month === $currentMonthKey ? '1' : '0' }}" data-status="{{ strtolower($vm->status ?? 'unknown') }}" data-highram="{{ $ramPercent >= 80 ? '1' : '0' }}" data-search="{{ e(strtolower(($vm->name ?? '').' '.($vm->vmid_clean ?? '').' '.($vm->node_name ?? '').' '.($vm->cluster_name ?? '').' '.($vm->storageName ?? ''))) }}">
                                            <td class="fw-bold">{{ $vm->node_name }}</td>
                                            <td><span class="badge-soft">{{ $vm->cluster_name }}</span></td>
                                            <td><span class="id-chip">{{ $vm->vmid_clean ?? 'N/A' }}</span></td>
                                            <td class="text-start fw-bold">{{ $vm->name }}</td>
                                            <td><span class="badge-soft {{ strtolower($vm->status) === 'running' ? 'ok' : 'danger' }}">{{ $vm->status ?: 'unknown' }}</span></td>
                                            <td>{{ $vm->maxcpu }}</td>
                                            <td>{{ $formatBytes($vm->maxmem) }}</td>
                                            <td>{{ $formatBytes($vm->maxdisk) }}</td>
                                            <td><div class="resource-wrap"><span class="resource-pct">{{ round($ramPercent) }}%</span><div class="resource-bar"><div class="resource-fill {{ $ramClass }}" style="width: {{ $ramPercent }}%"></div></div></div></td>
                                            <td><div class="resource-wrap"><span class="resource-pct">{{ round($cpuPercent) }}%</span><div class="resource-bar"><div class="resource-fill {{ $cpuClass }}" style="width: {{ $cpuPercent }}%"></div></div></div></td>
                                            <td><small>{{ $vm->storageName ?: 'S/D' }}</small></td>
                                            <td class="text-secondary">{{ Carbon::parse($vm->updated_at)->format('d/m/Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-secondary text-center py-4"><i class="bi bi-info-circle fs-1 mb-3 d-block"></i><h4>No hay registro de VMs eliminadas.</h4></div>
            @endforelse
        </div>
        <div id="deletedEmptyFilter" class="empty-filter">No hay VMs eliminadas que coincidan con el filtro aplicado.</div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const monthlyStats = @json($monthlyStats);
    const nodeStats = @json($topDeletedNodes);
    const chartText = '#dce6ef';
    const chartGrid = 'rgba(255,255,255,.08)';

    new Chart(document.getElementById('deletedMonthlyChart').getContext('2d'), {
        type:'bar',
        data:{ labels:monthlyStats.map(i => i.label), datasets:[
            { label:'VMs eliminadas', data:monthlyStats.map(i => i.count), backgroundColor:'rgba(220,53,69,.68)', borderColor:'#dc3545', borderWidth:1 },
            { label:'vCPU liberada', type:'line', data:monthlyStats.map(i => i.cpu), borderColor:'#38bdf8', backgroundColor:'rgba(56,189,248,.12)', tension:.35, yAxisID:'y1' }
        ]},
        options:{ responsive:true, maintainAspectRatio:false, interaction:{mode:'index',intersect:false}, scales:{ x:{ticks:{color:chartText},grid:{color:chartGrid}}, y:{beginAtZero:true,ticks:{color:chartText},grid:{color:chartGrid}}, y1:{beginAtZero:true,position:'right',ticks:{color:chartText},grid:{drawOnChartArea:false}} }, plugins:{legend:{labels:{color:chartText,usePointStyle:true}}, tooltip:{backgroundColor:'rgba(0,0,0,.85)'}} }
    });

    new Chart(document.getElementById('deletedNodeChart').getContext('2d'), {
        type:'bar',
        data:{ labels:nodeStats.slice(0,10).map(i => i.node), datasets:[{ label:'VMs eliminadas', data:nodeStats.slice(0,10).map(i => i.count), backgroundColor:'rgba(245,158,11,.72)', borderColor:'#f59e0b', borderWidth:1 }]},
        options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false, scales:{ x:{beginAtZero:true,ticks:{color:chartText},grid:{color:chartGrid}}, y:{ticks:{color:chartText},grid:{color:chartGrid}} }, plugins:{legend:{labels:{color:chartText,usePointStyle:true}}, tooltip:{backgroundColor:'rgba(0,0,0,.85)'}} }
    });

    const search = document.getElementById('deletedSearch');
    const filters = document.querySelectorAll('.deleted-filter');
    const months = document.querySelectorAll('.deleted-month');
    const rows = document.querySelectorAll('.deleted-row');
    const empty = document.getElementById('deletedEmptyFilter');
    let activeFilter = 'all';

    function rowMatches(row, query) {
        const matchesSearch = !query || (row.dataset.search || '').includes(query);
        let matchesFilter = true;
        if (activeFilter === 'current') matchesFilter = row.dataset.current === '1';
        if (activeFilter === 'running') matchesFilter = row.dataset.status === 'running';
        if (activeFilter === 'highram') matchesFilter = row.dataset.highram === '1';
        return matchesSearch && matchesFilter;
    }
    function applyFilters() {
        const query = (search?.value || '').toLowerCase().trim();
        let visibleRows = 0;
        rows.forEach(row => {
            const show = rowMatches(row, query);
            row.style.display = show ? '' : 'none';
            if (show) visibleRows++;
        });
        months.forEach(month => {
            const monthRows = month.querySelectorAll('.deleted-row');
            const anyVisible = Array.from(monthRows).some(row => row.style.display !== 'none');
            month.style.display = anyVisible ? '' : 'none';
        });
        if (empty) empty.style.display = visibleRows === 0 && rows.length > 0 ? 'block' : 'none';
    }
    if (search) search.addEventListener('input', applyFilters);
    filters.forEach(btn => btn.addEventListener('click', () => { filters.forEach(b => b.classList.remove('active')); btn.classList.add('active'); activeFilter = btn.dataset.filter || 'all'; applyFilters(); }));
});
</script>
@endsection
