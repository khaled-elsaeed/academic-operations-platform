<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentsExport implements FromCollection, WithMapping, WithHeadings
{
    protected $programId;
    protected $levelId;

    public function __construct($programId = null, $levelId = null)
    {
        $this->programId = $programId;
        $this->levelId = $levelId;
    }

    public function collection()
    {
        $query = Student::with(['program', 'level']);

        if ($this->programId) {
            $query->where('program_id', $this->programId);
        }

        if ($this->levelId) {
            $query->where('level_id', $this->levelId);
        }

        return $query->orderBy('level_id', 'asc')
            ->orderBy('program_id', 'asc')
            ->orderBy('name_en', 'asc')
            ->get();
    }

    public function map($student): array
    {
        return [
            $student->name_en ?? 'N/A',
            $student->name_ar ?? 'N/A',
            $student->national_id ?? 'N/A',
            $student->academic_id ?? 'N/A',
            $student->program ? $student->program->name : 'N/A',
            $student->level ? $student->level->name : 'N/A',
            $student->gender ?? 'N/A',
            $student->academic_email ?? 'N/A',
            $student->cgpa ?? 'N/A',
            $student->taken_hours ?? 'N/A',
        ];
    }

    public function headings(): array
    {
        return [
            'Student Name (EN)',
            'Student Name (AR)',
            'National ID',
            'Academic ID',
            'Program',
            'Level',
            'Gender',
            'Academic Email',
            'CGPA',
            'Taken Credit Hours',
        ];
    }
} 