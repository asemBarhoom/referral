<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

// Test-phase accounts shown as one-click logins on the login page (see config app.demo_login).
class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $centerIds = Center::where('is_active', true)->pluck('id');

        $admin = $this->demoUser('superadmin', 'السوبر أدمن', User::ROLE_ADMIN);
        $admin->assignRole('super_admin');
        $admin->centers()->sync($centerIds);

        foreach (Center::where('is_active', true)->orderBy('id')->get() as $center) {
            $user = $this->demoUser(
                'reception.' . Str::lower($center->code),
                'استقبال ' . Str::after($center->name, 'مركز '),
                User::ROLE_RECEPTION,
            );
            $user->centers()->sync([$center->id]);
            $user->projects()->sync([]);
        }

        foreach (Project::where('is_active', true)->orderBy('display_order')->get() as $project) {
            $user = $this->demoUser('point.' . Str::lower($project->code), 'نقطة ' . $project->name, User::ROLE_POINT);
            $user->centers()->sync($centerIds);
            $user->projects()->sync([$project->id]);
        }
    }

    private function demoUser(string $username, string $name, string $role): User
    {
        return User::updateOrCreate(
            ['email' => $username . '@' . User::DEMO_DOMAIN],
            ['name' => $name, 'username' => $username, 'password' => 'password', 'role' => $role, 'is_active' => true],
        );
    }
}
