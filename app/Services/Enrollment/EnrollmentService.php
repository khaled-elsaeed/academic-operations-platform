<?php

declare(strict_types=1);

namespace App\Services\Enrollment;

use App\Exceptions\BusinessValidationException;
use App\Jobs\Enrollment\ExportEnrollmentDocumentsJob;
use App\Jobs\Enrollment\ExportEnrollmentsJob;
use App\Jobs\Enrollment\ImportEnrollmentsJob;
use App\Models\AvailableCourseSchedule;
use App\Models\Enrollment;
use App\Models\Schedule\ScheduleAssignment;
use App\Models\User;
use App\Policies\FeatureAvailabilityPolicy;
use App\Services\CreditHoursExceptionService;
use App\Services\Enrollment\Operations\CreateEnrollmentService;
use App\Services\Enrollment\Operations\RemainingCreditHoursService;
use App\Services\EnrollmentDocumentService;
use App\Traits\{Exportable, Importable, Progressable};
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Cache;
use App\Models\Student;


class EnrollmentService
{
    use Progressable;
    use Importable {
        import as traitImport;
        getImportStatus as traitGetImportStatus;
        downloadImport as traitDownloadImport;
    }
    use Exportable {
        export as traitExport;
        getExportStatus as traitGetExportStatus;
        downloadExport as traitDownloadExport;
    }

    /**
     * @var CreditHoursExceptionService Service for managing credit hours exceptions
     */
    protected CreditHoursExceptionService $creditHoursExceptionService;

    /**
     * @var FeatureAvailabilityPolicy Policy for checking feature availability
     */
    protected FeatureAvailabilityPolicy $featureAvailabilityPolicy;

    /**
     * @var CreateEnrollmentService Service for creating enrollments
     */
    protected CreateEnrollmentService $createService;

    /**
     * @var RemainingCreditHoursService Service for calculating remaining credit hours
     */
    protected RemainingCreditHoursService $remainingCreditHoursService;

    /**
     * @var EnrollmentDocumentService Service for generating enrollment documents
     */
    protected EnrollmentDocumentService $enrollmentDocumentService;

    /**
     * EnrollmentService constructor.
     *
     * @param CreditHoursExceptionService $creditHoursExceptionService
     * @param FeatureAvailabilityPolicy $featureAvailabilityPolicy
     * @param CreateEnrollmentService $createService
     * @param RemainingCreditHoursService $remainingCreditHoursService
     * @param EnrollmentDocumentService $enrollmentDocumentService
     */
    public function __construct(
        CreditHoursExceptionService $creditHoursExceptionService,
        FeatureAvailabilityPolicy $featureAvailabilityPolicy,
        CreateEnrollmentService $createService,
        RemainingCreditHoursService $remainingCreditHoursService,
        EnrollmentDocumentService $enrollmentDocumentService
    ) {
        $this->creditHoursExceptionService = $creditHoursExceptionService;
        $this->featureAvailabilityPolicy = $featureAvailabilityPolicy;
        $this->createService = $createService;
        $this->remainingCreditHoursService = $remainingCreditHoursService;
        $this->enrollmentDocumentService = $enrollmentDocumentService;
    }

    /**
     * Import data from file.
     *
     * @param array $data Must contain 'file' key with UploadedFile
     * @return array{task_id:int,uuid:string}
     */
    public function import(array $data): array
    {
        return $this->traitImport(
            file: $data['file'],
            jobClass: ImportEnrollmentsJob::class,
            subtype: 'enrollment',
            additionalParams: ['template' => $data['template_select'] ?? 'system']
        );
    }

    public function getImportStatus(string $uuid): ?array
    {
        return $this->traitGetImportStatus($uuid, 'enrollments.import.download');
    }

