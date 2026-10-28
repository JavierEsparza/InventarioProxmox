@extends('layouts.app')

@section('content')
@php
    use Carbon\Carbon;
    Carbon::setLocale('es');

    $monthlyData = $VMHistory->sortBy('date')->groupBy(function ($item) {
        return Carbon::parse($item->date)->format('Y-m');
    });

    $formatBytes = function($bytes) {
        if (!$bytes || $bytes == 0) return '0 GB';
        $gb = $bytes / (1024 ** 3);
        return $gb >= 1024 ? number_format($gb / 1024, 2) . ' TB' : number_format($gb, 2) . ' GB';
    };
    $signedClass = function($value) {
        return $value > 0 ? 'text-success' : ($value < 0 ? 'text-danger' : 'text-secondary');
    };
    $signedText = function($value, $suffix='') use ($formatBytes) {
        $sign = $value > 0 ? '+' : '';
        return $sign . $value . $suffix;
    };
    $signedDiskText = function($bytes) use ($formatBytes) {
        return ($bytes > 0 ? '+' : '') . $formatBytes($bytes);
    };

    $monthlySummary = $monthlyData->map(function($items, $month) {
        return (object) [
            'month' => $month,
            'date' => $items->first()->date,
            'clusters' => $items->pluck('cluster_name')->unique()->count(),
            'qemus' => $items->sum('cluster_qemus'),
            'cpu' => $items->sum('cluster_cpu'),
            'memory' => $items->sum('cluster_memory'),
            'disk' => $items->sum('cluster_disk'),
            'vms_created' => $items->sum('vms_created'),
            'vms_deleted' => $items->sum('vms_deleted'),
            'vms_migrated' => $items->sum('vms_migrated'),
            'vms_unverified' => $items->sum('vms_unverified'),
            'nodes_created' => $items->sum('nodes_created'),
            'nodes_deleted' => $items->sum('nodes_deleted'),
            'net_vms' => $items->sum('vms_created') - $items->sum('vms_deleted'),
            'net_nodes' => $items->sum('nodes_created') - $items->sum('nodes_deleted'),
        ];
    })->values();

    $latestMonth = $monthlySummary->last();
    $previousMonth = $monthlySummary->count() > 1 ? $monthlySummary[$monthlySummary->count() - 2] : null;
    $latestLabel = $latestMonth ? Carbon::parse($latestMonth->date)->translatedFormat('F Y') : 'Sin registro';
    $monthWithMostGrowth = $monthlySummary->sortByDesc('net_vms')->first();
    $monthWithMostDeletes = $monthlySummary->sortByDesc('vms_deleted')->first();

    $topClusters = $VMHistory->groupBy('cluster_name')->map(function($items, $cluster) {
        $ordered = $items->sortBy('date')->values();
        $last = $ordered->last();
        $prev = $ordered->count() > 1 ? $ordered[$ordered->count() - 2] : null;
        return (object) [
            'cluster_name' => $cluster,
            'qemus' => $last->cluster_qemus ?? 0,
            'delta_qemus' => $prev ? (($last->cluster_qemus ?? 0) - ($prev->cluster_qemus ?? 0)) : 0,
            'delta_cpu' => $prev ? (($last->cluster_cpu ?? 0) - ($prev->cluster_cpu ?? 0)) : 0,
            'delta_memory' => $prev ? (($last->cluster_memory ?? 0) - ($prev->cluster_memory ?? 0)) : 0,
            'delta_disk' => $prev ? (($last->cluster_disk ?? 0) - ($prev->cluster_disk ?? 0)) : 0,
            'date' => $last->date ?? null,
        ];
    })->sortByDesc(function($c){ return abs($c->delta_qemus); })->take(8);
