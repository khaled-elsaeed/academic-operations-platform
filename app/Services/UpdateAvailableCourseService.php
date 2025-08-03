<?php

namespace App\Services;

use App\Models\AvailableCourse;
use App\Models\AvailableCourseSchedule;
use App\Models\Program;
use App\Models\Level;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleSlot;
use App\Models\Schedule\ScheduleAssignment;
use App\Exceptions\BusinessValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class UpdateAvailableCourseService
{
    /**
     * Update an existing available course with eligibility mode support.
     *
     * @param AvailableCourse $availableCourse
     * @param array $data
     * @return AvailableCourse
     * @throws BusinessValidationException
     */
    public function updateAvailableCourse(AvailableCourse $availableCourse, array $data): AvailableCourse
    {
        \Log::info('Starting update of available course', [
            'available_course_id' => $availableCourse->id,
            'data' => $data
        ]);
        $this->validateUpdateData($data, $availableCourse);
        $this->ensureUpdateDoesNotCreateConflict($data, $availableCourse->id);

        $result = DB::transaction(function () use ($availableCourse, $data) {
            \Log::info('Updating available course record', [
                'available_course_id' => $availableCourse->id,
                'old_eligibility_mode' => $availableCourse->eligibility_mode,
                'new_eligibility_mode' => $data['eligibility_mode'] ?? $availableCourse->eligibility_mode
            ]);

            // Update basic course information
            $this->updateAvailableCourseRecord($availableCourse, $data);

            // Handle eligibility updates
            \Log::info('Updating eligibility for available course', [
                'available_course_id' => $availableCourse->id
            ]);
            $this->updateEligibility($availableCourse, $data);

            // Handle schedule details updates
            if (array_key_exists('schedule_details', $data)) {
                \Log::info('Updating schedule details', [
                    'available_course_id' => $availableCourse->id,
                    'schedule_details_count' => is_array($data['schedule_details']) ? count($data['schedule_details']) : 0
                ]);
                $this->updateScheduleDetails($availableCourse, $data['schedule_details'] ?? []);
            }

            // Handle regular course details updates
            if (array_key_exists('details', $data)) {
                \Log::info('Updating course details', [
                    'available_course_id' => $availableCourse->id,
                    'details_count' => is_array($data['details']) ? count($data['details']) : 0
                ]);
                $this->updateCourseDetails($availableCourse, $data['details'] ?? []);
            }

            return $availableCourse->fresh(['programs', 'levels', 'schedules.scheduleAssignments']);
        });

        \Log::info('Available course updated successfully', [
            'available_course_id' => $result->id
        ]);

        return $result;
    }

    /**
     * Find the available course or throw exception.
     *
     * @param int $availableCourseId
     * @return AvailableCourse
     * @throws BusinessValidationException
     */
    private function findAvailableCourse(int $availableCourseId): AvailableCourse
    {
        $availableCourse = AvailableCourse::find($availableCourseId);

        if (!$availableCourse) {
            throw new BusinessValidationException('Available course not found.');
        }

        return $availableCourse;
    }

    /**
     * Validate update data.
     *
     * @param array $data
     * @param AvailableCourse $availableCourse
     * @throws BusinessValidationException
     */
    private function validateUpdateData(array $data, AvailableCourse $availableCourse): void
    {
        // Validate eligibility mode if provided
        if (isset($data['eligibility_mode'])) {
            $validModes = ['universal', 'all_programs', 'all_levels', 'individual'];

            if (!in_array($data['eligibility_mode'], $validModes)) {
                throw new BusinessValidationException('Invalid eligibility mode. Must be one of: ' . implode(', ', $validModes));
            }
        }

        // Get the eligibility mode (new or existing)
        $eligibilityMode = $data['eligibility_mode'] ?? $availableCourse->eligibility_mode;

        // Validate mode-specific requirements
        $this->validateEligibilityModeRequirements($data, $eligibilityMode);

        // Validate schedule details if provided
        if (isset($data['schedule_details']) && is_array($data['schedule_details'])) {
            $this->validateScheduleDetails($data['schedule_details']);
        }

        // Validate regular details if provided
        if (isset($data['details']) && is_array($data['details'])) {
            $this->validateCourseDetails($data['details']);
        }
    }

    /**
     * Validate eligibility mode specific requirements.
     *
     * @param array $data
     * @param string $eligibilityMode
     * @throws BusinessValidationException
     */
    private function validateEligibilityModeRequirements(array $data, string $eligibilityMode): void
    {
        switch ($eligibilityMode) {
            case 'all_programs':
                if (array_key_exists('level_id', $data) && empty($data['level_id'])) {
                    throw new BusinessValidationException('Level ID is required for all_programs eligibility mode.');
                }
                break;

            case 'all_levels':
                if (array_key_exists('program_id', $data) && empty($data['program_id'])) {
                    throw new BusinessValidationException('Program ID is required for all_levels eligibility mode.');
                }
                break;

            case 'individual':
                if (array_key_exists('eligibility', $data)) {
                    if (empty($data['eligibility']) || !is_array($data['eligibility'])) {
                        throw new BusinessValidationException('Eligibility array is required for individual eligibility mode.');
                    }

                    foreach ($data['eligibility'] as $pair) {
                        if (empty($pair['program_id']) || empty($pair['level_id'])) {
                            throw new BusinessValidationException('Each eligibility pair must have both program_id and level_id.');
                        }
                    }
                }
                break;

            case 'universal':
                // No additional validation needed for universal mode
                break;
        }
    }

    /**
     * Validate schedule details.
     *
     * @param array $scheduleDetails
     * @throws BusinessValidationException
     */
    private function validateScheduleDetails(array $scheduleDetails): void
    {
        foreach ($scheduleDetails as $index => $detail) {
            // Skip validation for items marked for deletion
            if (isset($detail['_action']) && $detail['_action'] === 'delete') {
                continue;
            }

            // Validate required fields for new/updated items
            if (empty($detail['schedule_slot_id']) && !isset($detail['id']) && !isset($detail['schedule_assignment_id'])) {
                throw new BusinessValidationException("Schedule slot ID is required for new schedule detail at index {$index}.");
            }

            if (empty($detail['group_number']) && !isset($detail['id']) && !isset($detail['schedule_assignment_id'])) {
                throw new BusinessValidationException("Group number is required for new schedule detail at index {$index}.");
            }

            if (empty($detail['activity_type']) && !isset($detail['id']) && !isset($detail['schedule_assignment_id'])) {
                throw new BusinessValidationException("Activity type is required for new schedule detail at index {$index}.");
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

    /**
     * Validate course details.
     *
     * @param array $details
     * @throws BusinessValidationException
     */
    private function validateCourseDetails(array $details): void
    {
        foreach ($details as $index => $detail) {
            // Skip validation for items marked for deletion
            if (isset($detail['_action']) && $detail['_action'] === 'delete') {
                continue;
            }

            if (empty($detail['group']) && !isset($detail['id'])) {
                throw new BusinessValidationException("Group is required for new course detail at index {$index}.");
            }

            if (empty($detail['activity_type']) && !isset($detail['id'])) {
                throw new BusinessValidationException("Activity type is required for new course detail at index {$index}.");
            }

            if (isset($detail['min_capacity']) || isset($detail['max_capacity'])) {
                $minCapacity = $detail['min_capacity'] ?? 1;
                $maxCapacity = $detail['max_capacity'] ?? 30;

                if ($minCapacity > $maxCapacity) {
                    throw new BusinessValidationException("Minimum capacity cannot be greater than maximum capacity in course detail at index {$index}.");
                }

                if ($minCapacity < 0 || $maxCapacity < 0) {
                    throw new BusinessValidationException("Capacity values cannot be negative in course detail at index {$index}.");
                }
            }
        }
    }

    /**
     * Ensure update does not create conflicts.
     *
     * @param array $data
     * @param int $excludeId
     * @throws BusinessValidationException
     */
    private function ensureUpdateDoesNotCreateConflict(array $data, int $excludeId): void
    {
        // Only check if relevant fields are being updated
        $needsConflictCheck = isset($data['course_id']) ||
                             isset($data['term_id']) ||
                             isset($data['eligibility_mode']) ||
                             isset($data['level_id']) ||
                             isset($data['program_id']) ||
                             isset($data['eligibility']);

        if (!$needsConflictCheck) {
            return;
        }

        $eligibilityMode = $data['eligibility_mode'] ?? null;

        if ($eligibilityMode === 'universal') {
            if ($this->universalAvailableCourseExists($data, $excludeId)) {
                throw new BusinessValidationException('A universal available course for this Course and Term already exists.');
            }
        } elseif ($eligibilityMode !== null) {
            if ($this->availableCourseEligibilitiesExist($data, $excludeId)) {
                throw new BusinessValidationException('An available course with the same Course, Term, Program, and Level already exists.');
            }
        }
    }

    /**
     * Check if a universal available course exists.
     *
     * @param array $data
     * @param int|null $excludeId
     * @return bool
     */
    private function universalAvailableCourseExists(array $data, int $excludeId = null): bool
    {
        $query = AvailableCourse::where('eligibility_mode', 'universal');

        if (isset($data['course_id'])) {
            $query->where('course_id', $data['course_id']);
        }

        if (isset($data['term_id'])) {
            $query->where('term_id', $data['term_id']);
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check if an available course with the same eligibilities exists.
     *
     * @param array $data
     * @param int|null $excludeId
     * @return bool
     */
    private function availableCourseEligibilitiesExist(array $data, int $excludeId = null): bool
    {
        $eligibilityMode = $data['eligibility_mode'];

        switch ($eligibilityMode) {
            case 'all_programs':
                return $this->checkAllProgramsConflict($data, $excludeId);

            case 'all_levels':
                return $this->checkAllLevelsConflict($data, $excludeId);

            case 'individual':
                return $this->checkIndividualEligibilityConflicts($data, $excludeId);

            default:
                return false;
        }
    }

    /**
     * Check for conflicts with all_programs eligibility mode.
     *
     * @param array $data
     * @param int|null $excludeId
     * @return bool
     */
    private function checkAllProgramsConflict(array $data, int $excludeId = null): bool
    {
        $query = AvailableCourse::query();

        if (isset($data['course_id'])) {
            $query->where('course_id', $data['course_id']);
        }

        if (isset($data['term_id'])) {
            $query->where('term_id', $data['term_id']);
        }

        if (isset($data['level_id'])) {
            $query->whereHas('eligibilities', function ($eq) use ($data) {
                $eq->where('level_id', $data['level_id']);
            });
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check for conflicts with all_levels eligibility mode.
     *
     * @param array $data
     * @param int|null $excludeId
     * @return bool
     */
    private function checkAllLevelsConflict(array $data, int $excludeId = null): bool
    {
        $query = AvailableCourse::query();

        if (isset($data['course_id'])) {
            $query->where('course_id', $data['course_id']);
        }

        if (isset($data['term_id'])) {
            $query->where('term_id', $data['term_id']);
        }

        if (isset($data['program_id'])) {
            $query->whereHas('eligibilities', function ($eq) use ($data) {
                $eq->where('program_id', $data['program_id']);
            });
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check for conflicts with individual eligibility pairs.
     *
     * @param array $data
     * @param int|null $excludeId
     * @return bool
     */
    private function checkIndividualEligibilityConflicts(array $data, int $excludeId = null): bool
    {
        if (!isset($data['eligibility']) || !is_array($data['eligibility'])) {
            return false;
        }

        foreach ($data['eligibility'] as $pair) {
            $query = AvailableCourse::query();

            if (isset($data['course_id'])) {
                $query->where('course_id', $data['course_id']);
            }

            if (isset($data['term_id'])) {
                $query->where('term_id', $data['term_id']);
            }

            $query->whereHas('eligibilities', function ($q) use ($pair) {
                $q->where('program_id', $pair['program_id'])
                  ->where('level_id', $pair['level_id']);
            });

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update the AvailableCourse record.
     *
     * @param AvailableCourse $availableCourse
     * @param array $data
     * @return void
     */
    private function updateAvailableCourseRecord(AvailableCourse $availableCourse, array $data): void
    {
        $updateData = [];

        if (isset($data['course_id'])) {
            $updateData['course_id'] = $data['course_id'];
        }

        if (isset($data['term_id'])) {
            $updateData['term_id'] = $data['term_id'];
        }

        if (isset($data['eligibility_mode'])) {
            $updateData['eligibility_mode'] = $data['eligibility_mode'];
        }

        if (!empty($updateData)) {
            $availableCourse->update($updateData);
        }
    }

    /**
     * Update eligibility based on eligibility mode.
     *
     * @param AvailableCourse $availableCourse
     * @param array $data
     * @return void
     */
    private function updateEligibility(AvailableCourse $availableCourse, array $data): void
    {
        $eligibilityMode = $data['eligibility_mode'] ?? $availableCourse->eligibility_mode;

        // Clear existing eligibilities if mode is changing or if explicitly updating eligibilities
        $shouldClearEligibilities = isset($data['eligibility_mode']) ||
                                   isset($data['eligibility']) ||
                                   isset($data['program_id']) ||
                                   isset($data['level_id']);

        if ($shouldClearEligibilities) {
            $availableCourse->clearProgramLevelPairs();
        }

        switch ($eligibilityMode) {
            case 'universal':
                // Universal courses have no eligibility restrictions
                break;

            case 'all_programs':
                if (isset($data['level_id'])) {
                    $this->createAllProgramsEligibility($availableCourse, $data['level_id']);
                }
                break;

            case 'all_levels':
                if (isset($data['program_id'])) {
                    $this->createAllLevelsEligibility($availableCourse, $data['program_id']);
                }
                break;

            case 'individual':
                if (isset($data['eligibility'])) {
                    $this->attachEligibilities($availableCourse, $data['eligibility']);
                }
                break;
        }
    }

    /**
     * Create eligibility for all programs with a specific level.
     *
     * @param AvailableCourse $availableCourse
     * @param int $levelId
     * @return void
     */
    private function createAllProgramsEligibility(AvailableCourse $availableCourse, int $levelId): void
    {
        $allPrograms = Program::pluck('id')->toArray();
        $bulkEligibility = [];

        foreach ($allPrograms as $programId) {
            $bulkEligibility[] = [
                'program_id' => $programId,
                'level_id' => $levelId
            ];
        }

        $this->attachEligibilities($availableCourse, $bulkEligibility);
    }

    /**
     * Create eligibility for all levels with a specific program.
     *
     * @param AvailableCourse $availableCourse
     * @param int $programId
     * @return void
     */
    private function createAllLevelsEligibility(AvailableCourse $availableCourse, int $programId): void
    {
        $allLevels = Level::pluck('id')->toArray();
        $bulkEligibility = [];

        foreach ($allLevels as $levelId) {
            $bulkEligibility[] = [
                'program_id' => $programId,
                'level_id' => $levelId
            ];
        }

        $this->attachEligibilities($availableCourse, $bulkEligibility);
    }

    /**
     * Attach eligibilities to the available course.
     *
     * @param AvailableCourse $availableCourse
     * @param array $eligibility
     * @return void
     */
    private function attachEligibilities(AvailableCourse $availableCourse, array $eligibility): void
    {
        $pairs = collect($eligibility)
            ->filter(function ($pair) {
                return isset($pair['program_id']) && isset($pair['level_id']);
            })
            ->map(function ($pair) {
                return [
                    'program_id' => $pair['program_id'],
                    'level_id' => $pair['level_id'],
                ];
            })
            ->unique(function ($pair) {
                return $pair['program_id'] . '-' . $pair['level_id'];
            })
            ->values()
            ->toArray();

        if (!empty($pairs)) {
            $availableCourse->setProgramLevelPairs($pairs);
        }
    }

    /**
     * Update schedule details for an available course.
     *
     * @param AvailableCourse $availableCourse
     * @param array $scheduleDetails
     * @return void
     */
    private function updateScheduleDetails(AvailableCourse $availableCourse, array $scheduleDetails): void
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
     * @param AvailableCourse $availableCourse
     * @param array $details
     * @return void
     */
    private function updateCourseDetails(AvailableCourse $availableCourse, array $details): void
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
        if (isset($detail['schedule_slot_id']) || isset($detail['schedule_assignment_id'])) {
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
     * Delete a schedule detail.
     *
     * @param array $detail
     * @param Collection $existingDetails
     * @return void
     */
    private function deleteScheduleDetail(array $detail, Collection $existingDetails): void
    {
        $existingDetail = null;
        if (isset($detail['id'])) {
            $existingDetail = $existingDetails->find($detail['id']);
        } elseif (isset($detail['schedule_assignment_id'])) {
            foreach ($existingDetails as $ed) {
                foreach ($ed->scheduleAssignments as $assignment) {
                    if ((string)$assignment->id === (string)$detail['schedule_assignment_id']) {
                        $existingDetail = $ed;
                        break 2;
                    }
                }
            }
        }
        if ($existingDetail) {
            $this->deleteScheduleDetailRecord($existingDetail);
        }
    }

    /**
     * Delete a course detail.
     *
     * @param array $detail
     * @param Collection $existingDetails
     * @return void
     */
    private function deleteCourseDetail(array $detail, Collection $existingDetails): void
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
        ScheduleAssignment::where('assignable_type', AvailableCourseSchedule::class)
            ->where('assignable_id', $detail->id)
            ->delete();

        // Delete the detail itself
        $detail->delete();
    }

    /**
     * Create an AvailableCourseSchedule record.
     *
     * @param AvailableCourse $availableCourse
     * @param array $detail
     * @return AvailableCourseSchedule
     */
    private function createAvailableCourseSchedule(AvailableCourse $availableCourse, array $detail): AvailableCourseSchedule
    {
        return AvailableCourseSchedule::create([
            'available_course_id' => $availableCourse->id,
            'group' => $detail['group_number'] ?? $detail['group'],
            'activity_type' => $detail['activity_type'],
            'min_capacity' => $detail['min_capacity'] ?? 1,
            'max_capacity' => $detail['max_capacity'] ?? 30,
        ]);
    }

    /**
     * Create a ScheduleAssignment for a given course detail.
     *
     * @param AvailableCourseSchedule $courseDetail
     * @param array $detail
     * @return void
     */
    private function createScheduleAssignmentForDetail(AvailableCourseSchedule $courseDetail, array $detail): void
    {
        $activityType = ucfirst(str_replace('_', ' ', $detail['activity_type']));
        $group = $detail['group_number'] ?? $detail['group'];
        $location = $detail['location'] ?? 'Main Campus';
        $slot = $detail['slot'] ?? null;

        $generatedTitle = $detail['title']
            ?? "{$activityType} - Group {$group}" . ($slot ? " - Slot {$slot}" : '');

        $generatedDescription = $detail['description']
            ?? "Scheduled {$activityType} for Group {$group}"
                . ($slot ? " during Slot {$slot}" : '')
                . " at {$location}.";

        ScheduleAssignment::create([
            'schedule_slot_id' => $detail['schedule_slot_id'],
            'assignable_type' => AvailableCourseSchedule::class,
            'assignable_id' => $courseDetail->id,
            'title' => $generatedTitle,
            'description' => $generatedDescription,
            'location' => $location,
            'capacity' => $detail['max_capacity'] ?? 30,
            'enrolled' => $detail['enrolled'] ?? 0,
            'resources' => $detail['resources'] ?? null,
            'status' => 'scheduled',
            'notes' => $detail['notes'] ?? null,
        ]);
    }

    /**
     * Update a ScheduleAssignment for a given course detail.
     *
     * @param AvailableCourseSchedule $courseDetail
     * @param array $detail
     * @return void
     */
    private function updateScheduleAssignmentForDetail(AvailableCourseSchedule $courseDetail, array $detail): void
    {
        $assignment = null;

        // If schedule_assignment_id is provided, use it to find the assignment
        if (isset($detail['schedule_assignment_id'])) {
            $assignment = ScheduleAssignment::where('id', $detail['schedule_assignment_id'])
                ->where('assignable_type', AvailableCourseSchedule::class)
                ->where('assignable_id', $courseDetail->id)
                ->first();
        }

        // Fallback: find the first assignment for this course detail
        if (!$assignment) {
            $assignment = ScheduleAssignment::where('assignable_type', AvailableCourseSchedule::class)
                ->where('assignable_id', $courseDetail->id)
                ->first();
        }

        if (!$assignment && isset($detail['schedule_slot_id'])) {
            // Create new assignment if it doesn't exist
            $this->createScheduleAssignmentForDetail($courseDetail, $detail);
            return;
        }

        if ($assignment) {
            $updateData = [];

            if (isset($detail['schedule_slot_id'])) {
                $updateData['schedule_slot_id'] = $detail['schedule_slot_id'];
            }

            if (isset($detail['title'])) {
                $updateData['title'] = $detail['title'];
            }

            if (isset($detail['description'])) {
                $updateData['description'] = $detail['description'];
            }

            if (isset($detail['location'])) {
                $updateData['location'] = $detail['location'];
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

            // Auto-generate title and description if not provided but other fields are updated
            if (empty($updateData['title']) && !empty($updateData)) {
                $activityType = ucfirst(str_replace('_', ' ', $courseDetail->activity_type));
                $group = $courseDetail->group;
                $slot = $detail['slot'] ?? null;
                $updateData['title'] = "{$activityType} - Group {$group}" . ($slot ? " - Slot {$slot}" : '');
            }

            if (empty($updateData['description']) && !empty($updateData)) {
                $activityType = ucfirst(str_replace('_', ' ', $courseDetail->activity_type));
                $group = $courseDetail->group;
                $location = $updateData['location'] ?? $assignment->location ?? 'Main Campus';
                $slot = $detail['slot'] ?? null;
                $updateData['description'] = "Scheduled {$activityType} for Group {$group}"
                    . ($slot ? " during Slot {$slot}" : '')
                    . " at {$location}.";
            }

            if (!empty($updateData)) {
                $assignment->update($updateData);
            }
        }
    }

    /**
     * Bulk update multiple available courses.
     *
     * @param array $updates Array of ['id' => int, 'data' => array] items
     * @return array Array of updated AvailableCourse instances
     * @throws BusinessValidationException
     */
    public function bulkUpdateAvailableCourses(array $updates): array
    {
        \Log::info('Starting bulk update of available courses', [
            'count' => count($updates)
        ]);

        $results = [];
        $errors = [];

        DB::transaction(function () use ($updates, &$results, &$errors) {
            foreach ($updates as $index => $update) {
                try {
                    if (!isset($update['id']) || !isset($update['data'])) {
                        throw new BusinessValidationException("Update at index {$index} must have 'id' and 'data' keys.");
                    }

                    $result = $this->updateAvailableCourse($update['id'], $update['data']);
                    $results[] = $result;
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'id' => $update['id'] ?? null,
                        'error' => $e->getMessage()
                    ];
                }
            }

            if (!empty($errors)) {
                throw new BusinessValidationException('Bulk update failed with errors: ' . json_encode($errors));
            }
        });

        \Log::info('Bulk update completed successfully', [
            'updated_count' => count($results)
        ]);

        return $results;
    }

    /**
     * Partially update an available course (only specific fields).
     *
     * @param int $availableCourseId
     * @param array $fields Specific fields to update
     * @return AvailableCourse
     * @throws BusinessValidationException
     */
    public function partialUpdateAvailableCourse(int $availableCourseId, array $fields): AvailableCourse
    {
        \Log::info('Starting partial update of available course', [
            'available_course_id' => $availableCourseId,
            'fields' => array_keys($fields)
        ]);

        $availableCourse = $this->findAvailableCourse($availableCourseId);

        // Only validate and update the provided fields
        $filteredData = array_intersect_key($fields, array_flip([
            'course_id', 'term_id', 'eligibility_mode', 'level_id', 'program_id', 'eligibility'
        ]));

        if (!empty($filteredData)) {
            $this->validateUpdateData($filteredData, $availableCourse);
            $this->ensureUpdateDoesNotCreateConflict($filteredData, $availableCourse->id);

            DB::transaction(function () use ($availableCourse, $filteredData) {
                $this->updateAvailableCourseRecord($availableCourse, $filteredData);
                $this->updateEligibility($availableCourse, $filteredData);
            });
        }

        \Log::info('Partial update completed successfully', [
            'available_course_id' => $availableCourse->id
        ]);

        return $availableCourse->fresh(['programs', 'levels', 'schedules.scheduleAssignments']);
    }

    /**
     * Toggle the eligibility mode of an available course.
     *
     * @param int $availableCourseId
     * @param string $newMode
     * @param array $additionalData Additional data needed for the new mode
     * @return AvailableCourse
     * @throws BusinessValidationException
     */
    public function toggleEligibilityMode(int $availableCourseId, string $newMode, array $additionalData = []): AvailableCourse
    {
        \Log::info('Toggling eligibility mode', [
            'available_course_id' => $availableCourseId,
            'new_mode' => $newMode
        ]);

        $availableCourse = $this->findAvailableCourse($availableCourseId);
        $oldMode = $availableCourse->eligibility_mode;

        $updateData = array_merge($additionalData, [
            'eligibility_mode' => $newMode
        ]);

        $result = $this->updateAvailableCourse($availableCourseId, $updateData);

        \Log::info('Eligibility mode toggled successfully', [
            'available_course_id' => $availableCourseId,
            'old_mode' => $oldMode,
            'new_mode' => $newMode
        ]);

        return $result;
    }

    /**
     * Clone an available course with modifications.
     *
     * @param int $sourceAvailableCourseId
     * @param array $modifications
     * @return AvailableCourse
     * @throws BusinessValidationException
     */
    public function cloneAndUpdateAvailableCourse(int $sourceAvailableCourseId, array $modifications = []): AvailableCourse
    {
        \Log::info('Cloning and updating available course', [
            'source_id' => $sourceAvailableCourseId,
            'modifications' => array_keys($modifications)
        ]);

        $sourceCourse = $this->findAvailableCourse($sourceAvailableCourseId);
        $sourceCourse->load(['eligibilities', 'schedules.scheduleAssignments']);

        // Prepare data for new course
        $cloneData = [
            'course_id' => $modifications['course_id'] ?? $sourceCourse->course_id,
            'term_id' => $modifications['term_id'] ?? $sourceCourse->term_id,
            'eligibility_mode' => $modifications['eligibility_mode'] ?? $sourceCourse->eligibility_mode,
        ];

        // Handle eligibility data based on mode
        switch ($cloneData['eligibility_mode']) {
            case 'all_programs':
                $cloneData['level_id'] = $modifications['level_id'] ?? $sourceCourse->eligibilities->first()?->level_id;
                break;

            case 'all_levels':
                $cloneData['program_id'] = $modifications['program_id'] ?? $sourceCourse->eligibilities->first()?->program_id;
                break;

            case 'individual':
                if (isset($modifications['eligibility'])) {
                    $cloneData['eligibility'] = $modifications['eligibility'];
                } else {
                    $cloneData['eligibility'] = $sourceCourse->eligibilities->map(function ($eligibility) {
                        return [
                            'program_id' => $eligibility->program_id,
                            'level_id' => $eligibility->level_id
                        ];
                    })->toArray();
                }
                break;
        }

        // Clone schedule details if requested
        if (isset($modifications['clone_schedules']) && $modifications['clone_schedules'] === true) {
            $cloneData['schedule_details'] = $sourceCourse->schedules->map(function ($schedule) {
                $assignment = $schedule->scheduleAssignments->first();
                return [
                    'schedule_slot_id' => $assignment?->schedule_slot_id,
                    'group_number' => $schedule->group,
                    'activity_type' => $schedule->activity_type,
                    'min_capacity' => $schedule->min_capacity,
                    'max_capacity' => $schedule->max_capacity,
                    'title' => $assignment?->title,
                    'description' => $assignment?->description,
                    'location' => $assignment?->location ?? 'Main Campus',
                    'resources' => $assignment?->resources,
                    'notes' => $assignment?->notes,
                ];
            })->toArray();
        }

        // Use CreateAvailableCourseService to create the clone
        $createService = app(CreateAvailableCourseService::class);
        $clonedCourse = $createService->createAvailableCourseSingle($cloneData);

        \Log::info('Available course cloned and updated successfully', [
            'source_id' => $sourceAvailableCourseId,
            'cloned_id' => $clonedCourse->id
        ]);

        return $clonedCourse;
    }
}