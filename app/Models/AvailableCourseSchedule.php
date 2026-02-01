<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Schedule\ScheduleAssignment;
use App\Models\Program;
use App\Models\Level;

class AvailableCourseSchedule extends Model
{
    use HasFactory;

    /**
     * Always eager-load assignments and their slots to avoid missing data when accessing schedules.
     *
     * @var array
     */
    protected $with = ['scheduleAssignments.scheduleSlot'];

    protected $fillable = [
        'available_course_id',
        'group',
        'activity_type',
        'location',
        'level_id',
        'program_id',
        'min_capacity',
        'max_capacity',
        'current_capacity',
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
     * The program this schedule belongs to (nullable).
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * The level this schedule belongs to (nullable).
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * Get all of the schedule assignments for the available course schedule.
     */
    public function scheduleAssignments(): HasMany
    {
        return $this->hasMany(ScheduleAssignment::class, 'available_course_schedule_id');
    }

    /**
     * Get all enrollment schedules attached to this available course schedule.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(EnrollmentSchedule::class, 'available_course_schedule_id');
    }

}
