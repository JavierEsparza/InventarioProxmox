<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        User::factory()->create([
            'username' => 'admin',
            'name' => 'admin admin',
            'email' => 'admin@email.com',
            'password' => bcrypt('password'),
        ]);

        $adminUser = User::where('username', 'admin')->first();
        $adminUser->assignRole('admin');

    }
}
