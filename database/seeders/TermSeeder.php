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
            [ 'code' => '2211', 'season' => 'fall',    'year' => 2021, 'is_active' => false ],
            [ 'code' => '2212', 'season' => 'spring',  'year' => 2022, 'is_active' => false ],
            [ 'code' => '2213', 'season' => 'summer',  'year' => 2022, 'is_active' => false ],
            [ 'code' => '2221', 'season' => 'fall',    'year' => 2022, 'is_active' => false ],
            [ 'code' => '2222', 'season' => 'spring',  'year' => 2023, 'is_active' => false ],
            [ 'code' => '2223', 'season' => 'summer',  'year' => 2023, 'is_active' => false ],
            [ 'code' => '2231', 'season' => 'fall',    'year' => 2023, 'is_active' => false ],
            [ 'code' => '2232', 'season' => 'spring',  'year' => 2024, 'is_active' => false ],
            [ 'code' => '2233', 'season' => 'summer',  'year' => 2024, 'is_active' => false ],
            [ 'code' => '2241', 'season' => 'fall',    'year' => 2024, 'is_active' => false ],
            [ 'code' => '2242', 'season' => 'spring',  'year' => 2025, 'is_active' => false ],
            [ 'code' => '2243', 'season' => 'summer',  'year' => 2025, 'is_active' => false ],
            [ 'code' => '2251', 'season' => 'fall',    'year' => 2025, 'is_active' => false ],
            [ 'code' => '2252', 'season' => 'spring',  'year' => 2025, 'is_active' => false ],
            [ 'code' => '2253', 'season' => 'summer',  'year' => 2025, 'is_active' => true  ],
        ];

        foreach ($terms as $term) {
            Term::updateOrCreate(
                ['code' => $term['code']],
                $term
            );
        }
    }
}