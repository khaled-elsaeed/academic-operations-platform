<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseEligibility extends Model
{
    protected $table = 'course_eligibilities';
    
    protected $fillable = [
        'available_course_id',
        'program_id',
        'level_id',
        'group',
    ];

    public function availableCourse(): BelongsTo
    {
        return $this->belongsTo(AvailableCourse::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }
} 