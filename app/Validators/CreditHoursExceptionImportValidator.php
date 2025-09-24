<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreditHoursExceptionImportValidator
{
    /**
     * Validate a single row for credit hours exception import.
     *
     * Expected headings:
     * - academic_id
     * - term_code
     * - additional_hours
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
            'academic_id'      => 'required|exists:students,academic_id',
            'term_code'        => 'required|exists:terms,code',
            'additional_hours' => 'required|integer|min:1|max:12',
            'reason'           => 'nullable|string|max:500',
            'is_active'        => 'nullable|boolean',
        ], [
            'academic_id.required'      => 'Academic ID is required.',
            'academic_id.exists'        => 'Student with this academic ID does not exist.',
            'term_code.required'        => 'Term code is required.',
            'term_code.exists'          => 'Term code does not exist.',
            'additional_hours.required' => 'Additional hours is required.',
            'additional_hours.integer'  => 'Additional hours must be an integer.',
            'additional_hours.min'      => 'Additional hours must be at least 1.',
            'additional_hours.max'      => 'Additional hours may not be greater than 12.',
            'reason.max'                => 'Reason may not be greater than 500 characters.',
            'is_active.boolean'         => 'Is active must be true or false.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                "Row $rowNumber" => $validator->errors()->all(),
            ]);
        }
    }
}


