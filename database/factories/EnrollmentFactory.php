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
        return [
            'student_id' => Student::factory(),
            'course_id' => Course::factory(),
            'term_id' => Term::factory(),
            'score' => $this->faker->optional(0.8)->randomFloat(2, 60, 100), // 80% chance of having a score, between 60-100
        ];
    }
} 