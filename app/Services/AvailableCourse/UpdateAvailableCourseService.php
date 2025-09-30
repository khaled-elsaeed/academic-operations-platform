<?php

namespace App\Services\AvailableCourse;

use App\Models\AvailableCourse;
use App\Models\AvailableCourseSchedule;
use App\Models\Schedule\ScheduleSlot;
use App\Models\Schedule\ScheduleAssignment;
use App\Models\Program;
use App\Models\Level;
use App\Exceptions\BusinessValidationException;
use Illuminate\Support\Facades\DB;

class UpdateAvailableCourseService
{
    /**
     * Update a single available course with eligibility mode support.
     *
     * @param AvailableCourse $availableCourse
     * @param array $data
     * @return AvailableCourse
     * @throws BusinessValidationException
     */
    public function updateAvailableCourseSingle(AvailableCourse $availableCourse, array $data): AvailableCourse
    {
        return DB::transaction(function () use ($availableCourse, $data) {
            // Validate required data
            $this->validateRequiredData($data);

            // Check for duplicates (excluding current record)
            $this->validateNoDuplicates($availableCourse, $data);

            // Update the AvailableCourse
            $this->updateAvailableCourseRecord($availableCourse, $data);

            // Update schedules and assignments (if present)
            $this->handleScheduleDetails($availableCourse, $data);

            // Update eligibility
            $this->handleEligibility($availableCourse, $data);

            return $availableCourse->fresh(['programs', 'levels', 'schedules']);
        });
    }

    /**
     * Validate required data for updating an available course.
     */
    public function validateRequiredData(array $data): void
    {
        if (empty($data['course_id'])) {
            throw new BusinessValidationException('Course ID is required.');
        }
        if (empty($data['term_id'])) {
            throw new BusinessValidationException('Term ID is required.');
        }

        $eligibilityMode = $data['mode'] ?? 'individual';
        $validModes = ['universal', 'all_programs', 'all_levels', 'individual'];
        if (!in_array($eligibilityMode, $validModes)) {
            throw new BusinessValidationException('Invalid eligibility mode. Must be one of: ' . implode(', ', $validModes));
        }

        switch ($eligibilityMode) {
            case 'all_programs':
                if (empty($data['level_id'])) {
                    throw new BusinessValidationException('Level ID is required for all_programs eligibility mode.');
                }
                break;
            case 'all_levels':
                if (empty($data['program_id'])) {
                    throw new BusinessValidationException('Program ID is required for all_levels eligibility mode.');
                }
                break;
            case 'individual':
                if (empty($data['eligibility']) || !is_array($data['eligibility'])) {
                    throw new BusinessValidationException('Eligibility array is required for individual eligibility mode.');
                }
                foreach ($data['eligibility'] as $pair) {
                    if (empty($pair['program_id']) || empty($pair['level_id']) || !isset($pair['group_ids'])) {
                        throw new BusinessValidationException('Each eligibility pair must have program_id, level_id, and at least one group.');
                    }
                    if (!is_array($pair['group_ids']) || count($pair['group_ids']) === 0) {
                        throw new BusinessValidationException('Each eligibility pair must include at least one group id.');
                    }
                }
                break;
            case 'universal':
                break;
        }

        if (isset($data['schedule_details']) && is_array($data['schedule_details'])) {
            foreach ($data['schedule_details'] as $index => $detail) {
                if (empty($detail['schedule_slot_id']) && (empty($detail['schedule_slot_ids']) || !is_array($detail['schedule_slot_ids']))) {
                    throw new BusinessValidationException("Schedule slot ID(s) are required for schedule detail at index {$index}.");
                }
                if (empty($detail['group_numbers'])) {
                    throw new BusinessValidationException("Group number is required for schedule detail at index {$index}.");
                }
                if (empty($detail['activity_type'])) {
                    throw new BusinessValidationException("Activity type is required for schedule detail at index {$index}.");
                }
                
                $slotIds = [];
                if (!empty($detail['schedule_slot_ids']) && is_array($detail['schedule_slot_ids'])) {
                    $slotIds = $detail['schedule_slot_ids'];
                } elseif (!empty($detail['schedule_slot_id'])) {
                    $slotIds = [$detail['schedule_slot_id']];
                }
                
                foreach ($slotIds as $slotId) {
                    if (!ScheduleSlot::where('id', $slotId)->exists()) {
                        throw new BusinessValidationException("Schedule slot with ID {$slotId} does not exist in schedule detail at index {$index}.");
                    }
                }
                
                if (isset($detail['min_capacity']) || isset($detail['max_capacity'])) {
                    $minCapacity = $detail['min_capacity'] ?? 1;
                    $maxCapacity = $detail['max_capacity'] ?? 30;
                    if ($minCapacity > $maxCapacity) {
                        throw new BusinessValidationException("Minimum capacity cannot be greater than maximum capacity in schedule detail at index {$index}.");
                    }
                    if ($minCapacity < 0 || $maxCapacity < 0) {
                        throw new BusinessValidationException("Capacity values cannot be negative in schedule detail at index {$index}.");
                    }
                }
            }
        }
    }

