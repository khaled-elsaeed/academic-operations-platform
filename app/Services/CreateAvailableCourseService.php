<?php

namespace App\Services;

use App\Models\AvailableCourse;
use App\Models\AvailableCourseDetail;
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
        $this->validateAvailableCourseData($data);
        $this->ensureAvailableCourseDoesNotExist($data);

        return DB::transaction(function () use ($data) {
            $isUniversal = $data['is_universal'] ?? false;
            $eligibilityMode = $data['eligibility_mode'] ?? 'individual';
            $availableCourse = $this->createAvailableCourseRecord($data);

            // Create course details if provided
            if (isset($data['details']) && is_array($data['details'])) {
                $this->createCourseDetails($availableCourse, $data['details']);
            }

            if (!$isUniversal && $eligibilityMode !== 'universal') {
                $eligibility = $data['eligibility'] ?? [];
                if ($eligibilityMode === 'all_programs') {
                    // All programs for a specific level
                    $levelId = $eligibility[0]['level_id'] ?? null;
                    $allPrograms = Program::pluck('id')->toArray();
                    $bulkEligibility = [];
                    foreach ($allPrograms as $pid) {
                        $bulkEligibility[] = ['program_id' => $pid, 'level_id' => $levelId];
                    }
                    $this->attachEligibilities($availableCourse, $bulkEligibility);
                } elseif ($eligibilityMode === 'all_levels') {
                    // All levels for a specific program
                    $programId = $eligibility[0]['program_id'] ?? null;
                    $allLevels = Level::pluck('id')->toArray();
                    $bulkEligibility = [];
                    foreach ($allLevels as $lid) {
                        $bulkEligibility[] = ['program_id' => $programId, 'level_id' => $lid];
                    }
                    $this->attachEligibilities($availableCourse, $bulkEligibility);
                } else {
                    // Individual mode (custom pairs)
                    $this->attachEligibilities($availableCourse, $eligibility);
                }
            }
            return $availableCourse->fresh(['programs', 'levels', 'details']);
        });
    }

    /**
     * Validate available course data.
     *
     * @param array $data
     * @throws BusinessValidationException
     */
    private function validateAvailableCourseData(array $data): void
    {
        // Validate min/max capacity for each detail if present
        if (isset($data['details']) && is_array($data['details'])) {
            foreach ($data['details'] as $detail) {
                $minCapacity = $detail['min_capacity'] ?? 1;
                $maxCapacity = $detail['max_capacity'] ?? 30;
                if ($minCapacity > $maxCapacity) {
                    throw new BusinessValidationException('Minimum capacity cannot be greater than maximum capacity in course details.');
                }
                if ($minCapacity < 0 || $maxCapacity < 0) {
                    throw new BusinessValidationException('Capacity values cannot be negative in course details.');
                }
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
        $isUniversal = $data['is_universal'] ?? false;
        if ($isUniversal) {
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
            ->where('is_universal', true);
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
        $programIds = $data['program_ids'] ?? [];
        $levels = $data['levels'] ?? [];
        foreach ($programIds as $programId) {
            foreach ($levels as $level) {
                $query = AvailableCourse::where('course_id', $data['course_id'])
                    ->where('term_id', $data['term_id'])
                    ->whereHas('eligibilities', function ($q) use ($programId, $level) {
                        $q->where('program_id', $programId)->where('level_id', $level);
                    });
                if ($excludeId) {
                    $query->where('id', '!=', $excludeId);
                }
                if ($query->exists()) {
                    return true;
                }
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
            'is_universal' => $data['is_universal'] ?? false,
        ]);
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
            ->toArray();
        $availableCourse->setProgramLevelPairs($pairs);
    }

    /**
     * Create course details for an available course.
     *
     * @param AvailableCourse $availableCourse
     * @param array $details
     * @return void
     */
    private function createCourseDetails(AvailableCourse $availableCourse, array $details): void
    {
        foreach ($details as $detail) {
            AvailableCourseDetail::create([
                'available_course_id' => $availableCourse->id,
                'group' => $detail['group'] ?? 1,
                'activity_type' => strtolower($detail['activity_type'] ?? 'lecture'),
                'min_capacity' => $detail['min_capacity'] ?? 1,
                'max_capacity' => $detail['max_capacity'] ?? 30,
                'day' => $detail['day'] ?? null,
                'slot' => $detail['slot'] ?? null,
            ]);
        }
    }
}