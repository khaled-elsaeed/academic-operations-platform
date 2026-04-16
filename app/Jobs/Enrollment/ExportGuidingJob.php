<?php

namespace App\Jobs\Enrollment;

use App\Models\Student;
use App\Models\Task;
use App\Models\Term;
use App\Services\Enrollment\EnrollmentGuidingService;
use App\Traits\Progressable;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Throwable;

/**
 * Exports guiding data directly via PhpSpreadsheet (no Maatwebsite\Excel)
 * to keep memory flat by writing rows one student at a time.
 */
class ExportGuidingJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Progressable;

    public int $timeout = 1800; // 30 minutes
    public bool $failOnTimeout = true;
    public int $tries = 1;

    private const HEADINGS = [
        'Student Name (EN)',
        'Student Name (AR)',
        'National ID',
        'Academic ID',
        'Academic Email',
        'Level',
        'Program',
        'CGPA',
        'Total Credit Hours',
        'Remaining Hours to Graduate',
        'Max Term Credit Hours',
        'Remaining Term Credit Hours',
        'Type',
        'Course Title',
        'Course Code',
        'Course Credit Hours',
        'Status',
        'Semester No.',
        'Note',
    ];

    private const COL_WIDTHS = [
        'A' => 24,
        'B' => 24,
        'C' => 16,
        'D' => 14,
        'E' => 28,
        'F' => 10,
        'G' => 22,
        'H' => 10,
        'I' => 20,
        'J' => 26,
        'K' => 22,
        'L' => 26,
        'M' => 38,
        'N' => 30,
        'O' => 14,
        'P' => 20,
        'Q' => 24,
        'R' => 14,
        'S' => 30,
    ];

    public function __construct(Task $task)
    {
        $this->setTask($task);
        $this->onQueue('background');
    }

    public function handle(): void
    {
        // Allow more memory for this heavy export
        ini_set('memory_limit', '2048M');

        try {
            $this->initProgress($this->task, 100, 'Initializing guiding export...');

            $parameters = $this->task->parameters ?? [];
            $termId = isset($parameters['term_id']) ? (int) $parameters['term_id'] : null;
            $programId = isset($parameters['program_id']) ? (int) $parameters['program_id'] : null;
            $levelId = isset($parameters['level_id']) ? (int) $parameters['level_id'] : null;

            // ── Collect student IDs ──────────────────────────────────────────
            $this->setProgress(5, 'Collecting students...');

            $query = Student::query();
            if ($programId)
                $query->where('program_id', $programId);
            if ($levelId)
                $query->where('level_id', $levelId);

            $studentIds = $query->orderBy('level_id')->orderBy('program_id')->orderBy('name_en')->pluck('id')->toArray();
            $totalStudents = count($studentIds);

            if ($totalStudents === 0) {
                $this->completeWithEmptyFile($termId);
                return;
            }

            // ── Create spreadsheet ──────────────────────────────────────────
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Sheet title
            $sheetTitle = 'Guiding';
            if ($termId) {
                $term = Term::find($termId);
                if ($term) {
                    $sheetTitle = 'Guiding - ' . ucfirst($term->season) . ' ' . $term->year;
                }
            }
            $sheet->setTitle(substr($sheetTitle, 0, 31)); // max 31 chars

            // Write headings at row 1
            foreach (self::HEADINGS as $col => $heading) {
                $sheet->setCellValue([$col + 1, 1], $heading);
            }

            // ── Process students one-by-one ─────────────────────────────────
            $currentRow = 2;
            $processed = 0;
            $courseCounts = [];

            foreach ($studentIds as $studentId) {
                // Check cancellation
                if ($processed % 20 === 0 && $this->isCancelled()) {
                    unset($spreadsheet);
                    return;
                }

                try {
                    $student = Student::with(['program', 'level'])->find($studentId);
                    if (!$student)
                        continue;

                    $guidingService = new EnrollmentGuidingService($student->id, $termId);
                    $guide = $guidingService->guide();

                    $currentRow = $this->writeStudentRows($sheet, $student, $guide, $currentRow, $courseCounts);

                    // Aggressive cleanup
                    unset($guidingService, $guide, $student);

                } catch (\Exception $e) {
                    // Skip this student, log and continue
                    \Log::warning("Guiding export: skipped student ID {$studentId}: " . $e->getMessage());
                    continue;
                }

                $processed++;

                // Update progress every 10 students
                if ($processed % 10 === 0) {
                    $pct = 10 + (int) (($processed / $totalStudents) * 75);
                    $this->setProgress(min($pct, 85), "Processing students ({$processed}/{$totalStudents})...");
                    gc_collect_cycles();
                }
            }

            // ── Apply styles ────────────────────────────────────────────────
            $this->setProgress(87, 'Applying styles...');
            $this->applyStyles($sheet, $currentRow - 1);

            // ── Create Course Counts Sheet ──────────────────────────────────
            $this->setProgress(89, 'Creating course counts sheet...');
            $countsSheet = $spreadsheet->createSheet();
            $countsSheet->setTitle('Course Counts');
            
            $countsSheet->setCellValue('A1', 'Course Code');
            $countsSheet->setCellValue('B1', 'Course Title');
            $countsSheet->setCellValue('C1', 'Recommended Students Count');
            
            // Sort by count descending
            uasort($courseCounts, function($a, $b) {
                return $b['count'] <=> $a['count'];
            });

            $cRow = 2;
            foreach ($courseCounts as $code => $data) {
                $countsSheet->setCellValue('A'.$cRow, $code);
                $countsSheet->setCellValue('B'.$cRow, $data['title']);
                $countsSheet->setCellValue('C'.$cRow, $data['count']);
                $cRow++;
            }

            // Apply basic styling to counts sheet
            $countsSheet->getColumnDimension('A')->setWidth(20);
            $countsSheet->getColumnDimension('B')->setWidth(50);
            $countsSheet->getColumnDimension('C')->setWidth(30);

            $countsSheet->getStyle('A1:C1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1D4ED8'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '93C5FD'],
                    ],
                ],
            ]);

            if ($cRow > 2) {
                $countsSheet->getStyle('A2:C' . ($cRow - 1))->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E5E7EB'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $countsSheet->getStyle('A2:A' . ($cRow - 1))->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $countsSheet->getStyle('C2:C' . ($cRow - 1))->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }
            
            // Set focus back to first sheet
            $spreadsheet->setActiveSheetIndex(0);

            // ── Write file ──────────────────────────────────────────────────
            $this->setProgress(90, 'Writing file...');

            $term = $termId ? Term::find($termId) : null;
            $filename = 'guiding_'
                . ($term ? str_replace(' ', '_', strtolower($term->name)) : 'all_terms')
                . '_' . now()->format('Ymd_His') . '.xlsx';

            $storagePath = Storage::disk('local')->path('exports');
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            $fullPath = $storagePath . '/' . $filename;
            $writer = new Xlsx($spreadsheet);
            $writer->save($fullPath);

            unset($writer, $spreadsheet);
            gc_collect_cycles();

            $permanentPath = 'exports/' . $filename;

            $this->task->update([
                'status' => 'completed',
                'progress' => 100,
                'result' => [
                    'file_path' => $permanentPath,
                    'filename' => $filename,
                    'download_url' => route('enrollments.exportGuiding.download', ['uuid' => $this->task->uuid]),
                ],
                'message' => "Guiding export completed. {$processed} students processed.",
            ]);

        } catch (Throwable $e) {
            $this->task->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'message' => 'Guiding export failed.',
            ]);
            throw $e;
        }
    }

    /**
     * Write all guiding rows for one student and return the next row number.
     */
    private function writeStudentRows($sheet, Student $student, array $guide, int $row, array &$courseCounts): int
    {
        $studyPlan = $guide['study_plan_courses'] ?? [];
        $missingCourses = $guide['missing_courses'] ?? [];
        $semesterNo = $studyPlan['semester_no'] ?? 'N/A';

        $trackCourse = function($course) use (&$courseCounts) {
            if (!$course) return;
            $code = $course->code ?? 'N/A';
            if ($code === 'N/A') return;
            if (!isset($courseCounts[$code])) {
                $courseCounts[$code] = [
                    'title' => $course->title ?? 'N/A',
                    'count' => 0
                ];
            }
            $courseCounts[$code]['count']++;
        };

        $termId = $this->task->parameters['term_id'] ?? null;
        $maxHours = 'N/A';
        $remainingHours = 'N/A';

        if ($termId) {
            try {
                $hoursService = app(\App\Services\Enrollment\Operations\RemainingCreditHoursService::class);
                $hoursInfo = $hoursService->getRemainingCreditHoursForStudent($student->id, $termId);
                $maxHours = $hoursInfo['max_allowed_hours'] ?? 'N/A';
                $remainingHours = $hoursInfo['remaining_hours'] ?? 'N/A';
            } catch (\Exception $e) {
                // Ignore if it fails due to CGPA/term mismatch
            }
        }

        $programGradHours = $student->program?->total_credit_hours;
        $remainingToGraduate = 'N/A';
        if ($programGradHours !== null && $student->taken_credit_hours !== null) {
            $remainingToGraduate = max(0, $programGradHours - $student->taken_credit_hours);
        }

        $info = [
            $student->name_en ?? 'N/A',
            $student->name_ar ?? 'N/A',
            $student->national_id ?? 'N/A',
            $student->academic_id ?? 'N/A',
            $student->academic_email ?? 'N/A',
            $student->level ? 'Level ' . $student->level->name : 'N/A',
            $student->program?->name ?? 'N/A',
            $student->cgpa ?? 'N/A',
            $student->taken_credit_hours ?? 'N/A',
            $remainingToGraduate,
            $maxHours,
            $remainingHours,
        ];

        // ── Recommended courses ──
        foreach ($studyPlan['courses'] ?? [] as $item) {
            $course = $item['course'];
            $trackCourse($course);
            $this->writeRow($sheet, $row++, $info, [
                'Recommended',
                $course->title ?? 'N/A',
                $course->code ?? 'N/A',
                $course->credit_hours ?? 'N/A',
                $this->studyPlanStatus($item),
                $semesterNo,
                $item['reason'] ?? '',
            ]);
        }

        // ── Elective pool ──
        $pool = $studyPlan['elective_info']['pool'] ?? [];
        $count = $studyPlan['elective_info']['count'] ?? 0;
        $codes = implode(', ', $studyPlan['elective_info']['codes'] ?? []);
        if ($pool && $count > 0) {
            $type = "Elective ({$codes}: choose {$count})";
            foreach ($pool as $item) {
                $course = $item['course'];
                $trackCourse($course);
                $this->writeRow($sheet, $row++, $info, [
                    $type,
                    $course->title ?? 'N/A',
                    $course->code ?? 'N/A',
                    $course->credit_hours ?? 'N/A',
                    $this->studyPlanStatus($item),
                    $semesterNo,
                    $item['reason'] ?? '',
                ]);
            }
        }

        // ── University Requirements pool ──
        $pool = $studyPlan['university_req_info']['pool'] ?? [];
        $count = $studyPlan['university_req_info']['count'] ?? 0;
        $codes = implode(', ', $studyPlan['university_req_info']['codes'] ?? []);
        if ($pool && $count > 0) {
            $type = "Univ. Req. ({$codes}: choose {$count})";
            foreach ($pool as $item) {
                $course = $item['course'];
                $trackCourse($course);
                $this->writeRow($sheet, $row++, $info, [
                    $type,
                    $course->title ?? 'N/A',
                    $course->code ?? 'N/A',
                    $course->credit_hours ?? 'N/A',
                    $this->studyPlanStatus($item),
                    $semesterNo,
                    $item['reason'] ?? '',
                ]);
            }
        }

        // ── Missing core ──
        foreach ($missingCourses['core'] ?? [] as $item) {
            $course = $item['course'];
            $trackCourse($course);
            $status = $item['is_incomplete']
                ? 'In Progress'
                : ($item['available'] ? 'Available' : 'Prerequisites Not Met');

            $this->writeRow($sheet, $row++, $info, [
                'Missing Core',
                $course->title ?? 'N/A',
                $course->code ?? 'N/A',
                $course->credit_hours ?? 'N/A',
                $status,
                $item['semester'] ?? 'N/A',
                $item['reason'] ?? '',
            ]);
        }

        return $row;
    }

    /**
     * Write a single data row to the sheet.
     */
    private function writeRow($sheet, int $row, array $studentInfo, array $courseInfo): void
    {
        $data = array_merge($studentInfo, $courseInfo);
        foreach ($data as $col => $value) {
            $sheet->setCellValue([$col + 1, $row], $value);
        }
    }

    /**
     * Apply header and column styles.
     */
    private function applyStyles($sheet, int $lastDataRow): void
    {
        // Column widths
        foreach (self::COL_WIDTHS as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Header row
        $sheet->getRowDimension(1)->setRowHeight(28);
        $headerRange = 'A1:S1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1D4ED8'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '93C5FD'],
                ],
            ],
        ]);

        if ($lastDataRow >= 2) {
            $dataRange = "A2:S{$lastDataRow}";
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E5E7EB'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ]);

            // Academic ID column – bold, centred
            $sheet->getStyle("D2:D{$lastDataRow}")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            // CGPA / Hours columns – centred
            $sheet->getStyle("H2:L{$lastDataRow}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            // Course Code – bold, centred
            $sheet->getStyle("O2:O{$lastDataRow}")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            // Course Credit Hours – centred
            $sheet->getStyle("P2:P{$lastDataRow}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            // Semester – centred
            $sheet->getStyle("R2:R{$lastDataRow}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }
    }

    /**
     * Handle the case when no students match the filters.
     */
    private function completeWithEmptyFile(int $termId = null): void
    {
        $term = $termId ? Term::find($termId) : null;
        $filename = 'guiding_'
            . ($term ? str_replace(' ', '_', strtolower($term->name)) : 'all_terms')
            . '_' . now()->format('Ymd_His') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Guiding');
        foreach (self::HEADINGS as $col => $heading) {
            $sheet->setCellValue([$col + 1, 1], $heading);
        }
        $this->applyStyles($sheet, 1);

        $storagePath = Storage::disk('local')->path('exports');
        if (!is_dir($storagePath))
            mkdir($storagePath, 0755, true);

        $writer = new Xlsx($spreadsheet);
        $writer->save($storagePath . '/' . $filename);

        unset($writer, $spreadsheet);

        $this->task->update([
            'status' => 'completed',
            'progress' => 100,
            'result' => [
                'file_path' => 'exports/' . $filename,
                'filename' => $filename,
                'download_url' => route('enrollments.exportGuiding.download', ['uuid' => $this->task->uuid]),
            ],
            'message' => 'Guiding export completed. No students found matching filters.',
        ]);
    }

    private function studyPlanStatus(array $item): string
    {
        if ($item['is_passed'] ?? false)
            return 'Passed';
        if ($item['is_incomplete'] ?? false)
            return 'In Progress';
        if ($item['is_taken'] ?? false)
            return 'In Progress';
        if ($item['available'] ?? false)
            return 'Available';
        return 'Prerequisites Not Met';
    }
}
