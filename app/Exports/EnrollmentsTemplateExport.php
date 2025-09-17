<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EnrollmentsTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        // Sample data for template with Egyptian academic context
        return [
            [
                '20230001', // academic_id (example Academic ID)
                'CS101',          // course_code
                '2252',       // term_code
                '1',          // group (optional)
                'A',       // grade (optional)
            ],
            [
                '20230002', // academic_id
                'MATH201',        // course_code
                '2252',       // term_code
                '2',          // group (optional)
                'B+',       // grade
            ],
            [
                '20230003', // academic_id
                'ENG101',        // course_code
                '2252',       // term_code
                '1',          // group (optional)
                '',          // grade (empty for no grade yet)
            ],
            [
                '20230004', // academic_id
                'PHY101',        // course_code
                '2252',       // term_code
                '5',          // group (empty for no group)
                'C-',       // grade
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'academic_id',
            'course_code',
            'term_code',
            'group',
            'grade',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
} 