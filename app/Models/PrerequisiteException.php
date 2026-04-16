<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PrerequisiteException extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'student_id',
        'course_id',
        'prerequisite_id',
        'term_id',
        'granted_by',
        'reason',
        'is_active',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * The attributes that should be appended to arrays.
     *
     * @var array<string>
     */
    protected $appends = [
        'status_text',
        'term_display_name',
        'student_display_name',
        'course_display_name',
        'prerequisite_display_name',
    ];

    /**
     * Get the student that owns the exception.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the course for the exception.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the prerequisite course being exempted.
     */
    public function prerequisite(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'prerequisite_id');
    }

    /**
     * Get the term for the exception.
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Get the user who granted the exception.
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * Scope to get only active exceptions.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only inactive exceptions.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to filter by student.
     */
    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope to filter by term.
     */
    public function scopeForTerm(Builder $query, int $termId): Builder
    {
        return $query->where('term_id', $termId);
    }

    /**
     * Scope to filter by course.
     */
    public function scopeForCourse(Builder $query, int $courseId): Builder
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope to filter by prerequisite.
     */
    public function scopeForPrerequisite(Builder $query, int $prerequisiteId): Builder
    {
        return $query->where('prerequisite_id', $prerequisiteId);
    }

    /**
     * Scope to filter by granted by user.
     */
    public function scopeGrantedBy(Builder $query, int $userId): Builder
    {
        return $query->where('granted_by', $userId);
    }

    /**
     * Check if the exception is currently valid.
     */
    public function isValid(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the status text attribute.
     */
    protected function statusText(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_active ? 'Active' : 'Inactive'
        );
    }

    /**
     * Get the term display name attribute.
     */
    protected function termDisplayName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->term ? "{$this->term->season} {$this->term->year}" : '-'
        );
    }

    /**
     * Get the student display name attribute.
     */
    protected function studentDisplayName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->student ? "{$this->student->name_en} ({$this->student->academic_id})" : '-'
        );
    }

    /**
     * Get the course display name attribute.
     */
    protected function courseDisplayName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->course ? "{$this->course->title} ({$this->course->code})" : '-'
        );
    }

    /**
     * Get the prerequisite display name attribute.
     */
    protected function prerequisiteDisplayName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->prerequisite ? "{$this->prerequisite->title} ({$this->prerequisite->code})" : '-'
        );
    }
}
