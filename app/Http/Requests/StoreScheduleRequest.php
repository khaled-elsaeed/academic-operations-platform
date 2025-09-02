<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'schedule_type_id' => 'required|exists:schedule_types,id',
            'term_id' => 'required|exists:terms,id',
            'description' => 'nullable|string',
            'day_starts_at' => 'required|date_format:H:i',
            'day_ends_at' => 'required|date_format:H:i',
            'slot_duration_minutes' => 'required|int|min:1',
            'break_duration_minutes' => 'nullable|int|min:1',
            'pattern' => 'required|in:repetitive,range',
            'start_date' => 'nullable|required_if:pattern,range|date',
            'end_date' => 'nullable|required_if:pattern,range|date|after_or_equal:start_date',
            'days' => 'required_if:pattern,repetitive|array|min:1',
            'days.*' => 'required_if:pattern,repetitive|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
        ];
    }
}
