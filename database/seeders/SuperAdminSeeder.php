<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar super admin
        $superAdmin = \App\Models\User::create([
            'name' => 'Super Admin',
            'email' => 'admin@pontoeletronico.com',
            'password' => bcrypt('password'),
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ]);

        // Atribuir role de super-admin
        $superAdmin->assignRole('super-admin');
    }
}
