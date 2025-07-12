<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Level;

class LevelSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 5) as $number) {
            Level::create(['name' => $number]);
        }
    }
} 