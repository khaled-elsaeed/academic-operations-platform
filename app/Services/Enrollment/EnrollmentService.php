<?php

namespace App\Services\Enrollment;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Course;
use App\Models\Term;
use App\Models\AvailableCourse;
use App\Models\CreditHoursException;
use App\Models\EnrollmentSchedule;
use App\Exports\EnrollmentsTemplateExport;
use App\Exceptions\BusinessValidationException;
use App\Rules\AcademicAdvisorAccessRule;
use App\Services\CreditHoursExceptionService;
use App\Services\SettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\Progressable;
use App\Traits\Importable;
use App\Traits\Exportable;
use App\Jobs\Enrollment\ImportEnrollmentsJob;
use App\Jobs\Enrollment\ExportEnrollmentsJob;
use App\Models\Task;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Exports\GenericImportResultsExport;
use App\Policies\FeatureAvailabilityPolicy;
use App\Services\Enrollment\Operations\CreateEnrollmentService;

class EnrollmentService
{
    use Progressable, Importable, Exportable;

    protected CreditHoursExceptionService $creditHoursExceptionService;
    protected FeatureAvailabilityPolicy $featureAvailabilityPolicy;
    protected CreateEnrollmentService $createService;

    public function __construct(CreditHoursExceptionService $creditHoursExceptionService, FeatureAvailabilityPolicy $featureAvailabilityPolicy,CreateEnrollmentService $createService)
    {
        $this->creditHoursExceptionService = $creditHoursExceptionService;
        $this->featureAvailabilityPolicy = $featureAvailabilityPolicy;
        $this->createService = $createService;
    }

    /**
     * Configure import for enrollments.
     */
    protected function getImportConfig(): array
    {
        return [
            'job' => ImportEnrollmentsJob::class,
            'subtype' => 'enrollment',
            'download_route' => 'enrollments.import.download',
            'filename_prefix' => 'enrollments_import_results',
        ];
    }

    /**
     * Configure export for enrollments.
     */
    protected function getExportConfig(): array
    {
        return [
            'job' => ExportEnrollmentsJob::class,
            'subtype' => 'enrollment',
            'download_route' => 'enrollments.export.download',
        ];
    }

    /**
     * Export enrollments (wrapper for the trait method).
     */
    public function exportEnrollments(array $data = []): array
    {
        return $this->export($data);
    }

    public function create(array $data): array
    {
        return $this->createService->create(
            (int) $data['student_id'],
            (int) $data['term_id'],
            $data['enrollments']
        )->toArray();
    }


    

    public function deleteEnrollment(Enrollment $enrollment): void
    {
        $this->featureAvailabilityPolicy->checkAvailable('enrollment', 'delete');
        $enrollment->delete();
    }

    public function getStats(): array
    {
        $latest = Enrollment::latest('updated_at')->value('updated_at');
        $totalEnrollments = Enrollment::count();
        
        return [
            'enrollments' => [
                'count' => formatNumber($totalEnrollments),
                'lastUpdateTime' => formatDate($latest),
            ]
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

        $currentEnrollmentHours = $this->getCurrentEnrollmentHours($studentId, $termId);

        $maxAllowedHours = $this->getMaxCreditHours($student, $term);

        $remainingHours = $maxAllowedHours - $currentEnrollmentHours;

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

    public function getEnrollmentsByStudent(int $studentId): array
    {
        $enrollments = Enrollment::with(['course', 'term', 'schedules.availableCourseSchedule.scheduleAssignments.scheduleSlot'])
            ->where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->get()->toArray();

        return $enrollments;
    }

     /**
     * Get schedules for a student in a specific term.
     *
     * @param int $studentId
     * @param int $termId
     * @return array
     */
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
        
            $scheduleSlots = $availableCourseSchedule->scheduleAssignments
                ->pluck('scheduleSlot')
                ->sortBy(['day_of_week', 'start_time']);
                
            if ($scheduleSlots->isEmpty()) {
                \Log::warning("No schedule slots found for available course schedule: {$availableCourseSchedule->id}");
                continue;
            }
            
            $enrolledCount = EnrollmentSchedule::where('available_course_schedule_id', $availableCourseSchedule->id)->count();
            
            $slotsByDay = $scheduleSlots->groupBy('day_of_week');
        
            foreach ($slotsByDay as $dayOfWeek => $daySlotsCollection) {
                $daySlots = $daySlotsCollection->sortBy('start_time');
                $firstSlot = $daySlots->first();
                $lastSlot = $daySlots->last();
                
                $schedules[] = [
                    'course' => [
                        'id' => $course->id,
                        'name' => $course->name,
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