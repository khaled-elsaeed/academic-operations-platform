<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AvailableCoursesTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        // Example row
        return [
            [
                'CS101',    // course_code
                '2024FALL', // term_code
                'Sch202568', // schedule_code
                'Computer Science', // program_name
                '1',  // level_name
                '10',       // min_capacity
                '30',       // max_capacity
                'Lecture',  // activity_type
                '1',  // group
                'Saturday', // day
                '1',        // slot
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'course_code',
            'term_code',
            'schedule_code',
            'program_name',
            'level_name',
            'min_capacity',
            'max_capacity',
            'activity_type',
            'group',
            'day',
            'slot',
        ];
    }
} 