<?php

namespace App\Models\Schedule;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ScheduleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_slot_id',
        'assignable_id',
        'assignable_type',
        'title',
        'description',
        'location',
        'capacity',
        'enrolled',
        'resources',
        'status',
        'notes'
    ];

    protected $casts = [
        'capacity' => 'integer',
        'enrolled' => 'integer',
        'resources' => 'array'
    ];

    // Relationships
    public function scheduleSlot(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlot::class);
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }


    // Helper methods
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function hasCapacityLimit(): bool
    {
        return !is_null($this->capacity);
    }

    public function isFull(): bool
    {
        return $this->hasCapacityLimit() && $this->enrolled >= $this->capacity;
    }

    public function getAvailableSpacesAttribute(): int
    {
        if (!$this->hasCapacityLimit()) {
            return PHP_INT_MAX;
        }
        return max(0, $this->capacity - $this->enrolled);
    }

    public function getUtilizationPercentageAttribute(): float
    {
        if (!$this->hasCapacityLimit() || $this->capacity == 0) {
            return 0;
        }
        return round(($this->enrolled / $this->capacity) * 100, 2);
    }

    public function confirm(): bool
    {
        if ($this->isScheduled()) {
            $this->update(['status' => 'confirmed']);
            return true;
        }
        return false;
    }

    public function cancel(): bool
    {
        if (in_array($this->status, ['scheduled', 'confirmed'])) {
            $this->update(['status' => 'cancelled']);
            return true;
        }
        return false;
    }

    public function complete(): bool
    {
        if ($this->isConfirmed()) {
            $this->update(['status' => 'completed']);
            return true;
        }
        return false;
    }
}