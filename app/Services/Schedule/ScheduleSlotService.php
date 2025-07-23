<?php

namespace App\Services\Schedule;

use App\Models\Schedule\ScheduleSlot;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleType;
use App\Models\Term;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Exceptions\BusinessValidationException;

class ScheduleSlotService
{
    /**
     * Delete a schedule slot.
     *
     * @param ScheduleSlot $slot ScheduleSlot to delete
     */
    public function deleteSchedule(ScheduleSlot $slot): void
    {
        DB::transaction(function () use ($slot) {
            $slot->delete();
        });
    }

    /**
     * Get DataTable response for schedule slots listing.
     *
     * @return JsonResponse DataTable JSON response
     */
    public function getDatatable(): JsonResponse
    {
        $query = ScheduleSlot::with(['schedule', 'schedule.scheduleType', 'schedule.term']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('schedule', fn($slot) => $slot->schedule?->name ?? '-')
            ->addColumn('type', fn($slot) => $slot->schedule?->scheduleType?->name ?? '-')
            ->addColumn('term', fn($slot) => $slot->schedule?->term?->name ?? '-')
            ->addColumn('status', fn($slot) => ucfirst($slot->status ?? '-'))
            ->editColumn('starts_at', fn($slot) => formatTime($slot->starts_at))
            ->editColumn('ends_at', fn($slot) => formatTime($slot->ends_at))
            ->addColumn('actions', fn($slot) => $this->renderActionButtons($slot))
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Render action buttons for DataTable.
     *
     * @param ScheduleSlot $slot ScheduleSlot instance
     * @return string HTML buttons
     */
    private function renderActionButtons(ScheduleSlot $slot): string
    {
        $buttons = '<div class="d-flex gap-2">';

        // View button
        $buttons .= sprintf(
            '<button type="button" class="btn btn-sm btn-icon btn-info rounded-circle viewScheduleSlotBtn" data-id="%d" title="View">
                <i class="bx bx-show"></i>
            </button>',
            $slot->id
        );

        // Delete button
        $buttons .= sprintf(
            '<button type="button" class="btn btn-sm btn-icon btn-danger rounded-circle deleteScheduleSlotBtn" data-id="%d" title="Delete">
                <i class="bx bx-trash"></i>
            </button>',
            $slot->id
        );

        return $buttons . '</div>';
    }

    /**
     * Get schedule slot statistics.
     *
     * @return array Statistics data
     */
    public function getStats(): array
    {
        $total = ScheduleSlot::count();
        $lastUpdateTime = ScheduleSlot::max('updated_at');

        return [
            'total' => [
                'count' => formatNumber($total),
                'lastUpdateTime' => formatDate($lastUpdateTime),
            ],
        ];
    }
}