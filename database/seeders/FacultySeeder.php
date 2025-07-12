<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Faculty;

class FacultySeeder extends Seeder
{
    public function run(): void
    {
        $faculties = [
            'Faculty of Computer Science & Engineering',
            'Faculty of Engineering',
            'Faculty of Science',
            'Faculty of Business',
            'Faculty of Social & Human Sciences',
        ];

        foreach ($faculties as $facultyName) {
            Faculty::firstOrCreate(['name' => $facultyName]);
        }
    }
} 