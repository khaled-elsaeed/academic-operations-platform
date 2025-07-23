<?php

namespace App\Models\Schedule;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_repetitive',
        'repetition_pattern',
        'default_settings',
        'is_active'
    ];

    protected $casts = [
        'is_repetitive' => 'boolean',
        'default_settings' => 'array',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
