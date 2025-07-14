<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EnrollmentImportValidator
{
    /**
     * Validate a single row for enrollment import.
     *
     * @param array $row
     * @param int $rowNumber
     * @throws ValidationException
     */
    public static function validateRow(array $row, int $rowNumber): void
    {
        $validator = Validator::make($row, [
            'academic_id' => 'required|exists:students,academic_id',
            'course_code'         => 'required|exists:courses,code',
            'term_code'           => 'required|exists:terms,code',
            'grade'               => 'nullable|in:A+,A,A-,B+,B,B-,C+,C,C-,D+,D,F,FL,FD,P,AU,W,I',
        ], [
            'academic_id.required' => 'Academic ID is required.',
            'academic_id.exists'   => 'Student with this academic ID does not exist.',
            'course_code.required'         => 'Course code is required.',
            'course_code.exists'           => 'Course code does not exist.',
            'term_code.required'           => 'Term code is required.',
            'term_code.exists'             => 'Term code does not exist.',
            'grade.in'                     => 'Grade must be one of: A+, A, A-, B+, B, B-, C+, C, C-, D+, D, F.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                "Row $rowNumber" => $validator->errors()->all(),
            ]);
        }
    }
} 