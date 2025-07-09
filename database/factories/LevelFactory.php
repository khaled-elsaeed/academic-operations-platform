<?php

namespace Database\Factories;

use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

class LevelFactory extends Factory
{
    protected $model = Level::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word . ' Level',
            'code' => strtoupper($this->faker->unique()->bothify('L##')),
        ];
    }
} 