<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\IdentityType;
use App\Models\Project;
use App\Models\ReasonOption;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SystemDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCenters();
        $this->seedProjects();
        $this->seedCenterProjects();
        $this->seedIdentityTypes();
        $this->seedReasonOptions();
        $this->seedSettings();
    }

    private function seedCenters(): void
    {
        $centers = [
            ['code' => 'GAZA', 'name' => 'مركز غزة', 'color' => 'blue'],
            ['code' => 'KHY',  'name' => 'مركز خانيونس', 'color' => 'green'],
        ];

        foreach ($centers as $data) {
            Center::firstOrCreate(['code' => $data['code']], $data);
        }
    }

    private function seedProjects(): void
    {
        $projects = [
            ['code' => 'WFP',    'name' => 'WFP'],
            ['code' => 'MAP',    'name' => 'MAP'],
            ['code' => 'WHH',    'name' => 'WHH'],
            ['code' => 'UNICEF', 'name' => 'UNICEF'],
            ['code' => 'STC',    'name' => 'Save The Children'],
            ['code' => 'CELIAC', 'name' => 'السيلياك'],
            ['code' => 'MECA',   'name' => 'MECA'],
            ['code' => 'TAAWON', 'name' => 'التعاون'],
        ];

        foreach ($projects as $data) {
            Project::firstOrCreate(['code' => $data['code']], $data);
        }
    }

    private function seedCenterProjects(): void
    {
        $centers = Center::all()->keyBy('code');
        $projects = Project::all();

        foreach ($centers as $center) {
            foreach ($projects as $project) {
                $center->projects()->syncWithoutDetaching([$project->id => ['is_active' => true]]);
            }
        }
    }

    private function seedIdentityTypes(): void
    {
        $types = [
            ['name' => 'mother', 'printed_name' => 'أم',    'is_system' => true],
            ['name' => 'father', 'printed_name' => 'أب',    'is_system' => true],
            ['name' => 'child',  'printed_name' => 'طفل',   'is_system' => true],
        ];

        foreach ($types as $data) {
            IdentityType::firstOrCreate(['name' => $data['name']], $data);
        }
    }

    private function seedReasonOptions(): void
    {
        $reasons = [
            // Exception reasons
            ['category' => 'exception', 'name' => 'استثناء طارئ'],
            ['category' => 'exception', 'name' => 'توجيه من المشروع'],
            // New project reasons
            ['category' => 'new_project', 'name' => 'توسيع تغطية المشروع'],
            ['category' => 'new_project', 'name' => 'إحالة من مركز آخر'],
            // Center transfer reasons
            ['category' => 'center_transfer', 'name' => 'تغيير مكان الإقامة'],
            ['category' => 'center_transfer', 'name' => 'طلب النقل'],
            // Cancel visit reasons
            ['category' => 'cancel_visit', 'name' => 'خطأ في البيانات'],
            ['category' => 'cancel_visit', 'name' => 'تسجيل مكرر'],
        ];

        foreach ($reasons as $data) {
            ReasonOption::firstOrCreate(
                ['category' => $data['category'], 'name' => $data['name']],
                $data
            );
        }
    }

    private function seedSettings(): void
    {
        $settings = [
            ['key' => 'idle_timeout',        'value' => '30',   'type' => 'integer'],
            ['key' => 'checksum_validation',  'value' => 'true', 'type' => 'boolean'],
            ['key' => 'search_limit',         'value' => '30',   'type' => 'integer'], // per minute per user
            ['key' => 'name_edit_timeout',    'value' => '15',   'type' => 'integer'], // minutes after creation
        ];

        foreach ($settings as $data) {
            Setting::firstOrCreate(['key' => $data['key']], $data);
        }
    }
}
