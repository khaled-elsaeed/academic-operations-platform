<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Term;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use PhpOffice\PhpWord\TemplateProcessor;
use ZipArchive;
use Carbon\Carbon;

class EnrollmentDocumentService
{
    // ============================================================================
    // CONSTANTS
    // ============================================================================

    private const MAX_COURSES_WORD = 16;
    private const MAX_COURSES_PDF = 10;
    private const MEMORY_LIMIT = '512M';
    private const EXECUTION_TIME = 300;
    private const TEMPLATE_FILENAME = 'enrollment_template.docx';
    private const PDF_STORAGE_PATH = 'documents/enrollments/pdf/';
    private const WORD_STORAGE_PATH = 'documents/enrollments/word/';
    private const DEFAULT_ACADEMIC_YEAR = '';
    private const DEFAULT_SEMESTER = '';
    private const DEFAULT_LEVEL = 'الأول';
    private const VALID_LEVELS = ['1', '2', '3', '4', '5'];
    private const LEVEL_MAPPING = [
        '1' => 'الأول',
        '2' => 'الثاني',
        '3' => 'الثالث',
        '4' => 'الرابع',
        '5' => 'الخامس',
    ];
    private const FONT_NAME = 'kfgqpc';
    private const FONT_DIR = 'public/fonts/KFGQPC';
    private const FONT_FILES = [
        'R' => 'ArbFONTS-UthmanTN1-Ver10.otf',
        'B' => 'ArbFONTS-Uthman-tahaTN1-bold.otf',
        'I' => 'ArbFONTS-4_6.otf',
        'BI' => 'ArbFONTS-UthmanTN1B-Ver10.otf',
    ];
    private const COURSE_TITLE_LIMIT = 40;

    // ============================================================================
    // FILE PATHS
    // ============================================================================

    private string $templatePath;
    private string $outputPath;

    // ============================================================================
    // CONSTRUCTOR
    // ============================================================================

    public function __construct()
    {
        $this->templatePath = storage_path('app/private/');
        $this->outputPath = storage_path('app/generated/');
        $this->ensureDirectoriesExist();
    }

    // ============================================================================
    // PUBLIC METHODS
    // ============================================================================

    public function generatePdf(Student $student, ?int $termId = null): array
    {
        $this->setResourceLimits();

        $studentWithEnrollments = $this->loadStudentWithEnrollments($student->id);
        $enrollments = $this->filterEnrollmentsByTerm($studentWithEnrollments->enrollments, $termId);
        $this->validateEnrollments($enrollments, $student->id, $termId);

        $pdfData = $this->prepareDataForPdf($studentWithEnrollments, $enrollments);

        // Term info
        $term = $termId ? Term::find($termId) : null;
        $pdfData['semester'] = $term ? $this->mapSeason($term->season) : self::DEFAULT_SEMESTER;
        $pdfData['academic_year'] = $term ? $term->year : self::DEFAULT_ACADEMIC_YEAR;

        $pdfData['enrollment_date'] = \Carbon\Carbon::now('Africa/Cairo')->translatedFormat('l d/m/Y h:i A');
        $pdfData['cgpa'] = $student->cgpa ?? 0.0;

        $html = view('pdf.enrollment', $pdfData)->render();
        $filename = $this->generateFilename($student, 'pdf');
        $pdfContent = $this->generatePdfContent($html, $filename);
        $url = $this->saveToPublicStorage($pdfContent, self::PDF_STORAGE_PATH . $filename);

        return ['url' => $url, 'filename' => $filename];
    }

    public function generateWord(Student $student, ?int $termId = null): array
    {
        $studentWithEnrollments = $this->loadStudentWithEnrollments($student->id);
        $enrollments = $this->filterEnrollmentsByTerm($studentWithEnrollments->enrollments, $termId);
        $this->validateEnrollments($enrollments, $student->id, $termId);

        $studentData = $this->prepareStudentData($studentWithEnrollments);

        // Term info
        $term = $termId ? Term::find($termId) : null;
        $studentData['semester'] = $term ? $this->mapSeason($term->season) : self::DEFAULT_SEMESTER;
        $studentData['academic_year'] = $term ? $term->year : self::DEFAULT_ACADEMIC_YEAR;

        $enrollmentsArray = $this->prepareEnrollmentsForWord($enrollments);
        $filename = $this->generateFilename($student, 'docx');
        $filePath = $this->generateWordDocument($studentData, $enrollmentsArray, $filename);
        $url = $this->saveFileToPublicStorage($filePath, self::WORD_STORAGE_PATH . $filename);

        return ['url' => $url, 'filename' => $filename];
    }

