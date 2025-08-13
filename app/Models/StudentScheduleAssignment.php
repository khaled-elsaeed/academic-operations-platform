<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentScheduleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'enrollment_id',
        'available_course_schedule_id',
        'term_id',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Get the student that owns the assignment.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the enrollment for the assignment.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Get the available course schedule for the assignment.
     */
    public function availableCourseSchedule(): BelongsTo
    {
        return $this->belongsTo(AvailableCourseSchedule::class);
    }

    /**
     * Get the term for the assignment.
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }
}