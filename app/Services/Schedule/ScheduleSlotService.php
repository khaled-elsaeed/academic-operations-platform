<?php

namespace App\Services\Schedule;

use App\Models\Schedule\ScheduleSlot;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleType;
use App\Models\Term;
use App\Services\Schedule\Create\CreateScheduleSlotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Exceptions\BusinessValidationException;

class ScheduleSlotService
{
    protected CreateScheduleSlotService $createService;

    public function __construct(CreateScheduleSlotService $createService)
    {
        $this->createService = $createService;
    }

    /**
     * Create a new schedule slot.
     *
     * @param array $data The slot data
     * @return ScheduleSlot
     * @throws BusinessValidationException
     */
    public function createSlot(array $data): ScheduleSlot
    {
        return $this->createService->execute($data);
    }

    /**
     * Get slot details.
     *
     * @param int $id The slot ID
     * @return ScheduleSlot
     */
    public function getSlotDetails(int $id): ScheduleSlot
    {
        return ScheduleSlot::findOrFail($id);
    }



    /**
     * Update a schedule slot.
     *
     * @param int $id
     * @param array $data
     * @return ScheduleSlot
     * @throws BusinessValidationException
     */
    public function updateSlot(int $id, array $data): ScheduleSlot
    {
        return DB::transaction(function () use ($id, $data) {
            $slot = ScheduleSlot::findOrFail($id);
            $schedule = Schedule::with('scheduleType')->findOrFail($slot->schedule_id);
            
            // Validate time range and conflicts
            $this->validateSlotTimeRange($schedule, $data['start_time'], $data['end_time']);
            $this->validateSlotConflicts($schedule, array_merge($data, ['id' => $id]));
            
            // Update the slot
            $slot->update($data);
            
            return $slot->fresh();
        });
    }

    /**
     * Delete a schedule slot.
     *
     * @param int $id The slot ID
     */
    public function deleteSlot(int $id): void
    {
        DB::transaction(function () use ($id) {
            $slot = ScheduleSlot::findOrFail($id);
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
        $query = ScheduleSlot::with(['schedule']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addcolumn('status', fn($slot) => $slot->is_active ? 'Active' : 'Inactive')
            ->addColumn('actions', fn($slot) => $this->renderActionButtons($slot))
            ->ordercolumn('stats', function ($query, $order) {
                return $query->orderBy('is_active', $order);
            })
            ->orderColumn('formatted_specific_date', function ($query, $order) {
                return $query->orderBy('specific_date', $order);
            })
            ->orderColumn('formatted_start_time', function ($query, $order) {
                return $query->orderBy('start_time', $order);
            })
            ->orderColumn('formatted_ends_time', function ($query, $order) {
                return $query->orderBy('end_time', $order);
            })
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
        $buttons = '<div class="dropdown">
            <button type="button" class="btn btn-primary btn-icon rounded-pill dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bx bx-dots-vertical-rounded"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">';

        // View option
        $buttons .= sprintf('
            <li>
                <a class="dropdown-item viewSlotBtn" href="javascript:void(0);" data-id="%d">
                    <i class="bx bx-show me-1"></i> View
                </a>
            </li>',
            $slot->id
        );

        // Edit option
        $buttons .= sprintf('
            <li>
                <a class="dropdown-item editSlotBtn" href="javascript:void(0);" data-id="%d">
                    <i class="bx bx-edit-alt me-1"></i> Edit
                </a>
            </li>',
            $slot->id
        );

        // Delete option
        $buttons .= sprintf('
            <li>
                <a class="dropdown-item deleteSlotBtn" href="javascript:void(0);" data-id="%d">
                    <i class="bx bx-trash text-danger me-1"></i> Delete
                </a>
            </li>',
            $slot->id
        );

        $buttons .= '</ul>
        </div>';

        return $buttons . '</div>';
    }

    /**
     * Toggle the status of a schedule slot.
     *
     * @param int $id The slot ID
     * @return ScheduleSlot
     */
    public function toggleStatus(int $id): ScheduleSlot
    {
        return DB::transaction(function () use ($id) {
            $slot = ScheduleSlot::findOrFail($id);
            $slot->update(['is_active' => !$slot->is_active]);
            return $slot->fresh();
        });
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