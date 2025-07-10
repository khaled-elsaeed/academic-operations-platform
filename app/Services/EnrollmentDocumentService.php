<?php

namespace App\Services;

use App\Models\Student;
use App\Services\EnrollmentTemplateService;
use PDF;
use Exception;
use Illuminate\Support\Facades\Log;

class EnrollmentDocumentService
{
    private $enrollmentTemplateService;

    public function __construct(EnrollmentTemplateService $enrollmentTemplateService)
    {
        $this->enrollmentTemplateService = $enrollmentTemplateService;
    }

    /**
     * Download enrollment document as PDF
     *
     * @param Student $student
     * @param int|null $termId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadAsPdf(Student $student, ?int $termId = null)
    {
        try {
            // Set higher limits for PDF generation
            ini_set('max_execution_time', 300); // 5 minutes
            ini_set('memory_limit', '512M');
            
            // Get student with enrollments
            $studentWithEnrollments = Student::with(['enrollments.course', 'program', 'level'])->findOrFail($student->id);
            
            // Filter enrollments by term if provided
            $enrollments = $studentWithEnrollments->enrollments;
            if ($termId) {
                $enrollments = $enrollments->where('term_id', $termId);
            }

            // Prepare data for PDF
            $pdfData = $this->prepareDataForDocument($studentWithEnrollments, $enrollments, 'pdf');

            // Configure PDF settings for better performance
            $pdf = PDF::loadView('pdf.enrollment', $pdfData);
            
            
            $filename = "enrollment_{$student->academic_id}.pdf";
            
            return $pdf->stream($filename);

        } catch (Exception $e) {
            Log::error('EnrollmentDocumentService@downloadAsPdf', [
                'student_id' => $student->id,
                'term_id' => $termId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Download enrollment document as Word
     *
     * @param Student $student
     * @param int|null $termId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadAsWord(Student $student, ?int $termId = null)
    {
        try {
            // Get student with enrollments
            $studentWithEnrollments = Student::with(['enrollments.course', 'program', 'level'])->findOrFail($student->id);
            
            // Filter enrollments by term if provided
            $enrollments = $studentWithEnrollments->enrollments;
            if ($termId) {
                $enrollments = $enrollments->where('term_id', $termId);
            }

            // Prepare data for Word
            $studentData = $this->prepareStudentData($studentWithEnrollments);
            $enrollmentsArray = $this->prepareEnrollmentsForWord($enrollments);

            $filename = "enrollment_{$student->academic_id}.docx";
            
            return $this->enrollmentTemplateService->streamEnrollmentDocument(
                $studentData,
                $enrollmentsArray,
                $filename
            );

        } catch (Exception $e) {
            Log::error('EnrollmentDocumentService@downloadAsWord', [
                'student_id' => $student->id,
                'term_id' => $termId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Prepare student data for document generation
     *
     * @param Student $student
     * @return array
     */
    private function prepareStudentData(Student $student): array
    {
        $levelName = $student->level->name ?? null;
        $validLevels = ['1', '2', '3', '4', '5', 'الأول', 'الثاني', 'الثالث', 'الرابع', 'الخامس'];
        
        return [
            'academic_number' => $student->academic_id,
            'student_name' => $student->name_ar ?? $student->name_en,
            'national_id' => $student->national_id,
            'program_name' => $student->program->name ?? '',
            'student_phone' => $student->phone ?? '',
            'level' => (in_array($levelName, $validLevels) ? $levelName : 'الأول'),
            'academic_year' => '2024-2025',
            'semester' => 'الصيف'
        ];
    }

    /**
     * Prepare enrollment data for Word document
     *
     * @param \Illuminate\Database\Eloquent\Collection $enrollments
     * @return array
     */
    private function prepareEnrollmentsForWord($enrollments): array
    {
        return $enrollments->map(function($enrollment) {
            return [
                'course_code' => $enrollment->course->code ?? '',
                'course_name' => $enrollment->course->title ?? '',
                'course_hours' => $enrollment->course->credit_hours ?? ''
            ];
        })->toArray();
    }

    /**
     * Prepare data for PDF document
     *
     * @param Student $student
     * @param \Illuminate\Database\Eloquent\Collection $enrollments
     * @param string $format
     * @return array
     */
    private function prepareDataForDocument(Student $student, $enrollments, string $format): array
    {
        $studentData = $this->prepareStudentData($student);
        
        if ($format === 'pdf') {
            // Prepare enrollment data for PDF (10 rows)
            $enrollmentData = [];
            $totalHours = 0;
            
            for ($i = 1; $i <= 10; $i++) {
                if (isset($enrollments[$i - 1])) {
                    $enrollment = $enrollments[$i - 1];
                    $enrollmentData["course_code_{$i}"] = $enrollment->course->code ?? '';
                    $enrollmentData["course_name_{$i}"] = $enrollment->course->title ?? '';
                    $enrollmentData["course_hours_{$i}"] = $enrollment->course->credit_hours ?? '';
                    $totalHours += (int)($enrollment->course->credit_hours ?? 0);
                } else {
                    $enrollmentData["course_code_{$i}"] = '';
                    $enrollmentData["course_name_{$i}"] = '';
                    $enrollmentData["course_hours_{$i}"] = '';
                }
            }
            
            $enrollmentData['total_hours'] = $totalHours;
            
            // Merge student data with enrollment data
            return array_merge($studentData, $enrollmentData);
        }
        
        return $studentData;
    }

    /**
     * Get available download formats for a student
     *
     * @param Student $student
     * @return array
     */
    public function getDownloadOptions(Student $student): array
    {
        return [
            'pdf' => route('admin.students.download.pdf', $student->id),
            'word' => route('admin.students.download.word', $student->id),
        ];
    }

    /**
     * Check if student has enrollments
     *
     * @param Student $student
     * @param int|null $termId
     * @return bool
     */
    public function hasEnrollments(Student $student, ?int $termId = null): bool
    {
        $query = $student->enrollments();
        
        if ($termId) {
            $query->where('term_id', $termId);
        }
        
        return $query->exists();
    }

    /**
     * Get enrollment statistics for a student
     *
     * @param Student $student
     * @param int|null $termId
     * @return array
     */
    public function getEnrollmentStats(Student $student, ?int $termId = null): array
    {
        $query = $student->enrollments();
        
        if ($termId) {
            $query->where('term_id', $termId);
        }
        
        $enrollments = $query->with('course')->get();
        
        $totalHours = $enrollments->sum(function($enrollment) {
            return (int)($enrollment->course->credit_hours ?? 0);
        });
        
        return [
            'total_courses' => $enrollments->count(),
            'total_hours' => $totalHours,
            'has_enrollments' => $enrollments->count() > 0
        ];
    }
} 