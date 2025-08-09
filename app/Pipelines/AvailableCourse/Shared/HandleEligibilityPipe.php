<?php

namespace App\Pipelines\AvailableCourse\Shared;

use App\Models\Program;
use App\Models\Level;
use Closure;

class HandleEligibilityPipe
{
    /**
     * Handle the pipeline step for managing course eligibility.
     *
     * @param array $data
     * @param Closure $next
     * @return mixed
     */
    public function handle(array $data, Closure $next)
    {
        $availableCourse = $data['available_course'];
        $eligibilityMode = $data['mode'] ?? $data['update_data']['mode'] ?? $data['mode'] ?? 'individual';
        $operation = isset($data['update_data']) ? 'update' : 'create';

        \Log::info('Pipeline: Handling eligibility for available course', [
            'available_course_id' => $availableCourse->id,
            'mode' => $eligibilityMode,
            'operation' => $operation
        ]);

        if ($operation === 'update') {
            $this->handleEligibilityUpdate($availableCourse, $data);
        } else {
            $this->handleEligibilityCreate($availableCourse, $data);
        }

        return $next($data);
    }

    /**
     * Handle eligibility creation for new courses.
     *
     * @param \App\Models\AvailableCourse $availableCourse
     * @param array $data
     * @return void
     */
    private function handleEligibilityCreate($availableCourse, array $data): void
    {
        $eligibilityMode = $data['mode'] ?? 'individual';

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
     * Handle eligibility updates for existing courses.
     *
     * @param \App\Models\AvailableCourse $availableCourse
     * @param array $data
     * @return void
     */
    private function handleEligibilityUpdate($availableCourse, array $data): void
    {
        $updateData = $data['update_data'];
        $eligibilityMode = $updateData['mode'] ?? $availableCourse->mode;

        // Clear existing eligibilities if mode is changing or if explicitly updating eligibilities
        $shouldClearEligibilities = isset($updateData['mode']) ||
                                   isset($updateData['eligibility']) ||
                                   isset($updateData['program_id']) ||
                                   isset($updateData['level_id']);

        if ($shouldClearEligibilities) {
            $availableCourse->clearProgramLevelPairs();
        }

        switch ($eligibilityMode) {
            case 'universal':
                // Universal courses have no eligibility restrictions
                break;

            case 'all_programs':
                if (isset($updateData['level_id'])) {
                    $this->createAllProgramsEligibility($availableCourse, $updateData['level_id']);
                }
                break;

            case 'all_levels':
                if (isset($updateData['program_id'])) {
                    $this->createAllLevelsEligibility($availableCourse, $updateData['program_id']);
                }
                break;

            case 'individual':
                if (isset($updateData['eligibility'])) {
                    $this->attachEligibilities($availableCourse, $updateData['eligibility']);
                }
                break;
        }
    }

    /**
     * Create eligibility for all programs with a specific level.
     *
     * @param \App\Models\AvailableCourse $availableCourse
     * @param int $levelId
     * @return void
     */
    private function createAllProgramsEligibility($availableCourse, int $levelId): void
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
     * @param \App\Models\AvailableCourse $availableCourse
     * @param int $programId
     * @return void
     */
    private function createAllLevelsEligibility($availableCourse, int $programId): void
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
     * @param \App\Models\AvailableCourse $availableCourse
     * @param array $eligibility
     * @return void
     */
    private function attachEligibilities($availableCourse, array $eligibility): void
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
}