    /**
     * Validate that there are no duplicate available courses.
     */
    public function validateNoDuplicates(AvailableCourse $availableCourse, array $data): void
    {
        $courseId = $data['course_id'];
        $termId = $data['term_id'];
        $eligibilityMode = $data['mode'] ?? 'individual';

        if ($eligibilityMode === 'universal') {
            $exists = AvailableCourse::where('mode', 'universal')
                ->where('course_id', $courseId)
                ->where('term_id', $termId)
                ->where('id', '!=', $availableCourse->id)
                ->exists();
            if ($exists) {
                throw new BusinessValidationException('A universal available course for this Course and Term already exists.');
            }
        } else {
            $conflict = false;
            switch ($eligibilityMode) {
                case 'all_programs':
                    $levelId = $data['level_id'];
                    $conflict = AvailableCourse::where('course_id', $courseId)
                        ->where('term_id', $termId)
                        ->where('id', '!=', $availableCourse->id)
                        ->whereHas('eligibilities', function ($q) use ($levelId) {
                            $q->where('level_id', $levelId);
                        })->exists();
                    break;
                case 'all_levels':
                    $programId = $data['program_id'];
                    $conflict = AvailableCourse::where('course_id', $courseId)
                        ->where('term_id', $termId)
                        ->where('id', '!=', $availableCourse->id)
                        ->whereHas('eligibilities', function ($q) use ($programId) {
                            $q->where('program_id', $programId);
                        })->exists();
                    break;
                case 'individual':
                default:
                    foreach ($data['eligibility'] as $pair) {
                        $groupIds = is_array($pair['group_ids']) ? $pair['group_ids'] : (isset($pair['group']) ? [$pair['group']] : []);
                        foreach ($groupIds as $g) {
                            $conflict = AvailableCourse::where('course_id', $courseId)
                                ->where('term_id', $termId)
                                ->where('id', '!=', $availableCourse->id)
                                ->whereHas('eligibilities', function ($q) use ($pair, $g) {
                                    $q->where('program_id', $pair['program_id'])
                                      ->where('level_id', $pair['level_id'])
                                      ->where('group', (int) $g);
                                })->exists();
                            if ($conflict) break 2;
                        }
                    }
                    break;
            }
            if ($conflict) {
                throw new BusinessValidationException('An available course with the same Course, Term, Program, and Level already exists.');
            }
        }
    }

    /**
     * Update the AvailableCourse record.
     */
    public function updateAvailableCourseRecord(AvailableCourse $availableCourse, array $data): void
    {
        $availableCourse->update([
            'course_id' => $data['course_id'],
            'term_id' => $data['term_id'],
            'mode' => $data['mode'] ?? 'individual',
        ]);
    }

