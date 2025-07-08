<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\Program;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Student([
            'name_en'        => (string)($row['name_en'] ?? ''),
            'name_ar'        => (string)($row['name_ar'] ?? ''),
            'academic_id'    => (string)($row['academic_id'] ?? ''),
            'national_id'    => (string)($row['national_id'] ?? ''),
            'academic_email' => (string)($row['academic_email'] ?? ''),
            'level'          => $row['level'],
            'cgpa'           => $row['cgpa'],
            'gender'         => (string)($row['gender'] ?? ''),
            'program_name'   => (string)($row['program_name'] ?? ''),
        ]);
    }
} 