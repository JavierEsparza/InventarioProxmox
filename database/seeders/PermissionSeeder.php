<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view dashboard', 'view cluster', 'view nodes', 'view vms', 'view storage',
            'view inventory', 'view pbs', 'view history', 'view network map',
            'manage cluster', 'manage users', 'run sync',
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        $allViews = ['view dashboard','view cluster','view nodes','view vms','view storage','view inventory','view pbs','view history','view network map'];
        $roles = [
            'admin' => array_merge($allViews, ['manage cluster','manage users','run sync']),
            'operador' => array_merge($allViews, ['manage cluster','run sync']),
            'sin sincronizacion' => array_merge($allViews, ['manage cluster']),
            'solo lectura' => $allViews,
            'auditor' => ['view dashboard','view vms','view inventory','view pbs','view history'],
            'personalizado' => [],
            'user' => $allViews,
        ];
        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }
    }
}