    public function downloadImport(string $uuid): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        return $this->traitDownloadImport($uuid, 'enrollments_import_results');
    }

    /**
     * Export enrollments to file.
     *
     * @param array<string, mixed> $data Optional filters and export parameters
     * @return array<string, mixed> Export task information
     */
    public function exportEnrollments(array $data = []): array
    {
        return $this->traitExport(
            jobClass: ExportEnrollmentsJob::class,
            subtype: 'enrollment',
            parameters: $data
        );
    }

     public function getExportStatus(string $uuid): ?array
    {
        return $this->traitGetExportStatus($uuid, 'enrollments.export.download');
    }

    public function downloadExport(string $uuid): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        return $this->traitDownloadExport($uuid);
    }

    /**
     * Export enrollment documents (PDFs) to ZIP file.
     *
     * @param array<string, mixed> $data Filters: term_id, academic_id, national_id, program_id, level_id
     * @return array<string, mixed> Export task information
     */
    public function exportDocuments(array $data = []): array
    {
        return $this->traitExport(
            jobClass: ExportEnrollmentDocumentsJob::class,
            subtype: 'enrollment_documents',
            parameters: $data
        );
    }

    /**
     * Get export documents task status by UUID.
     *
     * @param string $uuid Task UUID
     * @return array<string, mixed>|null
     */
    public function getExportDocumentsStatus(string $uuid): ?array
    {
        return $this->traitGetExportStatus($uuid, 'enrollments.exportDocuments.download');
    }

    /**
     * Cancel export documents task by UUID.
     *
     * @param string $uuid Task UUID
     * @return array<string, mixed>
     */
    public function cancelExportDocuments(string $uuid): array
    {
        return $this->cancelExport($uuid);
    }

    /**
     * Download completed export documents file by UUID.
     *
     * @param string $uuid Task UUID
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function downloadExportDocuments(string $uuid): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        return $this->traitDownloadExport($uuid);
    }

    /**
     * Create new enrollment(s) for a student.
     *
     * @param array<string, mixed> $data Enrollment data containing:
     *                                    - student_id: int
     *                                    - term_id: int
     *                                    - enrollments: array of course enrollments
     * @return array<string, mixed> Created enrollment data
     * @throws BusinessValidationException If validation fails
     */
    public function create(array $data): array
    {
        $this->featureAvailabilityPolicy->checkAvailable('enrollment', 'create');
        $result = $this->createService->create(
            (int) $data['student_id'],
            (int) $data['term_id'],
            $data['enrollments']
        )->toArray();

        try {
            $student = Student::find($data['student_id']);
            if ($student) {
                $document = $this->enrollmentDocumentService->generatePdf($student, (int) $data['term_id']);
                $result['document_url'] = $document['url'];
            }
        } catch (\Exception $e) {
            \Log::error('Failed to generate enrollment document', [
                'student_id' => $data['student_id'],
                'term_id' => $data['term_id'],
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    /**
     * Delete an enrollment.
     *
     * Checks feature availability before performing deletion.
     * Also decrements capacity for associated schedules.
     *
     * @param Enrollment $enrollment The enrollment to delete
     * @return void
     * @throws \App\Exceptions\FeatureNotAvailableException If delete feature is disabled
     */
    public function deleteEnrollment(Enrollment $enrollment): void
    {
        $this->featureAvailabilityPolicy->checkAvailable('enrollment', 'delete');

        // Decrement capacity for associated schedules
        $this->decrementScheduleCapacities($enrollment);

        $enrollment->delete();
    }

    /**
     * Decrement capacity counters for schedules associated with an enrollment.
     *
     * @param Enrollment $enrollment The enrollment being deleted
     * @return void
     */
    private function decrementScheduleCapacities(Enrollment $enrollment): void
    {
        $scheduleIds = $enrollment->schedules?->pluck('available_course_schedule_id')->toArray() ?? [];

        if (empty($scheduleIds)) {
            return;
        }

        foreach ($scheduleIds as $scheduleId) {
            AvailableCourseSchedule::where('id', $scheduleId)
                ->where('current_capacity', '>', 0)
                ->decrement('current_capacity', 1, ['updated_at' => now()]);

            ScheduleAssignment::where('available_course_schedule_id', $scheduleId)
                ->where('enrolled', '>', 0)
                ->decrement('enrolled', 1, ['updated_at' => now()]);
        }
    }


    /**
     * Get enrollment statistics.
     *
     * Returns total enrollments, graded enrollments, and last update times.
     *
     * @return array<string, array<string, string>> Statistics data with formatted counts and dates
     */
    public function getStats(): array
    {

        $stats = Cache::remember('enrollment_stats', now()->addMinutes(10), function () {
            return Enrollment::selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN grade IS NOT NULL THEN 1 END) as graded,
                MAX(updated_at) as latest,
                MAX(CASE WHEN grade IS NOT NULL THEN updated_at END) as graded_latest
            ')->first();
        });

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

    /**
     * Get datatable data for enrollments.
     *
     * Prepares and returns enrollment data formatted for DataTables.
     * Supports filtering by student, course, term, and grade.
     *
     * @return JsonResponse DataTables JSON response
     */
    public function getDatatable(): JsonResponse
    {
        $query = Enrollment::with(['student', 'course', 'term']);

        $query = $this->applySearchFilters($query);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('student', function (Enrollment $enrollment) {
                return $enrollment->student?->name_en ?? '-';
            })
            ->addColumn('course', function (Enrollment $enrollment) {
                return $enrollment->course?->title && $enrollment->course?->code
                    ? "{$enrollment->course->title} ({$enrollment->course->code})"
                    : '-';
            })
            ->addColumn('term', function (Enrollment $enrollment) {
                return $enrollment->term?->season && $enrollment->term?->year
                    ? "{$enrollment->term->season} {$enrollment->term->year}"
                    : '-';
            })
            ->addColumn('grade', function (Enrollment $enrollment) {
                return $enrollment->grade ?? 'No Grade Yet';
            })
            ->addColumn('action', function (Enrollment $enrollment) {
                return $this->renderActionButtons($enrollment);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Apply search filters to enrollment query.
     *
     * Filters can be applied for:
     * - Student (name_en or academic_id)
     * - Course (title or code)
     * - Term (season, year, or code)
     * - Grade (specific grade or 'no-grade')
     *
     * @param Builder<Enrollment> $query The query builder instance
     * @return Builder<Enrollment> Filtered query
     */
    private function applySearchFilters(Builder $query): Builder
    {
        // Filter by student name or academic ID
        if ($searchStudent = request('search_student')) {
            $query->whereHas('student', function (Builder $q) use ($searchStudent) {
                $q->where('name_en', 'LIKE', "%{$searchStudent}%")
                    ->orWhere('academic_id', 'LIKE', "%{$searchStudent}%");
            });
        }

        // Filter by course title or code
        if ($searchCourse = request('search_course')) {
            $query->whereHas('course', function (Builder $q) use ($searchCourse) {
                $q->where('title', 'LIKE', "%{$searchCourse}%")
                    ->orWhere('code', 'LIKE', "%{$searchCourse}%");
            });
        }

        // Filter by term season, year, or code
        if ($searchTerm = request('search_term')) {
            $query->whereHas('term', function (Builder $q) use ($searchTerm) {
                $q->where('season', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('year', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('code', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filter by grade or no grade
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
     * Render action buttons for datatable rows.
     *
     * Generates HTML for action buttons based on user permissions.
     *
     * @param Enrollment $enrollment The enrollment instance
     * @return string Rendered HTML for action buttons
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
     * Build available actions for an enrollment.
     *
     * Determines which actions are available based on user permissions.
     *
     * @param User $user The authenticated user
     * @param Enrollment $enrollment The enrollment instance
     * @return array<int, array{action: string, icon: string, class: string, label: string, data: array<string, mixed>}> Available actions
     */
    protected function buildSingleActions(User $user, Enrollment $enrollment): array
    {
        $actions = [];

        // Add delete action if user has permission
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

    /**
     * Get all enrollments for a specific student.
     *
     * Returns enrollments ordered by creation date (newest first).
     *
     * @param int $studentId The student ID
     * @return Collection<int, Enrollment> Collection of enrollments with course and term relations
     */
    public function getStudentEnrollments(int $studentId): Collection
    {
        return Enrollment::with(['course', 'term', 'schedules'])
            ->where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get remaining credit hours for a student in a specific term.
     *
     * Calculates how many credit hours a student can still enroll in
     * for the given term, considering exceptions and limits.
     *
     * @param int $studentId The student ID
     * @param int $termId The term ID
     * @return array<string, mixed> Remaining credit hours information
     * @throws Exception If calculation fails
     */
    public function getRemainingCreditHoursForStudent(int $studentId, int $termId): array
    {
        return $this->remainingCreditHoursService->getRemainingCreditHoursForStudent(
            $studentId,
            $termId
        );
    }

    /**
     * Get detailed enrollments for a student including schedules.
     *
     * Returns enrollments with full schedule information including
     * available course schedules and time slots.
     *
     * @param int $studentId The student ID
     * @return array<int, array<string, mixed>> Array of enrollment data with schedules
     */
    public function getEnrollmentsByStudent(int $studentId): array
    {
        $enrollments = Enrollment::with([
            'course',
            'term',
            'schedules.availableCourseSchedule.scheduleAssignments.scheduleSlot',
        ])
            ->where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->get()
            ->toArray();

        return $enrollments;
    }
}