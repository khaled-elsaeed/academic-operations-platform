<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicAdvisorAccess extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'academic_advisor_accesses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'advisor_id',
        'level_id',
        'program_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the advisor associated with this access rule.
     */
    public function advisor()
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }

    /**
     * Get the level associated with this access rule.
     */
    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * Get the program associated with this access rule.
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Scope to filter active access rules (where is_active is true).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter access rules for a specific advisor.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $advisorId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAdvisor($query, int $advisorId)
    {
        return $query->where('advisor_id', $advisorId);
    }

    /**
     * Scope to filter access rules for a specific level.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $levelId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForLevel($query, int $levelId)
    {
        return $query->where('level_id', $levelId);
    }

    /**
     * Scope to filter access rules for a specific program.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $programId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForProgram($query, int $programId)
    {
        return $query->where('program_id', $programId);
    }

    /**
     * Scope to filter access rules for a specific level and program combination.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $levelId
     * @param int $programId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForLevelAndProgram($query, int $levelId, int $programId)
    {
        return $query->where('level_id', $levelId)->where('program_id', $programId);
    }
} 