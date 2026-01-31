<?php

declare(strict_types=1);

namespace App\Services\AvailableCourse\Operations;

use App\Models\AvailableCourse;
use App\Models\AvailableCourseSchedule;
use App\Models\Course;
use App\Models\Level;
use App\Models\Program;
use App\Models\Term;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleSlot;
use App\Models\Schedule\ScheduleAssignment;
use App\Exceptions\BusinessValidationException;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProcessImportService
{
    // Column indices
    private const COURSE_CODE_COLUMN = 0;
    private const COURSE_NAME_COLUMN = 1;
    private const TERM_CODE_COLUMN = 2;
    private const ACTIVITY_TYPE_COLUMN = 3;
    private const GROUPING_COLUMN = 4;
    private const DAY_COLUMN = 5;
    private const SLOT_COLUMN = 6;
    private const TIME_COLUMN = 7;
    private const INSTRUCTOR_COLUMN = 8;
    private const LOCATION_COLUMN = 9;
    private const EXTERNAL_COLUMN = 10;
    private const LEVEL_COLUMN = 11;
    private const PROGRAM_COLUMN = 12;
    private const SCHEDULE_CODE_COLUMN = 13;

    private const MIN_CAPACITY = 1;

    private const MAX_CAPACITY_LAB = 30;

    private const MAX_CAPACITY_LECTURE = 30;

    private const MAX_CAPACITY_TUTORIAL = 30;

    protected array $results = [
        'summary' => [
            'total_processed' => 0,
            'created' => 0,
            'reused' => 0,
            'skipped' => 0,
            'failed' => 0,
        ],
        'rows' => [],
    ];

    protected array $groupedData = [];

    public function __construct(
        protected array $rows
    ) {
    }

    /**
     * Main entry point for processing available courses
     */
    public function process(): array
    {
        try {
            if (empty($this->rows)) {
                return $this->results;
            }

            // Step 1: Build grouped data structure
            $this->buildGroupedDataStructure();

            // Step 2: Process grouped data
            $this->processGroupedData();

            return $this->results;

        } catch (Throwable $e) {
            Log::error('Available Course processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    // ==========================================
    // STEP 1: BUILD GROUPED DATA STRUCTURE
    // ==========================================

    /**
     * Build grouped data structure:
     * - Group by: course + term (available course level)
     * - Within each course: group schedules by academicCourseId + group + activity + location + program + level
     * - Within each schedule: collect all slots
     */
    private function buildGroupedDataStructure(): void
    {
        foreach ($this->rows as $index => $row) {
            $rowNumber = $index + 1;

            try {
                $data = $this->extractDataFromRow($row);
                
                // Skip 'rest' course code
                if (strtolower($data['course_code']) === 'rest') {
                    $this->results['summary']['skipped']++;
                    continue;
                }

                $this->validateRowData($data, $rowNumber);

                // Build available course key (course + term)
                $availableCourseKey = $this->buildAvailableCourseKey($data);

                // Build schedule key (academicCourseId + group + activity + location + program + level)
                $scheduleKey = $this->buildScheduleKey($data);

                // Initialize available course group if not exists
                if (!isset($this->groupedData[$availableCourseKey])) {
                    $this->groupedData[$availableCourseKey] = [
                        'course_code' => $data['course_code'],
                        'course_name' => $data['course_name'],
                        'term_code' => $data['term_code'],
                        'schedules' => [],
                        'row_numbers' => [],
                    ];
                }

                // Initialize schedule group if not exists
                if (!isset($this->groupedData[$availableCourseKey]['schedules'][$scheduleKey])) {
                    $this->groupedData[$availableCourseKey]['schedules'][$scheduleKey] = [
                        'academic_course_id' => $data['course_code'],
                        'group' => $data['group'],
                        'activity_type' => $data['activity_type'],
                        'location' => $data['location'],
                        'program_code' => $data['program_code'],
                        'level_name' => $data['level_name'],
                        'schedule_code' => $data['schedule_code'],
                        'instructor' => $data['instructor'],
                        'external' => $data['external'],
                        'min_capacity' => $data['min_capacity'],
                        'max_capacity' => $data['max_capacity'],
                        'slots' => [],
                    ];
                }

                // Add slot to schedule
                $this->groupedData[$availableCourseKey]['schedules'][$scheduleKey]['slots'][] = [
                    'day' => $data['day'],
                    'slot' => $data['slot'],
                    'time' => $data['time'],
                ];

                // Track row numbers for this available course
                $this->groupedData[$availableCourseKey]['row_numbers'][] = $rowNumber;

            } catch (ValidationException $e) {
                $this->results['summary']['failed']++;
                $this->recordRowError($row, $rowNumber, $e);
            } catch (Exception $e) {
                $this->results['summary']['failed']++;
                $this->recordRowError($row, $rowNumber, $e);
            }

            $this->results['summary']['total_processed']++;
        }
    }

    /**
     * Build key for available course (course + term)
     */
    private function buildAvailableCourseKey(array $data): string
    {
        return $data['course_code'] . '|' . $data['term_code'];
    }

    /**
     * Build key for schedule (academicCourseId + group + activity + location + program + level)
     */
    private function buildScheduleKey(array $data): string
    {
        return implode('|', [
            $data['course_code'],
            $data['group'],
            $data['activity_type'],
            $data['location'],
            $data['program_code'],
            $data['level_name'],
        ]);
    }

    // ==========================================
    // STEP 2: PROCESS GROUPED DATA
    // ==========================================

    /**
     * Process all grouped available courses
     */
    private function processGroupedData(): void
    {
        foreach ($this->groupedData as $availableCourseKey => $courseGroup) {
            try {
                $this->processAvailableCourseGroup($courseGroup);
            } catch (Exception $e) {
                $this->results['summary']['failed']++;
                Log::error('Failed to process available course group', [
                    'course_code' => $courseGroup['course_code'],
                    'term_code' => $courseGroup['term_code'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Process a single available course group with all its schedules
     */
    private function processAvailableCourseGroup(array $courseGroup): void
    {
        DB::beginTransaction();
        try {
            // Resolve base entities (course, term)
            $course = $this->findCourseByCode($courseGroup['course_code']);
            $term = $this->findTermByCode($courseGroup['term_code']);

            // Determine mode based on first schedule (all schedules should have same mode logic)
            $firstSchedule = reset($courseGroup['schedules']);
            $program = $this->findProgramByCode($firstSchedule['program_code']);
            $level = $this->findLevelByName($firstSchedule['level_name']);
            $courseMode = $this->determineCourseMode($program, $level);

            // Find or create available course
            $courseResult = $this->findOrCreateAvailableCourse($course, $term, $courseMode);
            $availableCourse = $courseResult['course'];
            $operation = $courseResult['operation'];

            // Process each schedule with its slots
            foreach ($courseGroup['schedules'] as $scheduleData) {
                $this->processScheduleGroup($availableCourse, $scheduleData, $courseMode);
            }

            DB::commit();

            // Record success
            if ($operation === 'create') {
                $this->results['summary']['created']++;
            } else {
                $this->results['summary']['reused']++;
            }

            $this->recordGroupSuccess($availableCourse, $courseGroup, $operation);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process a single schedule group with all its slots
     */
    private function processScheduleGroup(
        AvailableCourse $availableCourse,
        array $scheduleData,
        string $courseMode
    ): void {
        // Resolve schedule entities
        $program = $this->findProgramByCode($scheduleData['program_code']);
        $level = $this->findLevelByName($scheduleData['level_name']);
        $schedule = $this->findScheduleByCode($scheduleData['schedule_code']);

        // Create eligibility if individual mode
        if ($courseMode === 'individual') {
            $this->findOrCreateEligibility($availableCourse, $program, $level, $scheduleData['group']);
        }

        // Create course schedule
        $courseSchedule = $this->createCourseSchedule($availableCourse, $program, $level, $scheduleData);

        // Create schedule assignments for all slots
        if ($schedule) {
            foreach ($scheduleData['slots'] as $slotData) {
                $this->createScheduleAssignment(
                    $courseSchedule,
                    $availableCourse,
                    $schedule,
                    $slotData
                );
            }
        }
    }

    // ==========================================
    // DATA EXTRACTION & VALIDATION
    // ==========================================

    /**
     * Map raw row data to standardized format
     */
    private function extractDataFromRow(array $row): array
    {
        $activity_type = strtolower(trim((string)($row[self::ACTIVITY_TYPE_COLUMN] ?? 'lecture')));

        $max_capacity = match ($activity_type) {
            'lab' => self::MAX_CAPACITY_LAB,
            'lecture' => self::MAX_CAPACITY_LECTURE,
            'tutorial' => self::MAX_CAPACITY_TUTORIAL,
            default => self::MAX_CAPACITY_LAB,
        };

        return [
            'course_code' => trim((string)($row[self::COURSE_CODE_COLUMN] ?? '')),
            'course_name' => trim((string)($row[self::COURSE_NAME_COLUMN] ?? '')),
            'term_code' => trim((string)($row[self::TERM_CODE_COLUMN] ?? '')),
            'activity_type' => $activity_type,
            'group' => (int)($row[self::GROUPING_COLUMN] ?? 1),
            'day' => strtolower(trim((string)($row[self::DAY_COLUMN] ?? ''))),
            'slot' => (int)($row[self::SLOT_COLUMN] ?? 0),
            'time' => $row[self::TIME_COLUMN] ?? null,
            'instructor' => $row[self::INSTRUCTOR_COLUMN] ?? null,
            'location' => trim((string)($row[self::LOCATION_COLUMN] ?? '')),
            'external' => $row[self::EXTERNAL_COLUMN] ?? null,
            'level_name' => trim((string)($row[self::LEVEL_COLUMN] ?? '')),
            'program_code' => trim((string)($row[self::PROGRAM_COLUMN] ?? '')),
            'min_capacity' => self::MIN_CAPACITY,
            'max_capacity' => $max_capacity,
            'schedule_code' => $row[self::SCHEDULE_CODE_COLUMN] ?? null,
        ];
    }

    /**
     * Validate row data against defined rules
     */
    private function validateRowData(array $data, int $rowNumber): void
    {
        $validator = Validator::make($data, [
            'course_code' => 'required|exists:courses,code',
            'term_code' => 'required|exists:terms,code',
            'activity_type' => 'required|string',
            'group' => 'required|integer|min:1',
            'day' => 'required|string',
            'slot' => 'required|integer|min:1',
            'location' => 'required|string',
            'level_name' => 'required|exists:levels,name',
            'program_code' => 'required|exists:programs,code',
        ], [
            'course_code.required' => 'Course code is required.',
            'course_code.exists' => 'Course code does not exist.',
            'term_code.required' => 'Term is required.',
            'term_code.exists' => 'Term code does not exist.',
            'activity_type.required' => 'Activity type is required.',
            'group.required' => 'Grouping is required.',
            'group.integer' => 'Grouping must be an integer.',
            'group.min' => 'Grouping must be at least 1.',
            'day.required' => 'Day is required.',
            'slot.required' => 'Slot is required.',
            'slot.integer' => 'Slot must be an integer.',
            'slot.min' => 'Slot must be at least 1.',
            'location.required' => 'Location is required.',
            'program_code.exists' => 'Program does not exist.',
            'level_name.exists' => 'Level does not exist.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                "Row $rowNumber" => $validator->errors()->all(),
            ]);
        }
    }

    // ==========================================
    // ENTITY RESOLUTION
    // ==========================================

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

    private function findProgramByCode(?string $code): ?Program
    {
        if (empty($code)) {
            return null;
        }

        $program = Program::where('code', $code)->first();

        if (!$program) {
            throw new BusinessValidationException("Program with code '{$code}' not found.");
        }

        return $program;
    }

    private function findLevelByName(?string $name): ?Level
    {
        if (empty($name)) {
            return null;
        }

        $level = Level::where('name', $name)->first();

        if (!$level) {
            throw new BusinessValidationException("Level with name '{$name}' not found.");
        }

        return $level;
    }

    private function findScheduleByCode(?string $code): ?Schedule
    {
        if (empty($code)) {
            return null;
        }

        $schedule = Schedule::where('code', $code)->first();

        if (!$schedule) {
            throw new BusinessValidationException("Schedule with code '{$code}' not found.");
        }

        return $schedule;
    }

    // ==========================================
    // MODE DETERMINATION & EXISTENCE CHECK
    // ==========================================

    /**
     * Determine if course should be universal or individual mode
     */
    private function determineCourseMode(?Program $program, ?Level $level): string
    {
        return (is_null($program) && is_null($level)) 
            ? 'universal' 
            : 'individual';
    }

    /**
     * Find existing available course or create new one
     */
    private function findOrCreateAvailableCourse(Course $course, Term $term, string $mode): array
    {
        $query = AvailableCourse::where('course_id', $course->id)
            ->where('term_id', $term->id)
            ->where('mode', $mode);

        $existing = $query->first();

        if ($existing) {
            return ['course' => $existing, 'operation' => 'reuse'];
        }

        $newCourse = AvailableCourse::create([
            'course_id' => $course->id,
            'term_id' => $term->id,
            'mode' => $mode,
        ]);

        return ['course' => $newCourse, 'operation' => 'create'];
    }

    // ==========================================
    // AVAILABLE COURSE CREATION
    // ==========================================

    /**
     * Find or create eligibility record for individual mode
     */
    private function findOrCreateEligibility(
        AvailableCourse $availableCourse,
        ?Program $program,
        ?Level $level,
        int $group
    ): void {
        if (!$program || !$level) {
            return;
        }

        // Check if eligibility already exists
        $existingEligibility = $availableCourse->eligibilities()
            ->where('program_id', $program->id)
            ->where('level_id', $level->id)
            ->where('group', $group)
            ->first();

        if ($existingEligibility) {
            return;
        }

        $eligibility = $availableCourse->eligibilities()->create([
            'program_id' => $program->id,
            'level_id' => $level->id,
            'group' => $group,
        ]);
    }

    // ==========================================
    // SCHEDULE CREATION
    // ==========================================

    /**
     * Create course schedule
     */
    private function createCourseSchedule(
        AvailableCourse $availableCourse,
        ?Program $program,
        ?Level $level,
        array $scheduleData
    ): AvailableCourseSchedule {
        return AvailableCourseSchedule::create([
            'available_course_id' => $availableCourse->id,
            'program_id' => $program?->id,
            'level_id' => $level?->id,
            'group' => $scheduleData['group'],
            'activity_type' => $scheduleData['activity_type'],
            'location' => $scheduleData['location'],
            'min_capacity' => $scheduleData['min_capacity'],
            'max_capacity' => $scheduleData['max_capacity'],
        ]);
    }

    /**
     * Create schedule slot assignment
     */
    private function createScheduleAssignment(
        AvailableCourseSchedule $courseSchedule,
        AvailableCourse $availableCourse,
        Schedule $schedule,
        array $slotData
    ): void {
        $day = $slotData['day'] ?? null;
        $slot = $slotData['slot'] ?? null;

        if (empty($day) || empty($slot)) {
            Log::warning('Skipping schedule assignment due to missing day or slot.', [
                'schedule_id' => $schedule->id,
                'day' => $day,
                'slot' => $slot,
            ]);
            return;
        }

        $scheduleSlot = $this->findScheduleSlot($schedule, $day, $slot);

        if (!$scheduleSlot) {
            Log::warning('Schedule slot not found.', [
                'schedule_id' => $schedule->id,
                'day' => $day,
                'slot' => $slot,
            ]);
            return;
        }

        ScheduleAssignment::create([
            'schedule_slot_id' => $scheduleSlot->id,
            'available_course_schedule_id' => $courseSchedule->id,
            'type' => 'available_course',
            'title' => $availableCourse->course->name ?? 'Course Activity',
            'description' => $availableCourse->course->description ?? null,
            'enrolled' => 0,
            'resources' => null,
            'status' => 'scheduled',
            'notes' => null,
        ]);
    }

    /**
     * Find schedule slot by day and slot order
     */
    private function findScheduleSlot(Schedule $schedule, string $day, $slot): ?ScheduleSlot
    {
        \Log::info('Finding schedule slot', [
            'schedule_id' => $schedule->id,
            'day' => $day,
            'slot' => $slot,
        ]);
        return ScheduleSlot::where('schedule_id', $schedule->id)
            ->where('day_of_week', $day)
            ->where('slot_order', $slot)
            ->first();
    }

    // ==========================================
    // RESULT TRACKING
    // ==========================================

    private function recordGroupSuccess(
        AvailableCourse $availableCourse,
        array $courseGroup,
        string $operation
    ): void {
        $this->results['rows'][] = [
            'operation' => $operation,
            'available_course_id' => $availableCourse->id,
            'course_code' => $courseGroup['course_code'],
            'term_code' => $courseGroup['term_code'],
            'schedules_created' => count($courseGroup['schedules']),
            'row_numbers' => $courseGroup['row_numbers'],
            'status' => 'success',
            'message' => 'Created successfully',
        ];
    }

    private function recordRowError(array $row, int $rowNumber, Exception $e): void
    {
        $this->results['rows'][] = [
            'row_number' => $rowNumber,
            'course_code' => $row[self::COURSE_CODE_COLUMN] ?? 'N/A',
            'status' => 'error',
            'message' => $e->getMessage(),
        ];
    }

    /**
     * Get the results array
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Get the grouped data structure (for debugging)
     */
    public function getGroupedData(): array
    {
        return $this->groupedData;
    }
}