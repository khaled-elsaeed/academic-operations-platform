<?php

namespace App\Http\Controllers\Schedule;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\View\View;
use App\Services\Schedule\ScheduleService;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleType;
use App\Http\Requests\StoreScheduleRequest;
use Exception;

class ScheduleController extends \App\Http\Controllers\Controller
{
    public function __construct(protected ScheduleService $scheduleService) {}

    public function index(): View
    {
        return view('schedule.index');
    }

    /**
     * Get all active schedules
     *
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        try {
            $schedules = $this->scheduleService->getAllActive();
            return successResponse('Schedules fetched successfully.', $schedules);
        } catch (Exception $e) {
            logError('ScheduleController@all', $e);
            return errorResponse('Failed to fetch schedules.', [], 500);
        }
    }

    public function create()
    {
        return view('schedule.create');
    }

    public function show($id): JsonResponse
    {
        try {
            $data = $this->scheduleService->getScheduleDetails($id);
            return successResponse('Schedule fetched successfully.', $data);
        } catch (Exception $e) {
            logError('ScheduleController@show', $e, ['schedule_id' => $id]);
            return errorResponse('Failed to fetch schedule details.', [], 500);
        }
    }

    public function datatable(): JsonResponse
    {
        try {
            return $this->scheduleService->getDatatable();
        } catch (Exception $e) {
            logError('ScheduleController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $stats = $this->scheduleService->getStats();
            return successResponse('Stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('ScheduleController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    public function store(StoreScheduleRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $schedule = $this->scheduleService->createSchedule($validated);
            return successResponse('Schedule created successfully.', $schedule);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('ScheduleController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    public function destroy(Schedule $schedule): JsonResponse
    {
        try {
            $this->scheduleService->deleteSchedule($schedule);
            return successResponse('Schedule deleted successfully.');
        } catch (Exception $e) {
            logError('ScheduleController@destroy', $e, ['schedule_id' => $schedule->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }
}
