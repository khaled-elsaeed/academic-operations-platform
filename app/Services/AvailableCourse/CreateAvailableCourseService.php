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
use Illuminate\Pipeline\Pipeline;

class CreateAvailableCourseService
{
    /**
     * Create a single available course with eligibility mode support using Pipeline pattern.
     *
     * @param array $data
     * @return AvailableCourse
     * @throws BusinessValidationException
     */
    public function createAvailableCourseSingle(array $data): AvailableCourse
    {
        return DB::transaction(function () use ($data) {
            // Validate required data
            $this->validateRequiredData($data);

            // Check for duplicates
            $this->validateNoDuplicates($data);

            // Create the AvailableCourse
            $availableCourse = $this->createAvailableCourseRecord($data);

            // Create schedules and assignments (if present)
            $this->handleScheduleDetails($availableCourse, $data);

            // Handle eligibility
            $this->handleEligibility($availableCourse, $data);

            return $availableCourse->fresh(['programs', 'levels', 'schedules']);
        });
    }

    /**
     * Validate required data for creating an available course.
     *
     * @param array $data
     * @throws BusinessValidationException
     * @return void
     */
    public function validateRequiredData(array $data): void
    {
        // 1. Validate required fields
        if (empty($data['course_id'])) {
            throw new BusinessValidationException('Course ID is required.');
        }
        if (empty($data['term_id'])) {
            throw new BusinessValidationException('Term ID is required.');
        }

        // 2. Validate eligibility mode
        $eligibilityMode = $data['mode'] ?? 'individual';
        $validModes = ['universal', 'all_programs', 'all_levels', 'individual'];
        if (!in_array($eligibilityMode, $validModes)) {
                throw new BusinessValidationException('Invalid eligibility mode. Must be one of: ' . implode(', ', $validModes));
            }


        // 3. Validate eligibility mode requirements
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


        // 4. Validate schedule details
        if (isset($data['schedule_details']) && is_array($data['schedule_details'])) {
            foreach ($data['schedule_details'] as $index => $detail) {
                if (empty($detail['schedule_slot_id']) && (empty($detail['schedule_slot_ids']) || !is_array($detail['schedule_slot_ids']))) {
                    throw new BusinessValidationException("Schedule slot ID(s) are required for schedule detail at index {$index}.");
                }
                if (empty($detail['group_number'])) {
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
                // Validate capacity
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
     * Validate that there are no duplicate available courses for the given data.
     *
     * @param array $data
     * @throws BusinessValidationException
     * @return void
     */
    public function validateNoDuplicates(array $data): void
    {
        $courseId = $data['course_id'];
        $termId = $data['term_id'];
        $eligibilityMode = $data['mode'] ?? 'individual';

        if ($eligibilityMode === 'universal') {
            $exists = AvailableCourse::where('mode', 'universal')
                ->where('course_id', $courseId)
                ->where('term_id', $termId)
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
                        ->whereHas('eligibilities', function ($q) use ($levelId) {
                            $q->where('level_id', $levelId);
                        })->exists();
                    break;
                case 'all_levels':
                    $programId = $data['program_id'];
                    $conflict = AvailableCourse::where('course_id', $courseId)
                        ->where('term_id', $termId)
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
     * Create the AvailableCourse record.
     *
     * @param array $data
     * @return AvailableCourse
     */
    public function createAvailableCourseRecord(array $data): AvailableCourse
    {
        $courseId = $data['course_id'];
        $termId = $data['term_id'];
        $eligibilityMode = $data['mode'] ?? 'individual';

        return AvailableCourse::create([
            'course_id' => $courseId,
            'term_id' => $termId,
            'mode' => $eligibilityMode,
        ]);
    }


    /**
     * Handle schedule details and create assignments for the available course.
     *
     * @param AvailableCourse $availableCourse
     * @param array $data
     * @return void
     */
    public function handleScheduleDetails(AvailableCourse $availableCourse, array $data): void
    {
        if (isset($data['schedule_details']) && is_array($data['schedule_details'])) {
            foreach ($data['schedule_details'] as $detail) {
                $courseDetail = AvailableCourseSchedule::create([
                    'available_course_id' => $availableCourse->id,
                    'location' => $detail['location'] ?? null,
                    'min_capacity' => $detail['min_capacity'] ?? 1,
                    'max_capacity' => $detail['max_capacity'] ?? 30,
                ]);
                $slotIds = $detail['schedule_slot_ids'] ?? [];
                foreach ($slotIds as $index => $slotId) {
                    $slot = ScheduleSlot::find($slotId);
                    $slotOrder = $slot ? $slot->slot_order : ($index + 1);
                    $slotInfo = count($slotIds) > 1 ? "Slots {$slotOrder}" : "Slot {$slotOrder}";
                    if (count($slotIds) > 1 && $index === 0) {
                        $firstSlot = ScheduleSlot::find($slotIds[0]);
                        $lastSlot = ScheduleSlot::find(end($slotIds));
                        if ($firstSlot && $lastSlot) {
                            $slotInfo = "Slots {$firstSlot->slot_order}-{$lastSlot->slot_order}";
                        }
                    }
                    $generatedTitle = $detail['title'] ?? "{$detail['activity_type']} - Group {$detail['group_number']} - {$slotInfo}";
                    $generatedDescription = $detail['description'] ?? "Scheduled {$detail['activity_type']} for Group {$detail['group_number']} during {$slotInfo} at {$detail['location']}.";
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
     *
     * @param AvailableCourse $availableCourse
     * @param array $data
     * @return void
     */
    public function handleEligibility(AvailableCourse $availableCourse, array $data): void
    {
        $eligibilityMode = $data['mode'] ?? 'individual';
        switch ($eligibilityMode) {
            case 'universal':
                break;
            case 'all_programs':
                $allPrograms = Program::pluck('id')->toArray();
                $bulkEligibility = [];
                foreach ($allPrograms as $programId) {
                    $bulkEligibility[] = [
                        'program_id' => $programId,
                        'level_id' => $data['level_id']
                    ];
                }
                $availableCourse->setProgramLevelPairs($bulkEligibility);
                break;
            case 'all_levels':
                $allLevels = Level::pluck('id')->toArray();
                $bulkEligibility = [];
                foreach ($allLevels as $levelId) {
                    $bulkEligibility[] = [
                        'program_id' => $data['program_id'],
                        'level_id' => $levelId
                    ];
                }
                $availableCourse->setProgramLevelPairs($bulkEligibility);
                break;
            case 'individual':
            default:
                // Expand group_ids into individual pairs
                $expanded = [];
                foreach ($data['eligibility'] as $pair) {
                    $programId = $pair['program_id'] ?? null;
                    $levelId = $pair['level_id'] ?? null;
                    $groupIds = is_array($pair['group_ids']) ? $pair['group_ids'] : (isset($pair['group']) ? [$pair['group']] : []);
                    foreach ($groupIds as $g) {
                        if ($programId && $levelId && $g) {
                            $expanded[] = [
                                'program_id' => $programId,
                                'level_id' => $levelId,
                                'group' => (int) $g,
                            ];
                        }
                    }
                }
                if (!empty($expanded)) {
                    $availableCourse->setProgramLevelPairs($expanded);
                }
                break;
        }
    }
}