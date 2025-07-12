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
        return [
            'student_id' => Student::factory(),
            'term_id' => Term::factory(),
            'granted_by' => User::factory(),
            'additional_hours' => $this->faker->numberBetween(1, 6),
            'reason' => $this->faker->optional(0.8)->sentence(),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
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
} 