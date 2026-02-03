<?php

namespace App\Services\Enrollment\Operations;

use App\Exceptions\BusinessValidationException;
use App\Models\Course;
use App\Models\Enrollment;
use App\Rules\AcademicAdvisorAccessRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateEnrollmentWithoutScheduleService
{
    /**
     * Passing grades for enrollment validation.
     */
    private const PASSING_GRADES = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'P'];

    public function __construct()
    {}

    /**
     * Create multiple enrollments without schedules (grade-only).
     *
     * @param int $studentId
     * @param array $enrollmentData Array of enrollment items with term_id, course_id, grade
     * @return Collection
     * @throws BusinessValidationException
     */
    public function create(int $studentId, array $enrollmentData): Collection
    {
        return DB::transaction(function () use ($studentId, $enrollmentData) {
            $this->validateAdvisorAccess($studentId);

            return collect($enrollmentData)->map(function ($item) use ($studentId) {
                return $this->createSingle($studentId, $item);
            });
        });
    }

    /**
     * Create a single enrollment without schedules.
     *
     * @param int $studentId
     * @param array $enrollmentItem
     * @return Enrollment
     * @throws BusinessValidationException
     */
    private function createSingle(int $studentId, array $enrollmentItem): Enrollment
    {
        $termId = (int) $enrollmentItem['term_id'];
        $courseId = (int) $enrollmentItem['course_id'];
        $grade = $enrollmentItem['grade'] ?? null;

        $course = Course::findOrFail($courseId);

        // Validate enrollment
        $this->validateEnrollment($studentId, $termId, $courseId, $course->name);

        // Create the enrollment record
        $enrollment = $this->createEnrollmentRecord($studentId, $termId, $courseId, $grade);

        return $enrollment->load('course', 'term');
    }

    // ==================== Validation Methods ====================

    /**
     * Validate advisor access to student.
     *
     * @param int $studentId
     * @throws BusinessValidationException
     */
    private function validateAdvisorAccess(int $studentId): void
    {
        $rule = new AcademicAdvisorAccessRule();
        
        $rule->validate('student_id', $studentId, function ($message) {
            throw new BusinessValidationException($message, 403);
        });
    }

    /**
     * Validate the enrollment item.
     *
     * @param int $studentId
     * @param int $termId
     * @param int $courseId
     * @param string $courseName
     * @throws BusinessValidationException
     */
    private function validateEnrollment(int $studentId, int $termId, int $courseId, string $courseName): void
    {
        $this->checkDuplicateEnrollment($studentId, $termId, $courseId, $courseName);
    }

    /**
     * Check if student is already enrolled in course for this term.
     *
     * @param int $studentId
     * @param int $termId
     * @param int $courseId
     * @param string $courseName
     * @throws BusinessValidationException
     */
    private function checkDuplicateEnrollment(int $studentId, int $termId, int $courseId, string $courseName): void
    {
        if (Enrollment::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->where('term_id', $termId)
            ->exists()
        ) {
            throw new BusinessValidationException(
                "Student is already enrolled in {$courseName} for this term.",
                422
            );
        }
    }

    // ==================== Creation Methods ====================

    /**
     * Create the enrollment record.
     *
     * @param int $studentId
     * @param int $termId
     * @param int $courseId
     * @param string|null $grade
     * @return Enrollment
     * @throws BusinessValidationException
     */
    private function createEnrollmentRecord(int $studentId, int $termId, int $courseId, ?string $grade): Enrollment
    {
        try {
            return Enrollment::create([
                'student_id' => $studentId,
                'course_id' => $courseId,
                'term_id' => $termId,
                'grade' => $grade,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create enrollment without schedule', [
                'student_id' => $studentId,
                'course_id' => $courseId,
                'term_id' => $termId,
                'grade' => $grade,
                'error' => $e->getMessage(),
            ]);

            throw new BusinessValidationException(
                'Failed to create enrollment. Please try again.',
                500
            );
        }
    }

    /**
     * Check if student has passed a course.
     *
     * @param int $studentId
     * @param int $courseId
     * @return bool
     */
    private function hasPassedCourse(int $studentId, int $courseId): bool
    {
        return Enrollment::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->whereIn('grade', self::PASSING_GRADES)
            ->exists();
    }
}
