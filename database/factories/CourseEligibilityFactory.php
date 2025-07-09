<?php

namespace Database\Factories;

use App\Models\CourseEligibility;
use App\Models\AvailableCourse;
use App\Models\Program;
use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseEligibilityFactory extends Factory
{
    protected $model = CourseEligibility::class;

    public function definition(): array
    {
        return [
            'available_course_id' => AvailableCourse::factory(),
            'program_id' => Program::factory(),
            'level_id' => Level::factory(),
        ];
    }
} 