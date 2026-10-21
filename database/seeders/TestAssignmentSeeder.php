<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@aei.org')->first();
        if (!$admin) {
            return;
        }

        $center = Center::where('code', 'GAZA')->first();
        $project = Project::where('code', 'WFP')->first();

        if ($center) {
            $admin->centers()->syncWithoutDetaching([$center->id]);
        }

        if ($project && $center) {
            $admin->projects()->syncWithoutDetaching([
                $project->id => ['center_id' => $center->id],
            ]);
        }

        // Set role for testing
        $admin->update(['role' => User::ROLE_RECEPTION]);

        $this->command->info('Admin assigned to ' . ($center?->name ?? 'N/A') . ' / ' . ($project?->code ?? 'N/A'));
    }
}
