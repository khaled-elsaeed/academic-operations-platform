<?php

namespace App\Http\Controllers\Schedule;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\View\View;
use App\Services\Schedule\ScheduleSlotService;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleType;
use Exception;

class ScheduleSlotController extends \App\Http\Controllers\Controller
{
    public function __construct(protected ScheduleSlotService $scheduleSlotService) {}

    public function index(): View
    {
        return view('schedule.index');
    }


    public function show($id): JsonResponse
    {
        try {
            $data = $this->scheduleSlotService->getScheduleDetails($id);
            return successResponse('Schedule fetched successfully.', $data);
        } catch (Exception $e) {
            logError('ScheduleSlotController@show', $e, ['schedule_id' => $id]);
            return errorResponse('Failed to fetch schedule details.', [], 500);
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


    public function destroy(Schedule $schedule): JsonResponse
    {
        try {
            $this->scheduleSlotService->deleteSchedule($schedule);
            return successResponse('Schedule deleted successfully.');
        } catch (Exception $e) {
            logError('ScheduleSlotController@destroy', $e, ['schedule_id' => $schedule->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }
}
