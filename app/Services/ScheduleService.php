<?php

namespace App\Services\Schedule;

use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\DataTables;

class ScheduleService
{
    /**
     * Create a new schedule.
     */
    public function createSchedule(array $data): Schedule
    {
        return Schedule::create($data);
    }

    /**
     * Update an existing schedule.
     */
    public function updateSchedule(Schedule $schedule, array $data): Schedule
    {
        $schedule->update($data);
        return $schedule;
    }

    /**
     * Delete a schedule and its slots.
     */
    public function deleteSchedule(Schedule $schedule): void
    {
        $schedule->slots()->delete(); // Delete all related slots
        $schedule->delete();
    }

    /**
     * Get all schedules for display.
     */
    public function getDatatable(): JsonResponse
    {
        $query = Schedule::with('scheduleType', 'creator');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('type', fn($schedule) => $schedule->scheduleType->name ?? '-')
            ->addColumn('created_by', fn($schedule) => $schedule->creator->name ?? '-')
            ->addColumn('status', fn($schedule) => ucfirst($schedule->status))
            ->addColumn('actions', fn($schedule) => $this->renderActionButtons($schedule))
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Render action buttons for UI.
     */
    protected function renderActionButtons($schedule): string
    {
        $buttons = '<div class="d-flex gap-2">';
        if (auth()->user()?->can('schedule.delete')) {
            $buttons .= '<button type="button"
                class="btn btn-sm btn-icon btn-danger rounded-circle deleteScheduleBtn"
                data-id="' . e($schedule->id) . '" title="Delete">
                <i class="bx bx-trash"></i></button>';
        }
        $buttons .= '</div>';
        return $buttons;
    }

    /**
     * Get a schedule by ID with related slots.
     */
    public function getScheduleWithSlots(int $scheduleId): ?Schedule
    {
        return Schedule::with('slots')->findOrFail($scheduleId);
    }

    /**
     * Finalize a schedule.
     */
    public function finalizeSchedule(Schedule $schedule): Schedule
    {
        $schedule->update([
            'status' => 'finalized',
            'finalized_at' => now(),
        ]);

        return $schedule;
    }
}