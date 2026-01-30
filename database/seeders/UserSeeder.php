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
        // Create system user
        $user = User::create([
            'first_name' => 'System',
            'last_name' => 'User',
            'email' => User::SYSTEM_EMAIL,
            'password' => bcrypt('password'),
        ]);

        $user->assignRole('admin');

        // $adminUser = User::factory()->admin()->create();
        // $adminUser->assignRole('admin');
    }
} 