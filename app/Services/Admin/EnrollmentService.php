<?php

namespace App\Services\Admin;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Course;
use App\Models\Term;
use App\Models\AvailableCourse;
use Yajra\DataTables\DataTables;
use App\Exceptions\BusinessValidationException;
use App\Rules\AcademicAdvisorAccessRule;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EnrollmentsImport;
use App\Exports\EnrollmentsTemplateExport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use App\Validators\EnrollmentImportValidator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use App\Rules\EnrollmentCreditHoursLimit;

class EnrollmentService
{
    public function createEnrollment(array $data): Enrollment
    {
        // Prevent duplicate enrollment for the same student, course, and term
        $exists = Enrollment::where('student_id', $data['student_id'])
            ->where('course_id', $data['course_id'])
            ->where('term_id', $data['term_id'])
            ->exists();
        if ($exists) {
            $courseName = \App\Models\Course::find($data['course_id'])?->name ?? 'this course';
            throw new BusinessValidationException("The student is already enrolled in {$courseName} for the selected term.");
        }
        return Enrollment::create($data);
    }

    /**
     * Create multiple enrollments for a student.
     *
     * @param array $data
     * @return array
     * @throws BusinessValidationException
     */
    public function createEnrollments(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Get student and term information for credit hours validation
            $student = Student::findOrFail($data['student_id']);
            $term = Term::findOrFail($data['term_id']);
            
            // Calculate total credit hours for requested courses
            $requestedCreditHours = 0;
            $availableCourses = [];
            $courseIds = [];
            
            foreach ($data['available_course_ids'] as $availableCourseId) {
                $availableCourse = AvailableCourse::with('course')->findOrFail($availableCourseId);
                $requestedCreditHours += $availableCourse->course->credit_hours;
                $availableCourses[] = $availableCourse;
                $courseIds[] = $availableCourse->course_id;
            }
            
            // Validate credit hours limit BEFORE creating enrollments
            $this->validateCreditHoursLimit($student, $term, $requestedCreditHours);
            
            // Create enrollments only after validation passes
            $enrollments = [];
            foreach ($availableCourses as $availableCourse) {
                $enrollments[] = $this->createEnrollment([
                    'student_id' => $data['student_id'],
                    'course_id' => $availableCourse->course_id,
                    'term_id' => $availableCourse->term_id,
                ]);
            }
            return $enrollments;
        });
    }



    public function deleteEnrollment(Enrollment $enrollment): void
    {
        $enrollment->delete();
    }

    public function getStats(): array
    {
        $latest = Enrollment::latest('created_at')->value('created_at');
        return [
            'enrollments' => [
                'total' => Enrollment::count(),
                'lastUpdateTime' => formatDate($latest),
            ],
        ];
    }

    public function getDatatable(): \Illuminate\Http\JsonResponse
    {
        $query = Enrollment::join('students', 'enrollments.student_id', '=', 'students.id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->join('terms', 'enrollments.term_id', '=', 'terms.id')
            ->select('enrollments.*', 'students.name_en as student_name', 'courses.title as course_title', 'courses.code as course_code', 'terms.season as term_season', 'terms.year as term_year');
            
        if (request()->has('student_id')) {
            $query->where('enrollments.student_id', request('student_id'));
        }
        
        return DataTables::of($query)
            ->addColumn('student', function($enrollment) {
                return $enrollment->student_name ?? '-';
            })
            ->addColumn('course', function($enrollment) {
                return $enrollment->course_title && $enrollment->course_code ? "{$enrollment->course_title} ({$enrollment->course_code})" : '-';
            })
            ->addColumn('term', function($enrollment) {
                return $enrollment->term_season && $enrollment->term_year ? "{$enrollment->term_season} {$enrollment->term_year}" : '-';
            })
            ->addColumn('action', function($enrollment) {
                return $this->renderActionButtons($enrollment);
            })
            ->orderColumn('student', 'students.name_en $1')
            ->orderColumn('course', 'courses.title $1')
            ->orderColumn('term', 'terms.season $1, terms.year $1')
            ->rawColumns(['action'])
            ->make(true);
    }

    protected function renderActionButtons($enrollment)
    {
        return '
        <div class="d-flex gap-2">
          <button type="button"
            class="btn btn-sm btn-icon btn-danger rounded-circle deleteEnrollmentBtn"
            data-id="' . e($enrollment->id) . '"
            title="Delete">
            <i class="bx bx-trash"></i>
          </button>
        </div>
        ';
    }

    public function getStudentEnrollments($studentId)
    {
        return Enrollment::with(['course', 'term'])
            ->where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Find a student by national or academic ID.
     *
     * @param string $identifier
     * @return Student
     * @throws BusinessValidationException
     */
    public function findStudent(string $identifier): Student
    {
        $student = Student::withoutGlobalScopes()
            ->where('national_id', $identifier)
            ->orWhere('academic_id', $identifier)
            ->with('program', 'level')
            ->first();

        if (!$student) {
            throw new BusinessValidationException('Student not found.', 404);
        }

        // Validate access using the custom validation rule
        $validator = \Validator::make(
            ['student_id' => $student->id],
            ['student_id' => ['required', 'exists:students,id', new AcademicAdvisorAccessRule()]]
        );

        if ($validator->fails()) {
            throw new BusinessValidationException($validator->errors()->first('student_id'), 403);
        }

        return $student;
    }

    /**
     * Import enrollments from an uploaded Excel file.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function importEnrollmentsFromFile(UploadedFile $file): array
    {
        try {
            $import = new EnrollmentsImport();
            Excel::import($import, $file);
            $rows = $import->rows ?? collect();
            
            return $this->importEnrollmentsFromRows($rows);
        } catch (\Exception $e) {
            Log::error('Failed to import enrollments', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to process the uploaded file.',
                'errors' => [$e->getMessage()],
                'created' => 0,
            ];
        }
    }

    /**
     * Import enrollments from collection of rows.
     *
     * @param Collection $rows
     * @return array
     */
    public function importEnrollmentsFromRows(Collection $rows): array
    {
        $errors = [];
        $created = 0;
        $skipped = 0;
        
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // Account for header row and 0-based index
            
            try {
                DB::transaction(function () use ($row, $rowNum, &$created, &$skipped) {
                    $result = $this->processImportRow($row->toArray(), $rowNum);
                    if ($result === 'created') {
                        $created++;
                    } else {
                        $skipped++;
                    }
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
        
        $totalProcessed = $created + $skipped;
        $message = empty($errors) 
            ? "Successfully processed {$totalProcessed} enrollments ({$created} created, {$skipped} skipped)." 
            : "Import completed with {$totalProcessed} successful ({$created} created, {$skipped} skipped) and " . count($errors) . " failed rows.";
        
        return [
            'success' => empty($errors),
            'message' => $message,
            'errors' => $errors,
            'imported_count' => $totalProcessed,
            'created_count' => $created,
            'skipped_count' => $skipped,
        ];
    }

    /**
     * Process a single import row.
     *
     * @param array $row
     * @param int $rowNum
     * @return string 'created' or 'skipped'
     * @throws ValidationException|BusinessValidationException
     */
    private function processImportRow(array $row, int $rowNum): string
    {
        EnrollmentImportValidator::validateRow($row, $rowNum);

        // Find related models
        $student = $this->findStudentByAcademicId($row['academic_id'] ?? '');
        $course = $this->findCourseByCode($row['course_code'] ?? '');
        $term = $this->findTermByCode($row['term_code'] ?? '');

        // Check if enrollment already exists
        $exists = Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('term_id', $term->id)
            ->exists();

        if ($exists) {
            return 'skipped'; // Skip duplicate enrollments
        }

        // Create enrollment
        Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'term_id' => $term->id,
        ]);

        return 'created';
    }

    /**
     * Find student by academic ID.
     *
     * @param string $academicId
     * @return Student
     * @throws BusinessValidationException
     */
    private function findStudentByAcademicId(string $academicId): Student
    {
        $student = Student::where('academic_id', $academicId)->first();
        if (!$student) {
            throw new BusinessValidationException("Student with academic ID '{$academicId}' not found.");
        }
        return $student;
    }

    /**
     * Find course by code.
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
     * Find term by code.
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
     * Import enrollments from an uploaded Excel file. Collects all errors and continues processing valid rows.
     *
     * @param UploadedFile $file
     * @return array [success => bool, message => string, errors => array, imported_count => int]
     */
    public function importEnrollments(UploadedFile $file): array
    {
        return $this->importEnrollmentsFromFile($file);
    }

    /**
     * Download the enrollments import template as an Excel file.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadTemplate()
    {
        return Excel::download(new EnrollmentsTemplateExport, 'enrollments_import_template.xlsx');
    }

    /**
     * Validate credit hours limit for student enrollment.
     *
     * @param Student $student
     * @param Term $term
     * @param int $requestedCreditHours
     * @throws BusinessValidationException
     */
    private function validateCreditHoursLimit(Student $student, Term $term, int $requestedCreditHours): void
    {
        // Check if student is graduating (you may need to implement this logic based on your requirements)
        $isGraduating = false; // TODO: Implement graduation check logic
        
        $validator = \Validator::make(
            ['credit_hours' => $requestedCreditHours],
            ['credit_hours' => [new EnrollmentCreditHoursLimit(
                $student->id,
                $term->id,
                $isGraduating
            )]]
        );

        if ($validator->fails()) {
            throw new BusinessValidationException($validator->errors()->first('credit_hours'));
        }
    }
} 