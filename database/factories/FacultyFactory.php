<?php

namespace Database\Factories;

use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;

class FacultyFactory extends Factory
{
    protected $model = Faculty::class;

    public function definition(): array
    {
        // Generate a realistic Egyptian faculty name without using example arrays
        $facultyPrefix = 'Faculty of ';
        $field = ucfirst($this->faker->unique()->word());
        $university = 'Cairo University';
        return [
            'name' => $facultyPrefix . $field . ', ' . $university,
        ];
    }
} 