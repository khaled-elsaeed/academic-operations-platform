<?php

namespace Database\Seeders;

use App\Models\AcademicAdvisorAccess;
use App\Models\User;
use App\Models\Level;
use App\Models\Program;
use Illuminate\Database\Seeder;

class AcademicAdvisorAccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users with advisor role
        $advisors = User::whereHas('roles', function ($query) {
            $query->where('name', 'advisor');
        })->get();

        // Get levels and programs
        $levels = Level::all();
        $programs = Program::all();

        if ($advisors->isEmpty() || $levels->isEmpty() || $programs->isEmpty()) {
            $this->command->warn('Skipping AcademicAdvisorAccess seeding: Missing advisors, levels, or programs');
            $this->command->info('Advisors found: ' . $advisors->count());
            $this->command->info('Levels found: ' . $levels->count());
            $this->command->info('Programs found: ' . $programs->count());
            return;
        }

        // Create access rules for each advisor
        foreach ($advisors as $advisor) {
            // Assign 2-4 random level-program combinations per advisor
            $combinations = $this->getRandomCombinations($levels, $programs, rand(2, 4));
            
            foreach ($combinations as $combination) {
                AcademicAdvisorAccess::create([
                    'advisor_id' => $advisor->id,
                    'level_id' => $combination['level']->id,
                    'program_id' => $combination['program']->id,
                    'is_active' => rand(1, 10) <= 8, // 80% chance of being active
                ]);
            }
        }

        $this->command->info('AcademicAdvisorAccess seeded successfully!');
        $this->command->info('Created access rules for ' . $advisors->count() . ' advisors');
    }

    /**
     * Get random combinations of levels and programs.
     */
    private function getRandomCombinations($levels, $programs, $count): array
    {
        $combinations = [];
        $usedCombinations = [];

        while (count($combinations) < $count && count($usedCombinations) < ($levels->count() * $programs->count())) {
            $level = $levels->random();
            $program = $programs->random();
            
            $key = $level->id . '-' . $program->id;
            
            if (!in_array($key, $usedCombinations)) {
                $combinations[] = [
                    'level' => $level,
                    'program' => $program
                ];
                $usedCombinations[] = $key;
            }
        }

        return $combinations;
    }
} 