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
use App\Services\Enrollment\Operations\RemainingCreditHoursService;
use Illuminate\Database\Eloquent\Builder;


class EnrollmentService
{
    use Progressable, Importable, Exportable;

    protected CreditHoursExceptionService $creditHoursExceptionService;
    protected FeatureAvailabilityPolicy $featureAvailabilityPolicy;
    protected CreateEnrollmentService $createService;
    protected RemainingCreditHoursService $remainingCreditHoursService;

    public function __construct(CreditHoursExceptionService $creditHoursExceptionService, FeatureAvailabilityPolicy $featureAvailabilityPolicy, CreateEnrollmentService $createService, RemainingCreditHoursService $remainingCreditHoursService)
    {
        $this->creditHoursExceptionService = $creditHoursExceptionService;
        $this->featureAvailabilityPolicy = $featureAvailabilityPolicy;
        $this->createService = $createService;
        $this->remainingCreditHoursService = $remainingCreditHoursService;
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
        $stats = Enrollment::selectRaw('
            COUNT(*) as total,
            COUNT(CASE WHEN grade IS NOT NULL THEN 1 END) as graded,
            MAX(updated_at) as latest,
            MAX(CASE WHEN grade IS NOT NULL THEN updated_at END) as graded_latest
        ')->first();

        return [
            'enrollments' => [
                'count' => formatNumber($stats->total),
                'lastUpdateTime' => formatDate($stats->latest),
            ],
            'graded-enrollments' => [
                'count' => formatNumber($stats->graded),
                'lastUpdateTime' => formatDate($stats->graded_latest),
            ],
        ];
    }

    public function getDatatable(): \Illuminate\Http\JsonResponse
    {
        $query = Enrollment::with(['student', 'course', 'term']);

        $query = $this->applySearchFilters($query);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('student', function($enrollment) {
                return $enrollment->student?->name_en ?? '-';
            })
            ->addColumn('course', function($enrollment) {
                return $enrollment->course?->title && $enrollment->course?->code ? "{$enrollment->course->title} ({$enrollment->course->code})" : '-';
            })
            ->addColumn('term', function($enrollment) {
                return $enrollment->term?->season && $enrollment->term?->year ? "{$enrollment->term->season} {$enrollment->term->year}" : '-';
            })
            ->addColumn('grade', function($enrollment) {
                return $enrollment->grade ?? "No Grade Yet" ;
            })
            ->addColumn('action', function($enrollment) {
                return $this->renderActionButtons($enrollment);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Apply filters.
     *
     * @param Builder<Enrollment> $query
     * @return Builder<Enrollment>
     */
    private function applySearchFilters(Builder $query): Builder
    {
        if ($searchStudent = request('search_student')) {
            $query->whereHas('student', function($q) use($searchStudent) {
                $q->where('name_en', 'LIKE', "%{$searchStudent}%")
                  ->orWhere('academic_id', 'LIKE', "%{$searchStudent}%");
            });
        }

        if ($searchCourse = request('search_course')) {
            $query->whereHas('course', function($q) use($searchCourse) {
                $q->where('title', 'LIKE', "%{$searchCourse}%")
                  ->orWhere('code', 'LIKE', "%{$searchCourse}%");
            });
        }

        if ($searchTerm = request('search_term')) {
            $query->whereHas('term', function($q) use($searchTerm) {
                $q->where('season', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('year', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('code', 'LIKE', "%{$searchTerm}%");
            });
        }

        if ($searchGrade = request('search_grade')) {
            if ($searchGrade === 'no-grade') {
                $query->whereNull('grade');
            } else {
                $query->where('grade', $searchGrade);
            }
        }

        return $query;
    }

    /**
     * Render action buttons.
     *
     * @param Enrollment $enrollment
     * @return string
     */
    protected function renderActionButtons(Enrollment $enrollment): string
    {
        $user = auth()->user();
        if (!$user) {
            return '';
        }

        $singleActions = $this->buildSingleActions($user, $enrollment);

        if (empty($singleActions)) {
            return '';
        }

        return view('components.ui.datatable.table-actions', [
            'mode' => 'single',
            'actions' => [],
            'id' => $enrollment->id,
            'type' => 'Enrollment',
            'singleActions' => $singleActions,
        ])->render();
    }

    /**
     * Build single actions.
     *
     * @param mixed $user
     * @param Enrollment $enrollment
     * @return array<int, array{action: string, icon: string, class: string, label: string, data: array}>
     */
    protected function buildSingleActions($user, Enrollment $enrollment): array
    {
        $actions = [];

        if ($user->can('enrollment.delete')) {
            $actions[] = [
                'action' => 'delete',
                'icon' => 'bx bx-trash',
                'class' => 'btn-danger',
                'label' => __('Delete'),
                'data' => ['id' => $enrollment->id],
            ];
        }

        return $actions;
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
        return $this->remainingCreditHoursService->getRemainingCreditHoursForStudent($studentId, $termId);
    }



    public function getEnrollmentsByStudent(int $studentId): array
    {
        $enrollments = Enrollment::with(['course', 'term', 'schedules.availableCourseSchedule.scheduleAssignments.scheduleSlot'])
            ->where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->get()->toArray();

        return $enrollments;
    }
}