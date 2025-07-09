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
        $isUniversal = $this->input('is_universal', false);

        $rules = [
            'course_id'    => 'required|exists:courses,id',
            'term_id'      => 'required|exists:terms,id',
            'min_capacity' => 'required|integer|min:1',
            'max_capacity' => 'required|integer|gte:min_capacity',
            'is_universal' => 'boolean',
        ];

        if (!$isUniversal) {
            $rules['eligibility'] = 'required|array|min:1';
            $rules['eligibility.*.program_id'] = 'required|exists:programs,id';
            $rules['eligibility.*.level_id'] = 'required|exists:levels,id';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'eligibility.required' => 'At least one eligibility (program/level pair) is required unless universal is checked.',
            'eligibility.*.program_id.required' => 'Program is required for each eligibility row.',
            'eligibility.*.level_id.required' => 'Level is required for each eligibility row.',
        ];
    }

    public function prepareForValidation()
    {
        // If JSON input with program_ids and levels arrays, merge into eligibility array for validation
        $isUniversal = $this->input('is_universal', false);
        if (!$isUniversal && $this->has('program_ids') && $this->has('levels')) {
            $programIds = $this->input('program_ids', []);
            $levels = $this->input('levels', []);
            $eligibility = [];
            $count = min(count($programIds), count($levels));
            for ($i = 0; $i < $count; $i++) {
                $eligibility[] = [
                    'program_id' => $programIds[$i],
                    'level_id' => $levels[$i],
                ];
            }
            $this->merge([
                'eligibility' => $eligibility
            ]);
        }
    }
} 