<?php

namespace App\Http\Controllers\Schedule;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\View\View;
use App\Models\Schedule\ScheduleType;
use Exception;

class ScheduleTypeController extends \App\Http\Controllers\Controller
{
    public function index(): View
    {
        return view('schedule_type.index');
    }

    public function create(): View
    {
        return view('schedule_type.create');
    }

    public function datatable(): JsonResponse
    {
        try {
            $types = ScheduleType::query();

            $data = $types->get();

            return successResponse('Schedule types fetched successfully.', $data);
        } catch (Exception $e) {
            logError('ScheduleTypeController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:schedule_types,code',
            'description' => 'nullable|string',
            'status' => 'required|string',
        ]);
        try {
            $validated = $request->only(['name', 'code', 'description', 'status']);
            $scheduleType = ScheduleType::create($validated);
            return successResponse('Schedule type created successfully.', $scheduleType);
        } catch (Exception $e) {
            logError('ScheduleTypeController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    public function destroy(ScheduleType $scheduleType): JsonResponse
    {
        try {
            $scheduleType->delete();
            return successResponse('Schedule type deleted successfully.');
        } catch (Exception $e) {
            logError('ScheduleTypeController@destroy', $e, ['schedule_type_id' => $scheduleType->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    // Return all schedule types as JSON (for select2 or similar).
    public function all(): JsonResponse
    {
        try {
            $types = ScheduleType::get();
            return successResponse('Schedule types fetched successfully.', $types);
        } catch (Exception $e) {
            logError('ScheduleTypeController@all', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }
}
