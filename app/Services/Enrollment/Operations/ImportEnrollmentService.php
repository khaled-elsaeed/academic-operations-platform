<?php

namespace App\Services\Enrollment\Operations;

use App\Imports\EnrollmentsImport;
use App\Exports\EnrollmentsTemplateExport;
use App\Exceptions\BusinessValidationException;
use App\Rules\AcademicAdvisorAccessRule;
use App\Validators\EnrollmentImportValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Course;
use App\Models\Term;
use App\Models\AvailableCourse;
use App\Models\AvailableCourseSchedule;
use App\Models\EnrollmentSchedule;

class ImportEnrollmentService
{
    /**
     * Import enrollments from an uploaded file.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function importEnrollmentsFromFile(UploadedFile $file): array
    {
        $import = new EnrollmentsImport();

        \Maatwebsite\Excel\Facades\Excel::import($import, $file);

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
                DB::transaction(function () use ($row, $rowNum, &$created, &$updated) {
                    $result = $this->processImportRow($row->toArray(), $rowNum);
                    $result === 'created' ? $created++ : $updated++;
                });
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
        

        $enrollment = $this->createOrUpdateEnrollment($row, $student, $course, $term);

        if ($availableCourseSchedules->isNotEmpty()) {
            foreach ($availableCourseSchedules as $availableCourseSchedule) {
                EnrollmentSchedule::firstOrCreate([
                    'enrollment_id' => $enrollment->id,
                    'available_course_schedule_id' => $availableCourseSchedule->id,
                ], [
                    'status' => 'active',
                ]);
            }
        }

        return $enrollment->wasRecentlyCreated ? 'created' : 'updated';
    }

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
        // Find the AvailableCourse entry for this course and term
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
}
