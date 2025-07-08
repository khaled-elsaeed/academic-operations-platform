<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
        ]);

        // Seed Faculties
        \App\Models\Faculty::factory(3)
            ->hasPrograms(3)
            ->create();

        // Seed Terms
        $terms = \App\Models\Term::factory(3)->create();

        // Seed Courses for each Program
        \App\Models\Program::all()->each(function ($program) {
            \App\Models\Course::factory(8)->create(['program_id' => $program->id]);
        });

        // Seed Students for each Program
        \App\Models\Program::all()->each(function ($program) {
            \App\Models\Student::factory(10)->create(['program_id' => $program->id]);
        });

        // Seed Available Courses for each Term and Program
        $programs = \App\Models\Program::all();
        $courses = \App\Models\Course::all();
        foreach ($terms as $term) {
            foreach ($programs as $program) {
                $programCourses = $courses->where('program_id', $program->id);
                foreach ($programCourses as $course) {
                    \App\Models\AvailableCourse::factory()->create([
                        'course_id' => $course->id,
                        'term_id' => $term->id,
                        'program_id' => $program->id,
                    ]);
                }
            }
        }

        // Seed Enrollments for each Student in random courses and terms
        $students = \App\Models\Student::all();
        foreach ($students as $student) {
            $enrolledCourses = $courses->random(3);
            foreach ($enrolledCourses as $course) {
                \App\Models\Enrollment::factory()->create([
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'term_id' => $terms->random()->id,
                ]);
            }
        }

        // Seed Course Prerequisites (randomly assign prerequisites)
        foreach ($courses as $course) {
            $otherCourses = $courses->where('id', '!=', $course->id)->random(2);
            foreach ($otherCourses as $prereq) {
                \App\Models\CoursePrerequisite::factory()->create([
                    'course_id' => $course->id,
                    'prerequisite_id' => $prereq->id,
                ]);
            }
        }

    }
}
