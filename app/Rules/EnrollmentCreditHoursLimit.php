<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Term;
use App\Models\CreditHoursException;

class EnrollmentCreditHoursLimit implements ValidationRule
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
        \Log::debug('EnrollmentCreditHoursLimit validate called', [
            'attribute' => $attribute,
            'value_received' => $value,
            'studentId' => $this->studentId,
            'termId' => $this->termId,
        ]);
        $totalCreditHours = (int) $value;
        
        // Get student and term data
        $student = Student::find($this->studentId);
        $term = Term::find($this->termId);
        
        if (!$student || !$term) {
            $fail("Student or term not found.");
            return;
        }
        
        $semester = strtolower($term->season);
        $cgpa = $student->cgpa;
        
        // Get current enrollment credit hours for this term
        $currentEnrollmentHours = $this->getCurrentEnrollmentHours();
        
        $maxAllowedHours = $this->getMaxCreditHours($semester, $cgpa);
        $remainingHours = $maxAllowedHours - $currentEnrollmentHours;

        // Fix: Use correct totalCreditHours in error message
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
    private function getMaxCreditHours(string $semester, float $cgpa): int
    {
        $baseHours = $this->getBaseHours($semester, $cgpa);
        $graduationBonus = $this->isGraduating ? 3 : 0;
        $adminException = $this->getAdminExceptionHours();

        return $baseHours + $graduationBonus + $adminException;
    }

    /**
     * Get base credit hours based on semester and CGPA
     */
    private function getBaseHours(string $semester, float $cgpa): int
    {
        // Summer semester has fixed 9 hours regardless of CGPA
        if ($semester === 'summer') {
            return 9;
        }

        // Fall and Spring semesters have CGPA-based limits
        if (in_array($semester, ['fall', 'spring'])) {
            if ($cgpa < 2.0) {
                return 14;
            } elseif ($cgpa >= 2.0 && $cgpa < 3.0) {
                return 18;
            } elseif ($cgpa >= 3.0) {
                return 21;
            }
        }

        // Default fallback (shouldn't reach here with valid semesters)
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