<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LevelSeeder::class,
            FacultySeeder::class,
            ProgramSeeder::class,
            CourseSeeder::class,
            TermSeeder::class,
            SettingSeeder::class,
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
            ScheduleTypeSeeder::class,
        ]);
    }
}
