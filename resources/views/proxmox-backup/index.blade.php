@extends('layouts.app')
@section('content')
@php
    $fmtBytes = function($bytes) {
        $bytes = (float)($bytes ?? 0);
        foreach (['B','KB','MB','GB','TB','PB'] as $unit) {
            if ($bytes < 1024 || $unit === 'PB') return number_format($bytes, $unit === 'B' ? 0 : 2, ',', '.') . ' ' . $unit;
            $bytes /= 1024;
        }
    };
    $statusLabel = ['ok'=>'Replicado', 'missing_slave'=>'Sin réplica Slave', 'stale_slave'=>'Slave atrasado', 'slave_only'=>'Solo Slave', 'no_master'=>'Sin Master'];
    $manualDnsRows = collect($backupScheduleAudit['manual_dns_rows'] ?? []);
    $manualDnsSummary = collect($backupScheduleAudit['manual_dns_summary'] ?? []);
@endphp
<style>
    .page-hero{background:linear-gradient(135deg,#08131f,#123047 48%,#0f5132);border:1px solid #27445c;border-radius:18px;padding:1.3rem 1.4rem;color:#fff;box-shadow:0 18px 50px rgba(0,0,0,.35)}
    .page-title{font-weight:900;letter-spacing:-.03em;margin:0}.page-subtitle{color:#9fb3c7}.pbs-pill{display:inline-flex;align-items:center;gap:.35rem;border:1px solid #31506a;border-radius:999px;padding:.28rem .6rem;color:#bcd1e5;background:rgba(255,255,255,.05);font-size:.78rem;font-weight:800}.pbs-pill.master{border-color:#38bdf8;color:#9be7ff}.pbs-pill.slave{border-color:#a78bfa;color:#c4b5fd}.pbs-pill.ok{border-color:#22c55e;color:#86efac}.pbs-pill.err,.pbs-pill.missing_slave{border-color:#ef4444;color:#fca5a5}.pbs-pill.stale_slave{border-color:#f59e0b;color:#fcd34d}.pbs-pill.slave_only,.pbs-pill.no_master{border-color:#94a3b8;color:#cbd5e1}
    .metric-card,.modern-panel{background:linear-gradient(180deg,#111b27,#0d1620);border:1px solid #26394c;border-radius:16px;color:#e7f0f8;box-shadow:0 12px 34px rgba(0,0,0,.28)}.metric-card{padding:.72rem .8rem;min-height:82px}.metric-label{font-size:.66rem;color:#91a5b8;text-transform:uppercase;font-weight:900}.metric-value{font-size:1.28rem;font-weight:900;line-height:1.05}.metric-foot{font-size:.68rem;color:#7f93a8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.modern-panel{padding:1rem}.modern-table th{background:#0b1520;color:#9fb3c7;text-transform:uppercase;font-size:.72rem}.modern-table td{vertical-align:middle}.usage-track{height:10px;border-radius:999px;background:#07111b;overflow:hidden;border:1px solid #26394c}.usage-fill{height:100%;background:linear-gradient(90deg,#22c55e,#eab308,#ef4444)}.form-control,.form-select{background:#0b1520!important;color:#fff!important;border-color:#31465b!important}.worker-cell{min-width:260px;max-width:380px;white-space:normal!important;overflow-wrap:anywhere;word-break:break-word;color:#e7f0f8!important;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.74rem;font-weight:700}.worker-user{display:block;color:#8fa6ba;font-family:inherit;font-size:.65rem;font-weight:500;margin-top:.18rem}.vm-chip{font-family:monospace;background:#0b1520;border:1px solid #334155;border-radius:8px;padding:.2rem .45rem;color:#bfdbfe}.muted-small{font-size:.78rem;color:#94a3b8}
</style>
<div class="container-fluid py-4">
    @if (session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if (session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="page-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <div class="d-flex flex-wrap gap-2 mb-3"><span class="pbs-pill ok">● Proxmox Backup Server</span><span class="pbs-pill">Puerto API 8007</span><span class="pbs-pill">{{ $totalServers }} servidor(es)</span><span class="pbs-pill">{{ $backupsTotal }} backup(s)</span></div>
                <h1 class="page-title">Proxmox Backup</h1>
                <div class="page-subtitle">PBS master/slave, datastores, snapshots y comparación de réplica por VM.</div>
            </div>
            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#addPbsModal"><i class="bi bi-plus-circle"></i> Agregar PBS</button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl col-md-4"><div class="metric-card"><div class="metric-label">Servidores</div><div class="metric-value">{{ $totalServers }}</div><div class="metric-foot">{{ $onlineServers }} online</div></div></div>
        <div class="col-xl col-md-4"><div class="metric-card"><div class="metric-label">Datastores</div><div class="metric-value">{{ $datastores->count() }}</div><div class="metric-foot">Master/Slave</div></div></div>
        <div class="col-xl col-md-4"><div class="metric-card"><div class="metric-label">Backups VM</div><div class="metric-value">{{ $backupsTotal }}</div><div class="metric-foot">{{ $namespacesTotal ?? (($namespaceRows ?? collect())->count()) }} namespace(s)</div></div></div>
        <div class="col-xl col-md-4"><div class="metric-card"><div class="metric-label">Replicados</div><div class="metric-value">{{ $backupsReplicated }}</div><div class="metric-foot">Master y Slave OK</div></div></div>
        <div class="col-xl col-md-4"><div class="metric-card"><div class="metric-label">Sin Slave</div><div class="metric-value">{{ $backupsMissingSlave }}</div><div class="metric-foot">Solo Master</div></div></div>
        <div class="col-xl col-md-4"><div class="metric-card"><div class="metric-label">Sin horario backup</div><div class="metric-value">{{ ($vmsWithoutBackup ?? collect())->count() }}</div><div class="metric-foot">Jobs PVE</div></div></div>
        <div class="col-xl col-md-4"><div class="metric-card"><div class="metric-label">Retención &gt;4</div><div class="metric-value">{{ $retentionAlerts->count() }}</div><div class="metric-foot">Master o Slave excedido</div></div></div>
        <div class="col-xl col-md-4"><div class="metric-card"><div class="metric-label">Uso global</div><div class="metric-value">{{ number_format($usagePercent,2,',','.') }}%</div><div class="usage-track mt-2"><div class="usage-fill" style="width:{{ min($usagePercent,100) }}%"></div></div></div></div>
    </div>

    <div class="modern-panel mb-4">
        <h5 class="mb-3">Servidores PBS</h5>
        <div class="table-responsive"><table class="table table-dark table-hover table-bordered modern-table mb-0">
            <thead><tr><th>Rol</th><th>Nombre</th><th>Endpoint</th><th>Versión</th><th>Estado</th><th>Datastores</th><th>Uso</th><th>Última sync</th><th>Acciones</th></tr></thead>
            <tbody>
            @forelse($servers as $server)
                @php $total=$server->datastores->sum('total'); $used=$server->datastores->sum('used'); $pct=$total?round(($used/$total)*100,2):0; @endphp
                <tr><td><span class="pbs-pill {{ $server->role }}">{{ strtoupper($server->role) }}</span></td><td><strong>{{ $server->name }}</strong><div class="text-muted small">{{ $server->notes }}</div></td><td><span class="font-monospace">https://{{ $server->host }}:{{ $server->port }}</span></td><td>{{ $server->version ?? 'S/D' }}</td><td><span class="pbs-pill {{ $server->status === 'online' ? 'ok' : 'err' }}">{{ $server->status }}</span>@if($server->last_error)<div class="text-danger small mt-1">{{ Str::limit($server->last_error, 110) }}</div>@endif</td><td>{{ $server->datastores->count() }}</td><td><div class="small">{{ $fmtBytes($used) }} / {{ $fmtBytes($total) }}</div><div class="usage-track"><div class="usage-fill" style="width:{{ min($pct,100) }}%"></div></div><div class="small text-muted">{{ number_format($pct,2,',','.') }}%</div></td><td>{{ $server->last_sync_at ? $server->last_sync_at->format('d/m/Y H:i') : 'S/D' }}</td><td>
                        <div class="d-flex gap-1 flex-wrap">
                            <a class="btn btn-sm btn-success" href="{{ route('pbs.sync', $server) }}" onclick="return confirm('Sincronizar PBS {{ $server->name }} por API 8007?')"><i class="bi bi-arrow-repeat"></i> Sync</a>
                            <form method="POST" action="{{ route('pbs.destroy', $server) }}" onsubmit="return confirm('Eliminar la conexión PBS {{ $server->name }}? No borra nada en el PBS, solo la credencial guardada en la plataforma.');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Eliminar</button>
                            </form>
                        </div>
                    </td></tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">Sin servidores PBS. Agregá el master y el slave con token API.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="modern-panel mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3">
            <h5 class="mb-0 text-info"><i class="bi bi-lightbulb"></i> Acciones recomendadas</h5>
            <span class="pbs-pill">Solo informativo</span>
        </div>
        <div class="row g-2">
            @forelse(($recommendedActions ?? []) as $action)
                <div class="col-xl-4 col-md-6">
                    <div class="metric-card h-100">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="metric-label">{{ $action['title'] }}</div>
                                <div class="metric-foot mt-1">{{ $action['message'] }}</div>
                            </div>
                            <span class="pbs-pill {{ $action['level'] === 'danger' ? 'err' : ($action['level'] === 'warning' ? 'stale_slave' : 'ok') }}">{{ $action['count'] }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-muted">Sin recomendaciones críticas al momento.</div>
            @endforelse
        </div>
        <div class="muted-small mt-2">La plataforma no ejecuta acciones sobre Proxmox/PBS desde este panel; solo entrega sugerencias operativas.</div>
    </div>

    <div class="modern-panel mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3">
            <div>
                <h5 class="mb-0 text-danger"><i class="bi bi-shield-exclamation"></i> VMs encendidas sin horario de backup Proxmox</h5>
                <div class="muted-small">Solo VMs running. Incluye DNS. Cache: {{ $backupScheduleAudit['generated_at'] ?? 'S/D' }}</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <span class="pbs-pill err">{{ ($vmsWithoutBackup ?? collect())->count() }} VM(s)</span>
                <a class="btn btn-sm btn-success" href="{{ route('pbs.backup-schedule.export') }}"><i class="bi bi-file-earmark-excel"></i> Exportar Excel</a>
                <a class="btn btn-sm btn-outline-info" href="{{ route('pbs.backup-schedule.refresh') }}" onclick="return confirm('Actualizar auditoría de horarios de backup por API Proxmox?')"><i class="bi bi-arrow-repeat"></i> Actualizar auditoría</a>
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-md-6"><button type="button" class="metric-card cluster-filter-card w-100 text-start" style="padding:.75rem" data-cluster="__all"><div class="metric-label">Todos los clusters</div><div class="metric-value" style="font-size:1.35rem">{{ ($vmsWithoutBackup ?? collect())->count() }}</div><div class="metric-foot">Mostrar todas las VMs</div></button></div>
            @foreach(($backupScheduleSummary ?? collect())->filter(fn($item) => (int)($item['uncovered'] ?? 0) > 0)->sortByDesc('uncovered') as $item)
                <div class="col-xl-3 col-md-6"><button type="button" class="metric-card cluster-filter-card w-100 text-start" style="padding:.75rem" data-cluster="{{ $item['cluster'] }}">
                    <div class="metric-label">{{ $item['cluster'] }}</div>
                    @if(!empty($item['error']))
                        <div class="text-danger small">{{ Str::limit($item['error'], 90) }}</div>
                    @else
                        <div class="metric-value" style="font-size:1.35rem">{{ $item['uncovered'] }}</div>
                    @endif
                </button></div>
            @endforeach
        </div>
        <div class="muted-small mb-2" id="noBackupFilterLabel">Mostrando todos los clusters</div>
        <div class="table-responsive" style="max-height:520px;overflow:auto"><table class="table table-dark table-hover table-bordered modern-table mb-0" id="noBackupTable">
            <thead style="position:sticky;top:0;z-index:1"><tr><th>Cluster</th><th>VMID</th><th>VM</th><th>Nodo</th><th>Estado</th></tr></thead>
            <tbody>
            @forelse(($vmsWithoutBackup ?? collect()) as $vm)
                <tr data-cluster="{{ $vm['cluster'] }}"><td>{{ $vm['cluster'] }}</td><td class="text-center"><span class="vm-chip">{{ $vm['vmid'] }}</span></td><td><strong>{{ $vm['name'] ?: 'VM '.$vm['vmid'] }}</strong></td><td>{{ $vm['node'] }}</td><td><span class="pbs-pill {{ $vm['status'] === 'running' ? 'ok' : '' }}">{{ $vm['status'] }}</span></td></tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Sin VMs fuera de jobs de backup habilitados.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="modern-panel mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3">
            <div><h5 class="mb-0 text-warning"><i class="bi bi-hdd-network"></i> DNS con backup manual</h5><div class="muted-small">Excluidos del conteo principal: DNS Lumen, DNS CCP y Ufinet.</div></div>
            <span class="pbs-pill stale_slave">{{ $manualDnsRows->count() }} VM(s)</span>
        </div>
        <div class="row g-2 mb-3">
            @foreach($manualDnsSummary->sortByDesc('uncovered') as $item)<div class="col-xl-3 col-md-4"><div class="metric-card"><div class="metric-label">{{ $item['cluster'] }}</div><div class="metric-value">{{ $item['uncovered'] }}</div><div class="metric-foot">Backup manual</div></div></div>@endforeach
        </div>
        <div class="table-responsive" style="max-height:360px;overflow:auto"><table class="table table-dark table-hover table-bordered modern-table mb-0"><thead><tr><th>Cluster</th><th>VMID</th><th>VM</th><th>Nodo</th><th>Estado</th></tr></thead><tbody>
            @forelse($manualDnsRows as $vm)<tr><td>{{ $vm['cluster'] }}</td><td class="text-center"><span class="vm-chip">{{ $vm['vmid'] }}</span></td><td><strong>{{ $vm['name'] ?: 'VM '.$vm['vmid'] }}</strong></td><td>{{ $vm['node'] }}</td><td><span class="pbs-pill ok">{{ $vm['status'] }}</span></td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">Sin VMs DNS pendientes en esta auditoría.</td></tr>@endforelse
        </tbody></table></div>
    </div>

    <div class="modern-panel mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3">
            <h5 class="mb-0 text-warning"><i class="bi bi-exclamation-triangle"></i> Alertas de retención &gt; 4 snapshots</h5>
            <span class="pbs-pill stale_slave">{{ $retentionAlerts->count() }} alerta(s)</span>
        </div>
        <div class="table-responsive"><table class="table table-dark table-hover table-bordered modern-table mb-0">
            <thead><tr><th>ID</th><th>Máquina PBS</th><th>IP / SO inventario</th><th>Namespace</th><th>Master</th><th>Slave</th><th>Diferencia</th><th>Estado</th></tr></thead>
            <tbody>
            @forelse($retentionAlerts as $row)
                @php
                    $diffRetention = abs(($row->master_count ?? 0) - ($row->slave_count ?? 0));
                    $masterOver = ($row->master_count ?? 0) > 4;
                    $slaveOver = ($row->slave_count ?? 0) > 4;
                @endphp
                <tr>
                    <td class="text-center">@if($row->vmid)<span class="vm-chip">{{ $row->vmid }}</span>@else<span class="muted-small">-</span>@endif</td>
                    <td><strong>{{ $row->vm_name ?: '-' }}</strong><div class="muted-small">{{ $row->qemu_exact_match ? ($row->cluster_name ?: 'Sin cluster') : ('Sin match exacto · vínculo: ' . implode(', ', $row->mapped_cluster ?? [])) }}</div></td>
                    <td>@if($row->ip_address)<span class="vm-chip">{{ $row->ip_address }}</span>@else<span class="muted-small">-</span>@endif<div class="muted-small mt-1">{{ $row->os_name ?: '-' }}</div></td>
                    <td><span class="vm-chip">{{ optional($row->master ?: $row->slave)->namespace ?: 'root' }}</span></td>
                    <td><span class="pbs-pill {{ $masterOver ? 'stale_slave' : 'ok' }}">Master {{ $row->master_count ?? 0 }}</span><div class="muted-small mt-1">{{ $row->master ? $row->master->backup_time?->format('d/m/Y H:i') : '-' }}</div></td>
                    <td><span class="pbs-pill {{ $slaveOver ? 'stale_slave' : 'ok' }}">Slave {{ $row->slave_count ?? 0 }}</span><div class="muted-small mt-1">{{ $row->slave ? $row->slave->backup_time?->format('d/m/Y H:i') : '-' }}</div></td>
                    <td class="text-center"><span class="vm-chip">{{ $diffRetention }}</span></td>
                    <td><span class="pbs-pill {{ $row->replication_status }}">{{ $statusLabel[$row->replication_status] ?? $row->replication_status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">Sin alertas: ningún backup supera 4 snapshots en Master o Slave.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="modern-panel mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3">
            <div><h5 class="mb-0">Backups por VM agrupados por Namespace</h5><div class="muted-small">Vínculo namespace ↔ cluster tomado desde storages PBS habilitados en Proxmox.</div></div>
            <span class="pbs-pill">{{ ($namespaceRows ?? collect())->count() }} namespace(s)</span>
        </div>
        <div class="accordion" id="pbsNamespaceAccordion">
        @forelse(($namespaceRows ?? collect()) as $nsIndex => $group)
            @php $collapseId = 'nsCollapse' . md5($group->namespace); @endphp
            <div class="modern-panel mb-3" style="padding:.65rem;border-radius:14px;">
                <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center">
                    <button class="btn btn-link text-decoration-none text-start p-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                        <strong><i class="bi bi-folder2-open text-info"></i> {{ $group->namespace }}</strong>
                        <span class="muted-small ms-2">{{ $group->total }} backup(s)</span>
                    </button>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="pbs-pill ok">OK {{ $group->ok }}</span>
                        <span class="pbs-pill missing_slave">Sin Slave {{ $group->missing_slave }}</span>
                        <span class="pbs-pill stale_slave">Atrasados {{ $group->stale_slave }}</span>
                        <span class="pbs-pill stale_slave">Ret &gt;4 {{ $group->retention_alerts }}</span>
                    </div>
                </div>
                <div id="{{ $collapseId }}" class="collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#pbsNamespaceAccordion">
                    <div class="table-responsive mt-3"><table class="table table-dark table-hover table-bordered modern-table mb-0">
                        <thead><tr><th>ID</th><th>Máquina PBS</th><th>IP / SO inventario</th><th>Master</th><th>Slave</th><th>Master / Slave</th><th>Estado réplica</th></tr></thead>
                        <tbody>
                        @foreach($group->rows as $row)
                            <tr>
                                <td class="text-center">@if($row->vmid)<span class="vm-chip">{{ $row->vmid }}</span>@else<span class="muted-small">-</span>@endif</td>
                                <td><strong>{{ $row->vm_name ?: '-' }}</strong><div class="muted-small">{{ $row->qemu_exact_match ? ($row->cluster_name ?: 'Sin cluster') : ('Sin match exacto · vínculo: ' . implode(', ', $row->mapped_cluster ?? [])) }}</div></td>
                                <td>@if($row->ip_address)<span class="vm-chip">{{ $row->ip_address }}</span>@else<span class="muted-small">-</span>@endif<div class="muted-small mt-1">{{ $row->os_name ?: '-' }}</div></td>
                                <td>@if($row->master)<span class="pbs-pill master">{{ $row->master->server->name ?? 'Master' }}</span><div class="muted-small mt-1">{{ $row->master->datastore }} · {{ $row->master->backup_time?->format('d/m/Y H:i') }}</div>@else<span class="muted-small">-</span>@endif</td>
                                <td>@if($row->slave)<span class="pbs-pill slave">{{ $row->slave->server->name ?? 'Slave' }}</span><div class="muted-small mt-1">{{ $row->slave->datastore }} · {{ $row->slave->backup_time?->format('d/m/Y H:i') }}</div>@else<span class="muted-small">-</span>@endif</td>
                                <td class="text-center"><div><span class="pbs-pill master">Master {{ $row->master_count ?? 0 }}</span></div><div class="mt-1"><span class="pbs-pill slave">Slave {{ $row->slave_count ?? 0 }}</span></div></td>
                                <td><span class="pbs-pill {{ $row->replication_status }}">{{ $statusLabel[$row->replication_status] ?? $row->replication_status }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-4">Sin snapshots PBS. Sincronizá el Master y el Slave.</div>
        @endforelse
        </div>
    </div>


    <div class="row g-4">
        <div class="col-xl-7"><div class="modern-panel"><h5 class="mb-3">Datastores</h5><div class="table-responsive"><table class="table table-dark table-hover table-bordered modern-table mb-0"><thead><tr><th>PBS</th><th>Datastore</th><th>Path</th><th>Total</th><th>Usado</th><th>Libre</th><th>%</th></tr></thead><tbody>@forelse($datastores as $ds)<tr><td>{{ $ds->server->name ?? '' }}</td><td><strong>{{ $ds->name }}</strong></td><td class="small text-muted">{{ $ds->path ?? 'S/D' }}</td><td>{{ $fmtBytes($ds->total) }}</td><td>{{ $fmtBytes($ds->used) }}</td><td>{{ $fmtBytes($ds->available) }}</td><td><div class="usage-track"><div class="usage-fill" style="width:{{ min($ds->usage_percent ?? 0,100) }}%"></div></div><div class="small">{{ number_format($ds->usage_percent ?? 0,2,',','.') }}%</div></td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-4">Sin datastores sincronizados.</td></tr>@endforelse</tbody></table></div></div></div>
        <div class="col-xl-5"><div class="modern-panel"><div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3"><h5 class="mb-0 text-danger"><i class="bi bi-exclamation-triangle"></i> Últimas 10 tareas con error</h5><span class="pbs-pill err">{{ $recentTasks->count() }} error(es)</span></div>
            <div class="table-responsive"><table class="table table-dark table-sm table-hover table-bordered modern-table mb-0"><thead><tr><th>PBS</th><th>Tipo</th><th style="min-width:260px">Worker</th><th>Estado</th><th>Inicio</th></tr></thead><tbody id="pbsTaskBody">@forelse($recentTasks as $task)@php $st=strtolower((string)$task->status); $ty=strtolower((string)$task->type); $worker=(string)($task->worker_id ?? ''); if ($worker === '' && !empty($task->upid)) { $parts=explode(':', (string)$task->upid); $worker=$parts[7] ?? ''; } $worker=str_replace(['\\x2d','\\x3a'], ['-',':'], $worker); @endphp<tr data-status="error" data-type="{{ $ty }}"><td>{{ $task->server->name ?? '' }}</td><td>{{ $task->type ?? 'S/D' }}</td><td class="worker-cell">{{ $worker !== '' ? $worker : 'No informado por PBS' }}@if($task->user)<span class="worker-user">Usuario: {{ $task->user }}</span>@endif</td><td><span class="pbs-pill err">{{ $task->status ?? 'error' }}</span></td><td>{{ $task->starttime ? $task->starttime->format('d/m H:i') : 'S/D' }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">Sin tareas con error.</td></tr>@endforelse</tbody></table></div></div></div>
    </div>
</div>

<div class="modal fade" id="addPbsModal" tabindex="-1" data-bs-theme="dark"><div class="modal-dialog modal-lg"><div class="modal-content" style="background:#0f1722;color:#fff;border:1px solid #31465b"><div class="modal-header border-secondary"><h5 class="modal-title">Agregar Proxmox Backup Server</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><form method="POST" action="{{ route('pbs.store') }}">@csrf<div class="modal-body"><div class="row g-3"><div class="col-md-6"><label class="form-label">Nombre</label><input name="name" class="form-control" required placeholder="PBS Master"></div><div class="col-md-3"><label class="form-label">Rol</label><select name="role" class="form-select"><option value="master">Master</option><option value="slave">Slave</option></select></div><div class="col-md-3"><label class="form-label">Puerto API</label><input name="port" type="number" class="form-control" value="8007" required></div><div class="col-md-6"><label class="form-label">Host/IP</label><input name="host" class="form-control" required placeholder="192.168.100.x"></div><div class="col-md-6"><label class="form-label">Usuario API</label><input name="api_user" class="form-control" required placeholder="usuario@pbs"></div><div class="col-md-6"><label class="form-label">Token ID</label><input name="api_token_id" class="form-control" required placeholder="Inventario"></div><div class="col-md-6"><label class="form-label">Token Secret</label><input name="api_token_secret" type="password" class="form-control" required></div><div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="verify_tls" value="1" id="verifyTls"><label class="form-check-label" for="verifyTls">Verificar certificado TLS</label></div><small class="text-muted">Por defecto queda desactivado para certificados self-signed internos.</small></div><div class="col-12"><label class="form-label">Notas</label><textarea name="notes" class="form-control" rows="2"></textarea></div></div></div><div class="modal-footer border-secondary"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-info">Guardar y sincronizar</button></div></form></div></div></div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('noBackupTable');
    const label = document.getElementById('noBackupFilterLabel');
    if (!table) return;
    document.querySelectorAll('.cluster-filter-card').forEach(function (card) {
        card.addEventListener('click', function () {
            const cluster = card.dataset.cluster; let visible = 0;
            table.querySelectorAll('tbody tr[data-cluster]').forEach(function (row) {
                const show = cluster === '__all' || row.dataset.cluster === cluster; row.style.display = show ? '' : 'none'; if (show) visible++;
            });
            label.textContent = cluster === '__all' ? 'Mostrando todos los clusters' : 'Filtro activo: ' + cluster + ' · ' + visible + ' VM(s)';
        });
    });
});
</script>

@endsection
