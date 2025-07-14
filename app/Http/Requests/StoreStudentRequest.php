<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
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
            'name_en' => 'required|string|min:2|max:255',
            'name_ar' => 'nullable|string|min:2|max:255',
            'academic_id' => 'required|string|max:50|unique:students,academic_id',
            'national_id' => 'required|string|max:50|unique:students,national_id',
            'academic_email' => 'required|email|unique:students,academic_email',
            'level_id' => 'required|exists:levels,id',
            'cgpa' => ['required','numeric','min:0','max:4', 'regex:/^\d{1}(\.\d{1,3})?$/'],
            'program_id' => 'required|exists:programs,id',
            'gender' => 'required|in:male,female',
        ];
    }
}
