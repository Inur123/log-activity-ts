<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // SUPER ADMIN
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name'       => 'Super Admin',
                'email'      => 'admin@gmail.com',
                'password'   => Hash::make('password'),
                'role'       => 'super_admin',
                'is_active'  => true,
            ]
        );

        // AUDITOR
        User::updateOrCreate(
            ['email' => 'auditor@gmail.com'],
            [
                'name'       => 'Auditor',
                'email'      => 'auditor@gmail.com',
                'password'   => Hash::make('password'),
                'role'       => 'auditor',
                'is_active'  => true,
            ]
        );
    }
}
