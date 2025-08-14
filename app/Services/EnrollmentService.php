<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Course;
use App\Models\Term;
use App\Models\AvailableCourse;
use App\Models\CreditHoursException;
use App\Models\EnrollmentSchedule;
use App\Imports\EnrollmentsImport;
use App\Exports\EnrollmentsTemplateExport;
use App\Exceptions\BusinessValidationException;
use App\Rules\AcademicAdvisorAccessRule;
use App\Rules\EnrollmentCreditHoursLimit;
use App\Validators\EnrollmentImportValidator;
use App\Services\CreditHoursExceptionService;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

use Yajra\DataTables\DataTables;
use Maatwebsite\Excel\Facades\Excel;

class EnrollmentService
{
    protected CreditHoursExceptionService $creditHoursExceptionService;

    public function __construct(CreditHoursExceptionService $creditHoursExceptionService)
    {
        $this->creditHoursExceptionService = $creditHoursExceptionService;
    }

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
                $enrollment = $this->createEnrollment([
                    'student_id' => $data['student_id'],
                    'course_id' => $availableCourse->course_id,
                    'term_id' => $availableCourse->term_id,
                ]);
                $enrollments[] = $enrollment;
                
                // Create schedule assignments if provided
                if (isset($data['course_schedule_mapping'])) {
                    $courseMapping = $data['course_schedule_mapping'];
                    $courseId = $availableCourse->id;
                    
                    if (isset($courseMapping[$courseId])) {
                        $scheduleIds = json_decode($courseMapping[$courseId], true);
                        if (is_array($scheduleIds)) {
                            foreach ($scheduleIds as $scheduleId) {
                                EnrollmentSchedule::create([
                                    'enrollment_id' => $enrollment->id,
                                    'available_course_schedule_id' => $scheduleId,
                                    'status' => 'active'
                                ]);
                            }
                        }
                    }
                }
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
                'total' => formatNumber($totalEnrollments),
                'lastUpdateTime' => formatDate($latest),
            ],
            'students' => [
                'total' => formatNumber($totalStudents),
                'enrolled' => formatNumber($uniqueEnrolledStudents),
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
            ->addIndexColumn()
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
                return $enrollment->grade ?? "No Grade Yet" ;
            })
            ->addColumn('action', function($enrollment) {
                return $this->renderActionButtons($enrollment);
            })
            ->orderColumn('student', 'students.name_en $1')
            ->orderColumn('course', 'courses.title $1')
            ->orderColumn('term', 'terms.season $1, terms.year $1')
            ->orderColumn('grade', 'enrollments.grade $1')
            ->rawColumns(['action'])
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
     * Export enrollments for a selected academic term, program, and level.
     *
     * @param int|null $termId
     * @param int|null $programId
     * @param int|null $levelId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportEnrollments($termId = null, $programId = null, $levelId = null)
    {
        $export = new \App\Exports\EnrollmentsExport($termId, $programId, $levelId);
        $term = $termId ? \App\Models\Term::find($termId) : null;
        $filename = 'enrollments_' . ($term ? str_replace(' ', '_', strtolower($term->name)) : 'all_terms') . '_' . now()->format('Ymd_His') . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
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

    /**
     * Get available courses for a student and term.
     *
     * @param int $studentId
     * @param int $termId
     * @return array
     */
    public function getAvailableCourses(int $studentId, int $termId): array
    {
        $student = Student::findOrFail($studentId);
        $studentProgram = $student->program_id;
        $studentLevel = $student->level_id;
        $availableCourses = AvailableCourse::available($studentProgram,$studentLevel,$termId)
            ->map(function ($availableCourse) {
                return [
                    'id' => $availableCourse->id,
                    'name' => $availableCourse->course->name,
                    'course_code' => $availableCourse->course->code,
                    'min_capacity' => $availableCourse->min_capacity,
                    'max_capacity' => $availableCourse->max_capacity,
                ];
            });

        return $availableCourses->toArray();
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

        // Calculate current enrollment credit hours
        $currentEnrollmentHours = $this->getCurrentEnrollmentHours($studentId, $termId);

        $maxAllowedHours = $this->getMaxCreditHours($student, $term);

        // Calculate remaining credit hours
        $remainingHours = $maxAllowedHours - $currentEnrollmentHours;

        // Get additional hours from admin exception
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

        // Default fallback (shouldn't reach here with valid semesters)
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

public function getSchedules(int $studentId, int $termId): array
{
    \Log::info("Fetching schedules for term & student : {$termId}, {$studentId}");
    
    // Get all enrollment schedules with proper eager loading based on actual model relationships
    $enrollmentSchedules = EnrollmentSchedule::with([
        'enrollment.course',           // Get the course info through enrollment
        'availableCourseSchedule.availableCourse', // Get available course info
        'availableCourseSchedule.scheduleAssignments.scheduleSlot' // Get schedule slots through assignments
    ])
    ->whereHas('enrollment', function ($query) use ($studentId, $termId) {
        $query->where('student_id', $studentId)
              ->where('term_id', $termId);
    })
    ->get();
    
    $schedules = [];
   
    foreach ($enrollmentSchedules as $enrollmentSchedule) {
        $availableCourseSchedule = $enrollmentSchedule->availableCourseSchedule;
        $availableCourse = $availableCourseSchedule->availableCourse;
        $course = $enrollmentSchedule->enrollment->course;
       
        // Get all schedule slots for this available course schedule
        $scheduleSlots = $availableCourseSchedule->scheduleAssignments
            ->pluck('scheduleSlot')
            ->filter() // Remove null values
            ->sortBy(['day_of_week', 'start_time']);
            
        if ($scheduleSlots->isEmpty()) {
            \Log::warning("No schedule slots found for available course schedule: {$availableCourseSchedule->id}");
            continue;
        }
        
        // Calculate enrolled count for this schedule
        $enrolledCount = EnrollmentSchedule::where('available_course_schedule_id', $availableCourseSchedule->id)->count();
        
        // Group by day of week in case course meets multiple days
        $slotsByDay = $scheduleSlots->groupBy('day_of_week');
       
        foreach ($slotsByDay as $dayOfWeek => $daySlotsCollection) {
            $daySlots = $daySlotsCollection->sortBy('start_time');
            $firstSlot = $daySlots->first();
            $lastSlot = $daySlots->last();
            
            $schedules[] = [
                'course' => [
                    'id' => $course->id,
                    'name' => $course->name, // From the enrollment->course relationship
                    'code' => $course->code,
                    'credit_hours' => $course->credit_hours,
                    'available_course_id' => $availableCourse->id,
                    'remaining_capacity' => ($availableCourseSchedule->max_capacity ?? 0) - $enrolledCount,
                ],
                'activity' => [
                    'id' => $availableCourseSchedule->id,
                    'activity_type' => $availableCourseSchedule->activity_type,
                    'location' => $availableCourseSchedule->location,
                    'min_capacity' => $availableCourseSchedule->min_capacity,
                    'max_capacity' => $availableCourseSchedule->max_capacity,
                    'enrolled_count' => $enrolledCount,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => \Carbon\Carbon::parse($firstSlot->start_time)->format('h:i A'),
                    'end_time' => \Carbon\Carbon::parse($lastSlot->end_time)->format('h:i A'),
                ],
                'group' => $availableCourseSchedule->group,
            ];
        }
    }
   
    return $schedules;
}

} 