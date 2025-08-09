<?php

namespace App\Pipelines\AvailableCourse\Update;

use App\Exceptions\BusinessValidationException;
use App\Models\AvailableCourse;
use Closure;

class ValidateUpdateDataPipe
{
    /**
     * Handle the pipeline step for validating update data.
     *
     * @param array $data
     * @param Closure $next
     * @return mixed
     * @throws BusinessValidationException
     */
    public function handle(array $data, Closure $next)
    {
        \Log::info('Pipeline: Validating update data', [
            'available_course_id' => $data['available_course']->id,
            'fields_to_update' => array_keys($data['update_data'])
        ]);
        
        $this->validateUpdateData($data['update_data'], $data['available_course']);

        return $next($data);
    }

    /**
     * Validate update data.
     *
     * @param array $updateData
     * @param AvailableCourse $availableCourse
     * @throws BusinessValidationException
     */
    private function validateUpdateData(array $updateData, AvailableCourse $availableCourse): void
    {
        // Validate eligibility mode if provided
        if (isset($updateData['mode'])) {
            $validModes = ['universal', 'all_programs', 'all_levels', 'individual'];

            if (!in_array($updateData['mode'], $validModes)) {
                throw new BusinessValidationException('Invalid eligibility mode. Must be one of: ' . implode(', ', $validModes));
            }
        }

        // Get the eligibility mode (new or existing)
        $eligibilityMode = $updateData['mode'] ?? $availableCourse->mode;

        // Validate mode-specific requirements
        $this->validateEligibilityModeRequirements($updateData, $eligibilityMode);

        // Validate schedule details if provided
        if (isset($updateData['schedule_details']) && is_array($updateData['schedule_details'])) {
            $this->validateScheduleDetails($updateData['schedule_details']);
        }

        // Validate regular details if provided
        if (isset($updateData['details']) && is_array($updateData['details'])) {
            $this->validateCourseDetails($updateData['details']);
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
            $hasSlotInfo = !empty($detail['schedule_slot_id']) || 
                          (!empty($detail['schedule_slot_ids']) && is_array($detail['schedule_slot_ids'])) ||
                          isset($detail['id']) || 
                          isset($detail['schedule_assignment_id']);
                          
            if (!$hasSlotInfo) {
                throw new BusinessValidationException("Schedule slot ID(s) are required for new schedule detail at index {$index}.");
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
}
