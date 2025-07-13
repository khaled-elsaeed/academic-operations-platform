<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Course;
use App\Models\Level;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class HomeService
{
    /**
     * Get admin dashboard statistics.
     */
    public function getAdminDashboardStats(): array
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
            'levelDistribution' => $this->getAdminLevelDistribution(),
            'cgpaDistribution' => $this->getAdminCGPADistribution(),
        ];
    }

    /**
     * Get advisor dashboard statistics.
     */
    public function getAdvisorDashboardStats(): array
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
            'levelDistribution' => $this->getAdvisorLevelDistribution(),
            'cgpaDistribution' => $this->getAdvisorCGPADistribution(),
        ];
    }

    /**
     * Get level-wise student distribution for admin (all students).
     */
    private function getAdminLevelDistribution(): array
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
     * Get CGPA distribution for admin (all students).
     */
    private function getAdminCGPADistribution(): array
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

    /**
     * Get level-wise student distribution for advisor (filtered by scope).
     */
    private function getAdvisorLevelDistribution(): array
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

    /**
     * Get CGPA distribution for advisor (filtered by scope).
     */
    private function getAdvisorCGPADistribution(): array
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