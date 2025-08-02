<?php

namespace App\Services;

use App\Models\AvailableCourse;
use App\Models\AvailableCourseSchedule;
use App\Models\Course;
use App\Models\CourseEligibility;
use App\Models\Schedule\ScheduleAssignment;
use App\Models\Schedule\ScheduleSlot;
use App\Models\Level;
use App\Models\Program;
use App\Models\Term;
use App\Models\Schedule\Schedule;
use App\Exceptions\BusinessValidationException;
use App\Imports\AvailableCoursesImport;
use App\Validators\AvailableCourseImportValidator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;

class AvailableCourseService
{
    private CreateAvailableCourseService $createService;
    // private UpdateAvailableCourseService $updateService;

    /**
     * Constructor - inject the create and update services.
     *
     * @param CreateAvailableCourseService $createService
     * @param UpdateAvailableCourseService $updateService
     */
    public function __construct(
        CreateAvailableCourseService $createService,
        // UpdateAvailableCourseService $updateService
    ) {
        $this->createService = $createService;
        // $this->updateService = $updateService;
    }

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
        
        // if ($availableCourseOrId instanceof AvailableCourse) {
        //     return $this->updateService->updateAvailableCourseSingle($availableCourseOrId, $data);
        // }
        
        // $availableCourse = AvailableCourse::findOrFail($availableCourseOrId);
        // return $this->updateService->updateAvailableCourseSingle($availableCourse, $data);
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
        $availableCourse = AvailableCourse::find($id);
        if (!$availableCourse) {
            throw new BusinessValidationException('Available course not found.');
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
                $pairs = $schedules->map(function ($detail) {
                    $group = $detail->group ?? '-';
                    $activity = ucfirst($detail->activity_type ?? '-');
                    $min = $detail->min_capacity ?? null;
                    $max = $detail->max_capacity ?? null;
                    $capacity = ($min !== null && $max !== null) ? " ({$min}-{$max})" : '';
                    return "Group {$group} / {$activity}{$capacity}";
                });
                $count = $pairs->count();
                if ($count === 1) {
                    return e($pairs->first());
                }
                return sprintf(
                    '<button type="button" class="btn btn-outline-secondary btn-sm show-schedules-modal position-relative group-hover-parent" data-schedules-pairs="%s" data-ac-id="%d" title="View Schedules" style="position: relative;">
                        <i class="bx bx-calendar"></i> Schedules 
                        <span class="badge bg-secondary schedules-badge-hover" style="transition: background-color 0.2s, color 0.2s;">%d</span>
                    </button>',
                    e(json_encode($pairs->toArray())),
                    $availableCourse->id,
                    $count
                );
            })
            ->addColumn('eligibility', function ($availableCourse) {
                if ($availableCourse->eligibility_mode === 'universal') {
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
                    '<button type="button" class="btn btn-outline-info btn-sm show-eligibility-modal position-relative group-hover-parent" data-eligibility-pairs="%s" data-ac-id="%d" title="View Eligibility Schedules" style="position: relative;">
                        <i class="bx bx-list-ul"></i> Eligibility 
                        <span class="badge bg-info eligibility-badge-hover" style="transition: background-color 0.2s, color 0.2s;">%d</span>
                    </button>',
                    e(json_encode($pairs->toArray())),
                    $availableCourse->id,
                    $count
                );
            })
            ->addColumn('capacity', function ($availableCourse) {
                // Show capacity as a summary of all schedules' capacities
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
            ->addColumn('action', function ($availableCourse) {
                return $this->renderActionButtons($availableCourse);
            })
            ->rawColumns(['eligibility', 'schedules', 'action'])
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
        $eligibilityMode = $request->input('search_eligibility_mode');
        if (!empty($eligibilityMode)) {
            $query->where('eligibility_mode', $eligibilityMode);
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
        try {
            $import = new AvailableCoursesImport();
            Excel::import($import, $file);
            $rows = $import->rows ?? collect();
            return $this->importAvailableCoursesFromRows($rows);
        } catch (\Exception $e) {
            Log::error('Failed to import available courses', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);
            return [
                'success' => false,
                'message' => 'Failed to process the uploaded file.',
                'errors' => [$e->getMessage()],
                'created' => 0,
            ];
        }
    }

    /**
     * Import available courses from collection of rows.
     *
     * @param Collection $rows
     * @return array
     */
    public function importAvailableCoursesFromRows(Collection $rows): array
    {
        $errors = [];
        $created = 0;
        $skipped = 0;
        
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;
            try {
                DB::transaction(function () use ($row, $rowNum, &$created, &$skipped) {
                    $result = $this->processImportRow($row->toArray(), $rowNum);
                    if ($result === 'created') {
                        $created++;
                    } else {
                        $skipped++;
                    }
                });
            } catch (ValidationException $e) {
                $errors[] = [
                    'row' => $rowNum,
                    'errors' => $e->errors()["Row {$rowNum}"] ?? [],
                    'original_data' => $row->toArray(),
                ];
            } catch (BusinessValidationException $e) {
                $errors[] = [
                    'row' => $rowNum,
                    'errors' => ['general' => [$e->getMessage()]],
                    'original_data' => $row->toArray(),
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $rowNum,
                    'errors' => ['general' => ['Unexpected error - ' . $e->getMessage()]],
                    'original_data' => $row->toArray(),
                ];
                Log::error('Import row processing failed', [
                    'row' => $rowNum,
                    'error' => $e->getMessage(),
                    'data' => $row
                ]);
            }
        }
        
        $totalProcessed = $created + $skipped;
        $message = empty($errors) 
            ? "Successfully processed {$totalProcessed} available courses. ({$created} created, {$skipped} skipped)." 
            : "Import completed with {$totalProcessed} successful ({$created} created, {$skipped} skipped) and " . count($errors) . " failed rows.";
        $success = empty($errors);
        
        return [
            'success' => $success,
            'message' => $message,
            'errors' => $errors,
            'imported_count' => $totalProcessed,
            'created_count' => $created,
            'skipped_count' => $skipped,
        ];
    }

    /**
     * Process a single import row.
     *
     * @param array $row
     * @param int $rowNum
     * @return string 'created' or 'skipped'
     * @throws ValidationException|BusinessValidationException
     */
    private function processImportRow(array $row, int $rowNum): string
    {
        AvailableCourseImportValidator::validateRow($row, $rowNum);
        
        $course = $this->findCourseByCode($row['course_code'] ?? '');
        $term = $this->findTermByCode($row['term_code'] ?? '');
        $programName = $row['program_name'] ?? null;
        $levelName = $row['level_name'] ?? null;
        $scheduleCode = $row['schedule_code'] ?? null;
        $activityType = $row['activity_type'] ?? 'lecture';
        $group = $row['group'] ?? 1;
        $day = $row['day'] ?? null;
        $slot = $row['slot'] ?? null;
        $minCapacity = $row['min_capacity'] ?? 1;
        $maxCapacity = $row['max_capacity'] ?? 30;

        if (empty($programName) && empty($levelName)) {
            // Universal course
            $existingCourse = $this->findOrCreateUniversalAvailableCourse($course, $term, $row);
            $this->createOrUpdateCourseDetail($existingCourse, $scheduleCode, $activityType, $group, $day, $slot, $minCapacity, $maxCapacity);
            return 'created';
        } else {
            // Program/Level specific course
            $program = $this->findProgramByName($programName);
            $level = $this->findLevelByName($levelName);
            $existingCourse = $this->findOrCreateProgramLevelAvailableCourse($course, $term, $program, $level, $row);
            $this->createOrUpdateCourseDetail($existingCourse, $scheduleCode, $activityType, $group, $day, $slot, $minCapacity, $maxCapacity);
            return 'created';
        }
    }

    /**
     * Find or create universal available course.
     *
     * @param Course $course
     * @param Term $term
     * @param array $row
     * @return AvailableCourse
     */
    private function findOrCreateUniversalAvailableCourse(Course $course, Term $term, array $row): AvailableCourse
    {
        $existingCourse = AvailableCourse::where('course_id', $course->id)
            ->where('term_id', $term->id)
            ->where('eligibility_mode', 'universal')
            ->first();

        if (!$existingCourse) {
            $data = [
                'course_id' => $course->id,
                'term_id' => $term->id,
                'eligibility_mode' => 'universal',
            ];
            $existingCourse = $this->createService->createAvailableCourseSingle($data);
        }

        return $existingCourse;
    }

    /**
     * Find or create program/level specific available course.
     *
     * @param Course $course
     * @param Term $term
     * @param Program $program
     * @param Level $level
     * @param array $row
     * @return AvailableCourse
     */
    private function findOrCreateProgramLevelAvailableCourse(Course $course, Term $term, Program $program, Level $level, array $row): AvailableCourse
    {
        $existingCourse = AvailableCourse::where('course_id', $course->id)
            ->where('term_id', $term->id)
            ->where('eligibility_mode', 'individual')
            ->whereHas('eligibilities', function ($q) use ($program, $level) {
                $q->where('program_id', $program->id)
                  ->where('level_id', $level->id);
            })
            ->first();

        if (!$existingCourse) {
            $data = [
                'course_id' => $course->id,
                'term_id' => $term->id,
                'eligibility_mode' => 'individual',
                'eligibility' => [
                    [
                        'program_id' => $program->id,
                        'level_id' => $level->id,
                    ]
                ],
            ];
            $existingCourse = $this->createService->createAvailableCourseSingle($data);
        }

        return $existingCourse;
    }

    /**
     * Create or update course detail.
     *
     * @param AvailableCourse $availableCourse
     * @param string|null $scheduleCode
     * @param string $activityType
     * @param int $group
     * @param string|null $day
     * @param string|null $slot
     * @param int|null $minCapacity
     * @param int|null $maxCapacity
     * @return AvailableCourseSchedule
     */
    private function createOrUpdateCourseDetail(
        AvailableCourse $availableCourse,
        ?string $scheduleCode,
        string $activityType,
        int $group,
        ?string $day,
        ?string $slot,
        ?int $minCapacity = 1,
        ?int $maxCapacity = 30
    ): AvailableCourseSchedule {
        $detailData = [
            'available_course_id' => $availableCourse->id,
            'group' => $group,
            'activity_type' => strtolower($activityType),
            'min_capacity' => $minCapacity ?? 1,
            'max_capacity' => $maxCapacity ?? 30,
        ];

        $schedule = null;
        if ($scheduleCode) {
            $schedule = $this->findScheduleByCode($scheduleCode);
            $detailData['schedule_id'] = $schedule->id;
        }

        if ($day) {
            $detailData['day'] = $day;
        }

        if ($slot) {
            $detailData['slot'] = $slot;
        }

        // If both day and slot are provided and schedule is found, try to resolve slot id
        $scheduleSlot = null;
        if ($schedule && $day && $slot) {
            $scheduleSlot = ScheduleSlot::where('schedule_id', $schedule->id)
                ->where('day_of_week', $day)
                ->where('slot_order', $slot)
                ->first();

            if ($scheduleSlot) {
                $detailData['schedule_slot_id'] = $scheduleSlot->id;
            }
        }

        // Create or update the AvailableCourseSchedule
        $availableCourseSchedule = AvailableCourseSchedule::updateOrCreate(
            [
                'available_course_id' => $availableCourse->id,
                'group' => $group,
                'activity_type' => strtolower($activityType),
            ],
            $detailData
        );

        // If a schedule slot was found, create a ScheduleAssignment morphing to this AvailableCourseSchedule
        if ($scheduleSlot) {
            ScheduleAssignment::firstOrCreate([
                'schedule_slot_id' => $scheduleSlot->id,
                'assignable_id' => $availableCourseSchedule->id,
                'assignable_type' => AvailableCourseSchedule::class,
            ], [
                'title' => $availableCourse->course->name ?? 'Course Activity',
                'description' => $availableCourse->course->description ?? null,
                'location' => null,
                'capacity' => $availableCourseSchedule->max_capacity,
                'enrolled' => 0,
                'resources' => null,
                'status' => 'scheduled',
                'notes' => null,
            ]);
        }

        return $availableCourseSchedule;
    }

    /**
     * Create course schedules for an available course.
     *
     * @param AvailableCourse $availableCourse
     * @param array $schedules
     * @return void
     */
    private function createCourseSchedules(AvailableCourse $availableCourse, array $schedules): void
    {
        foreach ($schedules as $detail) {
            AvailableCourseSchedule::create([
                'available_course_id' => $availableCourse->id,
                'group' => $detail['group'] ?? 1,
                'activity_type' => strtolower($detail['activity_type'] ?? 'lecture'),
                'min_capacity' => $detail['min_capacity'] ?? 1,
                'max_capacity' => $detail['max_capacity'] ?? 30,
                'day' => $detail['day'] ?? null,
                'slot' => $detail['slot'] ?? null,
            ]);
        }
    }

    /**
     * Update course schedules for an available course.
     *
     * @param AvailableCourse $availableCourse
     * @param array $schedules
     * @return void
     */
    private function updateCourseSchedules(AvailableCourse $availableCourse, array $schedules): void
    {
        // Delete existing schedules
        $availableCourse->schedules()->delete();

        // Create new schedules
        $this->createCourseSchedules($availableCourse, $schedules);
    }

    // --- Private helpers for finding related models and validation ---

    private function findCourseByCode(string $code): Course
    {
        $course = Course::where('code', $code)->first();
        if (!$course) {
            throw new BusinessValidationException("Course with code '{$code}' not found.");
        }
        return $course;
    }

    private function findTermByCode(string $code): Term
    {
        $term = Term::where('code', $code)->first();
        if (!$term) {
            throw new BusinessValidationException("Term with code '{$code}' not found.");
        }
        return $term;
    }

    private function findProgramByName(string $name): Program
    {
        $program = Program::where('name', $name)->first();
        if (!$program) {
            throw new BusinessValidationException("Program '{$name}' not found.");
        }
        return $program;
    }

    private function findLevelByName(string $name): Level
    {
        $level = Level::where('name', $name)->first();
        if (!$level) {
            throw new BusinessValidationException("Level '{$name}' not found.");
        }
        return $level;
    }

    private function findScheduleByCode(string $code): Schedule
    {
        $schedule = Schedule::where('code', $code)->first();
        if (!$schedule) {
            throw new BusinessValidationException("Schedule with code '{$code}' not found.");
        }
        return $schedule;
    }

    private function validateAvailableCourseData(array $data): void
    {
        // Validate min/max capacity for each detail if present
        if (isset($data['schedules']) && is_array($data['schedules'])) {
            foreach ($data['schedules'] as $detail) {
                $minCapacity = $detail['min_capacity'] ?? 1;
                $maxCapacity = $detail['max_capacity'] ?? 30;
                if ($minCapacity > $maxCapacity) {
                    throw new BusinessValidationException('Minimum capacity cannot be greater than maximum capacity in course schedules.');
                }
                if ($minCapacity < 0 || $maxCapacity < 0) {
                    throw new BusinessValidationException('Capacity values cannot be negative in course schedules.');
                }
            }
        }
    }

    private function ensureAvailableCourseDoesNotExist(array $data, int $excludeId = null): void
    {
        $eligibilityMode = $data['eligibility_mode'] ?? 'individual';
        
        if ($eligibilityMode === 'universal') {
            if ($this->universalAvailableCourseExists($data, $excludeId)) {
                throw new BusinessValidationException('A universal available course for this Course and Term already exists.');
            }
        } else {
            if ($this->availableCourseEligibilitiesExist($data, $excludeId)) {
                throw new BusinessValidationException('An available course with the same Course, Term, Program, and Level already exists.');
            }
        }
    }

    private function universalAvailableCourseExists(array $data, int $excludeId = null): bool
    {
        $query = AvailableCourse::where('course_id', $data['course_id'])
            ->where('term_id', $data['term_id'])
            ->where('eligibility_mode', 'universal');
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    private function availableCourseEligibilitiesExist(array $data, int $excludeId = null): bool
    {
        $eligibilityMode = $data['eligibility_mode'] ?? 'individual';
        $courseId = $data['course_id'];
        $termId = $data['term_id'];

        switch ($eligibilityMode) {
            case 'all_programs':
                $levelId = $data['level_id'];
                $query = AvailableCourse::where('course_id', $courseId)
                    ->where('term_id', $termId)
                    ->where(function ($q) use ($levelId) {
                        $q->where('eligibility_mode', 'all_programs')
                          ->whereHas('eligibilities', function ($eq) use ($levelId) {
                              $eq->where('level_id', $levelId);
                          });
                        $q->orWhereHas('eligibilities', function ($eq) use ($levelId) {
                            $eq->where('level_id', $levelId);
                        });
                    });
                break;

            case 'all_levels':
                $programId = $data['program_id'];
                $query = AvailableCourse::where('course_id', $courseId)
                    ->where('term_id', $termId)
                    ->where(function ($q) use ($programId) {
                        $q->where('eligibility_mode', 'all_levels')
                          ->whereHas('eligibilities', function ($eq) use ($programId) {
                              $eq->where('program_id', $programId);
                          });
                        $q->orWhereHas('eligibilities', function ($eq) use ($programId) {
                            $eq->where('program_id', $programId);
                        });
                    });
                break;

            case 'individual':
            default:
                $eligibility = $data['eligibility'] ?? [];
                foreach ($eligibility as $pair) {
                    $programId = $pair['program_id'];
                    $levelId = $pair['level_id'];
                    
                    $query = AvailableCourse::where('course_id', $courseId)
                        ->where('term_id', $termId)
                        ->whereHas('eligibilities', function ($q) use ($programId, $levelId) {
                            $q->where('program_id', $programId)->where('level_id', $levelId);
                        });
                    
                    if ($excludeId) {
                        $query->where('id', '!=', $excludeId);
                    }
                    
                    if ($query->exists()) {
                        return true;
                    }
                }
                return false;
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    // --- Additional methods for fetching and formatting data ---

    public function getAvailableCourseWithEligibilities(int $id): AvailableCourse
    {
        return AvailableCourse::with(['eligibilities.program', 'eligibilities.level', 'course', 'term', 'schedules'])
            ->findOrFail($id);
    }

    public function getAvailableCourse(int $id): array
    {
        $availableCourse = $this->getAvailableCourseWithEligibilities($id);
        return [
            'id' => $availableCourse->id,
            'course_id' => $availableCourse->course_id,
            'term_id' => $availableCourse->term_id,
            'eligibility_mode' => $availableCourse->eligibility_mode,
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
                    'group' => $detail->group,
                    'activity_type' => $detail->activity_type,
                    'day' => $detail->day ?? null,
                    'slot' => $detail->slot ?? null,
                    'schedule_code' => $detail->schedule?->code ?? null,
                    'min_capacity' => $detail->min_capacity ?? 1,
                    'max_capacity' => $detail->max_capacity ?? 30,
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
                    'eligibility_mode' => $availableCourse->eligibility_mode,
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
                            'group' => $detail->group,
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
                $q->where('eligibility_mode', 'universal')
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
        $detail = AvailableCourseSchedule::findOrFail($detailId);
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
            <div class="btn-group">
                <button
                    type="button"
                    class="btn btn-primary btn-icon rounded-pill dropdown-toggle hide-arrow"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >
                    <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item editAvailableCourseBtn" href="' . e($editUrl) . '" data-id="' . e($availableCourse->id) . '">
                            <i class="bx bx-edit me-1"></i> Edit
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item viewCourseSchedulesBtn" href="javascript:void(0);" data-id="' . e($availableCourse->id) . '">
                            <i class="bx bx-calendar me-1"></i> View Schedules
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item deleteAvailableCourseBtn" href="javascript:void(0);" data-id="' . e($availableCourse->id) . '">
                            <i class="bx bx-trash text-danger me-1"></i> Delete
                        </a>
                    </li>
                </ul>
            </div>
        ';
    }
}