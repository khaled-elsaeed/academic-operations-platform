<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $adminUser = User::factory()->admin()->create();
        $adminUser->assignRole('admin');

        // Create advisor users
        $advisorUsers = User::factory()->count(3)->create();
        foreach ($advisorUsers as $user) {
            $user->assignRole('advisor');
        }

        // Create some users without roles
        User::factory()->count(2)->create();
    }
} 