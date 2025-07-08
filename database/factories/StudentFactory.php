<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'name_en' => $this->faker->name(),
            'name_ar' => $this->faker->name(),
            'academic_id' => $this->faker->unique()->numerify('A########'),
            'national_id' => $this->faker->unique()->numerify('###########'),
            'academic_email' => $this->faker->unique()->safeEmail(),
            'level' => $this->faker->randomElement(['Freshman', 'Sophomore', 'Junior', 'Senior']),
            'cgpa' => $this->faker->randomFloat(2, 2.0, 4.0),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'program_id' => Program::factory(),
        ];
    }
} 