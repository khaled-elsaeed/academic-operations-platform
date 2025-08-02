<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAvailableCourseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $eligibilityMode = $this->input('eligibility_mode', 'individual');

        $rules = [
            'course_id'                              => 'required|exists:courses,id',
            'term_id'                                => 'required|exists:terms,id',
            'eligibility_mode'                       => 'required|string|in:individual,all_programs,all_levels,universal',
            'schedule_details'                       => 'required|array|min:1',
            'schedule_details.*.schedule_id'         => 'required|exists:schedules,id',
            'schedule_details.*.activity_type'       => 'required|string|in:lecture,tutorial,lab',
            'schedule_details.*.schedule_day_id'     => 'required',
            'schedule_details.*.schedule_slot_id'    => 'required|exists:schedule_slots,id',
            'schedule_details.*.group_number'        => 'required|integer|min:1',
            'schedule_details.*.min_capacity'        => 'required|integer|min:1',
            'schedule_details.*.max_capacity'        => 'required|integer|gte:schedule_details.*.min_capacity',
        ];

        switch ($eligibilityMode) {
            case 'individual':
                $rules['eligibility'] = 'required|array|min:1';
                $rules['eligibility.*.program_id'] = 'required|exists:programs,id';
                $rules['eligibility.*.level_id'] = 'required|exists:levels,id';
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

    public function messages()
    {
        return [
            // General
            'course_id.required' => 'Please select a course.',
            'course_id.exists' => 'The selected course does not exist.',
            'term_id.required' => 'Please select a term.',
            'term_id.exists' => 'The selected term does not exist.',
            'eligibility_mode.required' => 'Please select an eligibility mode.',
            'eligibility_mode.in' => 'Invalid eligibility mode selected.',
            
            // Eligibility (individual)
            'eligibility.required' => 'Please add at least one eligibility pair (program and level).',
            'eligibility.array' => 'Eligibility must be an array of program and level pairs.',
            'eligibility.min' => 'Please add at least one eligibility pair (program and level).',
            'eligibility.*.program_id.required' => 'Please select a program for each eligibility row.',
            'eligibility.*.program_id.exists' => 'The selected program does not exist.',
            'eligibility.*.level_id.required' => 'Please select a level for each eligibility row.',
            'eligibility.*.level_id.exists' => 'The selected level does not exist.',
            
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
            'schedule_details.*.schedule_slot_id.required' => 'Please select a slot for each schedule row.',
            'schedule_details.*.schedule_slot_id.exists' => 'The selected slot does not exist.',
            'schedule_details.*.group_number.required' => 'Please enter a group number for each schedule row.',
            'schedule_details.*.group_number.integer' => 'Group number must be a valid number.',
            'schedule_details.*.group_number.min' => 'Group number must be at least 1.',
            'schedule_details.*.min_capacity.required' => 'Please enter a minimum capacity for each schedule row.',
            'schedule_details.*.min_capacity.integer' => 'Minimum capacity must be a valid number.',
            'schedule_details.*.min_capacity.min' => 'Minimum capacity must be at least 1.',
            'schedule_details.*.max_capacity.required' => 'Please enter a maximum capacity for each schedule row.',
            'schedule_details.*.max_capacity.integer' => 'Maximum capacity must be a valid number.',
            'schedule_details.*.max_capacity.gte' => 'Maximum capacity must be greater than or equal to minimum capacity.',
        ];
    }
}