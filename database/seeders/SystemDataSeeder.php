<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Project;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SystemDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCenters();
        $this->seedProjects();
        $this->seedCenterProjects();
        $this->seedSettings();
    }

    private function seedCenters(): void
    {
        $centers = [
            ['code' => 'GAZA', 'name' => 'مركز غزة',      'color' => 'blue'],
            ['code' => 'KHY',  'name' => 'مركز خانيونس', 'color' => 'green'],
        ];

        foreach ($centers as $data) {
            Center::firstOrCreate(['code' => $data['code']], $data);
        }
    }

    private function seedProjects(): void
    {
        $projects = [
            ['code' => 'WFP',    'name' => 'WFP',              'display_order' => 1],
            ['code' => 'MAP',    'name' => 'MAP',              'display_order' => 2],
            ['code' => 'WHH',    'name' => 'WHH',              'display_order' => 3],
            ['code' => 'UNICEF', 'name' => 'UNICEF',           'display_order' => 4],
            ['code' => 'STC',    'name' => 'Save The Children','display_order' => 5],
            ['code' => 'CELIAC', 'name' => 'السيلياك',         'display_order' => 6],
            ['code' => 'MECA',   'name' => 'MECA',             'display_order' => 7],
            ['code' => 'TAAWON', 'name' => 'التعاون',          'display_order' => 8],
        ];

        foreach ($projects as $data) {
            Project::firstOrCreate(['code' => $data['code']], $data);
        }
    }

    private function seedCenterProjects(): void
    {
        $centers  = Center::all();
        $projects = Project::all();

        foreach ($centers as $center) {
            foreach ($projects as $project) {
                $center->projects()->syncWithoutDetaching([$project->id => ['is_active' => true]]);
            }
        }
    }

    private function seedSettings(): void
    {
        $settings = [
            ['key' => 'idle_timeout',     'value' => '30',                                           'type' => 'integer'],
            ['key' => 'hint_reception',   'value' => 'أدخل رقم هوية الحالة، ثم اختر المشاريع التي ستتوجه لها الحالة.', 'type' => 'string'],
            ['key' => 'hint_point',       'value' => 'أدخل كل حالة تخدمها، واحدة تلو الأخرى، فور تقديم الخدمة.',         'type' => 'string'],
        ];

        foreach ($settings as $data) {
            Setting::firstOrCreate(['key' => $data['key']], $data);
        }
    }
}
