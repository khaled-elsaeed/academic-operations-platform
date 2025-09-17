<?php

namespace App\Services\AvailableCourse;

use App\Models\AvailableCourse;
use App\Models\AvailableCourseSchedule;
use App\Models\Course;
use App\Models\CourseEligibility;
use App\Models\Schedule\ScheduleAssignment;
use App\Models\Schedule\ScheduleSlot;
use App\Models\Level;
use App\Models\EnrollmentSchedule;
use App\Models\Program;
use App\Models\Term;
use App\Models\Schedule\Schedule;
use App\Exceptions\BusinessValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;
use App\Services\AvailableCourse\CreateAvailableCourseService;
use App\Services\AvailableCourse\UpdateAvailableCourseService;
use App\Services\AvailableCourse\ImportAvailableCourseService;

class AvailableCourseService
{
    public function __construct(
        private CreateAvailableCourseService $createService,
        private UpdateAvailableCourseService $updateService,
        private ImportAvailableCourseService $importService
    ) {}

    /**
     * Create a new available course or multiple courses in bulk.
     *
     * @param array $data
     * @return AvailableCourse|array
     * @throws BusinessValidationException
     */
    public function createAvailableCourse(array $data)
    {
        // Bulk creation support
        if (isset($data['courses']) && is_array($data['courses'])) {
            $results = [];
            foreach ($data['courses'] as $courseData) {
                $results[] = $this->createService->createAvailableCourseSingle($courseData);
            }
            return $results;
        }
        return $this->createService->createAvailableCourseSingle($data);
    }

    /**
     * Update an existing available course or multiple courses in bulk.
     *
     * @param int|AvailableCourse $availableCourseOrId
     * @param array $data
     * @return AvailableCourse|array
     * @throws BusinessValidationException
     */
    public function updateAvailableCourse($availableCourseOrId, array $data)
    {
        $availableCourse = AvailableCourse::findOrFail($availableCourseOrId);
        return $this->updateService->updateAvailableCourseSingle($availableCourse, $data);
    }

    /**
     * Update available course by ID.
     *
     * @param int $id
     * @param array $data
     * @return AvailableCourse
     */
    public function updateAvailableCourseById(int $id, array $data): AvailableCourse
    {
        $availableCourse = AvailableCourse::findOrFail($id);
        return $this->updateService->updateAvailableCourseSingle($availableCourse, $data);
    }

    /**
     * Delete an available course by ID.
     *
     * @param int $id
     * @return void
     * @throws BusinessValidationException
     */
    public function deleteAvailableCourse(int $id): void
    {
        $availableCourse = AvailableCourse::with('schedules')->find($id);
        if (!$availableCourse) {
            throw new BusinessValidationException('Available course not found.');
        }

        // Prevent deleting available course if there are enrollments
        // Check direct enrollments linked by course+term
        $directEnrollmentsCount = $availableCourse->enrollments()->count();

        // Check enrollments attached to any of its schedules
        $scheduleIds = $availableCourse->schedules->pluck('id')->toArray();
        $scheduleEnrollmentsCount = 0;
        if (!empty($scheduleIds)) {
            $scheduleEnrollmentsCount = \App\Models\EnrollmentSchedule::whereIn('available_course_schedule_id', $scheduleIds)->count();
        }

        if ($directEnrollmentsCount > 0 || $scheduleEnrollmentsCount > 0) {
            throw new BusinessValidationException('Cannot delete available course with existing enrollments.');
        }

        $availableCourse->delete();
    }

