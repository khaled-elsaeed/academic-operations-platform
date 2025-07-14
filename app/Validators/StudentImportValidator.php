<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StudentImportValidator
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {}

    public static function validateRow(array $row, int $rowNumber): void
    {
        $validator = Validator::make($row, [
            'name_en'        => 'required|min:2|max:255',
            'name_ar'        => [
                'nullable',
                function ($attribute, $value, $fail) {
                    // Allow null, empty string, or strings with at least 2 characters
                    if ($value !== null && $value !== '' && strlen(trim($value)) < 2) {
                        $fail('The Arabic name must be at least 2 characters long if provided.');
                    }
                },
                'max:255'
            ],
            'academic_id'    => 'required|max:50',
            // Accept either a valid Egyptian national ID (14 digits) or a passport number
            'national_id'    => [
                'required',
                'max:50',
                function ($attribute, $value, $fail) {
                    $value = (string)$value;
                    $isNationalId = preg_match('/^\d{14}$/', $value);
                    $isPassport = preg_match('/^(?=.*[A-Za-z])[A-Za-z0-9]{6,20}$/', $value);
                    if (!$isNationalId && !$isPassport) {
                        $fail('The :attribute must be a valid Egyptian national ID (14 digits) or a passport number (6-20 alphanumeric characters, at least one letter).');
                    }
                },
            ],
            'academic_email' => 'required|email',
            'level'          => 'required|exists:levels,name',
            'cgpa'           => 'nullable|numeric|min:0|max:4',
            'program_name'   => 'required|exists:programs,name',
        ], [
            'name_en.required'        => 'English name is required.',
            'academic_id.required'    => 'Academic ID is required.',
            'national_id.required'    => 'National ID is required.',
            'academic_email.required' => 'Academic email is required.',
            'academic_email.email'    => 'Academic email must be a valid email address.',
            'level.required'          => 'Level is required.',
            'level.exists'            => 'Level does not exist.',
            'cgpa.numeric'            => 'CGPA must be a number.',
            'cgpa.min'                => 'CGPA cannot be less than 0.',
            'cgpa.max'                => 'CGPA cannot be more than 4.',
            'program_name.required'   => 'Program is required.',
            'program_name.exists'     => 'Program does not exist.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                "Row $rowNumber" => $validator->errors()->all(),
            ]);
        }
    }
}
