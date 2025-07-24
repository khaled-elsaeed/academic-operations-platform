<?php

namespace App\Models\Schedule;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected $appends = [
        'formatted_start_time',
        'formatted_end_time',
        'formatted_specific_date',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i:s',
            'end_time' => 'datetime:H:i:s',
            'duration_minutes' => 'integer',
            'specific_date' => 'date',
            'slot_order' => 'integer',
            'is_active' => 'boolean'
        ];
    }

    // Relationships
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ScheduleAssignment::class);
    }

    // Formatted Attributes
    protected function formattedStartTime(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->start_time ? formatTime($this->start_time) : null,
        );
    }

    protected function formattedEndTime(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->end_time ? formatTime($this->end_time) : null,
        );
    }

    protected function formattedSpecificDate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->specific_date ? formatDate($this->specific_date) : null,
        );
    }
}