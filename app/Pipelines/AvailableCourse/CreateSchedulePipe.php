<?php

namespace App\Pipelines\AvailableCourse;

use App\Models\AvailableCourseSchedule;
use App\Models\Schedule\ScheduleSlot;
use App\Models\Schedule\ScheduleAssignment;
use Closure;

class CreateSchedulePipe
{
    /**
     * Handle the pipeline step for creating course schedules.
     *
     * @param array $data
     * @param Closure $next
     * @return mixed
     */
    public function handle(array $data, Closure $next)
    {
        if (isset($data['schedule_details']) && is_array($data['schedule_details'])) {
            \Log::info('Pipeline: Creating course details from schedule', [
                'available_course_id' => $data['available_course']->id
            ]);
            $this->createCourseDetailsFromSchedule($data['available_course'], $data['schedule_details']);
        }

        return $next($data);
    }

    /**
     * Create course details from schedule details for an available course.
     *
     * @param \App\Models\AvailableCourse $availableCourse
     * @param array $scheduleDetails
     * @return void
     */
    private function createCourseDetailsFromSchedule($availableCourse, array $scheduleDetails): void
    {
        foreach ($scheduleDetails as $detail) {
            $courseDetail = $this->createAvailableCourseSchedule($availableCourse, $detail);
            $this->createScheduleAssignmentsForDetail($courseDetail, $detail);
        }
    }

    /**
     * Create an AvailableCourseSchedule record.
     *
     * @param \App\Models\AvailableCourse $availableCourse
     * @param array $detail
     * @return AvailableCourseSchedule
     */
    private function createAvailableCourseSchedule($availableCourse, array $detail): AvailableCourseSchedule
    {
        return AvailableCourseSchedule::create([
            'available_course_id' => $availableCourse->id,
            'group' => $detail['group_number'],
            'activity_type' => $detail['activity_type'],
            'location' => $detail['location'] ?? null,
            'min_capacity' => $detail['min_capacity'] ?? 1,
            'max_capacity' => $detail['max_capacity'] ?? 30,
        ]);
    }

    /**
     * Create ScheduleAssignments for a given course detail with multiple slots.
     *
     * @param AvailableCourseSchedule $courseDetail
     * @param array $detail
     * @return void
     */
    private function createScheduleAssignmentsForDetail(AvailableCourseSchedule $courseDetail, array $detail): void
    {
        $activityType = ucfirst(str_replace('_', ' ', $detail['activity_type']));
        $group = $detail['group_number'];
        $location = $detail['location'] ?? null;
        $slotIds = $detail['schedule_slot_ids'] ?? [];

        // Handle multiple slots
        foreach ($slotIds as $index => $slotId) {
            // Get slot information for title generation
            $slot = ScheduleSlot::find($slotId);
            $slotOrder = $slot ? $slot->slot_order : ($index + 1);
            
            // For multiple slots, create a combined title
            $slotInfo = count($slotIds) > 1 ? "Slots {$slotOrder}" : "Slot {$slotOrder}";
            if (count($slotIds) > 1 && $index === 0) {
                // For the first slot in a multi-slot assignment, show the range
                $firstSlot = ScheduleSlot::find($slotIds[0]);
                $lastSlot = ScheduleSlot::find(end($slotIds));
                if ($firstSlot && $lastSlot) {
                    $slotInfo = "Slots {$firstSlot->slot_order}-{$lastSlot->slot_order}";
                }
            }

            $generatedTitle = $detail['title']
                ?? "{$activityType} - Group {$group} - {$slotInfo}";

            $generatedDescription = $detail['description']
                ?? "Scheduled {$activityType} for Group {$group}"
                    . " during {$slotInfo}"
                    . " at {$location}.";

            ScheduleAssignment::create([
                'schedule_slot_id' => $slotId,
                'type' => 'available_course',
                'available_course_schedule_id' => $courseDetail->id,
                'title' => $generatedTitle,
                'description' => $generatedDescription,
                'enrolled' => $detail['enrolled'] ?? 0,
                'resources' => $detail['resources'] ?? null,
                'status' => 'scheduled',
                'notes' => $detail['notes'] ?? null,
            ]);
        }
    }
}
