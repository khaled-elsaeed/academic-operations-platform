<?php

namespace App\Services;

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
            ->addColumn('score', function($enrollment) {
                return $enrollment->score ? number_format($enrollment->score, 2) : '-';
            })
            ->addColumn('action', function($enrollment) {
                return $this->renderActionButtons($enrollment);
            })
            ->orderColumn('student', 'students.name_en $1')
            ->orderColumn('course', 'courses.title $1')
            ->orderColumn('term', 'terms.season $1, terms.year $1')
            ->orderColumn('score', 'enrollments.score $1')
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

        // Load the taken hours attribute
        $student->load('enrollments.course');
        $student->taken_hours = $student->taken_hours;

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
            $rows = Excel::toArray(new EnrollmentsImport, $file);
            $data = $rows[0] ?? [];
            
            // Remove header row
            array_shift($data);
            
            return $this->importEnrollmentsFromRows(collect($data));
        } catch (\Exception $e) {
            Log::error('Enrollment import failed', ['error' => $e->getMessage()]);
            throw new BusinessValidationException('Failed to read the uploaded file. Please ensure it is a valid Excel file.');
        }
    }

    /**
     * Import enrollments from rows of data.
     *
     * @param Collection $rows
     * @return array
     */
    public function importEnrollmentsFromRows(Collection $rows): array
    {
        $importedCount = 0;
        $errors = [];
        
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 because we removed header and arrays are 0-indexed
            
            try {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $result = $this->processImportRow($row, $rowNum);
                if ($result === 'success') {
                    $importedCount++;
                } else {
                    $errors[] = $result;
                }
            } catch (\Exception $e) {
                $errors[] = "Row {$rowNum}: " . $e->getMessage();
            }
        }
        
        $message = "Import completed. {$importedCount} enrollments imported successfully.";
        if (!empty($errors)) {
            $message .= " " . count($errors) . " errors occurred.";
        }
        
        return [
            'imported_count' => $importedCount,
            'errors' => $errors,
            'message' => $message
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
        // Validate required fields
        if (empty($row[0]) || empty($row[1]) || empty($row[2])) {
            return "Row {$rowNum}: Missing required fields (Academic ID, Course Code, Term Code)";
        }
        
        $academicId = trim($row[0]);
        $courseCode = trim($row[1]);
        $termCode = trim($row[2]);
        
        try {
            // Find student
            $student = $this->findStudentByAcademicId($academicId);
            if (!$student) {
                return "Row {$rowNum}: Student with Academic ID '{$academicId}' not found";
            }
            
            // Find course
            $course = $this->findCourseByCode($courseCode);
            if (!$course) {
                return "Row {$rowNum}: Course with code '{$courseCode}' not found";
            }
            
            // Find term
            $term = $this->findTermByCode($termCode);
            if (!$term) {
                return "Row {$rowNum}: Term with code '{$termCode}' not found";
            }
            
            // Check if enrollment already exists
            $existingEnrollment = Enrollment::where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->where('term_id', $term->id)
                ->first();
                
            if ($existingEnrollment) {
                return "Row {$rowNum}: Student is already enrolled in this course for the specified term";
            }
            
            // Create enrollment
            Enrollment::create([
                'student_id' => $student->id,
                'course_id' => $course->id,
                'term_id' => $term->id,
            ]);
            
            return 'success';
        } catch (\Exception $e) {
            return "Row {$rowNum}: " . $e->getMessage();
        }
    }

    /**
     * Find a student by academic ID.
     *
     * @param string $academicId
     * @return Student|null
     */
    private function findStudentByAcademicId(string $academicId): ?Student
    {
        return Student::withoutGlobalScopes()
            ->where('academic_id', $academicId)
            ->first();
    }

    /**
     * Find a course by code.
     *
     * @param string $code
     * @return Course|null
     */
    private function findCourseByCode(string $code): ?Course
    {
        return Course::where('code', $code)->first();
    }

    /**
     * Find a term by code.
     *
     * @param string $code
     * @return Term|null
     */
    private function findTermByCode(string $code): ?Term
    {
        return Term::where('code', $code)->first();
    }

    /**
     * Import enrollments from an uploaded file.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function importEnrollments(UploadedFile $file): array
    {
        return $this->importEnrollmentsFromFile($file);
    }

    /**
     * Download the enrollments import template.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadTemplate()
    {
        return Excel::download(new EnrollmentsTemplateExport, 'enrollments_template.xlsx');
    }

    /**
     * Validate credit hours limit for a student in a term.
     *
     * @param Student $student
     * @param Term $term
     * @param int $requestedCreditHours
     * @throws BusinessValidationException
     */
    private function validateCreditHoursLimit(Student $student, Term $term, int $requestedCreditHours): void
    {
        // Get current enrollments for this student in this term
        $currentEnrollments = Enrollment::where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->with('course')
            ->get();
        
        $currentCreditHours = $currentEnrollments->sum('course.credit_hours');
        $totalCreditHours = $currentCreditHours + $requestedCreditHours;
        
        // Check against the credit hours limit rule
        $validator = \Validator::make(
            ['credit_hours' => $totalCreditHours],
            ['credit_hours' => [new EnrollmentCreditHoursLimit($student, $term)]]
        );
        
        if ($validator->fails()) {
            throw new BusinessValidationException($validator->errors()->first('credit_hours'));
        }
    }
} 