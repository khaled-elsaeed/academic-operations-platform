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
use App\Services\AvailableCourse\ImportAvailableCourseService;

class AvailableCourseService
{
    public function __construct(
        private CreateAvailableCourseService $createService,
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
        return $this->updateService->update($availableCourse, $data);
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
    // Eager-load available course schedules and their assignments -> slots
    $availableCourse = AvailableCourse::with(['schedules.scheduleAssignments.scheduleSlot'])
        ->find($availableCourseId);

    if (!$availableCourse) {
        throw new BusinessValidationException('Available course not found.');
    }

    // Normalize and filter schedules
    $groups = $this->normalizeGroups($group);
    $filteredSchedules = $this->filterSchedulesByGroups($availableCourse->schedules, $groups);

    // Transform into grouped result
    return $this->groupSchedulesByActivity($filteredSchedules);
}

/**
 * Normalize group input into an array of strings (or null if no filtering).
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
        return is_array($decoded) ? $decoded : [$group];
    }

    return null;
}

/**
 * Filter schedules based on given groups (if any).
 */
private function filterSchedulesByGroups($schedules, ?array $groups)
{
    if ($groups === null || count($groups) === 0) {
        return $schedules;
    }

    return $schedules->filter(function ($s) use ($groups) {
        return in_array((string) $s->group, array_map('strval', $groups), true);
    });
}

/**
 * Group schedules by activity_type and transform each schedule.
 */
private function groupSchedulesByActivity($schedules): array
{
    return $schedules->groupBy('activity_type')->map(function ($schedules, $activityType) {
        $activitySchedules = $schedules->map(fn($schedule) => $this->transformSchedule($schedule))->toArray();

        return [
            'activity_type' => $activityType,
            'schedules' => $activitySchedules
        ];
    })->values()->toArray();
}

/**
 * Transform a single schedule into API-friendly structure.
 */
private function transformSchedule($schedule): array
{
    $slots = $schedule->scheduleAssignments->map(function ($assignment) {
        return $assignment->scheduleSlot;
    })->filter();

    $sortedSlots = $slots->isNotEmpty() ? $slots->sortBy('start_time')->values() : collect();
    $firstSlot = $sortedSlots->first();
    $lastSlot = $sortedSlots->last();

    $enrolledCount = \App\Models\EnrollmentSchedule::whereHas('availableCourseSchedule', function ($query) use ($schedule) {
        $query->where('id', $schedule->id);
    })->count();

    return [
        'id' => $schedule->id,
        'activity_type' => $schedule->activity_type,
        'group_number' => $schedule->group,
        'location' => $schedule->location,
        'min_capacity' => $schedule->min_capacity,
        'max_capacity' => $schedule->max_capacity,
        'enrolled_count' => $enrolledCount,
        'day_of_week' => $firstSlot?->day_of_week ?? null,
        'start_time' => $firstSlot ? formatTime($firstSlot->start_time) : null,
        'end_time' => $lastSlot ? formatTime($lastSlot->end_time) : null,
    ];
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
                'universal' => true,
                'message' => 'All programs and levels are eligible.',
                'eligibilities' => []
            ];
        }

        // Get eligibilities with program, level, and group
        $eligibilities = $availableCourse->eligibilities->map(function ($eligibility) {
            return [
                'program_name' => $eligibility->program->name ?? 'Unknown Program',
                'level_name' => $eligibility->level->name ?? 'Unknown Level',
                'group' => $eligibility->group ?? '-',
                'combined' => ($eligibility->program->name ?? 'Unknown Program') . ' / ' . ($eligibility->level->name ?? 'Unknown Level') . ' / ' . ($eligibility->group ?? '-')
            ];
        })->toArray();

        return [
            'universal' => false,
            'message' => null,
            'eligibilities' => $eligibilities
        ];
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
                $count = 0;
                $buttonText = 'Eligibility';
                if ($availableCourse->mode === 'universal') {
                    $count = 1;
                    $buttonText = 'Universal Eligibility';
                } else {
                    $pairs = $availableCourse->eligibilities->map(function ($eligibility) {
                        $programName = $eligibility->program?->name ?? '-';
                        $levelName = $eligibility->level?->name ?? '-';
                        return "{$programName} / {$levelName}";
                    });
                    $count = $pairs->count();
                }
                if ($count === 0) {
                    return '-';
                }
                return sprintf(
                    '<button type="button" class="btn btn-info btn-sm show-eligibility-modal position-relative group-hover-parent" data-id="%d" title="View Eligibility Requirements" style="position: relative;">
                        <i class="bx bx-list-ul"></i> %s
                        <span class="badge eligibility-badge-hover" style="transition: background-color 0.2s, color 0.2s;">%d</span>
                    </button>',
                    $availableCourse->id,
                    $buttonText,
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


    /**
     * Get eligibility datatable for edit page.
     *
     * @param int $availableCourseId
     * @return JsonResponse
     */
    public function getEligibilityDatatable(int $availableCourseId): JsonResponse
    {
        $query = CourseEligibility::with(['program', 'level'])
            ->where('available_course_id', $availableCourseId);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('program_name', function ($eligibility) {
                return $eligibility->program->name ?? '-';
            })
            ->addColumn('level_name', function ($eligibility) {
                return $eligibility->level->name ?? '-';
            })
            ->addColumn('groups', function ($eligibility) {
                return $eligibility->group ?? '-';
            })
            ->addColumn('actions', function ($eligibility) {
                return '
                    <button class="btn btn-sm btn-danger deleteEligibilityBtn"
                            data-id="' . e($eligibility->id) . '"
                            title="Delete">
                        <i class="bx bx-trash"></i>
                    </button>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Store new eligibility for available course.
     *
     * @param int $availableCourseId
     * @param array $data
     * @return array
     * @throws BusinessValidationException
     */
    public function storeEligibility(int $availableCourseId, array $data): array
    {
        $availableCourse = AvailableCourse::findOrFail($availableCourseId);
        $results = [];

        foreach ($data['group_numbers'] as $group) {
            // Check for existing eligibility
            $exists = CourseEligibility::where('available_course_id', $availableCourseId)
                ->where('program_id', $data['program_id'])
                ->where('level_id', $data['level_id'])
                ->where('group', $group)
                ->exists();

            if (!$exists) {
                $eligibility = CourseEligibility::create([
                    'available_course_id' => $availableCourseId,
                    'program_id' => $data['program_id'],
                    'level_id' => $data['level_id'],
                    'group' => $group,
                ]);
                $results[] = $eligibility->load('program', 'level');
            }
        }

        return $results;
    }

    /**
     * Delete eligibility for available course.
     *
     * @param int $eligibilityId
     * @return void
     * @throws BusinessValidationException
     */
    public function deleteEligibility(int $eligibilityId): void
    {
        $eligibility = CourseEligibility::findOrFail($eligibilityId);
        $eligibility->delete();
    }

    /**
     * Get schedules datatable for edit page.
     *
     * @param int $availableCourseId
     * @return JsonResponse
     */
    public function getSchedulesDatatable(int $availableCourseId): JsonResponse
    {
        $query = AvailableCourseSchedule::with(['scheduleAssignments.scheduleSlot'])
            ->where('available_course_id', $availableCourseId);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('activity_type', function ($schedule) {
                return ucfirst($schedule->activity_type);
            })
            ->addColumn('location', function ($schedule) {
                return $schedule->location ?? '-';
            })
            ->addColumn('groups', function ($schedule) {
                return $schedule->group ?? '-';
            })
            ->addColumn('day', function ($schedule) {
                $firstSlot = $schedule->scheduleAssignments->first()?->scheduleSlot;
                return $firstSlot ? ucfirst($firstSlot->day_of_week) : '-';
            })
            ->addColumn('slots', function ($schedule) {
                $slots = $schedule->scheduleAssignments->map(function ($assignment) {
                    return $assignment->scheduleSlot;
                })->filter()->sortBy('start_time');

                if ($slots->isEmpty()) {
                    return '-';
                }

                $firstSlot = $slots->first();
                $lastSlot = $slots->last();
                
                return formatTime($firstSlot->start_time) . ' - ' . formatTime($lastSlot->end_time);
            })
            ->addColumn('capacity', function ($schedule) {
                $enrolled = \App\Models\EnrollmentSchedule::where('available_course_schedule_id', $schedule->id)->count();
                $capacity = '';
                
                if ($schedule->min_capacity) {
                    $capacity .= 'Min: ' . $schedule->min_capacity;
                }
                if ($schedule->max_capacity) {
                    if ($capacity) $capacity .= ' / ';
                    $capacity .= 'Max: ' . $schedule->max_capacity;
                }
                if ($capacity) {
                    $capacity .= ' / Enrolled: ' . $enrolled;
                } else {
                    $capacity = 'Enrolled: ' . $enrolled;
                }
                
                return $capacity;
            })
            ->addColumn('actions', function ($schedule) {
                return '
                    <button class="btn btn-sm btn-primary editScheduleBtn me-1"
                            data-id="' . e($schedule->id) . '"
                            data-activity-type="' . e($schedule->activity_type) . '"
                            data-group="' . e($schedule->group) . '"
                            data-location="' . e($schedule->location) . '"
                            data-min-capacity="' . e($schedule->min_capacity) . '"
                            data-max-capacity="' . e($schedule->max_capacity) . '"
                            title="Edit">
                        <i class="bx bx-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger deleteScheduleBtn"
                            data-id="' . e($schedule->id) . '"
                            title="Delete">
                        <i class="bx bx-trash"></i>
                    </button>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Get single schedule for editing.
     *
     * @param int $scheduleId
     * @return array
     * @throws BusinessValidationException
     */
    public function getSchedule(int $scheduleId): array
    {
        $schedule = AvailableCourseSchedule::with(['scheduleAssignments.scheduleSlot'])->find($scheduleId);
        
        if (!$schedule) {
            throw new BusinessValidationException('Schedule not found.');
        }

        // Get slots information
        $slots = $schedule->scheduleAssignments->map(function ($assignment) {
            return $assignment->scheduleSlot;
        })->filter()->sortBy('start_time');

        $slotIds = $slots->pluck('id')->toArray();
        $firstSlot = $slots->first();

        return [
            'id' => $schedule->id,
            'schedule_template_id' => $firstSlot?->schedule?->id,
            'day'=> $firstSlot?->day_of_week,
            'activity_type' => $schedule->activity_type,
            'group' => $schedule->group,
            'group_number' => [$schedule->group],
            'location' => $schedule->location,
            'min_capacity' => $schedule->min_capacity,
            'max_capacity' => $schedule->max_capacity,
            'day_of_week' => $firstSlot?->day_of_week,
            'slot_ids' => $slotIds,
        ];
    }

    /**
     * Store new schedule for available course.
     *
     * @param int $availableCourseId
     * @param array $data
     * @return array
     * @throws BusinessValidationException
     */
    public function storeSchedule(int $availableCourseId, array $data): array
    {
        $availableCourse = AvailableCourse::findOrFail($availableCourseId);

        if (isset($data['min_capacity'], $data['max_capacity']) && $data['min_capacity'] !== null && $data['max_capacity'] !== null) {
            if ((int)$data['min_capacity'] > (int)$data['max_capacity']) {
                throw new BusinessValidationException('Min capacity cannot be greater than max capacity.');
            }
        }

        $slotIds = $data['schedule_slot_ids'] ?? [];
        $results = [];
        $skippedCount = 0;

        DB::transaction(function () use (&$results, &$skippedCount, $data, $availableCourseId, $slotIds) {
            foreach ($data['group_numbers'] as $group) {
                $location = $data['location'] ?? null;
                
                $existsQuery = AvailableCourseSchedule::where('available_course_id', $availableCourseId)
                    ->where('activity_type', $data['activity_type'])
                    ->where('group', $group);
                
                // Handle null location properly
                if ($location === null) {
                    $existsQuery->whereNull('location');
                } else {
                    $existsQuery->where('location', $location);
                }
                
                $exists = $existsQuery->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                $schedule = AvailableCourseSchedule::create([
                    'available_course_id' => $availableCourseId,
                    'activity_type' => $data['activity_type'],
                    'group' => $group,
                    'location' => $location,
                    'min_capacity' => $data['min_capacity'] ?? null,
                    'max_capacity' => $data['max_capacity'] ?? null,
                ]);

                // Assign slots if provided
                if (!empty($slotIds)) {
                    foreach ($slotIds as $slotId) {
                        ScheduleAssignment::create([
                            'available_course_schedule_id' => $schedule->id,
                            'schedule_slot_id' => $slotId,
                            'type' => 'available_course',
                            'title' => $data['title'] ?? ($schedule->activity_type . ' - Group ' . $group),
                            'description' => $data['description'] ?? null,
                            'enrolled' => 0,
                            'resources' => $data['resources'] ?? null,
                            'status' => $data['status'] ?? 'scheduled',
                            'notes' => $data['notes'] ?? null,
                        ]);
                    }
                }

                $results[] = $schedule->load('scheduleAssignments.scheduleSlot');
            }
        });

        // If no schedules were created because they all already exist
        if (empty($results) && $skippedCount > 0) {
            throw new BusinessValidationException('All schedules for the selected groups already exist.');
        }

        // If no schedules were created for other reasons
        if (empty($results)) {
            throw new BusinessValidationException('No schedules were created. Please check your input data.');
        }

        return $results;
    }

    /**
     * Update schedule for available course.
     *
     * @param int $scheduleId
     * @param array $data
     * @return array
     * @throws BusinessValidationException
     */
    public function updateSchedule(int $scheduleId, array $data): array
    {
        $schedule = AvailableCourseSchedule::findOrFail($scheduleId);

        if (isset($data['min_capacity'], $data['max_capacity']) && $data['min_capacity'] !== null && $data['max_capacity'] !== null) {
            if ((int)$data['min_capacity'] > (int)$data['max_capacity']) {
                throw new BusinessValidationException('Min capacity cannot be greater than max capacity.');
            }
        }

        $slotIds = $data['schedule_slot_ids'] ?? [];
        
        $schedule->update([
            'group' => $data['group_numbers'] ?? $data['group_number'] ?? $schedule->group,
            'activity_type' => $data['activity_type'] ?? $schedule->activity_type,
            'location' => $data['location'] ?? $schedule->location,
            'min_capacity' => $data['min_capacity'] ?? $schedule->min_capacity,
            'max_capacity' => $data['max_capacity'] ?? $schedule->max_capacity,
        ]);

        // Handle schedule assignments update
        if (!empty($slotIds)) {
            // Get existing assignments
            $existingAssignments = ScheduleAssignment::where('available_course_schedule_id', $scheduleId)
                ->pluck('schedule_slot_id')
                ->toArray();

            // Remove assignments that are no longer needed
            $assignmentsToRemove = array_diff($existingAssignments, $slotIds);
            if (!empty($assignmentsToRemove)) {
                ScheduleAssignment::where('available_course_schedule_id', $scheduleId)
                    ->whereIn('schedule_slot_id', $assignmentsToRemove)
                    ->delete();
            }

            // Add new assignments
            $assignmentsToAdd = array_diff($slotIds, $existingAssignments);
            foreach ($assignmentsToAdd as $slotId) {
                ScheduleAssignment::create([
                    'available_course_schedule_id' => $scheduleId,
                    'schedule_slot_id' => $slotId,
                    'type' => 'available_course',
                    'title' => $data['title'] ?? ($schedule->activity_type . ' - Group ' . $schedule->group),
                    'description' => $data['description'] ?? null,
                    'enrolled' => 0,
                    'resources' => $data['resources'] ?? null,
                    'status' => $data['status'] ?? 'scheduled',
                    'notes' => $data['notes'] ?? null,
                ]);
            }

            // Update existing assignments with new data if provided
            if (isset($data['title']) || isset($data['description']) || isset($data['resources']) || isset($data['status']) || isset($data['notes'])) {
                $updateData = [];
                if (isset($data['title'])) $updateData['title'] = $data['title'];
                if (isset($data['description'])) $updateData['description'] = $data['description'];
                if (isset($data['resources'])) $updateData['resources'] = $data['resources'];
                if (isset($data['status'])) $updateData['status'] = $data['status'];
                if (isset($data['notes'])) $updateData['notes'] = $data['notes'];
                
                if (!empty($updateData)) {
                    ScheduleAssignment::where('available_course_schedule_id', $scheduleId)
                        ->whereIn('schedule_slot_id', array_intersect($existingAssignments, $slotIds))
                        ->update($updateData);
                }
            }
        }

        return [$schedule->fresh()->load('scheduleAssignments.scheduleSlot')];
    }

    /**
     * Delete schedule for available course.
     *
     * @param int $scheduleId
     * @return void
     * @throws BusinessValidationException
     */
    public function deleteSchedule(int $scheduleId): void
    {
        $schedule = AvailableCourseSchedule::findOrFail($scheduleId);
        
        // Check if there are enrollments
        $enrollmentCount = \App\Models\EnrollmentSchedule::where('available_course_schedule_id', $scheduleId)->count();
        if ($enrollmentCount > 0) {
            throw new BusinessValidationException('Cannot delete schedule with existing enrollments.');
        }

        // Delete schedule assignments and then the schedule
        $schedule->scheduleAssignments()->delete();
        $schedule->delete();
    }
}