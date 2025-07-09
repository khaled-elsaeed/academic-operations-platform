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
            'name_en'        => 'required|max:255',
            'name_ar'        => 'required|max:255',
            'academic_id'    => 'required|max:50|unique:students,academic_id',
            'national_id'    => 'required|max:50',
            'academic_email' => 'required|email|unique:students,academic_email',
            'level'          => 'required|exists:levels,name',
            'cgpa'           => 'nullable|numeric|min:0|max:4',
            'gender'         => 'required|in:male,female',
            'program_name'     => 'required|exists:programs,name',
        ], [
            'name_en.required'        => 'English name is required.',
            'name_ar.required'        => 'Arabic name is required.',
            'academic_id.required'    => 'Academic ID is required.',
            'academic_id.unique'      => 'Academic ID must be unique.',
            'national_id.required'    => 'National ID is required.',
            'academic_email.required' => 'Academic email is required.',
            'academic_email.email'    => 'Academic email must be a valid email address.',
            'academic_email.unique'   => 'Academic email must be unique.',
            'level.required'          => 'Level is required.',
            'cgpa.numeric'            => 'CGPA must be a number.',
            'cgpa.min'                => 'CGPA cannot be less than 0.',
            'cgpa.max'                => 'CGPA cannot be more than 4.',
            'gender.required'         => 'Gender is required.',
            'gender.in'               => 'Gender must be either male or female.',
            'program_name.required'     => 'Program is required.',
            'program_name.exists'       => 'Program does not exist.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                "Row $rowNumber" => $validator->errors()->all(),
            ]);
        }
    }
}
