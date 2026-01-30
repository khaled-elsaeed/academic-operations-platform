<?php

namespace App\Services\AvailableCourse;

use App\Models\AvailableCourseSchedule;
use App\Models\Schedule\ScheduleAssignment;
use App\Exceptions\BusinessValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class ScheduleService
{
    /**
     * Get schedules datatable for edit page.
     *
     * @param int $availableCourseId
     * @return JsonResponse
     */
    public function getSchedulesDatatable(int $availableCourseId): JsonResponse
    {
        $query = AvailableCourseSchedule::with(['scheduleAssignments.scheduleSlot', 'enrollments'])
            ->where('available_course_id', $availableCourseId);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('activity_type', function ($schedule) {
                return ucfirst($schedule->activity_type);
            })
            ->addColumn('location', function ($schedule) {
                return $schedule->location ?? '-';
            })
            ->addColumn('groups', function ($schedule) {
                return $schedule->group ?? '-';
            })
            ->addColumn('day', function ($schedule) {
                return $this->getScheduleDays($schedule);
            })
            ->addColumn('slots', function ($schedule) {
                return $this->getScheduleTimeSlots($schedule);
            })
            ->addColumn('capacity', function ($schedule) {
                return $this->getScheduleCapacity($schedule);
            })
            ->addColumn('action', function ($schedule) {
                return $this->renderActionButtons($schedule);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Render action buttons.
     *
     * @param AvailableCourseSchedule $schedule
     * @return string
     */
    protected function renderActionButtons(AvailableCourseSchedule $schedule): string
    {
        $user = auth()->user();
        if (!$user) {
            return '';
        }

        $singleActions = $this->buildSingleActions($user, $schedule);

        if (empty($singleActions)) {
            return '';
        }

        return view('components.ui.datatable.table-actions', [
            'mode' => 'single',
            'actions' => [],
            'id' => $schedule->id,
            'type' => 'Schedule',
            'singleActions' => $singleActions,
        ])->render();
    }

    /**
     * Build single actions.
     *
     * @param mixed $user
     * @param AvailableCourseSchedule $schedule
     * @return array<int, array{action: string, icon: string, class: string, label: string, data: array}>
     */
    protected function buildSingleActions($user, AvailableCourseSchedule $schedule): array
    {
        $actions = [];

        if ($user->hasPermissionTo('available_course.edit')) {
            $actions[] = [
                'action' => 'edit',
                'icon' => 'bx bx-edit',
                'class' => 'btn-warning',
                'label' => __('Edit'),
                'data' => [
                    'id' => $schedule->id,
                    'activity-type' => $schedule->activity_type,
                    'group' => $schedule->group,
                    'location' => $schedule->location,
                    'min-capacity' => $schedule->min_capacity,
                    'max-capacity' => $schedule->max_capacity,
                ],
            ];
        }

        if ($user->hasPermissionTo('available_course.delete')) {
            $actions[] = [
                'action' => 'delete',
                'icon' => 'bx bx-trash',
                'class' => 'btn-danger',
                'label' => __('Delete'),
                'data' => ['id' => $schedule->id],
            ];
        }

        return $actions;
    }

    /**
     * Get formatted days for a schedule.
     *
     * @param AvailableCourseSchedule $schedule
     * @return string
     */
    protected function getScheduleDays(AvailableCourseSchedule $schedule): string
    {
        $days = $schedule->scheduleAssignments->map(function ($assignment) {
            return $assignment->scheduleSlot?->day_of_week;
        })->filter()->unique()->values();

        if ($days->isEmpty()) {
            return '-';
        }

        $dayMap = [
            'M' => 'Monday',
            'T' => 'Tuesday',
            'W' => 'Wednesday',
            'Th' => 'Thursday',
            'F' => 'Friday',
            'S' => 'Saturday',
            'Su' => 'Sunday',
        ];

        return $days->map(function ($day) use ($dayMap) {
            return $dayMap[$day] ?? ucfirst($day);
        })->join(', ');
    }

    /**
     * Get formatted time slots for a schedule.
     *
     * @param AvailableCourseSchedule $schedule
     * @return string
     */
    protected function getScheduleTimeSlots(AvailableCourseSchedule $schedule): string
    {
        $slots = $schedule->scheduleAssignments->map(function ($assignment) {
            return $assignment->scheduleSlot;
        })->filter()->sortBy('start_time');

        if ($slots->isEmpty()) {
            return '-';
        }

        $firstSlot = $slots->first();
        $lastSlot = $slots->last();

        return formatTime($firstSlot->start_time) . ' - ' . formatTime($lastSlot->end_time);
    }

    /**
     * Get formatted capacity information for a schedule.
     *
     * @param AvailableCourseSchedule $schedule
     * @return string
     */
    protected function getScheduleCapacity(AvailableCourseSchedule $schedule): string
    {
        $enrolled = $schedule->enrollments->count();
        $capacity = '';

        if ($schedule->min_capacity) {
            $capacity .= 'Min: ' . $schedule->min_capacity;
        }
        if ($schedule->max_capacity) {
            if ($capacity) $capacity .= ' / ';
            $capacity .= 'Max: ' . $schedule->max_capacity;
        }
        if ($capacity) {
            $capacity .= ' / Enrolled: ' . $enrolled;
        } else {
            $capacity = 'Enrolled: ' . $enrolled;
        }

        return $capacity;
    }


    /**
     * Get Aavailable Course Schedules
     */
    public function getAvailableCourseSchedules(int $availableCourseId): array
    {
        $schedules = AvailableCourseSchedule::with(['scheduleAssignments.scheduleSlot', 'enrollments'])
            ->where('available_course_id', $availableCourseId)
            ->get();

        return $schedules->toArray();
    }

    /**
     * Get single schedule for editing.
     *
     * @param int $scheduleId
     * @return array
     * @throws BusinessValidationException
     */
    public function getSchedule(int $scheduleId): array
    {
        $schedule = AvailableCourseSchedule::with(['scheduleAssignments.scheduleSlot', 'enrollments'])->find($scheduleId);

        if (!$schedule) {
            throw new BusinessValidationException('Schedule not found.');
        }

        // Get slots information
        $slots = $schedule->scheduleAssignments->map(function ($assignment) {
            return $assignment->scheduleSlot;
        })->filter()->sortBy('start_time');

        $slotIds = $slots->pluck('id')->toArray();
        $firstSlot = $slots->first();

        return [
            'id' => $schedule->id,
            'schedule_template_id' => $firstSlot?->schedule?->id,
            'day'=> $firstSlot?->day_of_week,
            'activity_type' => $schedule->activity_type,
            'group' => $schedule->group,
            'group_number' => [$schedule->group],
            'location' => $schedule->location,
            'min_capacity' => $schedule->min_capacity,
            'max_capacity' => $schedule->max_capacity,
            'day_of_week' => $firstSlot?->day_of_week,
            'level_id' => $schedule->level_id,
            'program_id' => $schedule->program_id,
            'slot_ids' => $slotIds,
        ];
    }

    /**
     * Store new schedule for available course.
     *
     * @param int $availableCourseId
     * @param array $data
     * @return array
     * @throws BusinessValidationException
     */
    public function storeSchedule(int $availableCourseId, array $data): array
    {
        $availableCourse = \App\Models\AvailableCourse::findOrFail($availableCourseId);

        if (isset($data['min_capacity'], $data['max_capacity']) && $data['min_capacity'] !== null && $data['max_capacity'] !== null) {
            if ((int)$data['min_capacity'] > (int)$data['max_capacity']) {
                throw new BusinessValidationException('Min capacity cannot be greater than max capacity.');
            }
        }

        $slotIds = $data['schedule_slot_ids'] ?? [];
        $results = [];
        $skippedCount = 0;

        DB::transaction(function () use (&$results, &$skippedCount, $data, $availableCourseId, $slotIds) {
            foreach ($data['group_numbers'] as $group) {
                $location = $data['location'] ?? null;

                $existsQuery = AvailableCourseSchedule::where('available_course_id', $availableCourseId)
                    ->where('activity_type', $data['activity_type'])
                    ->where('group', $group);

                // Handle null location properly
                if ($location === null) {
                    $existsQuery->whereNull('location');
                } else {
                    $existsQuery->where('location', $location);
                }
                // consider program and level in existence check if provided
                if (isset($data['program_id'])) {
                    if ($data['program_id'] === null) {
                        $existsQuery->whereNull('program_id');
                    } else {
                        $existsQuery->where('program_id', $data['program_id']);
                    }
                }
                if (isset($data['level_id'])) {
                    if ($data['level_id'] === null) {
                        $existsQuery->whereNull('level_id');
                    } else {
                        $existsQuery->where('level_id', $data['level_id']);
                    }
                }

                $exists = $existsQuery->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                $schedule = AvailableCourseSchedule::create([
                    'available_course_id' => $availableCourseId,
                    'activity_type' => $data['activity_type'],
                    'group' => $group,
                    'location' => $location,
                    'program_id' => $data['program_id'] ?? null,
                    'level_id' => $data['level_id'] ?? null,
                    'min_capacity' => $data['min_capacity'] ?? null,
                    'max_capacity' => $data['max_capacity'] ?? null,
                ]);

                // Assign slots if provided
                if (!empty($slotIds)) {
                    foreach ($slotIds as $slotId) {
                        ScheduleAssignment::create([
                            'available_course_schedule_id' => $schedule->id,
                            'schedule_slot_id' => $slotId,
                            'type' => 'available_course',
                            'title' => $data['title'] ?? ($schedule->activity_type . ' - Group ' . $group),
                            'description' => $data['description'] ?? null,
                            'enrolled' => 0,
                            'resources' => $data['resources'] ?? null,
                            'status' => $data['status'] ?? 'scheduled',
                            'notes' => $data['notes'] ?? null,
                        ]);
                    }
                }

                $results[] = $schedule->load('scheduleAssignments.scheduleSlot');
            }
        });

        // If no schedules were created because they all already exist
        if (empty($results) && $skippedCount > 0) {
            throw new BusinessValidationException('All schedules for the selected groups already exist.');
        }

        // If no schedules were created for other reasons
        if (empty($results)) {
            throw new BusinessValidationException('No schedules were created. Please check your input data.');
        }

        return $results;
    }

    /**
     * Update schedule for available course.
     *
     * @param int $scheduleId
     * @param array $data
     * @return array
     * @throws BusinessValidationException
     */
    public function updateSchedule(int $scheduleId, array $data): array
    {
        $schedule = AvailableCourseSchedule::findOrFail($scheduleId);

        if (isset($data['min_capacity'], $data['max_capacity']) && $data['min_capacity'] !== null && $data['max_capacity'] !== null) {
            if ((int)$data['min_capacity'] > (int)$data['max_capacity']) {
                throw new BusinessValidationException('Min capacity cannot be greater than max capacity.');
            }
        }

        $slotIds = $data['schedule_slot_ids'] ?? [];

        $schedule->update([
            'group' => $data['group_numbers'] ?? $data['group_number'] ?? $schedule->group,
            'activity_type' => $data['activity_type'] ?? $schedule->activity_type,
            'location' => $data['location'] ?? $schedule->location,
            'program_id' => $data['program_id'] ?? $schedule->program_id ?? null,
            'level_id' => $data['level_id'] ?? $schedule->level_id ?? null,
            'min_capacity' => $data['min_capacity'] ?? $schedule->min_capacity,
            'max_capacity' => $data['max_capacity'] ?? $schedule->max_capacity,
        ]);

        // Handle schedule assignments update
        if (!empty($slotIds)) {
            // Get existing assignments
            $existingAssignments = ScheduleAssignment::where('available_course_schedule_id', $scheduleId)
                ->pluck('schedule_slot_id')
                ->toArray();

            // Remove assignments that are no longer needed
            $assignmentsToRemove = array_diff($existingAssignments, $slotIds);
            if (!empty($assignmentsToRemove)) {
                ScheduleAssignment::where('available_course_schedule_id', $scheduleId)
                    ->whereIn('schedule_slot_id', $assignmentsToRemove)
                    ->delete();
            }

            // Add new assignments
            $assignmentsToAdd = array_diff($slotIds, $existingAssignments);
            foreach ($assignmentsToAdd as $slotId) {
                ScheduleAssignment::create([
                    'available_course_schedule_id' => $scheduleId,
                    'schedule_slot_id' => $slotId,
                    'type' => 'available_course',
                    'title' => $data['title'] ?? ($schedule->activity_type . ' - Group ' . $schedule->group),
                    'description' => $data['description'] ?? null,
                    'enrolled' => 0,
                    'resources' => $data['resources'] ?? null,
                    'status' => $data['status'] ?? 'scheduled',
                    'notes' => $data['notes'] ?? null,
                ]);
            }

            // Update existing assignments with new data if provided
            if (isset($data['title']) || isset($data['description']) || isset($data['resources']) || isset($data['status']) || isset($data['notes'])) {
                $updateData = [];
                if (isset($data['title'])) $updateData['title'] = $data['title'];
                if (isset($data['description'])) $updateData['description'] = $data['description'];
                if (isset($data['resources'])) $updateData['resources'] = $data['resources'];
                if (isset($data['status'])) $updateData['status'] = $data['status'];
                if (isset($data['notes'])) $updateData['notes'] = $data['notes'];

                if (!empty($updateData)) {
                    ScheduleAssignment::where('available_course_schedule_id', $scheduleId)
                        ->whereIn('schedule_slot_id', array_intersect($existingAssignments, $slotIds))
                        ->update($updateData);
                }
            }
        }

        return [$schedule->fresh()->load('scheduleAssignments.scheduleSlot')];
    }

    /**
     * Delete schedule for available course.
     *
     * @param int $scheduleId
     * @return void
     * @throws BusinessValidationException
     */
    public function deleteSchedule(int $scheduleId): void
    {
        $schedule = AvailableCourseSchedule::findOrFail($scheduleId);

        // Check if there are enrollments
        $enrollmentCount = \App\Models\EnrollmentSchedule::where('available_course_schedule_id', $scheduleId)->count();
        if ($enrollmentCount > 0) {
            throw new BusinessValidationException('Cannot delete schedule with existing enrollments.');
        }

        // Delete schedule assignments and then the schedule
        $schedule->scheduleAssignments()->delete();
        $schedule->delete();
    }
}