<?php

namespace App\Pipelines\AvailableCourse\Shared;

use App\Models\AvailableCourseSchedule;
use App\Models\Schedule\ScheduleSlot;
use App\Models\Schedule\ScheduleAssignment;
use Closure;

class HandleSchedulePipe
{
    /**
     * Handle the pipeline step for managing course schedules.
     *
     * @param array $data
     * @param Closure $next
     * @return mixed
     */
    public function handle(array $data, Closure $next)
    {
        $availableCourse = $data['available_course'];
        $operation = isset($data['update_data']) ? 'update' : 'create';

        \Log::info('Pipeline: Handling schedules for available course', [
            'available_course_id' => $availableCourse->id,
            'operation' => $operation
        ]);

        if ($operation === 'update') {
            $this->handleScheduleUpdate($availableCourse, $data);
        } else {
            $this->handleScheduleCreate($availableCourse, $data);
        }

        return $next($data);
    }

    /**
     * Handle schedule creation for new courses.
     *
     * @param \App\Models\AvailableCourse $availableCourse
     * @param array $data
     * @return void
     */
    private function handleScheduleCreate($availableCourse, array $data): void
    {
        if (isset($data['schedule_details']) && is_array($data['schedule_details'])) {
            $this->createCourseDetailsFromSchedule($availableCourse, $data['schedule_details']);
        }

        if (isset($data['details']) && is_array($data['details'])) {
            $this->createCourseDetails($availableCourse, $data['details']);
        }
    }

