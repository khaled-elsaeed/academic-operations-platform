<?php

namespace App\Http\Controllers;

use App\Services\EnrollmentTemplateService;
use App\Models\Student;
use App\Models\Enrollment;

class EnrollmentDocumentController extends Controller
{
    private $enrollmentTemplateService;

    public function __construct(EnrollmentTemplateService $enrollmentTemplateService)
    {
        $this->enrollmentTemplateService = $enrollmentTemplateService;
    }

    /**
     * Generate and download enrollment document
     */
    public function downloadEnrollmentDocument($studentId)
    {
        $termId = request()->query('term_id');
        // Get student with enrollments
        $student = Student::with(['enrollments.course', 'program', 'level'])->findOrFail($studentId);
        
        // Prepare student data
        $levelName = $student->level->name ?? null;
        $validLevels = ['1', '2', '3', '4', '5', 'الأول', 'الثاني', 'الثالث', 'الرابع', 'الخامس'];
        $studentData = [
            'academic_number' => $student->academic_id, // fixed field
            'student_name' => $student->name_ar ?? $student->name_en, // prefer Arabic name
            'national_id' => $student->national_id,
            'program_name' => $student->program->name ?? '',
            'student_phone' => $student->phone ?? '',
            'level' => (in_array($levelName, $validLevels) ? $levelName : 'الأول'),
            'academic_year' => '2024-2025',
            'semester' => 'الصيف'
        ];

        // Prepare enrollment data
        $enrollments = $student->enrollments;

        $enrollments = $enrollments->map(function($enrollment) {
            return [
                'course_code' => $enrollment->course->code,
                'course_name' => $enrollment->course->title,
                'course_hours' => $enrollment->course->credit_hours
            ];
        })->toArray();

        // Log the data used for debugging
        \Log::info('EnrollmentDocumentController@downloadEnrollmentDocument', [
            'studentData' => $studentData,
            'enrollments' => $enrollments
        ]);

        return $this->enrollmentTemplateService->streamEnrollmentDocument(
            $studentData,
            $enrollments,
            "enrollment_{$student->academic_id}.docx"
        );
    }


    /**
     * Convert level number to Arabic
     */
    private function getLevelInArabic($level): string
    {
        $levels = [
            1 => 'الأول',
            2 => 'الثاني', 
            3 => 'الثالث',
            4 => 'الرابع',
            5 => 'الخامس'
        ];

        return $levels[$level] ?? 'الأول';
    }
}