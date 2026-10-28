@extends('layouts.app')

@section('content')
<style>
    #syncProgressBar { position:fixed; top:0; left:0; width:100%; z-index:9999; display:none; padding:.75rem 1rem; background:linear-gradient(180deg, rgba(10,18,27,.98), rgba(10,18,27,.92)); border-bottom:1px solid #2d4053; box-shadow:0 12px 34px rgba(0,0,0,.48); backdrop-filter:blur(12px); }
    .sync-progress-card { max-width:1180px; margin:0 auto; background:linear-gradient(135deg,#101820,#132536); border:1px solid #284056; border-radius:16px; padding:.85rem 1rem; color:#fff; box-shadow:inset 0 1px 0 rgba(255,255,255,.04); }
    .sync-progress-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; background:rgba(255,193,7,.14); border:1px solid rgba(255,193,7,.42); color:#ffd76a; font-size:1.15rem; }
    .sync-progress-title { font-weight:900; letter-spacing:-.02em; margin:0; color:#fff; }
    .sync-progress-subtitle { color:#93a6b8; font-size:.82rem; margin-top:.12rem; }
    .sync-progress-percent { min-width:58px; text-align:right; font-weight:900; color:#9be7ff; }
    .sync-progress-track { height:13px; background:#0a131c; border:1px solid #26384a; border-radius:999px; overflow:hidden; box-shadow:inset 0 1px 4px rgba(0,0,0,.55); }
    .sync-progress-fill { height:100%; width:0%; border-radius:999px; background:linear-gradient(90deg,#ffc107,#1cc88a,#38bdf8); background-size:200% 100%; animation:syncGradient 1.8s linear infinite; transition:width .35s ease; position:relative; }
    .sync-progress-fill::after { content:''; position:absolute; inset:0; background:linear-gradient(120deg, transparent 0%, rgba(255,255,255,.28) 45%, transparent 70%); transform:translateX(-100%); animation:syncShine 1.5s ease-in-out infinite; }
    .sync-step { display:inline-flex; align-items:center; gap:.35rem; border:1px solid #344455; background:rgba(255,255,255,.045); color:#9fb0c2; border-radius:999px; padding:.25rem .55rem; font-size:.75rem; font-weight:800; }
    .sync-step.active { border-color:rgba(255,193,7,.65); background:rgba(255,193,7,.14); color:#ffe08a; }
    .sync-step.done { border-color:rgba(25,135,84,.65); background:rgba(25,135,84,.16); color:#91e7b4; }
    .sync-progress-card.success .sync-progress-icon { color:#91e7b4; border-color:rgba(25,135,84,.65); background:rgba(25,135,84,.18); }
    .sync-progress-card.success .sync-progress-fill { background:linear-gradient(90deg,#1cc88a,#38bdf8); animation:none; }
    .sync-progress-card.danger .sync-progress-icon { color:#ff9ca8; border-color:rgba(220,53,69,.65); background:rgba(220,53,69,.18); }
    .sync-progress-card.danger .sync-progress-fill { background:linear-gradient(90deg,#dc3545,#ff9ca8); animation:none; }
    @keyframes syncGradient { 0% { background-position:0% 50%; } 100% { background-position:200% 50%; } }
    @keyframes syncShine { 0% { transform:translateX(-100%); } 65%,100% { transform:translateX(180%); } }
    @media (max-width: 768px) { #syncProgressBar { padding:.55rem; } .sync-progress-card { border-radius:14px; padding:.75rem; } .sync-step { display:none; } }
    .page-hero { background:linear-gradient(135deg,#101820 0%,#172330 58%,#0f5132 155%); border:1px solid #2f3b49; border-radius:18px; padding:1.25rem; color:#fff; box-shadow:0 16px 35px rgba(0,0,0,.28); }
    .page-title { font-size:clamp(1.7rem,2.6vw,2.45rem); font-weight:850; letter-spacing:-.03em; margin:0; }
    .page-subtitle { color:#aab7c4; font-size:.95rem; margin-top:.4rem; }
    .cluster-pill { display:inline-flex; align-items:center; gap:.35rem; border:1px solid #344455; background:rgba(255,255,255,.06); color:#d7e2ec; border-radius:999px; padding:.35rem .65rem; font-size:.82rem; font-weight:750; }
    .cluster-pill.success { border-color:rgba(25,135,84,.6); background:rgba(25,135,84,.18); color:#91e7b4; }
    .cluster-pill.warn { border-color:rgba(255,193,7,.62); background:rgba(255,193,7,.14); color:#ffe08a; }
    .metric-card { background:#151d26; border:1px solid #2b3846; border-radius:16px; padding:1rem; color:#fff; height:100%; box-shadow:inset 0 1px 0 rgba(255,255,255,.03); }
    .metric-label { color:#96a6b5; font-size:.74rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
    .metric-value { font-size:2rem; font-weight:850; line-height:1; margin-top:.35rem; }
    .metric-foot { color:#8d9baa; font-size:.82rem; margin-top:.5rem; }
    .modern-panel { background:#0f1821; border:1px solid #1f3345; border-radius:18px; padding:1rem; color:#fff; box-shadow:0 12px 28px rgba(0,0,0,.18); }
    .inventory-toolbar { display:flex; flex-wrap:wrap; justify-content:space-between; gap:.75rem; margin-bottom:1rem; }
    .inventory-search { background:#0d141c; border:1px solid #324254; color:#e8eef5; border-radius:12px; padding:.65rem .85rem; min-width:360px; }
    .inventory-search::placeholder { color:#7f8d9a; }
    .inventory-filter { border:1px solid #344455; background:rgba(255,255,255,.04); color:#dce6ef; border-radius:999px; padding:.45rem .8rem; font-weight:750; font-size:.84rem; }
    .inventory-filter.active { background:rgba(25,135,84,.25); border-color:rgba(25,135,84,.7); color:#94ebb9; }
    .modern-table { --bs-table-bg:#0f1821; --bs-table-border-color:#223346; --bs-table-color:#e5edf4; border-radius:14px; overflow:hidden; }
    .modern-table thead th { background:#0c151d !important; color:#8fa3b7; font-size:.72rem; text-transform:uppercase; letter-spacing:.07em; border-bottom:1px solid #344455; white-space:nowrap; }
    .modern-table tbody td { background:#0f1821 !important; border-color:#223346; vertical-align:middle; }
    .modern-table tbody tr.inventory-row:nth-of-type(4n+1) td { background:#101a24 !important; }
    .modern-table tbody tr.inventory-row:hover td { background:#142536 !important; }
    .modern-table tbody tr.detail-row td { background:#0c151d !important; }
    .node-name-main { font-weight:850; color:#fff; }
    .node-name-sub { color:#8c9baa; font-size:.78rem; }
    .badge-soft { border:1px solid #344455; background:rgba(255,255,255,.06); color:#dbe5ee; border-radius:999px; padding:.3rem .55rem; font-size:.74rem; font-weight:800; }
    .badge-soft.running { border-color:rgba(25,135,84,.65); background:rgba(25,135,84,.18); color:#91e7b4; }
    .badge-soft.warn { border-color:rgba(255,193,7,.62); background:rgba(255,193,7,.14); color:#ffe08a; }
    .badge-soft.danger { border-color:rgba(220,53,69,.65); background:rgba(220,53,69,.18); color:#ff9ca8; }
    .chev { color:#7890a5; transition:transform .18s ease; }
    tr[aria-expanded="true"] .chev { transform:rotate(180deg); }
    .detail-shell { background:#0f1821; border-top:1px solid #223346; padding:1rem; }
    .nav-pills .nav-link { color:#cbd7e2; border:1px solid #344455; background:rgba(255,255,255,.035); margin-right:.35rem; border-radius:999px; font-weight:750; }
    .nav-pills .nav-link.active { background:rgba(25,135,84,.25); border-color:rgba(25,135,84,.7); color:#94ebb9; }
    .detail-shell .table { --bs-table-bg:#0f1821; --bs-table-border-color:#223346; --bs-table-color:#e5edf4; }
    .detail-shell .table thead th { background:#0c151d !important; color:#8fa3b7 !important; }
    .action-btn { border-radius:10px; font-weight:750; }
    .inventory-empty-filter { display:none; color:#aeb9c5; text-align:center; padding:1rem; }
    .modal-content.modern-modal { background:#0f1821; border:1px solid #2d4053; color:#fff; border-radius:16px; }
    .iface-table { font-size:.82rem; border-radius:14px; overflow:hidden; }
    .iface-table thead th { background:#0a131c !important; color:#8fa3b7 !important; text-transform:uppercase; letter-spacing:.07em; font-size:.68rem; white-space:nowrap; }
    .iface-table tbody td { background:#0f1821 !important; border-color:#203247 !important; }
    .iface-table tbody tr:nth-child(even) td { background:#101a24 !important; }
    .iface-name { font-weight:900; color:#eaf2f8; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace; }
    .iface-chip { display:inline-flex; align-items:center; gap:.25rem; border:1px solid #344455; background:#172330; color:#dce6ef; border-radius:999px; padding:.18rem .48rem; font-size:.72rem; font-weight:800; margin:.08rem; }
    .iface-chip.bridge { border-color:rgba(13,202,240,.55); background:rgba(13,202,240,.12); color:#8be9ff; }
    .iface-chip.bond { border-color:rgba(255,193,7,.6); background:rgba(255,193,7,.12); color:#ffe08a; }
    .iface-chip.up { border-color:rgba(25,135,84,.65); background:rgba(25,135,84,.18); color:#91e7b4; }
    .iface-chip.down { border-color:rgba(220,53,69,.65); background:rgba(220,53,69,.18); color:#ff9ca8; }
    .iface-mac { font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace; color:#b8c7d7; background:#0b131b; border:1px solid #26384a; border-radius:8px; padding:.18rem .42rem; white-space:nowrap; }
    .iface-ip { color:#91e7b4; font-weight:850; }
    .iface-muted { color:#687b8e; }
    .iface-comment { color:#ffe08a; font-style:italic; }
    .iface-legend { display:flex; flex-wrap:wrap; gap:.45rem; margin-bottom:.75rem; }
</style>

@php
    $totalNodes = $nodes->count();
    $clustersCount = $nodes->pluck('cluster_name')->filter()->unique()->count();
    $brandsCount = $nodes->pluck('brand')->filter()->unique()->count();
    $serialOk = $nodes->filter(fn($n) => !empty($n->serial_number) && $n->serial_number !== 'N/A')->count();
    $firmwareOk = $nodes->filter(fn($n) => !empty($n->firmware_version) && $n->firmware_version !== 'N/A')->count();
    $licensesSet = $nodes->filter(fn($n) => !empty($n->support_license_date))->count();
@endphp

<div class="container-fluid mt-4 px-4">
    <div id="syncProgressBar" aria-live="polite">
        <div id="syncProgressCard" class="sync-progress-card">
            <div class="d-flex align-items-center gap-3">
                <div id="syncProgressIcon" class="sync-progress-icon"><i class="bi bi-arrow-repeat"></i></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                        <div>
                            <h6 class="sync-progress-title">Sincronizando inventario por SSH</h6>
                            <div id="progressText" class="sync-progress-subtitle">Preparando conexión...</div>
                        </div>
                        <div id="progressPercentage" class="sync-progress-percent">0%</div>
                    </div>
                    <div class="sync-progress-track"><div id="progressBarFill" class="sync-progress-fill" role="progressbar" style="width:0%" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div></div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span id="syncStepConnect" class="sync-step active"><i class="bi bi-plug"></i> Conexión</span>
                        <span id="syncStepHardware" class="sync-step"><i class="bi bi-cpu"></i> Hardware</span>
                        <span id="syncStepNetwork" class="sync-step"><i class="bi bi-hdd-network"></i> Interfaces</span>
                        <span id="syncStepFinish" class="sync-step"><i class="bi bi-check2-circle"></i> Finalización</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))<div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
    @if (session('error'))<div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif

    <div class="page-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <div class="d-flex flex-wrap gap-2 mb-3"><span class="cluster-pill success">● inventario hardware</span><span class="cluster-pill">{{ $clustersCount }} cluster(s)</span><span class="cluster-pill">{{ $totalNodes }} nodo(s)</span></div>
                <h1 class="page-title">Inventario de Hardware</h1>
                <div class="page-subtitle">Nodos Proxmox, seriales, firmware, soporte e interfaces físicas/lógicas.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('proxmox.exportInventory') }}" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Exportar a Excel</a>
                <button type="button" id="sshSyncBtn" class="btn btn-warning" onclick="startSyncWithProgress()"><i class="fas fa-sync"></i> Sincronizar Hardware (SSH)</button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Nodos</div><div class="metric-value">{{ $totalNodes }}</div><div class="metric-foot">Total inventariado</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Clusters</div><div class="metric-value">{{ $clustersCount }}</div><div class="metric-foot">Con hardware</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Marcas</div><div class="metric-value">{{ $brandsCount }}</div><div class="metric-foot">Fabricantes detectados</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Serial OK</div><div class="metric-value">{{ $serialOk }}</div><div class="metric-foot">Con serial number</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Firmware</div><div class="metric-value">{{ $firmwareOk }}</div><div class="metric-foot">Con versión reportada</div></div></div>
        <div class="col-xl-2 col-md-4 col-sm-6"><div class="metric-card"><div class="metric-label">Soporte</div><div class="metric-value">{{ $licensesSet }}</div><div class="metric-foot">Licencias cargadas</div></div></div>
    </div>

    <div class="modern-panel">
        <div class="inventory-toolbar">
            <form action="{{ route('proxmox.inventory') }}" method="GET" class="d-flex gap-2 flex-wrap">
                <input id="inventorySearch" type="search" name="_q" class="inventory-search" placeholder="Buscar por cluster, nodo, IP, marca, modelo, serial..." value="{{ $searchQuery ?? '' }}">
                <button class="btn btn-outline-info" type="submit"><i class="fa fa-search"></i> Buscar</button>
                @if($searchQuery ?? false)<a class="btn btn-outline-light" href="{{ route('proxmox.inventory') }}">Limpiar</a>@endif
            </form>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="inventory-filter active" data-filter="all">Todos</button>
                <button type="button" class="inventory-filter" data-filter="lenovo">Lenovo</button>
                <button type="button" class="inventory-filter" data-filter="huawei">Huawei</button>
                <button type="button" class="inventory-filter" data-filter="dell">Dell Inc</button>
                <button type="button" class="inventory-filter" data-filter="noserial">Sin serial</button>
                <button type="button" class="inventory-filter" data-filter="nofirmware">Sin firmware</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover table-bordered align-middle modern-table mb-0">
                <thead class="text-center"><tr><th>Cluster / Nodo</th><th>IP Gestión</th><th>Marca</th><th>Modelo</th><th>Serial Number</th><th>Firmware</th><th>Soporte</th><th>Actualizado</th><th>Acciones</th></tr></thead>
                <tbody id="inventoryTableBody">
                @forelse ($nodes as $node)
                    @php
                        $brand = $node->brand ?: 'N/A';
                        $serialMissing = empty($node->serial_number) || $node->serial_number === 'N/A';
                        $firmwareMissing = empty($node->firmware_version) || $node->firmware_version === 'N/A';
                        $nodeSearchText = strtolower(($node->cluster_name ?? '') . ' ' . ($node->node ?? '') . ' ' . ($node->ip ?? '') . ' ' . ($node->brand ?? '') . ' ' . ($node->model ?? '') . ' ' . ($node->serial_number ?? '') . ' ' . ($node->firmware_version ?? '') . ' ' . ($node->pve_version ?? '') . ' ' . ($node->kernel_version ?? ''));
                        $supportText = $node->support_license_date ? \Carbon\Carbon::parse($node->support_license_date)->format('d/m/Y') : 'S/D';
                    @endphp
                    <tr data-bs-toggle="collapse" data-bs-target="#collapseNode{{ $node->id }}" class="inventory-row" style="cursor:pointer;" aria-expanded="false" data-brand="{{ strtolower($brand) }}" data-serial="{{ $serialMissing ? '0' : '1' }}" data-firmware="{{ $firmwareMissing ? '0' : '1' }}" data-search="{{ e($nodeSearchText) }}">
                        <td><div class="node-name-main"><i class="bi bi-chevron-down chev me-2"></i>{{ $node->node }}</div><div class="node-name-sub">{{ $node->cluster_name ?? 'Sin cluster' }}</div></td>
                        <td class="text-center">{{ $node->ip }}</td>
                        <td class="text-center"><span class="badge-soft {{ strtolower($brand) === 'lenovo' ? 'running' : '' }}">{{ $brand }}</span></td>
                        <td class="text-center">{{ $node->model ?? 'N/A' }}</td>
                        <td class="text-center font-monospace small">{{ $node->serial_number ?? 'N/A' }}</td>
                        <td class="text-center"><span class="badge-soft {{ $firmwareMissing ? 'warn' : '' }}">{{ $node->firmware_version ?? 'N/A' }}</span></td>
                        <td class="text-center"><span class="badge-soft {{ $node->support_license_date ? 'running' : '' }}">{{ $supportText }}</span></td>
                        <td class="text-center node-name-sub">{{ \Carbon\Carbon::parse($node->updated_at)->format('d/m/Y H:i') }}</td>
                        <td class="text-center">
                            <div class="d-flex flex-wrap gap-1 justify-content-center">
                                <button type="button" class="btn btn-warning btn-sm action-btn node-sync-btn" onclick="event.stopPropagation(); startSyncWithProgress({{ $node->id }}, '{{ e($node->node) }}', this);"><i class="bi bi-arrow-repeat"></i> Sync</button>
                                <button class="btn btn-outline-info btn-sm action-btn" onclick="event.stopPropagation();" data-bs-toggle="modal" data-bs-target="#manualInventoryModal" data-node-id="{{ $node->id }}" data-node-name="{{ $node->node }}" data-brand="{{ e($node->brand) }}" data-model="{{ e($node->model) }}" data-serial="{{ e($node->serial_number) }}" data-firmware="{{ e($node->firmware_version) }}" data-pve="{{ e($node->pve_version) }}" data-kernel="{{ e($node->kernel_version) }}" data-skip="{{ $node->skip_ssh_sync ? '1' : '0' }}"><i class="bi bi-pencil-square"></i> Manual/TXT</button>
                                <button class="btn btn-outline-light btn-sm action-btn" onclick="event.stopPropagation();" data-bs-toggle="modal" data-bs-target="#editLicenseModal" data-node-id="{{ $node->id }}" data-node-name="{{ $node->node }}" data-support-date="{{ $node->support_license_date }}">Soporte</button>
                            </div>
                        </td>
                    </tr>
                    <tr class="detail-row"><td colspan="9" class="p-0 border-0">
                        <div class="collapse" id="collapseNode{{ $node->id }}"><div class="detail-shell text-start">
                            @php
                                $allInterfaces = $node->interfaces;
                                $bridgesMap = $allInterfaces->where('type', 'bridge')->keyBy('iface');
                                $groupBridges = $allInterfaces->where('type', 'bridge');
                                $groupBonds = $allInterfaces->where('type', 'bond');
                                $ethInterfaces = $allInterfaces->where('type', 'eth')->filter(fn($i) => !preg_match('/^(tap|veth|fwpr|fwbr|fwln|vlan|vxlan)/', $i->iface));
                                $groupEthActive = $ethInterfaces->filter(fn($i) => $i->address || $i->bridge_parent || $i->bond_parent || $i->active);
                                $groupEthRest = $ethInterfaces->diffKeys($groupEthActive);
                            @endphp
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="cluster-pill">PVE {{ $node->pve_version ?? 'S/D' }}</span>
                                <span class="cluster-pill">Kernel {{ $node->kernel_version ?? 'S/D' }}</span>
                                <span class="cluster-pill">{{ $allInterfaces->count() }} interfaces</span>
                                @if($node->skip_ssh_sync)<span class="cluster-pill warn">Carga manual/offline: preservado sin SSH</span>@endif
                            </div>
                            <ul class="nav nav-pills mb-3" id="pills-tab-{{ $node->id }}" role="tablist">
                                <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#pills-active-{{ $node->id }}" type="button"><i class="bi bi-activity"></i> Físicas Activas <span class="badge bg-white text-success ms-1">{{ $groupEthActive->count() }}</span></button></li>
                                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-bonds-{{ $node->id }}" type="button"><i class="bi bi-layers"></i> Bonds <span class="badge bg-white text-dark ms-1">{{ $groupBonds->count() }}</span></button></li>
                                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-bridges-{{ $node->id }}" type="button"><i class="bi bi-hdd-network"></i> Bridges <span class="badge bg-white text-info ms-1">{{ $groupBridges->count() }}</span></button></li>
                                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-rest-{{ $node->id }}" type="button"><i class="bi bi-plug"></i> Disponibles <span class="badge bg-secondary text-white ms-1">{{ $groupEthRest->count() }}</span></button></li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="pills-active-{{ $node->id }}">@if($groupEthActive->count() > 0) @include('proxmox.partials.interface_table', ['interfaces'=>$groupEthActive,'bridgesMap'=>$bridgesMap,'tableColor'=>'table-success']) @else <div class="alert alert-dark">No hay interfaces físicas activas.</div> @endif</div>
                                <div class="tab-pane fade" id="pills-bonds-{{ $node->id }}">
                                    @if($groupBonds->count() > 0)
                                        <div class="iface-legend"><span class="iface-chip bond">Bond lógico</span><span class="iface-chip">Miembro físico + MAC real</span></div>
                                        <div class="table-responsive"><table class="table table-sm table-bordered table-dark table-hover mb-0 iface-table"><thead><tr><th>Bond</th><th>IP / CIDR</th><th>Puertos miembros</th><th>Bond MAC</th><th>Estado</th></tr></thead><tbody>
                                        @foreach($groupBonds as $bond)
                                            <tr>
                                                <td><span class="iface-name">{{ $bond->iface }}</span></td>
                                                <td>@if($bond->address)<span class="iface-ip">{{ $bond->address }}{{ $bond->cidr ? '/' . $bond->cidr : '' }}</span>@else<span class="iface-muted">Layer 2 / Sin IP</span>@endif</td>
                                                <td>
                                                    @if($bond->ports)
                                                        <div class="d-flex flex-wrap gap-1">
                                                            @foreach(explode(' ', $bond->ports) as $portName)
                                                                @php $slaveIface=$allInterfaces->firstWhere('iface',$portName); @endphp
                                                                <span class="iface-chip bond">{{ $portName }}</span>
                                                                @if($slaveIface)<span class="iface-mac">{{ $slaveIface->mac }}</span>@else<span class="iface-muted">No data</span>@endif
                                                            @endforeach
                                                        </div>
                                                    @else<span class="iface-muted">--</span>@endif
                                                </td>
                                                <td><span class="iface-mac">{{ $bond->mac ?? '--' }}</span></td>
                                                <td>@if($bond->active)<span class="iface-chip up">UP</span>@else<span class="iface-chip down">DOWN</span>@endif</td>
                                            </tr>
                                        @endforeach
                                        </tbody></table></div>
                                    @else <div class="alert alert-dark">No hay Bonds configurados.</div> @endif
                                </div>
                                <div class="tab-pane fade" id="pills-bridges-{{ $node->id }}">
                                    @if($groupBridges->count() > 0)
                                        <div class="iface-legend"><span class="iface-chip bridge">Bridge</span><span class="iface-chip">Puerto miembro</span><span class="iface-chip up">UP</span><span class="iface-chip down">DOWN</span></div>
                                        <div class="table-responsive"><table class="table table-sm table-bordered table-dark table-hover mb-0 iface-table"><thead><tr><th>Bridge</th><th>Puertos miembros</th><th>IP / CIDR</th><th>MAC</th><th>Estado</th></tr></thead><tbody>
                                        @foreach($groupBridges as $br)
                                            <tr>
                                                <td><span class="iface-name">{{ $br->iface }}</span></td>
                                                <td>@if($br->ports)<div class="d-flex flex-wrap gap-1">@foreach(explode(' ', $br->ports) as $port)<span class="iface-chip bridge">{{ $port }}</span>@endforeach</div>@else<span class="iface-muted">--</span>@endif</td>
                                                <td>@if($br->address)<span class="iface-ip">{{ $br->address }}/{{ $br->cidr }}</span>@else<span class="iface-muted">--</span>@endif</td>
                                                <td><span class="iface-mac">{{ $br->mac ?? '--' }}</span></td>
                                                <td>@if($br->active)<span class="iface-chip up">UP</span>@else<span class="iface-chip down">DOWN</span>@endif</td>
                                            </tr>
                                        @endforeach
                                        </tbody></table></div>
                                    @else <div class="alert alert-dark">No hay Bridges configurados.</div> @endif
                                </div>
                                <div class="tab-pane fade" id="pills-rest-{{ $node->id }}">@if($groupEthRest->count() > 0) @include('proxmox.partials.interface_table', ['interfaces'=>$groupEthRest,'bridgesMap'=>$bridgesMap,'tableColor'=>'table-secondary']) @else <div class="alert alert-dark">No hay interfaces disponibles (todas en uso).</div> @endif</div>
                            </div>
                        </div></div>
                    </td></tr>
                @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">No se encontraron nodos que coincidan con la búsqueda.</td></tr>
                @endforelse
                </tbody>
            </table>
            <div id="inventoryEmptyFilter" class="inventory-empty-filter">No hay nodos que coincidan con el filtro aplicado.</div>
        </div>
    </div>

    <div class="modal fade" id="editLicenseModal" tabindex="-1" aria-labelledby="editLicenseModalLabel" aria-hidden="true" data-bs-theme="dark">
        <div class="modal-dialog"><div class="modal-content modern-modal"><div class="modal-header border-secondary"><h5 class="modal-title" id="editLicenseModalLabel">Editar Licencia de Soporte</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form id="editLicenseForm" method="POST" action="">@csrf<div class="modal-body"><p>Nodo: <strong id="modalNodeName" class="text-info"></strong></p><div class="mb-3"><label for="modalLicenseDate" class="form-label">Fecha de vencimiento</label><input type="date" class="form-control bg-dark text-white border-secondary" id="modalLicenseDate" name="support_license_date"></div><small class="text-muted">Dejá la fecha en blanco para borrarla.</small></div><div class="modal-footer border-secondary"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar Cambios</button></div></form>
        </div></div>
    </div>

    <div class="modal fade" id="manualInventoryModal" tabindex="-1" aria-labelledby="manualInventoryModalLabel" aria-hidden="true" data-bs-theme="dark">
        <div class="modal-dialog modal-lg"><div class="modal-content modern-modal"><div class="modal-header border-secondary"><h5 class="modal-title" id="manualInventoryModalLabel">Carga manual / TXT de inventario</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form id="manualInventoryForm" method="POST" action="" enctype="multipart/form-data">@csrf
                <div class="modal-body">
                    <p class="mb-3">Nodo: <strong id="manualNodeName" class="text-info"></strong></p>
                    <div class="alert alert-dark border-secondary small">Para nodos sin sudo/SSH podés cargar datos manuales o adjuntar TXT del collector. Si subís TXT, el nodo queda marcado para preservar inventario y no pisarlo con sync SSH global.</div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Marca</label><input type="text" class="form-control bg-dark text-white border-secondary" id="manualBrand" name="brand"></div>
                        <div class="col-md-6"><label class="form-label">Modelo</label><input type="text" class="form-control bg-dark text-white border-secondary" id="manualModel" name="model"></div>
                        <div class="col-md-6"><label class="form-label">Serial Number</label><input type="text" class="form-control bg-dark text-white border-secondary" id="manualSerial" name="serial_number"></div>
                        <div class="col-md-6"><label class="form-label">Firmware / BIOS</label><input type="text" class="form-control bg-dark text-white border-secondary" id="manualFirmware" name="firmware_version"></div>
                        <div class="col-md-6"><label class="form-label">Versión PVE</label><input type="text" class="form-control bg-dark text-white border-secondary" id="manualPve" name="pve_version"></div>
                        <div class="col-md-6"><label class="form-label">Kernel</label><input type="text" class="form-control bg-dark text-white border-secondary" id="manualKernel" name="kernel_version"></div>
                        <div class="col-12"><label class="form-label">Archivo TXT collector</label><input type="file" class="form-control bg-dark text-white border-secondary" name="inventory_txt" accept=".txt,.log,text/plain"><small class="text-muted">Formato recomendado: bloques ===== iface ===== con salida de ethtool, ethtool -i, ethtool -m, dmidecode/pveversion/uname.</small></div>
                        <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="manualSkipSsh" name="skip_ssh_sync" value="1"><label class="form-check-label" for="manualSkipSsh">Preservar este nodo: excluirlo de sincronización SSH global</label></div></div>
                    </div>
                </div>
                <div class="modal-footer border-secondary"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-info">Guardar carga manual/TXT</button></div>
            </form>
        </div></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editLicenseModal = document.getElementById('editLicenseModal');
    if(editLicenseModal) editLicenseModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const nodeId = button.getAttribute('data-node-id');
        document.getElementById('editLicenseForm').setAttribute('action', '{{ url("proxmox/inventory/node") }}/' + nodeId + '/update-license');
        document.getElementById('modalNodeName').textContent = button.getAttribute('data-node-name');
        const supportDate = button.getAttribute('data-support-date');
        document.getElementById('modalLicenseDate').value = supportDate ? supportDate.split(' ')[0] : '';
    });

    const manualInventoryModal = document.getElementById('manualInventoryModal');
    if(manualInventoryModal) manualInventoryModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const nodeId = button.getAttribute('data-node-id');
        document.getElementById('manualInventoryForm').setAttribute('action', '{{ url("proxmox/inventory/node") }}/' + nodeId + '/manual');
        document.getElementById('manualNodeName').textContent = button.getAttribute('data-node-name') || '';
        document.getElementById('manualBrand').value = button.getAttribute('data-brand') || '';
        document.getElementById('manualModel').value = button.getAttribute('data-model') || '';
        document.getElementById('manualSerial').value = button.getAttribute('data-serial') || '';
        document.getElementById('manualFirmware').value = button.getAttribute('data-firmware') || '';
        document.getElementById('manualPve').value = button.getAttribute('data-pve') || '';
        document.getElementById('manualKernel').value = button.getAttribute('data-kernel') || '';
        document.getElementById('manualSkipSsh').checked = button.getAttribute('data-skip') === '1';
    });

    const search = document.getElementById('inventorySearch');
    const filters = document.querySelectorAll('.inventory-filter');
    const rows = document.querySelectorAll('.inventory-row');
    const empty = document.getElementById('inventoryEmptyFilter');
    let activeFilter = 'all';
    function applyFilters() {
        const query = (search?.value || '').toLowerCase().trim();
        let visible = 0;
        rows.forEach(row => {
            const detail = row.nextElementSibling;
            const matchesSearch = !query || (row.dataset.search || '').includes(query);
            let matchesFilter = true;
            if (activeFilter === 'lenovo') matchesFilter = (row.dataset.brand || '').includes('lenovo');
            if (activeFilter === 'huawei') matchesFilter = (row.dataset.brand || '').includes('huawei');
            if (activeFilter === 'dell') matchesFilter = (row.dataset.brand || '').includes('dell');
            if (activeFilter === 'noserial') matchesFilter = row.dataset.serial === '0';
            if (activeFilter === 'nofirmware') matchesFilter = row.dataset.firmware === '0';
            const show = matchesSearch && matchesFilter;
            row.style.display = show ? '' : 'none';
            if (detail) detail.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (empty) empty.style.display = visible === 0 && rows.length > 0 ? 'block' : 'none';
    }
    if (search) search.addEventListener('input', applyFilters);
    filters.forEach(btn => btn.addEventListener('click', () => { filters.forEach(b => b.classList.remove('active')); btn.classList.add('active'); activeFilter = btn.dataset.filter || 'all'; applyFilters(); }));
});

function startSyncWithProgress(nodeId = null, nodeName = null, clickedBtn = null) {
    const syncScope = nodeId ? ('solo el nodo ' + (nodeName || nodeId)) : 'todos los nodos habilitados';
    if (!confirm('Esto actualizará por SSH ' + syncScope + '. ¿Estás seguro?')) return;
    const progressBar = document.getElementById('syncProgressBar');
    const progressCard = document.getElementById('syncProgressCard');
    const progressIcon = document.getElementById('syncProgressIcon');
    const progressBarFill = document.getElementById('progressBarFill');
    const progressPercentage = document.getElementById('progressPercentage');
    const progressText = document.getElementById('progressText');
    const syncBtn = clickedBtn || document.getElementById('sshSyncBtn');
    const steps = {
        connect: document.getElementById('syncStepConnect'),
        hardware: document.getElementById('syncStepHardware'),
        network: document.getElementById('syncStepNetwork'),
        finish: document.getElementById('syncStepFinish')
    };

    function setProgress(value, label, activeStep) {
        const pct = Math.max(0, Math.min(100, Math.round(value)));
        progressBarFill.style.width = pct + '%';
        progressBarFill.setAttribute('aria-valuenow', pct);
        progressPercentage.textContent = pct + '%';
        if (label) progressText.textContent = label;
        Object.entries(steps).forEach(([key, el]) => {
            if (!el) return;
            el.classList.toggle('active', key === activeStep);
            const order = ['connect','hardware','network','finish'];
            el.classList.toggle('done', order.indexOf(key) < order.indexOf(activeStep));
        });
    }

    progressCard.classList.remove('success','danger');
    progressIcon.innerHTML = '<i class="bi bi-arrow-repeat"></i>';
    progressBar.style.display = 'block';
    syncBtn.disabled = true;
    syncBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Sincronizando...';

    let progress = 8;
    setProgress(progress, nodeId ? ('Conectando a ' + (nodeName || 'nodo') + '...') : 'Conectando a nodos Proxmox...', 'connect');

    const interval = setInterval(() => {
        if (progress < 88) {
            const increment = progress < 28 ? 4 : (progress < 58 ? 3 : 1.6);
            progress += increment;
            const label = progress < 30
                ? (nodeId ? ('Conectando a ' + (nodeName || 'nodo') + '...') : 'Conectando a nodos Proxmox...')
                : (progress < 60 ? 'Recopilando marca, modelo, serial y firmware...' : 'Procesando interfaces, ethtool, fibra/UTP y bus-info...');
            const step = progress < 30 ? 'connect' : (progress < 60 ? 'hardware' : 'network');
            setProgress(progress, label, step);
        }
    }, 650);

    fetch(nodeId ? ('{{ url('proxmox/inventory/sync') }}/' + nodeId) : '{{ route('proxmox.inventory.sync') }}', { method:'GET', headers:{ 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' } })
    .then(response => response.json())
    .then(data => {
        clearInterval(interval);
        progressCard.classList.add('success');
        progressIcon.innerHTML = '<i class="bi bi-check2-circle"></i>';
        const events = data.events || [];
        const details = events.slice(-8).map(e => `[${e.ts || ''}] ${e.level || ''}: ${e.message || ''}`).join('\n');
        const logInfo = data.log_path ? `\nLog: ${data.log_path}` : '';
        setProgress(100, (data.message || 'Sincronización completada.') + logInfo, 'finish');
        if (details) console.log('SSH sync log', events);
        setTimeout(() => { progressBar.style.display='none'; location.reload(); }, 3200);
    })
    .catch(error => {
        clearInterval(interval);
        console.error('Error:', error);
        progressCard.classList.add('danger');
        progressIcon.innerHTML = '<i class="bi bi-exclamation-triangle"></i>';
        setProgress(Math.max(progress, 12), 'Error en la sincronización. Revisá conectividad SSH/permisos.', 'connect');
        setTimeout(() => {
            progressBar.style.display='none';
            syncBtn.disabled=false;
            syncBtn.innerHTML = clickedBtn ? '<i class="bi bi-arrow-repeat"></i> Sync' : '<i class="fas fa-sync"></i> Sincronizar Hardware (SSH)';
            alert('❌ Error al sincronizar.');
        }, 2600);
    });
}
</script>
@endsection
