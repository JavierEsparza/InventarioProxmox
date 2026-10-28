<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Contracts\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        /*  \App\Models\User::factory()->create([
             'name' => 'Test User',
             'email' => 'test@example.com',
                'password' => bcrypt('password'),

         ]); */

        $this->call(VirtualMachineHistorySeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(UsersSeeder::class);
    }
}
