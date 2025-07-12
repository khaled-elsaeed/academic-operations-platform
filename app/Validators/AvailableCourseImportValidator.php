<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AvailableCourseImportValidator
{
    /**
     * Validate a single row for available course import.
     *
     * @param array $row
     * @param int $rowNumber
     * @throws ValidationException
     */
    public static function validateRow(array $row, int $rowNumber): void
    {
        $validator = Validator::make($row, [
            'course_code'   => 'required|exists:courses,code',
            'term_code'     => 'required',
            'program_name'  => 'required|exists:programs,name',
            'level_name'    => 'required|exists:levels,name',
            'min_capacity'  => 'nullable|integer|min:0',
            'max_capacity'  => 'nullable|integer|min:0',
        ], [
            'course_code.required'   => 'Course code is required.',
            'course_code.exists'     => 'Course code does not exist.',
            'term_code.required'     => 'Term code is required.',
            'program_name.required'  => 'Program name is required.',
            'program_name.exists'    => 'Program does not exist.',
            'level_name.required'    => 'Level name is required.',
            'level_name.exists'      => 'Level does not exist.',
            'min_capacity.integer'   => 'Min capacity must be an integer.',
            'min_capacity.min'       => 'Min capacity cannot be negative.',
            'max_capacity.integer'   => 'Max capacity must be an integer.',
            'max_capacity.min'       => 'Max capacity cannot be negative.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                "Row $rowNumber" => $validator->errors()->all(),
            ]);
        }
    }
} 