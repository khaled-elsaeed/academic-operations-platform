<?php

declare(strict_types=1);

namespace App\Services\Enrollment\Operations;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Term;
use App\Exceptions\BusinessValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProcessSisImportService
{
    // Column indices
    private const TERM_CODE_COLUMN = 0;
    private const STUDENT_ID_COLUMN = 2;
    private const COURSE_CODE_COLUMN = 6;
    private const GRADE_COLUMN = 8;
    private const CGPA_COLUMN = 10;

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
    ) {}

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
            Log::error('SIS Enrollment processing failed', [
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
            $rowNum = $index + 2; 

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
                Log::error('SIS Import row processing failed', [
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

        $student = $this->findStudent($row[self::STUDENT_ID_COLUMN] ?? '');
        $course = $this->findCourse($row[self::COURSE_CODE_COLUMN] ?? '');
        $term = $this->findTerm($row[self::TERM_CODE_COLUMN] ?? '');

        if (isset($row[self::CGPA_COLUMN])) {
            $student->cgpa = (float) $row[self::CGPA_COLUMN];
            $student->save();
        }
        $enrollment = Enrollment::where([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'term_id' => $term->id,
        ])->first();

        if (!$enrollment) {
            Enrollment::create([
                'student_id' => $student->id,
                'course_id' => $course->id,
                'term_id' => $term->id,
                'grade' => $row[self::GRADE_COLUMN] ?? null,
            ]);

            $this->results['summary']['created']++;
        } else {
            $updateData = [];
            if (array_key_exists(self::GRADE_COLUMN, $row)) {
                $updateData['grade'] = $row[self::GRADE_COLUMN];
            }
            if (!empty($updateData)) {
                $enrollment->update($updateData);
            }

            $this->results['summary']['updated']++;
        }
    }

    /**
     * Validate a single row
     */
    private function validateRow(array $row, int $rowNum): void
    {
        $validator = Validator::make($row, [
            self::STUDENT_ID_COLUMN => 'required|string',
            self::COURSE_CODE_COLUMN => 'required|string',
            self::TERM_CODE_COLUMN => 'required|string',
            self::GRADE_COLUMN => 'nullable|string|max:5',
            self::CGPA_COLUMN => 'nullable|numeric|between:0,4.00',
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
