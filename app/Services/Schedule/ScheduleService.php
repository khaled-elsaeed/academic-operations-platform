<?php

namespace App\Services\Schedule;

use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleSlot;
use App\Services\Schedule\Create\CreateScheduleService;
use App\Services\Schedule\Update\UpdateScheduleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\DataTables;
use App\Exceptions\BusinessValidationException;

class ScheduleService
{
    /**
     * Get all active schedules
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllActive(array $filters = [])
    {
        $query = Schedule::with(['term', 'scheduleType'])
            ->orderBy('title');

        // Filter by term if provided
        if (!empty($filters['term_id'])) {
            $query->where('term_id', $filters['term_id']);
        }

        // Filter by type (weekly = repetitive weekly schedules)
        if (!empty($filters['type']) && $filters['type'] === 'weekly') {
            $query->whereHas('scheduleType', function ($q) {
                $q->where('is_repetitive', true)
                  ->where('repetition_pattern', 'weekly');
            });
        }

        return $query->get();
    }

    /**
     * Create a new schedule with slots.
     *
     * @param array $data Schedule data
     * @return Schedule Created schedule
     * @throws BusinessValidationException
     */
    public function createSchedule(array $data): Schedule
    {
        return app(CreateScheduleService::class)->execute($data);
    }

    /**
     * Delete a schedule and all its slots.
     *
     * @param Schedule $schedule Schedule to delete
     */
    public function deleteSchedule(Schedule $schedule): void
    {
        DB::transaction(function () use ($schedule) {
            $schedule->slots()->delete();
            $schedule->delete();
        });
    }

