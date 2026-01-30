<?php

namespace App\Services\AvailableCourse;

use Illuminate\Database\Eloquent\Builder;
use App\Models\AvailableCourse;
use App\Models\AvailableCourseSchedule;
use App\Models\EnrollmentSchedule;
use App\Models\User;
use App\Exceptions\BusinessValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;
use App\Services\AvailableCourse\Operations\CreateAvailableCourseService;
use App\Traits\Progressable;
use App\Traits\Importable;
use App\Jobs\AvailableCourse\ImportAvailableCoursesJob;
use App\Models\Task;
use Exception;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GenericImportResultsExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AvailableCourseService
{
    use Progressable, Importable;

    /**
     * Create a new class instance.
     */
    public function __construct(
        private CreateAvailableCourseService $createService
    ){}

    /**
     * Configure import for available courses.
     */
    protected function getImportConfig(): array
    {
        return [
            'job' => ImportAvailableCoursesJob::class,
            'subtype' => 'available_course',
            'download_route' => 'available_courses.import.download',
            'filename_prefix' => 'available_courses_import_results',
        ];
    }

     /**
     * Create a new available course.
     *
     * @param array $data
     * @return AvailableCourse|array
     * @throws BusinessValidationException
     */
    public function create(array $data)
    {
        return $this->createService->create($data);
    }

    public function delete(AvailableCourse $availableCourse): bool
    {
        $scheduleEnrollmentsCount = EnrollmentSchedule::whereHas('availableCourseSchedule', function($q) use($availableCourse) {
            $q->where('available_course_id', $availableCourse->id);
        })->count();

        if ($scheduleEnrollmentsCount > 0) {
            throw new BusinessValidationException('Cannot delete available course with existing enrollments.');
        }

        $availableCourse->delete();

        return true;
    }

    public function getAvailableCourse(int|string $id): array
    {
        return AvailableCourse::with([
            'eligibilities.program',
            'eligibilities.level',
            'course',
            'term',
            'schedules.scheduleAssignments.scheduleSlot'
        ])->findOrFail($id)->toArray();
    }

     /**
     * Get available course statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        $stats = AvailableCourse::selectRaw('
            COUNT(*) as total,
            COUNT(CASE WHEN mode = "universal" THEN 1 END) as universal,
            MAX(updated_at) as latest,
            MAX(CASE WHEN mode = "universal" THEN updated_at END) as universal_latest
        ')->first();

        return [
            'available-courses' => [
                'count' => formatNumber($stats->total),
                'lastUpdateTime' => formatDate($stats->latest),
            ],
            'universal-courses' => [
                'count' => formatNumber($stats->universal),
                'lastUpdateTime' => formatDate($stats->universal_latest),
            ],
        ];
    }

     /**
     *
     * @return JsonResponse
     */
    public function getDatatable(): JsonResponse
    {
        
        $query = AvailableCourse::with(['course', 'term', 'eligibilities.program', 'eligibilities.level', 'schedules']);

        $this->applySearchFilters($query);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('course', function ($availableCourse) {
                return $availableCourse->course?->name ?? '-';
            })
            ->addColumn('term', function ($availableCourse) {
                return $availableCourse->term?->name ?? '-';
            })
            ->addColumn('schedules', function ($availableCourse) {
                return $availableCourse->schedules->count();
            })
            ->addColumn('eligibilities', function ($availableCourse) {
                return $availableCourse->eligibilities->count();
            })
            ->addColumn('enrollments', function ($availableCourse) {
                return $availableCourse->enrollments->count();
            })
            ->addColumn('action', function ($availableCourse) {
                return $this->renderActionButtons($availableCourse);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    
    /**
     * Apply filters.
     *
     * @param Builder<AvailableCourse> $query
     * @return Builder<AvailableCourse>
     */
    private function applySearchFilters(Builder $query): Builder
    {
        if ($searchTerm = request('search_term')) {
            $query->whereHas('term', function($q) use($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%");
            });
        }

        if ($searchCourse = request('search_course')) {
            $query->whereHas('course', function($q) use($searchCourse) {
                $q->where('title', 'LIKE', "%{$searchCourse}%");
            });
        }

        return $query;
    }

      /**
     * Render action buttons.
     *
     * @param AvailableCourse $availableCourse
     * @return string
     */
    protected function renderActionButtons(AvailableCourse $availableCourse): string
    {
        $user = auth()->user();
        if (!$user) {
            return '';
        }

        $singleActions = $this->buildSingleActions($user, $availableCourse);

        if (empty($singleActions)) {
            return '';
        }

        return view('components.ui.datatable.table-actions', [
            'mode' => 'single',
            'actions' => [],
            'id' => $availableCourse->id,
            'type' => 'AvailableCourse',
            'singleActions' => $singleActions,
        ])->render();
    }

    /**
     * Build single actions.
     *
     * @param mixed $user
     * @param AvailableCourse $availableCourse
     * @return array<int, array{action: string, icon: string, class: string, label: string, data: array, modal_toggle?: string, modal_target?: string, href?: string}>
     */
    protected function buildSingleActions($user, AvailableCourse $availableCourse): array
    {
        $actions = [];

        if ($user->hasPermissionTo('available_course.edit')) {
            $actions[] = [
                'action' => 'edit',
                'icon' => 'bx bx-edit',
                'class' => 'btn-warning',
                'label' => __('Edit'),
                'href' => route('available_courses.edit', $availableCourse->id),
            ];
        }

        if ($user->hasPermissionTo('available_course.delete')) {
            $actions[] = [
                'action' => 'delete',
                'icon' => 'bx bx-trash',
                'class' => 'btn-danger',
                'label' => __('Delete'),
                'data' => ['id' => $availableCourse->id],
            ];
        }

        if ($user->hasPermissionTo('available_course.view')) {
            $actions[] = [
                'action' => 'schedules',
                'icon' => 'bx bx-calendar',
                'class' => 'btn-secondary',
                'label' => __('Schedules'),
                'data' => ['id' => $availableCourse->id],
            ];

            $actions[] = [
                'action' => 'eligibilities',
                'icon' => 'bx bx-list-ul',
                'class' => 'btn-info',
                'label' => __('Eligibilities'),
                'data' => ['id' => $availableCourse->id],
            ];
        }

        return $actions;
    }

    /**
     * Get available courses for a specific student and term.
     *
     * @param int $studentId
     * @param int $termId
     * @param bool $exceptionForDifferentLevels
     * @return Collection
     */
    public function getAvailableCoursesByStudent(int $studentId, int $termId, bool $exceptionForDifferentLevels = false): Collection
    {
        $student = \App\Models\Student::findOrFail($studentId);
        $programId = $student->program_id;
        $levelId = $student->level_id;

        $availableCourses = AvailableCourse::available($programId, $levelId, $termId, $exceptionForDifferentLevels)
            ->notEnrolled($studentId, $termId)
            ->with(['course', 'eligibilities', 'schedules'])
            ->get();

        return $availableCourses->map(function ($availableCourse) use ($programId, $levelId) {
            $eligibilitiesForStudent = $availableCourse->eligibilities->filter(function ($elig) use ($programId, $levelId) {
                return $elig->program_id == $programId && $elig->level_id == $levelId;
            })->values();

            $groups = $eligibilitiesForStudent->pluck('group')->unique()->values()->all();

            $scheduleRemains = [];
            $totalRemaining = 0;
            foreach ($availableCourse->schedules as $sched) {
                if ($sched->max_capacity !== null && $sched->max_capacity !== '') {
                    $enrolledCount = EnrollmentSchedule::where('available_course_schedule_id', $sched->id)
                        ->whereHas('enrollment', function ($q) use ($availableCourse) {
                            $q->where('term_id', $availableCourse->term_id);
                        })->count();
                    $rem = (int)$sched->max_capacity - $enrolledCount;
                    if ($rem > 0) {
                        $totalRemaining += $rem;
                    }
                }
            }

            $remainingCapacityValue = $totalRemaining > 0 ? $totalRemaining : $availableCourse->remaining_capacity;

            return [
                'id' => $availableCourse->course->id,
                'name' => $availableCourse->course->name,
                'code' => $availableCourse->course->code,
                'groups' => $groups,
                'credit_hours' => $availableCourse->course->credit_hours,
                'available_course_id' => $availableCourse->id,
                'remaining_capacity' => $remainingCapacityValue,
            ];
        });
    }

}


