<?php

namespace Database\Seeders;

use App\Models\CreditHoursException;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Seeder;

class CreditHoursExceptionSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing students, terms, and users
        $students = Student::all();
        $terms = Term::all();
        $users = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        if ($students->isEmpty() || $terms->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Skipping CreditHoursExceptionSeeder: Missing required data (students, terms, or admin users)');
            return;
        }

        // Create some sample credit hours exceptions
        $reasons = [
            'Academic excellence - student maintains high GPA',
            'Graduating student - needs additional hours to complete degree',
            'Special circumstances approved by academic advisor',
            'Transfer student with credit evaluation',
            'Research project participation',
            'Internship program requirement',
            'Academic probation recovery plan',
            'Special needs accommodation',
            'Athletic scholarship requirements',
            'International student program'
        ];

        for ($i = 0; $i < 15; $i++) {
            $student = $students->random();
            $term = $terms->random();
            $user = $users->random();

            // Check if there's already an active exception for this student and term
            $existingException = CreditHoursException::where('student_id', $student->id)
                ->where('term_id', $term->id)
                ->where('is_active', true)
                ->first();

            if (!$existingException) {
                CreditHoursException::create([
                    'student_id' => $student->id,
                    'term_id' => $term->id,
                    'granted_by' => $user->id,
                    'additional_hours' => rand(1, 6),
                    'reason' => $reasons[array_rand($reasons)],
                    'is_active' => rand(1, 10) <= 8, // 80% chance of being active
                ]);
            }
        }

        $this->command->info('Credit hours exceptions seeded successfully!');
    }
} 