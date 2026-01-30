<?php

declare(strict_types=1);

namespace App\Services\Enrollment\Operations;

use App\Models\AvailableCourse;
use App\Models\AvailableCourseSchedule;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EnrollmentSchedule;
use App\Models\Student;
use App\Models\Term;
use App\Exceptions\BusinessValidationException;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProcessImportService
{
    // Column indices
    private const ACADEMIC_ID_COLUMN = 0;
    private const COURSE_CODE_COLUMN = 1;
    private const TERM_CODE_COLUMN = 2;
    private const GRADE_COLUMN = 3;

    private const PASSING_GRADES = ['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'P'];
    private const STATUS_ACTIVE = 'active';

    protected array $results = [
        'summary' => [
            'total_processed' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
        ],
        'rows' => [],
    ];

    public function __construct(
        protected array $rows
    ) {
    }

    /**
     * Main entry point for processing enrollments
     */
    public function process(): array
    {
        try {
            if (empty($this->rows)) {
                return $this->results;
            }

            $this->processRows();

            return $this->results;

        } catch (Throwable $e) {
            Log::error('Enrollment processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Process all rows
     */
    private function processRows(): void
    {
        foreach ($this->rows as $index => $row) {
            $rowNum = $index + 2; // +2 because Excel is 1-indexed and we skip header

            try {
                DB::transaction(function () use ($row, $rowNum) {
                    $this->processSingleRow($row, $rowNum);
                });

                $this->results['summary']['total_processed']++;

            } catch (ValidationException $e) {
                $this->handleRowError($rowNum, $e->errors(), $row);
            } catch (BusinessValidationException $e) {
                $this->handleRowError($rowNum, ['general' => [$e->getMessage()]], $row);
            } catch (Throwable $e) {
                $this->handleRowError($rowNum, ['general' => ['Unexpected error: ' . $e->getMessage()]], $row);
                Log::error('Import row processing failed', [
                    'row' => $rowNum,
                    'error' => $e->getMessage(),
                    'data' => $row
                ]);
            }
        }
    }

    /**
     * Process a single row
     */
    private function processSingleRow(array $row, int $rowNum): void
    {
        $this->validateRow($row, $rowNum);

        $student = $this->findStudent($row[self::ACADEMIC_ID_COLUMN] ?? '');
        $course = $this->findCourse($row[self::COURSE_CODE_COLUMN] ?? '');
        $term = $this->findTerm($row[self::TERM_CODE_COLUMN] ?? '');

        $availableCourseSchedules = $this->findAvailableCourseSchedules($student, $course, $term);

        $this->validatePrerequisites($student, [$course->id]);

        $enrollment = $this->createOrUpdateEnrollment($row, $student, $course, $term);

        $this->createEnrollmentSchedules($enrollment, $availableCourseSchedules);

        // Track success
        if ($enrollment->wasRecentlyCreated) {
            $this->results['summary']['created']++;
        } else {
            $this->results['summary']['updated']++;
        }
    }

    /**
     * Validate a single row
     */
    private function validateRow(array $row, int $rowNum): void
    {
        $validator = Validator::make($row, [
            self::ACADEMIC_ID_COLUMN => 'required|string',
            self::COURSE_CODE_COLUMN => 'required|string',
            self::TERM_CODE_COLUMN => 'required|string',
            self::GRADE_COLUMN => 'nullable|string|max:5',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Find student by academic ID
     */
    private function findStudent(string $academicId): Student
    {
        $student = Student::where('academic_id', $academicId)->first();

        if (!$student) {
            throw new BusinessValidationException("Student with academic ID '{$academicId}' not found.");
        }

        return $student;
    }

    /**
     * Find course by code
     */
    private function findCourse(string $code): Course
    {
        $course = Course::where('code', $code)->first();

        if (!$course) {
            throw new BusinessValidationException("Course with code '{$code}' not found.");
        }

        return $course;
    }

    /**
     * Find term by code
     */
    private function findTerm(string $code): Term
    {
        $term = Term::where('code', $code)->first();

        if (!$term) {
            throw new BusinessValidationException("Term with code '{$code}' not found.");
        }

        return $term;
    }

    /**
     * Find available course schedules for the enrollment
     */
    private function findAvailableCourseSchedules(Student $student, Course $course, Term $term): \Illuminate\Support\Collection
    {
        $availableCourse = AvailableCourse::where('course_id', $course->id)
            ->where('term_id', $term->id)
            ->first();

        if (!$availableCourse) {
            throw new BusinessValidationException("No available course found for course '{$course->code}' in term '{$term->code}'.");
        }

        return $availableCourse->schedules;
    }

    /**
     * Validate prerequisites (placeholder - implement based on your business logic)
     */
    private function validatePrerequisites(Student $student, array $courseIds): void
    {
        // Implement prerequisite validation if needed
        // For now, we'll skip this as it might be complex
    }

    /**
     * Create or update enrollment
     */
    private function createOrUpdateEnrollment(array $row, Student $student, Course $course, Term $term): Enrollment
    {
        return Enrollment::updateOrCreate(
            [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'term_id' => $term->id,
            ],
            [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'term_id' => $term->id,
                'grade' => $row[self::GRADE_COLUMN] ?? null,
            ]
        );
    }

    /**
     * Create enrollment schedules
     */
    private function createEnrollmentSchedules(Enrollment $enrollment, \Illuminate\Support\Collection $availableCourseSchedules): void
    {
        foreach ($availableCourseSchedules as $availableCourseSchedule) {
            $exists = EnrollmentSchedule::where('enrollment_id', $enrollment->id)
                ->where('available_course_schedule_id', $availableCourseSchedule->id)
                ->exists();

            if (!$exists) {
                $this->validateScheduleCapacity($availableCourseSchedule);

                EnrollmentSchedule::create([
                    'enrollment_id' => $enrollment->id,
                    'available_course_schedule_id' => $availableCourseSchedule->id,
                    'status' => self::STATUS_ACTIVE,
                ]);
            }
        }
    }

    /**
     * Validate schedule capacity
     */
    private function validateScheduleCapacity(AvailableCourseSchedule $schedule): void
    {
        if ($schedule->max_capacity !== null) {
            $enrolledCount = EnrollmentSchedule::where('available_course_schedule_id', $schedule->id)->count();

            if ($enrolledCount >= $schedule->max_capacity) {
                throw new BusinessValidationException("Schedule capacity exceeded for schedule ID {$schedule->id}.");
            }
        }
    }

    /**
     * Handle row error
     */
    private function handleRowError(int $rowNum, array $errors, array $originalData): void
    {
        $this->results['summary']['failed']++;

        $this->results['rows'][] = [
            'row' => $rowNum,
            'errors' => $errors,
            'original_data' => $originalData,
        ];
    }
}