<?php

namespace App\Http\Controllers;

use App\Services\EnrollmentTemplateService;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnrollmentDocumentController extends Controller
{
    private EnrollmentTemplateService $enrollmentTemplateService;

    public function __construct(EnrollmentTemplateService $enrollmentTemplateService)
    {
        $this->enrollmentTemplateService = $enrollmentTemplateService;
    }

    /**
     * Generate and download enrollment document for a student
     *
     * @param int $studentId
     * @return \Illuminate\Http\Response
     */
    public function downloadEnrollmentDocument($studentId)
    {
        try {
            $termId = request()->query('term_id');

            if (!$termId) {
                return response()->json([
                    'message' => 'يرجى تحديد الفصل الدراسي.'
                ], 400);
            }

            // Get student with relationships
            $student = Student::with(['enrollments.course', 'program', 'level'])
                ->findOrFail($studentId);

            $term = Term::findOrFail($termId);

            $validLevels = ['1', '2', '3', '4', '5'];
            $termSeason = [
                'fall' => 'الخريف',
                'spring' => 'الربيع',
                'summer' => 'الصيف'
            ];

            $levelName = $student->level->name ?? null;

            $studentData = [
                'academic_number' => $student->academic_id,
                'student_name' => $student->name_ar ?? $student->name_en,
                'national_id' => $student->national_id ?? '',
                'program_name' => $student->program->name ?? '',
                'student_phone' => $student->phone ?? '',
                'level' => in_array($levelName, $validLevels) ? $levelName : 'الأول',
                'academic_year' => $term->year,
                'semester' => $termSeason[$term->season] ?? '',
                'cgpa' => $student->cgpa ?? 0.0,
            ];

            // Map enrollments
            $enrollments = $student->enrollments->map(function ($enrollment) {
                return [
                    'course_code' => $enrollment->course->code ?? '',
                    'course_name' => $enrollment->course->title ?? '',
                    'course_hours' => $enrollment->course->credit_hours ?? 0,
                ];
            })->toArray();

            // Generate and stream document
            return $this->enrollmentTemplateService->streamEnrollmentDocument(
                $studentData,
                $enrollments,
                "enrollment_{$student->academic_id}.docx"
            );

        } catch (\Exception $e) {
            Log::error('Error generating enrollment document', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'student_id' => $studentId,
                'term_id' => request()->query('term_id')
            ]);

            return response()->json([
                'message' => 'حدث خطأ أثناء توليد مستند القيد. برجاء المحاولة لاحقاً.'
            ], 500);
        }
    }
}
