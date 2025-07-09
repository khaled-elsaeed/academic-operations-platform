<?php

namespace Database\Factories;

use App\Models\AvailableCourse;
use App\Models\Course;
use App\Models\Term;
use App\Models\Program;
use App\Models\CourseEligibility;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailableCourseFactory extends Factory
{
    protected $model = AvailableCourse::class;

    public function definition(): array
    {
        $min = $this->faker->numberBetween(1, 10);
        $max = $this->faker->numberBetween($min, $min + 20);
        return [
            'course_id' => Course::factory(),
            'term_id' => Term::factory(),
            'min_capacity' => $min,
            'max_capacity' => $max,
            'is_universal' => $this->faker->boolean(20), // 20% universal
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (AvailableCourse $availableCourse) {
            if (!$availableCourse->is_universal) {
                $programIds = \App\Models\Program::inRandomOrder()->limit(rand(1, 3))->pluck('id');
                $levelIds = \App\Models\Level::inRandomOrder()->limit(rand(1, 3))->pluck('id');
                foreach ($programIds as $programId) {
                    foreach ($levelIds as $levelId) {
                        CourseEligibility::create([
                            'available_course_id' => $availableCourse->id,
                            'program_id' => $programId,
                            'level_id' => $levelId,
                        ]);
                    }
                }
            }
        });
    }
} 