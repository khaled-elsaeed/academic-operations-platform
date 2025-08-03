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

class CreateAvailableCourseService
{
    /**
     * Create a single available course with eligibility mode support.
     *
     * @param array $data
     * @return AvailableCourse
     * @throws BusinessValidationException
     */
    public function createAvailableCourseSingle(array $data): AvailableCourse
    {
        \Log::info('Starting creation of available course', ['data' => $data]);
        $this->validateAvailableCourseData($data);
        $this->ensureAvailableCourseDoesNotExist($data);

        $result = DB::transaction(function () use ($data) {
            \Log::info('Creating available course record', ['course_id' => $data['course_id'], 'term_id' => $data['term_id']]);
            $availableCourse = $this->createAvailableCourseRecord($data);

            if (isset($data['schedule_details']) && is_array($data['schedule_details'])) {
                \Log::info('Creating course details from schedule', ['available_course_id' => $availableCourse->id]);
                $this->createCourseDetailsFromSchedule($availableCourse, $data['schedule_details']);
            }

            \Log::info('Handling eligibility for available course', ['available_course_id' => $availableCourse->id]);
            $this->handleEligibility($availableCourse, $data);

            return $availableCourse->fresh(['programs', 'levels', 'schedules']);
        });

        \Log::info('Available course created successfully', ['available_course_id' => $result->id]);
        return $result;
    }

