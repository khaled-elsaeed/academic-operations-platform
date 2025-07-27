<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScheduleTypeSeeder extends Seeder
{
    public function run(): void
    {
        $scheduleTypes = [
            [
                'name' => 'Final Exam Schedule',
                'slug' => Str::slug('Final Exam Schedule'),
                'description' => 'This schedule outlines the final arrangement for all final exam activities.',
                'is_repetitive' => 0,
                'repetition_pattern' => 'none',
                'default_settings' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Midterm Exam Schedule',
                'slug' => Str::slug('Midterm Exam Schedule'),
                'description' => 'This schedule outlines the arrangement for all midterm exam activities.',
                'is_repetitive' => 0,
                'repetition_pattern' => 'none',
                'default_settings' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Weekly Teaching Schedule',
                'slug' => Str::slug('Weekly Teaching Schedule'),
                'description' => 'Recurring weekly schedule for regular teaching sessions.',
                'is_repetitive' => 1,
                'repetition_pattern' => 'weekly',
                'default_settings' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('schedule_types')->insert($scheduleTypes);
    }
}
