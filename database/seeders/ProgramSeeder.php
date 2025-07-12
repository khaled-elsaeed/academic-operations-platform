<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\Faculty;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            'Faculty of Computer Science & Engineering' => [
                ['name' => 'Biomedical Informatics', 'code' => 'BMI'],
                ['name' => 'Computer Science', 'code' => 'CS'],
                ['name' => 'Artificial Intelligence Science', 'code' => 'AIS'],
                ['name' => 'Computer Engineering', 'code' => 'CE'],
                ['name' => 'Artificial Intelligence Engineering', 'code' => 'AIE'],
            ],
            'Faculty of Engineering' => [
                ['name' => 'Mechanical Engineering', 'code' => 'ME'],
                ['name' => 'Electrical Engineering', 'code' => 'EE'],
            ],
            'Faculty of Science' => [
                ['name' => 'Physics', 'code' => 'PHY'],
                ['name' => 'Mathematics', 'code' => 'MAT'],
                ['name' => 'Chemistry', 'code' => 'CHE'],
                ['name' => 'Biology', 'code' => 'BIO'],
            ],
            'Faculty of Business' => [
                ['name' => 'Management', 'code' => 'MGT'],
                ['name' => 'Economics', 'code' => 'ECO'],
            ],
            'Faculty of Social & Human Sciences' => [
                ['name' => 'Language Studies', 'code' => 'LAN'],
                ['name' => 'Geography', 'code' => 'GEO'],
                ['name' => 'Political Science', 'code' => 'PSC'],
                ['name' => 'Library Science', 'code' => 'LIB'],
            ],
        ];

        foreach ($programs as $facultyName => $facultyPrograms) {
            $faculty = Faculty::where('name', $facultyName)->first();
            
            if ($faculty) {
                foreach ($facultyPrograms as $program) {
                    Program::firstOrCreate([
                        'name' => $program['name'],
                        'faculty_id' => $faculty->id,
                        'code' => $program['code'],
                    ]);
                }
            }
        }
    }
} 