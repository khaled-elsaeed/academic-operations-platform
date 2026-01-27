<?php

namespace App\Services\Enrollment\Operations;

use App\Exceptions\BusinessValidationException;
use App\Imports\EnrollmentsImport;
use App\Models\AvailableCourse;
use App\Models\AvailableCourseSchedule;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EnrollmentSchedule;
use App\Models\Student;
use App\Models\Term;
use App\Validators\EnrollmentImportValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ImportEnrollmentService
{
    private const PASSING_GRADES = ['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'P'];
    private const STATUS_ACTIVE = 'active';
    private const STATUS_CREATED = 'created';
    private const STATUS_UPDATED = 'updated';

    /**
     * Import enrollments from an uploaded file.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function importEnrollmentsFromFile(UploadedFile $file): array
    {
        $import = new EnrollmentsImport();

        Excel::import($import, $file);

        $rows = $import->rows ?? collect();
        return $this->importEnrollmentsFromRows($rows);
    }

    /**
     * Import enrollments from rows of data.
     *
     * @param Collection $rows
     * @return array
     */
    public function importEnrollmentsFromRows(Collection $rows): array
    {
        $errors = [];
        $created = 0;
        $updated = 0;

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            try {
                // Use a variable to capture the result from the transaction
                $result = DB::transaction(function () use ($row, $rowNum) {
                    return $this->processImportRow($row->toArray(), $rowNum);
                });

                if ($result === self::STATUS_CREATED) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (ValidationException $e) {
                $errors[] = [
                    'row' => $rowNum,
                    'errors' => $e->errors()["Row {$rowNum}"] ?? [],
                    'original_data' => $row->toArray()
                ];
            } catch (BusinessValidationException $e) {
                $errors[] = [
                    'row' => $rowNum,
                    'errors' => ['general' => [$e->getMessage()]],
                    'original_data' => $row->toArray()
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $rowNum,
                    'errors' => ['general' => ['Unexpected error - ' . $e->getMessage()]],
                    'original_data' => $row->toArray()
                ];
                Log::error('Import row processing failed', [
                    'row' => $rowNum,
                    'error' => $e->getMessage(),
                    'data' => $row
                ]);
            }
        }

        $totalProcessed = $created + $updated;
        $message = empty($errors)
            ? "Successfully processed {$totalProcessed} students ({$created} created, {$updated} updated)."
            : "Import completed with {$totalProcessed} successful ({$created} created, {$updated} updated) and " . count($errors) . " failed rows.";

        return [
            'success' => empty($errors),
            'message' => $message,
            'errors' => $errors,
            'imported_count' => $totalProcessed,
            'created_count' => $created,
            'updated_count' => $updated,
        ];
    }

    /**
     * Process a single import row.
     *
     * @param array $row
     * @param int $rowNum
     * @return string
     */
    private function processImportRow(array $row, int $rowNum): string
    {
        EnrollmentImportValidator::validateRow($row, $rowNum);

        $student = $this->findStudentByAcademicId($row['academic_id'] ?? '');

        $course = $this->findCourseByCode($row['course_code'] ?? '');

        $term = $this->findTermByCode($row['term_code'] ?? '');

        $availableCourseSchedules = $this->findAvailableCourseSchedule($row, $student, $course, $term);

        $this->validatePrerequisites($student, [$course->id]);

        $enrollment = $this->createOrUpdateEnrollment($row, $student, $course, $term);

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

        return $enrollment->wasRecentlyCreated ? self::STATUS_CREATED : self::STATUS_UPDATED;
    }

    /**
     * Create or update an enrollment record.
     *
     * @param array $row
     * @param Student $student
     * @param Course $course
     * @param Term $term
     * @return Enrollment
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
                'grade' => $row['grade'] ?? null,
            ]
        );
    }

    /**
     * Find a student by academic ID.
     *
     * @param string $academicId
     * @return Student|null
     */
    private function findStudentByAcademicId(string $academicId): ?Student
    {
        $student = Student::where('academic_id', $academicId)->first();

        if (!$student) {
            throw new BusinessValidationException("Student with academic ID '{$academicId}' not found.");
        }

        return $student;
    }

    /**
     * Find a course by code.
     *
     * @param string $code
     * @return Course
     * @throws BusinessValidationException
     */
    private function findCourseByCode(string $code): Course
    {
        $course = Course::where('code', $code)->first();

        if (!$course) {
            throw new BusinessValidationException("Course with code '{$code}' not found.");
        }

        return $course;
    }

    /**
     * Find a term by code.
     *
     * @param string $code
     * @return Term
     * @throws BusinessValidationException
     */
    private function findTermByCode(string $code): Term
    {
        $term = Term::where('code', $code)->first();

        if (!$term) {
            throw new BusinessValidationException("Term with code '{$code}' not found.");
        }

        return $term;
    }

    /**
     * Find available course schedules for a student, course, and term.
     *
     * @param array $row
     * @param Student $student
     * @param Course $course
     * @param Term $term
     * @return Collection
     * @throws BusinessValidationException
     */
    private function findAvailableCourseSchedule(array $row, Student $student, Course $course, Term $term): Collection
    {
        $availableCourse = AvailableCourse::where('course_id', $course->id)
            ->where('term_id', $term->id)
            ->first();

        if (!$availableCourse) {
            throw new BusinessValidationException("No available course found for course '{$course->code}' in term '{$term->code}'.");
        }

        $schedulesQuery = AvailableCourseSchedule::where('available_course_id', $availableCourse->id);

        $group = $row['group'] ?? null;

        if (!empty($group)) {
            $schedulesQuery->where('group', $group);
        }

        $schedules = $schedulesQuery->get();

        if ($schedules->isEmpty()) {
            throw new BusinessValidationException("No available schedules found for course '{$course->code}' in term '{$term->code}'" . (isset($row['group']) && $row['group'] !== '' ? " with group '{$row['group']}'." : '.'));
        }

        return $schedules;
    }

    /**
     * Validate that the student has met all prerequisites for the courses being enrolled.
     *
     * @param Student $student
     * @param array $courseIds
     * @throws BusinessValidationException
     */
    private function validatePrerequisites(Student $student, array $courseIds): void
    {
        foreach ($courseIds as $courseId) {
            $course = Course::with('prerequisites')->find($courseId);
            if (!$course) {
                continue;
            }

            foreach ($course->prerequisites as $prerequisite) {
                $hasPassed = Enrollment::where('student_id', $student->id)
                    ->where('course_id', $prerequisite->id)
                    ->whereIn('grade', self::PASSING_GRADES)
                    ->exists();

                if (!$hasPassed) {
                    throw new BusinessValidationException("Cannot enroll in {$course->name}. Prerequisite {$prerequisite->name} has not been passed.");
                }
            }
        }
    }

    /**
     * Validate the capacity of the schedule.
     *
     * @param AvailableCourseSchedule $schedule
     * @throws BusinessValidationException
     */
    private function validateScheduleCapacity(AvailableCourseSchedule $schedule): void
    {
        if (is_null($schedule->max_capacity) || $schedule->max_capacity <= 0) {
            return;
        }

        $currentEnrollmentCount = $schedule->enrollments()->count();

        if ($currentEnrollmentCount >= $schedule->max_capacity) {
            $courseCode = $schedule->availableCourse->course->code ?? 'Unknown Course';
            throw new BusinessValidationException("Schedule for course '{$courseCode}' (Group {$schedule->group}) is full. Capacity: {$schedule->max_capacity}.");
        }
    }
}
