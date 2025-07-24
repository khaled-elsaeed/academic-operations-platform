<?php

namespace App\Services\Schedule;

use App\Models\Schedule\ScheduleSlot;
use App\Models\Schedule\Schedule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exceptions\BusinessValidationException;

class SlotOperationService
{
    /**
     * Create a new schedule slot.
     *
     * @param array $data The slot data
     * @param Schedule $schedule The schedule instance
     * @return ScheduleSlot
     * @throws BusinessValidationException
     */
    public function createSlot(array $data, Schedule $schedule): ScheduleSlot
    {
        return DB::transaction(function () use ($data, $schedule) {
            // Check if slot time is within schedule time range
            $this->validateSlotTimeRange($schedule, $data['start_time'], $data['end_time']);
            
            // Check for conflicts
            $this->validateSlotConflicts($schedule, $data);
            
            // Generate slot identifier and order
            $slotData = $this->prepareSlotData($schedule, $data);
            
            // Create the slot
            return ScheduleSlot::create($slotData);
        });
    }

    /**
     * Validate that slot time is within schedule time range
     *
     * @param Schedule $schedule
     * @param string $startTime
     * @param string $endTime
     * @throws BusinessValidationException
     */
    private function validateSlotTimeRange(Schedule $schedule, string $startTime, string $endTime): void
    {
        $slotStart = Carbon::parse($startTime);
        $slotEnd = Carbon::parse($endTime);
        $scheduleStart = Carbon::parse($schedule->day_starts_at);
        $scheduleEnd = Carbon::parse($schedule->day_ends_at);

        if ($slotStart->lt($scheduleStart) || $slotEnd->gt($scheduleEnd)) {
            throw new BusinessValidationException(
                'Slot time must be within schedule time range (' . 
                $scheduleStart->format('H:i') . ' - ' . 
                $scheduleEnd->format('H:i') . ')'
            );
        }
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

        // Generate slot identifier
        $identifier = $this->generateSlotIdentifier($schedule, $data, $nextOrder);

        // Convert switch input 'on' to boolean true
        $isActive = isset($data['is_active']) ? 
            ($data['is_active'] === 'on' || $data['is_active'] === true || $data['is_active'] === 1) : 
            true;

        return array_merge($data, [
            'slot_order' => $nextOrder,
            'slot_identifier' => $identifier,
            'is_active' => $isActive,
            'schedule_id' => $schedule->id
        ]);
    }

    /**
     * Generate a unique slot identifier
     *
     * @param Schedule $schedule
     * @param array $data
     * @param int $order
     * @return string
     */
    private function generateSlotIdentifier(Schedule $schedule, array $data, int $order): string
    {
        $prefix = strtoupper(substr($schedule->title, 0, 3));
        
        if ($schedule->scheduleType->is_repetitive && $schedule->scheduleType->repetition_pattern === 'weekly') {
            $dayPrefix = substr(ucfirst($data['day_of_week']), 0, 3);
        } else {
            $dayPrefix = Carbon::parse($data['specific_date'])->format('md');
        }
        
        $timePrefix = Carbon::parse($data['start_time'])->format('Hi');
        $orderPadded = str_pad($order, 2, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$dayPrefix}-{$timePrefix}-{$orderPadded}";
    }
}
