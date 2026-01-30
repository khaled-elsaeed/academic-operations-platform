<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Term;
use App\Models\CreditHoursException;

class EnrollmentCreditHoursRule implements ValidationRule
{
    protected int $studentId;
    protected int $termId;
    protected bool $isGraduating;

    /**
     * Create a new rule instance.
     *
     * @param int $studentId The student's ID
     * @param int $termId The term's ID
     * @param bool $isGraduating Whether the student is graduating (default: false)
     */
    public function __construct(int $studentId, int $termId, bool $isGraduating = false)
    {
        $this->studentId = $studentId;
        $this->termId = $termId;
        $this->isGraduating = $isGraduating;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $totalCreditHours = (int) $value;
        
        $student = Student::find($this->studentId);
        $term = Term::find($this->termId);
        
        if (!$student || !$term) {
            $fail("Student or term not found.");
            return;
        }
        
        $semester = strtolower($term->season);
        $cgpa = $student->cgpa;
        $takenCreditHours = $student->taken_credit_hours;
        
        $currentEnrollmentHours = $this->getCurrentEnrollmentHours();
        
        $maxAllowedHours = $this->getMaxCreditHours($semester, $cgpa, $takenCreditHours);
        $remainingHours = $maxAllowedHours - $currentEnrollmentHours;

        if ($totalCreditHours > $maxAllowedHours) {
            if ($remainingHours <= 0) {
                $fail("You cannot add any more credit hours for this term. The maximum allowed is {$maxAllowedHours} based on your CGPA ({$cgpa}) and semester ({$semester}).");
            } else {
                $fail("The total credit hours ({$totalCreditHours}) cannot exceed {$maxAllowedHours} based on your CGPA ({$cgpa}) and semester ({$semester}). You can only add {$remainingHours} more credit hours.");
            }
        }
    }

    /**
     * Get the maximum allowed credit hours based on CGPA and semester
     */
    private function getMaxCreditHours(string $semester, float $cgpa, int $takenCreditHours): int
    {
        $baseHours = $this->getBaseHours($semester, $cgpa, $takenCreditHours);
        $graduationBonus = $this->isGraduating ? 3 : 0;
        $adminException = $this->getAdminExceptionHours();

        return $baseHours + $graduationBonus + $adminException;
    }

    /**
     * Credit Hour Limits:
     * - Summer: 9 hours
     * - Fall/Spring:
     *   - CGPA < 2.0: 14 hours
     *   - 2.0 <= CGPA < 3.0: 18 hours
     *   - CGPA >= 3.0: 21 hours
     * - If no credit hours taken yet, allow maximum of 18 hours , for new students.
     */
    private function getBaseHours(string $semester, float $cgpa, int $takenCreditHours): int
    {
        if ($takenCreditHours == 0) {
            return 18;
        }

        if ($semester === 'summer') {
            return 9;
        }

        if (in_array($semester,  ['fall', 'spring'])) {
            if ($cgpa < 2.0) {
                return 14;
            } elseif ($cgpa >= 2.0 && $cgpa < 3.0) {
                return 18;
            } elseif ($cgpa >= 3.0) {
                return 21;
            }
        }

        return 14;
    }

    /**
     * Get current enrollment credit hours for the student in this term
     */
    private function getCurrentEnrollmentHours(): int
    {
        if ($this->studentId === 0 || $this->termId === 0) {
            return 0;
        }

        return Enrollment::where('student_id', $this->studentId)
            ->where('term_id', $this->termId)
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->sum('courses.credit_hours');
    }

    /**
     * Get additional hours from admin exception for this student and term
     */
    private function getAdminExceptionHours(): int
    {
        if ($this->studentId === 0 || $this->termId === 0) {
            return 0;
        }

        $exception = CreditHoursException::where('student_id', $this->studentId)
            ->where('term_id', $this->termId)
            ->active()
            ->first();

        return $exception ? $exception->getEffectiveAdditionalHours() : 0;
    }
}