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

        if (!$user) {
            $fail('You do not have permission to access this student. Please contact your administrator if you believe this is an error.');
            return;
        }

        if (!$user->isAcademicAdvisor()) {
            return;
        }

        $student = Student::find($value);
        if (!$student) {
            $fail('You do not have permission to access this student. Please contact your administrator if you believe this is an error.');
            return;
        }

        $hasAccess = AcademicAdvisorAccess::where('advisor_id', $user->id)
            ->where('is_active', true)
            ->where(function ($query) use ($student) {
                $query->where(function ($subQuery) use ($student) {
                    $subQuery->where('level_id', $student->level_id)
                             ->where('program_id', $student->program_id);
                })->orWhere(function ($subQuery) use ($student) {
                    $subQuery->where('level_id', $student->level_id)
                             ->whereNull('program_id');
                })->orWhere(function ($subQuery) use ($student) {
                    $subQuery->whereNull('level_id')
                             ->where('program_id', $student->program_id);
                })->orWhere(function ($subQuery) use ($student) {
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