    /**
     * Get schedule with slots for display, with formatted dates.
     *
     * @param int $scheduleId Schedule ID
     * @return array|null Schedule details with formatted dates
     */
    public function getScheduleDetails(int $scheduleId): ?array
    {
        // Define the correct day order (Saturday first)
        $dayOrder = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        $schedule = Schedule::with([
            'slots' => function($query) use ($dayOrder) {
            $orderExpr = "FIELD(day_of_week, '" . implode("','", $dayOrder) . "')";
            $query->orderByRaw($orderExpr)->orderBy('slot_order');
            },
            'scheduleType',
            'term'
        ])->findOrFail($scheduleId);

        if (!$schedule) {
            return null;
        }

        // Format the schedule data and dates
        return [
            'id' => $schedule->id,
            'title' => $schedule->title,
            'status' => $schedule->status,
            // keep a simple 'type' string for backward compatibility
            'type' => $schedule->scheduleType?->name,
            'term' => $schedule->term?->name,
            'day_starts_at' => $schedule->day_starts_at ? formatDate($schedule->day_starts_at) : null,
            'day_ends_at' => $schedule->day_ends_at ? formatDate($schedule->day_ends_at) : null,
            'created_at' => $schedule->created_at ? formatDate($schedule->created_at) : null,
            'updated_at' => $schedule->updated_at ? formatDate($schedule->updated_at) : null,
            'schedule_type' => $schedule->scheduleType ? [
                'id' => $schedule->scheduleType->id,
                'name' => $schedule->scheduleType->name,
                'is_repetitive' => $schedule->scheduleType->is_repetitive,
                'repetitive_pattern' => $schedule->scheduleType->repetition_pattern,
            ] : null,
            'slots' => $schedule->slots->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'day_of_week' => ucfirst($slot->day_of_week),
                    'slot_order' => $slot->slot_order,
                    'start_time' => $slot->start_time ? formatTime($slot->start_time) : null,
                    'end_time' => $slot->end_time ? formatTime($slot->end_time) : null,
                    'label' => $slot->label ?? null,
                ];
            })->toArray(),
            // return schedule_type as an object with useful fields
            
        ];
    }

    /**
     * Get available days and slots for a given schedule.
     *
     * @param int $scheduleId
     * @return array
     */
    public function getDaysAndSlots(int $scheduleId): array
    {
        // Fetch slots for the schedule, ordered by slot order
        $slots = ScheduleSlot::where('schedule_id', $scheduleId)
            ->orderBy('slot_order')
            ->get();

        // Define the correct day order (Saturday first as per frontend expectation)
        $dayOrder = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        // Group slots by day_of_week
        $days = [];
        foreach ($slots as $slot) {
            $day = $slot->day_of_week;
            if (!isset($days[$day])) {
                $days[$day] = [
                    'day_of_week' => $day,
                    'slots' => [],
                ];
            }
            $days[$day]['slots'][] = [
                'id' => $slot->id,
                'slot_order' => $slot->slot_order,
                'start_time' => $slot->start_time ? $slot->start_time->format('H:i') : null,
                'end_time' => $slot->end_time ? $slot->end_time->format('H:i') : null,
                'label' => $slot->label ?? null,
            ];
        }

        // Sort days according to the correct weekly order
        $sortedDays = [];
        foreach ($dayOrder as $day) {
            if (isset($days[$day])) {
                $sortedDays[] = $days[$day];
            }
        }

        return $sortedDays;
    }

    /**
     * Get DataTable response for schedules listing.
     *
     * @return JsonResponse DataTable JSON response
     */
    public function getDatatable(): JsonResponse
    {
        $query = Schedule::with(['scheduleType', 'term']);    

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('type', fn($schedule) => $schedule->scheduleType->name ?? '-')
            ->addColumn('day_starts_at', fn($schedule) => formatTime($schedule->day_starts_at))
            ->addColumn('day_ends_at', fn($schedule) => formatTime($schedule->day_ends_at))
            ->addColumn('status', fn($schedule) => ucfirst($schedule->status))
            ->addColumn('slots_count', fn($schedule) => $schedule->slots()->count())
            ->addColumn('actions', fn($schedule) => $this->renderActionButtons($schedule))
            ->orderColumn('type', function ($query, $order) {
                return $query->join('schedule_types', 'schedules.schedule_type_id', '=', 'schedule_types.id')
                            ->orderBy('schedule_types.name', $order)
                            ->select('schedules.*');
            })
            ->orderColumn('slots_count', function ($query, $order) {
                return $query->withCount('slots')
                            ->orderBy('slots_count', $order);
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Render action buttons for DataTable.
     *
     * @param Schedule $schedule Schedule instance
     * @return string HTML buttons
     */
    private function renderActionButtons(Schedule $schedule): string
    {
        $buttons = '<div class="d-flex gap-2">';

            // View button
            $buttons .= sprintf(
                '<button type="button" class="btn btn-sm btn-icon btn-info rounded-circle viewScheduleBtn" data-id="%d" title="View">
                    <i class="bx bx-show"></i>
                </button>',
                $schedule->id
            );

    
            $buttons .= sprintf(
                '<button type="button" class="btn btn-sm btn-icon btn-danger rounded-circle deleteScheduleBtn" data-id="%d" title="Delete">
                    <i class="bx bx-trash"></i>
                </button>',
                $schedule->id
            );

        return $buttons . '</div>';
    }

    /**
     * Get schedule statistics.
     *
     * @return array Statistics data
     */
    public function getStats(): array
    {
        $total = Schedule::count();
        $lastUpdateTime = Schedule::max('updated_at');

        return [
            'total' => [
                'count' => formatNumber($total),
                'lastUpdateTime' => formatDate($lastUpdateTime),
            ],
            
        ];
    }

    /**
     * Get weekly teaching schedule data for display.
     *
     * @param array $filters
     * @return array
     */
    public function getWeeklyTeachingData(array $filters): array
    {
        $scheduleId = !empty($filters['schedule_id']) ? $filters['schedule_id'] : null;
        $programId = !empty($filters['program_id']) ? $filters['program_id'] : null;
        $levelId = !empty($filters['level_id']) ? $filters['level_id'] : null;
        $groupFilter = !empty($filters['group']) ? $filters['group'] : null;


        if (!$scheduleId) {
            return [
                'slots' => [],
                'assignments' => [],
                'stats' => [
                    'total_courses' => 0,
                    'total_sessions' => 0,
                    'lectures' => 0,
                    'tutorials' => 0,
                    'labs' => 0
                ]
            ];
        }

        // Define the correct day order
        $dayOrder = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        // Get schedule with slots
        $schedule = Schedule::with([
            'slots' => function($query) use ($dayOrder) {
                $orderExpr = "FIELD(day_of_week, '" . implode("','", $dayOrder) . "')";
                $query->orderByRaw($orderExpr)->orderBy('slot_order');
            }
        ])->findOrFail($scheduleId);

        // Get slots data - keep as Collection for easier searching
        $slotsCollection = $schedule->slots->map(function ($slot) {
            return [
                'id' => $slot->id,
                'day_of_week' => $slot->day_of_week,
                'slot_order' => $slot->slot_order,
                'start_time' => $slot->start_time ? $slot->start_time->format('H:i') : null,
                'end_time' => $slot->end_time ? $slot->end_time->format('H:i') : null,
            ];
        });
        
        // Convert to array for frontend consumption
        $slots = $slotsCollection->toArray();

        $slotIds = $schedule->slots->pluck('id')->toArray();

        // Build query for assignments with course schedule relationships
        $assignmentsQuery = \App\Models\Schedule\ScheduleAssignment::whereIn('schedule_slot_id', $slotIds)
            ->with([
                'availableCourseSchedule.availableCourse.course',
                'availableCourseSchedule.program',
                'availableCourseSchedule.level'
            ]);

        // Get all assignments first
        $allAssignments = $assignmentsQuery->get();

        // Group assignments by available course schedule to handle multiple slots properly
        $groupedByCourse = [];
        $nonCourseAssignments = [];
        
        foreach ($allAssignments as $assignment) {
            $acs = $assignment->availableCourseSchedule;
            
            if ($acs) {
                $course = $acs->availableCourse?->course;
                
                // Filter by program if provided
                if ($programId && $acs->program_id && $acs->program_id != $programId) {
                    continue;
                }

                // Filter by level if provided  
                if ($levelId && $acs->level_id && $acs->level_id != $levelId) {
                    continue;
                }
                
                // Filter by group if provided
                if ($groupFilter && $acs->group && $acs->group !== $groupFilter) {
                    continue;
                }
                
                // Filter by group if provided
                if ($groupFilter && $acs->group && $acs->group !== $groupFilter) {
                    continue;
                }

                // Group by available course schedule ID
                $courseKey = $acs->id;
                
                if (!isset($groupedByCourse[$courseKey])) {
                    $groupedByCourse[$courseKey] = [
                        'course_schedule' => $acs,
                        'course' => $course,
                        'assignments' => [],
                        'slot_ids' => [],
                        'slots_data' => []
                    ];
                }
                
                $groupedByCourse[$courseKey]['assignments'][] = $assignment;
                $groupedByCourse[$courseKey]['slot_ids'][] = $assignment->schedule_slot_id;
                
                // Find the slot data for time calculation using the Collection
                $slotData = $slotsCollection->firstWhere('id', $assignment->schedule_slot_id);
                if ($slotData) {
                    $groupedByCourse[$courseKey]['slots_data'][] = $slotData;
                }
            } else {
                // Non-course assignments (events, etc.)
                $nonCourseAssignments[] = $assignment;
            }
        }

        // Process grouped course assignments
        $assignments = [];
        
        foreach ($groupedByCourse as $courseKey => $group) {
            $acs = $group['course_schedule'];
            $course = $group['course'];
            $slotsData = $group['slots_data'];
            
            // Calculate combined time range from first slot start to last slot end
            $startTimes = [];
            $endTimes = [];
            $days = [];
            
            foreach ($slotsData as $slotData) {
                if ($slotData['start_time']) {
                    $startTimes[] = $slotData['start_time'];
                }
                if ($slotData['end_time']) {
                    $endTimes[] = $slotData['end_time'];
                }
                $days[] = $slotData['day_of_week'];
            }
            
            // Get earliest start time and latest end time
            $combinedStartTime = !empty($startTimes) ? min($startTimes) : null;
            $combinedEndTime = !empty($endTimes) ? max($endTimes) : null;
            $combinedDays = array_unique($days);
            
            // Use the first assignment for basic data
            $firstAssignment = $group['assignments'][0];
            
            $assignments[] = [
                'id' => $firstAssignment->id,
                'schedule_slot_id' => $firstAssignment->schedule_slot_id, // Keep first slot ID for compatibility
                'course_code' => $course?->code ?? '-',
                'course_name' => $course?->name ?? '-',
                'activity_type' => ucfirst($acs->activity_type ?? 'lecture'),
                'group' => $acs->group ?? '-',
                'location' => $acs->location ?? null,
                'program_id' => $acs->program_id,
                'program_name' => $acs->program?->name ?? null,
                'level_id' => $acs->level_id,
                'level_name' => $acs->level?->name ?? null,
                'min_capacity' => $acs->min_capacity,
                'max_capacity' => $acs->max_capacity,
                'current_capacity' => $acs->current_capacity ?? 0,
                'status' => $firstAssignment->status,
                'slot_count' => count($group['slot_ids']),
                'has_multiple_slots' => count($group['slot_ids']) > 1,
                'slot_ids' => array_unique($group['slot_ids']),
                'combined_start_time' => $combinedStartTime,
                'combined_end_time' => $combinedEndTime,
                'days' => $combinedDays,
                'available_course_schedule_id' => $acs->id,
            ];
        }
        
        // Add non-course assignments
        foreach ($nonCourseAssignments as $assignment) {
            $assignments[] = [
                'id' => $assignment->id,
                'schedule_slot_id' => $assignment->schedule_slot_id,
                'course_code' => '-',
                'course_name' => $assignment->title ?? '-',
                'activity_type' => ucfirst($assignment->type ?? 'event'),
                'group' => '-',
                'location' => null,
                'program_id' => null,
                'program_name' => null,
                'level_id' => null,
                'level_name' => null,
                'min_capacity' => null,
                'max_capacity' => null,
                'current_capacity' => 0,
                'status' => $assignment->status,
                'slot_count' => 1,
                'has_multiple_slots' => false,
                'slot_ids' => [$assignment->schedule_slot_id],
                'combined_start_time' => null,
                'combined_end_time' => null,
                'days' => [],
                'available_course_schedule_id' => null,
            ];
        }

        // Calculate stats - now based on unique available course schedules
        $uniqueCourses = collect($assignments)->whereNotNull('available_course_schedule_id')->unique('available_course_schedule_id')->count();
        $lectureCount = collect($assignments)->where('activity_type', 'Lecture')->count();
        $tutorialCount = collect($assignments)->where('activity_type', 'Tutorial')->count();
        $labCount = collect($assignments)->where('activity_type', 'Lab')->count();

        return [
            'slots' => $slots,
            'assignments' => $assignments,
            'stats' => [
                'total_courses' => $uniqueCourses,
                'total_sessions' => count($assignments),
                'lectures' => $lectureCount,
                'tutorials' => $tutorialCount,
                'labs' => $labCount
            ]
        ];
    }

    /**
     * Get available groups for a given schedule
     *
     * @param int $scheduleId
     * @return array
     */
    public function getAvailableGroups(int $scheduleId): array
    {
        try {
            // Get schedule slots first
            $schedule = Schedule::findOrFail($scheduleId);
            $slotIds = $schedule->slots->pluck('id')->toArray();

            // Get unique groups from available course schedules that have assignments in this schedule
            $groups = \App\Models\Schedule\ScheduleAssignment::whereIn('schedule_slot_id', $slotIds)
                ->with('availableCourseSchedule')
                ->get()
                ->pluck('availableCourseSchedule.group')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->toArray();

            return $groups;
        } catch (\Exception $e) {
            Log::error('Error getting available groups:', ['error' => $e->getMessage(), 'schedule_id' => $scheduleId]);
            return [];
        }
    }
}