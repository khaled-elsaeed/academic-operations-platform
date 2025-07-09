<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Settings;
use Illuminate\Support\Facades\Storage;
use Exception;

class EnrollmentTemplateService
{
    private $templatePath;
    private $outputPath;
    /**
     * The maximum number of courses that can be filled in the enrollment document.
     */
    private const MAX_COURSES = 16;

    public function __construct()
    {
        $this->templatePath = storage_path('app/private/');
        $this->outputPath = storage_path('app/generated/');
        
        // Create directories if they don't exist
        if (!file_exists($this->templatePath)) {
            mkdir($this->templatePath, 0755, true);
        }
        if (!file_exists($this->outputPath)) {
            mkdir($this->outputPath, 0755, true);
        }
    }

    /**
     * Generate enrollment document for student
     *
     * @param array $studentData
     * @param array $enrollments
     * @param string $outputName
     * @return string Path to generated file
     */
    public function generateEnrollmentDocument(array $studentData, array $enrollments, string $outputName = null): string
    {
        $templateFile = $this->templatePath . 'enrollment_template.docx';
        
        if (!file_exists($templateFile)) {
            throw new Exception("Template file not found: enrollment_template.docx");
        }

        $outputName = $outputName ?? 'enrollment_' . $studentData['academic_number'] . '_' . time() . '.docx';
        $outputFile = $this->outputPath . $outputName;

        // Load template
        $templateProcessor = new TemplateProcessor($templateFile);

        // Replace student information
        $this->replaceStudentData($templateProcessor, $studentData);

        // Handle enrollment table
        $this->handleEnrollmentTable($templateProcessor, $enrollments);

        // Save the document
        $templateProcessor->saveAs($outputFile);

        return $outputFile;
    }

    /**
     * Replace student data in template
     *
     * @param TemplateProcessor $templateProcessor
     * @param array $studentData
     */
    private function replaceStudentData(TemplateProcessor $templateProcessor, array $studentData): void
    {
        // Replace student information placeholders
        $templateProcessor->setValue('academic_number', $studentData['academic_number'] ?? '');
        $templateProcessor->setValue('student_name', $studentData['student_name'] ?? '');
        $templateProcessor->setValue('national_id', $studentData['national_id'] ?? '');
        $templateProcessor->setValue('program_name', $studentData['program_name'] ?? '');
        $templateProcessor->setValue('student_phone', $studentData['student_phone'] ?? '');
        $templateProcessor->setValue('level', $studentData['level'] ?? 'الأول');
        $templateProcessor->setValue('academic_year', $studentData['academic_year'] ?? '2024-2025');
        $templateProcessor->setValue('semester', $studentData['semester'] ?? 'الصيف');
    }

    /**
     * Handle enrollment table data
     *
     * @param TemplateProcessor $templateProcessor
     * @param array $enrollments
     */
    private function handleEnrollmentTable(TemplateProcessor $templateProcessor, array $enrollments): void
    {
        if (empty($enrollments)) {
            for ($i = 1; $i <= self::MAX_COURSES; $i++) {
                $templateProcessor->setValue("course_code_{$i}", '');
                $templateProcessor->setValue("course_name_{$i}", '');
                $templateProcessor->setValue("course_hours_{$i}", '');
            }
            $templateProcessor->setValue('total_hours', '0');
            return;
        }

        $totalHours = 0;
        
        for ($i = 1; $i <= self::MAX_COURSES; $i++) {
            if (isset($enrollments[$i - 1])) {
                $enrollment = $enrollments[$i - 1];
                $templateProcessor->setValue("course_code_{$i}", $enrollment['course_code'] ?? '');
                $templateProcessor->setValue("course_name_{$i}", $enrollment['course_name'] ?? '');
                $templateProcessor->setValue("course_hours_{$i}", $enrollment['course_hours'] ?? '');
                $totalHours += (int)($enrollment['course_hours'] ?? 0);
            } else {
                // Clear empty rows
                $templateProcessor->setValue("course_code_{$i}", '');
                $templateProcessor->setValue("course_name_{$i}", '');
                $templateProcessor->setValue("course_hours_{$i}", '');
            }
        }

        // Set total hours
        $templateProcessor->setValue('total_hours', $totalHours);
    }

    /**
     * Download generated document
     *
     * @param string $filePath
     * @param string $downloadName
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadDocument(string $filePath, string $downloadName = null): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $downloadName = $downloadName ?? basename($filePath);
        
        return response()->download($filePath, $downloadName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Stream document for immediate download
     *
     * @param array $studentData
     * @param array $enrollments
     * @param string $downloadName
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function streamEnrollmentDocument(array $studentData, array $enrollments, string $downloadName = null): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filePath = $this->generateEnrollmentDocument($studentData, $enrollments);
        $downloadName = $downloadName ?? "enrollment_{$studentData['academic_number']}.docx";
        
        return $this->downloadDocument($filePath, $downloadName);
    }
}

