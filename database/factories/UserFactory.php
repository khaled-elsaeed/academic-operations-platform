<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $arabicNames = ['أحمد', 'محمد', 'محمود', 'مصطفى', 'عبدالله', 'سارة', 'فاطمة', 'مريم', 'ياسمين', 'نور'];
        $arabicSurnames = ['حسن', 'سعيد', 'عبدالعزيز', 'علي', 'إبراهيم', 'يوسف', 'رمضان', 'سليمان', 'فاروق', 'منصور'];
        $firstName = $this->faker->randomElement(['Ahmed','Mohamed','Mahmoud','Mostafa','Abdallah','Sara','Fatma','Mariam','Yasmin','Nour']);
        $lastName = $this->faker->randomElement(['Hassan','Saeed','Abdelaziz','Ali','Ibrahim','Youssef','Ramadan','Suleiman','Farouk','Mansour']);
        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower($firstName) . '.' . strtolower($lastName) . $this->faker->unique()->numberBetween(1, 9999) . '@cu.edu.eg',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
