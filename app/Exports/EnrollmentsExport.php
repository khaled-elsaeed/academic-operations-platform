<?php

namespace App\Exports;

use App\Models\EnrollmentSchedule;
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
        $query = EnrollmentSchedule::with([
            'enrollment.student',
            'enrollment.course',
            'enrollment.term',
            'availableCourseSchedule.availableCourse',
        ])
        ->select('enrollment_schedules.*')
        ->join('enrollments', 'enrollment_schedules.enrollment_id', '=', 'enrollments.id')
        ->join('students', 'enrollments.student_id', '=', 'students.id')
        ->join('levels', 'students.level_id', '=', 'levels.id')
        ->join('terms', 'enrollments.term_id', '=', 'terms.id')
        ->join('available_course_schedules', 'enrollment_schedules.available_course_schedule_id', '=', 'available_course_schedules.id');

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

    public function map($enrollmentSchedule): array
    {
        $group = $enrollmentSchedule->availableCourseSchedule->group ?? 'N/A';
        $assignments = $enrollmentSchedule->availableCourseSchedule->scheduleAssignments;
        $slots = $assignments->pluck('scheduleSlot')->filter();
        if ($slots->isNotEmpty()) {
            $firstSlot = $slots->sortBy('start_time')->first();
            $lastSlot = $slots->sortByDesc('end_time')->first();
            $startTime = $firstSlot && $firstSlot->start_time ? formatDate($firstSlot->start_time, 'h:i A') : 'N/A';
            $endTime = $lastSlot && $lastSlot->end_time ? formatDate($lastSlot->end_time, 'h:i A') : 'N/A';
        } else {
            $startTime = 'N/A';
            $endTime = 'N/A';
        }
            $location = $enrollmentSchedule->availableCourseSchedule->location ?? 'N/A';
            $studentProgram = $enrollmentSchedule->enrollment->student->program->name ?? 'N/A';
            return [
                $enrollmentSchedule->enrollment->student->name_en ?? 'N/A',
                $enrollmentSchedule->enrollment->student->name_ar ?? 'N/A',
                $enrollmentSchedule->enrollment->student->national_id ?? 'N/A',
                $enrollmentSchedule->enrollment->student->academic_id ?? 'N/A',
                isset($enrollmentSchedule->enrollment->student->level) ? 'Level ' . $enrollmentSchedule->enrollment->student->level->name : 'N/A',
                $studentProgram,
                $enrollmentSchedule->enrollment->course->title ?? 'N/A',
                $enrollmentSchedule->enrollment->course->code ?? 'N/A',
                $enrollmentSchedule->enrollment->grade ?? 'N/A',
                $enrollmentSchedule->enrollment->course->credit_hours ?? 'N/A',
                $enrollmentSchedule->enrollment->term->name ?? 'N/A',
                $enrollmentSchedule->availableCourseSchedule->activity_type ?? 'N/A',
                $group,
                $location,
                $startTime,
                $endTime,
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
            'Program',
            'Course Title',
            'Course Code',
            'Grade',
            'Credit Hours',
            'Term',
            'Activity Type',
            'Group',
            'Location',
            'Start Time',
            'End Time',
        ];
    }
}
