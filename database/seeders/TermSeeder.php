<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Term;

class TermSeeder extends Seeder
{
    public function run(): void
    {
        // Egyptian academic terms with code, season, and year (real data)
        $terms = [
            [
                'code' => '2252',
                'season' => 'spring',
                'year' => 2025,
                'is_active' => false,
            ],
            [
                'code' => '2253',
                'season' => 'summer',
                'year' => 2025,
                'is_active' => true,
            ],
        ];

        foreach ($terms as $term) {
            Term::create($term);
        }
    }
}