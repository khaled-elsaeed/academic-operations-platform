<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PrerequisiteExceptionImportValidator
{
    /**
     * Validate a single row for prerequisite exception import.
     *
     * Expected headings:
     * - academic_id
     * - course_code
     * - prerequisite_code
     * - term_code
     * - reason (optional)
     * - is_active (optional: 1/0, true/false)
     *
     * @param array $row
     * @param int $rowNumber
     * @throws ValidationException
     */
    public static function validateRow(array $row, int $rowNumber): void
    {
        $validator = Validator::make($row, [
            'academic_id'       => 'required|exists:students,academic_id',
            'course_code'       => 'required|exists:courses,code',
            'prerequisite_code' => 'required|exists:courses,code',
            'term_code'         => 'required|exists:terms,code',
            'reason'            => 'nullable|string|max:500',
            'is_active'         => 'nullable|boolean',
        ], [
            'academic_id.required'       => 'Academic ID is required.',
            'academic_id.exists'         => 'Student with this academic ID does not exist.',
            'course_code.required'       => 'Course code is required.',
            'course_code.exists'         => 'Course with this code does not exist.',
            'prerequisite_code.required' => 'Prerequisite code is required.',
            'prerequisite_code.exists'   => 'Prerequisite course with this code does not exist.',
            'term_code.required'         => 'Term code is required.',
            'term_code.exists'           => 'Term code does not exist.',
            'reason.max'                 => 'Reason may not be greater than 500 characters.',
            'is_active.boolean'          => 'Is active must be true or false.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                "Row $rowNumber" => $validator->errors()->all(),
            ]);
        }
    }
}