    /**
     * Handle schedule details and update assignments for the available course.
     * CRITICAL: Schedules with enrollments are NEVER modified or deleted.
     */
    public function handleScheduleDetails(AvailableCourse $availableCourse, array $data): void
    {
        if (!isset($data['schedule_details']) || !is_array($data['schedule_details'])) {
            return;
        }

        $existingSchedules = $availableCourse->schedules()->with('enrollments')->get();
        $schedulesWithEnrollments = collect();
        $emptySchedules = collect();

        // Separate schedules by enrollment status
        foreach ($existingSchedules as $schedule) {
            if ($schedule->enrollments()->count() > 0) {
                $schedulesWithEnrollments->push($schedule);
            } else {
                $emptySchedules->push($schedule);
            }
        }

        // Build map of incoming schedule details
        $incomingScheduleMap = collect();
        foreach ($data['schedule_details'] as $detail) {
            $groupNumbers = is_array($detail['group_numbers'] ?? null) ? $detail['group_numbers'] : [];
            foreach ($groupNumbers as $groupNumber) {
                $key = $groupNumber . '|' . ($detail['activity_type'] ?? '') . '|' . ($detail['location'] ?? '');
                $incomingScheduleMap->put($key, $detail);
            }
        }

        // CRITICAL CHECK: Verify all schedules with enrollments exist in new data
        foreach ($schedulesWithEnrollments as $schedule) {
            $key = $schedule->group . '|' . ($schedule->activity_type ?? '') . '|' . ($schedule->location ?? '');
            if (!$incomingScheduleMap->has($key)) {
                throw new BusinessValidationException(
                    "Cannot remove schedule (Group: {$schedule->group}, Activity: {$schedule->activity_type}, Location: {$schedule->location}) because it has {$schedule->enrollments()->count()} enrollment(s). You must keep this schedule unchanged."
                );
            }
        }

        // Delete only empty schedules (with their assignments)
        foreach ($emptySchedules as $schedule) {
            // Delete assignments first
            ScheduleAssignment::where('available_course_schedule_id', $schedule->id)->delete();
            // Then delete the schedule
            $schedule->delete();
        }

        // Process incoming schedule details
        foreach ($data['schedule_details'] as $detail) {
            $groupNumbers = is_array($detail['group_numbers'] ?? null) ? $detail['group_numbers'] : [];
            $slotIds = $detail['schedule_slot_ids'] ?? [];

            foreach ($groupNumbers as $groupNumber) {
                $key = $groupNumber . '|' . ($detail['activity_type'] ?? '') . '|' . ($detail['location'] ?? '');
                
                // Check if this schedule has enrollments
                $scheduleWithEnrollments = $schedulesWithEnrollments->first(function ($s) use ($groupNumber, $detail) {
                    return $s->group == $groupNumber 
                        && $s->activity_type == ($detail['activity_type'] ?? null) 
                        && $s->location == ($detail['location'] ?? null);
                });

                if ($scheduleWithEnrollments) {
                    // CRITICAL: DO NOT modify schedule with enrollments
                    // DO NOT delete assignments
                    // DO NOT update capacity
                    // DO NOT create new assignments
                    // Simply skip this schedule completely
                    continue;
                }

                // Check if schedule exists (but is empty)
                $existingEmptySchedule = $emptySchedules->first(function ($s) use ($groupNumber, $detail) {
                    return $s->group == $groupNumber 
                        && $s->activity_type == ($detail['activity_type'] ?? null) 
                        && $s->location == ($detail['location'] ?? null);
                });

                if ($existingEmptySchedule) {
                    // This should not happen as we deleted empty schedules above
                    // But if it does, update it
                    $existingEmptySchedule->update([
                        'min_capacity' => $detail['min_capacity'] ?? 1,
                        'max_capacity' => $detail['max_capacity'] ?? 30,
                    ]);
                    $courseDetail = $existingEmptySchedule;
                } else {
                    // Create new schedule
                    $courseDetail = AvailableCourseSchedule::create([
                        'available_course_id' => $availableCourse->id,
                        'group' => $groupNumber !== null ? (int) $groupNumber : null,
                        'activity_type' => $detail['activity_type'] ?? null,
                        'location' => $detail['location'] ?? null,
                        'min_capacity' => $detail['min_capacity'] ?? 1,
                        'max_capacity' => $detail['max_capacity'] ?? 30,
                    ]);
                }

                // Generate slot info
                $slotInfo = '';
                if (count($slotIds) > 1) {
                    $firstSlot = ScheduleSlot::find($slotIds[0]);
                    $lastSlot = ScheduleSlot::find(end($slotIds));
                    if ($firstSlot && $lastSlot) {
                        $slotInfo = "Slots {$firstSlot->slot_order}-{$lastSlot->slot_order}";
                    } else {
                        $slotInfo = "Slots";
                    }
                }

                // Create schedule assignments
                foreach ($slotIds as $index => $slotId) {
                    if (count($slotIds) === 1) {
                        $slot = ScheduleSlot::find($slotId);
                        $slotOrder = $slot ? $slot->slot_order : ($index + 1);
                        $slotInfo = "Slot {$slotOrder}";
                    }
                    
                    $generatedTitle = $detail['title'] ?? "{$detail['activity_type']} - Group {$groupNumber} - {$slotInfo}";
                    $generatedDescription = $detail['description'] ?? "Scheduled {$detail['activity_type']} for Group {$groupNumber} during {$slotInfo} at {$detail['location']}.";
                    
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
    }

    /**
     * Handle eligibility assignment for the available course.
     * Syncs eligibility records based on mode.
     */
    public function handleEligibility(AvailableCourse $availableCourse, array $data): void
    {
        $eligibilityMode = $data['mode'] ?? 'individual';

        switch ($eligibilityMode) {
            case 'universal':
                // Clear all eligibility for universal mode
                $availableCourse->eligibilities()->delete();
                break;
                
            case 'all_programs':
                if (!isset($data['level_id'])) {
                    return;
                }
                $allPrograms = Program::pluck('id')->toArray();
                $bulkEligibility = [];
                foreach ($allPrograms as $programId) {
                    $bulkEligibility[] = [
                        'program_id' => $programId,
                        'level_id' => $data['level_id']
                    ];
                }
                $availableCourse->eligibilities()->delete();
                $availableCourse->setProgramLevelPairs($bulkEligibility);
                break;
                
            case 'all_levels':
                if (!isset($data['program_id'])) {
                    return;
                }
                $allLevels = Level::pluck('id')->toArray();
                $bulkEligibility = [];
                foreach ($allLevels as $levelId) {
                    $bulkEligibility[] = [
                        'program_id' => $data['program_id'],
                        'level_id' => $levelId
                    ];
                }
                $availableCourse->eligibilities()->delete();
                $availableCourse->setProgramLevelPairs($bulkEligibility);
                break;
                
            case 'individual':
            default:
                if (!isset($data['eligibility']) || !is_array($data['eligibility'])) {
                    return;
                }
                
                // Sync eligibility: remove old, add new
                $existingKeys = [];
                foreach ($availableCourse->eligibilities as $e) {
                    $key = $e->program_id . '-' . $e->level_id . '-' . $e->group;
                    $existingKeys[$key] = $e->id;
                }
                
                $newKeys = [];
                foreach ($data['eligibility'] as $pair) {
                    $programId = $pair['program_id'] ?? null;
                    $levelId = $pair['level_id'] ?? null;
                    $groupIds = is_array($pair['group_ids']) ? $pair['group_ids'] : (isset($pair['group']) ? [$pair['group']] : []);
                    
                    foreach ($groupIds as $g) {
                        if ($programId && $levelId && $g) {
                            $key = $programId . '-' . $levelId . '-' . $g;
                            $newKeys[$key] = true;
                        }
                    }
                }
                
                // Delete eligibilities not in new data
                $toDelete = array_diff_key($existingKeys, $newKeys);
                if (!empty($toDelete)) {
                    $availableCourse->eligibilities()->whereIn('id', array_values($toDelete))->delete();
                }
                
                // Add new eligibilities not in existing
                $toAdd = array_diff_key($newKeys, $existingKeys);
                if (!empty($toAdd)) {
                    $expanded = [];
                    foreach (array_keys($toAdd) as $key) {
                        list($programId, $levelId, $g) = explode('-', $key);
                        $expanded[] = [
                            'program_id' => (int)$programId,
                            'level_id' => (int)$levelId,
                            'group' => (int)$g,
                        ];
                    }
                    $availableCourse->addProgramLevelPairs($expanded);
                }
                break;
        }
    }
}