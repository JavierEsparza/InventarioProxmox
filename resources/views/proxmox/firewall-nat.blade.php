@extends('layouts.app')

@section('content')
<div class="container-fluid mt-3 px-4">
    <div class="page-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="cluster-pill success"><i class="bi bi-diagram-3"></i> WatchGuard M570</span>
                    <span class="cluster-pill">{{ $summary['total'] }} publicaciones NAT</span>
                    <span class="cluster-pill">{{ $summary['unmatched'] }} sin VM asociada</span>
                </div>
                <h1 class="page-title">VMs publicadas por NAT</h1>
                <div class="page-subtitle">Relación entre inventario Proxmox y publicaciones DNAT del firewall.</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6"><div class="metric-card"><div class="metric-label">Publicaciones</div><div class="metric-value">{{ $summary['total'] }}</div><div class="metric-foot">Reglas NAT importadas desde XML</div></div></div>
        <div class="col-xl-3 col-md-6"><div class="metric-card"><div class="metric-label">Habilitadas</div><div class="metric-value text-success">{{ $summary['enabled'] }}</div><div class="metric-foot">Reglas activas en el firewall</div></div></div>
        <div class="col-xl-3 col-md-6"><div class="metric-card"><div class="metric-label">Deshabilitadas</div><div class="metric-value text-warning">{{ $summary['disabled'] }}</div><div class="metric-foot">Requieren revisión</div></div></div>
        <div class="col-xl-3 col-md-6"><div class="metric-card"><div class="metric-label">Sin asociación</div><div class="metric-value {{ $summary['unmatched'] ? 'text-warning' : 'text-success' }}">{{ $summary['unmatched'] }}</div><div class="metric-foot">IP NAT no encontrada en VMs</div></div></div>
    </div>

    <div class="modern-panel">
        <div class="inventory-toolbar">
            <form method="GET" class="d-flex gap-2 flex-wrap">
                <input type="search" name="search" value="{{ request('search') }}" class="inventory-search" placeholder="Buscar VM, ID, cluster, IP o regla...">
                <select name="enabled" class="inventory-filter">
                    <option value="">Todos los estados</option>
                    <option value="1" @selected(request('enabled') === '1')>Habilitadas</option>
                    <option value="0" @selected(request('enabled') === '0')>Deshabilitadas</option>
                </select>
                <select name="geolocation" class="inventory-filter">
                    <option value="">Toda geolocalización</option>
                    @foreach($geolocations as $geo)<option value="{{ $geo }}" @selected(request('geolocation') === $geo)>{{ $geo }}</option>@endforeach
                </select>
                <button class="btn btn-outline-info action-btn" type="submit"><i class="bi bi-search"></i> Buscar</button>
                @if(request()->hasAny(['search','enabled','geolocation']))<a class="btn btn-outline-light action-btn" href="{{ route('proxmox.firewall.nat') }}">Limpiar</a>@endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-hover table-bordered align-middle modern-table mb-0">
                <thead class="text-center"><tr><th>ID VM</th><th>Nombre VM</th><th>Cluster VM</th><th>IP VM</th><th>IP pública NAT</th><th>Geolocalización</th><th>Nombre regla</th><th>Estado</th></tr></thead>
                <tbody>
                @forelse($rules as $rule)
                    @php $vmIp = $rule->vm_ip_manual ?: $rule->vm_ip; @endphp
                    <tr>
                        <td class="text-center"><span class="badge-soft blue">{{ $rule->vm_id ?: '—' }}</span></td>
                        <td><div class="node-name-main">{{ $rule->vm_name ?: 'Sin VM asociada' }}</div>@if(!$rule->vm_id)<div class="node-name-sub">IP NAT: {{ $rule->private_ip ?: '—' }}</div>@endif</td>
                        <td class="table-small-text">{{ $rule->vm_cluster ?: '—' }}</td>
                        <td><code>{{ $vmIp ?: $rule->private_ip ?: '—' }}</code></td>
                        <td><code>{{ $rule->public_ip ?: '—' }}{{ $rule->public_port ? ':' . $rule->public_port : '' }}</code></td>
                        <td class="table-small-text">{{ $rule->geolocation ?: '—' }}</td>
                        <td title="{{ $rule->policy_name }}"><span class="node-name-main">{{ $rule->policy_name }}</span></td>
                        <td class="text-center"><span class="badge-soft {{ $rule->enabled ? 'running' : 'off' }}">{{ $rule->enabled ? 'HABILITADA' : 'DESHABILITADA' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-secondary py-4">No hay resultados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($rules->hasPages())<div class="mt-3">{{ $rules->links() }}</div>@endif
    </div>
</div>
<style>
    .page-hero{background:linear-gradient(135deg,#101820 0%,#172330 58%,#0f5132 155%);border:1px solid #2f3b49;border-radius:18px;padding:1.25rem;color:#fff;box-shadow:0 16px 35px rgba(0,0,0,.28)}
    .page-title{font-size:clamp(1.7rem,2.6vw,2.45rem);font-weight:850;letter-spacing:-.03em;margin:0}.page-subtitle{color:#aab7c4;font-size:.95rem;margin-top:.4rem}
    .cluster-pill{display:inline-flex;align-items:center;gap:.35rem;border:1px solid #344455;background:rgba(255,255,255,.06);color:#d7e2ec;border-radius:999px;padding:.35rem .65rem;font-size:.82rem;font-weight:750}.cluster-pill.success{border-color:rgba(25,135,84,.6);background:rgba(25,135,84,.18);color:#91e7b4}
    .metric-card{background:#151d26;border:1px solid #2b3846;border-radius:16px;padding:1rem;color:#fff;height:100%;box-shadow:inset 0 1px 0 rgba(255,255,255,.03)}.metric-label{color:#96a6b5;font-size:.74rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em}.metric-value{font-size:2rem;font-weight:850;line-height:1;margin-top:.35rem}.metric-foot{color:#8d9baa;font-size:.82rem;margin-top:.5rem}
    .modern-panel{background:#0f1821;border:1px solid #1f3345;border-radius:18px;padding:1rem;color:#fff;box-shadow:0 12px 28px rgba(0,0,0,.18)}.inventory-toolbar{display:flex;flex-wrap:wrap;justify-content:space-between;gap:.75rem;margin-bottom:1rem}.inventory-search{background:#0d141c;border:1px solid #324254;color:#e8eef5;border-radius:12px;padding:.65rem .85rem;min-width:340px}.inventory-search::placeholder{color:#7f8d9a}.inventory-filter{border:1px solid #344455;background:rgba(255,255,255,.04);color:#dce6ef;border-radius:999px;padding:.45rem .8rem;font-weight:750;font-size:.84rem}.inventory-filter option{background:#111b25;color:#e8eef5}
    .modern-table{--bs-table-bg:#0f1821;--bs-table-border-color:#223346;--bs-table-color:#e5edf4;border-radius:14px;overflow:hidden}.modern-table thead th{background:#0c151d!important;color:#8fa3b7;font-size:.72rem;text-transform:uppercase;letter-spacing:.07em;border-bottom:1px solid #344455;white-space:nowrap}.modern-table tbody td{background:#0f1821!important;border-color:#223346;vertical-align:middle}.modern-table tbody tr:nth-child(even) td{background:#101a24!important}.modern-table tbody tr:hover td{background:#142536!important}.node-name-main{font-weight:850;color:#fff}.node-name-sub{color:#8c9baa;font-size:.78rem}.badge-soft{border:1px solid #344455;background:rgba(255,255,255,.06);color:#dbe5ee;border-radius:999px;padding:.3rem .55rem;font-size:.74rem;font-weight:800}.badge-soft.running{border-color:rgba(25,135,84,.65);background:rgba(25,135,84,.18);color:#91e7b4}.badge-soft.off{border-color:rgba(108,117,125,.65);background:rgba(108,117,125,.18);color:#c9d0d6}.badge-soft.blue{border-color:rgba(13,110,253,.65);background:rgba(13,110,253,.18);color:#99c2ff}.action-btn{border-radius:10px;font-weight:750}.table-small-text{font-size:.78rem;color:#a7b4c1}.firewall-nat-page code,code{color:#91e7b4;font-weight:700}
    @media(max-width:900px){.inventory-search{min-width:min(100%,280px)}.modern-table{font-size:.8rem}}
</style>
@endsection
