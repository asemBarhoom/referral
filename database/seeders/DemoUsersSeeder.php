<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

// Test-phase accounts shown as one-click logins on the login page (see config app.demo_login).
class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->demoUser('superadmin', 'السوبر أدمن', User::ROLE_ADMIN)->assignRole('super_admin');

        foreach (Center::where('is_active', true)->orderBy('id')->get() as $index => $center) {
            $short  = Str::after($center->name, 'مركز ');
            $suffix = Str::lower($center->code);

            $this->demoUser("reception.$suffix", "استقبال $short", User::ROLE_RECEPTION, $center->id);

            $projects = $center->projects()
                ->where('projects.is_active', true)
                ->wherePivot('is_active', true)
                ->orderBy('display_order')
                ->get();

            foreach ($projects as $project) {
                $code = Str::lower($project->code);

                // Earlier seeds had one point user per project for all centers; it becomes the first center's user so its entries are kept.
                if ($index === 0) {
                    User::where('email', "point.$code@" . User::DEMO_DOMAIN)
                        ->update(['email' => "point.$code.$suffix@" . User::DEMO_DOMAIN]);
                }

                $this->demoUser("point.$code.$suffix", "نقطة {$project->name} $short", User::ROLE_POINT, $center->id, $project->id);
            }
        }
    }

    private function demoUser(string $username, string $name, string $role, ?int $centerId = null, ?int $projectId = null): User
    {
        return User::updateOrCreate(
            ['email' => $username . '@' . User::DEMO_DOMAIN],
            [
                'name'       => $name,
                'username'   => $username,
                'password'   => 'password',
                'role'       => $role,
                'is_active'  => true,
                'center_id'  => $centerId,
                'project_id' => $projectId,
            ],
        );
    }
}
