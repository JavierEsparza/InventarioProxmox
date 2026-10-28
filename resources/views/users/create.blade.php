@extends('layouts.app')
@section('content')

<style>
.users-page{width:min(96vw,1760px);max-width:none;margin:0 auto;padding:0 1.25rem}.users-hero{background:linear-gradient(135deg,rgba(28,200,138,.18),rgba(56,189,248,.10)),#0f1924;border:1px solid #26384b;border-radius:22px;padding:1.25rem 1.4rem;box-shadow:0 18px 45px rgba(0,0,0,.22)}.metric-card,.users-card,.users-panel{background:#111c27;border:1px solid #27394c;border-radius:18px;box-shadow:0 14px 36px rgba(0,0,0,.22)}.metric-card{padding:1rem}.metric-card .label{color:#93a4b8;text-transform:uppercase;font-size:.72rem;font-weight:900;letter-spacing:.06em}.metric-card .value{font-size:1.7rem;color:#fff;font-weight:900}.users-panel{overflow:hidden}.users-table{margin:0;color:#dce8f3;--bs-table-bg:#111c27;--bs-table-color:#dce8f3;--bs-table-border-color:#223346;--bs-table-hover-bg:#162435;--bs-table-hover-color:#fff}.users-table thead th{background:#0b1420!important;color:#93c5fd!important;border-color:#24384c!important;font-size:.78rem;text-transform:uppercase;letter-spacing:.05em}.users-table tbody tr,.users-table tbody td{background:#111c27!important;color:#dce8f3!important;border-color:#223346!important}.users-table tbody tr:nth-child(even) td{background:#0f1a25!important}.users-table tbody tr:hover td{background:#17283a!important}.user-main{font-weight:900;color:#fff}.user-sub,.hint{color:#93a4b8}.user-sub{font-size:.78rem}.search-box,.form-control,.form-select{background:#0b1420!important;border:1px solid #2b4258!important;color:#eef5fb!important;border-radius:12px}.search-box{border-radius:999px}.form-label{color:#b9c7d6;font-weight:800}.btn-soft{border:1px solid #2b4258;color:#d9e8f5;background:#132232}.btn-soft:hover{background:#1a2b3e;color:#fff}.action-btn{border-radius:999px;font-weight:800}.role-desc{display:block;color:#8fa3b7;font-size:.74rem;margin-top:.15rem}.perm-chip{display:inline-flex;align-items:center;gap:.25rem;border:1px solid #2b4258;border-radius:999px;padding:.22rem .48rem;margin:.1rem;background:#0b1420;color:#cfe2f3;font-size:.72rem;font-weight:800}.perm-card{border:1px solid #2b4258;border-radius:14px;padding:.75rem;background:#0d1824;height:100%}.perm-card label{color:#dce8f3;font-weight:800}.perm-card small{display:block;color:#93a4b8}.users-card .card-header{border-bottom:1px solid #26384b;background:rgba(255,255,255,.025);color:#eaf2f8;font-weight:900}
</style>

<div class="users-page"><div class="users-hero d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><div class="text-uppercase small text-info fw-bold">Administración de acceso</div><h1 class="mb-1">Crear usuario</h1><p class="mb-0 hint">Seleccioná módulos visibles y si el usuario puede sincronizar.</p></div><a href="{{ route('users.index') }}" class="btn btn-soft"><i class="bi bi-arrow-left me-1"></i> Volver</a></div>
@if($errors->any())<div class="alert alert-danger border-0 rounded-4"><strong>Revisar datos:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif @if(session('error'))<div class="alert alert-danger border-0 rounded-4">{{ session('error') }}</div>@endif
<div class="users-card card"><div class="card-header"><i class="bi bi-person-plus me-2 text-success"></i>Datos y permisos</div><div class="card-body p-4"><form action="{{ route('users.store') }}" method="POST">@csrf

<div class="row g-3">
 <div class="col-md-6"><label class="form-label" for="username">Usuario de login</label><input id="username" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $user->username ?? '') }}" required>@error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
 <div class="col-md-6"><label class="form-label" for="name">Nombre completo</label><input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name ?? '') }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
 <div class="col-md-6"><label class="form-label" for="email">Correo</label><input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email ?? '') }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
 <div class="col-md-6"><label class="form-label" for="password">{{ isset($user) ? 'Nueva contraseña' : 'Contraseña temporal' }}</label><div class="input-group"><input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" {{ isset($user) ? '' : 'required' }} placeholder="{{ isset($user) ? 'Dejar en blanco para no cambiar' : '' }}"><button class="btn btn-soft" type="button" onclick="const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';this.innerHTML=p.type==='password'?'<i class=&quot;bi bi-eye&quot;></i>':'<i class=&quot;bi bi-eye-slash&quot;></i>';" title="Mostrar/Ocultar"><i class="bi bi-eye"></i></button>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="form-text hint">Mínimo 8 caracteres.</div></div>
 <div class="col-12">
  <label class="form-label" for="role">Perfil base</label>
  <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
    @foreach($roles as $role)
      @php
        $meta = $roleMeta[$role->name] ?? ['label' => ucfirst($role->name), 'description' => 'Rol personalizado'];
        $selectedRole = old('role', isset($user) ? optional($user->roles->first())->name : 'personalizado');
      @endphp
      <option value="{{ $role->name }}" {{ $selectedRole === $role->name ? 'selected' : '' }}>{{ $meta['label'] }} — {{ $meta['description'] }}</option>
    @endforeach
  </select>
  @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
  <div class="form-text hint">Para acceso exacto por módulo usá “Personalizado”. Si elegís admin/operador/solo lectura, el rol entrega permisos base además de los checks marcados.</div>
</div>
 <div class="col-12">
  <label class="form-label">Permisos específicos por usuario</label>
  <div class="row g-2">
    @foreach($permissions as $perm)
      @php
        $pm = $permissionMeta[$perm->name];
        $directPermissions = old('permissions', isset($user) ? $user->permissions->pluck('name')->toArray() : []);
        $checked = in_array($perm->name, $directPermissions);
      @endphp
      <div class="col-md-6 col-xl-4">
        <div class="perm-card">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="perm_{{ $loop->index }}" name="permissions[]" value="{{ $perm->name }}" {{ $checked ? 'checked' : '' }}>
            <label class="form-check-label" for="perm_{{ $loop->index }}"><i class="bi {{ $pm['icon'] }} me-1 text-info"></i>{{ $pm['label'] }}</label>
            <small>{{ $pm['group'] }}</small>
          </div>
        </div>
      </div>
    @endforeach
  </div>
</div>
 <div class="col-12 d-flex flex-wrap gap-2 pt-2"><button type="submit" class="btn btn-success fw-bold px-4"><i class="bi bi-save me-1"></i>{{ isset($user) ? 'Guardar cambios' : 'Crear usuario' }}</button><a href="{{ route('users.index') }}" class="btn btn-soft">Cancelar</a></div>
</div>

</form></div></div></div>
@endsection
