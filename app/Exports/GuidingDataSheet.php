<?php

namespace App\Exports;

use App\Models\Student;
use App\Models\Term;
use App\Services\Enrollment\EnrollmentGuidingService;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Generates a single flat worksheet for the guiding export.
 *
 * Uses FromGenerator (not FromArray / FromCollection) so that rows are
 * streamed one-at-a-time and memory is never exhausted even for large
 * student populations.
 */
class GuidingDataSheet implements FromGenerator, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    private const CHUNK_SIZE = 50;

    /**
     * @param int[]   $studentIds  Ordered list of student IDs to export
     * @param int|null $termId
     */
    public function __construct(
        private readonly array $studentIds,
        private readonly ?int  $termId = null,
    ) {}

    // -------------------------------------------------------------------------
    // Generator
    // -------------------------------------------------------------------------

    /**
     * Yield rows one by one, processing students in small chunks.
     *
     * @return \Generator
     */
    public function generator(): \Generator
    {
        $chunks = array_chunk($this->studentIds, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            // Load only this batch of students with the relations needed for
            // display (guiding service fetches its own relations internally).
            $students = Student::with(['program', 'level'])
                ->whereIn('id', $chunk)
                ->orderBy('name_en')
                ->get();

            foreach ($students as $student) {
                try {
                    $guidingService = new EnrollmentGuidingService($student->id, $this->termId);
                    $guide          = $guidingService->guide();
                } catch (\Exception) {
                    // Skip students where guiding calculation fails
                    continue;
                }

                $studyPlan     = $guide['study_plan_courses'] ?? [];
                $missingCourses = $guide['missing_courses']   ?? [];
                $semesterNo    = $studyPlan['semester_no']    ?? 'N/A';

                $info = [
                    $student->name_en    ?? 'N/A',
                    $student->name_ar    ?? 'N/A',
                    $student->national_id ?? 'N/A',
                    $student->academic_id ?? 'N/A',
                    $student->academic_email ?? 'N/A',
                    $student->level  ? 'Level ' . $student->level->name : 'N/A',
                    $student->program?->name ?? 'N/A',
                ];

                // ---- Recommended (current-semester study plan) ----
                foreach ($studyPlan['courses'] ?? [] as $item) {
                    $course = $item['course'];
                    yield array_merge($info, [
                        'Recommended',
                        $course->title        ?? 'N/A',
                        $course->code         ?? 'N/A',
                        $course->credit_hours ?? 'N/A',
                        $this->studyPlanStatus($item),
                        $semesterNo,
                        $item['reason'] ?? '',
                    ]);
                }

                // ---- Elective pool ----
                $electivePool  = $studyPlan['elective_info']['pool']  ?? [];
                $electiveCount = $studyPlan['elective_info']['count'] ?? 0;
                $electiveCodes = implode(', ', $studyPlan['elective_info']['codes'] ?? []);
                if ($electivePool && $electiveCount > 0) {
                    $type = "Elective Option ({$electiveCodes}: choose {$electiveCount})";
                    foreach ($electivePool as $item) {
                        $course = $item['course'];
                        yield array_merge($info, [
                            $type,
                            $course->title        ?? 'N/A',
                            $course->code         ?? 'N/A',
                            $course->credit_hours ?? 'N/A',
                            $this->studyPlanStatus($item),
                            $semesterNo,
                            $item['reason'] ?? '',
                        ]);
                    }
                }

                // ---- University Requirements pool ----
                $urPool  = $studyPlan['university_req_info']['pool']  ?? [];
                $urCount = $studyPlan['university_req_info']['count'] ?? 0;
                $urCodes = implode(', ', $studyPlan['university_req_info']['codes'] ?? []);
                if ($urPool && $urCount > 0) {
                    $type = "University Req. Option ({$urCodes}: choose {$urCount})";
                    foreach ($urPool as $item) {
                        $course = $item['course'];
                        yield array_merge($info, [
                            $type,
                            $course->title        ?? 'N/A',
                            $course->code         ?? 'N/A',
                            $course->credit_hours ?? 'N/A',
                            $this->studyPlanStatus($item),
                            $semesterNo,
                            $item['reason'] ?? '',
                        ]);
                    }
                }

                // ---- Missing core courses ----
                foreach ($missingCourses['core'] ?? [] as $item) {
                    $course = $item['course'];
                    yield array_merge($info, [
                        'Missing Core',
                        $course->title        ?? 'N/A',
                        $course->code         ?? 'N/A',
                        $course->credit_hours ?? 'N/A',
                        $item['is_incomplete'] ? 'In Progress' : ($item['available'] ? 'Available' : 'Prerequisites Not Met'),
                        $item['semester'] ?? 'N/A',
                        $item['reason']   ?? '',
                    ]);
                }
            }

            // Free memory after each chunk to keep usage flat.
            unset($students);
            gc_collect_cycles();
        }
    }

    // -------------------------------------------------------------------------
    // Headings / Title
    // -------------------------------------------------------------------------

    public function headings(): array
    {
        return [
            'Student Name (EN)',
            'Student Name (AR)',
            'National ID',
            'Academic ID',
            'Academic Email',
            'Level',
            'Program',
            'Type',
            'Course Title',
            'Course Code',
            'Credit Hours',
            'Status',
            'Semester No.',
            'Note',
        ];
    }

    public function title(): string
    {
        if ($this->termId) {
            $term = Term::find($this->termId);
            if ($term) {
                return 'Guiding - ' . ucfirst($term->season) . ' ' . $term->year;
            }
        }
        return 'Guiding';
    }

    // -------------------------------------------------------------------------
    // Column widths
    // -------------------------------------------------------------------------

    public function columnWidths(): array
    {
        return [
            'A' => 24, 'B' => 24, 'C' => 16, 'D' => 14,
            'E' => 28, 'F' => 10, 'G' => 22, 'H' => 38,
            'I' => 30, 'J' => 14, 'K' => 13, 'L' => 24,
            'M' => 14, 'N' => 30,
        ];
    }

    // -------------------------------------------------------------------------
    // Styles
    // -------------------------------------------------------------------------

    public function styles(Worksheet $sheet): array
    {
        $sheet->getRowDimension(1)->setRowHeight(28);

        // NOTE: We cannot apply per-row conditional formatting here because the
        // generator has already finished writing data by the time styles() is
        // called.  We apply column-level rules only; per-row colouring would
        // require WithEvents / AfterSheet which adds significant complexity.

        return [
            // ---- Header row ----
            1 => [
                'font' => [
                    'bold'  => true,
                    'size'  => 11,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1D4ED8'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => '93C5FD'],
                    ],
                ],
            ],
            // ---- All data cells ----
            'A2:N5000' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'E5E7EB'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
            // Academic ID – centred & bold
            'D:D' => [
                'font'      => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Course Code – centred & bold
            'J:J' => [
                'font'      => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Credit Hours – centred
            'K:K' => [
                'alignment'    => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'numberFormat' => ['formatCode' => '0'],
            ],
            // Semester – centred
            'M:M' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function studyPlanStatus(array $item): string
    {
        if ($item['is_passed']    ?? false) return 'Passed';
        if ($item['is_incomplete'] ?? false) return 'In Progress';
        if ($item['is_taken']     ?? false) return 'In Progress';
        if ($item['available']    ?? false) return 'Available';
        return 'Prerequisites Not Met';
    }
}
