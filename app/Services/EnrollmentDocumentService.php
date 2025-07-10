<?php

namespace App\Services;

use App\Models\Student;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use PhpOffice\PhpWord\TemplateProcessor;

class EnrollmentDocumentService
{
    /**
     * Constants
     */
    private const MAX_COURSES_WORD = 16;
    private const MAX_COURSES_PDF = 10;
    private const MEMORY_LIMIT = '512M';
    private const EXECUTION_TIME = 300;
    private const TEMPLATE_FILENAME = 'enrollment_template.docx';
    private const PDF_STORAGE_PATH = 'documents/enrollments/pdf/';
    private const WORD_STORAGE_PATH = 'documents/enrollments/word/';
    private const DEFAULT_ACADEMIC_YEAR = '2024-2025';
    private const DEFAULT_SEMESTER = 'الصيف';
    private const DEFAULT_LEVEL = 'الأول';
    private const VALID_LEVELS = ['1', '2', '3', '4', '5', 'الأول', 'الثاني', 'الثالث', 'الرابع', 'الخامس'];
    private const FONT_NAME = 'kfgqpc';
    private const FONT_DIR = 'public/fonts/KFGQPC';
    private const FONT_FILES = [
        'R' => 'ArbFONTS-UthmanTN1-Ver10.otf',
        'B' => 'ArbFONTS-Uthman-tahaTN1-bold.otf',
        'I' => 'ArbFONTS-4_6.otf',
        'BI' => 'ArbFONTS-UthmanTN1B-Ver10.otf',
    ];
    private const COURSE_TITLE_LIMIT = 40;

