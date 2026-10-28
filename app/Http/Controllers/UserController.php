<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:manage users');
    }

    private function roleMeta(): array
    {
        return [
            'admin' => ['label'=>'Administrador','badge'=>'danger','description'=>'Acceso total a módulos, usuarios y sincronizaciones.'],
            'operador' => ['label'=>'Operador','badge'=>'success','description'=>'Todos los módulos, administración operativa y sincronización.'],
            'sin sincronizacion' => ['label'=>'Sin sincronización','badge'=>'warning','description'=>'Todos los módulos y edición operativa, sin ejecutar sync.'],
            'solo lectura' => ['label'=>'Solo lectura','badge'=>'info','description'=>'Todos los módulos visibles, sin editar ni sincronizar.'],
            'auditor' => ['label'=>'Auditor / Histórico','badge'=>'secondary','description'=>'Vista acotada para VMs, inventario, PBS e históricos.'],
            'personalizado' => ['label'=>'Personalizado','badge'=>'primary','description'=>'Acceso exacto definido por los checks de módulos/permisos.'],
            'user' => ['label'=>'Usuario legado','badge'=>'secondary','description'=>'Rol anterior: equivalente a solo lectura.'],
        ];
    }

    private function permissionMeta(): array
    {
        return [
            'view dashboard' => ['label'=>'Resumen','icon'=>'bi-speedometer2','group'=>'Vistas'],
            'view cluster' => ['label'=>'Clusters','icon'=>'bi-diagram-3','group'=>'Vistas'],
            'view nodes' => ['label'=>'Inventario servidores / Nodos','icon'=>'bi-hdd-network','group'=>'Vistas'],
            'view vms' => ['label'=>'Inventario VM\'s','icon'=>'bi-pc-display','group'=>'Vistas'],
            'view storage' => ['label'=>'Storage','icon'=>'bi-database','group'=>'Vistas'],
            'view inventory' => ['label'=>'Inventario hardware','icon'=>'bi-clipboard-data','group'=>'Vistas'],
            'view pbs' => ['label'=>'PBS / Backups','icon'=>'bi-shield-lock','group'=>'Vistas'],
            'view history' => ['label'=>'Histórico','icon'=>'bi-clock-history','group'=>'Vistas'],
            'view network map' => ['label'=>'Network Map','icon'=>'bi-map','group'=>'Vistas'],
            'manage cluster' => ['label'=>'Editar / administrar datos operativos','icon'=>'bi-pencil-square','group'=>'Acciones'],
            'run sync' => ['label'=>'Puede sincronizar','icon'=>'bi-arrow-repeat','group'=>'Acciones'],
            'manage users' => ['label'=>'Administrar usuarios y perfiles','icon'=>'bi-people','group'=>'Acciones'],
        ];
    }

    private function orderedRoles()
    {
        return Role::orderByRaw("FIELD(name, 'admin', 'operador', 'sin sincronizacion', 'solo lectura', 'auditor', 'personalizado', 'user')")->orderBy('name')->get();
    }

    private function permissionsForForm()
    {
        $order = array_keys($this->permissionMeta());
        return Permission::whereIn('name', $order)->get()->sortBy(fn($p) => array_search($p->name, $order))->values();
    }

    public function index()
    {
        $users = User::with(['roles','permissions'])->orderBy('name')->get();
        return view('users.index', ['users'=>$users, 'roleMeta'=>$this->roleMeta(), 'permissionMeta'=>$this->permissionMeta()]);
    }

    public function show($id)
    {
        $user = User::with(['roles','permissions'])->findOrFail($id);
        return view('users.show', ['user'=>$user, 'roleMeta'=>$this->roleMeta(), 'permissionMeta'=>$this->permissionMeta()]);
    }

    public function create()
    {
        return view('users.create', ['roles'=>$this->orderedRoles(), 'roleMeta'=>$this->roleMeta(), 'permissions'=>$this->permissionsForForm(), 'permissionMeta'=>$this->permissionMeta()]);
    }

    public function store(StoreUserRequest $request)
    {
        try {
            $user = User::create([
                'name'=>$request->input('name'), 'email'=>$request->input('email'),
                'password'=>bcrypt($request->input('password')), 'username'=>$request->input('username'),
            ]);
            $user->assignRole($request->input('role'));
            $user->syncPermissions($request->input('permissions', []));
            return redirect()->route('users.show', $user->id)->with('success', 'Usuario creado correctamente');
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->route('users.create')->withInput()->with('error', 'Error al crear el usuario: '.$e->getMessage());
        }
    }

    public function edit($id)
    {
        $user = User::with(['roles','permissions'])->findOrFail($id);
        return view('users.edit', ['user'=>$user, 'roles'=>$this->orderedRoles(), 'roleMeta'=>$this->roleMeta(), 'permissions'=>$this->permissionsForForm(), 'permissionMeta'=>$this->permissionMeta()]);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'username' => ['required','string','min:3','max:255', Rule::unique('users','username')->ignore($id)],
            'name' => 'required|string|max:255',
            'email' => ['required','email', Rule::unique('users','email')->ignore($id)],
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|exists:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);
        $user = User::findOrFail($id);
        $user->username=$validatedData['username']; $user->name=$validatedData['name']; $user->email=$validatedData['email'];
        if (!empty($validatedData['password'])) { $user->password=bcrypt($validatedData['password']); }
        $user->save();
        $user->syncRoles([$validatedData['role']]);
        $user->syncPermissions($request->input('permissions', []));
        return redirect()->route('users.show', $user->id)->with('success', 'Usuario actualizado correctamente');
    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();
        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente');
    }
}
