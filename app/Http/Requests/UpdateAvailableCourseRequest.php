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
            'mode'                            => 'required|string|in:individual,all_programs,all_levels,universal',
            'schedule_details'                            => 'required|array|min:1',
            'schedule_details.*.schedule_id'              => 'required|exists:schedules,id',
            'schedule_details.*.activity_type'            => 'required|string|in:lecture,tutorial,lab',
            'schedule_details.*.schedule_day_id'          => 'required',
            'schedule_details.*.schedule_slot_ids'   => 'required|array|min:1',
            'schedule_details.*.schedule_slot_ids.*' => 'required|exists:schedule_slots,id',
            // groups per schedule row as multi-select
            'schedule_details.*.group_numbers'             => 'required|array|min:1',
            'schedule_details.*.group_numbers.*'           => 'required|integer|min:1',
            'schedule_details.*.min_capacity'             => 'required|integer|min:1',
            'schedule_details.*.max_capacity'             => 'required|integer|gte:schedule_details.*.min_capacity',
            'schedule_details.*.schedule_assignment_id'   => 'nullable|integer|exists:schedule_assignments,id',
            'schedule_details.*.location'                 => 'nullable|string|max:255',
        ];

        switch ($eligibilityMode) {
            case 'individual':
                $rules['eligibility'] = 'required|array|min:1';
                $rules['eligibility.*.program_id'] = 'required|exists:programs,id';
                $rules['eligibility.*.level_id'] = 'required|exists:levels,id';
                $rules['eligibility.*.group'] = 'required|string|max:255';
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
            // Validate consecutive slots for each schedule detail
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
        });
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
            'eligibility.*.group.required' => 'Please enter a group number for each eligibility row.',

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
            'schedule_details.required' => 'Please add at least one schedule detail row.',
            'schedule_details.array' => 'Schedule details must be an array.',
            'schedule_details.min' => 'Please add at least one schedule detail row.',
            'schedule_details.*.schedule_id.required' => 'Please select a schedule for each row.',
            'schedule_details.*.schedule_id.exists' => 'The selected schedule does not exist.',
            'schedule_details.*.activity_type.required' => 'Please select an activity type (lecture, tutorial, or lab) for each schedule row.',
            'schedule_details.*.activity_type.in' => 'The selected activity type must be one of: lecture, tutorial, or lab.',
            'schedule_details.*.schedule_day_id.required' => 'Please select a day for each schedule row.',
            'schedule_details.*.schedule_slot_ids.required' => 'Please select a slot for each schedule row.',
            'schedule_details.*.schedule_slot_ids.*.exists' => 'The selected slot does not exist.',
            'schedule_details.*.group_numbers.required' => 'Please select at least one group for each schedule row.',
            'schedule_details.*.group_numbers.array' => 'Group selection must be an array.',
            'schedule_details.*.group_numbers.*.integer' => 'Each selected group must be a valid number.',
            'schedule_details.*.min_capacity.required' => 'Please enter a minimum capacity for each schedule row.',
            'schedule_details.*.min_capacity.integer' => 'Minimum capacity must be a valid number.',
            'schedule_details.*.min_capacity.min' => 'Minimum capacity must be at least 1.',
            'schedule_details.*.max_capacity.required' => 'Please enter a maximum capacity for each schedule row.',
            'schedule_details.*.max_capacity.integer' => 'Maximum capacity must be a valid number.',
            'schedule_details.*.max_capacity.gte' => 'Maximum capacity must be greater than or equal to minimum capacity.',
            'schedule_details.*.schedule_assignment_id.exists' => 'The selected schedule assignment does not exist.',
            'schedule_details.*.schedule_assignment_id.integer' => 'Schedule assignment ID must be a valid number.',
        ];
    }
}