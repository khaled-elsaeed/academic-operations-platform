<?php

namespace App\Services\Advisor;

use App\Models\Student;
use App\Models\Course;
use App\Models\Level;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class HomeService
{
    public function getDashboardStats(): array
    {
        // All Student::query() is already filtered by AcademicAdvisorScope for the logged-in advisor
        $advisees = Student::query();

        return [
            'advisees' => [
                'total' => $advisees->count(),
                'avgCgpa' => number_format($advisees->avg('cgpa'), 2),
                'lastUpdatedTime' => formatDate($advisees->max('updated_at')),
            ],
            'courses' => [
                'total' => Course::whereIn('id',
                    Enrollment::query()->pluck('course_id')->unique()
                )->count(),
                'lastUpdatedTime' => formatDate(now()), // Or use latest enrollment update
            ],
            'levelDistribution' => $this->getLevelDistribution(),
            'cgpaDistribution' => $this->getCGPADistribution(),
        ];
    }

    private function getLevelDistribution(): array
    {
        $levelStats = Student::query()
            ->join('levels', 'students.level_id', '=', 'levels.id')
            ->select('levels.name', DB::raw('count(*) as count'))
            ->groupBy('levels.id', 'levels.name')
            ->orderBy('levels.name')
            ->get();

        return [
            'labels' => $levelStats->pluck('name')->toArray(),
            'data' => $levelStats->pluck('count')->toArray(),
        ];
    }

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
            $count = Student::query()
                ->whereBetween('cgpa', $bounds)
                ->count();
            $labels[] = $range;
            $data[] = $count;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
} 