    /**
     * Handle schedule updates for existing courses.
     *
     * @param \App\Models\AvailableCourse $availableCourse
     * @param array $data
     * @return void
     */
    private function handleScheduleUpdate($availableCourse, array $data): void
    {
        $updateData = $data['update_data'];

        // Handle schedule details updates
        if (array_key_exists('schedule_details', $updateData)) {
            \Log::info('Updating schedule details', [
                'available_course_id' => $availableCourse->id,
                'schedule_details_count' => is_array($updateData['schedule_details']) ? count($updateData['schedule_details']) : 0
            ]);
            $this->updateScheduleDetails($availableCourse, $updateData['schedule_details'] ?? []);
        }

        // Handle regular course details updates
        if (array_key_exists('details', $updateData)) {
            \Log::info('Updating course details', [
                'available_course_id' => $availableCourse->id,
                'details_count' => is_array($updateData['details']) ? count($updateData['details']) : 0
            ]);
            $this->updateCourseDetails($availableCourse, $updateData['details'] ?? []);
        }
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
     * Create course details for an available course.
     *
     * @param \App\Models\AvailableCourse $availableCourse
     * @param array $details
     * @return void
     */
    private function createCourseDetails($availableCourse, array $details): void
    {
        foreach ($details as $detail) {
            $this->createAvailableCourseSchedule($availableCourse, $detail);
        }
    }

    /**
     * Update schedule details for an available course.
     *
     * @param \App\Models\AvailableCourse $availableCourse
     * @param array $scheduleDetails
     * @return void
     */
    private function updateScheduleDetails($availableCourse, array $scheduleDetails): void
    {
        // Load all schedules with assignments
        $existingDetails = $availableCourse->schedules()->with('scheduleAssignments')->get();
        $processedIds = [];

        foreach ($scheduleDetails as $detail) {
            // Try to find by id, or by schedule_assignment_id if present
            $existingDetail = null;
            if (isset($detail['id'])) {
                $existingDetail = $existingDetails->find($detail['id']);
            } elseif (isset($detail['schedule_assignment_id'])) {
                // Find the schedule detail by assignment id
                foreach ($existingDetails as $ed) {
                    foreach ($ed->scheduleAssignments as $assignment) {
                        if ((string)$assignment->id === (string)$detail['schedule_assignment_id']) {
                            $existingDetail = $ed;
                            break 2;
                        }
                    }
                }
            }

            if (isset($detail['_action']) && $detail['_action'] === 'delete') {
                if ($existingDetail) {
                    $this->deleteScheduleDetailRecord($existingDetail);
                }
                continue;
            }

            if ($existingDetail) {
                $this->updateExistingScheduleDetail($existingDetail, $detail);
                $processedIds[] = $existingDetail->id;
            } else {
                // Create new detail
                $newDetail = $this->createAvailableCourseSchedule($availableCourse, $detail);
                $this->createScheduleAssignmentForDetail($newDetail, $detail);
                $processedIds[] = $newDetail->id;
            }
        }

        // Delete any existing details that weren't processed (if not preserving unmentioned items)
        if (isset($scheduleDetails[0]['_replace_all']) && $scheduleDetails[0]['_replace_all'] === true) {
            $toDelete = $existingDetails->whereNotIn('id', $processedIds);
            foreach ($toDelete as $detail) {
                $this->deleteScheduleDetailRecord($detail);
            }
        }
    }

    /**
     * Update course details for an available course.
     *
     * @param \App\Models\AvailableCourse $availableCourse
     * @param array $details
     * @return void
     */
    private function updateCourseDetails($availableCourse, array $details): void
    {
        $existingDetails = $availableCourse->schedules()->get();
        $processedIds = [];

        foreach ($details as $detail) {
            if (isset($detail['_action']) && $detail['_action'] === 'delete') {
                $this->deleteCourseDetail($detail, $existingDetails);
                continue;
            }

            if (isset($detail['id'])) {
                // Update existing detail
                $existingDetail = $existingDetails->find($detail['id']);
                if ($existingDetail) {
                    $this->updateExistingCourseDetail($existingDetail, $detail);
                    $processedIds[] = $detail['id'];
                }
            } else {
                // Create new detail
                $newDetail = $this->createAvailableCourseSchedule($availableCourse, $detail);
                $processedIds[] = $newDetail->id;
            }
        }

        // Delete any existing details that weren't processed (if replacing all)
        if (isset($details[0]['_replace_all']) && $details[0]['_replace_all'] === true) {
            $toDelete = $existingDetails->whereNotIn('id', $processedIds);
            foreach ($toDelete as $detail) {
                $detail->delete();
            }
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
            'group' => $detail['group_number'] ?? $detail['group'],
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
        $group = $detail['group_number'] ?? $detail['group'];
        $location = $detail['location'] ?? null;
        
        // Handle both single slot (backward compatibility) and multiple slots
        $slotIds = [];
        if (isset($detail['schedule_slot_ids']) && is_array($detail['schedule_slot_ids'])) {
            $slotIds = $detail['schedule_slot_ids'];
        } elseif (isset($detail['schedule_slot_id'])) {
            $slotIds = [$detail['schedule_slot_id']];
        }

        if (empty($slotIds)) {
            return;
        }

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
                    . ($location ? " at {$location}." : "");

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

    /**
     * Update an existing schedule detail.
     *
     * @param AvailableCourseSchedule $existingDetail
     * @param array $detail
     * @return void
     */
    private function updateExistingScheduleDetail(AvailableCourseSchedule $existingDetail, array $detail): void
    {
        $updateData = [];

        if (isset($detail['group_number'])) {
            $updateData['group'] = $detail['group_number'];
        }

        if (isset($detail['activity_type'])) {
            $updateData['activity_type'] = $detail['activity_type'];
        }
        
        if (isset($detail['location'])) {
            $updateData['location'] = $detail['location'];
        }

        if (isset($detail['min_capacity'])) {
            $updateData['min_capacity'] = $detail['min_capacity'];
        }

        if (isset($detail['max_capacity'])) {
            $updateData['max_capacity'] = $detail['max_capacity'];
        }

        if (!empty($updateData)) {
            $existingDetail->update($updateData);
        }

        // Update schedule assignment if needed
        if (isset($detail['schedule_slot_ids']) || isset($detail['schedule_slot_id']) || isset($detail['schedule_assignment_id'])) {
            $this->updateScheduleAssignmentForDetail($existingDetail, $detail);
        }
    }

    /**
     * Update an existing course detail.
     *
     * @param AvailableCourseSchedule $existingDetail
     * @param array $detail
     * @return void
     */
    private function updateExistingCourseDetail(AvailableCourseSchedule $existingDetail, array $detail): void
    {
        $updateData = [];

        if (isset($detail['group'])) {
            $updateData['group'] = $detail['group'];
        }

        if (isset($detail['activity_type'])) {
            $updateData['activity_type'] = $detail['activity_type'];
        }

        if (isset($detail['min_capacity'])) {
            $updateData['min_capacity'] = $detail['min_capacity'];
        }

        if (isset($detail['max_capacity'])) {
            $updateData['max_capacity'] = $detail['max_capacity'];
        }

        if (!empty($updateData)) {
            $existingDetail->update($updateData);
        }
    }

    /**
     * Delete a course detail.
     *
     * @param array $detail
     * @param \Illuminate\Support\Collection $existingDetails
     * @return void
     */
    private function deleteCourseDetail(array $detail, $existingDetails): void
    {
        if (isset($detail['id'])) {
            $existingDetail = $existingDetails->find($detail['id']);
            if ($existingDetail) {
                $existingDetail->delete();
            }
        }
    }

    /**
     * Delete a schedule detail record and its assignments.
     *
     * @param AvailableCourseSchedule $detail
     * @return void
     */
    private function deleteScheduleDetailRecord(AvailableCourseSchedule $detail): void
    {
        // Delete associated schedule assignments
        ScheduleAssignment::where('available_course_schedule_id', $detail->id)
            ->delete();

        // Delete the detail itself
        $detail->delete();
    }

    /**
     * Create ScheduleAssignments for a given course detail with multiple slots (for updates).
     *
     * @param AvailableCourseSchedule $courseDetail
     * @param array $detail
     * @return void
     */
    private function createScheduleAssignmentForDetail(AvailableCourseSchedule $courseDetail, array $detail): void
    {
        $this->createScheduleAssignmentsForDetail($courseDetail, $detail);
    }

    /**
     * Update ScheduleAssignments for a given course detail with multiple slots.
     *
     * @param AvailableCourseSchedule $courseDetail
     * @param array $detail
     * @return void
     */
    private function updateScheduleAssignmentForDetail(AvailableCourseSchedule $courseDetail, array $detail): void
    {
        // Handle both single slot (backward compatibility) and multiple slots
        $slotIds = [];
        if (isset($detail['schedule_slot_ids']) && is_array($detail['schedule_slot_ids'])) {
            $slotIds = $detail['schedule_slot_ids'];
        } elseif (isset($detail['schedule_slot_id'])) {
            $slotIds = [$detail['schedule_slot_id']];
        }

        // If we have new slot IDs, delete existing assignments and create new ones
        if (!empty($slotIds)) {
            // Delete all existing assignments for this course detail
            ScheduleAssignment::where('available_course_schedule_id', $courseDetail->id)->delete();
            
            // Create new assignments for the new slots
            $this->createScheduleAssignmentForDetail($courseDetail, $detail);
            return;
        }

        // If no new slot IDs, just update existing assignment properties
        $assignment = null;

        // If schedule_assignment_id is provided, use it to find the assignment
        if (isset($detail['schedule_assignment_id'])) {
            $assignment = ScheduleAssignment::where('id', $detail['schedule_assignment_id'])
                ->where('available_course_schedule_id', $courseDetail->id)
                ->first();
        }

        // Fallback: find the first assignment for this course detail
        if (!$assignment) {
            $assignment = ScheduleAssignment::where('available_course_schedule_id', $courseDetail->id)
                ->first();
        }

        if (!$assignment) {
            return;
        }

        $updateData = [];

        if (isset($detail['title'])) {
            $updateData['title'] = $detail['title'];
        }

        if (isset($detail['description'])) {
            $updateData['description'] = $detail['description'];
        }

        if (isset($detail['max_capacity'])) {
            $updateData['capacity'] = $detail['max_capacity'];
        }

        if (isset($detail['enrolled'])) {
            $updateData['enrolled'] = $detail['enrolled'];
        }

        if (isset($detail['resources'])) {
            $updateData['resources'] = $detail['resources'];
        }

        if (isset($detail['status'])) {
            $updateData['status'] = $detail['status'];
        }

        if (isset($detail['notes'])) {
            $updateData['notes'] = $detail['notes'];
        }

        if (!empty($updateData)) {
            $assignment->update($updateData);
        }
    }
}
