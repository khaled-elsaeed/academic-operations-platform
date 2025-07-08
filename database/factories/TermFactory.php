<?php

namespace Database\Factories;

use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

class TermFactory extends Factory
{
    protected $model = Term::class;

    public function definition(): array
    {
        $seasons = ['Fall', 'Spring', 'Summer'];
        return [
            'season' => $this->faker->randomElement($seasons),
            'year' => $this->faker->year(),
            'is_active' => $this->faker->boolean(20),
        ];
    }
} 