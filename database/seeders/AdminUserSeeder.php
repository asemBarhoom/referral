<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $roles = [
            'super_admin',
            'dashboard_admin',
            'center_admin',
            'project_staff',
            'reception',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Create default dashboard admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@aei.org'],
            [
                'name' => 'Dashboard Admin',
                'password' => bcrypt('password'),
                'role' => User::ROLE_DASHBOARD_ADMIN,
                'is_active' => true,
            ]
        );

        $admin->assignRole('super_admin');
    }
}
