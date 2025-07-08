<?php

namespace Database\Factories;

use App\Models\CoursePrerequisite;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoursePrerequisiteFactory extends Factory
{
    protected $model = CoursePrerequisite::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'prerequisite_id' => Course::factory(),
            'order' => $this->faker->numberBetween(1, 3),
        ];
    }
} 