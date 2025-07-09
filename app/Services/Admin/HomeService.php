<?php

namespace App\Services\Admin;

use App\Models\Student;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Course;

class HomeService
{
    public function getDashboardStats(): array
    {
        return [
            'students' => [
                'total' => Student::count(),
                'lastUpdatedTime' =>formatDate(Student::max('updated_at')),
            ],
            'faculty' => [
                'total' => Faculty::count(),
                'lastUpdatedTime' =>formatDate(Faculty::max('updated_at')),
            ],
            'programs' => [
                'total' => Program::count(),
                'lastUpdatedTime' =>formatDate(Program::max('updated_at')),
            ],
            'courses' => [
                'total' => Course::count(),
                'lastUpdatedTime' =>formatDate(Course::max('updated_at')),
            ],
        ];
    }
} 