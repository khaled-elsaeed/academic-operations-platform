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
           
        ];

        foreach ($terms as $term) {
            Term::updateOrCreate(
                ['code' => $term['code']],
                $term
            );
        }
    }
}