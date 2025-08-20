<?php

namespace App\Pipelines\AvailableCourse\Import;

use App\Models\AvailableCourseSchedule;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleSlot;
use App\Models\Schedule\ScheduleAssignment;
use Closure;

class HandleImportSchedulePipe
{
    /**
     * Handle the pipeline step for managing course schedules during import.
     *
     * @param array $data
     * @param Closure $next
     * @return mixed
     */
    public function handle(array $data, Closure $next)
    {
        $availableCourse = $data['available_course'];
        $mappedData = $data['mapped_data'];
        $rowNumber = $data['row_number'];

        \Log::info('Pipeline: Handling import schedule', [
            'row_number' => $rowNumber,
            'available_course_id' => $availableCourse->id,
            'activity_type' => $mappedData['activity_type'],
            'group' => $mappedData['group']
        ]);


    // Pass the whole $data so we can access schedule directly
    $this->createOrUpdateCourseSchedule($availableCourse, $mappedData, $data);

        return $next($data);
    }

    /**
     * Create or update course schedule with flexible slot handling.
     *
     * @param \App\Models\AvailableCourse $availableCourse
     * @param array $mappedData
     * @return AvailableCourseSchedule
     */
    private function createOrUpdateCourseSchedule($availableCourse, array $mappedData): AvailableCourseSchedule
    {
        $activityType = $mappedData['activity_type'];
        $group = $mappedData['group'];
        $location = $mappedData['location'];
        $minCapacity = $mappedData['min_capacity'];
        $maxCapacity = $mappedData['max_capacity'];

        // Define the unique keys for finding existing schedule
        $scheduleKeys = [
            'available_course_id' => $availableCourse->id,
            'group' => $group,
            'activity_type' => $activityType,
            'location' => $location,
        ];

        // Define the values to update/create
        $scheduleValues = [
            'min_capacity' => $minCapacity,
            'max_capacity' => $maxCapacity,
        ];

        \Log::info('Creating/updating course schedule', [
            'available_course_id' => $availableCourse->id,
            'keys' => $scheduleKeys,
            'values' => $scheduleValues
        ]);

    // Create or update the available course schedule
    $availableCourseSchedule = AvailableCourseSchedule::updateOrCreate($scheduleKeys, $scheduleValues);

    // Handle schedule slot assignment if provided, pass $data for schedule
    $this->handleScheduleSlotAssignment($availableCourseSchedule, $availableCourse, $mappedData, func_num_args() > 2 ? func_get_arg(2) : []);

    return $availableCourseSchedule;
    }

    /**
     * Handle schedule slot assignment for the course schedule.
     *
     * @param AvailableCourseSchedule $courseSchedule
     * @param \App\Models\AvailableCourse $availableCourse
     * @param array $mappedData
     * @return void
     */
    private function handleScheduleSlotAssignment(
        AvailableCourseSchedule $courseSchedule,
        $availableCourse,
        array $mappedData,
        array $data = []
    ): void {
        $scheduleId = $data['schedule_id'] ?? null;
        $day = $mappedData['day'] ?? null;
        $slot = $mappedData['slot'] ?? null;

        // If we don't have complete schedule information, skip slot assignment
        if (empty($scheduleId) || empty($day) || empty($slot)) {
            \Log::info('Incomplete schedule slot information, skipping slot assignment', [
                'schedule_id' => $scheduleId,
                'day' => $day,
                'slot' => $slot
            ]);
            return;
        }

        $schedule = Schedule::find($scheduleId);
        if (!$schedule) {
            \Log::warning('Schedule not found', [
                'schedule_id' => $scheduleId
            ]);
            return;
        }

        $scheduleSlot = $this->findScheduleSlot($schedule, $day, $slot);
        if (!$scheduleSlot) {
            \Log::warning('Schedule slot not found', [
                'schedule_id' => $schedule->id,
                'day' => $day,
                'slot' => $slot
            ]);
            return;
        }

        // Create or update schedule assignment
        $this->createOrUpdateScheduleAssignment($courseSchedule, $availableCourse, $scheduleSlot);
    }

    /**
     * Find schedule by id (if provided) or by code.
     *
     * @param string|null $id
     * @param string|null $code
     * @return Schedule|null
     */
    private function findSchedule($id = null, $code = null): ?Schedule
    {
        if (!empty($id)) {
            return Schedule::find($id);
        }
        if (!empty($code)) {
            return Schedule::where('code', $code)->first();
        }
        return null;
    }

    /**
     * Find schedule slot by day and slot order.
     *
     * @param Schedule $schedule
     * @param string $day
     * @param string|int $slot
     * @return ScheduleSlot|null
     */
    private function findScheduleSlot(Schedule $schedule, string $day, $slot): ?ScheduleSlot
    {
        return ScheduleSlot::where('schedule_id', $schedule->id)
            ->where('day_of_week', $day)
            ->where('slot_order', $slot)
            ->first();
    }

    /**
     * Create or update schedule assignment.
     *
     * @param AvailableCourseSchedule $courseSchedule
     * @param \App\Models\AvailableCourse $availableCourse
     * @param ScheduleSlot $scheduleSlot
     * @return ScheduleAssignment
     */
    private function createOrUpdateScheduleAssignment(
        AvailableCourseSchedule $courseSchedule,
        $availableCourse,
        ScheduleSlot $scheduleSlot
    ): ScheduleAssignment {
        $assignmentKeys = [
            'schedule_slot_id' => $scheduleSlot->id,
            'available_course_schedule_id' => $courseSchedule->id,
        ];

        $assignmentValues = [
            'type' => 'available_course',
            'title' => $availableCourse->course->name ?? 'Course Activity',
            'description' => $availableCourse->course->description ?? null,
            'enrolled' => 0,
            'resources' => null,
            'status' => 'scheduled',
            'notes' => null,
        ];

        \Log::info('Creating/updating schedule assignment', [
            'schedule_slot_id' => $scheduleSlot->id,
            'available_course_schedule_id' => $courseSchedule->id,
            'course_name' => $availableCourse->course->name ?? 'Unknown'
        ]);

        return ScheduleAssignment::updateOrCreate($assignmentKeys, $assignmentValues);
    }

    /**
     * Handle multiple schedule slots for a course (for future enhancement).
     *
     * @param AvailableCourseSchedule $courseSchedule
     * @param \App\Models\AvailableCourse $availableCourse
     * @param array $scheduleSlots
     * @return void
     */
    private function handleMultipleScheduleSlots(
        AvailableCourseSchedule $courseSchedule,
        $availableCourse,
        array $scheduleSlots
    ): void {
        foreach ($scheduleSlots as $slotData) {
            $scheduleSlot = $this->findScheduleSlot($schedule, $slotData['day'], $slotData['slot']);
            if (!$scheduleSlot) {
                continue;
            }

            $this->createOrUpdateScheduleAssignment($courseSchedule, $availableCourse, $scheduleSlot);
        }
    }
}
