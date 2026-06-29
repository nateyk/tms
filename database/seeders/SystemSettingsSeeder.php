<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'company_name', 'value' => 'Menkem International Business PLC', 'group' => 'general'],
            ['key' => 'max_trailers_per_power', 'value' => '1', 'group' => 'fleet'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
