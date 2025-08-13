<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Schedule\ScheduleAssignment;

class AvailableCourseSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'available_course_id',
        'group',
        'activity_type',
        'location',
        'min_capacity',
        'max_capacity',
        'capacity',
    ];

    /**
     * Boot the model and add event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($model) {
            $model->scheduleAssignments()->each(function ($assignment) {
                $assignment->delete();
            });
        });
    }

    /**
     * Get the available course that owns the schedule.
     */
    public function availableCourse(): BelongsTo
    {
        return $this->belongsTo(AvailableCourse::class);
    }

    /**
     * Get all of the schedule assignments for the available course schedule.
     */
    public function scheduleAssignments(): HasMany
    {
        return $this->hasMany(ScheduleAssignment::class, 'available_course_schedule_id');
    }

}
