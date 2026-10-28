@extends('layouts.app')

@section('content')
<style>
    .login-shell { min-height: calc(100vh - 150px); display:flex; align-items:center; justify-content:center; padding:1.5rem 0 2.5rem; }
    .login-wrap { width:100%; max-width:1120px; }
    .login-hero { background:linear-gradient(135deg,#101820 0%,#172330 58%,#0f5132 155%); border:1px solid #2f3b49; border-radius:24px; color:#fff; box-shadow:0 22px 55px rgba(0,0,0,.35); overflow:hidden; }
    .login-left { padding:2rem; height:100%; position:relative; background:radial-gradient(circle at 15% 15%, rgba(28,200,138,.22), transparent 32%), radial-gradient(circle at 90% 80%, rgba(56,189,248,.13), transparent 28%); }
    .login-logo { height:58px; width:auto; filter:drop-shadow(0 8px 18px rgba(0,0,0,.28)); margin-bottom:1.35rem; }
    .login-title { font-size:clamp(2rem,3vw,3rem); font-weight:900; letter-spacing:-.04em; line-height:1; margin:0; }
    .login-subtitle { color:#b6c4d4; max-width:520px; margin-top:.8rem; font-size:.98rem; }
    .cluster-pill { display:inline-flex; align-items:center; gap:.35rem; border:1px solid #344455; background:rgba(255,255,255,.06); color:#d7e2ec; border-radius:999px; padding:.35rem .65rem; font-size:.82rem; font-weight:750; }
    .cluster-pill.ok { border-color:rgba(25,135,84,.65); background:rgba(25,135,84,.18); color:#91e7b4; }
    .cluster-pill.info { border-color:rgba(13,202,240,.55); background:rgba(13,202,240,.12); color:#9beaf7; }
    .login-feature { display:flex; align-items:center; gap:.7rem; color:#c8d5e2; font-size:.9rem; margin-bottom:.8rem; }
    .login-feature i { color:#1cc88a; font-size:1.05rem; }
    .login-panel { background:#0f1821; border-left:1px solid #24384b; padding:2rem; height:100%; }
    .login-card { background:#111b25; border:1px solid #26384a; border-radius:18px; padding:1.25rem; box-shadow:inset 0 1px 0 rgba(255,255,255,.03); }
    .form-label { color:#cdd8e3; font-weight:800; font-size:.84rem; letter-spacing:.02em; }
    .form-hint { color:#8fa0b2; font-size:.8rem; margin-top:.35rem; }
    .form-control-modern { background:#0b141d !important; border:1px solid #31465a !important; color:#e8f0f8 !important; border-radius:12px !important; padding:.76rem .85rem !important; }
    .form-control-modern:focus { border-color:#1cc88a !important; box-shadow:0 0 0 .2rem rgba(28,200,138,.15) !important; }
    .input-group-text-modern { background:#0b141d !important; border:1px solid #31465a !important; color:#9fb0c2 !important; border-radius:12px 0 0 12px !important; }
    .input-icon-control { border-left:0 !important; border-radius:0 12px 12px 0 !important; }
    .btn-modern-primary { background:linear-gradient(135deg,#0d6efd,#0aa36d); border:0; border-radius:12px; font-weight:850; padding:.82rem 1rem; box-shadow:0 12px 24px rgba(13,110,253,.22); color:#fff; }
    .btn-modern-primary:hover { filter:brightness(1.08); transform:translateY(-1px); color:#fff; }
    .btn-modern-secondary { border:1px solid #3a4d61; color:#d8e2ec; background:rgba(255,255,255,.03); border-radius:12px; font-weight:800; padding:.72rem 1rem; }
    .btn-modern-secondary:hover { color:#fff; background:rgba(255,255,255,.08); }
    .login-footer-note { color:#718196; font-size:.78rem; margin-top:1rem; text-align:center; }
    .invalid-feedback strong { color:#ff9ca8; }
    .form-check-label { color:#b6c4d4; }
    @media (max-width: 991.98px) {
        .login-shell { align-items:flex-start; }
        .login-panel { border-left:0; border-top:1px solid #24384b; }
        .login-left, .login-panel { padding:1.35rem; }
    }
</style>

<div class="container-fluid px-4">
    <div class="login-shell">
        <div class="login-wrap">
            <div class="login-hero">
                <div class="row g-0">
                    <div class="col-lg-6">
                        <div class="login-left">
                            <img src="{{ asset('holdco.png') }}" alt="Holdco Networks" class="login-logo">
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="cluster-pill ok">● Inventario Infra</span>
                                <span class="cluster-pill info">Proxmox VE</span>
                            </div>
                            <h1 class="login-title">Acceso operativo</h1>
                            <div class="login-subtitle">Dashboard centralizado para revisar clusters, nodos, VMs, storage, históricos y eliminados.</div>

                            <div class="mt-4 pt-2">
                                <div class="login-feature"><i class="bi bi-diagram-3-fill"></i><span>Inventario de clusters y nodos.</span></div>
                                <div class="login-feature"><i class="bi bi-pc-display"></i><span>Estado de VMs, agentes y recursos.</span></div>
                                <div class="login-feature"><i class="bi bi-clock-history"></i><span>Históricos mensuales y servidores eliminados.</span></div>
                                <div class="login-feature"><i class="bi bi-shield-check"></i><span>Acceso autenticado por usuario.</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="login-panel d-flex align-items-center">
                            <div class="login-card w-100">
                                <div class="mb-4">
                                    <h4 class="mb-1"><i class="bi bi-box-arrow-in-right me-2 text-success"></i>Iniciar sesión</h4>
                                    <div class="form-hint">Ingresá con tu cuenta autorizada para Inventario Infra.</div>
                                </div>

                                <form method="POST" action="{{ route('login') }}" id="loginForm">
                                    @csrf

                                    <div class="mb-3">
                                        <label for="email" class="form-label">Correo electrónico</label>
                                        <div class="input-group">
                                            <span class="input-group-text input-group-text-modern"><i class="bi bi-envelope"></i></span>
                                            <input id="email" type="email" class="form-control form-control-modern input-icon-control {{ (isset($errors) && $errors->has('email')) ? 'is-invalid' : '' }}" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="usuario@holdconet.cl">
                                            @if (isset($errors) && $errors->has('email'))
                                                <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('email') }}</strong></span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="password" class="form-label">Contraseña</label>
                                        <div class="input-group">
                                            <span class="input-group-text input-group-text-modern"><i class="bi bi-key"></i></span>
                                            <input id="password" type="password" class="form-control form-control-modern input-icon-control {{ (isset($errors) && $errors->has('password')) ? 'is-invalid' : '' }}" name="password" required autocomplete="current-password" placeholder="••••••••">
                                            <button class="btn btn-modern-secondary" type="button" id="togglePassword" title="Mostrar/Ocultar"><i class="bi bi-eye"></i></button>
                                            @if (isset($errors) && $errors->has('password'))
                                                <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('password') }}</strong></span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="remember">Recordar sesión</label>
                                        </div>
                                        @if (Route::has('password.request'))
                                            <a class="text-decoration-none text-info fw-bold" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                                        @endif
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-modern-primary" id="loginBtn"><i class="bi bi-box-arrow-in-right me-1"></i> Ingresar</button>
                                    </div>
                                </form>

                                <div class="login-footer-note">Holdco Networks · Inventario Infraestructura</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const form = document.getElementById('loginForm');
    const btn = document.getElementById('loginBtn');

    if (toggle && password) {
        toggle.addEventListener('click', function () {
            password.type = password.type === 'password' ? 'text' : 'password';
            toggle.innerHTML = password.type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });
    }

    if (form && btn) {
        form.addEventListener('submit', function () {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Ingresando...';
        });
    }
});
</script>
@endsection
