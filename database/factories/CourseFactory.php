<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('CSE###')),
            'title' => $this->faker->sentence(3),
            'credit_hours' => $this->faker->numberBetween(1, 4),
            'faculty_id' => Faculty::factory(),
        ];
    }
} 