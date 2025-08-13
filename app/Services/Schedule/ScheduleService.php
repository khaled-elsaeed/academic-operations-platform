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
     * Get schedule with slots for display.
     *
     * @param int $scheduleId Schedule ID
     * @return Schedule|null Schedule with slots
     */
    public function getScheduleDetails(int $scheduleId): ?Schedule
    {
        $schedule = Schedule::with([
            'slots' => fn($query) => $query->orderBy('day_of_week')->orderBy('slot_order'),
            'scheduleType',
            'term'
        ])->findOrFail($scheduleId);
        return $schedule;
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
            ->addColumn('term', fn($schedule) => $schedule->term->name ?? '-')
            ->addColumn('start_time', fn($schedule) => $schedule->start_time ? $schedule->start_time->format('H:i') : '-')
            ->addColumn('end_time', fn($schedule) => $schedule->end_time ? $schedule->end_time->format('H:i') : '-')
            ->addColumn('status', fn($schedule) => ucfirst($schedule->status))
            ->addColumn('slots_count', fn($schedule) => $schedule->slots()->count())
            ->addColumn('actions', fn($schedule) => $this->renderActionButtons($schedule))
            ->orderColumn('type', function ($query, $order) {
                return $query->join('schedule_types', 'schedules.schedule_type_id', '=', 'schedule_types.id')
                            ->orderBy('schedule_types.name', $order)
                            ->select('schedules.*');
            })
            ->orderColumn('term', function ($query, $order) {
                return $query->join('terms', 'schedules.term_id', '=', 'terms.id')
                            ->orderBy('terms.code', $order)
                            ->select('schedules.*');
            })
            ->orderColumn('slots_count', function ($query, $order) {
                return $query->withCount('slots')
                            ->orderBy('slots_count', $order);
            })
            ->orderColumn('formatted_day_starts_at', function ($query, $order) {
                return $query->orderBy('day_starts_at', $order);
            })
            ->orderColumn('formatted_day_ends_at', function ($query, $order) {
                return $query->orderBy('day_ends_at', $order);
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