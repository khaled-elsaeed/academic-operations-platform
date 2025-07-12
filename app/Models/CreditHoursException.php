<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class CreditHoursException extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'student_id',
        'term_id',
        'granted_by',
        'additional_hours',
        'reason',
        'is_active',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'additional_hours' => 'integer',
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
    ];

    /**
     * Get the student that owns the exception.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
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
     * Get the effective additional hours (0 if inactive).
     */
    public function getEffectiveAdditionalHours(): int
    {
        return $this->isValid() ? $this->additional_hours : 0;
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
} 