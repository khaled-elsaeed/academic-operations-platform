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
        // Generate realistic Egyptian Arabic names without arrays
        $arabicFirstName = $this->faker->optional()->firstNameMale;
        if (!$arabicFirstName) {
            $arabicFirstName = $this->faker->optional()->firstNameFemale;
        }
        if (!$arabicFirstName) {
            $arabicFirstName = 'أحمد';
        }
        $arabicLastName = $this->faker->lastName;
        // Fallback to a common Egyptian surname if not available
        if (empty($arabicLastName) || preg_match('/[a-zA-Z]/', $arabicLastName)) {
            $arabicLastName = 'حسن';
        }

        // Gender selection without array
        $gender = (mt_rand(0, 1) === 0) ? 'male' : 'female';

        return [
            'name_en' => $this->faker->firstName . ' ' . $this->faker->lastName,
            'name_ar' => $arabicFirstName . ' ' . $arabicLastName . ' ' . $this->faker->unique()->numberBetween(100, 999),
            'academic_id' => 'A' . $this->faker->unique()->numerify('2########'),
            'national_id' => $this->faker->unique()->numerify('2###########'),
            'academic_email' => $this->faker->unique()->userName . '@student.cu.edu.eg',
            'level_id' => \App\Models\Level::inRandomOrder()->first()?->id ?? 1,
            'cgpa' => $this->faker->randomFloat(2, 2.0, 4.0),
            'gender' => $gender,
            'program_id' => \App\Models\Program::inRandomOrder()->first()?->id ?? 1,
        ];
    }
} 