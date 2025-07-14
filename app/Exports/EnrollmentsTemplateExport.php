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
                'A',       // grade (optional)
            ],
            [
                '20230002', // academic_id
                'MATH201',        // course_code
                '2252',       // term_code
                'B+',       // grade
            ],
            [
                '20230003', // academic_id
                'ENG101',        // course_code
                '2252',       // term_code
                '',          // grade (empty for no grade yet)
            ],
            [
                '20230004', // academic_id
                'PHY101',        // course_code
                '2252',       // term_code
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