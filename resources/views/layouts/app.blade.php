<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Data') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @yield('css')




    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

</head>
<style>
    :root {
        --nav-bg: rgba(15, 24, 33, .96);
        --nav-border: #223346;
        --nav-text: #aebccd;
        --nav-text-active: #ffffff;
        --nav-accent: #1cc88a;
        --nav-accent-blue: #38bdf8;
    }
    body {
        background-color: #101820;
        font-family: 'Nunito', sans-serif;
    }
    h1, h2, h3, h4, h5, p { color: #e9ecef; }
    .navbar-custom {
        background: linear-gradient(180deg, rgba(18,27,37,.98), rgba(12,19,27,.98)) !important;
        backdrop-filter: blur(12px);
        box-shadow: 0 10px 30px rgba(0,0,0,.24);
        padding-top: .55rem;
        padding-bottom: .55rem;
        border-bottom: 1px solid var(--nav-border);
        min-height: 72px;
    }
    .navbar-custom .container-fluid { gap: 1rem; }
    .navbar-brand {
        min-width: 170px;
        padding: .2rem .7rem .2rem 0;
        border-right: 1px solid rgba(255,255,255,.06);
    }
    .navbar-brand img {
        height: 45px;
        object-fit: contain;
        transition: transform .2s ease, filter .2s ease;
        filter: drop-shadow(0 6px 10px rgba(0,0,0,.25));
    }
    .navbar-brand:hover img { transform: translateY(-1px) scale(1.02); }
    .navbar-toggler {
        border-color: #304153;
        background: rgba(255,255,255,.04);
        border-radius: 12px;
    }
    .main-nav {
        background: rgba(255,255,255,.035);
        border: 1px solid rgba(255,255,255,.055);
        border-radius: 999px;
        padding: .28rem;
        gap: .12rem;
    }
    .main-nav .nav-link {
        color: var(--nav-text) !important;
        font-weight: 800;
        font-size: .86rem;
        padding: .48rem .78rem !important;
        margin: 0 !important;
        border-radius: 999px;
        position: relative;
        transition: all .18s ease;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: .38rem;
    }
    .main-nav .nav-link i { font-size: .95rem; opacity: .8; }
    .main-nav .nav-link::after { display:none !important; }
    .main-nav .nav-link:hover {
        color: #fff !important;
        background: rgba(255,255,255,.07);
        transform: translateY(-1px);
    }
    .main-nav .nav-link.active {
        color: var(--nav-text-active) !important;
        background: linear-gradient(135deg, rgba(28,200,138,.26), rgba(56,189,248,.15));
        border: 1px solid rgba(28,200,138,.38);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.08), 0 7px 18px rgba(0,0,0,.18);
    }
    .dropdown-menu-dark {
        background-color: #111b25;
        border: 1px solid #2a3b4e;
        border-radius: 14px;
        box-shadow: 0 18px 38px rgba(0,0,0,.42);
        padding: .45rem;
    }
    .dropdown-item {
        color: #b9c7d6;
        padding: .58rem .75rem;
        border-radius: 10px;
        font-weight: 700;
        font-size: .86rem;
    }
    .dropdown-item:hover, .dropdown-item:focus {
        background-color: rgba(28,200,138,.14);
        color: #fff;
    }
    .dropdown-divider { border-color: #2b3d50; }
    .admin-pill {
        border: 1px solid rgba(220,53,69,.65) !important;
        color: #ff9ca8 !important;
        background: rgba(220,53,69,.08) !important;
        border-radius: 999px !important;
        padding: .45rem .82rem !important;
        font-weight: 850 !important;
        font-size: .82rem !important;
    }
    .admin-pill:hover {
        background: rgba(220,53,69,.18) !important;
        color: #fff !important;
    }
    .user-chip {
        display:flex;
        align-items:center;
        gap:.55rem;
        padding:.3rem .5rem .3rem .3rem !important;
        border:1px solid rgba(255,255,255,.08);
        background:rgba(255,255,255,.04);
        border-radius:999px;
        color:#dce6ef !important;
        font-weight:800;
        max-width: 250px;
    }
    .user-avatar {
        width:32px;
        height:32px;
        border-radius:50%;
        display:flex;
        justify-content:center;
        align-items:center;
        color:#fff;
        font-weight:900;
        background:linear-gradient(135deg,#0d6efd,#38bdf8);
        box-shadow:0 6px 14px rgba(13,110,253,.28);
        flex:0 0 auto;
    }
    .user-name {
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
        max-width:170px;
        font-size:.84rem;
    }
    .nav-actions {
        gap:.7rem;
        border-left:1px solid rgba(255,255,255,.08);
        padding-left:1rem;
    }
    main.py-4 { padding-top: 1.2rem !important; }
    @media (max-width: 991.98px) {
        .navbar-brand { border-right: 0; min-width:auto; }
        .main-nav {
            border-radius: 16px;
            padding: .55rem;
            margin-top: .75rem;
            align-items: stretch;
        }
        .main-nav .nav-link { justify-content:flex-start; }
        .nav-actions {
            border-left:0;
            border-top:1px solid rgba(255,255,255,.08);
            padding-left:0;
            padding-top:.75rem;
            margin-top:.75rem;
            align-items:flex-start !important;
        }
        .user-chip { max-width: 100%; }
        .user-name { max-width: 260px; }
    }
</style>
<body>
    <div id="app">
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="{{ url('/proxmox') }}" title="Dashboard Proxmox">
                <img src="{{ asset('holdco.png') }}" alt="Holdco Networks" class="d-inline-block align-text-top">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Abrir navegación">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav main-nav mx-auto mb-2 mb-lg-0">
                    @can('view dashboard')<li class="nav-item"><a class="nav-link {{ request()->is('proxmox') ? 'active' : '' }}" href="{{ url('/proxmox') }}"><i class="bi bi-speedometer2"></i> Resumen</a></li>@endcan
                    @can('view cluster')<li class="nav-item"><a class="nav-link {{ request()->is('proxmox/cluster*') ? 'active' : '' }}" href="{{ url('/proxmox/cluster') }}"><i class="bi bi-diagram-3"></i> Clusters</a></li>@endcan
                    @can('view nodes')<li class="nav-item"><a class="nav-link {{ request()->is('proxmox/node*') ? 'active' : '' }}" href="{{ url('/proxmox/node') }}"><i class="bi bi-hdd-network"></i> Nodos</a></li>@endcan
                    @can('view vms')<li class="nav-item"><a class="nav-link {{ request()->is('proxmox/qemu*') ? 'active' : '' }}" href="{{ url('/proxmox/qemu') }}"><i class="bi bi-pc-display"></i> VMs</a></li>@endcan
                    @can('view storage')<li class="nav-item"><a class="nav-link {{ request()->is('proxmox/storage*') ? 'active' : '' }}" href="{{ url('/proxmox/storage') }}"><i class="bi bi-database"></i> Storage</a></li>@endcan
                    @can('view inventory')<li class="nav-item"><a class="nav-link {{ request()->routeIs('proxmox.inventory') ? 'active' : '' }}" href="{{ route('proxmox.inventory') }}"><i class="bi bi-clipboard-data"></i> Inventario</a></li>@endcan
                    @can('view inventory')<li class="nav-item"><a class="nav-link {{ request()->routeIs('proxmox.globalSearch') ? 'active' : '' }}" href="{{ route('proxmox.globalSearch') }}"><i class="bi bi-search"></i> Buscar</a></li>@endcan
                    @can('view dashboard')<li class="nav-item"><a class="nav-link {{ request()->routeIs('proxmox.firewall.nat') ? 'active' : '' }}" href="{{ route('proxmox.firewall.nat') }}"><i class="bi bi-shield-lock"></i> Reglas NAT</a></li>@endcan
                    @can('view pbs')<li class="nav-item"><a class="nav-link {{ request()->routeIs('pbs.*') ? 'active' : '' }}" href="{{ route('pbs.index') }}"><i class="bi bi-shield-lock"></i> PBS</a></li>@endcan
                    @can('view history')
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->is('proxmox/history*') || request()->is('proxmox/QemuDeleted*') ? 'active' : '' }}" href="#" id="moreDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-clock-history"></i> Histórico</a>
                        <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="moreDropdown">
                            <li><a class="dropdown-item" href="{{ url('/proxmox/history') }}"><i class="bi bi-graph-up me-2 text-success"></i> Histórico mensual</a></li>
                            <li><a class="dropdown-item" href="{{ url('/proxmox/historyAnual') }}"><i class="bi bi-calendar3 me-2 text-info"></i> Histórico anual</a></li>
                            <li><a class="dropdown-item" href="{{ url('/proxmox/QemuDeleted') }}"><i class="bi bi-trash3 me-2 text-danger"></i> Eliminados</a></li>
                        </ul>
                    </li>
                    @endcan
                    @can('view network map')
                    <li class="nav-item"><a class="nav-link" href="http://inventario-infra.holdconet.com/NetworkMapper/" target="_blank"><i class="bi bi-map"></i> Network Map <i class="bi bi-box-arrow-up-right" style="font-size:.65rem;"></i></a></li>
                    @endcan
                </ul>

                <ul class="navbar-nav nav-actions ms-auto align-items-center">
                    @if(auth()->check() && (auth()->user()->can('manage users') || auth()->user()->can('manage cluster') || auth()->user()->can('run sync')))
                    <li class="nav-item dropdown">
                        <a class="btn btn-sm admin-pill d-flex align-items-center" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shield-lock me-1"></i> Administrar
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" aria-labelledby="adminDropdown">
                            @can('manage users')
                            <li><a class="dropdown-item" href="{{ route('users.index') }}"><i class="bi bi-people me-2 text-info"></i> Usuarios y perfiles</a></li>
                            @endcan
                            @can('run sync')
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('proxmox.sync.fast') }}"><i class="bi bi-lightning-fill me-2 text-warning"></i> Actualización rápida</a></li>
                            <li><a class="dropdown-item" href="{{ route('proxmox.sync.qga') }}" onclick="return confirm('ATENCIÓN: La sincronización completa consulta el Agente QEMU y puede tardar varios minutos. ¿Deseas continuar?');"><i class="bi bi-database-fill-gear me-2 text-danger"></i> Sincronización completa</a></li>
                            @endcan
                            @can('manage cluster')
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('proxmox.cluster.create') }}"><i class="bi bi-plus-circle me-2 text-success"></i> Crear cluster</a></li>
                            @endcan
                        </ul>
                    </li>
                    @endif

                    @guest
                        @if (Route::has('login'))
                            <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a></li>
                        @endif
                    @else
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle user-chip" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                <span class="user-avatar">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}</span>
                                <span class="user-name">{{ Auth::user()->name ?? 'Usuario' }}</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-dark" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="bi bi-box-arrow-right me-2 text-danger"></i> {{ __('Cerrar Sesión') }}</a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                            </div>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

        <main class="py-4">
            @yield('content')
            @yield('script')
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    {{-- AÑADE ESTE SCRIPT PARA EL BOTÓN DE "ACTUALIZAR" --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const syncButton = document.getElementById('syncButton');
            if (syncButton) {
                syncButton.addEventListener('click', function(e) {
                    // Previene clics si ya está deshabilitado
                    if (syncButton.classList.contains('disabled')) {
                        e.preventDefault();
                        return;
                    }

                    // Deshabilita el botón y muestra el spinner
                    syncButton.classList.add('disabled');
                    document.getElementById('syncText').textContent = 'Actualizando...';
                    document.getElementById('syncSpinner').classList.remove('d-none');

                    // El enlace (href) continuará
                });
            }
        });
    </script>
    @yield('scripts')

</body>

</html>