    /**
     * Validate available course data.
     *
     * @param array $data
     * @throws BusinessValidationException
     */
    private function validateAvailableCourseData(array $data): void
    {
        // Validate required fields
        if (empty($data['course_id'])) {
            throw new BusinessValidationException('Course ID is required.');
        }

        if (empty($data['term_id'])) {
            throw new BusinessValidationException('Term ID is required.');
        }

        // Validate eligibility mode
        $validModes = ['universal', 'all_programs', 'all_levels', 'individual'];
        $eligibilityMode = $data['eligibility_mode'] ?? 'individual';
        
        if (!in_array($eligibilityMode, $validModes)) {
            throw new BusinessValidationException('Invalid eligibility mode. Must be one of: ' . implode(', ', $validModes));
        }

        // Validate mode-specific requirements
        $this->validateEligibilityModeRequirements($data, $eligibilityMode);

        // Validate min/max capacity for schedule details
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
                    if (empty($pair['program_id']) || empty($pair['level_id'])) {
                        throw new BusinessValidationException('Each eligibility pair must have both program_id and level_id.');
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
            // Validate required fields
            if (empty($detail['schedule_slot_id'])) {
                throw new BusinessValidationException("Schedule slot ID is required for schedule detail at index {$index}.");
            }

            if (empty($detail['group_number'])) {
                throw new BusinessValidationException("Group number is required for schedule detail at index {$index}.");
            }

            if (empty($detail['activity_type'])) {
                throw new BusinessValidationException("Activity type is required for schedule detail at index {$index}.");
            }

            // Validate capacity
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

    /**
     * Validate course details.
     *
     * @param array $details
     * @throws BusinessValidationException
     */
    private function validateCourseDetails(array $details): void
    {
        foreach ($details as $index => $detail) {
            if (empty($detail['group'])) {
                throw new BusinessValidationException("Group is required for course detail at index {$index}.");
            }

            if (empty($detail['activity_type'])) {
                throw new BusinessValidationException("Activity type is required for course detail at index {$index}.");
            }

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

    /**
     * Ensure an available course does not already exist.
     *
     * @param array $data
     * @param int|null $excludeId
     * @throws BusinessValidationException
     */
    private function ensureAvailableCourseDoesNotExist(array $data, int $excludeId = null): void
    {
        $eligibilityMode = $data['eligibility_mode'] ?? 'individual';
        
        if ($eligibilityMode === 'universal') {
            if ($this->universalAvailableCourseExists($data, $excludeId)) {
                throw new BusinessValidationException('A universal available course for this Course and Term already exists.');
            }
        } else {
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
        $query = AvailableCourse::where('course_id', $data['course_id'])
            ->where('term_id', $data['term_id'])
            ->where('mode', 'universal');

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
        $eligibilityMode = $data['eligibility_mode'] ?? 'individual';
        $courseId = $data['course_id'];
        $termId = $data['term_id'];

        switch ($eligibilityMode) {
            case 'all_programs':
                return $this->checkAllProgramsConflict($courseId, $termId, $data['level_id'], $excludeId);

            case 'all_levels':
                return $this->checkAllLevelsConflict($courseId, $termId, $data['program_id'], $excludeId);

            case 'individual':
            default:
                return $this->checkIndividualEligibilityConflicts($courseId, $termId, $data['eligibility'] ?? [], $excludeId);
        }
    }

    /**
     * Check for conflicts with all_programs eligibility mode.
     *
     * @param int $courseId
     * @param int $termId
     * @param int $levelId
     * @param int|null $excludeId
     * @return bool
     */
    private function checkAllProgramsConflict(int $courseId, int $termId, int $levelId, int $excludeId = null): bool
    {
        $query = AvailableCourse::where('course_id', $courseId)
            ->where('term_id', $termId)
            ->where(function ($q) use ($levelId) {
                // Check for exact same all_programs + level combination
                $q->where('mode', 'all_programs')
                  ->whereHas('eligibilities', function ($eq) use ($levelId) {
                      $eq->where('level_id', $levelId);
                  });
                // Or check for individual eligibilities that would conflict
                $q->orWhereHas('eligibilities', function ($eq) use ($levelId) {
                    $eq->where('level_id', $levelId);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check for conflicts with all_levels eligibility mode.
     *
     * @param int $courseId
     * @param int $termId
     * @param int $programId
     * @param int|null $excludeId
     * @return bool
     */
    private function checkAllLevelsConflict(int $courseId, int $termId, int $programId, int $excludeId = null): bool
    {
        $query = AvailableCourse::where('course_id', $courseId)
            ->where('term_id', $termId)
            ->where(function ($q) use ($programId) {
                // Check for exact same all_levels + program combination
                $q->where('mode', 'all_levels')
                  ->whereHas('eligibilities', function ($eq) use ($programId) {
                      $eq->where('program_id', $programId);
                  });
                // Or check for individual eligibilities that would conflict
                $q->orWhereHas('eligibilities', function ($eq) use ($programId) {
                    $eq->where('program_id', $programId);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check for conflicts with individual eligibility pairs.
     *
     * @param int $courseId
     * @param int $termId
     * @param array $eligibility
     * @param int|null $excludeId
     * @return bool
     */
    private function checkIndividualEligibilityConflicts(int $courseId, int $termId, array $eligibility, int $excludeId = null): bool
    {
        foreach ($eligibility as $pair) {
            $programId = $pair['program_id'];
            $levelId = $pair['level_id'];
            
            $query = AvailableCourse::where('course_id', $courseId)
                ->where('term_id', $termId)
                ->whereHas('eligibilities', function ($q) use ($programId, $levelId) {
                    $q->where('program_id', $programId)->where('level_id', $levelId);
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
     * Create the AvailableCourse record.
     *
     * @param array $data
     * @return AvailableCourse
     */
    private function createAvailableCourseRecord(array $data): AvailableCourse
    {
        return AvailableCourse::create([
            'course_id' => $data['course_id'],
            'term_id' => $data['term_id'],
            'mode' => $data['eligibility_mode'] ?? 'individual',
        ]);
    }

    /**
     * Handle eligibility creation based on eligibility mode.
     *
     * @param AvailableCourse $availableCourse
     * @param array $data
     * @return void
     */
    private function handleEligibility(AvailableCourse $availableCourse, array $data): void
    {
        $eligibilityMode = $data['eligibility_mode'] ?? 'individual';

        switch ($eligibilityMode) {
            case 'universal':
                // Universal courses have no eligibility restrictions
                break;

            case 'all_programs':
                $this->createAllProgramsEligibility($availableCourse, $data['level_id']);
                break;

            case 'all_levels':
                $this->createAllLevelsEligibility($availableCourse, $data['program_id']);
                break;

            case 'individual':
            default:
                $eligibility = $data['eligibility'] ?? [];
                $this->attachEligibilities($availableCourse, $eligibility);
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
     * Create course details from schedule details for an available course.
     *
     * @param AvailableCourse $availableCourse
     * @param array $scheduleDetails
     * @return void
     */
    private function createCourseDetailsFromSchedule(AvailableCourse $availableCourse, array $scheduleDetails): void
    {
        foreach ($scheduleDetails as $detail) {
            $courseDetail = $this->createAvailableCourseSchedule($availableCourse, $detail);
            $this->createScheduleAssignmentForDetail($courseDetail, $detail);
        }
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
            'group' => $detail['group_number'],
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
        $group = $detail['group_number'];
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
}