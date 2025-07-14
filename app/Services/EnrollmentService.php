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
        \Log::debug('createEnrollments called', [
            'student_id' => $data['student_id'],
            'term_id' => $data['term_id'],
            'available_course_ids' => $data['available_course_ids'] ?? [],
        ]);
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
            \Log::debug('Requested credit hours calculated', [
                'requestedCreditHours' => $requestedCreditHours,
                'courseIds' => $courseIds,
            ]);
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
        $latest = Enrollment::latest('updated_at')->value('updated_at');
        $totalStudents = \App\Models\Student::count();
        $totalEnrollments = Enrollment::count();
        $uniqueEnrolledStudents = Enrollment::distinct('student_id')->count();
        
        return [
            'enrollments' => [
                'total' => $totalEnrollments,
                'lastUpdateTime' => formatDate($latest),
            ],
            'students' => [
                'total' => $totalStudents,
                'enrolled' => $uniqueEnrolledStudents,
                'lastUpdateTime' => formatDate($latest),
            ],
        ];
    }

    public function getDatatable(): \Illuminate\Http\JsonResponse
    {
        $query = Enrollment::join('students', 'enrollments.student_id', '=', 'students.id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->join('terms', 'enrollments.term_id', '=', 'terms.id')
            ->select(
                'enrollments.*',
                'students.name_en as student_name',
                'students.academic_id as student_academic_id',
                'courses.title as course_title',
                'courses.code as course_code',
                'terms.season as term_season',
                'terms.year as term_year'
            );

        $request = request();

        $this->applySearchFilters($query, $request);

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
            ->addColumn('grade', function($enrollment) {
                if ($enrollment->grade === null) {
                    return '<span class="badge bg-label-secondary"><i class="bx bx-time me-1"></i>No Grade</span>';
                }
                $badgeClass = match($enrollment->grade) {
                    'A+', 'A' => 'bg-label-success',
                    'A-', 'B+', 'B' => 'bg-label-primary',
                    'B-', 'C+', 'C' => 'bg-label-warning',
                    'C-', 'D+', 'D' => 'bg-label-info',
                    'F', 'FL', 'FD' => 'bg-label-danger',
                    'P' => 'bg-label-success',
                    'AU' => 'bg-label-info',
                    'W' => 'bg-label-secondary',
                    'I' => 'bg-label-warning',
                    default => 'bg-label-secondary',
                };
                return "<span class='badge {$badgeClass}'><i class='bx bx-star me-1'></i>{$enrollment->grade}</span>";
            })
            ->addColumn('action', function($enrollment) {
                return $this->renderActionButtons($enrollment);
            })
            ->orderColumn('student', 'students.name_en $1')
            ->orderColumn('course', 'courses.title $1')
            ->orderColumn('term', 'terms.season $1, terms.year $1')
            ->orderColumn('grade', 'enrollments.grade $1')
            ->rawColumns(['action', 'grade'])
            ->make(true);
    }

    private function applySearchFilters($query, $request): void
    {
        // Apply search filters
        if ($request->has('search_student') && !empty($request->input('search_student'))) {
            $searchStudent = $request->input('search_student');
            $query->where(function($q) use ($searchStudent) {
                $q->where('students.name_en', 'like', "%{$searchStudent}%")
                  ->orWhere('students.academic_id', 'like', "%{$searchStudent}%");
            });
        }
        
        if ($request->has('search_course') && !empty($request->input('search_course'))) {
            $searchCourse = $request->input('search_course');
            $query->where(function($q) use ($searchCourse) {
                $q->where('courses.title', 'like', "%{$searchCourse}%")
                  ->orWhere('courses.code', 'like', "%{$searchCourse}%");
            });
        }
        
        if ($request->has('search_term') && !empty($request->input('search_term'))) {
            $searchTerm = $request->input('search_term');
            $query->where(function($q) use ($searchTerm) {
                $q->where('terms.season', 'like', "%{$searchTerm}%")
                  ->orWhere('terms.year', 'like', "%{$searchTerm}%")
                  ->orWhere('terms.code', 'like', "%{$searchTerm}%");
            });
        }
        
        if ($request->has('search_grade') && !empty($request->input('search_grade'))) {
            $searchGrade = $request->input('search_grade');
            if ($searchGrade === 'no-grade') {
                $query->whereNull('enrollments.grade');
            } else {
                $query->where('enrollments.grade', $searchGrade);
            }
        }
    }

    protected function renderActionButtons($enrollment)
    {
        $user = auth()->user();
        $buttons = '<div class="d-flex gap-2">';
        if ($user && $user->can('enrollment.delete')) {
            $buttons .= '<button type="button"
                class="btn btn-sm btn-icon btn-danger rounded-circle deleteEnrollmentBtn"
                data-id="' . e($enrollment->id) . '"
                title="Delete">
                <i class="bx bx-trash"></i>
              </button>';
        }
        $buttons .= '</div>';
        return trim($buttons) === '<div class="d-flex gap-2"></div>' ? '' : $buttons;
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

        $enrollment = $this->createOrUpdateEnrollment($row, $student, $course, $term);

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
        \Log::debug('validateCreditHoursLimit', [
            'student_id' => $student->id,
            'term_id' => $term->id,
            'currentCreditHours' => $currentCreditHours,
            'requestedCreditHours' => $requestedCreditHours,
            'totalCreditHours' => $totalCreditHours,
            'validator_value_passed' => $totalCreditHours,
        ]);
        // Check against the credit hours limit rule
        $validator = \Validator::make(
            ['credit_hours' => $totalCreditHours],
            ['credit_hours' => [new EnrollmentCreditHoursLimit($student->id, $term->id)]]
        );
        if ($validator->fails()) {
            \Log::debug('Credit hour validation failed', [
                'errors' => $validator->errors()->all(),
            ]);
            throw new BusinessValidationException($validator->errors()->first('credit_hours'));
        }
    }
} 