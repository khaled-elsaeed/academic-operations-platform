<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Support\Facades\DB;

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
        'mode',
    ];

    /**
     * Enum values for mode.
     */
    public const MODE_UNIVERSAL = 'universal';
    public const MODE_INDIVIDUAL = 'individual';
    public const MODE_ALL_PROGRAMS = 'all_programs';
    public const MODE_ALL_LEVELS = 'all_levels';

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
     * Get the schedules for this available course.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(AvailableCourseSchedule::class);
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
     * Get all existing (program_id, level_id) pairs for this available course as an array of arrays.
     *
     * @return array
     */
    public function getExistingProgramLevelPairs(): array
    {
        return $this->eligibilities()
            ->get(['program_id', 'level_id'])
            ->map(function ($item) {
                return [
                    'program_id' => $item->program_id,
                    'level_id' => $item->level_id,
                ];
            })
            ->toArray();
    }

    /**
     * Get the enrollments for this available course.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'course_id', 'course_id')
                    ->where('enrollments.term_id', $this->term_id);
    }

    /**
     * Scope a query to filter available courses by program, level, and term.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|null  $programId
     * @param  int|null  $levelId
     * @param  int|null  $termId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    #[Scope]
    /**
     * Scope a query to filter available courses by program, level, and term.
     * If $exceptionForDifferentLevels is true, show all available courses for the student's program, for all levels (ignore $levelId).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|null  $programId
     * @param  int|null  $levelId
     * @param  int|null  $termId
     * @param  bool|null $exceptionForDifferentLevels
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function available(
        Builder $query,
        ?int $programId = null,
        ?int $levelId = null,
        ?int $termId = null,
        ?bool $exceptionForDifferentLevels = false
    ): Builder
    {
        $query = $query->when($termId, function ($q) use ($termId) {
            return $q->where('term_id', $termId);
        });

        if ($programId) {
            $query = $query->where(function ($subQuery) use ($programId) {
                $subQuery
                    ->where('mode', self::MODE_UNIVERSAL)
                    ->orWhere(function ($q2) use ($programId) {
                        $q2->where('mode', self::MODE_ALL_PROGRAMS);
                    })
                    ->orWhereHas('eligibilities', function ($pairQuery) use ($programId) {
                        $pairQuery->where('program_id', $programId);
                    });
            });
        }

        // If exceptionForDifferentLevels is true, do NOT filter by level at all (show all levels for the program)
        if (!$exceptionForDifferentLevels && $levelId) {
            $query = $query->where(function ($subQuery) use ($levelId) {
                $subQuery
                    ->where('mode', self::MODE_UNIVERSAL)
                    ->orWhere(function ($q2) use ($levelId) {
                        $q2->where('mode', self::MODE_ALL_LEVELS);
                    })
                    ->orWhereHas('eligibilities', function ($pairQuery) use ($levelId) {
                        $pairQuery->where('level_id', $levelId);
                    });
            });
        }

        return $query;
    }

    /**
     * Scope a query to filter available courses that a student is enrolled in.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $studentId
     * @param  int  $termId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    #[Scope]
    protected function enrolled(Builder $query, int $studentId, int $termId): Builder
    {
        return $query->whereExists(function ($subQuery) use ($studentId, $termId) {
            $subQuery->select(\DB::raw(1))
                     ->from('enrollments')
                     ->whereColumn('enrollments.course_id', 'available_courses.course_id')
                     ->where('enrollments.student_id', $studentId)
                     ->where('enrollments.term_id', $termId);
        });
    }

    /**
     * Scope a query to filter available courses that a student is NOT enrolled in.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $studentId
     * @param  int  $termId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    #[Scope]
    protected function notEnrolled(Builder $query, int $studentId, int $termId): Builder
    {
        return $query->whereNotExists(function ($subQuery) use ($studentId, $termId) {
            $subQuery->select(\DB::raw(1))
                     ->from('enrollments')
                     ->whereColumn('enrollments.course_id', 'available_courses.course_id')
                     ->where('enrollments.student_id', $studentId)
                     ->where('enrollments.term_id', $termId);
        });
    }
 
    /**
     * Get all available level IDs for this course.
     *
     * @return array
     */
    public function getAvailableLevelIds(): array
    {
        if ($this->mode === self::MODE_UNIVERSAL || $this->mode === self::MODE_ALL_LEVELS) {
            return Level::pluck('id')->toArray();
        }

        return $this->eligibilities()->distinct()->pluck('level_id')->toArray();
    }

    /**
     * Add program-level pairs to this available course.
     *
     * @param array $pairs Array of ['program_id' => int, 'level_id' => int, 'group' => int]
     * @return void
     */
    public function addProgramLevelPairs(array $pairs): void
    {
        foreach ($pairs as $pair) {
            // Create eligibility record with appropriate error handling
            try {
                $this->eligibilities()->create([
                    'program_id' => $pair['program_id'],
                    'level_id' => $pair['level_id'],
                    'group' => $pair['group'] ?? 1,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Ignore duplicate entry errors (unique constraint violations)
                if ($e->errorInfo[1] !== 1062) { // MySQL duplicate entry error code
                    throw $e;
                }
            }
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
     * Clear all program-level pairs for this available course.
     *
     * @return void
     */
    public function clearProgramLevelPairs(): void
    {
        $this->eligibilities()->delete();
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

    /**
     * Get all required activity types for this available course.
     */
    public function getRequiredActivityTypes(): \Illuminate\Support\Collection
    {
        return $this->schedules()->distinct()->pluck('activity_type');
    }

    /**
     * Get one schedule for each activity type matching the given group.
     */
    public function getSchedulesForGroup(string $group,int $level,int $program): \Illuminate\Support\Collection
    {
        $requiredTypes = $this->getRequiredActivityTypes();
        $schedules = collect();
        $isUniversal = $this->mode === self::MODE_UNIVERSAL;

        foreach ($requiredTypes as $type) {

            if ($isUniversal) {
                    $schedule = $this->schedules()
                    ->where('activity_type', $type)
                    ->first();
                } else {
            $schedule = $this->schedules()
                ->where('activity_type', $type)
                ->where('group', $group)
                ->where('level_id',$level)
                ->where('program_id',$program)
                ->first();
            }

            if ($schedule) {
                $schedules->push($schedule);
            }
        }

        return $schedules;
    }
}