@endphp

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
    .history-toolbar { display:flex; flex-wrap:wrap; justify-content:space-between; gap:.75rem; margin-bottom:1rem; }
    .history-search { background:#0d141c; border:1px solid #324254; color:#e8eef5; border-radius:12px; padding:.65rem .85rem; min-width:320px; }
    .history-filter { border:1px solid #344455; background:rgba(255,255,255,.04); color:#dce6ef; border-radius:999px; padding:.45rem .8rem; font-weight:750; font-size:.84rem; }
    .history-filter.active { background:rgba(25,135,84,.25); border-color:rgba(25,135,84,.7); color:#94ebb9; }
    .modern-table { --bs-table-bg:#0f1821; --bs-table-border-color:#223346; --bs-table-color:#e5edf4; border-radius:14px; overflow:hidden; }
    .modern-table thead th { background:#0c151d !important; color:#8fa3b7; font-size:.72rem; text-transform:uppercase; letter-spacing:.07em; border-bottom:1px solid #344455; white-space:nowrap; }
    .modern-table tbody td { background:#0f1821 !important; border-color:#223346; vertical-align:middle; }
    .modern-table tbody tr:nth-child(even) td { background:#101a24 !important; }
    .modern-table tbody tr:hover td { background:#142536 !important; }
    .badge-soft { border:1px solid #344455; background:rgba(255,255,255,.06); color:#dbe5ee; border-radius:999px; padding:.3rem .55rem; font-size:.74rem; font-weight:800; }
    .badge-soft.running { border-color:rgba(25,135,84,.65); background:rgba(25,135,84,.18); color:#91e7b4; }
    .badge-soft.warn { border-color:rgba(255,193,7,.62); background:rgba(255,193,7,.14); color:#ffe08a; }
    .badge-soft.danger { border-color:rgba(220,53,69,.65); background:rgba(220,53,69,.18); color:#ff9ca8; }
    .chart-card { background:#0f1821; border:1px solid #223346; border-radius:18px; padding:1rem; color:#fff; height:100%; }
    .chart-box { height:340px; }
    .cluster-accordion .accordion-item { background:#0f1821; border:1px solid #223346; border-radius:16px; overflow:hidden; margin-bottom:.85rem; }
    .cluster-accordion .accordion-button { background:#0c151d; color:#eaf2f8; box-shadow:none; padding:.85rem 1rem; }
    .cluster-accordion .accordion-button:not(.collapsed) { background:#102033; color:#fff; }
    .cluster-accordion .accordion-button::after { filter:invert(1); }
    .accordion-title { font-weight:850; }
    .modal-content.modern-modal { background:#0f1821; border:1px solid #2d4053; color:#fff; border-radius:16px; }
    .history-empty-filter { display:none; color:#aeb9c5; text-align:center; padding:1rem; }
</style>

<div class="container-fluid mt-4 px-4">
    <div class="page-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="cluster-pill success">● histórico mensual</span>
                    <span class="cluster-pill">{{ $monthlySummary->count() }} mes(es)</span>
                    <span class="cluster-pill">{{ $latestMonth->clusters ?? 0 }} cluster(s)</span>
                </div>
                <h1 class="page-title">Históricos Proxmox</h1>
                <div class="page-subtitle">Evolución mensual de VMs, vCPU, RAM, disco y movimientos por cluster.</div>
            </div>
            <div class="btn-group" role="group">
                <a href="{{ url('/proxmox/history') }}" class="btn btn-primary">Mensual</a>
                <a href="{{ url('/proxmox/historyAnual') }}" class="btn btn-outline-light">Anual</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">VMs actual</div><div class="metric-value">{{ $lastMonthlyTotal->cluster_qemus ?? 'N/A' }}</div><div class="metric-foot">{{ $latestLabel }}</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">vCPU actual</div><div class="metric-value">{{ $lastMonthlyTotal->cluster_cpu ?? 'N/A' }}</div><div class="metric-foot">Total asignado</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">RAM actual</div><div class="metric-value">{{ $lastMonthlyTotal->cluster_memory ? number_format($lastMonthlyTotal->cluster_memory / 1024 ** 3) : 'N/A' }}<span style="font-size:1rem;"> GB</span></div><div class="metric-foot">Total asignado</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Disco actual</div><div class="metric-value" style="font-size:1.55rem;">{{ $formatBytes($lastMonthlyTotal->cluster_disk ?? 0) }}</div><div class="metric-foot">Provisionado</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Δ VMs</div><div class="metric-value {{ $signedClass($growth['qemus'] ?? 0) }}">{{ ($growth['qemus'] ?? 0) > 0 ? '+' : '' }}{{ $growth['qemus'] ?? 0 }}</div><div class="metric-foot">vs mes anterior</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Δ Disco</div><div class="metric-value {{ $signedClass($growth['disks'] ?? 0) }}" style="font-size:1.55rem;">{{ $signedDiskText($growth['disks'] ?? 0) }}</div><div class="metric-foot">vs mes anterior</div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6"><div class="chart-card"><div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Evolución de recursos</h5><span class="badge-soft">VMs / vCPU / RAM / Disco</span></div><div class="chart-box"><canvas id="resourcesChart"></canvas></div></div></div>
        <div class="col-lg-6"><div class="chart-card"><div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Altas, bajas y neto</h5><span class="badge-soft">VMs por mes</span></div><div class="chart-box"><canvas id="changesChart"></canvas></div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4"><div class="metric-card"><div class="metric-label">Mayor crecimiento</div><div class="metric-value text-success">{{ $monthWithMostGrowth ? (($monthWithMostGrowth->net_vms > 0 ? '+' : '') . $monthWithMostGrowth->net_vms) : '0' }}</div><div class="metric-foot">{{ $monthWithMostGrowth ? Carbon::parse($monthWithMostGrowth->date)->translatedFormat('F Y') : 'Sin datos' }}</div></div></div>
        <div class="col-lg-4"><div class="metric-card"><div class="metric-label">Más eliminaciones</div><div class="metric-value text-danger">{{ $monthWithMostDeletes->vms_deleted ?? 0 }}</div><div class="metric-foot">{{ $monthWithMostDeletes ? Carbon::parse($monthWithMostDeletes->date)->translatedFormat('F Y') : 'Sin datos' }}</div></div></div>
        <div class="col-lg-4"><div class="metric-card"><div class="metric-label">Promedio actual</div><div class="metric-value" style="font-size:1.45rem;">{{ ($lastMonthlyTotal && $lastMonthlyTotal->cluster_qemus > 0) ? round($lastMonthlyTotal->cluster_cpu / $lastMonthlyTotal->cluster_qemus, 1) : 0 }} vCPU/VM</div><div class="metric-foot">{{ ($lastMonthlyTotal && $lastMonthlyTotal->cluster_qemus > 0) ? round(($lastMonthlyTotal->cluster_memory / 1024 ** 3) / $lastMonthlyTotal->cluster_qemus, 1) : 0 }} GB RAM/VM</div></div></div>
    </div>

    <div class="modern-panel mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div><h5 class="mb-0">Top clusters por variación reciente</h5><div class="page-subtitle">Último registro contra el anterior de cada cluster.</div></div>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-hover table-bordered align-middle modern-table mb-0">
                <thead class="text-center"><tr><th class="text-start">Cluster</th><th>VMs</th><th>Δ VMs</th><th>Δ CPU</th><th>Δ RAM</th><th>Δ Disco</th><th>Último registro</th></tr></thead>
                <tbody class="text-center">
                    @forelse($topClusters as $cluster)
                        <tr>
                            <td class="text-start fw-bold">{{ $cluster->cluster_name }}</td>
                            <td>{{ $cluster->qemus }}</td>
                            <td class="{{ $signedClass($cluster->delta_qemus) }}">{{ $cluster->delta_qemus > 0 ? '+' : '' }}{{ $cluster->delta_qemus }}</td>
                            <td class="{{ $signedClass($cluster->delta_cpu) }}">{{ $cluster->delta_cpu > 0 ? '+' : '' }}{{ $cluster->delta_cpu }}</td>
                            <td class="{{ $signedClass($cluster->delta_memory) }}">{{ $signedDiskText($cluster->delta_memory) }}</td>
                            <td class="{{ $signedClass($cluster->delta_disk) }}">{{ $signedDiskText($cluster->delta_disk) }}</td>
                            <td class="text-secondary">{{ $cluster->date ? Carbon::parse($cluster->date)->format('d/m/Y') : 'S/D' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary">Sin datos de clusters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modern-panel">
        <div class="history-toolbar">
            <input id="historySearch" class="history-search" type="search" placeholder="Filtrar por mes o cluster...">
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="history-filter active" data-filter="all">Todos</button>
                <button type="button" class="history-filter" data-filter="changed">Con cambios</button>
                <button type="button" class="history-filter" data-filter="growth">Crecimiento</button>
                <button type="button" class="history-filter" data-filter="decrease">Bajas</button>
            </div>
        </div>

        <div class="accordion cluster-accordion" id="historyAccordion">
            @forelse ($monthlyData->reverse() as $month => $items)
                @php
                    $monthDate = $items->first()->date;
                    $monthLabel = Carbon::parse($monthDate)->translatedFormat('F Y');
                    $vmsCreated = $items->sum('vms_created');
                    $vmsDeleted = $items->sum('vms_deleted');
                    $netVms = $vmsCreated - $vmsDeleted;
                    $monthSearch = strtolower($monthLabel . ' ' . $items->pluck('cluster_name')->implode(' '));
                    $filterKind = $netVms > 0 ? 'growth' : ($vmsDeleted > 0 ? 'decrease' : 'none');
                    $hasChanges = ($vmsCreated + $vmsDeleted + $items->sum('nodes_created') + $items->sum('nodes_deleted')) > 0;
                @endphp
                <div class="accordion-item history-month" data-search="{{ e($monthSearch) }}" data-kind="{{ $filterKind }}" data-changed="{{ $hasChanges ? '1' : '0' }}">
                    <h2 class="accordion-header" id="heading-{{ $loop->iteration }}">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $loop->iteration }}" aria-expanded="false" aria-controls="collapse-{{ $loop->iteration }}">
                            <div class="d-flex flex-wrap align-items-center gap-2 w-100 pe-3">
                                <span class="accordion-title">{{ ucfirst($monthLabel) }}</span>
                                <span class="cluster-pill">{{ $items->sum('cluster_qemus') }} VMs</span>
                                <span class="cluster-pill">{{ $items->count() }} cluster(s)</span>
                                <span class="cluster-pill success">+{{ $vmsCreated }} altas</span>
                                <span class="cluster-pill danger">-{{ $vmsDeleted }} bajas</span>
                                <span class="cluster-pill {{ $netVms > 0 ? 'success' : ($netVms < 0 ? 'danger' : '') }}">Neto {{ $netVms > 0 ? '+' : '' }}{{ $netVms }}</span>
                            </div>
                        </button>
                    </h2>
                    <div id="collapse-{{ $loop->iteration }}" class="accordion-collapse collapse" aria-labelledby="heading-{{ $loop->iteration }}" data-bs-parent="#historyAccordion">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-dark table-hover table-bordered align-middle modern-table mb-0">
                                    <thead class="text-center"><tr><th class="text-start">Cluster</th><th>VMs</th><th>CPU</th><th>Memoria</th><th>Disco</th><th>VMs creadas</th><th>VMs eliminadas</th><th>Migradas</th><th>No verificadas</th><th>Nodos creados</th><th>Nodos eliminados</th><th>Fecha</th></tr></thead>
                                    <tbody class="text-center">
                                    @foreach ($items as $item)
                                        <tr>
                                            <td class="text-start fw-bold">{{ $item->cluster_name }}</td>
                                            <td>{{ $item->cluster_qemus }}</td>
                                            <td>{{ $item->cluster_cpu }}</td>
                                            <td>{{ round($item->cluster_memory / 1024 ** 3, 2) }} GB</td>
                                            <td>{{ $formatBytes($item->cluster_disk) }}</td>
                                            <td><span class="badge-soft running">{{ $item->vms_created }}</span>@if($item->vms_created > 0)<button class="btn btn-sm btn-link p-0 ms-1 btn-detail text-success" data-cluster="{{ $item->cluster_name }}" data-date="{{ $item->date }}" data-type="vms_created" data-title="VMs Creadas" title="Ver detalles"><i class="bi bi-search"></i></button>@endif</td>
                                            <td><span class="badge-soft danger">{{ $item->vms_deleted }}</span>@if($item->vms_deleted > 0)<button class="btn btn-sm btn-link p-0 ms-1 btn-detail text-danger" data-cluster="{{ $item->cluster_name }}" data-date="{{ $item->date }}" data-type="vms_deleted" data-title="VMs Eliminadas" title="Ver detalles"><i class="bi bi-search"></i></button>@endif</td>
                                            <td><span class="badge-soft">{{ $item->vms_migrated ?? 0 }}</span></td>
                                            <td><span class="badge-soft warn">{{ $item->vms_unverified ?? 0 }}</span></td>
                                            <td><span class="badge-soft">{{ $item->nodes_created }}</span>@if($item->nodes_created > 0)<button class="btn btn-sm btn-link p-0 ms-1 btn-detail text-info" data-cluster="{{ $item->cluster_name }}" data-date="{{ $item->date }}" data-type="nodes_created" data-title="Nodos Creados" title="Ver detalles"><i class="bi bi-search"></i></button>@endif</td>
                                            <td><span class="badge-soft warn">{{ $item->nodes_deleted }}</span>@if($item->nodes_deleted > 0)<button class="btn btn-sm btn-link p-0 ms-1 btn-detail text-warning" data-cluster="{{ $item->cluster_name }}" data-date="{{ $item->date }}" data-type="nodes_deleted" data-title="Nodos Eliminados" title="Ver detalles"><i class="bi bi-search"></i></button>@endif</td>
                                            <td class="text-secondary">{{ Carbon::parse($item->date)->format('d/m/Y') }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-secondary text-center">No se encontró historial mensual.</div>
            @endforelse
        </div>
        <div id="historyEmptyFilter" class="history-empty-filter">No hay meses que coincidan con el filtro aplicado.</div>
    </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content modern-modal">
            <div class="modal-header border-secondary"><h5 class="modal-title" id="detailModalLabel">Detalles</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body" id="modalContent"><div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div></div>
            <div class="modal-footer border-secondary"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const monthlyData = @json($monthlySummary);
    const labels = monthlyData.map(item => new Date(item.date).toLocaleDateString('es-CL', { year: 'numeric', month: 'short' }));
    const chartGrid = 'rgba(255,255,255,.08)';
    const chartText = '#dce6ef';

    new Chart(document.getElementById('resourcesChart').getContext('2d'), {
        type: 'line',
        data: { labels, datasets: [
            { label:'VMs', data:monthlyData.map(i=>i.qemus), borderColor:'#22c55e', backgroundColor:'rgba(34,197,94,.12)', tension:.35, yAxisID:'y' },
            { label:'vCPU', data:monthlyData.map(i=>i.cpu), borderColor:'#38bdf8', backgroundColor:'rgba(56,189,248,.12)', tension:.35, yAxisID:'y' },
            { label:'RAM TB', data:monthlyData.map(i=>Math.round((i.memory/1099511627776)*100)/100), borderColor:'#a78bfa', backgroundColor:'rgba(167,139,250,.12)', tension:.35, yAxisID:'y1' },
            { label:'Disco TB', data:monthlyData.map(i=>Math.round((i.disk/1099511627776)*100)/100), borderColor:'#f59e0b', backgroundColor:'rgba(245,158,11,.12)', tension:.35, yAxisID:'y1' }
        ]},
        options:{ responsive:true, maintainAspectRatio:false, interaction:{mode:'index',intersect:false}, scales:{ x:{ticks:{color:chartText},grid:{color:chartGrid}}, y:{beginAtZero:true,ticks:{color:chartText},grid:{color:chartGrid}}, y1:{beginAtZero:true,position:'right',ticks:{color:chartText},grid:{drawOnChartArea:false}} }, plugins:{ legend:{labels:{color:chartText,usePointStyle:true}}, tooltip:{backgroundColor:'rgba(0,0,0,.85)'} } }
    });

    new Chart(document.getElementById('changesChart').getContext('2d'), {
        type:'bar',
        data:{ labels, datasets:[
            { label:'VMs creadas', data:monthlyData.map(i=>i.vms_created), backgroundColor:'rgba(34,197,94,.65)', borderColor:'#22c55e', borderWidth:1 },
            { label:'VMs eliminadas', data:monthlyData.map(i=>i.vms_deleted), backgroundColor:'rgba(239,68,68,.65)', borderColor:'#ef4444', borderWidth:1 },
            { label:'Neto', type:'line', data:monthlyData.map(i=>i.net_vms), borderColor:'#eab308', backgroundColor:'rgba(234,179,8,.15)', tension:.35, borderWidth:3 }
        ]},
        options:{ responsive:true, maintainAspectRatio:false, scales:{ x:{ticks:{color:chartText},grid:{color:chartGrid}}, y:{beginAtZero:true,ticks:{color:chartText, stepSize:1},grid:{color:chartGrid}} }, plugins:{ legend:{labels:{color:chartText,usePointStyle:true}}, tooltip:{backgroundColor:'rgba(0,0,0,.85)'} } }
    });

    const search = document.getElementById('historySearch');
    const filters = document.querySelectorAll('.history-filter');
    const months = document.querySelectorAll('.history-month');
    const empty = document.getElementById('historyEmptyFilter');
    let activeFilter = 'all';
    function applyFilters() {
        const query = (search?.value || '').toLowerCase().trim();
        let visible = 0;
        months.forEach(row => {
            const matchesSearch = !query || (row.dataset.search || '').includes(query);
            let matchesFilter = true;
            if (activeFilter === 'changed') matchesFilter = row.dataset.changed === '1';
            if (activeFilter === 'growth') matchesFilter = row.dataset.kind === 'growth';
            if (activeFilter === 'decrease') matchesFilter = row.dataset.kind === 'decrease';
            const show = matchesSearch && matchesFilter;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (empty) empty.style.display = visible === 0 && months.length > 0 ? 'block' : 'none';
    }
    if (search) search.addEventListener('input', applyFilters);
    filters.forEach(btn => btn.addEventListener('click', () => { filters.forEach(b => b.classList.remove('active')); btn.classList.add('active'); activeFilter = btn.dataset.filter || 'all'; applyFilters(); }));

    const modalElement = document.getElementById('detailModal');
    const detailModal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const modalTitle = document.getElementById('detailModalLabel');
    const modalContent = document.getElementById('modalContent');
    document.body.addEventListener('click', function(e) {
        const button = e.target.closest('.btn-detail');
        if (!button) return;
        e.preventDefault();
        const cluster = button.dataset.cluster;
        const date = button.dataset.date;
        const type = button.dataset.type;
        const title = button.dataset.title;
        modalTitle.textContent = `${title} - ${cluster} (${new Date(date).toLocaleDateString('es-CL')})`;
        modalContent.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';
        detailModal.show();
        const url = `{{ route('proxmox.history.changeDetails') }}?cluster_name=${encodeURIComponent(cluster)}&date=${date}&type=${type}`;
        fetch(url)
            .then(response => { if (!response.ok) throw new Error('Error en la respuesta'); return response.json(); })
            .then(data => {
                if (data.success && data.data.length > 0) {
                    let html = '<div class="table-responsive"><table class="table table-dark table-hover table-bordered align-middle modern-table mb-0"><thead><tr>';
                    html += type.includes('vms') ? '<th>VMID</th><th>Nombre</th><th>Nodo</th><th>Fecha</th>' : '<th>ID</th><th>Nodo</th><th>IP</th><th>Fecha</th>';
                    html += '</tr></thead><tbody>';
                    data.data.forEach(item => {
                        html += '<tr>';
                        if (type.includes('vms')) {
                            html += `<td>${item.vmid || item.id_proxmox || 'N/A'}</td>`;
                            html += `<td>${item.name || 'N/A'}</td>`;
                            const nodeName = (item.node_id || 'N/A').replace(/^node\//, '');
                            html += `<td>${nodeName}</td>`;
                        } else {
                            html += `<td>${item.id_proxmox || 'N/A'}</td>`;
                            html += `<td>${item.node || 'N/A'}</td>`;
                            html += `<td>${item.ip || 'N/A'}</td>`;
                        }
                        html += `<td>${new Date(item.created_at).toLocaleString('es-CL')}</td>`;
                        html += '</tr>';
                    });
                    html += `</tbody></table></div><p class="text-center text-light mt-2">Total: ${data.count} registros</p>`;
                    modalContent.innerHTML = html;
                } else {
                    modalContent.innerHTML = '<div class="alert alert-warning">No se encontraron registros para este período.</div>';
                }
            })
            .catch(error => { modalContent.innerHTML = '<div class="alert alert-danger">Error al cargar los datos: ' + error.message + '</div>'; });
    });
});
</script>
@endsection
