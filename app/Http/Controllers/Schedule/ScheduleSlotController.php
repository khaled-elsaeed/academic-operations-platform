<?php

namespace App\Http\Controllers\Schedule;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\View\View;
use App\Services\Schedule\ScheduleSlotService;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleType;
use App\Exceptions\BusinessValidationException;
use Exception;

class ScheduleSlotController extends \App\Http\Controllers\Controller
{
    public function __construct(protected ScheduleSlotService $scheduleSlotService) {}

    public function index(): View
    {
        return view('schedule_slot.index');
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $slot = $this->scheduleSlotService->createSlot($request->all());
            return successResponse('Schedule slot created successfully.', $slot);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('ScheduleSlotController@store', $e);
            return errorResponse('Failed to create schedule slot.', [], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $data = $this->scheduleSlotService->getSlotDetails($id);
            return successResponse('Schedule slot fetched successfully.', $data);
        } catch (Exception $e) {
            logError('ScheduleSlotController@show', $e, ['slot_id' => $id]);
            return errorResponse('Failed to fetch schedule slot details.', [], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $slot = $this->scheduleSlotService->updateSlot($id, $request->all());
            return successResponse('Schedule slot updated successfully.', $slot);
        } catch (Exception $e) {
            logError('ScheduleSlotController@update', $e, ['slot_id' => $id]);
            return errorResponse('Failed to update schedule slot.', [], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $this->scheduleSlotService->deleteSlot($id);
            return successResponse('Schedule slot deleted successfully.');
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('ScheduleSlotController@destroy', $e, ['slot_id' => $id]);
            return errorResponse('Failed to delete schedule slot.', [], 500);
        }
    }

    public function datatable(): JsonResponse
    {
        try {
            return $this->scheduleSlotService->getDatatable();
        } catch (Exception $e) {
            logError('ScheduleSlotController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $stats = $this->scheduleSlotService->getStats();
            return successResponse('Stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('ScheduleSlotController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    public function toggleStatus($id): JsonResponse
    {
        try {
            $slot = $this->scheduleSlotService->toggleStatus($id);
            return successResponse('Schedule slot status updated successfully.', $slot);
        } catch (Exception $e) {
            logError('ScheduleSlotController@toggleStatus', $e, ['slot_id' => $id]);
            return errorResponse('Failed to update schedule slot status.', [], 500);
        }
    }
}
