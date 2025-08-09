<?php

namespace App\Pipelines\AvailableCourse\Create;

use App\Exceptions\BusinessValidationException;
use App\Models\Schedule\ScheduleSlot;
use Closure;

class ValidateCreateDataPipe
{
    /**
     * Handle the pipeline step for validating available course data.
     *
     * @param array $data
     * @param Closure $next
     * @return mixed
     * @throws BusinessValidationException
     */
    public function handle(array $data, Closure $next)
    {
        \Log::info('Pipeline: Validating create data for available course', ['course_id' => $data['course_id'] ?? null]);
        
        $this->validateRequiredFields($data);
        $this->validateEligibilityMode($data);
        $this->validateEligibilityModeRequirements($data);
        $this->validateScheduleDetails($data);
        $this->validateCourseDetails($data);

        return $next($data);
    }

    /**
     * Validate required fields.
     *
     * @param array $data
     * @throws BusinessValidationException
     */
    private function validateRequiredFields(array $data): void
    {
        if (empty($data['course_id'])) {
            throw new BusinessValidationException('Course ID is required.');
        }

        if (empty($data['term_id'])) {
            throw new BusinessValidationException('Term ID is required.');
        }
    }

    /**
     * Validate eligibility mode.
     *
     * @param array $data
     * @throws BusinessValidationException
     */
    private function validateEligibilityMode(array $data): void
    {
        $eligibilityMode = $data['mode'] ?? 'individual';
        $validModes = ['universal', 'all_programs', 'all_levels', 'individual'];

        if (!in_array($eligibilityMode, $validModes)) {
            throw new BusinessValidationException('Invalid eligibility mode. Must be one of: ' . implode(', ', $validModes));
        }
    }

    /**
     * Validate eligibility mode specific requirements.
     *
     * @param array $data
     * @throws BusinessValidationException
     */
    private function validateEligibilityModeRequirements(array $data): void
    {
        $eligibilityMode = $data['mode'] ?? 'individual';

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
     * @param array $data
     * @throws BusinessValidationException
     */
    private function validateScheduleDetails(array $data): void
    {
        if (!isset($data['schedule_details']) || !is_array($data['schedule_details'])) {
            return;
        }

        foreach ($data['schedule_details'] as $index => $detail) {
            if (empty($detail['schedule_slot_id']) && 
                (empty($detail['schedule_slot_ids']) || !is_array($detail['schedule_slot_ids']))) {
                throw new BusinessValidationException("Schedule slot ID(s) are required for schedule detail at index {$index}.");
            }

            if (empty($detail['group_number'])) {
                throw new BusinessValidationException("Group number is required for schedule detail at index {$index}.");
            }

            if (empty($detail['activity_type'])) {
                throw new BusinessValidationException("Activity type is required for schedule detail at index {$index}.");
            }

            // Validate slot IDs exist
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

    /**
     * Validate course details.
     *
     * @param array $data
     * @throws BusinessValidationException
     */
    private function validateCourseDetails(array $data): void
    {
        if (!isset($data['details']) || !is_array($data['details'])) {
            return;
        }

        foreach ($data['details'] as $index => $detail) {
            if (empty($detail['group'])) {
                throw new BusinessValidationException("Group is required for course detail at index {$index}.");
            }

            if (empty($detail['activity_type'])) {
                throw new BusinessValidationException("Activity type is required for course detail at index {$index}.");
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
}
