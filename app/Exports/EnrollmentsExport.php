<?php

namespace App\Exports;

use App\Models\EnrollmentSchedule;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EnrollmentsExport implements FromCollection, WithMapping, WithHeadings, WithTitle, WithStyles
{
    protected ?int $termId;
    protected ?int $programId;
    protected ?int $levelId;

    /**
     * Constructor.
     *
     * @param int|null $termId
     * @param int|null $programId
     * @param int|null $levelId
     */
    public function __construct(?int $termId = null, ?int $programId = null, ?int $levelId = null)
    {
        $this->termId = $termId;
        $this->programId = $programId;
        $this->levelId = $levelId;
    }

    /**
     * Get collection of enrollment schedules with related data.
     *
     * @return \Illuminate\Support\Collection
     */
     public function collection()
    {
        $query = EnrollmentSchedule::with([
            'enrollment.student',
            'enrollment.course',
            'enrollment.term',
            'availableCourseSchedule.availableCourse',
        ])
        ->select('enrollment_schedules.*')
        ->join('enrollments', 'enrollment_schedules.enrollment_id', '=', 'enrollments.id')
        ->join('students', 'enrollments.student_id', '=', 'students.id')
        ->join('levels', 'students.level_id', '=', 'levels.id')
        ->join('terms', 'enrollments.term_id', '=', 'terms.id')
        ->join('available_course_schedules', 'enrollment_schedules.available_course_schedule_id', '=', 'available_course_schedules.id');

        if ($this->termId !== null) {
            $query->where('enrollments.term_id', $this->termId);
        }

        if ($this->programId) {
            $query->where('students.program_id', $this->programId);
        }

        if ($this->levelId) {
            $query->where('students.level_id', $this->levelId);
        }

        return $query->orderBy('levels.name', 'asc')
            ->orderBy('terms.code', 'asc')
            ->get();
    }

    /**
     * Map each enrollment schedule to columns.
     *
     * @param \App\Models\EnrollmentSchedule $enrollmentSchedule
     * @return array
     */
    public function map($enrollmentSchedule): array
    {
        $group = $enrollmentSchedule->availableCourseSchedule->group ?? 'N/A';
        $assignments = $enrollmentSchedule->availableCourseSchedule->scheduleAssignments;
        $slots = $assignments->pluck('scheduleSlot')->filter();
        $day = 'N/A';
        if ($slots->isNotEmpty()) {
            $firstSlot = $slots->sortBy('start_time')->first();
            $lastSlot = $slots->sortByDesc('end_time')->first();
            $startTime = $firstSlot?->start_time ? (is_string($firstSlot->start_time) ? date('g:i A', strtotime($firstSlot->start_time)) : $firstSlot->start_time->format('g:i A')) : 'N/A';
            $endTime = $lastSlot?->end_time ? (is_string($lastSlot->end_time) ? date('g:i A', strtotime($lastSlot->end_time)) : $lastSlot->end_time->format('g:i A')) : 'N/A';
            $day = $firstSlot && $firstSlot->day_of_week ? $firstSlot->day_of_week : 'N/A';
        } else {
            $startTime = 'N/A';
            $endTime = 'N/A';
        }
        $location = $enrollmentSchedule->availableCourseSchedule->location ?? 'N/A';
        $studentProgram = $enrollmentSchedule->enrollment->student->program->name ?? 'N/A';
        return [
            $enrollmentSchedule->enrollment->student->name_en ?? 'N/A',
            $enrollmentSchedule->enrollment->student->name_ar ?? 'N/A',
            $enrollmentSchedule->enrollment->student->national_id ?? 'N/A',
            $enrollmentSchedule->enrollment->student->academic_id ?? 'N/A',
            $enrollmentSchedule->enrollment->student->academic_email ?? 'N/A',
            isset($enrollmentSchedule->enrollment->student->level) ? 'Level ' . $enrollmentSchedule->enrollment->student->level->name : 'N/A',
            $studentProgram,
            $enrollmentSchedule->enrollment->course->title ?? 'N/A',
            $enrollmentSchedule->enrollment->course->code ?? 'N/A',
            $enrollmentSchedule->enrollment->grade ?? 'N/A',
            $enrollmentSchedule->enrollment->course->credit_hours ?? 'N/A',
            $enrollmentSchedule->enrollment->term->name ?? 'N/A',
            $enrollmentSchedule->availableCourseSchedule->activity_type ?? 'N/A',
            $group,
            $location,
            $day,
            $startTime,
            $endTime,
        ];
    }

    /**
     * Define column headings.
     *
     * @return array
     */
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
            'Course Title',
            'Course Code',
            'Grade',
            'Credit Hours',
            'Term',
            'Activity Type',
            'Group',
            'Location',
            'Day',
            'Start Time',
            'End Time',
        ];
    }

    /**
     * Define the sheet title.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Enrollments';
    }

    /**
     * Apply styles to the worksheet.
     *
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet): array
    {
        // Auto-size all columns
        foreach (range('A', 'R') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set minimum widths for specific columns
        $sheet->getColumnDimension('A')->setWidth(20); // Student Name (EN)
        $sheet->getColumnDimension('B')->setWidth(20); // Student Name (AR)
        $sheet->getColumnDimension('C')->setWidth(15); // National ID
        $sheet->getColumnDimension('D')->setWidth(12); // Academic ID
        $sheet->getColumnDimension('E')->setWidth(25); // Academic Email
        $sheet->getColumnDimension('H')->setWidth(25); // Course Title
        $sheet->getColumnDimension('O')->setWidth(15); // Location
        $sheet->getColumnDimension('P')->setWidth(12); // Day
        $sheet->getColumnDimension('Q')->setWidth(12); // Start Time
        $sheet->getColumnDimension('R')->setWidth(12); // End Time

        // Set row height for header
        $sheet->getRowDimension(1)->setRowHeight(25);

        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'], // Blue header
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
            // All data rows
            'A2:R1000' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
            // Alternate row colors
            'A2:R2' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8F9FA'],
                ],
            ],
            // Academic ID column
            'D:D' => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ],
            // Course Code column
            'I:I' => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ],
            // Grade column
            'J:J' => [
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'font' => ['bold' => true],
            ],
            // Credit Hours column
            'K:K' => [
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'numberFormat' => [
                    'formatCode' => '0',
                ],
            ],
            // Group column
            'N:N' => [
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ],
            // Time columns
            'Q:R' => [
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
