<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PrerequisiteExceptionsTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                '20230001',     // academic_id
                'CS301',        // course_code
                'CS201',        // prerequisite_code
                '2252',         // term_code
                'Transfer credit equivalent accepted', // reason (optional)
                '1',            // is_active (1 or 0)
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'academic_id',
            'course_code',
            'prerequisite_code',
            'term_code',
            'reason',
            'is_active',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
