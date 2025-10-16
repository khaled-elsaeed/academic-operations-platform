<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            ['key' => 'enable_enrollment', 'value' => '1', 'type' => 'boolean', 'group' => 'enrollment'],
            ['key' => 'allow_create_enrollment', 'value' => '1', 'type' => 'boolean', 'group' => 'enrollment'],
            ['key' => 'allow_delete_enrollment', 'value' => '1', 'type' => 'boolean', 'group' => 'enrollment'],
        ];

        foreach ($defaults as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
