<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\AcademicAdvisorAccess;

class AcademicAdvisorAccessRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = Auth::user();

        // If user is not authenticated, fail validation
        if (!$user) {
            $fail('You do not have permission to access this student. Please contact your administrator if you believe this is an error.');
            return;
        }

        // If user is not an academic advisor, allow access (admin or other roles)
        if (!$user->isAcademicAdvisor()) {
            return;
        }

        // Find the student
        $student = Student::find($value);
        if (!$student) {
            $fail('You do not have permission to access this student. Please contact your administrator if you believe this is an error.');
            return;
        }

        // Check if advisor has access to this student's level and program
        $hasAccess = AcademicAdvisorAccess::where('advisor_id', $user->id)
            ->where('is_active', true)
            ->where(function ($query) use ($student) {
                $query->where(function ($subQuery) use ($student) {
                    // Check if advisor has access to this specific level and program
                    $subQuery->where('level_id', $student->level_id)
                             ->where('program_id', $student->program_id);
                })->orWhere(function ($subQuery) use ($student) {
                    // Check if advisor has access to this level and ALL programs
                    $subQuery->where('level_id', $student->level_id)
                             ->whereNull('program_id');
                })->orWhere(function ($subQuery) use ($student) {
                    // Check if advisor has access to ALL levels and this program
                    $subQuery->whereNull('level_id')
                             ->where('program_id', $student->program_id);
                })->orWhere(function ($subQuery) use ($student) {
                    // Check if advisor has access to ALL levels and ALL programs
                    $subQuery->whereNull('level_id')
                             ->whereNull('program_id');
                });
            })
            ->exists();

        if (!$hasAccess) {
            $fail('You do not have permission to access this student. Please contact your administrator if you believe this is an error.');
        }
    }
}
