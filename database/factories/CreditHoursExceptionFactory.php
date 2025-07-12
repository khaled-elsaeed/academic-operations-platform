<?php

namespace Database\Factories;

use App\Models\CreditHoursException;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditHoursExceptionFactory extends Factory
{
    protected $model = CreditHoursException::class;

    public function definition(): array
    {
        $reasons = [
            'Graduating student needs additional courses to complete degree requirements',
            'Student has high CGPA and academic advisor recommendation',
            'Special circumstances approved by academic committee',
            'Student needs to retake failed courses for graduation',
            'Academic advisor approved based on student performance',
            'Student has exceptional academic record',
            'Required for graduation in current semester',
            'Student has completed all prerequisites successfully',
            'Special permission granted by department head',
            'Student needs to maintain full-time status for scholarship',
            'Academic advisor recommendation for accelerated graduation',
            'Student has demonstrated exceptional academic performance',
            'Required for completing minor or concentration requirements',
            'Student has valid medical documentation for accommodation',
            'Special circumstances approved by faculty council',
        ];

        return [
            'student_id' => Student::factory(),
            'term_id' => Term::factory(),
            'granted_by' => User::factory(),
            'additional_hours' => $this->faker->randomElement([1, 2, 3, 4, 5, 6]),
            'reason' => $this->faker->optional(0.9)->randomElement($reasons),
            'is_active' => $this->faker->boolean(75), // 75% chance of being active
        ];
    }

    /**
     * Indicate that the exception is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the exception is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create exception for graduating students (higher additional hours).
     */
    public function graduating(): static
    {
        return $this->state(fn (array $attributes) => [
            'additional_hours' => $this->faker->randomElement([4, 5, 6]),
            'reason' => 'Graduating student needs additional courses to complete degree requirements',
            'is_active' => true,
        ]);
    }

    /**
     * Create exception for high CGPA students.
     */
    public function highCgpa(): static
    {
        return $this->state(fn (array $attributes) => [
            'additional_hours' => $this->faker->randomElement([2, 3, 4]),
            'reason' => 'Student has high CGPA and academic advisor recommendation',
            'is_active' => true,
        ]);
    }

    /**
     * Create exception for special circumstances.
     */
    public function specialCircumstances(): static
    {
        return $this->state(fn (array $attributes) => [
            'additional_hours' => $this->faker->randomElement([1, 2, 3]),
            'reason' => 'Special circumstances approved by academic committee',
            'is_active' => true,
        ]);
    }
} 