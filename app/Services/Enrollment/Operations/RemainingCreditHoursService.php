<?php

namespace App\Services\Enrollment\Operations;

use App\Models\Student;
use App\Models\Term;
use App\Models\Enrollment;
use App\Models\CreditHoursException;
use App\Services\CreditHoursExceptionService;

class RemainingCreditHoursService
{
    protected CreditHoursExceptionService $creditHoursExceptionService;

    public function __construct(CreditHoursExceptionService $creditHoursExceptionService)
    {
        $this->creditHoursExceptionService = $creditHoursExceptionService;
    }

    /**
     * Get remaining credit hours for a student in a specific term.
     *
     * @param int $studentId
     * @param int $termId
     * @return array
     * @throws \Exception
     */
    public function getRemainingCreditHoursForStudent(int $studentId, int $termId): array
    {
        $student = Student::findOrFail($studentId);
        $term = Term::findOrFail($termId);

        $currentEnrollmentHours = $this->getCurrentEnrollmentHours($studentId, $termId);

        $maxAllowedHours = $this->getMaxCreditHours($student, $term);

        $remainingHours = $maxAllowedHours - $currentEnrollmentHours;

        $exceptionHours = $this->creditHoursExceptionService->getAdditionalHoursAllowed($studentId, $termId);

        return [
            'current_enrollment_hours' => $currentEnrollmentHours,
            'max_allowed_hours' => $maxAllowedHours,
            'remaining_hours' => max(0, $remainingHours),
            'exception_hours' => $exceptionHours,
            'student_cgpa' => $student->cgpa,
            'term_season' => $term->season,
            'term_year' => $term->year,
        ];
    }

    /**
     * Get current enrollment credit hours for the student in this term
     */
    private function getCurrentEnrollmentHours(int $studentId, int $termId): int
    {
        return Enrollment::where('student_id', $studentId)
            ->where('term_id', $termId)
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->sum('courses.credit_hours');
    }

    /**
     * Get the maximum allowed credit hours based on CGPA and semester
     */
    private function getMaxCreditHours(Student $student, Term $term): int
    {
        $semester = strtolower($term->season);
        $cgpa = $student->cgpa;
        
        $baseHours = $this->getBaseHours($semester, $cgpa);
        $graduationBonus = 0; // TODO: Implement graduation check logic
        $adminException = $this->getAdminExceptionHours($student->id, $term->id);

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

        return 14;
    }

    /**
     * Get additional hours from admin exception for this student and term
     */
    private function getAdminExceptionHours(int $studentId, int $termId): int
    {
        $exception = CreditHoursException::where('student_id', $studentId)
            ->where('term_id', $termId)
            ->active()
            ->first();

        return $exception ? $exception->getEffectiveAdditionalHours() : 0;
    }
}