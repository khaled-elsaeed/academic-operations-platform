<?php

namespace App\Http\Controllers\AvailableCourse;

use App\Http\Controllers\Controller;
use App\Services\AvailableCourse\ScheduleService;
use Illuminate\Http\{Request, JsonResponse};
use App\Exceptions\BusinessValidationException;
use Exception;

class ScheduleController extends Controller
{
    /**
     * ScheduleController constructor.
     *
     * @param ScheduleService $scheduleService
     */
    public function __construct(protected ScheduleService $scheduleService)
    {}

    /**
     * Get schedules datatable for edit page.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function datatable($id): JsonResponse
    {
        try {
            return $this->scheduleService->getSchedulesDatatable($id);
        } catch (Exception $e) {
            logError('ScheduleController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
    * Get all schedules for available course.
    *
    * @param int $id
    * @return JsonResponse
    */
    public function getAvailableCourseSchedules($id): JsonResponse
    {
        try {
            $schedules = $this->scheduleService->getAvailableCourseSchedules($id);
            return successResponse('Schedules retrieved successfully.', $schedules);
        } catch (Exception $e) {
            logError('ScheduleController@getAvailableCourseSchedules', $e, ['id' => $id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Show schedule for editing.
     *
     * @param int $availableCourseId
     * @param int $scheduleId
     * @return JsonResponse
     */
    public function show($availableCourseId, $scheduleId): JsonResponse
    {
        try {
            $schedule = $this->scheduleService->getSchedule($scheduleId);
            return successResponse('Schedule retrieved successfully.', $schedule);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('ScheduleController@show', $e, ['availableCourseId' => $availableCourseId, 'scheduleId' => $scheduleId]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Store new schedule for available course.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function store(Request $request, $id): JsonResponse
    {
        $request->validate([
            'schedule_template_id' => 'nullable|integer|exists:schedules,id',
            'activity_type' => 'required|in:lecture,tutorial,lab',
            'group_numbers' => 'required|array|min:1',
            'group_numbers.*' => 'integer|min:1|max:30',
            'location' => 'nullable|string|max:255',
            'program_id' => 'nullable|exists:programs,id',
            'level_id' => 'nullable|exists:levels,id',
            'min_capacity' => 'nullable|integer|min:0',
            'max_capacity' => 'nullable|integer|min:0',
            'schedule_slot_ids' => 'nullable|array',
            'schedule_slot_ids.*' => 'integer|exists:schedule_slots,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'resources' => 'nullable|array',
            'status' => 'nullable|in:scheduled,confirmed,cancelled,completed',
            'notes' => 'nullable|string',
        ]);

        try {
            $schedule = $this->scheduleService->storeSchedule($id, $request->all());
            return successResponse('Schedule added successfully.', $schedule);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('ScheduleController@store', $e, ['id' => $id, 'request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Update schedule for available course.
     *
     * @param Request $request
     * @param int $availableCourseId
     * @param int $scheduleId
     * @return JsonResponse
     */
    public function update(Request $request, $availableCourseId, $scheduleId): JsonResponse
    {
        $request->validate([
            'schedule_template_id' => 'nullable|integer|exists:schedules,id',
            'activity_type' => 'required|in:lecture,tutorial,lab',
            'group_number' => 'required|integer|min:1|max:30', // Single group number for updates
            'location' => 'nullable|string|max:255',
            'program_id' => 'nullable|exists:programs,id',
            'level_id' => 'nullable|exists:levels,id',
            'min_capacity' => 'nullable|integer|min:0',
            'max_capacity' => 'nullable|integer|min:0',
            'schedule_slot_ids' => 'nullable|array',
            'schedule_slot_ids.*' => 'integer|exists:schedule_slots,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'resources' => 'nullable|array',
            'status' => 'nullable|in:scheduled,confirmed,cancelled,completed',
            'notes' => 'nullable|string',
        ]);

        try {
            $schedule = $this->scheduleService->updateSchedule($scheduleId, $request->all());
            return successResponse('Schedule updated successfully.', $schedule);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('ScheduleController@update', $e, ['availableCourseId' => $availableCourseId, 'scheduleId' => $scheduleId, 'request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Delete schedule for available course.
     *
     * @param int $availableCourseId
     * @param int $scheduleId
     * @return JsonResponse
     */
    public function delete($availableCourseId, $scheduleId): JsonResponse
    {
        try {
            $this->scheduleService->deleteSchedule($scheduleId);
            return successResponse('Schedule deleted successfully.');
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('ScheduleController@delete', $e, ['availableCourseId' => $availableCourseId, 'scheduleId' => $scheduleId]);
            return errorResponse('Internal server error.', [], 500);
        }
    }
}