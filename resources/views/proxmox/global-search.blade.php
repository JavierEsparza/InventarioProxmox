@extends('layouts.app')

@section('content')
<style>
    .global-search-page { color:#e7f0f8; }
    .global-search-page .page-hero { background:linear-gradient(135deg,#101820 0%,#172330 58%,#0f5132 155%); border:1px solid #2f3b49; border-radius:18px; padding:1.35rem 1.45rem; color:#fff; box-shadow:0 16px 35px rgba(0,0,0,.28); }
    .global-search-page .page-title { font-size:clamp(1.7rem,2.6vw,2.45rem); font-weight:850; letter-spacing:-.03em; margin:0; }
    .global-search-page .page-subtitle { color:#aab7c4; font-size:.95rem; margin-top:.4rem; }
    .global-search-page .modern-panel { background:#0f1821; border:1px solid #1f3345; border-radius:18px; padding:1rem; color:#fff; box-shadow:0 12px 28px rgba(0,0,0,.18); }
    .global-search-page .form-label { color:#96a6b5; font-size:.74rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
    .global-search-page .form-control, .global-search-page .form-select { background:#0d141c !important; border:1px solid #324254 !important; color:#e8eef5 !important; border-radius:12px; min-height:42px; }
    .global-search-page .form-control::placeholder { color:#7f8d9a; }
    .global-search-page .btn-info { background:linear-gradient(135deg,#0dcaf0,#38bdf8); border:0; color:#07131d; font-weight:850; border-radius:10px; min-height:42px; }
    .global-search-page .btn-export-search { background:#198754; border:1px solid #38b87b; color:#fff !important; font-weight:850; border-radius:10px; min-height:42px; box-shadow:0 4px 12px rgba(25,135,84,.22); }
    .global-search-page .btn-export-search:hover, .global-search-page .btn-export-search:focus { background:#21a366; border-color:#65d49a; color:#fff !important; }
    .global-search-page .result-count { display:inline-flex; align-items:center; gap:.35rem; border:1px solid rgba(13,202,240,.55); background:rgba(13,202,240,.12); color:#8be9ff; border-radius:999px; padding:.35rem .65rem; font-size:.8rem; font-weight:850; }
    .global-search-page .section-heading { color:#f1f6fa; font-size:1rem; font-weight:850; display:flex; align-items:center; gap:.45rem; margin:.2rem 0 .75rem; }
    .global-search-page .section-heading .badge { background:#26384a !important; color:#dce6ef; border:1px solid #3a5065; }
    .global-search-page .result-card { background:linear-gradient(180deg,#151f2a,#0f1821); border:1px solid #26394c; border-radius:16px; padding:1rem; color:#e7f0f8; box-shadow:0 10px 24px rgba(0,0,0,.2); height:100%; }
    .global-search-page .result-card strong { color:#fff; font-weight:850; }
    .global-search-page .result-card .text-muted, .global-search-page .result-card .small { color:#91a5b8 !important; }
    .global-search-page .result-card .badge { border-radius:999px; font-weight:800; border:1px solid rgba(255,255,255,.12); }
    .global-search-page .result-card .bg-secondary { background:#26384a !important; color:#dce6ef; }
    .global-search-page .search-table-wrap { background:#0f1821; border:1px solid #26394c; border-radius:16px; overflow:auto; box-shadow:0 10px 24px rgba(0,0,0,.2); }
    .global-search-page .search-table { --bs-table-bg:#0f1821; --bs-table-border-color:#223346; --bs-table-color:#e5edf4; min-width:980px; margin:0; }
    .global-search-page .search-table thead th { background:#0b1520 !important; color:#9fb3c7; border-bottom:1px solid #344455; font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; white-space:nowrap; padding:.75rem .7rem; }
    .global-search-page .search-table tbody td { background:#0f1821 !important; color:#e5edf4; border-color:#223346; vertical-align:middle; padding:.7rem; font-size:.82rem; }
    .global-search-page .search-table tbody tr:nth-child(even) td { background:#101a24 !important; }
    .global-search-page .search-table tbody tr:hover td { background:#142536 !important; }
    .global-search-page .vm-name { color:#fff; font-weight:850; white-space:nowrap; }
    .global-search-page .vm-meta { color:#91a5b8; font-size:.7rem; margin-top:.15rem; }
    .global-search-page .vm-id { display:inline-flex; border:1px solid #3a5065; background:#26384a; color:#dce6ef; border-radius:999px; padding:.22rem .45rem; font-size:.7rem; font-weight:800; white-space:nowrap; }
    .global-search-page .vm-status { display:inline-flex; border-radius:999px; padding:.25rem .5rem; font-size:.7rem; font-weight:850; border:1px solid rgba(255,255,255,.12); white-space:nowrap; }
    .global-search-page .vm-status.running { background:rgba(25,135,84,.18); border-color:rgba(25,135,84,.65); color:#91e7b4; }
    .global-search-page .vm-status.other { background:rgba(255,193,7,.14); border-color:rgba(255,193,7,.62); color:#ffe08a; }
    .global-search-page .vm-os { color:#bcd1e5; white-space:nowrap; }
    .global-search-page .vm-ip { color:#91e7b4; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; white-space:nowrap; }
    .global-search-page .vm-storage { color:#cbd7e2; max-width:230px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:block; }
    .global-search-page .vm-table-tools { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.65rem; margin-bottom:.7rem; }
    .global-search-page .vm-table-tools .form-control, .global-search-page .vm-table-tools .form-select { min-height:36px; border-radius:9px; font-size:.8rem; }
    .global-search-page .vm-table-tools .form-control { max-width:330px; }
    .global-search-page .vm-table-tools .form-select { width:auto; min-width:110px; }
    .global-search-page .vm-table-summary { color:#91a5b8; font-size:.78rem; }
    .global-search-page .vm-sort { cursor:pointer; user-select:none; }
    .global-search-page .vm-sort:hover { color:#e8f7ff !important; }
    .global-search-page .vm-sort::after { content:'↕'; opacity:.5; margin-left:.35rem; }
    .global-search-page .vm-sort.sort-asc::after { content:'↑'; opacity:1; color:#8be9ff; }
    .global-search-page .vm-sort.sort-desc::after { content:'↓'; opacity:1; color:#8be9ff; }
    .global-search-page .vm-pagination { display:flex; justify-content:flex-end; gap:.35rem; margin-top:.7rem; }
    .global-search-page .vm-pagination button { background:#172738; border:1px solid #385168; color:#dce6ef; border-radius:8px; min-width:34px; min-height:32px; font-size:.78rem; }
    .global-search-page .vm-pagination button:hover, .global-search-page .vm-pagination button.active { background:#0d6efd; border-color:#65a5ff; color:#fff; }
    .global-search-page .vm-pagination button:disabled { opacity:.4; cursor:not-allowed; }
    .global-search-page .alert { background:#172330; border:1px solid #2f465a; color:#dce6ef; border-radius:14px; }
</style>
<div class="container-fluid mt-4 px-4 global-search-page">
    <div class="page-hero mb-4">
        <h1 class="page-title">Buscador global</h1>
        <div class="page-subtitle">VMs, nodos, clústeres, storage, IPs, sistemas operativos y agentes.</div>
    </div>

    <div class="modern-panel mb-4">
        <form method="GET" action="{{ route('proxmox.globalSearch') }}" class="row g-2 align-items-end">
            <div class="col-lg-6">
                <label class="form-label">Qué querés encontrar</label>
                <input name="q" value="{{ $term }}" class="form-control inventory-search w-100" placeholder="IP, Ubuntu, Zabbix, serial, VMID, storage..." autofocus>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Tipo</label>
                <select name="type" class="form-select">
                    @foreach(['all' => 'Todo', 'vm' => 'VMs', 'node' => 'Nodos', 'cluster' => 'Clústeres', 'storage' => 'Storage'] as $value => $label)
                        <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Estado VM</label>
                <select name="status" class="form-select">
                    <option value="">Cualquiera</option>
                    <option value="running" @selected($status === 'running')>Running</option>
                    <option value="stopped" @selected($status === 'stopped')>Stopped</option>
                </select>
            </div>
            <div class="col-lg-2 d-grid gap-2">
                <button class="btn btn-info" type="submit"><i class="bi bi-search"></i> Buscar</button>
                <a class="btn btn-export-search" href="{{ route('proxmox.globalSearch.export', ['q' => $term, 'type' => $type, 'status' => $status]) }}"><i class="bi bi-file-earmark-spreadsheet"></i> Exportar</a>
            </div>
        </form>
    </div>

    @if ($term === '')
        <div class="alert alert-secondary">Ingresá un término para buscar en el inventario operativo.</div>
    @elseif ($totalResults === 0)
        <div class="alert alert-warning">No encontramos coincidencias. Probá con una IP parcial, VMID, servicio, sistema operativo o serial.</div>
    @else
        <div class="row g-3">
            <div class="col-12">
                <span class="result-count"><i class="bi bi-search"></i> {{ $totalResults }} resultado(s)</span>
            </div>

            @if ($vmResults->isNotEmpty())
                <div class="col-12"><h2 class="section-heading"><i class="bi bi-pc-display"></i> VMs <span class="badge">{{ $vmResults->count() }}</span></h2></div>
                <div class="col-12">
                    <div class="vm-table-tools">
                        <input type="search" class="form-control" id="vmTableFilter" placeholder="Filtrar en estas VMs..." aria-label="Filtrar en estas VMs">
                        <div class="d-flex align-items-center gap-2">
                            <span class="vm-table-summary" id="vmTableSummary"></span>
                            <label class="vm-table-summary mb-0" for="vmPageSize">Filas</label>
                            <select class="form-select" id="vmPageSize" aria-label="Filas por página">
                                <option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="all">Todas</option>
                            </select>
                        </div>
                    </div>
                    <div class="search-table-wrap">
                        <table class="table search-table align-middle">
                            <thead>
                                <tr><th class="vm-sort" data-column="0">VM</th><th class="vm-sort" data-column="1">VMID</th><th class="vm-sort" data-column="2">Clúster</th><th class="vm-sort" data-column="3">Nodo</th><th class="vm-sort" data-column="4">Estado</th><th class="vm-sort" data-column="5">Sistema operativo</th><th class="vm-sort" data-column="6">IP</th><th class="vm-sort" data-column="7">Storage</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($vmResults as $vm)
                                    @php($vmStatus = strtolower((string) ($vm->status ?: 'S/D')))
                                    <tr>
                                        <td><div class="vm-name">{{ $vm->name ?: 'VM sin nombre' }}</div><div class="vm-meta">Resultado de inventario</div></td>
                                        <td><span class="vm-id">{{ $vm->vmid }}</span></td>
                                        <td>{{ $vm->cluster_name ?: 'Sin clúster' }}</td>
                                        <td>{{ $vm->node_id ?: 'S/D' }}</td>
                                        <td><span class="vm-status {{ $vmStatus === 'running' ? 'running' : 'other' }}">{{ $vm->status ?: 'S/D' }}</span></td>
                                        <td><span class="vm-os">{{ $vm->os_name ?: $vm->os_name_manual ?: 'OS S/D' }}</span></td>
                                        <td><span class="vm-ip">{{ $vm->ip_address ?: $vm->ip_address_manual ?: 'S/D' }}</span></td>
                                        <td><span class="vm-storage" title="{{ $vm->storageName ?: 'S/D' }}">{{ $vm->storageName ?: 'S/D' }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="vm-pagination" id="vmPagination" aria-label="Paginación de VMs"></div>
                </div>
            @endif

            @if ($nodeResults->isNotEmpty())
                <div class="col-12 mt-3"><h2 class="section-heading"><i class="bi bi-server"></i> Nodos <span class="badge">{{ $nodeResults->count() }}</span></h2></div>
                @foreach ($nodeResults as $node)
                    <div class="col-xl-4 col-md-6">
                        <div class="result-card">
                            <strong>{{ $node->node }}</strong>
                            <span class="badge bg-secondary">{{ $node->brand ?: 'Marca S/D' }}</span>
                            <div class="small text-muted mt-2">{{ $node->cluster_name ?: 'Sin clúster' }} · {{ $node->ip ?: 'IP S/D' }}</div>
                            <div class="small mt-2">{{ $node->model ?: 'Modelo S/D' }} · PVE {{ $node->pve_version ?: 'S/D' }}</div>
                            <div class="small text-muted mt-2">Serial: {{ $node->serial_number ?: 'S/D' }} · Firmware: {{ $node->firmware_version ?: 'S/D' }}</div>
                        </div>
                    </div>
                @endforeach
            @endif

            @if ($clusterResults->isNotEmpty())
                <div class="col-12 mt-3"><h2 class="section-heading"><i class="bi bi-diagram-3"></i> Clústeres <span class="badge">{{ $clusterResults->count() }}</span></h2></div>
                @foreach ($clusterResults as $cluster)
                    <div class="col-xl-4 col-md-6">
                        <div class="result-card">
                            <strong>{{ $cluster->name }}</strong>
                            <div class="small text-muted">Clúster Proxmox</div>
                        </div>
                    </div>
                @endforeach
            @endif

            @if ($storageResults->isNotEmpty())
                <div class="col-12 mt-3"><h2 class="section-heading"><i class="bi bi-device-hdd"></i> Storage <span class="badge">{{ $storageResults->count() }}</span></h2></div>
                @foreach ($storageResults as $storage)
                    <div class="col-xl-4 col-md-6">
                        <div class="result-card">
                            <strong>{{ $storage->storage }}</strong>
                            <div class="small text-muted mt-2">{{ $storage->cluster ?: 'Sin clúster' }} · {{ $storage->plugintype ?: 'Tipo S/D' }}</div>
                            <span class="badge {{ $storage->status === 'online' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $storage->status ?: 'S/D' }}</span>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    @endif
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = document.querySelector('.search-table');
    const filter = document.getElementById('vmTableFilter');
    const pageSize = document.getElementById('vmPageSize');
    const summary = document.getElementById('vmTableSummary');
    const pagination = document.getElementById('vmPagination');
    if (!table || !filter || !pageSize || !summary || !pagination) return;

    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    let filteredRows = rows.slice();
    let currentPage = 1;
    let sortColumn = -1;
    let sortDirection = 1;

    function normalized(value) { return value.toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g, ''); }

    function render() {
        const sizeValue = pageSize.value;
        const size = sizeValue === 'all' ? filteredRows.length || 1 : Number(sizeValue);
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / size));
        currentPage = Math.min(currentPage, totalPages);
        rows.forEach(row => row.style.display = 'none');
        filteredRows.slice((currentPage - 1) * size, currentPage * size).forEach(row => row.style.display = '');
        summary.textContent = filteredRows.length ? `Mostrando ${(currentPage - 1) * size + 1}-${Math.min(currentPage * size, filteredRows.length)} de ${filteredRows.length}` : 'Sin coincidencias';
        pagination.replaceChildren();
        if (totalPages <= 1) return;
        const addButton = (label, page, disabled, active) => {
            const button = document.createElement('button');
            button.type = 'button'; button.textContent = label; button.disabled = disabled;
            if (active) button.classList.add('active');
            button.addEventListener('click', () => { currentPage = page; render(); });
            pagination.appendChild(button);
        };
        addButton('‹', Math.max(1, currentPage - 1), currentPage === 1, false);
        for (let page = 1; page <= totalPages; page++) addButton(String(page), page, false, page === currentPage);
        addButton('›', Math.min(totalPages, currentPage + 1), currentPage === totalPages, false);
    }

    filter.addEventListener('input', function () {
        const term = normalized(filter.value.trim());
        filteredRows = rows.filter(row => normalized(row.textContent).includes(term));
        currentPage = 1; render();
    });
    pageSize.addEventListener('change', function () { currentPage = 1; render(); });
    table.querySelectorAll('.vm-sort').forEach(header => header.addEventListener('click', function () {
        const column = Number(header.dataset.column);
        sortDirection = sortColumn === column ? sortDirection * -1 : 1;
        sortColumn = column;
        table.querySelectorAll('.vm-sort').forEach(item => item.classList.remove('sort-asc', 'sort-desc'));
        header.classList.add(sortDirection === 1 ? 'sort-asc' : 'sort-desc');
        rows.sort((a, b) => {
            const left = a.cells[column].textContent.trim();
            const right = b.cells[column].textContent.trim();
            if (column === 1) return ((parseInt(left, 10) || 0) - (parseInt(right, 10) || 0)) * sortDirection;
            return normalized(left).localeCompare(normalized(right), 'es', { numeric: true }) * sortDirection;
        });
        rows.forEach(row => tbody.appendChild(row));
        filteredRows = rows.filter(row => normalized(row.textContent).includes(normalized(filter.value.trim())));
        currentPage = 1; render();
    }));
    render();
});
</script>
@endsection
