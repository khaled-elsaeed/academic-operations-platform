<?php

namespace App\Services\Enrollment\Operations;

use App\DTOs\EnrollmentData;
use App\Exceptions\BusinessValidationException;
use App\Models\AvailableCourse;
use App\Models\AvailableCourseSchedule;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EnrollmentSchedule;
use App\Models\Student;
use App\Rules\AcademicAdvisorAccessRule;
use App\Rules\EnrollmentCreditHoursRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Schedule\ScheduleAssignment;

class CreateEnrollmentService
{
    private const PASSING_GRADES = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'P'];

    public function __construct()
    {}

    /**
     * Create multiple enrollments with schedules.
     */
    public function create(int $studentId, int $termId, array $enrollments): Collection
    {
        return DB::transaction(function () use ($studentId, $termId, $enrollments) {
            $this->validateBatchRequest($studentId, $termId, $enrollments);

            return collect($enrollments)->map(function ($item) use ($studentId, $termId) {
                return $this->createSingle($studentId, $termId, $item);
            });
        });
    }

    /**
     * Create a single enrollment with schedules.
     */
    private function createSingle(int $studentId, int $termId, array $enrollmentData): Enrollment
    {
        $availableCourse = AvailableCourse::with('course.prerequisites')
            ->findOrFail($enrollmentData['available_course_id']);

        $this->validateSingleEnrollment($studentId, $termId, $availableCourse, $enrollmentData);

        $enrollment = $this->createEnrollmentRecord($studentId, $termId, $availableCourse->course_id);

        if ($this->shouldAttachSchedules($enrollmentData)) {
            $this->attachSchedules($enrollment, $availableCourse, $enrollmentData);
        }

        return $enrollment->load('schedules');
    }

    // ==================== Validation Methods ====================

    /**
     * Validate the entire batch request.
     */
    private function validateBatchRequest(int $studentId, int $termId, array $enrollments): void
    {
        $this->validateAdvisorAccess($studentId);
        $this->validateTotalCreditHours($studentId, $termId, $enrollments);
    }

    /**
     * Validate advisor access to student.
     */
    private function validateAdvisorAccess(int $studentId): void
    {
        $rule = new AcademicAdvisorAccessRule();
        
        $rule->validate('student_id', $studentId, function ($message) {
            throw new BusinessValidationException($message, 403);
        });
    }

    /**
     * Validate total credit hours don't exceed limit.
     */
    private function validateTotalCreditHours(int $studentId, int $termId, array $enrollments): void
    {
        $requestedHours = $this->calculateRequestedHours($enrollments);
        $currentHours = $this->getCurrentEnrollmentHours($studentId, $termId);
        $totalHours = $currentHours + $requestedHours;

        $rule = new EnrollmentCreditHoursRule($studentId, $termId);
        
        $rule->validate('credit_hours', $totalHours, function ($message) {
            throw new BusinessValidationException($message, 422);
        });
    }

    /**
     * Calculate requested credit hours from enrollment items.
     */
    private function calculateRequestedHours(array $enrollments): int
    {
        return collect($enrollments)
            ->map(fn($item) => AvailableCourse::with('course')
                ->find($item['available_course_id'])
                ?->course
                ?->credit_hours ?? 0
            )
            ->sum();
    }

    /**
     * Get current enrollment hours for student in term.
     */
    private function getCurrentEnrollmentHours(int $studentId, int $termId): int
    {
        return Enrollment::where('student_id', $studentId)
            ->where('term_id', $termId)
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->sum('courses.credit_hours') ?? 0;
    }

    /**
     * Validate a single enrollment item.
     */
    private function validateSingleEnrollment(
        int $studentId,
        int $termId,
        AvailableCourse $availableCourse,
        array $enrollmentData
    ): void {
        $this->checkDuplicateEnrollment($studentId, $termId, $availableCourse->course_id, $availableCourse->course->name);
        $this->validatePrerequisites($studentId, $availableCourse->course);
        
        if ($this->shouldAttachSchedules($enrollmentData)) {
            $this->validateScheduleIds($enrollmentData['selected_schedule_ids'] ?? []);
            $this->validateSchedulesCapacity($termId, $enrollmentData['selected_schedule_ids'] ?? []);
            $this->validateTimeConflicts($studentId, $termId, $enrollmentData['selected_schedule_ids'] ?? []);
        }
    }

    /**
     * Check if student is already enrolled in course.
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

    /**
     * Validate course prerequisites are met.
     */
    private function validatePrerequisites(int $studentId, Course $course): void
    {
        $unmetPrerequisites = $course->prerequisites->filter(function ($prerequisite) use ($studentId) {
            return !$this->hasPassedCourse($studentId, $prerequisite->id);
        });

        if ($unmetPrerequisites->isNotEmpty()) {
            $prerequisiteName = $unmetPrerequisites->first()->name;
            throw new BusinessValidationException(
                "Prerequisite not met: {$prerequisiteName} must be passed before enrolling in {$course->name}.",
                422
            );
        }
    }

    /**
     * Check if student has passed a course.
     */
    private function hasPassedCourse(int $studentId, int $courseId): bool
    {
        return Enrollment::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->whereIn('grade', self::PASSING_GRADES)
            ->exists();
    }

    /**
     * Validate that schedule IDs are not empty.
     */
    private function validateScheduleIds(array $scheduleIds): void
    {
        if (empty($scheduleIds)) {
            throw new BusinessValidationException(
                'At least one schedule must be selected when create_schedule is enabled.',
                422
            );
        }
    }

    /**
     * Validate schedule capacities.
     */
    private function validateSchedulesCapacity(int $termId, array $scheduleIds): void
    {
        collect($scheduleIds)->each(function ($scheduleId) {
            $schedule = AvailableCourseSchedule::findOrFail($scheduleId);

            if ($this->isScheduleFull($schedule, $schedule->current_capacity ?? 0)) {
                throw new BusinessValidationException(
                    "Schedule capacity reached for Group {$schedule->group} (ID: {$scheduleId}).",
                    422
                );
            }
        });
    }

    /**
     * Get enrollment count for a schedule.
     */
    private function getScheduleEnrollmentCount(int $scheduleId, int $termId): int
    {
        return EnrollmentSchedule::where('available_course_schedule_id', $scheduleId)
            ->whereHas('enrollment', fn($q) => $q->where('term_id', $termId))
            ->count();
    }

    /**
     * Check if schedule is at capacity.
     */
    private function isScheduleFull(AvailableCourseSchedule $schedule, int $enrolledCount): bool
    {
        return $schedule->max_capacity !== null && $enrolledCount >= $schedule->max_capacity;
    }

    /**
     * Validate time conflicts with existing enrollments.
     */
    private function validateTimeConflicts(int $studentId, int $termId, array $scheduleIds): void
    {
        $existingSlots = EnrollmentSchedule::whereHas('enrollment', function ($query) use ($studentId, $termId) {
                $query->where('student_id', $studentId)->where('term_id', $termId);
            })
            ->with('availableCourseSchedule.scheduleAssignments.scheduleSlot')
            ->get()
            ->flatMap(fn($es) => $es->availableCourseSchedule->scheduleAssignments ?? collect())
            ->map(fn($assignment) => $assignment->scheduleSlot)
            ->filter();

        $newSchedules = AvailableCourseSchedule::with('scheduleAssignments.scheduleSlot')
            ->whereIn('id', $scheduleIds)
            ->get();

        $newSlots = $newSchedules
            ->flatMap(fn($schedule) => $schedule->scheduleAssignments ?? collect())
            ->map(fn($assignment) => $assignment->scheduleSlot)
            ->filter();

        foreach ($newSlots as $newSlot) {
            foreach ($existingSlots as $existingSlot) {
                if ($this->hasTimeConflict($newSlot, $existingSlot)) {
                    throw new BusinessValidationException(
                        "Schedule time conflict: {$newSlot->day_of_week} {$newSlot->start_time->format('H:i')} - {$newSlot->end_time->format('H:i')} overlaps with existing enrollment.",
                        422
                    );
                }
            }
        }
    }

    /**
     * Check if two schedule slots have a time conflict.
     */
    private function hasTimeConflict($slot1, $slot2): bool
    {
        if ($slot1->day_of_week !== $slot2->day_of_week) {
            return false;
        }

        return $slot1->start_time < $slot2->end_time && $slot1->end_time > $slot2->start_time;
    }

    // ==================== Creation Methods ====================

    /**
     * Create the enrollment record.
     */
    private function createEnrollmentRecord(int $studentId, int $termId, int $courseId): Enrollment
    {
        try {
            return Enrollment::create([
                'student_id' => $studentId,
                'course_id' => $courseId,
                'term_id' => $termId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create enrollment', [
                'student_id' => $studentId,
                'course_id' => $courseId,
                'term_id' => $termId,
                'error' => $e->getMessage(),
            ]);

            throw new BusinessValidationException(
                'Failed to create enrollment. Please try again.',
                500
            );
        }
    }

    /**
     * Attach schedules to enrollment.
     */
    private function attachSchedules(
        Enrollment $enrollment,
        AvailableCourse $availableCourse,
        array $enrollmentData
    ): void {
        $scheduleIds = $this->resolveScheduleIds($availableCourse, $enrollmentData);

        if (empty($scheduleIds)) {
            throw new BusinessValidationException(
                "No schedules available for {$availableCourse->course->name}.",
                422
            );
        }

        $this->createEnrollmentSchedules($enrollment, $scheduleIds);
    }

    /**
     * Resolve which schedule IDs to use.
     */
    private function resolveScheduleIds(AvailableCourse $availableCourse, array $enrollmentData): array
    {
        return $enrollmentData['selected_schedule_ids'] 
            ?? $availableCourse->schedules->pluck('id')->toArray();
    }

    /**
     * Bulk create enrollment schedule records.
     */
    private function createEnrollmentSchedules(Enrollment $enrollment, array $scheduleIds): void
    {
        $records = collect($scheduleIds)->map(fn($scheduleId) => [
            'enrollment_id' => $enrollment->id,
            'available_course_schedule_id' => $scheduleId,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        EnrollmentSchedule::insert($records);

        foreach ($scheduleIds as $scheduleId) {
            AvailableCourseSchedule::where('id', $scheduleId)->increment('current_capacity');
            
            ScheduleAssignment::where('available_course_schedule_id', $scheduleId)->increment('enrolled');
        }
    }

    /**
     * Check if schedules should be attached.
     */
    private function shouldAttachSchedules(array $enrollmentData): bool
    {
        return filter_var($enrollmentData['create_schedule'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }
}