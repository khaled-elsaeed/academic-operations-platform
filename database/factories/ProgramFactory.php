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
        static $usedNames = [];
        static $usedCodes = [];

        $programs = [
            'Civil Engineering', 'Mechanical Engineering', 'Electrical Engineering', 'Business Administration',
            'Accounting', 'Physics', 'Chemistry', 'Law', 'English Literature', 'Pharmacy',
            'Computer Science', 'Information Systems', 'Agricultural Sciences', 'Medicine', 'Dentistry'
        ];

        // Generate a unique name
        do {
            $baseName = $this->faker->randomElement($programs);
            $suffix = $this->faker->unique()->numberBetween(100, 999);
            $name = "{$baseName} {$suffix} Program";
        } while (in_array($name, $usedNames));
        $usedNames[] = $name;

        // Generate a unique code
        do {
            $code = strtoupper($this->faker->bothify('EGP###'));
        } while (in_array($code, $usedCodes));
        $usedCodes[] = $code;

        return [
            'name' => $name,
            'code' => $code,
            'faculty_id' => \App\Models\Faculty::inRandomOrder()->first()?->id ?? 1,
        ];
    }
} 