<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

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
} 