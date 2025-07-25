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
        $isBulk = $this->isBulk();
        $isUniversal = $this->input('is_universal', false);
        $eligibilityMode = $this->input('eligibility_mode', 'individual');

        if ($isBulk) {
            return [
                'courses' => 'required|array|min:1',
                'courses.*.course_id' => 'required|exists:courses,id',
                'courses.*.term_id' => 'required|exists:terms,id',
                'courses.*.min_capacity' => 'required|integer|min:1',
                'courses.*.max_capacity' => 'required|integer|gte:courses.*.min_capacity',
                'courses.*.is_universal' => 'boolean',
                'courses.*.eligibility_mode' => 'required|string|in:individual,all_programs,all_levels,universal',
                'courses.*.eligibility' => 'array',
            ];
        }

        $rules = [
            'course_id'    => 'required|exists:courses,id',
            'term_id'      => 'required|exists:terms,id',
            'min_capacity' => 'required|integer|min:1',
            'max_capacity' => 'required|integer|gte:min_capacity',
            'is_universal' => 'boolean',
            'eligibility_mode' => 'required|string|in:individual,all_programs,all_levels,universal',
        ];

        if (!$isUniversal && $eligibilityMode !== 'universal') {
            $rules['eligibility'] = 'required|array|min:1';
            $rules['eligibility.*.program_id'] = 'required';
            $rules['eligibility.*.level_id'] = 'required';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'eligibility.required' => 'At least one eligibility (program/level pair) is required unless universal is checked.',
            'eligibility.*.program_id.required' => 'Program is required for each eligibility row.',
            'eligibility.*.level_id.required' => 'Level is required for each eligibility row.',
            'eligibility_mode.required' => 'Eligibility mode is required.',
        ];
    }

    public function prepareForValidation()
    {
        // If JSON input with program_ids and levels arrays, merge into eligibility array for validation
        $isUniversal = $this->input('is_universal', false);
        $isBulk = $this->isBulk();
        if ($isBulk) {
            // No transformation needed for bulk, handled in service
            return;
        }
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

    private function isBulk()
    {
        return $this->has('courses') && is_array($this->input('courses'));
    }
} 