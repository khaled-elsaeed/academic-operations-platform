<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class AvailableCourse extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'course_id',
        'term_id',
        'min_capacity',
        'max_capacity',
        'is_universal', // true if available for all programs/levels
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'course_id' => 'integer',
        'term_id' => 'integer',
        'min_capacity' => 'integer',
        'max_capacity' => 'integer',
        'is_universal' => 'boolean',
    ];

    /**
     * Get the enrollment count attribute.
     */
    protected function enrollmentCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->enrollments()->count()
        );
    }

    /**
     * Get the remaining capacity attribute.
     */
    protected function remainingCapacity(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->max_capacity - $this->enrollment_count
        );
    }

    /**
     * Get the course associated with this available course.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the term associated with this available course.
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Get the eligibilities (program-level pairs) for this available course.
     */
    public function eligibilities(): HasMany
    {
        return $this->hasMany(CourseEligibility::class);
    }

    /**
     * Get programs through the course_eligibilities pivot table.
     */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'course_eligibilities', 'available_course_id', 'program_id')
                    ->withPivot('level_id');
    }

    /**
     * Get levels through the course_eligibilities pivot table.
     */
    public function levels(): BelongsToMany
    {
        return $this->belongsToMany(Level::class, 'course_eligibilities', 'available_course_id', 'level_id')
                    ->withPivot('program_id');
    }

    /**
     * Get all (program, level) pairs as an array of ['program_id' => ..., 'level_id' => ...]
     */
    public function getProgramLevelPairsArray(): array
    {
        return $this->eligibilities()->get(['program_id', 'level_id'])->toArray();
    }

    /**
     * Get the enrollments for this available course.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'available_course_id');
    }

    /**
     * Scope a query to filter available courses by program, level, and term.
     *
     * @param Builder $query
     * @param int|null $programId
     * @param int|null $levelId
     * @param int|null $termId
     * @return Builder
     */
    public function scopeAvailable(Builder $query, ?int $programId = null, ?int $levelId = null, ?int $termId = null): Builder
    {
        return $query->when($termId, function ($q) use ($termId) {
            return $q->where('term_id', $termId);
        })->when($programId, function ($q) use ($programId) {
            return $q->where(function($subQuery) use ($programId) {
                $subQuery->where('is_universal', true)
                        ->orWhereHas('eligibilities', function($pairQuery) use ($programId) {
                            $pairQuery->where('program_id', $programId);
                        });
            });
        })->when($levelId, function ($q) use ($levelId) {
            return $q->where(function($subQuery) use ($levelId) {
                $subQuery->where('is_universal', true)
                        ->orWhereHas('eligibilities', function($pairQuery) use ($levelId) {
                            $pairQuery->where('level_id', $levelId);
                        });
            });
        });
    }

    /**
     * Scope for courses available to specific programs.
     *
     * @param Builder $query
     * @param array $programIds
     * @return Builder
     */
    public function scopeForPrograms(Builder $query, array $programIds): Builder
    {
        return $query->where(function($q) use ($programIds) {
            $q->where('is_universal', true)
              ->orWhereHas('eligibilities', function($pairQuery) use ($programIds) {
                  $pairQuery->whereIn('program_id', $programIds);
              });
        });
    }

    /**
     * Scope for courses available to specific levels.
     *
     * @param Builder $query
     * @param array $levelIds
     * @return Builder
     */
    public function scopeForLevels(Builder $query, array $levelIds): Builder
    {
        return $query->where(function($q) use ($levelIds) {
            $q->where('is_universal', true)
              ->orWhereHas('eligibilities', function($pairQuery) use ($levelIds) {
                  $pairQuery->whereIn('level_id', $levelIds);
              });
        });
    }

    /**
     * Scope to filter courses with available capacity.
     *
     * @param Builder $query
     * @param int $minCapacity
     * @return Builder
     */
    public function scopeWithCapacity(Builder $query, int $minCapacity = 1): Builder
    {
        return $query->whereRaw('max_capacity > (
            SELECT COUNT(*) 
            FROM enrollments 
            WHERE enrollments.available_course_id = available_courses.id
        )')->having('max_capacity', '>=', $minCapacity);
    }

    /**
     * Check if this course is available for a specific program and level.
     *
     * @param int|null $programId
     * @param int|null $levelId
     * @return bool
     */
    public function isAvailableFor(?int $programId = null, ?int $levelId = null): bool
    {
        if ($this->is_universal) {
            return true;
        }

        return $this->eligibilities()
                    ->when($programId, fn($q) => $q->where('program_id', $programId))
                    ->when($levelId, fn($q) => $q->where('level_id', $levelId))
                    ->exists();
    }

    /**
     * Get all available program IDs for this course.
     *
     * @return array
     */
    public function getAvailableProgramIds(): array
    {
        if ($this->is_universal) {
            return Program::pluck('id')->toArray();
        }

        return $this->eligibilities()->distinct()->pluck('program_id')->toArray();
    }

    /**
     * Get all available level IDs for this course.
     *
     * @return array
     */
    public function getAvailableLevelIds(): array
    {
        if ($this->is_universal) {
            return Level::pluck('id')->toArray();
        }

        return $this->eligibilities()->distinct()->pluck('level_id')->toArray();
    }

    /**
     * Add program-level pairs to this available course.
     *
     * @param array $pairs Array of ['program_id' => int, 'level_id' => int]
     * @return void
     */
    public function addProgramLevelPairs(array $pairs): void
    {
        foreach ($pairs as $pair) {
            $this->eligibilities()->firstOrCreate([
                'program_id' => $pair['program_id'],
                'level_id' => $pair['level_id'],
            ]);
        }
    }

    /**
     * Remove program-level pairs from this available course.
     *
     * @param array $pairs Array of ['program_id' => int, 'level_id' => int]
     * @return void
     */
    public function removeProgramLevelPairs(array $pairs): void
    {
        foreach ($pairs as $pair) {
            $this->eligibilities()
                 ->where('program_id', $pair['program_id'])
                 ->where('level_id', $pair['level_id'])
                 ->delete();
        }
    }

    /**
     * Set program-level pairs for this available course (replaces existing).
     *
     * @param array $pairs Array of ['program_id' => int, 'level_id' => int]
     * @return void
     */
    public function setProgramLevelPairs(array $pairs): void
    {
        $this->eligibilities()->delete();
        $this->addProgramLevelPairs($pairs);
    }

    /**
     * Add programs with all their levels to this available course.
     *
     * @param array $programIds
     * @return void
     */
    public function addPrograms(array $programIds): void
    {
        $pairs = [];
        foreach ($programIds as $programId) {
            $levelIds = Level::pluck('id')->toArray();
            foreach ($levelIds as $levelId) {
                $pairs[] = ['program_id' => $programId, 'level_id' => $levelId];
            }
        }
        $this->addProgramLevelPairs($pairs);
    }

    /**
     * Add levels with all their programs to this available course.
     *
     * @param array $levelIds
     * @return void
     */
    public function addLevels(array $levelIds): void
    {
        $pairs = [];
        foreach ($levelIds as $levelId) {
            $programIds = Program::pluck('id')->toArray();
            foreach ($programIds as $programId) {
                $pairs[] = ['program_id' => $programId, 'level_id' => $levelId];
            }
        }
        $this->addProgramLevelPairs($pairs);
    }

    /**
     * Remove programs from this available course.
     *
     * @param array $programIds
     * @return void
     */
    public function removePrograms(array $programIds): void
    {
        $this->eligibilities()->whereIn('program_id', $programIds)->delete();
    }

    /**
     * Remove levels from this available course.
     *
     * @param array $levelIds
     * @return void
     */
    public function removeLevels(array $levelIds): void
    {
        $this->eligibilities()->whereIn('level_id', $levelIds)->delete();
    }
}