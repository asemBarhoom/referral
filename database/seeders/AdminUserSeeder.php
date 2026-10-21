<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['super_admin', 'admin', 'point', 'reception'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@aei.org'],
            [
                'name'     => 'مدير النظام',
                'username' => 'admin',
                'password' => bcrypt('password'),
                'role'     => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        $admin->assignRole('super_admin');
    }
}
