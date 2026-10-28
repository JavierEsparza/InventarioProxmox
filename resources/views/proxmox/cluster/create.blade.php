@extends('layouts.app')

@section('content')
<style>
    .page-hero { background:linear-gradient(135deg,#101820 0%,#172330 58%,#0f5132 155%); border:1px solid #2f3b49; border-radius:18px; padding:1.25rem; color:#fff; box-shadow:0 16px 35px rgba(0,0,0,.28); }
    .page-title { font-size:clamp(1.75rem,2.8vw,2.55rem); font-weight:850; letter-spacing:-.03em; margin:0; }
    .page-subtitle { color:#aab7c4; font-size:.95rem; margin-top:.4rem; }
    .cluster-pill { display:inline-flex; align-items:center; gap:.35rem; border:1px solid #344455; background:rgba(255,255,255,.06); color:#d7e2ec; border-radius:999px; padding:.35rem .65rem; font-size:.82rem; font-weight:750; }
    .cluster-pill.ok { border-color:rgba(25,135,84,.65); background:rgba(25,135,84,.18); color:#91e7b4; }
    .cluster-pill.warn { border-color:rgba(255,193,7,.62); background:rgba(255,193,7,.14); color:#ffe08a; }
    .cluster-pill.info { border-color:rgba(13,202,240,.55); background:rgba(13,202,240,.12); color:#9beaf7; }
    .modern-panel { background:#0f1821; border:1px solid #1f3345; border-radius:18px; padding:1rem; color:#fff; box-shadow:0 12px 28px rgba(0,0,0,.18); }
    .form-panel { background:#111b25; border:1px solid #26384a; border-radius:18px; color:#fff; overflow:hidden; box-shadow:0 18px 40px rgba(0,0,0,.28); }
    .form-panel-header { background:linear-gradient(135deg,#142231,#1b3243); border-bottom:1px solid #2a3e52; padding:1rem 1.15rem; }
    .form-label { color:#cdd8e3; font-weight:800; font-size:.84rem; letter-spacing:.02em; }
    .form-hint { color:#8fa0b2; font-size:.8rem; margin-top:.35rem; }
    .form-control-modern { background:#0b141d !important; border:1px solid #31465a !important; color:#e8f0f8 !important; border-radius:12px !important; padding:.72rem .85rem !important; }
    .form-control-modern:focus { border-color:#1cc88a !important; box-shadow:0 0 0 .2rem rgba(28,200,138,.15) !important; }
    .input-group-text-modern { background:#0b141d !important; border:1px solid #31465a !important; color:#9fb0c2 !important; border-radius:12px 0 0 12px !important; }
    .input-icon-control { border-left:0 !important; border-radius:0 12px 12px 0 !important; }
    .step-card { background:#0d1720; border:1px solid #26384a; border-radius:16px; padding:1rem; height:100%; }
    .step-number { width:34px; height:34px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; background:rgba(28,200,138,.18); color:#91e7b4; border:1px solid rgba(28,200,138,.5); font-weight:900; margin-bottom:.75rem; }
    .step-title { font-weight:850; color:#fff; margin-bottom:.35rem; }
    .step-text { color:#95a6b8; font-size:.86rem; margin:0; }
    .notice-box { background:rgba(13,202,240,.08); border:1px solid rgba(13,202,240,.45); color:#b9f2fb; border-radius:16px; padding:1rem; }
    .notice-box code { color:#8ee8bf; background:rgba(0,0,0,.24); padding:.08rem .35rem; border-radius:6px; }
    .action-card { background:#0d1720; border:1px solid #26384a; border-radius:16px; padding:1rem; }
    .btn-modern-primary { background:linear-gradient(135deg,#0d6efd,#0aa36d); border:0; border-radius:12px; font-weight:850; padding:.82rem 1rem; box-shadow:0 12px 24px rgba(13,110,253,.22); }
    .btn-modern-primary:hover { filter:brightness(1.08); transform:translateY(-1px); }
    .btn-modern-secondary { border:1px solid #3a4d61; color:#d8e2ec; background:rgba(255,255,255,.03); border-radius:12px; font-weight:800; padding:.72rem 1rem; }
    .btn-modern-secondary:hover { color:#fff; background:rgba(255,255,255,.08); }
    .small-kpi { display:flex; align-items:center; gap:.6rem; color:#aebccd; font-size:.86rem; }
    .small-kpi i { color:#1cc88a; }
    .metric-label { color:#96a6b5; font-size:.74rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
    .id-chip { font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; background:#07111a; border:1px solid #25384b; color:#b8e7ff; padding:.32rem .5rem; border-radius:8px; font-size:.82rem; display:inline-block; }
    .guide-table { --bs-table-bg:#0f1821; --bs-table-border-color:#26384a; --bs-table-color:#dbe6f1; margin-bottom:0; }
    .guide-table th { color:#91a2b3; text-transform:uppercase; font-size:.72rem; letter-spacing:.06em; background:#0b141d !important; }
    .guide-table td { vertical-align:middle; }
    .guide-list { color:#aebccd; margin-bottom:0; padding-left:1.2rem; }
    .guide-list li { margin-bottom:.45rem; }

</style>

<div class="container-fluid px-4 py-4">
    <div class="page-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="cluster-pill ok">● Alta de cluster</span>
                    <span class="cluster-pill info">API Token</span>
                    <span class="cluster-pill warn">usuario read-only</span>
                </div>
                <h1 class="page-title">Conectar nuevo cluster</h1>
                <div class="page-subtitle">Primero preparar usuario/rol/token en Proxmox; después pegar nodo, Token ID y Secret en el formulario.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('proxmox.cluster') }}" class="btn btn-outline-light"><i class="bi bi-diagram-3 me-1"></i> Volver a clusters</a>
                <a href="{{ route('proxmox.index') }}" class="btn btn-outline-light"><i class="bi bi-speedometer2 me-1"></i> Resumen</a>
            </div>
        </div>
    </div>

    <div class="row g-4 align-items-start">
        {{-- Columna izquierda: guía operativa --}}
        <div class="col-xl-5 col-lg-5">
            <div class="modern-panel mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h5 class="mb-1"><i class="bi bi-shield-check me-2 text-success"></i>Preparar usuario y token en Proxmox</h5>
                        <div class="page-subtitle mt-0">Configuración read-only recomendada para inventario.</div>
                    </div>
                    <span class="cluster-pill ok">Permisos read-only</span>
                </div>

                <div class="d-grid gap-3 mb-3">
                    <div class="step-card"><div class="d-flex gap-3"><div class="step-number mb-0">1</div><div><div class="step-title">Crear usuario</div><p class="step-text">Datacenter → Permissions → Users → Add. Usuario: <code>monitoreoTI</code>, Realm: <code>pam</code>, Enabled: sí.</p></div></div></div>
                    <div class="step-card"><div class="d-flex gap-3"><div class="step-number mb-0">2</div><div><div class="step-title">Crear grupo</div><p class="step-text">Datacenter → Permissions → Groups → Create. Grupo: <code>Read_Only</code>. Asociar <code>monitoreoTI@pam</code> al grupo.</p></div></div></div>
                    <div class="step-card"><div class="d-flex gap-3"><div class="step-number mb-0">3</div><div><div class="step-title">Crear rol</div><p class="step-text">Datacenter → Permissions → Roles → Create. Rol: <code>Monitoreo</code>.</p><div class="mt-2 d-flex flex-wrap gap-1"><span class="id-chip">Datastore.Audit</span><span class="id-chip">Sys.Audit</span><span class="id-chip">VM.Audit</span><span class="id-chip">VM.Monitor</span></div></div></div></div>
                    <div class="step-card"><div class="d-flex gap-3"><div class="step-number mb-0">4</div><div><div class="step-title">Asignar permiso y crear token</div><p class="step-text">Permissions → Add → Group Permission. Path: <code>/</code>, Group: <code>Read_Only</code>, Role: <code>Monitoreo</code>. Luego API Tokens → Add.</p></div></div></div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-dark table-bordered guide-table align-middle">
                        <tbody>
                            <tr><th>Usuario</th><td><span class="id-chip">monitoreoTI@pam</span></td></tr>
                            <tr><th>Grupo</th><td><span class="id-chip">Read_Only</span></td></tr>
                            <tr><th>Rol</th><td><span class="id-chip">Monitoreo</span></td></tr>
                            <tr><th>ACL</th><td>Path <code>/</code> · Group <code>Read_Only</code> · Role <code>Monitoreo</code></td></tr>
                            <tr><th>Token ID</th><td><span class="id-chip">monitoreoTI@pam!InventarioInfra</span></td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="notice-box">
                    <div class="fw-bold mb-2"><i class="bi bi-terminal me-1"></i> Para pegar en el formulario</div>
                    <ul class="guide-list">
                        <li><strong>Token ID completo:</strong> <code>monitoreoTI@pam!InventarioInfra</code></li>
                        <li><strong>Token Secret:</strong> valor generado por Proxmox al crear el token.</li>
                        <li><strong>Privilege Separation:</strong> desmarcado.</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Columna derecha: formulario --}}
        <div class="col-xl-7 col-lg-7">
            <div class="form-panel mb-4">
                <div class="form-panel-header">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <h4 class="mb-1"><i class="bi bi-plug-fill me-2 text-success"></i>Datos de conexión</h4>
                            <div class="page-subtitle mt-0">Nodo destino + Token ID completo + Secret del token.</div>
                        </div>
                        <span class="cluster-pill ok">Puerto 8006</span>
                    </div>
                </div>
                <div class="p-3 p-md-4">
                    @if (isset($errors) && $errors->any())
                        <div class="alert alert-danger border-danger bg-dark text-danger-subtle">
                            <strong><i class="bi bi-exclamation-triangle me-1"></i> Revisá estos campos:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('proxmox.cluster.store') }}" method="POST" id="clusterCreateForm">
                        @csrf

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="ip" class="form-label">IP o hostname de nodo</label>
                                <div class="input-group">
                                    <span class="input-group-text input-group-text-modern"><i class="bi bi-hdd-network"></i></span>
                                    <input type="text" class="form-control form-control-modern input-icon-control" id="ip" name="ip" placeholder="Ej: 192.168.1.50" required value="{{ old('ip') }}" autocomplete="off">
                                </div>
                                <div class="form-hint">Cualquier nodo online del cluster.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="token_id" class="form-label">Token ID completo</label>
                                <div class="input-group">
                                    <span class="input-group-text input-group-text-modern"><i class="bi bi-person-badge"></i></span>
                                    <input type="text" class="form-control form-control-modern input-icon-control" id="token_id" name="token_id" placeholder="monitoreoTI@pam!InventarioInfra" required value="{{ old('token_id') }}" autocomplete="off">
                                </div>
                                <div class="form-hint"><code>monitoreoTI@pam!InventarioInfra</code></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="token_secret" class="form-label">Token Secret</label>
                            <div class="input-group">
                                <span class="input-group-text input-group-text-modern"><i class="bi bi-key"></i></span>
                                <input type="password" class="form-control form-control-modern input-icon-control" id="token_secret" name="token_secret" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" required autocomplete="new-password">
                                <button class="btn btn-modern-secondary" type="button" id="toggleSecret" title="Mostrar/Ocultar"><i class="bi bi-eye"></i></button>
                            </div>
                            <div class="form-hint">El Secret se muestra una sola vez al crear el token.</div>
                        </div>

                        <div class="notice-box mb-4">
                            <div class="fw-bold mb-2"><i class="bi bi-info-circle me-1"></i> Lo que hará la aplicación</div>
                            <div class="row g-2">
                                <div class="col-md-4"><div class="small-kpi"><i class="bi bi-search"></i><span>Consulta API Proxmox</span></div></div>
                                <div class="col-md-4"><div class="small-kpi"><i class="bi bi-diagram-3"></i><span>Detecta cluster/nodos</span></div></div>
                                <div class="col-md-4"><div class="small-kpi"><i class="bi bi-arrow-repeat"></i><span>Sincroniza inventario</span></div></div>
                            </div>
                        </div>

                        <div class="action-card">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div>
                                    <div class="fw-bold text-white"><i class="bi bi-arrow-repeat me-1 text-success"></i> Conectar y sincronizar</div>
                                    <div class="form-hint mb-0">La primera sincronización puede tardar algunos minutos.</div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('proxmox.cluster') }}" class="btn btn-modern-secondary"><i class="bi bi-x-circle me-1"></i> Cancelar</a>
                                    <button type="submit" class="btn btn-primary btn-modern-primary" id="submitBtn"><i class="bi bi-plug-fill me-1"></i> Conectar</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-7">
                    <div class="modern-panel h-100">
                        <h5 class="mb-3"><i class="bi bi-list-check me-2 text-success"></i>Checklist previo</h5>
                        <div class="d-grid gap-3">
                            <div class="small-kpi"><i class="bi bi-check-circle-fill"></i><span>El nodo responde por HTTPS en 8006.</span></div>
                            <div class="small-kpi"><i class="bi bi-check-circle-fill"></i><span>El Token ID incluye usuario, realm y nombre del token.</span></div>
                            <div class="small-kpi"><i class="bi bi-check-circle-fill"></i><span>El grupo <code>Read_Only</code> tiene rol <code>Monitoreo</code> en path <code>/</code>.</span></div>
                            <div class="small-kpi"><i class="bi bi-check-circle-fill"></i><span>Privilege Separation está desmarcado.</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="modern-panel h-100">
                        <h5 class="mb-3"><i class="bi bi-terminal me-2 text-info"></i>Formato esperado</h5>
                        <div class="mb-3"><div class="metric-label">Nodo</div><div class="id-chip mt-1">192.168.1.50</div></div>
                        <div class="mb-3"><div class="metric-label">Token ID</div><div class="id-chip mt-1">monitoreoTI@pam!InventarioInfra</div></div>
                        <div><div class="metric-label">Secret</div><div class="id-chip mt-1">UUID del token</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('toggleSecret');
    const secret = document.getElementById('token_secret');
    const form = document.getElementById('clusterCreateForm');
    const submit = document.getElementById('submitBtn');

    if (toggle && secret) {
        toggle.addEventListener('click', function () {
            secret.type = secret.type === 'password' ? 'text' : 'password';
            toggle.innerHTML = secret.type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });
    }

    if (form && submit) {
        form.addEventListener('submit', function () {
            submit.disabled = true;
            submit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Conectando...';
        });
    }
});
</script>
@endsection
