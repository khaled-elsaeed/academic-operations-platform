<?php

namespace App\Services;

use App\Models\AvailableCourse;
use App\Models\AvailableCourseDetail;
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
                $results[] = $this->createAvailableCourseSingle($courseData);
            }
            return $results;
        }
        return $this->createAvailableCourseSingle($data);
    }

    /**
     * Create a single available course with eligibility mode support.
     *
     * @param array $data
     * @return AvailableCourse
     * @throws BusinessValidationException
     */
    public function createAvailableCourseSingle(array $data): AvailableCourse
    {
        $this->validateAvailableCourseData($data);
        $this->ensureAvailableCourseDoesNotExist($data);

        return DB::transaction(function () use ($data) {
            $isUniversal = $data['is_universal'] ?? false;
            $eligibilityMode = $data['eligibility_mode'] ?? 'individual';
            $availableCourse = $this->createAvailableCourseRecord($data);

            // Create course details if provided
            if (isset($data['details']) && is_array($data['details'])) {
                $this->createCourseDetails($availableCourse, $data['details']);
            }

            if (!$isUniversal && $eligibilityMode !== 'universal') {
                $eligibility = $data['eligibility'] ?? [];
                if ($eligibilityMode === 'all_programs') {
                    // All programs for a specific level
                    $levelId = $eligibility[0]['level_id'] ?? null;
                    $allPrograms = Program::pluck('id')->toArray();
                    $bulkEligibility = [];
                    foreach ($allPrograms as $pid) {
                        $bulkEligibility[] = ['program_id' => $pid, 'level_id' => $levelId];
                    }
                    $this->attachEligibilities($availableCourse, $bulkEligibility);
                } elseif ($eligibilityMode === 'all_levels') {
                    // All levels for a specific program
                    $programId = $eligibility[0]['program_id'] ?? null;
                    $allLevels = Level::pluck('id')->toArray();
                    $bulkEligibility = [];
                    foreach ($allLevels as $lid) {
                        $bulkEligibility[] = ['program_id' => $programId, 'level_id' => $lid];
                    }
                    $this->attachEligibilities($availableCourse, $bulkEligibility);
                } else {
                    // Individual mode (custom pairs)
                    $this->attachEligibilities($availableCourse, $eligibility);
                }
            }
            return $availableCourse->fresh(['programs', 'levels', 'details']);
        });
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
        // Bulk update support
        if (isset($data['courses']) && is_array($data['courses'])) {
            $results = [];
            foreach ($data['courses'] as $courseData) {
                $id = $courseData['id'] ?? null;
                if (!$id) continue;
                $results[] = $this->updateAvailableCourseById($id, $courseData);
            }
            return $results;
        }
        if ($availableCourseOrId instanceof AvailableCourse) {
            return $this->updateAvailableCourseSingle($availableCourseOrId, $data);
        }
        $availableCourse = AvailableCourse::findOrFail($availableCourseOrId);
        return $this->updateAvailableCourseSingle($availableCourse, $data);
    }

    /**
     * Update a single available course with eligibility mode support.
     *
     * @param AvailableCourse $availableCourse
     * @param array $data
     * @return AvailableCourse
     * @throws BusinessValidationException
     */
    public function updateAvailableCourseSingle(AvailableCourse $availableCourse, array $data): AvailableCourse
    {
        $this->validateAvailableCourseData($data);
        $this->ensureAvailableCourseDoesNotExist($data, $availableCourse->id);

        return DB::transaction(function () use ($availableCourse, $data) {
            $isUniversal = $data['is_universal'] ?? false;
            $eligibilityMode = $data['eligibility_mode'] ?? 'individual';
            $availableCourse->update([
                'course_id' => $data['course_id'],
                'term_id' => $data['term_id'],
                'is_universal' => $isUniversal,
            ]);

            // Update course details if provided
            if (isset($data['details']) && is_array($data['details'])) {
                $this->updateCourseDetails($availableCourse, $data['details']);
            }

            if (!$isUniversal && $eligibilityMode !== 'universal') {
                $eligibility = $data['eligibility'] ?? [];
                if ($eligibilityMode === 'all_programs') {
                    $levelId = $eligibility[0]['level_id'] ?? null;
                    $allPrograms = Program::pluck('id')->toArray();
                    $bulkEligibility = [];
                    foreach ($allPrograms as $pid) {
                        $bulkEligibility[] = ['program_id' => $pid, 'level_id' => $levelId];
                    }
                    $this->attachEligibilities($availableCourse, $bulkEligibility);
                } elseif ($eligibilityMode === 'all_levels') {
                    $programId = $eligibility[0]['program_id'] ?? null;
                    $allLevels = Level::pluck('id')->toArray();
                    $bulkEligibility = [];
                    foreach ($allLevels as $lid) {
                        $bulkEligibility[] = ['program_id' => $programId, 'level_id' => $lid];
                    }
                    $this->attachEligibilities($availableCourse, $bulkEligibility);
                } else {
                    $this->attachEligibilities($availableCourse, $eligibility);
                }
            } else {
                $availableCourse->setProgramLevelPairs([]);
            }
            return $availableCourse->fresh(['programs', 'levels', 'details']);
        });
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
        $universal = AvailableCourse::where('is_universal', true)->count();

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
        $query = AvailableCourse::with(['course', 'term', 'eligibilities.program', 'eligibilities.level', 'details']);
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
            ->addColumn('details', function ($availableCourse) {
                $details = $availableCourse->details;
                if ($details->isEmpty()) {
                    return '<span class="text-muted">No details</span>';
                }

                $count = $details->count();
                if ($count === 1) {
                    $detail = $details->first();
                    $capacity = '';
                    if (isset($detail->min_capacity) && isset($detail->max_capacity)) {
                        $capacity = sprintf(' (%d-%d)', $detail->min_capacity, $detail->max_capacity);
                    }
                    return sprintf(
                        '<span class="badge bg-info">Group %d - %s%s</span>',
                        $detail->group,
                        ucfirst($detail->activity_type),
                        $capacity
                    );
                }

                return sprintf(
                    '<button type="button" class="btn btn-outline-secondary btn-sm show-details-modal" data-course-id="%d" title="View Course Details">
                        <i class="bx bx-calendar"></i> Details 
                        <span class="badge bg-secondary">%d</span>
                    </button>',
                    $availableCourse->id,
                    $count
                );
            })
            ->addColumn('eligibility', function ($availableCourse) {
                if ($availableCourse->is_universal) {
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
                    '<button type="button" class="btn btn-outline-info btn-sm show-eligibility-modal position-relative group-hover-parent" data-eligibility-pairs="%s" data-ac-id="%d" title="View Eligibility Details" style="position: relative;">
                        <i class="bx bx-list-ul"></i> Eligibility 
                        <span class="badge bg-info eligibility-badge-hover" style="transition: background-color 0.2s, color 0.2s;">%d</span>
                    </button>',
                    e(json_encode($pairs->toArray())),
                    $availableCourse->id,
                    $count
                );
            })
            ->addColumn('capacity', function ($availableCourse) {
                // Show capacity as a summary of all details' capacities
                $details = $availableCourse->details;
                if ($details->isEmpty()) {
                    return '-';
                }
                $ranges = $details->map(function ($detail) {
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
            ->rawColumns(['eligibility', 'details', 'action'])
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

        // --- Universal Status Filter ---
        $isUniversal = $request->input('search_is_universal');
        if ($isUniversal !== null && $isUniversal !== '') {
            $boolVal = filter_var($isUniversal, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($boolVal !== null) {
                $query->where('is_universal', $boolVal);
            }
        }

        // --- Activity Type Filter ---
        $activityType = $request->input('search_activity_type');
        if (!empty($activityType)) {
            $query->whereHas('details', function ($q) use ($activityType) {
                $q->where('activity_type', $activityType);
            });
        }

        // --- Group Filter ---
        $group = $request->input('search_group');
        if (!empty($group)) {
            $query->whereHas('details', function ($q) use ($group) {
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
        $min_capacity = $row['min_capacity'] ?? 1;
        $max_capacity = $row['max_capacity'] ?? 30;

        if ((empty($programName) && empty($levelName))) {
            // Universal course
            $existingCourse = $this->findOrCreateUniversalAvailableCourse($course, $term, $row);
            $this->createOrUpdateCourseDetail($existingCourse, $scheduleCode, $activityType, $group, $day, $slot, $min_capacity, $max_capacity);
            return 'created';
        } else {
            // Program/Level specific course
            $program = $this->findProgramByName($programName);
            $level = $this->findLevelByName($levelName);
            $existingCourse = $this->findOrCreateProgramLevelAvailableCourse($course, $term, $program, $level, $row);
            $this->createOrUpdateCourseDetail($existingCourse, $scheduleCode, $activityType, $group, $day, $slot, $min_capacity, $max_capacity);
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
            ->where('is_universal', true)
            ->first();

        if (!$existingCourse) {
            $existingCourse = AvailableCourse::create([
                'course_id' => $course->id,
                'term_id' => $term->id,
                'is_universal' => true,
            ]);
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
            ->where('is_universal', false)
            ->whereHas('eligibilities', function ($q) use ($program, $level) {
                $q->where('program_id', $program->id)
                  ->where('level_id', $level->id);
            })
            ->first();

        if (!$existingCourse) {
            $existingCourse = AvailableCourse::create([
                'course_id' => $course->id,
                'term_id' => $term->id,
                // Remove min_capacity and max_capacity from AvailableCourse
                // 'min_capacity' => $row['min_capacity'] ?? 1,
                // 'max_capacity' => $row['max_capacity'] ?? 30,
                'is_universal' => false,
            ]);

            CourseEligibility::create([
                'available_course_id' => $existingCourse->id,
                'program_id' => $program->id,
                'level_id' => $level->id,
            ]);
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
     * @param int|null $min_capacity
     * @param int|null $max_capacity
     * @return AvailableCourseDetail
     */
    private function createOrUpdateCourseDetail(
        AvailableCourse $availableCourse,
        ?string $scheduleCode,
        string $activityType,
        int $group,
        ?string $day,
        ?string $slot,
        ?int $min_capacity = 1,
        ?int $max_capacity = 30
    ): AvailableCourseDetail
    {
        $detailData = [
            'available_course_id' => $availableCourse->id,
            'group' => $group,
            'activity_type' => strtolower($activityType),
            'min_capacity' => $min_capacity ?? 1,
            'max_capacity' => $max_capacity ?? 30,
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

        // Create or update the AvailableCourseDetail
        $availableCourseDetail = AvailableCourseDetail::updateOrCreate(
            [
                'available_course_id' => $availableCourse->id,
                'group' => $group,
                'activity_type' => strtolower($activityType),
            ],
            $detailData
        );

        // If a schedule slot was found, create a ScheduleAssignment morphing to this AvailableCourseDetail
        if ($scheduleSlot) {
            ScheduleAssignment::firstOrCreate([
                'schedule_slot_id'   => $scheduleSlot->id,
                'assignable_id'      => $availableCourseDetail->id,
                'assignable_type'    => AvailableCourseDetail::class,
            ], [
                'title'       => $availableCourse->course->name ?? 'Course Activity',
                'description' => $availableCourse->course->description ?? null,
                'location'    => null,
                'capacity'    => $availableCourseDetail->max_capacity,
                'enrolled'    => 0,
                'resources'   => null,
                'status'      => 'scheduled',
                'notes'       => null,
            ]);
        }

        return $availableCourseDetail;
    }

    /**
     * Create course details for an available course.
     *
     * @param AvailableCourse $availableCourse
     * @param array $details
     * @return void
     */
    private function createCourseDetails(AvailableCourse $availableCourse, array $details): void
    {
        foreach ($details as $detail) {
            AvailableCourseDetail::create([
                'available_course_id' => $availableCourse->id,
                'group' => $detail['group'] ?? 1,
                'activity_type' => strtolower($detail['activity_type'] ?? 'lecture'),
                'min_capacity' => $detail['min_capacity'] ?? 1,
                'max_capacity' => $detail['max_capacity'] ?? 30,
                // Add other fields as needed
                'day' => $detail['day'] ?? null,
                'slot' => $detail['slot'] ?? null,
                // 'schedule_id' => $detail['schedule_id'] ?? null,
            ]);
        }
    }

    /**
     * Update course details for an available course.
     *
     * @param AvailableCourse $availableCourse
     * @param array $details
     * @return void
     */
    private function updateCourseDetails(AvailableCourse $availableCourse, array $details): void
    {
        // Delete existing details
        $availableCourse->details()->delete();

        // Create new details
        $this->createCourseDetails($availableCourse, $details);
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
        if (isset($data['details']) && is_array($data['details'])) {
            foreach ($data['details'] as $detail) {
                $minCapacity = $detail['min_capacity'] ?? 1;
                $maxCapacity = $detail['max_capacity'] ?? 30;
                if ($minCapacity > $maxCapacity) {
                    throw new BusinessValidationException('Minimum capacity cannot be greater than maximum capacity in course details.');
                }
                if ($minCapacity < 0 || $maxCapacity < 0) {
                    throw new BusinessValidationException('Capacity values cannot be negative in course details.');
                }
            }
        }
    }

    private function ensureAvailableCourseDoesNotExist(array $data, int $excludeId = null): void
    {
        $isUniversal = $data['is_universal'] ?? false;
        if ($isUniversal) {
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
            ->where('is_universal', true);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    private function availableCourseEligibilitiesExist(array $data, int $excludeId = null): bool
    {
        $programIds = $data['program_ids'] ?? [];
        $levels = $data['levels'] ?? [];
        foreach ($programIds as $programId) {
            foreach ($levels as $level) {
                $query = AvailableCourse::where('course_id', $data['course_id'])
                    ->where('term_id', $data['term_id'])
                    ->whereHas('eligibilities', function ($q) use ($programId, $level) {
                        $q->where('program_id', $programId)->where('level_id', $level);
                    });
                if ($excludeId) {
                    $query->where('id', '!=', $excludeId);
                }
                if ($query->exists()) {
                    return true;
                }
            }
        }
        return false;
    }

    private function createAvailableCourseRecord(array $data): AvailableCourse
    {
        return AvailableCourse::create([
            'course_id' => $data['course_id'],
            'term_id' => $data['term_id'],
            // Remove min_capacity and max_capacity from AvailableCourse
            // 'min_capacity' => $data['min_capacity'] ?? 1,
            // 'max_capacity' => $data['max_capacity'] ?? 30,
            'is_universal' => $data['is_universal'] ?? false,
        ]);
    }

    private function attachEligibilities(AvailableCourse $availableCourse, array $eligibility): void
    {
        $pairs = collect($eligibility)
            ->filter(function ($pair) {
                return isset($pair['program_id']) && isset($pair['level_id']);
            })
            ->map(function ($pair) {
                return [
                    'program_id' => $pair['program_id'],
                    'level_id' => $pair['level_id'],
                ];
            })
            ->toArray();
        $availableCourse->setProgramLevelPairs($pairs);
    }

    private function checkForDuplicateImportCourse(Course $course, Term $term, ?Program $program = null, ?Level $level = null, bool $isUniversal = false): void
    {
        if ($isUniversal) {
            $exists = AvailableCourse::where('course_id', $course->id)
                ->where('term_id', $term->id)
                ->where('is_universal', true)
                ->exists();
            if ($exists) {
                throw new BusinessValidationException('A universal available course for this Course and Term already exists.');
            }
        } else {
            if (!$program || !$level) {
                throw new BusinessValidationException('Program and Level are required for non-universal available courses.');
            }
            $exists = AvailableCourse::where('course_id', $course->id)
                ->where('term_id', $term->id)
                ->where('is_universal', false)
                ->whereHas('eligibilities', function ($q) use ($program, $level) {
                    $q->where('program_id', $program->id)
                      ->where('level_id', $level->id);
                })
                ->exists();
            if ($exists) {
                throw new BusinessValidationException('An available course with the same Course, Term, Program, and Level already exists.');
            }
        }
    }

    // --- Additional methods for fetching and formatting data ---

    public function getAvailableCourseWithEligibilities(int $id): AvailableCourse
    {
        return AvailableCourse::with(['eligibilities.program', 'eligibilities.level', 'course', 'term', 'details'])
            ->findOrFail($id);
    }

    public function getAvailableCourse(int $id): array
    {
        $availableCourse = $this->getAvailableCourseWithEligibilities($id);
        return [
            'id' => $availableCourse->id,
            'course_id' => $availableCourse->course_id,
            'term_id' => $availableCourse->term_id,
            // Remove min_capacity and max_capacity from AvailableCourse
            // 'min_capacity' => $availableCourse->min_capacity,
            // 'max_capacity' => $availableCourse->max_capacity,
            'is_universal' => (bool) $availableCourse->is_universal,
            'eligibilities' => $availableCourse->eligibilities->map(function($eligibility) {
                return [
                    'program_id' => $eligibility->program_id,
                    'level_id' => $eligibility->level_id,
                    'program_name' => $eligibility->program?->name,
                    'level_name' => $eligibility->level?->name,
                ];
            })->toArray(),
            'details' => $availableCourse->details->map(function($detail) {
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

    public function updateAvailableCourseById(int $id, array $data): AvailableCourse
    {
        $availableCourse = AvailableCourse::findOrFail($id);
        return $this->updateAvailableCourse($availableCourse, $data);
    }

    public function getAll(): Collection
    {
        return AvailableCourse::with(['course', 'term', 'eligibilities.program', 'eligibilities.level', 'details'])
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
                    'is_universal' => (bool) $availableCourse->is_universal,
                    'eligibilities' => $availableCourse->eligibilities->map(function($eligibility) {
                        return [
                            'program_id' => $eligibility->program_id,
                            'level_id' => $eligibility->level_id,
                            'program_name' => $eligibility->program?->name,
                            'level_name' => $eligibility->level?->name,
                        ];
                    })->toArray(),
                    'details' => $availableCourse->details->map(function($detail) {
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
     * Get available courses with their details for scheduling purposes.
     *
     * @param array $filters
     * @return Collection
     */
    public function getAvailableCoursesForScheduling(array $filters = []): Collection
    {
        $query = AvailableCourse::with(['course', 'term', 'details', 'eligibilities.program', 'eligibilities.level']);

        if (isset($filters['term_id'])) {
            $query->where('term_id', $filters['term_id']);
        }

        if (isset($filters['program_id']) && isset($filters['level_id'])) {
            $query->where(function($q) use ($filters) {
                $q->where('is_universal', true)
                  ->orWhereHas('eligibilities', function($eligibilityQuery) use ($filters) {
                      $eligibilityQuery->where('program_id', $filters['program_id'])
                                      ->where('level_id', $filters['level_id']);
                  });
            });
        }

        if (isset($filters['activity_type'])) {
            $query->whereHas('details', function($detailQuery) use ($filters) {
                $detailQuery->where('activity_type', $filters['activity_type']);
            });
        }

        return $query->get();
    }

    /**
     * Get course details by available course ID.
     *
     * @param int $availableCourseId
     * @return Collection
     */
    public function getCourseDetails(int $availableCourseId): Collection
    {
        return AvailableCourseDetail::where('available_course_id', $availableCourseId)
            ->orderBy('group')
            ->orderBy('activity_type')
            ->get();
    }

    /**
     * Update course detail.
     *
     * @param int $detailId
     * @param array $data
     * @return AvailableCourseDetail
     */
    public function updateCourseDetail(int $detailId, array $data): AvailableCourseDetail
    {
        $detail = AvailableCourseDetail::findOrFail($detailId);
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
        $detail = AvailableCourseDetail::findOrFail($detailId);
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
        $query = AvailableCourseDetail::where('available_course_id', $availableCourseId)
            ->where('group', $group)
            ->where('activity_type', strtolower($activityType));

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get statistics for course details.
     *
     * @return array
     */
    public function getCourseDetailsStats(): array
    {
        $totalDetails = AvailableCourseDetail::count();
        $lectureCount = AvailableCourseDetail::where('activity_type', 'lecture')->count();
        $labCount = AvailableCourseDetail::where('activity_type', 'lab')->count();
        $tutorialCount = AvailableCourseDetail::where('activity_type', 'tutorial')->count();

        return [
            'total_details' => $totalDetails,
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
                        <a class="dropdown-item viewCourseDetailsBtn" href="javascript:void(0);" data-id="' . e($availableCourse->id) . '">
                            <i class="bx bx-calendar me-1"></i> View Details
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