    /**
     * File paths
     */
    private string $templatePath;
    private string $outputPath;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->templatePath = storage_path('app/private/');
        $this->outputPath = storage_path('app/generated/');
        $this->ensureDirectoriesExist();
    }

    // ============================================================================
    // PUBLIC METHODS - Main API
    // ============================================================================

    /**
     * Generate PDF enrollment document
     *
     * @param Student $student
     * @param int|null $termId
     * @return array
     * @throws Exception
     */
    public function generatePdf(Student $student, ?int $termId = null): array
    {
        try {
            $this->setResourceLimits();
            
            $studentWithEnrollments = $this->loadStudentWithEnrollments($student->id);
            $enrollments = $this->filterEnrollmentsByTerm($studentWithEnrollments->enrollments, $termId);
            
            $this->validateEnrollments($enrollments, $student->id, $termId);
            
            $pdfData = $this->prepareDataForPdf($studentWithEnrollments, $enrollments);
            $html = view('pdf.enrollment', $pdfData)->render();
            
            $filename = $this->generateFilename($student, 'pdf');
            $pdfContent = $this->generatePdfContent($html, $filename);
            
            $url = $this->saveToPublicStorage($pdfContent, self::PDF_STORAGE_PATH . $filename);
            
            return ['url' => $url, 'filename' => $filename];
            
        } catch (Exception $e) {
            $this->logError('generatePdf', $student->id, $termId, $e);
            throw $e;
        }
    }

    /**
     * Generate Word enrollment document
     *
     * @param Student $student
     * @param int|null $termId
     * @return array
     * @throws Exception
     */
    public function generateWord(Student $student, ?int $termId = null): array
    {
        try {
            $studentWithEnrollments = $this->loadStudentWithEnrollments($student->id);
            $enrollments = $this->filterEnrollmentsByTerm($studentWithEnrollments->enrollments, $termId);
            
            $this->validateEnrollments($enrollments, $student->id, $termId);
            
            $studentData = $this->prepareStudentData($studentWithEnrollments);
            $enrollmentsArray = $this->prepareEnrollmentsForWord($enrollments);
            
            $filename = $this->generateFilename($student, 'docx');
            $filePath = $this->generateWordDocument($studentData, $enrollmentsArray, $filename);
            
            $url = $this->saveFileToPublicStorage($filePath, self::WORD_STORAGE_PATH . $filename);
            
            return ['url' => $url, 'filename' => $filename];
            
        } catch (Exception $e) {
            $this->logError('generateWord', $student->id, $termId, $e);
            throw $e;
        }
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

    // ============================================================================
    // PRIVATE METHODS - Data Loading & Validation
    // ============================================================================

    /**
     * Load student with enrollments
     *
     * @param int $studentId
     * @return Student
     */
    private function loadStudentWithEnrollments(int $studentId): Student
    {
        return Student::with(['enrollments.course', 'program', 'level'])->findOrFail($studentId);
    }

    /**
     * Filter enrollments by term
     *
     * @param \Illuminate\Database\Eloquent\Collection $enrollments
     * @param int|null $termId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function filterEnrollmentsByTerm($enrollments, ?int $termId)
    {
        return $termId ? $enrollments->where('term_id', $termId) : $enrollments;
    }

    /**
     * Validate enrollments exist
     *
     * @param \Illuminate\Database\Eloquent\Collection $enrollments
     * @param int $studentId
     * @param int|null $termId
     * @throws Exception
     */
    private function validateEnrollments($enrollments, int $studentId, ?int $termId): void
    {
        if ($enrollments->isEmpty()) {
            $message = $termId 
                ? "No enrollments found for this student in the selected term"
                : "No enrollments found for this student";
            
            Log::warning('No enrollments found', [
                'student_id' => $studentId,
                'term_id' => $termId
            ]);
            
            throw new Exception($message);
        }
    }

    // ============================================================================
    // PRIVATE METHODS - Data Preparation
    // ============================================================================

    /**
     * Prepare student data for documents
     *
     * @param Student $student
     * @return array
     */
    private function prepareStudentData(Student $student): array
    {
        $levelName = $student->level->name ?? null;
        
        return [
            'academic_number' => $student->academic_id,
            'student_name' => $student->name_ar ?? $student->name_en,
            'national_id' => $student->national_id,
            'program_name' => $student->program->name ?? '',
            'student_phone' => $student->phone ?? '',
            'level' => (in_array($levelName, self::VALID_LEVELS) ? $levelName : self::DEFAULT_LEVEL),
            'academic_year' => self::DEFAULT_ACADEMIC_YEAR,
            'semester' => self::DEFAULT_SEMESTER
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
     * @return array
     */
    private function prepareDataForPdf(Student $student, $enrollments): array
    {
        $studentData = $this->prepareStudentData($student);
        $enrollmentData = [];
        $totalHours = 0;
        
        Log::info('Preparing enrollment data for PDF', [
            'student_id' => $student->id,
            'enrollments_count' => $enrollments->count(),
        ]);
        
        for ($i = 1; $i <= self::MAX_COURSES_PDF; $i++) {
            if (isset($enrollments[$i - 1])) {
                $enrollment = $enrollments[$i - 1];
                $enrollmentData["course_code_{$i}"] = $enrollment->course->code ?? '';
                $enrollmentData["course_title_{$i}"] = Str::limit($enrollment->course->title ?? '', self::COURSE_TITLE_LIMIT, '...');
                $enrollmentData["course_hours_{$i}"] = $enrollment->course->credit_hours ?? '';
                $totalHours += (int)($enrollment->course->credit_hours ?? 0);
                
                Log::info("Enrollment row {$i} populated", [
                    'course_code' => $enrollmentData["course_code_{$i}"],
                    'course_title' => $enrollmentData["course_title_{$i}"],
                    'course_hours' => $enrollmentData["course_hours_{$i}"],
                ]);
            } else {
                $enrollmentData["course_code_{$i}"] = '';
                $enrollmentData["course_title_{$i}"] = '';
                $enrollmentData["course_hours_{$i}"] = '';
                Log::info("Enrollment row {$i} left blank");
            }
        }
        
        $enrollmentData['total_hours'] = $totalHours;
        
        return array_merge($studentData, $enrollmentData);
    }

    // ============================================================================
    // PRIVATE METHODS - Document Generation
    // ============================================================================

    /**
     * Generate PDF content
     *
     * @param string $html
     * @param string $filename
     * @return string
     */
    private function generatePdfContent(string $html, string $filename): string
    {
        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];
        
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'fontDir' => array_merge($fontDirs, [
                base_path(self::FONT_DIR),
            ]),
            'fontdata' => $fontData + [
                self::FONT_NAME => array_merge(self::FONT_FILES, [
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ]),
            ],
            'default_font' => self::FONT_NAME,
        ]);
        
        $mpdf->WriteHTML($html);
        return $mpdf->Output($filename, 'S');
    }

    /**
     * Generate Word document
     *
     * @param array $studentData
     * @param array $enrollments
     * @param string $filename
     * @return string
     * @throws Exception
     */
    private function generateWordDocument(array $studentData, array $enrollments, string $filename): string
    {
        $templateFile = $this->templatePath . self::TEMPLATE_FILENAME;
        
        if (!file_exists($templateFile)) {
            throw new Exception("Template file not found: " . self::TEMPLATE_FILENAME);
        }
        
        $outputFile = $this->outputPath . $filename;
        
        $templateProcessor = new TemplateProcessor($templateFile);
        
        $this->replaceStudentDataInTemplate($templateProcessor, $studentData);
        $this->handleEnrollmentTable($templateProcessor, $enrollments);
        
        $templateProcessor->saveAs($outputFile);
        
        return $outputFile;
    }

    /**
     * Replace student data in template
     *
     * @param TemplateProcessor $templateProcessor
     * @param array $studentData
     */
    private function replaceStudentDataInTemplate(TemplateProcessor $templateProcessor, array $studentData): void
    {
        foreach ($studentData as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }
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
            $this->fillEmptyEnrollmentTable($templateProcessor);
            return;
        }
        
        $totalHours = 0;
        
        for ($i = 1; $i <= self::MAX_COURSES_WORD; $i++) {
            if (isset($enrollments[$i - 1])) {
                $enrollment = $enrollments[$i - 1];
                $templateProcessor->setValue("course_code_{$i}", $enrollment['course_code'] ?? '');
                $templateProcessor->setValue("course_name_{$i}", $enrollment['course_name'] ?? '');
                $templateProcessor->setValue("course_hours_{$i}", $enrollment['course_hours'] ?? '');
                $totalHours += (int)($enrollment['course_hours'] ?? 0);
            } else {
                $templateProcessor->setValue("course_code_{$i}", '');
                $templateProcessor->setValue("course_name_{$i}", '');
                $templateProcessor->setValue("course_hours_{$i}", '');
            }
        }
        
        $templateProcessor->setValue('total_hours', $totalHours);
    }

    /**
     * Fill empty enrollment table
     *
     * @param TemplateProcessor $templateProcessor
     */
    private function fillEmptyEnrollmentTable(TemplateProcessor $templateProcessor): void
    {
        for ($i = 1; $i <= self::MAX_COURSES_WORD; $i++) {
            $templateProcessor->setValue("course_code_{$i}", '');
            $templateProcessor->setValue("course_name_{$i}", '');
            $templateProcessor->setValue("course_hours_{$i}", '');
        }
        $templateProcessor->setValue('total_hours', '0');
    }

    // ============================================================================
    // PRIVATE METHODS - Utilities
    // ============================================================================

    /**
     * Ensure required directories exist
     */
    private function ensureDirectoriesExist(): void
    {
        if (!file_exists($this->templatePath)) {
            mkdir($this->templatePath, 0755, true);
        }
        if (!file_exists($this->outputPath)) {
            mkdir($this->outputPath, 0755, true);
        }
    }

    /**
     * Set resource limits for PDF generation
     */
    private function setResourceLimits(): void
    {
        ini_set('max_execution_time', self::EXECUTION_TIME);
        ini_set('memory_limit', self::MEMORY_LIMIT);
    }

    /**
     * Generate filename
     *
     * @param Student $student
     * @param string $extension
     * @return string
     */
    private function generateFilename(Student $student, string $extension): string
    {
        return "enrollment_{$student->academic_id}_" . time() . ".{$extension}";
    }

    /**
     * Save content to public storage
     *
     * @param string $content
     * @param string $path
     * @return string
     */
    private function saveToPublicStorage(string $content, string $path): string
    {
        Storage::disk('public')->put($path, $content);
        return Storage::url($path);
    }

    /**
     * Save file to public storage
     *
     * @param string $filePath
     * @param string $publicPath
     * @return string
     */
    private function saveFileToPublicStorage(string $filePath, string $publicPath): string
    {
        Storage::disk('public')->put($publicPath, file_get_contents($filePath));
        return Storage::url($publicPath);
    }

    /**
     * Log error
     *
     * @param string $method
     * @param int $studentId
     * @param int|null $termId
     * @param Exception $exception
     */
    private function logError(string $method, int $studentId, ?int $termId, Exception $exception): void
    {
        Log::error("EnrollmentDocumentService@{$method}", [
            'student_id' => $studentId,
            'term_id' => $termId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}