    /**
     * Get available course statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        $latest = AvailableCourse::max('updated_at');
        $total = AvailableCourse::count();
        $universal = AvailableCourse::where('mode', 'universal')->count();

        return [
            'available_courses' => [
                'total' => formatNumber($total),
                'lastUpdateTime' => formatDate($latest),
            ],
            'universal_courses' => [
                'total' => formatNumber($universal),
                'lastUpdateTime' => formatDate($latest),
            ],
        ];
    }

    
  /**
 * @param int $availableCourseId
 * @param string|array|null $group Accept a single group, comma-separated string, or array of groups
 * @return array
 * @throws BusinessValidationException
 */
public function getSchedules(int $availableCourseId, $group = null): array
{
    $availableCourse = $this->findAvailableCourse($availableCourseId);

    $groups = $this->normalizeGroups($group);

    $filteredSchedules = $this->filterSchedulesByGroups($availableCourse->schedules, $groups);

    return $this->groupSchedules($filteredSchedules);
}

/**
 * Fetch course with required relationships or throw exception
 */
private function findAvailableCourse(int $id): AvailableCourse
{
    $availableCourse = AvailableCourse::with('schedules.scheduleAssignments.scheduleSlot')->find($id);

    if (!$availableCourse) {
        throw new BusinessValidationException('Available course not found.');
    }

    return $availableCourse;
}

/**
 * Normalize input groups into a clean array or null
 */
private function normalizeGroups($group): ?array
{
    if (is_array($group)) {
        return array_values(array_filter($group, fn($g) => $g !== null && $g !== ''));
    }

    if (is_string($group) && $group !== '') {
        if (str_contains($group, ',')) {
            return array_map('trim', explode(',', $group));
        }

        $decoded = json_decode($group, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return [$group];
    }

    return null;
}

/**
 * Filter schedules by given groups, or return all
 */
private function filterSchedulesByGroups($schedules, ?array $groups)
{
    if ($groups && count($groups) > 0) {
        return $schedules->filter(fn($s) =>
            in_array((string)$s->group, array_map('strval', $groups), true)
        );
    }

    return $schedules;
}

/**
 * Group schedules by activity type and format response
 */
private function groupSchedules($schedules): array
{
    return $schedules->groupBy('activity_type')->map(function ($schedules, $activityType) {
        $activitySchedules = [];

        foreach ($schedules as $schedule) {
            $slots = $schedule->scheduleAssignments->map(fn($assignment) => $assignment->scheduleSlot)->filter();

            $sortedSlots = $slots->isNotEmpty() ? $slots->sortBy('start_time')->values() : collect();
            $firstSlot   = $sortedSlots->first();
            $lastSlot    = $sortedSlots->last();

            $enrolledCount = \App\Models\EnrollmentSchedule::whereHas(
                'availableCourseSchedule',
                fn($query) => $query->where('id', $schedule->id)
            )->count();

            $activitySchedules[] = [
                'id'             => $schedule->id,
                'activity_type'  => $schedule->activity_type,
                'group_number'   => $schedule->group,
                'location'       => $schedule->location,
                'min_capacity'   => $schedule->min_capacity,
                'max_capacity'   => $schedule->max_capacity,
                'enrolled_count' => $enrolledCount,
                'day_of_week'    => $firstSlot?->day_of_week,
                'start_time'     => formatTime($firstSlot?->start_time),
                'end_time'       => formatTime($lastSlot?->end_time),
            ];
        }

        return [
            'activity_type' => $activityType,
            'schedules'    => $activitySchedules,
        ];
    })->values()->toArray();
}


    /**
     * Get eligibilities for a specific available course.
     *
     * @param int $availableCourseId
     * @return array
     * @throws BusinessValidationException
     */
    public function getEligibilities(int $availableCourseId): array
    {
        $availableCourse = AvailableCourse::with(['eligibilities.program', 'eligibilities.level'])->find($availableCourseId);

        if (!$availableCourse) {
            throw new BusinessValidationException('Available course not found.');
        }

        // Check if it's universal mode
        if ($availableCourse->mode === AvailableCourse::MODE_UNIVERSAL) {
            return [
                [
                    'program_name' => 'All Programs',
                    'level_name' => 'All Levels',
                    'combined' => 'All Programs / All Levels'
                ]
            ];
        }

        // Get eligibilities with program and level names
        $eligibilities = $availableCourse->eligibilities->map(function ($eligibility) {
            return [
                'program_name' => $eligibility->program->name ?? 'Unknown Program',
                'level_name' => $eligibility->level->name ?? 'Unknown Level',
                'combined' => ($eligibility->program->name ?? 'Unknown Program') . ' / ' . ($eligibility->level->name ?? 'Unknown Level')
            ];
        })->toArray();

        return $eligibilities;
    }

    /**
     * Get DataTables JSON response for available courses.
     *
     * @return JsonResponse
     */
    public function getDatatable(): JsonResponse
    {
        $query = AvailableCourse::with(['course', 'term', 'eligibilities.program', 'eligibilities.level', 'schedules']);
        $request = request();
        $this->applySearchFilters($query, $request);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('course', function ($availableCourse) {
                return $availableCourse->course?->name ?? '-';
            })
            ->addColumn('term', function ($availableCourse) {
                return $availableCourse->term?->name ?? '-';
            })
            ->addColumn('schedules', function ($availableCourse) {
                $schedules = $availableCourse->schedules;
                if ($schedules->isEmpty()) {
                    return '<span class="text-muted">No schedules</span>';
                }
                $count = $schedules->count();
                return sprintf(
                    '<button type="button" class="btn btn-secondary btn-sm show-schedules-modal position-relative group-hover-parent" data-id="%d" title="View Schedules" style="position: relative;">
                        <i class="bx bx-calendar"></i> Schedules 
                        <span class="badge schedules-badge-hover" style="transition: background-color 0.2s, color 0.2s;">%d</span>
                    </button>',
                    $availableCourse->id,
                    $count
                );
            })
            ->addColumn('eligibility', function ($availableCourse) {
                if ($availableCourse->mode === 'universal') {
                    return '<span class="badge bg-primary">Universal</span>';
                }
                $pairs = $availableCourse->eligibilities->map(function ($eligibility) {
                    $programName = $eligibility->program?->name ?? '-';
                    $levelName = $eligibility->level?->name ?? '-';
                    return "{$programName} / {$levelName}";
                });
                $count = $pairs->count();
                if ($count === 0) {
                    return '-';
                }
                if ($count === 1) {
                    return e($pairs->first());
                }
                return sprintf(
                    '<button type="button" class="btn btn-info btn-sm show-eligibility-modal position-relative group-hover-parent" data-id="%d" title="View Eligibility Requirements" style="position: relative;">
                        <i class="bx bx-list-ul"></i> Eligibility 
                        <span class="badge eligibility-badge-hover" style="transition: background-color 0.2s, color 0.2s;">%d</span>
                    </button>',
                    $availableCourse->id,
                    $count
                );
            })
            ->addColumn('capacity', function ($availableCourse) {
                $schedules = $availableCourse->schedules;
                if ($schedules->isEmpty()) {
                    return '-';
                }
                $ranges = $schedules->map(function ($detail) {
                    if (isset($detail->min_capacity) && isset($detail->max_capacity)) {
                        return "{$detail->min_capacity}-{$detail->max_capacity}";
                    }
                    return null;
                })->filter()->unique()->values();
                return $ranges->count() === 1
                    ? $ranges->first()
                    : $ranges->implode(', ');
            })
            ->addColumn('enrollments', function ($availableCourse) {
                return $availableCourse->enrollments->count();
            })
            ->addColumn('action', function ($availableCourse) {
                return $this->renderActionButtons($availableCourse);
            })
            ->rawColumns(['eligibility', 'schedules', 'enrollments', 'action'])
            ->orderColumn('course', function ($query, $order) {
                $query->join('courses', 'available_courses.course_id', '=', 'courses.id')
                    ->orderBy('courses.title', $order)
                    ->select('available_courses.*');
            })
            ->orderColumn('term', function ($query, $order) {
                $query->join('terms', 'available_courses.term_id', '=', 'terms.id')
                    ->orderBy('terms.year', $order)
                    ->orderBy('terms.season', $order)
                    ->select('available_courses.*');
            })
            ->make(true);
    }

    /**
     * Apply search filters to the available courses query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    private function applySearchFilters($query, $request): void
    {
        // --- Course Title/Code Filter ---
        $searchCourse = $request->input('search_course');
        if (!empty($searchCourse)) {
            $query->whereHas('course', function ($q) use ($searchCourse) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%' . mb_strtolower($searchCourse) . '%'])
                  ->orWhereRaw('LOWER(code) LIKE ?', ['%' . mb_strtolower($searchCourse) . '%']);
            });
        }

        // --- Term Season/Year/Code Filter ---
        $searchTerm = $request->input('search_term');
        if (!empty($searchTerm)) {
            $query->whereHas('term', function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(season) LIKE ?', ['%' . mb_strtolower($searchTerm) . '%'])
                  ->orWhereRaw('CAST(year AS CHAR) LIKE ?', ['%' . mb_strtolower($searchTerm) . '%'])
                  ->orWhereRaw('LOWER(code) LIKE ?', ['%' . mb_strtolower($searchTerm) . '%']);
            });
        }

        // --- Eligibility Mode Filter ---
        $eligibilityMode = $request->input('search_mode');
        if (!empty($eligibilityMode)) {
            $query->where('mode', $eligibilityMode);
        }

        // --- Activity Type Filter ---
        $activityType = $request->input('search_activity_type');
        if (!empty($activityType)) {
            $query->whereHas('schedules', function ($q) use ($activityType) {
                $q->where('activity_type', $activityType);
            });
        }

        // --- Group Filter ---
        $group = $request->input('search_group');
        if (!empty($group)) {
            $query->whereHas('schedules', function ($q) use ($group) {
                $q->where('group', $group);
            });
        }
    }

    /**
     * Import available courses from uploaded Excel file.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function importAvailableCoursesFromFile(UploadedFile $file): array
    {
        return $this->importService->importAvailableCoursesFromFile($file);
    }

    /**
     * Import available courses from collection of rows.
     *
     * @param Collection $rows
     * @return array
     */
    public function importAvailableCoursesFromRows(Collection $rows): array
    {
        return $this->importService->importAvailableCoursesFromRows($rows);
    }

    

    public function getAvailableCourse(int $id): array
    {
        $availableCourse = AvailableCourse::with([
            'eligibilities.program',
            'eligibilities.level',
            'course',
            'term',
            'schedules.scheduleAssignments.scheduleSlot'
        ])->findOrFail($id);

        return [
            'id' => $availableCourse->id,
            'course_id' => $availableCourse->course_id,
            'term_id' => $availableCourse->term_id,
            'mode' => $availableCourse->mode,
            'eligibilities' => $availableCourse->eligibilities->map(function ($eligibility) {
                return [
                    'program_id' => $eligibility->program_id,
                    'level_id' => $eligibility->level_id,
                    'program_name' => $eligibility->program?->name,
                    'level_name' => $eligibility->level?->name,
                    'group' => $eligibility->group,
                ];
            })->toArray(),
            'schedules' => $availableCourse->schedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'group' => $schedule->group,
                    'activity_type' => $schedule->activity_type,
                    'location' => $schedule->location,
                    'slots' => $schedule->scheduleAssignments->map(function ($assignment) {
                        $slot = $assignment->scheduleSlot;
                        return [
                            'schedule_assignment_id' => $assignment->id,
                            'slot_id' => $slot?->id,
                            'schedule_id' => $slot?->schedule_id,
                            'day_of_week' => $slot?->day_of_week,
                            'start_time' => $slot ? formatTime($slot->start_time) : null,
                            'end_time' => $slot ? formatTime($slot->end_time) : null,
                            'slot_order' => $slot?->slot_order,
                        ];
                    })->toArray(),
                    'min_capacity' => $schedule->min_capacity ?? 1,
                    'max_capacity' => $schedule->max_capacity ?? 30,
                ];
            })->toArray(),
        ];
    }


    public function getAll(): Collection
    {
        return AvailableCourse::with(['course', 'term', 'eligibilities.program', 'eligibilities.level', 'schedules'])
            ->get()
            ->map(function ($availableCourse) {
                return [
                    'id' => $availableCourse->id,
                    'course_id' => $availableCourse->course_id,
                    'course_name' => $availableCourse->course?->name ?? '-',
                    'course_code' => $availableCourse->course?->code ?? '-',
                    'term_id' => $availableCourse->term_id,
                    'term_name' => $availableCourse->term?->name ?? '-',
                    'term_code' => $availableCourse->term?->code ?? '-',
                    'mode' => $availableCourse->mode,
                    'eligibilities' => $availableCourse->eligibilities->map(function($eligibility) {
                        return [
                            'program_id' => $eligibility->program_id,
                            'level_id' => $eligibility->level_id,
                            'program_name' => $eligibility->program?->name,
                            'level_name' => $eligibility->level?->name,
                        ];
                    })->toArray(),
                    'schedules' => $availableCourse->schedules->map(function($detail) {
                        return [
                            'id' => $detail->id,
                            'activity_type' => $detail->activity_type,
                            'day' => $detail->day ?? null,
                            'slot' => $detail->slot ?? null,
                            'schedule_code' => $detail->schedule?->code ?? null,
                            'min_capacity' => $detail->min_capacity ?? 1,
                            'max_capacity' => $detail->max_capacity ?? 30,
                        ];
                    })->toArray(),
                ];
            });
    }

    /**
     * Get available courses with their schedules for scheduling purposes.
     *
     * @param array $filters
     * @return Collection
     */
    public function getAvailableCoursesForScheduling(array $filters = []): Collection
    {
        $query = AvailableCourse::with(['course', 'term', 'schedules', 'eligibilities.program', 'eligibilities.level']);

        if (isset($filters['term_id'])) {
            $query->where('term_id', $filters['term_id']);
        }

        if (isset($filters['program_id']) && isset($filters['level_id'])) {
            $query->where(function($q) use ($filters) {
                $q->where('mode', 'universal')
                  ->orWhereHas('eligibilities', function($eligibilityQuery) use ($filters) {
                      $eligibilityQuery->where('program_id', $filters['program_id'])
                                      ->where('level_id', $filters['level_id']);
                  });
            });
        }

        if (isset($filters['activity_type'])) {
            $query->whereHas('schedules', function($detailQuery) use ($filters) {
                $detailQuery->where('activity_type', $filters['activity_type']);
            });
        }

        return $query->get();
    }

    /**
     * Get course schedules by available course ID.
     *
     * @param int $availableCourseId
     * @return Collection
     */
    public function getCourseSchedules(int $availableCourseId): Collection
    {
        return AvailableCourseSchedule::where('available_course_id', $availableCourseId)
            ->orderBy('group')
            ->orderBy('activity_type')
            ->get();
    }

    /**
     * Update course detail.
     *
     * @param int $detailId
     * @param array $data
     * @return AvailableCourseSchedule
     */
    public function updateCourseDetail(int $detailId, array $data): AvailableCourseSchedule
    {
        $detail = AvailableCourseSchedule::findOrFail($detailId);
        $detail->update($data);
        return $detail->fresh();
    }

    /**
     * Delete course detail.
     *
     * @param int $detailId
     * @return void
     */
    public function deleteCourseDetail(int $detailId): void
    {
        $detail = AvailableCourseSchedule::withCount('enrollments')->findOrFail($detailId);
        
        if($detail->enrollments_count > 0){
            throw new BusinessValidationException('Cannot delete course detail with enrollments.');
        }

        $detail->delete();
    }


    /**
     * Check if a course detail already exists.
     *
     * @param int $availableCourseId
     * @param int $group
     * @param string $activityType
     * @param int|null $excludeId
     * @return bool
     */
    public function courseDetailExists(int $availableCourseId, int $group, string $activityType, ?int $excludeId = null): bool
    {
        $query = AvailableCourseSchedule::where('available_course_id', $availableCourseId)
            ->where('group', $group)
            ->where('activity_type', strtolower($activityType));

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get statistics for course schedules.
     *
     * @return array
     */
    public function getCourseSchedulesStats(): array
    {
        $totalSchedules = AvailableCourseSchedule::count();
        $lectureCount = AvailableCourseSchedule::where('activity_type', 'lecture')->count();
        $labCount = AvailableCourseSchedule::where('activity_type', 'lab')->count();
        $tutorialCount = AvailableCourseSchedule::where('activity_type', 'tutorial')->count();

        return [
            'total_schedules' => $totalSchedules,
            'lecture_count' => $lectureCount,
            'lab_count' => $labCount,
            'tutorial_count' => $tutorialCount,
            'activity_distribution' => [
                'lecture' => $lectureCount,
                'lab' => $labCount,
                'tutorial' => $tutorialCount,
            ]
        ];
    }

    /**
     * Render action buttons for DataTables.
     *
     * @param AvailableCourse $availableCourse
     * @return string
     */
    private function renderActionButtons(AvailableCourse $availableCourse): string
    {
        $editUrl = route('available_courses.edit', $availableCourse->id);

        return '
            <a class="btn btn-sm btn-primary editAvailableCourseBtn me-1"
               href="' . e($editUrl) . '"
               data-id="' . e($availableCourse->id) . '"
               title="Edit">
                <i class="bx bx-edit"></i>
            </a>
            <button class="btn btn-sm btn-danger deleteAvailableCourseBtn"
                    data-id="' . e($availableCourse->id) . '"
                    title="Delete"
                    type="button">
                <i class="bx bx-trash"></i>
            </button>
        ';
    }
}