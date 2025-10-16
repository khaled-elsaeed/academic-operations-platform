<?php

namespace App\Services\Enrollment;

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
use App\Services\Enrollment\Operations\ImportEnrollmentService;
use App\Services\SettingService;

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
    protected SettingService $settingService;

    public function __construct(CreditHoursExceptionService $creditHoursExceptionService, SettingService $settingService)
    {
        $this->creditHoursExceptionService = $creditHoursExceptionService;
        $this->settingService = $settingService;
    }

    public function createEnrollment(array $data): Enrollment
    {
        $this->actionAvailable('create');

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
        $this->actionAvailable('create');

        return DB::transaction(function () use ($data) {
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
            // Pass optional course_schedule_mapping from input for schedule-level capacity checks
            $this->validateAvailableCourseScheduleCapacity($availableCourses, $term->id, $data['course_schedule_mapping'] ?? null);
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


     /**
     * Create enrollments for the without_schedule (grade-only) flow.
     * Expects 'enrollment_data' => [ ['course_id' => int, 'term_id' => int, 'grade' => string|null], ... ]
     *
     * @param array $data
     * @return array
     * @throws BusinessValidationException
     */
    public function createEnrollmentsWithoutSchedule(array $data): array
    {
        $this->actionAvailable('create');

        return DB::transaction(function () use ($data) {
            $student = Student::findOrFail($data['student_id']);
            $created = [];

            foreach ($data['enrollment_data'] as $row) {
                // Normalize keys
                $courseId = $row['course_id'] ?? null;
                $termId = $row['term_id'] ?? null;
                $grade = $row['grade'] ?? null;

                if (!$courseId || !$termId) {
                    throw new BusinessValidationException('Invalid enrollment row: missing course or term.');
                }

                // Prevent duplicate
                $exists = Enrollment::where('student_id', $student->id)
                    ->where('course_id', $courseId)
                    ->where('term_id', $termId)
                    ->exists();

                if ($exists) {
                    $courseName = Course::find($courseId)?->name ?? 'this course';
                    throw new BusinessValidationException("Student already enrolled in {$courseName} for the selected term.");
                }

                // Create the enrollment record with grade (grade-only flow)
                $enrollment = Enrollment::create([
                    'student_id' => $student->id,
                    'course_id' => $courseId,
                    'term_id' => $termId,
                    'grade' => $grade,
                ]);

                $created[] = $enrollment;
            }

            return $created;
        });
    }

    public function deleteEnrollment(Enrollment $enrollment): void
    {
        $this->actionAvailable('delete');
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
        $student->taken_hours = $student->taken_credit_hours;

        return $student;
    }

    /**
     * Import enrollments from an uploaded Excel file.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function importEnrollments(UploadedFile $file): array
    {
        $this->actionAvailable('create');

        $import = new ImportEnrollmentService();

        return $import->importEnrollmentsFromFile($file);
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
     * Validate available course and schedule capacities before creating enrollments.
     *
     * @param array $availableCourses Array of AvailableCourse models
     * @param int $termId
     * @param array|string|null $courseScheduleMapping Optional mapping of available_course_id => JSON-encoded array of available_course_schedule_ids
     * @throws BusinessValidationException
     */
    private function validateAvailableCourseScheduleCapacity(array $availableCourses, int $termId, $courseScheduleMapping = null): void
    {
        // Normalize mapping and build counters
        $mapping = $this->normalizeCourseScheduleMapping($courseScheduleMapping);
        $requestedSeatsPerSchedule = $this->buildRequestedSeatsCounters($mapping);

        // We no longer enforce course-level max_capacity here. Capacity is enforced on schedules only.
        foreach ($availableCourses as $availableCourse) {
            if (!($availableCourse instanceof AvailableCourse)) {
                $availableCourse = AvailableCourse::find($availableCourse);
                if (!$availableCourse) continue;
            }

            // If mapping includes this available course, validate the selected schedules
            if (is_array($mapping) && isset($mapping[$availableCourse->id])) {
                $selected = $mapping[$availableCourse->id];
                if (is_string($selected)) {
                    $selected = json_decode($selected, true) ?: [];
                }

                if (is_array($selected) && count($selected) > 0) {
                    $this->validateScheduleCapacities($selected, $termId, $availableCourse, $requestedSeatsPerSchedule);
                }
            }
        }
    }

    /**
     * Normalize the incoming course schedule mapping to an associative array
     * keyed by available_course_id => array of schedule ids.
     *
     * @param array|string|null $mapping
     * @return array|null
     */
    private function normalizeCourseScheduleMapping($mapping): ?array
    {
        if (is_null($mapping)) return null;
        if (is_string($mapping)) {
            $decoded = json_decode($mapping, true);
            $mapping = is_array($decoded) ? $decoded : null;
        }

        if (!is_array($mapping)) return null;

        // Ensure each value is an array of ints
        $normalized = [];
        foreach ($mapping as $acId => $payload) {
            $items = $payload;
            if (is_string($items)) {
                $items = json_decode($items, true) ?: [];
            }
            if (!is_array($items)) continue;
            $normalized[intval($acId)] = array_map('intval', array_values($items));
        }

        return $normalized;
    }

    /**
     * Build counters of requested seats per schedule id from the mapping.
     *
     * @param array|null $mapping
     * @return array
     */
    private function buildRequestedSeatsCounters(?array $mapping): array
    {
        $requested = [];
        if (!is_array($mapping)) return $requested;

        foreach ($mapping as $acId => $scheduleIds) {
            if (!is_array($scheduleIds)) continue;
            foreach ($scheduleIds as $sid) {
                $id = intval($sid);
                $requested[$id] = ($requested[$id] ?? 0) + 1;
            }
        }

        return $requested;
    }

    /**
     * Validate schedule capacities for a given available course and the selected schedule ids.
     * Throws BusinessValidationException on failure.
     *
     * @param array $selectedScheduleIds
     * @param int $termId
     * @param AvailableCourse $availableCourse
     * @param array $requestedSeatsPerSchedule
     * @throws BusinessValidationException
     */
    private function validateScheduleCapacities(array $selectedScheduleIds, int $termId, AvailableCourse $availableCourse, array $requestedSeatsPerSchedule): void
    {
        foreach ($selectedScheduleIds as $schedId) {
            $schedId = intval($schedId);
            $acs = \App\Models\AvailableCourseSchedule::find($schedId);
            if (!$acs) {
                throw new BusinessValidationException("Selected schedule (ID: {$schedId}) is invalid for available course ID {$availableCourse->id}.");
            }

            if ($acs->available_course_id != $availableCourse->id) {
                throw new BusinessValidationException("Selected schedule (ID: {$schedId}) does not belong to available course ID {$availableCourse->id}.");
            }

            // Count enrolled students for this schedule within the same term
            $enrolledCount = EnrollmentSchedule::where('available_course_schedule_id', $acs->id)
                ->whereHas('enrollment', function ($q) use ($termId) {
                    $q->where('term_id', $termId);
                })->count();

            $requestedForThisSchedule = $requestedSeatsPerSchedule[$acs->id] ?? 0;

            if ($acs->max_capacity !== null && $acs->max_capacity !== '') {
                $cap = (int) $acs->max_capacity;
                if (($enrolledCount + $requestedForThisSchedule) > $cap) {
                    throw new BusinessValidationException("Selected schedule for Group {$acs->group} (Schedule ID: {$acs->id}) does not have enough seats. Remaining: " . max(0, $cap - $enrolledCount));
                }
            }
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
        $enrollmentSchedules = EnrollmentSchedule::with([
            'enrollment.course',           
            'availableCourseSchedule.availableCourse', 
            'availableCourseSchedule.scheduleAssignments.scheduleSlot' 
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

    /**
     * Check whether a given action is available according to system settings.
     * Throws BusinessValidationException when the action is not allowed.
     *
     * @param string $action 'create'|'delete'|other
     * @return bool
     * @throws BusinessValidationException
     */
    private function actionAvailable($action)
    {
        $settings = $this->settingService->getEnrollmentSettings();

        $enabled = isset($settings['enable_enrollment']) ? (int) $settings['enable_enrollment'] : 0;
        if ($enabled !== 1) {
            throw new BusinessValidationException('Enrollment editing is not available now.');
        }

        if ($action === 'create') {
            $allowed = isset($settings['allow_create_enrollment']) ? (int) $settings['allow_create_enrollment'] : 0;
            if ($allowed !== 1) {
                throw new BusinessValidationException('Creating enrollments is currently disabled.');
            }
        }

        if ($action === 'delete') {
            $allowed = isset($settings['allow_delete_enrollment']) ? (int) $settings['allow_delete_enrollment'] : 0;
            if ($allowed !== 1) {
                throw new BusinessValidationException('Deleting enrollments is currently disabled.');
            }
        }

        return true;
    }
}