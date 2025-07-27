<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\Schedule\ScheduleAssignment;

class AvailableCourseDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'available_course_id',
        'group',
        'activity_type',
        'min_capacity',
        'max_capacity',
        'capacity',
    ];

    /**
     * Get the available course that owns the detail.
     */
    public function availableCourse()
    {
        return $this->belongsTo(AvailableCourse::class);
    }

    /**
     * Get all of the schedule assignments for the available course detail.
     */
    public function scheduleAssignments(): MorphMany
    {
        return $this->morphMany(ScheduleAssignment::class, 'assignable');
    }
}