    /**
     * Generate a professional timetable PDF for a student and optional term.
     *
     * @param Student $student
     * @param int|null $termId
     * @return array
     */
    public function generateTimetablePdf(Student $student, ?int $termId = null): array
    {
        $this->setResourceLimits();

        // Load student with enrollments and their schedules
        $studentWithEnrollments = Student::with(['enrollments.course', 'enrollments.enrollmentSchedules', 'program', 'level'])->findOrFail($student->id);
        $enrollments = $this->filterEnrollmentsByTerm($studentWithEnrollments->enrollments, $termId);
        $this->validateEnrollments($enrollments, $student->id, $termId);

        // Prepare a simple timetable data structure: day => [slots]
        $timetable = [];
        foreach ($enrollments as $enrollment) {
            $schedules = $enrollment->enrollmentSchedules ?? [];
            foreach ($schedules as $slot) {
                // slot is expected to have day, start_time, end_time, location
                $day = $slot->day ?? 'Unknown';
                $timetable[$day][] = [
                    'course_code' => $enrollment->course->code ?? '',
                    'course_title' => $enrollment->course->title ?? '',
                    'start_time' => $slot->start_time ?? '',
                    'end_time' => $slot->end_time ?? '',
                    'location' => $slot->location ?? '',
                    'type' => $slot->type ?? '',
                ];
            }
        }

        // Term info
        $term = $termId ? Term::find($termId) : null;
        $data = [
            'student' => $this->prepareStudentData($studentWithEnrollments),
            'program' => $studentWithEnrollments->program->name ?? '',
            'level' => $studentWithEnrollments->level->name ?? '',
            'timetable' => $timetable,
            'semester' => $term ? $this->mapSeason($term->season) : self::DEFAULT_SEMESTER,
            'academic_year' => $term ? $term->year : self::DEFAULT_ACADEMIC_YEAR,
            'generated_at' => Carbon::now('Africa/Cairo')->translatedFormat('l d/m/Y h:i A')
        ];

        $html = view('pdf.timetable', $data)->render();
        $filename = 'timetable_' . $student->academic_id . '_' . time() . '.pdf';
        $pdfContent = $this->generatePdfContent($html, $filename);
        $url = $this->saveToPublicStorage($pdfContent, self::PDF_STORAGE_PATH . $filename);

        return ['url' => $url, 'filename' => $filename];
    }

    public function hasEnrollments(Student $student, ?int $termId = null): bool
    {
        $query = $student->enrollments();
        if ($termId) $query->where('term_id', $termId);
        return $query->exists();
    }

    public function getEnrollmentStats(Student $student, ?int $termId = null): array
    {
        $query = $student->enrollments();
        if ($termId) $query->where('term_id', $termId);

        $enrollments = $query->with('course')->get();
        $totalHours = $enrollments->sum(fn($enrollment) => (int)($enrollment->course->credit_hours ?? 0));

        return [
            'total_courses' => $enrollments->count(),
            'total_hours' => $totalHours,
            'has_enrollments' => $enrollments->count() > 0
        ];
    }

    public function getDownloadOptions(Student $student): array
    {
        return [
            'pdf' => route('students.download.pdf', $student->id),
            'word' => route('students.download.word', $student->id),
        ];
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function loadStudentWithEnrollments(int $studentId): Student
    {
        return Student::with(['enrollments.course', 'program', 'level'])->findOrFail($studentId);
    }

    private function filterEnrollmentsByTerm($enrollments, ?int $termId)
    {
        return $termId ? $enrollments->where('term_id', $termId) : $enrollments;
    }

    private function validateEnrollments($enrollments, int $studentId, ?int $termId): void
    {
        if ($enrollments->isEmpty()) {
            throw new Exception($termId 
                ? "No enrollments found for this student in the selected term" 
                : "No enrollments found for this student");
        }
    }

    private function prepareStudentData(Student $student): array
    {
        $levelName = $student->level->name ?? null;
        return [
            'academic_number' => $student->academic_id,
            'student_name' => $student->name_ar ?? $student->name_en,
            'national_id' => $student->national_id ?? '',
            'program_name' => $student->program->name ?? '',
            'student_phone' => $student->phone ?? '',
            'level' => $this->mapLevel($levelName),
            'academic_year' => self::DEFAULT_ACADEMIC_YEAR,
            'semester' => self::DEFAULT_SEMESTER
        ];
    }

    private function mapLevel(?string $levelName): string
    {
        if (!$levelName || !in_array($levelName, self::VALID_LEVELS)) {
            return self::LEVEL_MAPPING[self::DEFAULT_LEVEL];
        }
        return self::LEVEL_MAPPING[$levelName] ?? self::LEVEL_MAPPING[self::DEFAULT_LEVEL];
    }

    private function mapSeason(?string $season): string
    {
        $seasonMapping = [
            'fall' => 'الخريف',
            'spring' => 'الربيع',
            'summer' => 'الصيف'
        ];
        return $seasonMapping[$season] ?? self::DEFAULT_SEMESTER;
    }

    private function prepareEnrollmentsForWord($enrollments): array
    {
        return $enrollments->map(fn($enrollment) => [
            'course_code' => $enrollment->course->code ?? '',
            'course_name' => $enrollment->course->title ?? '',
            'course_hours' => $enrollment->course->credit_hours ?? ''
        ])->toArray();
    }

    private function prepareDataForPdf(Student $student, $enrollments): array
    {
        $enrollments = $enrollments->values();
        $studentData = $this->prepareStudentData($student);
        $enrollmentData = [];
        $totalHours = 0;

        for ($i = 1; $i <= self::MAX_COURSES_PDF; $i++) {
            if (isset($enrollments[$i - 1])) {
                $enrollment = $enrollments[$i - 1];
                $enrollmentData["course_code_{$i}"] = $enrollment->course->code ?? '';
                $enrollmentData["course_title_{$i}"] = Str::limit($enrollment->course->title ?? '', self::COURSE_TITLE_LIMIT, '...');
                $enrollmentData["course_hours_{$i}"] = $enrollment->course->credit_hours ?? '';
                $totalHours += (int)($enrollment->course->credit_hours ?? 0);
            } else {
                $enrollmentData["course_code_{$i}"] = '';
                $enrollmentData["course_title_{$i}"] = '';
                $enrollmentData["course_hours_{$i}"] = '';
            }
        }

        $enrollmentData['total_hours'] = $totalHours;
        return array_merge($studentData, $enrollmentData);
    }

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
        
        // Add watermark to the PDF
        $this->addWatermark($mpdf);
        
        $mpdf->WriteHTML($html);
        return $mpdf->Output($filename, 'S');
    }

