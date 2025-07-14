<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Course;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        $grades = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'F', 'FL', 'FD', 'P', 'AU', 'W', 'I'];
        return [
            'student_id' => Student::factory(),
            'course_id' => Course::factory(),
            'term_id' => Term::factory(),
            'grade' => $this->faker->optional(0.8)->randomElement($grades), // 80% chance of having a grade
        ];
    }
} 