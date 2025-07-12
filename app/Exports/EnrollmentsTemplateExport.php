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
                '12345678901234', // student_national_id (Egyptian national ID)
                'CS101',          // course_code
                'FALL2024',       // term_code
            ],
            [
                '98765432109876', // student_national_id
                'MATH201',        // course_code
                'FALL2024',       // term_code
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'student_national_id',
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