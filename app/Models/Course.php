<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'title',
        'credit_hours',
        'faculty_id',
    ];

    protected $appends = [
        'name'
    ];

    /**
     * Get the name attribute for the course (e.g., "Course Title (CODE)").
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->title} ({$this->code})"
        );
    }


    /**
     * Get a comma-separated string of prerequisite course names.
     */
    protected function prerequisiteNames(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->prerequisites->pluck('name')->join(', ')
        );
    }

    /**
     * Get the faculty that owns the course.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Get the prerequisites for the course.
     */
    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(
            Course::class,
            'course_prerequisite',
            'course_id',
            'prerequisite_id'
        )->using(CoursePrerequisite::class)->withPivot('order')->withTimestamps();
    }

    /**
     * Get the courses that have this course as a prerequisite.
     */
    public function dependentCourses(): BelongsToMany
    {
        return $this->belongsToMany(
            Course::class,
            'course_prerequisite',
            'prerequisite_id',
            'course_id'
        )->using(CoursePrerequisite::class)->withPivot('order')->withTimestamps();
    }

    /**
     * Get the available courses for this course.
     */
    public function availableCourses(): HasMany
    {
        return $this->hasMany(AvailableCourse::class);
    }
}