    /**
     * Add watermark to PDF using proper mPDF functionality
     *
     * @param \Mpdf\Mpdf $mpdf
     */
    private function addWatermark(\Mpdf\Mpdf $mpdf): void
    {
        $watermarkText = 'ACADCSE';

        // Set watermark text with custom alpha (transparency) - 0.1 = very transparent
        $mpdf->SetWatermarkText($watermarkText, 0.1);

        // Set watermark font to "DejaVuSansCondensed"
        $mpdf->watermark_font = 'DejaVuSansCondensed';

        // Enable watermark display
        $mpdf->showWatermarkText = true;
        $mpdf->showWatermarkImage = false;

        // Set additional watermark properties
        $mpdf->watermarkTextAlpha = 0.1;
        $mpdf->watermarkImgAlpha = 0.1;
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

    /**
     * Export enrollment documents for students matching filters.
     * Returns temp zip path and zip filename.
     *
     * @param array $filters
     * @return array
     * @throws Exception
     */
    public function exportDocumentsByFilters(array $filters): array
    {

        // Validate minimum required filters
        $termId = $filters['term_id'] ?? null;
        if (empty($termId)) {
            throw new Exception('term_id is required.');
        }

        // Determine mode: individual if academic_id or national_id provided; otherwise group
        $isIndividual = !empty($filters['academic_id']) || !empty($filters['national_id']);

        if ($isIndividual) {
            // Individual export must have academic_id or national_id (already true)
            $query = Student::query();
            if (!empty($filters['academic_id'])) {
                $query->where('academic_id', $filters['academic_id']);
            }
            if (!empty($filters['national_id'])) {
                $query->where('national_id', $filters['national_id']);
            }
        } else {
            // Group export must provide program_id and level_id
            if (empty($filters['program_id']) || empty($filters['level_id'])) {
                throw new Exception('For group export please provide both program_id and level_id.');
            }

            $query = Student::query();
            $query->where('program_id', $filters['program_id'])
                  ->where('level_id', $filters['level_id']);
        }

        $students = $query->get();

        if ($students->isEmpty()) {
            throw new Exception('No students found matching the provided filters.');
        }

        $files = [];

        foreach ($students as $student) {
            try {
                $result = $this->generatePdf($student, $termId);
                $publicPath = parse_url($result['url'], PHP_URL_PATH);
                $storagePath = public_path(ltrim($publicPath, '/'));
                if (file_exists($storagePath)) {
                    $files[$result['filename']] = $storagePath;
                }
            } catch (Exception $e) {
                // log and continue
                $this->logError('exportDocumentsByFilters', $student->id, $termId, $e);
            }
        }

        if (empty($files)) {
            throw new Exception('Failed to generate any documents.');
        }

        $zipName = 'enrollment_documents_' . Carbon::now()->format('Ymd_His') . '.zip';
        $tempZip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($tempZip, ZipArchive::CREATE) !== true) {
            throw new Exception('Failed to create zip archive.');
        }

        foreach ($files as $name => $path) {
            $zip->addFile($path, $name);
        }

        $zip->close();

        return ['temp_zip' => $tempZip, 'zip_name' => $zipName];
    }
}