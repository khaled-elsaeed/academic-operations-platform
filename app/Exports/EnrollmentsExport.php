<?php

namespace App\Exports;

use App\Models\Enrollment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EnrollmentsExport implements FromCollection, WithMapping, WithHeadings
{
    protected $termId;
    protected $programId;
    protected $levelId;

    public function __construct($termId, $programId = null, $levelId = null)
    {
        $this->termId = $termId;
        $this->programId = $programId;
        $this->levelId = $levelId;
    }

    public function collection()
    {
        $query = Enrollment::with(['student', 'course', 'term', 'student.level'])
            ->select('enrollments.*')
            ->join('students', 'enrollments.student_id', '=', 'students.id')
            ->join('levels', 'students.level_id', '=', 'levels.id')
            ->join('terms', 'enrollments.term_id', '=', 'terms.id');

        if ($this->termId !== null) {
            $query->where('enrollments.term_id', $this->termId);
        }

        if ($this->programId) {
            $query->where('students.program_id', $this->programId);
        }

        if ($this->levelId) {
            $query->where('students.level_id', $this->levelId);
        }

        return $query->orderBy('levels.name', 'asc')
            ->orderBy('terms.code', 'asc')
            ->get();
    }

    public function map($enrollment): array
    {
        return [
            isset($enrollment->student->name_en) ? $enrollment->student->name_en : 'N/A',
            isset($enrollment->student->name_ar) ? $enrollment->student->name_ar : 'N/A',
            isset($enrollment->student->national_id) ? $enrollment->student->national_id : 'N/A',
            isset($enrollment->student->academic_id) ? $enrollment->student->academic_id : 'N/A',
            isset($enrollment->student->level) ? 'Level ' . $enrollment->student->level->name : 'N/A',
            isset($enrollment->course->title) ? $enrollment->course->title : 'N/A',
            isset($enrollment->course->code) ? $enrollment->course->code : 'N/A',
            isset($enrollment->grade) ? $enrollment->grade : 'N/A',
            isset($enrollment->course->credit_hours) ? $enrollment->course->credit_hours : 'N/A',
            isset($enrollment->term->name) ? $enrollment->term->name : 'N/A',
            isset($enrollment->created_at) ? $enrollment->created_at->format('Y-m-d H:i:s') : 'N/A',

        ];
    }

    public function headings(): array
    {
        return [
            'Student Name (EN)',
            'Student Name (AR)',
            'National ID',
            'Academic ID',
            'Level',
            'Course Title',
            'Course Code',
            'Grade',
            'Credit Hours',
            'Term',
            'Created At'
        ];
    }
}
