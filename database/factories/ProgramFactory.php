<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word . ' Program',
            'code' => strtoupper($this->faker->unique()->bothify('??###')),
            'faculty_id' => Faculty::factory(),
        ];
    }
} 