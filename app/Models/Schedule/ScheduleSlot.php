<?php

namespace App\Models\Schedule;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'slot_identifier',
        'start_time',
        'end_time',
        'duration_minutes',
        'specific_date',
        'day_of_week',
        'slot_order',
        'is_active'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'duration_minutes' => 'integer',
        'specific_date' => 'date',
        'slot_order' => 'integer',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ScheduleAssignment::class);
    }
}
