<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentsTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        // Example row
        return [
            [
                'John Doe',      // name_en
                'جون دو',        // name_ar
                '20230001',      // academic_id
                '1234567890',    // national_id
                'john.doe@univ.edu', // academic_email
                '1',             // level
                '3.5',           // cgpa
                'male',          // gender
                'Computer Science', // program_name (example)
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'name_en',
            'name_ar',
            'academic_id',
            'national_id',
            'academic_email',
            'level',
            'cgpa',
            'gender',
            'program_name',
        ];
    }
} 