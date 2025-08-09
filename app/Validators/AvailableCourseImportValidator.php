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
            'course_code'    => 'required|exists:courses,code',
            'term_code'       => 'required|exists:terms,code',
            'activity_type'  => 'nullable|string',
            'group'          => 'nullable|integer|min:1',
            'day'            => 'nullable|string',
            'slot'           => 'nullable',
            'location'       => 'nullable|string',
            'level_name'     => 'nullable|exists:levels,name',
            'program_code'   => 'nullable|exists:programs,code',
        ], [
            'course_code.required'   => 'Course code is required.',
            'course_code.exists'     => 'Course code does not exist.',
            'term_code.required'     => 'Term is required. Please provide a valid term code.',
            'term_code.exists'       => 'Term code does not exist.',
            'group.integer'          => 'Grouping must be an integer.',
            'group.min'              => 'Grouping must be at least 1.',
            'program_code.exists'    => 'Program does not exist.',
            'level_name.exists'      => 'Level does not exist.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                "Row $rowNumber" => $validator->errors()->all(),
            ]);
        }
    }
} 