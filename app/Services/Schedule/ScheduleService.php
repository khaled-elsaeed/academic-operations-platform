<?php

namespace App\Services\Schedule;

use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleSlot;
use App\Services\Schedule\Create\CreateScheduleService;
use App\Services\Schedule\Update\UpdateScheduleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\DataTables;
use App\Exceptions\BusinessValidationException;

class ScheduleService
{
    /**
     * Get all active schedules
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllActive()
    {
        return Schedule::with(['term', 'scheduleType'])
            ->orderBy('title')
            ->get();
    }

    /**
     * Create a new schedule with slots.
     *
     * @param array $data Schedule data
     * @return Schedule Created schedule
     * @throws BusinessValidationException
     */
    public function createSchedule(array $data): Schedule
    {
        return app(CreateScheduleService::class)->execute($data);
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
     * Get schedule with slots for display, with formatted dates.
     *
     * @param int $scheduleId Schedule ID
     * @return array|null Schedule details with formatted dates
     */
    public function getScheduleDetails(int $scheduleId): ?array
    {
        // Define the correct day order (Saturday first)
        $dayOrder = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        $schedule = Schedule::with([
            'slots' => function($query) use ($dayOrder) {
            $orderExpr = "FIELD(day_of_week, '" . implode("','", $dayOrder) . "')";
            $query->orderByRaw($orderExpr)->orderBy('slot_order');
            },
            'scheduleType',
            'term'
        ])->findOrFail($scheduleId);

        if (!$schedule) {
            return null;
        }

        // Format the schedule data and dates
        return [
            'id' => $schedule->id,
            'title' => $schedule->title,
            'status' => $schedule->status,
            // keep a simple 'type' string for backward compatibility
            'type' => $schedule->scheduleType?->name,
            'term' => $schedule->term?->name,
            'day_starts_at' => $schedule->day_starts_at ? formatDate($schedule->day_starts_at) : null,
            'day_ends_at' => $schedule->day_ends_at ? formatDate($schedule->day_ends_at) : null,
            'created_at' => $schedule->created_at ? formatDate($schedule->created_at) : null,
            'updated_at' => $schedule->updated_at ? formatDate($schedule->updated_at) : null,
            'schedule_type' => $schedule->scheduleType ? [
                'id' => $schedule->scheduleType->id,
                'name' => $schedule->scheduleType->name,
                'is_repetitive' => $schedule->scheduleType->is_repetitive,
                'repetitive_pattern' => $schedule->scheduleType->repetitive_pattern,
            ] : null,
            'slots' => $schedule->slots->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'day_of_week' => ucfirst($slot->day_of_week),
                    'slot_order' => $slot->slot_order,
                    'start_time' => $slot->start_time ? formatTime($slot->start_time) : null,
                    'end_time' => $slot->end_time ? formatTime($slot->end_time) : null,
                    'label' => $slot->label ?? null,
                ];
            })->toArray(),
            // return schedule_type as an object with useful fields
            
        ];
    }

    /**
     * Get available days and slots for a given schedule.
     *
     * @param int $scheduleId
     * @return array
     */
    public function getDaysAndSlots(int $scheduleId): array
    {
        // Fetch slots for the schedule, ordered by slot order
        $slots = ScheduleSlot::where('schedule_id', $scheduleId)
            ->orderBy('slot_order')
            ->get();

        // Define the correct day order (Saturday first as per frontend expectation)
        $dayOrder = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        // Group slots by day_of_week
        $days = [];
        foreach ($slots as $slot) {
            $day = $slot->day_of_week;
            if (!isset($days[$day])) {
                $days[$day] = [
                    'day_of_week' => $day,
                    'slots' => [],
                ];
            }
            $days[$day]['slots'][] = [
                'id' => $slot->id,
                'slot_order' => $slot->slot_order,
                'start_time' => $slot->start_time ? $slot->start_time->format('H:i') : null,
                'end_time' => $slot->end_time ? $slot->end_time->format('H:i') : null,
                'label' => $slot->label ?? null,
            ];
        }

        // Sort days according to the correct weekly order
        $sortedDays = [];
        foreach ($dayOrder as $day) {
            if (isset($days[$day])) {
                $sortedDays[] = $days[$day];
            }
        }

        return $sortedDays;
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
            ->addColumn('day_starts_at', fn($schedule) => formatTime($schedule->day_starts_at))
            ->addColumn('day_ends_at', fn($schedule) => formatTime($schedule->day_ends_at))
            ->addColumn('status', fn($schedule) => ucfirst($schedule->status))
            ->addColumn('slots_count', fn($schedule) => $schedule->slots()->count())
            ->addColumn('actions', fn($schedule) => $this->renderActionButtons($schedule))
            ->orderColumn('type', function ($query, $order) {
                return $query->join('schedule_types', 'schedules.schedule_type_id', '=', 'schedule_types.id')
                            ->orderBy('schedule_types.name', $order)
                            ->select('schedules.*');
            })
            ->orderColumn('slots_count', function ($query, $order) {
                return $query->withCount('slots')
                            ->orderBy('slots_count', $order);
            })
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
}