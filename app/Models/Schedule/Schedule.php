<?php

namespace App\Models\Schedule;

use App\Models\Term;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'code',
        'schedule_type_id',
        'term_id',
        'description',
        'start_date',
        'end_date',
        'day_starts_at',
        'day_ends_at',
        'slot_duration_minutes',
        'break_duration_minutes',
        'settings',
        'status',
        'finalized_at',
    ];


    /**
     * Attribute casting for Eloquent
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'day_starts_at' => 'datetime:H:i:s',
        'day_ends_at' => 'datetime:H:i:s',
        'slot_duration_minutes' => 'integer',
        'break_duration_minutes' => 'integer',
        'settings' => 'array',
        'finalized_at' => 'datetime'
    ];
    
    // Relationships
    public function scheduleType(): BelongsTo
    {
        return $this->belongsTo(ScheduleType::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(ScheduleSlot::class);
    }
}