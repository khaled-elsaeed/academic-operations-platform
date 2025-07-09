<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AvailableCourse;

class AvailableCourseSeeder extends Seeder
{
    public function run(): void
    {
        AvailableCourse::factory(30)->create();
    }
} 