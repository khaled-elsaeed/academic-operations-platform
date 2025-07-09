<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Level extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function availableCourseLevels(): HasMany
    {
        return $this->hasMany(AvailableCourseLevel::class);
    }
} 