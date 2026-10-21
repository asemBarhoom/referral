<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Flush cache before touching permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = [
            'super_admin',
            'dashboard_admin',
            'center_admin',
            'project_staff',
            'reception',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $superAdmin     = Role::findByName('super_admin');
        $dashboardAdmin = Role::findByName('dashboard_admin');
        $centerAdmin    = Role::findByName('center_admin');
        $projectStaff   = Role::findByName('project_staff');
        $reception      = Role::findByName('reception');

        // super_admin gets everything (Shield bypasses policies for this role automatically)
        $superAdmin->givePermissionTo(Permission::all());

        // dashboard_admin: full access to everything
        $dashboardAdmin->givePermissionTo(Permission::all());

        // center_admin: beneficiaries, restrictions, review, visits, service records + operational pages
        $centerAdminPerms = [
            'ViewAny:Beneficiary', 'View:Beneficiary', 'Update:Beneficiary',
            'ViewAny:Restriction', 'View:Restriction', 'Create:Restriction',
            'Update:Restriction', 'Delete:Restriction', 'DeleteAny:Restriction',
            'ViewAny:ReviewItem', 'View:ReviewItem', 'Update:ReviewItem',
            'ViewAny:ReceptionVisit', 'View:ReceptionVisit', 'Update:ReceptionVisit',
            'ViewAny:ServiceRecord', 'View:ServiceRecord',
            'View:ReceptionPage',
            'View:ProjectServicePage',
            'View:DailyStatsWidget',
        ];

        foreach ($centerAdminPerms as $perm) {
            if (Permission::where('name', $perm)->exists()) {
                $centerAdmin->givePermissionTo($perm);
            }
        }

        // project_staff: project service page + stats widget only
        $projectStaffPerms = [
            'View:ProjectServicePage',
            'View:DailyStatsWidget',
        ];

        foreach ($projectStaffPerms as $perm) {
            if (Permission::where('name', $perm)->exists()) {
                $projectStaff->givePermissionTo($perm);
            }
        }

        // reception: reception page + stats widget only
        $receptionPerms = [
            'View:ReceptionPage',
            'View:DailyStatsWidget',
        ];

        foreach ($receptionPerms as $perm) {
            if (Permission::where('name', $perm)->exists()) {
                $reception->givePermissionTo($perm);
            }
        }

        $this->command->info('Roles and permissions seeded successfully.');
        $this->command->table(
            ['Role', 'Permissions'],
            [
                ['super_admin',     'All (' . Permission::count() . ')'],
                ['dashboard_admin', 'All (' . Permission::count() . ')'],
                ['center_admin',    count($centerAdminPerms) . ' permissions'],
                ['project_staff',   count($projectStaffPerms) . ' permissions'],
                ['reception',       count($receptionPerms) . ' permissions'],
            ]
        );
    }
}
