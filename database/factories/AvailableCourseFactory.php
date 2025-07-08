<?php

namespace Database\Factories;

use App\Models\AvailableCourse;
use App\Models\Course;
use App\Models\Term;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailableCourseFactory extends Factory
{
    protected $model = AvailableCourse::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'term_id' => Term::factory(),
            'program_id' => Program::factory(),
            'level' => $this->faker->numberBetween(1, 5),
            'capacity' => $this->faker->numberBetween(20, 200),
        ];
    }
} 