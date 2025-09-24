<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CreditHoursExceptionsTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                '20230001', // academic_id
                '2252',     // term_code
                '3',        // additional_hours
                'Overload due to graduation requirements', // reason (optional)
                '1',        // is_active (1 or 0)
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'academic_id',
            'term_code',
            'additional_hours',
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


