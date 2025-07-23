<?php

namespace App\Services\Schedule;

use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleSlot;
use App\Models\Schedule\ScheduleType;
use App\Models\Term;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Exceptions\BusinessValidationException;

class ScheduleService
{
    /**
     * Create a new schedule with slots.
     *
     * @param array $data Schedule data
     * @return Schedule Created schedule
     * @throws BusinessValidationException
     */
    public function createSchedule(array $data): Schedule
    {
        return DB::transaction(function () use ($data) {
            $schedule = $this->createScheduleBase($data);
            $this->createScheduleSlots($schedule, $data);
            return $schedule;
        });
    }

    /**
     * Create the base schedule record.
     *
     * @param array $data Schedule data
     * @return Schedule Created schedule
     */
    private function createScheduleBase(array $data): Schedule
    {
        $titleAndCode = $this->generateScheduleTitleAndCode($data);

        return Schedule::create([
            'title' => $titleAndCode['title'],
            'code' => $titleAndCode['code'],
            'schedule_type_id' => $data['schedule_type_id'],
            'term_id' => $data['term_id'],
            'description' => $data['description'] ?? null,
            'day_starts_at' => $data['day_starts_at'],
            'day_ends_at' => $data['day_ends_at'],
            'slot_duration_minutes' => $data['slot_duration_minutes'],
            'break_duration_minutes' => $data['break_duration_minutes'] ?? 0,
            'pattern' => $data['pattern'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'] ?? 'draft',
        ]);
    }

    /**
     * Create schedule slots based on pattern type.
     *
     * @param Schedule $schedule Schedule instance
     * @param array $data Schedule data
     * @throws BusinessValidationException
     */
    private function createScheduleSlots(Schedule $schedule, array $data): void
    {
        $pattern = $data['pattern'] ?? null;

        match ($pattern) {
            'repetitive' => $this->createRepetitiveSlots($schedule, $data),
            'range' => $this->createRangeSlots($schedule, $data),
            default => throw new BusinessValidationException('Invalid schedule pattern')
        };
    }

    /**
     * Generate unique schedule title and code.
     *
     * @param array $data Schedule data
     * @return array Title and code
     */
    private function generateScheduleTitleAndCode(array $data): array
    {
        $scheduleType = ScheduleType::findOrFail($data['schedule_type_id']);
        $term = Term::findOrFail($data['term_id']);

        $title = "{$scheduleType->name} - {$term->name}";
        $baseCode = "SCH-{$term->code}{$scheduleType->id}";
        $code = $this->generateUniqueCode($baseCode);

        return ['title' => $title, 'code' => $code];
    }

    /**
     * Generate unique code for schedule.
     *
     * @param string $baseCode Base code
     * @return string Unique code
     */
    private function generateUniqueCode(string $baseCode): string
    {
        $code = $baseCode;
        $counter = 1;

        while (Schedule::where('code', $code)->exists()) {
            $code = "{$baseCode}-{$counter}";
            $counter++;
        }

        return $code;
    }

    /**
     * Update an existing schedule.
     *
     * @param Schedule $schedule Schedule to update
     * @param array $data Update data
     * @return Schedule Updated schedule
     * @throws BusinessValidationException
     */
    public function updateSchedule(Schedule $schedule, array $data): Schedule
    {
        return DB::transaction(function () use ($schedule, $data) {
            $needsSlotRecreation = $this->needsSlotRecreation($schedule, $data);

            if ($needsSlotRecreation) {
                $schedule->slots()->delete();
                $schedule->update($data);
                $this->createScheduleSlots($schedule, $data);
            } else {
                $schedule->update($data);
            }

            return $schedule;
        });
    }

    /**
     * Check if slot recreation is needed after update.
     *
     * @param Schedule $schedule Current schedule
     * @param array $data Update data
     * @return bool Whether slot recreation is needed
     */
    private function needsSlotRecreation(Schedule $schedule, array $data): bool
    {
        $criticalFields = [
            'pattern', 'day_starts_at', 'day_ends_at',
            'slot_duration_minutes', 'break_duration_minutes',
            'start_date', 'end_date'
        ];

        foreach ($criticalFields as $field) {
            if (isset($data[$field]) && $schedule->{$field} != $data[$field]) {
                return true;
            }
        }

        // Check if days changed for repetitive schedules
        if ($schedule->pattern === 'repetitive' && isset($data['days'])) {
            $existingDays = $schedule->slots()
                ->distinct('day_of_week')
                ->pluck('day_of_week')
                ->sort()
                ->values()
                ->toArray();

            $newDays = collect($data['days'])->sort()->values()->toArray();

            return $existingDays !== $newDays;
        }

        return false;
    }

    /**
     * Delete a schedule and all its slots.
     *
     * @param Schedule $schedule Schedule to delete
     */
    public function deleteSchedule(Schedule $schedule): void
    {
        DB::transaction(function () use ($schedule) {
            $schedule->slots()->delete();
            $schedule->delete();
        });
    }

    /**
     * Finalize a schedule (change status to finalized).
     *
     * @param Schedule $schedule Schedule to finalize
     * @return Schedule Finalized schedule
     * @throws BusinessValidationException
     */
    public function finalizeSchedule(Schedule $schedule): Schedule
    {
        if ($schedule->status === 'finalized') {
            throw new BusinessValidationException('Schedule is already finalized');
        }

        $schedule->update([
            'status' => 'finalized',
            'finalized_at' => now(),
        ]);

        return $schedule;
    }


    /**
     * Get schedule with slots for display.
     *
     * @param int $scheduleId Schedule ID
     * @return Schedule|null Schedule with slots
     */
    public function getScheduleDetails(int $scheduleId): ?Schedule
    {
        return Schedule::with([
            'slots' => fn($query) => $query->orderBy('day_of_week')->orderBy('slot_order'),
            'scheduleType',
            'term'
        ])->findOrFail($scheduleId);
    }


    /**
     * Get DataTable response for schedules listing.
     *
     * @return JsonResponse DataTable JSON response
     */
    public function getDatatable(): JsonResponse
    {
        $query = Schedule::with(['scheduleType', 'term']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('type', fn($schedule) => $schedule->scheduleType->name ?? '-')
            ->addColumn('term', fn($schedule) => $schedule->term->name ?? '-')
            ->addColumn('status', fn($schedule) => ucfirst($schedule->status))
            ->addColumn('slots_count', fn($schedule) => $schedule->slots()->count())
            ->editColumn('day_starts_at', fn($schedule) => formatTime($schedule->day_starts_at))
            ->editColumn('day_ends_at', fn($schedule) => formatTime($schedule->day_ends_at))
            ->addColumn('actions', fn($schedule) => $this->renderActionButtons($schedule))
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Render action buttons for DataTable.
     *
     * @param Schedule $schedule Schedule instance
     * @return string HTML buttons
     */
    private function renderActionButtons(Schedule $schedule): string
    {
        $buttons = '<div class="d-flex gap-2">';

            // View button
            $buttons .= sprintf(
                '<button type="button" class="btn btn-sm btn-icon btn-info rounded-circle viewScheduleBtn" data-id="%d" title="View">
                    <i class="bx bx-show"></i>
                </button>',
                $schedule->id
            );

    
            $buttons .= sprintf(
                '<button type="button" class="btn btn-sm btn-icon btn-danger rounded-circle deleteScheduleBtn" data-id="%d" title="Delete">
                    <i class="bx bx-trash"></i>
                </button>',
                $schedule->id
            );

        return $buttons . '</div>';
    }

    /**
     * Get schedule statistics.
     *
     * @return array Statistics data
     */
    public function getStats(): array
    {
        $total = Schedule::count();
        $lastUpdateTime = Schedule::max('updated_at');

        return [
            'total' => [
                'count' => formatNumber($total),
                'lastUpdateTime' => formatDate($lastUpdateTime),
            ],
            
        ];
    }


    /**
     * Create repetitive schedule slots.
     *
     * @param Schedule $schedule Schedule instance
     * @param array $data Schedule data
     * @throws BusinessValidationException
     */
    private function createRepetitiveSlots(Schedule $schedule, array $data): void
    {
        $this->validateRepetitiveData($data);

        $slotsPerDay = $this->generateDailySlots($data);
        $this->insertRepetitiveSlots($schedule, $data['days'], $slotsPerDay);
    }

    /**
     * Validate repetitive schedule data.
     *
     * @param array $data Schedule data
     * @throws BusinessValidationException
     */
    private function validateRepetitiveData(array $data): void
    {
        if (!isset($data['days']) || !is_array($data['days'])) {
            throw new BusinessValidationException('Days array is required for repetitive schedules');
        }

        $this->validateTimeData($data);
    }

    /**
     * Generate daily time slots.
     *
     * @param array $data Schedule data
     * @return array Array of slot data
     */
    private function generateDailySlots(array $data): array
    {
        $dayStartsAt = Carbon::parse($data['day_starts_at']);
        $dayEndsAt = Carbon::parse($data['day_ends_at']);
        $slotDurationMinutes = (int) $data['slot_duration_minutes'];
        $breakDurationMinutes = (int) ($data['break_duration_minutes'] ?? 0);

        $intervalMinutes = $slotDurationMinutes + $breakDurationMinutes;
        $period = CarbonPeriod::create($dayStartsAt, "{$intervalMinutes} minutes", $dayEndsAt);

        return collect($period)
            ->map(function ($time) use ($slotDurationMinutes, $dayEndsAt) {
                $endTime = $time->copy()->addMinutes($slotDurationMinutes);

                if ($endTime->gt($dayEndsAt)) {
                    return null;
                }

                return [
                    'start_time' => $time->format('H:i:s'),
                    'end_time' => $endTime->format('H:i:s'),
                    'duration_minutes' => $slotDurationMinutes,
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Insert repetitive slots into database.
     *
     * @param Schedule $schedule Schedule instance
     * @param array $days Days of week
     * @param array $slotsPerDay Daily slots data
     */
    private function insertRepetitiveSlots(Schedule $schedule, array $days, array $slotsPerDay): void
    {
        $slots = [];

        foreach ($days as $day) {
            foreach ($slotsPerDay as $index => $slot) {
                $slots[] = [
                    'schedule_id' => $schedule->id,
                    'slot_identifier' => "SLOT-{$schedule->id}-{$day}-{$index}",
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'duration_minutes' => $slot['duration_minutes'],
                    'day_of_week' => $day,
                    'slot_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($slots)) {
            ScheduleSlot::insert($slots);
        }
    }

    /**
     * Create range schedule slots.
     *
     * @param Schedule $schedule Schedule instance
     * @param array $data Schedule data
     * @throws BusinessValidationException
     */
    private function createRangeSlots(Schedule $schedule, array $data): void
    {
        $this->validateRangeData($data);

        $slots = $this->generateRangeSlots($schedule, $data);

        if (!empty($slots)) {
            ScheduleSlot::insert($slots);
        }
    }

    /**
     * Validate range schedule data.
     *
     * @param array $data Schedule data
     * @throws BusinessValidationException
     */
    private function validateRangeData(array $data): void
    {
        if (!isset($data['start_date']) || !isset($data['end_date'])) {
            throw new BusinessValidationException('Start date and end date are required for range schedules');
        }

        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        if ($startDate->gte($endDate)) {
            throw new BusinessValidationException('Start date must be before end date');
        }

        $this->validateTimeData($data);
    }

    /**
     * Generate range schedule slots.
     *
     * @param Schedule $schedule Schedule instance
     * @param array $data Schedule data
     * @return array Array of slot data
     */
    private function generateRangeSlots(Schedule $schedule, array $data): array
    {
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $dayStartsAt = Carbon::parse($data['day_starts_at']);
        $dayEndsAt = Carbon::parse($data['day_ends_at']);
        $slotDurationMinutes = (int) $data['slot_duration_minutes'];
        $breakDurationMinutes = (int) ($data['break_duration_minutes'] ?? 0);

        $intervalMinutes = $slotDurationMinutes + $breakDurationMinutes;
        $slots = [];

        $datePeriod = CarbonPeriod::create($startDate, '1 day', $endDate);

        foreach ($datePeriod as $date) {
            $dayStart = $date->copy()->setTimeFrom($dayStartsAt);
            $dayEnd = $date->copy()->setTimeFrom($dayEndsAt);

            $timePeriod = CarbonPeriod::create($dayStart, "{$intervalMinutes} minutes", $dayEnd);

            foreach ($timePeriod as $index => $time) {
                $endTime = $time->copy()->addMinutes($slotDurationMinutes);

                if ($endTime->gt($dayEnd)) {
                    break;
                }

                $slots[] = [
                    'schedule_id' => $schedule->id,
                    'slot_identifier' => "SLOT-{$schedule->id}-{$date->format('Y-m-d')}-{$index}",
                    'start_time' => $time->format('H:i:s'),
                    'end_time' => $endTime->format('H:i:s'),
                    'duration_minutes' => $slotDurationMinutes,
                    'specific_date' => $date->format('Y-m-d'),
                    'day_of_week' => $date->dayOfWeek,
                    'slot_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        return $slots;
    }

    /**
     * Validate time-related data.
     *
     * @param array $data Schedule data
     * @throws BusinessValidationException
     */
    private function validateTimeData(array $data): void
    {
        $dayStartsAt = Carbon::parse($data['day_starts_at']);
        $dayEndsAt = Carbon::parse($data['day_ends_at']);
        $slotDurationMinutes = (int) $data['slot_duration_minutes'];

        if ($dayStartsAt->gte($dayEndsAt)) {
            throw new BusinessValidationException('Day start time must be before end time');
        }

        if ($slotDurationMinutes <= 0) {
            throw new BusinessValidationException('Slot duration must be greater than 0');
        }
    }
}