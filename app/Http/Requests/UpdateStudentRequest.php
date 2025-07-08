<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
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
        $studentId = $this->route('student')?->id;
        return [
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'academic_id' => 'required|string|max:50|unique:students,academic_id,' . $studentId,
            'national_id' => 'required|string|max:50|unique:students,national_id,' . $studentId,
            'academic_email' => 'required|email|unique:students,academic_email,' . $studentId,
            'level' => 'required|string|max:50',
            'cgpa' => 'required|numeric|min:0|max:4',
            'program_id' => 'required|exists:programs,id',
        ];
    }
}
