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
        // Sample data for template
        return [
            [
                '20230001', // academic_id (example Academic ID)
                'CS101',          // course_code
                '2252',       // term_code
            ],
            [
                '20230002', // academic_id
                'MATH201',        // course_code
                '2252',       // term_code
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'academic_id',
            'course_code',
            'term_code',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
} 