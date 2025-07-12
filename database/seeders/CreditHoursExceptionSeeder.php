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

        // Create credit hours exceptions using factory
        $this->createRegularExceptions($students, $terms, $users);
        $this->createGraduatingExceptions($students, $terms, $users);
        $this->createHighCgpaExceptions($students, $terms, $users);
        $this->createSpecialCircumstancesExceptions($students, $terms, $users);

        $this->command->info('Credit hours exceptions seeded successfully!');
    }

    /**
     * Create regular exceptions
     */
    private function createRegularExceptions($students, $terms, $users): void
    {
        for ($i = 0; $i < 10; $i++) {
            $student = $students->random();
            $term = $terms->random();
            $user = $users->random();

            // Check if there's already an exception for this student and term
            $existingException = CreditHoursException::forStudent($student->id)
                ->forTerm($term->id)
                ->first();

            if (!$existingException) {
                CreditHoursException::factory()->create([
                    'student_id' => $student->id,
                    'term_id' => $term->id,
                    'granted_by' => $user->id,
                ]);
            }
        }
    }

    /**
     * Create exceptions for graduating students
     */
    private function createGraduatingExceptions($students, $terms, $users): void
    {
        for ($i = 0; $i < 5; $i++) {
            $student = $students->random();
            $term = $terms->random();
            $user = $users->random();

            // Check if there's already an exception for this student and term
            $existingException = CreditHoursException::forStudent($student->id)
                ->forTerm($term->id)
                ->first();

            if (!$existingException) {
                CreditHoursException::factory()->graduating()->create([
                    'student_id' => $student->id,
                    'term_id' => $term->id,
                    'granted_by' => $user->id,
                ]);
            }
        }
    }

    /**
     * Create exceptions for high CGPA students
     */
    private function createHighCgpaExceptions($students, $terms, $users): void
    {
        // Get students with high CGPA (3.0 or above)
        $highCgpaStudents = $students->filter(function ($student) {
            return $student->cgpa >= 3.0;
        });

        if ($highCgpaStudents->count() > 0) {
            for ($i = 0; $i < min(3, $highCgpaStudents->count()); $i++) {
                $student = $highCgpaStudents->random();
                $term = $terms->random();
                $user = $users->random();

                // Check if there's already an exception for this student and term
                $existingException = CreditHoursException::forStudent($student->id)
                    ->forTerm($term->id)
                    ->first();

                if (!$existingException) {
                    CreditHoursException::factory()->highCgpa()->create([
                        'student_id' => $student->id,
                        'term_id' => $term->id,
                        'granted_by' => $user->id,
                    ]);
                }
            }
        }
    }

    /**
     * Create exceptions for special circumstances
     */
    private function createSpecialCircumstancesExceptions($students, $terms, $users): void
    {
        for ($i = 0; $i < 3; $i++) {
            $student = $students->random();
            $term = $terms->random();
            $user = $users->random();

            // Check if there's already an exception for this student and term
            $existingException = CreditHoursException::forStudent($student->id)
                ->forTerm($term->id)
                ->first();

            if (!$existingException) {
                CreditHoursException::factory()->specialCircumstances()->create([
                    'student_id' => $student->id,
                    'term_id' => $term->id,
                    'granted_by' => $user->id,
                ]);
            }
        }
    }
} 