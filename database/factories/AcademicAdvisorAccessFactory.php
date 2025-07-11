<?php

namespace Database\Factories;

use App\Models\AcademicAdvisorAccess;
use App\Models\User;
use App\Models\Level;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicAdvisorAccess>
 */
class AcademicAdvisorAccessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'advisor_id' => User::whereHas('roles', function ($query) {
                $query->where('name', 'advisor');
            })->inRandomOrder()->first()?->id ?? User::factory(),
            'access_type' => $this->faker->randomElement(['view', 'edit', 'full']),
            'level_id' => Level::inRandomOrder()->first()?->id ?? Level::factory(),
            'program_id' => Program::inRandomOrder()->first()?->id ?? Program::factory(),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the access rule is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the access rule is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
} 