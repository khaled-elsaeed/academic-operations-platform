<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvailableCourseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $eligibilityMode = $this->input('mode', 'individual');

        $rules = [
            'course_id'                                   => 'required|exists:courses,id',
            'term_id'                                     => 'required|exists:terms,id',
            'mode'                                        => 'required|string|in:individual,all_programs,all_levels,universal',
            'schedule_details'                            => 'required|array|min:1',
            'schedule_details.*.schedule_id'              => 'required|exists:schedules,id',
            'schedule_details.*.activity_type'            => 'required|string|in:lecture,tutorial,lab',
            'schedule_details.*.schedule_day_id'          => 'required',
            'schedule_details.*.schedule_slot_ids'        => 'required|array|min:1',
            'schedule_details.*.schedule_slot_ids.*'      => 'required|exists:schedule_slots,id',
            'schedule_details.*.group_number'             => 'required|integer|min:1|max:30',
            'schedule_details.*.min_capacity'             => 'required|integer|min:1',
            'schedule_details.*.max_capacity'             => 'required|integer|min:1',
            'schedule_details.*.schedule_assignment_id'   => 'nullable|integer|exists:schedule_assignments,id',
            'schedule_details.*.available_course_schedule_id' => 'nullable|integer',
            'schedule_details.*.location'                 => 'nullable|string|max:255',
        ];

        switch ($eligibilityMode) {
            case 'individual':
                $rules['eligibility'] = 'required|array|min:1';
                $rules['eligibility.*.program_id'] = 'required|exists:programs,id';
                $rules['eligibility.*.level_id'] = 'required|exists:levels,id';
                $rules['eligibility.*.group_ids'] = 'required|array|min:1';
                $rules['eligibility.*.group_ids.*'] = 'required|integer|min:1|max:30';
                $rules['level_id'] = 'prohibited';
                $rules['program_id'] = 'prohibited';
                break;

            case 'all_programs':
                $rules['level_id'] = 'required|exists:levels,id';
                $rules['eligibility'] = 'prohibited';
                $rules['program_id'] = 'prohibited';
                break;

            case 'all_levels':
                $rules['program_id'] = 'required|exists:programs,id';
                $rules['eligibility'] = 'prohibited';
                $rules['level_id'] = 'prohibited';
                break;

            case 'universal':
                $rules['eligibility'] = 'prohibited';
                $rules['level_id'] = 'prohibited';
                $rules['program_id'] = 'prohibited';
                break;
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateConsecutiveSlots($validator);
            $this->validateCapacity($validator);
        });
    }

    protected function validateConsecutiveSlots($validator)
    {
        $scheduleDetails = $this->input('schedule_details', []);
        
        foreach ($scheduleDetails as $index => $detail) {
            if (!isset($detail['schedule_slot_ids']) || !is_array($detail['schedule_slot_ids'])) {
                continue;
            }
            
            $slotIds = $detail['schedule_slot_ids'];
            
            if (count($slotIds) > 1) {
                // Get slot orders from database
                $slots = \App\Models\Schedule\ScheduleSlot::whereIn('id', $slotIds)
                    ->orderBy('slot_order')
                    ->pluck('slot_order')
                    ->toArray();
                
                // Check if slots are consecutive
                for ($i = 1; $i < count($slots); $i++) {
                    if ($slots[$i] !== $slots[$i-1] + 1) {
                        $validator->errors()->add(
                            "schedule_details.{$index}.schedule_slot_ids",
                            "Selected slots must be consecutive in schedule detail " . ($index + 1) . "."
                        );
                        break;
                    }
                }
            }
        }
    }

    protected function validateCapacity($validator)
    {
        $scheduleDetails = $this->input('schedule_details', []);
        
        foreach ($scheduleDetails as $index => $detail) {
            if (isset($detail['min_capacity']) && isset($detail['max_capacity'])) {
                $min = (int) $detail['min_capacity'];
                $max = (int) $detail['max_capacity'];

                if ($max < $min) {
                    $validator->errors()->add(
                        "schedule_details.{$index}.max_capacity",
                        "Maximum capacity must be greater than or equal to minimum capacity for schedule detail " . ($index + 1) . "."
                    );
                }
            }
        }
    }

    public function messages()
    {
        return [
            // General
            'course_id.required' => 'Please select a course.',
            'course_id.exists' => 'The selected course does not exist.',
            'term_id.required' => 'Please select a term.',
            'term_id.exists' => 'The selected term does not exist.',
            'mode.required' => 'Please select an eligibility mode.',
            'mode.in' => 'Invalid eligibility mode selected.',

            // Eligibility (individual)
            'eligibility.required' => 'Please add at least one eligibility pair (program and level).',
            'eligibility.array' => 'Eligibility must be an array of program and level pairs.',
            'eligibility.min' => 'Please add at least one eligibility pair (program and level).',
            'eligibility.*.program_id.required' => 'Please select a program for each eligibility row.',
            'eligibility.*.program_id.exists' => 'The selected program does not exist.',
            'eligibility.*.level_id.required' => 'Please select a level for each eligibility row.',
            'eligibility.*.level_id.exists' => 'The selected level does not exist.',
            'eligibility.*.group_ids.required' => 'Please select at least one group for each eligibility row.',
            'eligibility.*.group_ids.array' => 'Group selection must be an array.',
            'eligibility.*.group_ids.*.required' => 'Please select at least one group for each eligibility row.',
            'eligibility.*.group_ids.*.integer' => 'Each group must be a valid number.',
            'eligibility.*.group_ids.*.min' => 'Group numbers must be at least 1.',
            'eligibility.*.group_ids.*.max' => 'Group numbers cannot be greater than 30.',

            // Eligibility (other modes)
            'level_id.required' => 'Please select a level for "All Programs" mode.',
            'level_id.exists' => 'The selected level does not exist.',
            'program_id.required' => 'Please select a program for "All Levels" mode.',
            'program_id.exists' => 'The selected program does not exist.',

            // Prohibited fields
            'eligibility.prohibited' => 'Eligibility criteria should not be provided for this mode.',
            'level_id.prohibited' => 'Level should not be provided for this mode.',
            'program_id.prohibited' => 'Program should not be provided for this mode.',

            // Schedule details
            'schedule_details.required' => 'Please add at least one schedule detail.',
            'schedule_details.array' => 'Schedule details must be an array.',
            'schedule_details.min' => 'Please add at least one schedule detail.',
            'schedule_details.*.schedule_id.required' => 'Please select a schedule for each row.',
            'schedule_details.*.schedule_id.exists' => 'The selected schedule does not exist.',
            'schedule_details.*.activity_type.required' => 'Please select an activity type (lecture, tutorial, or lab) for each schedule row.',
            'schedule_details.*.activity_type.in' => 'The selected activity type must be one of: lecture, tutorial, or lab.',
            'schedule_details.*.schedule_day_id.required' => 'Please select a day for each schedule row.',
            'schedule_details.*.schedule_slot_ids.required' => 'Please select at least one slot for each schedule row.',
            'schedule_details.*.schedule_slot_ids.array' => 'Slot selection must be an array.',
            'schedule_details.*.schedule_slot_ids.min' => 'Please select at least one slot for each schedule row.',
            'schedule_details.*.schedule_slot_ids.*.required' => 'Each selected slot must be valid.',
            'schedule_details.*.schedule_slot_ids.*.exists' => 'One or more selected slots do not exist.',
            'schedule_details.*.group_number.required' => 'Please specify a group number for each schedule row.',
            'schedule_details.*.group_number.integer' => 'Group number must be a valid integer.',
            'schedule_details.*.group_number.min' => 'Group number must be at least 1.',
            'schedule_details.*.group_number.max' => 'Group number cannot be greater than 30.',
            'schedule_details.*.min_capacity.required' => 'Please enter a minimum capacity for each schedule row.',
            'schedule_details.*.min_capacity.integer' => 'Minimum capacity must be a valid number.',
            'schedule_details.*.min_capacity.min' => 'Minimum capacity must be at least 1.',
            'schedule_details.*.max_capacity.required' => 'Please enter a maximum capacity for each schedule row.',
            'schedule_details.*.max_capacity.integer' => 'Maximum capacity must be a valid number.',
            'schedule_details.*.max_capacity.min' => 'Maximum capacity must be at least 1.',
            'schedule_details.*.schedule_assignment_id.exists' => 'The selected schedule assignment does not exist.',
            'schedule_details.*.schedule_assignment_id.integer' => 'Schedule assignment ID must be a valid number.',
            'schedule_details.*.available_course_schedule_id.integer' => 'Available course schedule ID must be a valid number.',
            'schedule_details.*.location.required' => 'Please enter a location for each schedule row.',
            'schedule_details.*.location.string' => 'Location must be a valid string.',
            'schedule_details.*.location.max' => 'Location must not exceed 255 characters.',
        ];
    }
}