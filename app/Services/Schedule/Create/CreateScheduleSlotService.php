<?php

namespace App\Services\Schedule\Create;

use App\Models\Schedule\ScheduleSlot;
use App\Models\Schedule\Schedule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exceptions\BusinessValidationException;

class CreateScheduleSlotService
{
    /**
     * Create a new schedule slot.
     *
     * @param array $data The slot data
     * @return ScheduleSlot
     * @throws BusinessValidationException
     */
    public function execute(array $data): ScheduleSlot
    {
        return DB::transaction(function () use ($data) {
            // Get the schedule
            $schedule = Schedule::with('scheduleType')->findOrFail($data['schedule_id']);
                        
            // Check for conflicts
            $this->validateSlotConflicts($schedule, $data);
            
            // Generate slot identifier and order
            $slotData = $this->prepareSlotData($schedule, $data);
            
            // Create the slot
            return ScheduleSlot::create($slotData);
        });
    }

    /**
     * Validate slot conflicts with existing slots
     *
     * @param Schedule $schedule
     * @param array $data
     * @throws BusinessValidationException
     */
    private function validateSlotConflicts(Schedule $schedule, array $data): void
    {
        $query = ScheduleSlot::where('schedule_id', $schedule->id)
            ->where(function ($q) use ($data) {
                $q->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                    ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']])
                    ->orWhere(function ($q) use ($data) {
                        $q->where('start_time', '<=', $data['start_time'])
                            ->where('end_time', '>=', $data['end_time']);
                    });
            });

        // Add day specific conditions
        if ($schedule->scheduleType->is_repetitive && $schedule->scheduleType->repetition_pattern === 'weekly') {
            $query->where('day_of_week', $data['day_of_week']);
        } else {
            $query->where('specific_date', $data['specific_date']);
        }

        if (isset($data['id'])) {
            $query->where('id', '!=', $data['id']);
        }

        if ($query->exists()) {
            throw new BusinessValidationException('A slot already exists in this time range');
        }
    }

    /**
     * Prepare slot data with generated identifier and order
     *
     * @param Schedule $schedule
     * @param array $data
     * @return array
     */
    private function prepareSlotData(Schedule $schedule, array $data): array
    {
        // Get the next order number
        $maxOrder = ScheduleSlot::where('schedule_id', $schedule->id)
            ->when($schedule->scheduleType->is_repetitive && $schedule->scheduleType->repetition_pattern === 'weekly', 
                function ($query) use ($data) {
                    return $query->where('day_of_week', $data['day_of_week']);
                },
                function ($query) use ($data) {
                    return $query->where('specific_date', $data['specific_date']);
                }
            )
            ->max('slot_order') ?? 0;

        $nextOrder = $maxOrder + 1;


        // Convert switch input 'on' to boolean true
        $isActive = isset($data['is_active']) ? 
            ($data['is_active'] === 'on' || $data['is_active'] === true || $data['is_active'] === 1) : 
            true;

        return array_merge($data, [
            'slot_order' => $nextOrder,
            'is_active' => $isActive,
            'schedule_id' => $schedule->id
        ]);
    }
}
