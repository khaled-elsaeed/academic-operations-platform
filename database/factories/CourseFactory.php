<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Program;
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
            'program_id' => Program::factory(),
        ];
    }
} 