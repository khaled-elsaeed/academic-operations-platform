<?php

namespace App\Pipelines\AvailableCourse\Shared;

use App\Exceptions\BusinessValidationException;
use App\Models\AvailableCourse;
use Closure;

class CheckDuplicatesPipe
{
    /**
     * Handle the pipeline step for checking duplicate available courses.
     *
     * @param array $data
     * @param Closure $next
     * @return mixed
     * @throws BusinessValidationException
     */
    public function handle(array $data, Closure $next)
    {
        $courseId = $data['course_id'] ?? $data['update_data']['course_id'] ?? null;
        $termId = $data['term_id'] ?? $data['update_data']['term_id'] ?? null;
        $eligibilityMode = $data['mode'] ?? $data['update_data']['mode'] ?? $data['mode'] ?? 'individual';
        
        // For updates, get the ID to exclude from duplicate check
        $excludeId = isset($data['available_course']) ? $data['available_course']->id : null;

        \Log::info('Pipeline: Checking for duplicate available courses', [
            'course_id' => $courseId,
            'term_id' => $termId,
            'mode' => $eligibilityMode,
            'exclude_id' => $excludeId,
            'operation' => isset($data['available_course']) ? 'update' : 'create'
        ]);

        // Only check if relevant fields are being changed
        $needsConflictCheck = $this->shouldCheckForConflicts($data);
        
        if ($needsConflictCheck) {
            $this->ensureAvailableCourseDoesNotExist($data, $excludeId);
        }

        return $next($data);
    }

    /**
     * Determine if conflict checking is needed.
     *
     * @param array $data
     * @return bool
     */
    private function shouldCheckForConflicts(array $data): bool
    {
        // For create operations, always check
        if (!isset($data['available_course'])) {
            return true;
        }

        // For update operations, only check if relevant fields are being updated
        $updateData = $data['update_data'] ?? [];
        
        return isset($updateData['course_id']) ||
               isset($updateData['term_id']) ||
               isset($updateData['mode']) ||
               isset($updateData['level_id']) ||
               isset($updateData['program_id']) ||
               isset($updateData['eligibility']);
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
        $eligibilityMode = $data['mode'] ?? $data['update_data']['mode'] ?? $data['mode'] ?? 'individual';
        
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
        $courseId = $data['course_id'] ?? $data['update_data']['course_id'] ?? null;
        $termId = $data['term_id'] ?? $data['update_data']['term_id'] ?? null;
        
        $query = AvailableCourse::where('mode', 'universal');
        
        if ($courseId) {
            $query->where('course_id', $courseId);
        }
        
        if ($termId) {
            $query->where('term_id', $termId);
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
        $eligibilityMode = $data['mode'] ?? $data['update_data']['mode'] ?? $data['mode'] ?? 'individual';
        $courseId = $data['course_id'] ?? $data['update_data']['course_id'] ?? null;
        $termId = $data['term_id'] ?? $data['update_data']['term_id'] ?? null;

        switch ($eligibilityMode) {
            case 'all_programs':
                $levelId = $data['level_id'] ?? $data['update_data']['level_id'] ?? null;
                return $this->checkAllProgramsConflict($courseId, $termId, $levelId, $excludeId);

            case 'all_levels':
                $programId = $data['program_id'] ?? $data['update_data']['program_id'] ?? null;
                return $this->checkAllLevelsConflict($courseId, $termId, $programId, $excludeId);

            case 'individual':
            default:
                $eligibility = $data['eligibility'] ?? $data['update_data']['eligibility'] ?? [];
                return $this->checkIndividualEligibilityConflicts($courseId, $termId, $eligibility, $excludeId);
        }
    }

    /**
     * Check for conflicts with all_programs eligibility mode.
     *
     * @param int|null $courseId
     * @param int|null $termId
     * @param int|null $levelId
     * @param int|null $excludeId
     * @return bool
     */
    private function checkAllProgramsConflict(?int $courseId, ?int $termId, ?int $levelId, int $excludeId = null): bool
    {
        if (!$courseId || !$termId || !$levelId) {
            return false;
        }

        $query = AvailableCourse::where('course_id', $courseId)
            ->where('term_id', $termId)
            ->whereHas('eligibilities', function ($eq) use ($levelId) {
                $eq->where('level_id', $levelId);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check for conflicts with all_levels eligibility mode.
     *
     * @param int|null $courseId
     * @param int|null $termId
     * @param int|null $programId
     * @param int|null $excludeId
     * @return bool
     */
    private function checkAllLevelsConflict(?int $courseId, ?int $termId, ?int $programId, int $excludeId = null): bool
    {
        if (!$courseId || !$termId || !$programId) {
            return false;
        }

        $query = AvailableCourse::where('course_id', $courseId)
            ->where('term_id', $termId)
            ->whereHas('eligibilities', function ($eq) use ($programId) {
                $eq->where('program_id', $programId);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check for conflicts with individual eligibility pairs.
     *
     * @param int|null $courseId
     * @param int|null $termId
     * @param array $eligibility
     * @param int|null $excludeId
     * @return bool
     */
    private function checkIndividualEligibilityConflicts(?int $courseId, ?int $termId, array $eligibility, int $excludeId = null): bool
    {
        if (!$courseId || !$termId || empty($eligibility)) {
            return false;
        }

        foreach ($eligibility as $pair) {
            if (empty($pair['program_id']) || empty($pair['level_id'])) {
                continue;
            }

            $query = AvailableCourse::where('course_id', $courseId)
                ->where('term_id', $termId)
                ->whereHas('eligibilities', function ($q) use ($pair) {
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
}
