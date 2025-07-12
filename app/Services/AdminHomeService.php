<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Course;
use App\Models\Level;
use Illuminate\Support\Facades\DB;

class AdminHomeService
{
    public function getDashboardStats(): array
    {
        return [
            'students' => [
                'total' => Student::count(),
                'lastUpdatedTime' => formatDate(Student::max('updated_at')),
            ],
            'faculty' => [
                'total' => Faculty::count(),
                'lastUpdatedTime' => formatDate(Faculty::max('updated_at')),
            ],
            'programs' => [
                'total' => Program::count(),
                'lastUpdatedTime' => formatDate(Program::max('updated_at')),
            ],
            'courses' => [
                'total' => Course::count(),
                'lastUpdatedTime' => formatDate(Course::max('updated_at')),
            ],
            'levelDistribution' => $this->getLevelDistribution(),
            'cgpaDistribution' => $this->getCGPADistribution(),
        ];
    }

    /**
     * Get level-wise student distribution for bar chart
     */
    private function getLevelDistribution(): array
    {
        $levelStats = Student::join('levels', 'students.level_id', '=', 'levels.id')
            ->select('levels.name', DB::raw('count(*) as count'))
            ->groupBy('levels.id', 'levels.name')
            ->orderBy('levels.name')
            ->get();

        return [
            'labels' => $levelStats->pluck('name')->toArray(),
            'data' => $levelStats->pluck('count')->toArray(),
        ];
    }

    /**
     * Get CGPA distribution for histogram
     */
    private function getCGPADistribution(): array
    {
        $cgpaRanges = [
            '0.0-1.0' => [0.0, 1.0],
            '1.0-2.0' => [1.0, 2.0],
            '2.0-2.5' => [2.0, 2.5],
            '2.5-3.0' => [2.5, 3.0],
            '3.0-3.5' => [3.0, 3.5],
            '3.5-4.0' => [3.5, 4.0],
        ];

        $data = [];
        $labels = [];

        foreach ($cgpaRanges as $range => $bounds) {
            $count = Student::whereBetween('cgpa', $bounds)->count();
            $labels[] = $range;
            $data[] = $count;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
} 