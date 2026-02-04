<?php

namespace App\Exports;

use App\Models\AvailableCourse;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AvailableCoursesExport implements FromCollection, WithMapping, WithHeadings, WithTitle, WithStyles
{
    protected ?int $termId;

    /**
     * Constructor.
     *
     * @param int|null $termId
     */
    public function __construct(?int $termId = null)
    {
        $this->termId = $termId;
    }

    /**
     * Get collection of available courses with their schedules.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = AvailableCourse::with([
            'course',
            'term',
            'schedules.scheduleAssignments.scheduleSlot',
            'schedules.program',
            'schedules.level',
            'schedules.enrollments',
            'enrollments',
        ]);

        // Filter by term if specified
        if ($this->termId) {
            $query->where('term_id', $this->termId);
        }

        return $query->orderBy('term_id', 'asc')
            ->get()
            ->flatMap(function ($availableCourse) {
                // If no schedules, return course with empty schedule info
                if ($availableCourse->schedules->isEmpty()) {
                    return collect([[
                        'available_course' => $availableCourse,
                        'schedule' => null,
                    ]]);
                }

                // Return one row per schedule
                return $availableCourse->schedules->map(function ($schedule) use ($availableCourse) {
                    return [
                        'available_course' => $availableCourse,
                        'schedule' => $schedule,
                    ];
                });
            });
    }

    /**
     * Map each row to columns.
     *
     * @param array $row
     * @return array
     */
    public function map($row): array
    {
        $availableCourse = $row['available_course'];
        $schedule = $row['schedule'];

        // Course info
        $courseCode = $availableCourse->course?->code ?? 'N/A';
        $courseTitle = $availableCourse->course?->title ?? $availableCourse->course?->name ?? 'N/A';
        $creditHours = $availableCourse->course?->credit_hours ?? 'N/A';
        $termName = $availableCourse->term?->name ?? 'N/A';
        $termCode = $availableCourse->term?->code ?? 'N/A';
        $mode = ucfirst($availableCourse->mode ?? 'N/A');

        // Schedule info
        if ($schedule) {
            $activityType = ucfirst($schedule->activity_type ?? 'N/A');
            $group = $schedule->group ?? 'N/A';
            $location = $schedule->location ?? 'N/A';
            $minCapacity = $schedule->min_capacity ?? 'N/A';
            $maxCapacity = $schedule->max_capacity ?? 'N/A';
            $programName = $schedule->program?->name ?? 'All Programs';
            $levelName = $schedule->level?->name ? 'Level ' . $schedule->level->name : 'All Levels';
            $currentEnrollments = $schedule->enrollments->count();
            $remainingCapacity = $maxCapacity !== 'N/A' && is_numeric($maxCapacity) ? ($maxCapacity - $currentEnrollments) : 'N/A';

            // Get time slots
            $slots = $schedule->scheduleAssignments->pluck('scheduleSlot')->filter()->sortBy('start_time');
            
            if ($slots->isNotEmpty()) {
                $firstSlot = $slots->first();
                $lastSlot = $slots->last();
                $day = $firstSlot?->day_of_week ? ucfirst($firstSlot->day_of_week) : 'N/A';
                $startTime = $firstSlot?->start_time ? (is_string($firstSlot->start_time) ? date('g:i A', strtotime($firstSlot->start_time)) : $firstSlot->start_time->format('g:i A')) : 'N/A';
                $endTime = $lastSlot?->end_time ? (is_string($lastSlot->end_time) ? date('g:i A', strtotime($lastSlot->end_time)) : $lastSlot->end_time->format('g:i A')) : 'N/A';
            } else {
                $day = 'N/A';
                $startTime = 'N/A';
                $endTime = 'N/A';
            }
        } else {
            $activityType = 'No Schedule';
            $group = 'N/A';
            $location = 'N/A';
            $minCapacity = 'N/A';
            $maxCapacity = 'N/A';
            $programName = 'N/A';
            $levelName = 'N/A';
            $day = 'N/A';
            $startTime = 'N/A';
            $endTime = 'N/A';
            $currentEnrollments = $availableCourse->enrollments->count();
            $remainingCapacity = 'N/A';
        }


        return [
            $courseCode,
            $courseTitle,
            $creditHours,
            $termName,
            $termCode,
            $mode,
            $activityType,
            $group,
            $programName,
            $levelName,
            $location,
            $day,
            $startTime,
            $endTime,
            $minCapacity,
            $maxCapacity,
            $currentEnrollments,
            $remainingCapacity
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
            'Course Code',
            'Course Title',
            'Credit Hours',
            'Term Name',
            'Term Code',
            'Mode',
            'Activity Type',
            'Group',
            'Program',
            'Level',
            'Location',
            'Day',
            'Start Time',
            'End Time',
            'Min Capacity',
            'Max Capacity',
            'Current Enrollments',
            'Remaining Capacity',
        ];
    }

    /**
     * Define the sheet title.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Available Courses';
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
        foreach (range('A', 'S') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set minimum widths for specific columns
        $sheet->getColumnDimension('B')->setWidth(25); // Course Title
        $sheet->getColumnDimension('D')->setWidth(15); // Term Name
        $sheet->getColumnDimension('K')->setWidth(15); // Location
        $sheet->getColumnDimension('M')->setWidth(12); // Start Time
        $sheet->getColumnDimension('N')->setWidth(12); // End Time

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
            'A2:S1000' => [
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
            'A2:S2' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8F9FA'],
                ],
            ],
            // Course code column
            'A:A' => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ],
            // Numeric columns (capacities and enrollments)
            'O:S' => [
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'numberFormat' => [
                    'formatCode' => '0',
                ],
            ],
            // Time columns
            'M:N' => [
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
