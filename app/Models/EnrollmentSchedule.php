<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'available_course_schedule_id',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Get the enrollment for this schedule assignment.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Get the available course schedule for this assignment.
     */
    public function availableCourseSchedule(): BelongsTo
    {
        return $this->belongsTo(AvailableCourseSchedule::class);
    }

    /**
     * Get the student through the enrollment.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id')
            ->through('enrollment');
    }

    /**
     * Scope to get active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get schedules for a specific term.
     */
    public function scopeForTerm($query, $termId)
    {
        return $query->whereHas('enrollment', function ($q) use ($termId) {
            $q->where('term_id', $termId);
        });
    }
}