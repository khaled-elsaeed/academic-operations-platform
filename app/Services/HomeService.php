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

    private const FACULTY_NAME = 'Faculty of Computer Science & Engineering';
    private const CGPA_RANGES = [
        '0.0-1.0' => [0.0, 1.0],
        '1.0-2.0' => [1.0, 2.0],
        '2.0-2.5' => [2.0, 2.5],
        '2.5-3.0' => [2.5, 3.0],
        '3.0-3.5' => [3.0, 3.5],
        '3.5-4.0' => [3.5, 4.0],
    ];
    
    /**
     * Get admin dashboard statistics for Faculty of Computer Science & Engineering
     * 
     * @return array Dashboard statistics including counts and last updated times
     */
    public function getAdminDashboardStats(): array
    {
        // ===== STUDENTS STATISTICS =====
        $studentsCount = $this->getStudentsCount();
        $studentsLastUpdated = $this->getStudentsLastUpdated();

        // ===== FACULTY STATISTICS =====
        $facultyCount = $this->getFacultyCount();
        $facultyLastUpdated = $this->getFacultyLastUpdated();

        // ===== PROGRAMS STATISTICS =====
        $programsCount = $this->getProgramsCount();
        $programsLastUpdated = $this->getProgramsLastUpdated();

        // ===== COURSES STATISTICS =====
        $coursesCount = $this->getCoursesCount();
        $coursesLastUpdated = $this->getCoursesLastUpdated();

        // ===== RETURN ORGANIZED DASHBOARD DATA =====
        return [
            'students' => [
                'total' => $studentsCount,
                'lastUpdatedTime' => $studentsLastUpdated,
            ],
            'faculty' => [
                'total' => $facultyCount,
                'lastUpdatedTime' => $facultyLastUpdated,
            ],
            'programs' => [
                'total' => $programsCount,
                'lastUpdatedTime' => $programsLastUpdated,
            ],
            'courses' => [
                'total' => $coursesCount,
                'lastUpdatedTime' => $coursesLastUpdated,
            ],
            'levelDistribution' => $this->getAdminLevelDistribution(),
            'cgpaDistribution' => $this->getAdminCGPADistribution(),
        ];
    }

    /**
     * Get count of students in specified faculty
     * 
     * @return int
     */
    private function getStudentsCount(): int
    {
        return Student::whereHas('program.faculty', function ($query) {
            $query->where('name', self::FACULTY_NAME);
        })->count();
    }

    /**
     * Get last updated time for students in specified faculty
     * 
     * @return string|null
     */
    private function getStudentsLastUpdated(): ?string
    {
        $lastUpdated = Student::whereHas('program.faculty', function ($query) {
            $query->where('name', self::FACULTY_NAME);
        })->max('updated_at');

        return $lastUpdated ? formatDate($lastUpdated) : null;
    }

    /**
     * Get count of faculty by name
     * 
     * @return int
     */
    private function getFacultyCount(): int
    {
        return Faculty::where('name', self::FACULTY_NAME)->count();
    }

    /**
     * Get last updated time for all faculties (filtered by FACULTY_NAME)
     * 
     * @return string|null
     */
    private function getFacultyLastUpdated(): ?string
    {
        $lastUpdated = Faculty::where('name', self::FACULTY_NAME)->max('updated_at');
        return $lastUpdated ? formatDate($lastUpdated) : null;
    }

    /**
     * Get count of programs in specified faculty
     * 
     * @return int
     */
    private function getProgramsCount(): int
    {
        return Program::whereHas('faculty', function ($query) {
            $query->where('name', self::FACULTY_NAME);
        })->count();
    }

    /**
     * Get last updated time for programs in specified faculty
     * 
     * @return string|null
     */
    private function getProgramsLastUpdated(): ?string
    {
        $lastUpdated = Program::whereHas('faculty', function ($query) {
            $query->where('name', self::FACULTY_NAME);
        })->max('updated_at');

        return $lastUpdated ? formatDate($lastUpdated) : null;
    }

    /**
     * Get count of courses in specified faculty
     * 
     * @return int
     */
    private function getCoursesCount(): int
    {
        return Course::whereHas('faculty', function ($query) {
            $query->where('name', self::FACULTY_NAME);
        })->count();
    }

    /**
     * Get last updated time for courses in specified faculty
     * 
     * @return string|null
     */
    private function getCoursesLastUpdated(): ?string
    {
        $lastUpdated = Course::whereHas('faculty', function ($query) {
            $query->where('name', self::FACULTY_NAME);
        })->max('updated_at');

        return $lastUpdated ? formatDate($lastUpdated) : null;
    }

    /**
     * Get advisor dashboard statistics.
     */
    public function getAdvisorDashboardStats(): array
    {
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
        $data = [];
        $labels = [];

        foreach (self::CGPA_RANGES as $range => $bounds) {
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
        $data = [];
        $labels = [];

        foreach (self::CGPA_RANGES as $range => $bounds) {
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