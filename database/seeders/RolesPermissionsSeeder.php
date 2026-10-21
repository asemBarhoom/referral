<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = ['super_admin', 'admin', 'point', 'reception'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $superAdmin = Role::findByName('super_admin');
        $admin      = Role::findByName('admin');
        $point      = Role::findByName('point');
        $reception  = Role::findByName('reception');

        // super_admin and admin: all permissions
        $superAdmin->givePermissionTo(Permission::all());
        $admin->givePermissionTo(Permission::all());

        // point: entry page + stats widget
        foreach (['View:EntryPage', 'View:DailyStatsWidget'] as $perm) {
            if (Permission::where('name', $perm)->exists()) {
                $point->givePermissionTo($perm);
            }
        }

        // reception: entry page + stats widget
        foreach (['View:EntryPage', 'View:DailyStatsWidget'] as $perm) {
            if (Permission::where('name', $perm)->exists()) {
                $reception->givePermissionTo($perm);
            }
        }

        $this->command->info('Roles and permissions seeded successfully.');
    }
}
