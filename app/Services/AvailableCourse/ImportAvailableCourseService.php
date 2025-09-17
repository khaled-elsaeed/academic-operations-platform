<?php

namespace App\Services\AvailableCourse;

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
use App\Imports\AvailableCoursesImport;
use App\Validators\AvailableCourseImportValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Exceptions\SkipImportRowException;
use Maatwebsite\Excel\Facades\Excel;

class ImportAvailableCourseService
{
    /**
     * Import available courses from uploaded Excel file using Pipeline pattern.
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
                'summary' => [
                    'total_processed' => 0,
                    'total_created' => 0,
                    'total_updated' => 0,
                    'total_errors' => 1,
                ]
            ];
        }
    }

    /**
     * Import available courses from collection of rows using Pipeline pattern.
     *
     * @param Collection $rows
     * @return array
     */
    public function importAvailableCoursesFromRows(Collection $rows): array
    {
        $results = [
            'created' => [],
            'updated' => [],
            'errors' => [],
            'summary' => [
                'total_processed' => 0,
                'total_created' => 0,
                'total_updated' => 0,
                'total_errors' => 0
            ]
        ];

        return DB::transaction(function () use ($rows, $results) {
            foreach ($rows as $index => $row) {
                try {
                    $result = $this->importSingleAvailableCourse($row->toArray(), $index + 1);

                    if ($result === null) {
                        // Defensive: should not happen, but skip if so
                        continue;
                    }

                    if ($result['operation'] === 'create') {
                        $results['created'][] = $result;
                        $results['summary']['total_created']++;
                    } else {
                        $results['updated'][] = $result;
                        $results['summary']['total_updated']++;
                    }

                    $results['summary']['total_processed']++;

                } catch (SkipImportRowException $e) {
                    // continue, do not count as error or processed as this is rest schedule
                    continue;
                } catch (\Exception $e) {
                    $error = [
                        'row_number' => $index + 1,
                        'data' => $row->toArray(),
                        'error' => $e->getMessage()
                    ];

                    $results['errors'][] = $error;
                    $results['summary']['total_errors']++;

                    \Log::error('Error importing available course row', $error);
                }
            }

            $message = $this->generateSummaryMessage($results['summary']);
            $success = $results['summary']['total_errors'] === 0;

            return [
                'success' => $success,
                'message' => $message,
                'created' => $results['created'],
                'updated' => $results['updated'],
                'errors' => $results['errors'],
                'summary' => $results['summary'],
            ];
        });
    }

    /**
     * Import a single available course without using pipelines (inline logic).
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return array
     * @throws BusinessValidationException
     */
    public function importSingleAvailableCourse(array $rowData, int $rowNumber): array
    {
        // Map and validate row
        $mappedData = $this->mapRowData($rowData);

        // Skip if course_code is 'rest'
        if (strtolower(trim($mappedData['course_code'])) === 'rest') {
            throw new SkipImportRowException("Row {$rowNumber} skipped: course_code is 'rest'.");
        }

        AvailableCourseImportValidator::validateRow($mappedData, $rowNumber);

        // Resolve related entities
        $course = $this->findCourseByCode($mappedData['course_code']);
        $term = $this->findTermByCode($mappedData['term_code']);
        $program = $this->findProgramByCode($mappedData['program_code']);
        $level = $this->findLevelByName($mappedData['level_name']);
        $schedule = $this->findScheduleByCode($mappedData['schedule_code']);

        // Determine mode and existence
        $group = $mappedData['group'] ?? 1;
        if (is_null($program) && is_null($level)) {
            $mode = 'universal';
            $existingCourse = $this->findUniversalAvailableCourse($course, $term);
        } else {
            $mode = 'individual';
            $existingCourse = $this->findIndividualAvailableCourse($course, $term, $program, $level, $group);
        }

        $operation = $existingCourse ? 'update' : 'create';

        // Create or update
        if ($operation === 'create') {
            $availableCourse = AvailableCourse::create([
                'course_id' => $course->id,
                'term_id' => $term->id,
                'mode' => $mode,
            ]);

            // If individual and both program & level are present, attach eligibility
            if ($mode === 'individual' && $program && $level) {
                $this->ensureEligibilityExists($availableCourse, $program, $level, $group);
            }
        } else {
            $availableCourse = $existingCourse;
            if ($mode === 'individual' && $program && $level) {
                $this->ensureEligibilityExists($availableCourse, $program, $level, $group);
            }
            $availableCourse->refresh();
        }

        // Handle schedule for this row
        $scheduleId = $schedule->id ?? null;
        $this->createOrUpdateCourseSchedule($availableCourse, $mappedData, [
            'schedule_id' => $scheduleId,
        ]);

        return [
            'operation' => $operation,
            'available_course' => $availableCourse->fresh(['programs', 'levels', 'schedules.scheduleAssignments']),
            'row_number' => $rowNumber,
            'course_code' => $course->code ?? null,
            'term_name' => $term->name ?? null,
        ];
    }

    /**
     * Batch import available courses with improved performance.
     *
     * @param Collection $rows
     * @param int $batchSize
     * @return array
     */
    public function batchImportAvailableCourses(Collection $rows, int $batchSize = 50): array
    {
        $results = [
            'created' => [],
            'updated' => [],
            'errors' => [],
            'summary' => [
                'total_processed' => 0,
                'total_created' => 0,
                'total_updated' => 0,
                'total_errors' => 0,
                'batches_processed' => 0
            ]
        ];

        $batches = $rows->chunk($batchSize);

        foreach ($batches as $batchIndex => $batch) {

            $batchResult = $this->importAvailableCoursesFromRows($batch);
            
            // Merge results
            $results['created'] = array_merge($results['created'], $batchResult['created']);
            $results['updated'] = array_merge($results['updated'], $batchResult['updated']);
            $results['errors'] = array_merge($results['errors'], $batchResult['errors']);
            
            // Update summary
            $results['summary']['total_processed'] += $batchResult['summary']['total_processed'];
            $results['summary']['total_created'] += $batchResult['summary']['total_created'];
            $results['summary']['total_updated'] += $batchResult['summary']['total_updated'];
            $results['summary']['total_errors'] += $batchResult['summary']['total_errors'];
            $results['summary']['batches_processed']++;
        }

        $message = $this->generateSummaryMessage($results['summary']);
        $success = $results['summary']['total_errors'] === 0;
        
        return [
            'success' => $success,
            'message' => $message,
            'created' => $results['created'],
            'updated' => $results['updated'],
            'errors' => $results['errors'],
            'summary' => $results['summary'],
        ];
    }

    /**
     * Generate summary message for import results.
     *
     * @param array $summary
     * @return string
     */
    private function generateSummaryMessage(array $summary): string
    {
        $totalProcessed = $summary['total_processed'];
        $created = $summary['total_created'];
        $updated = $summary['total_updated'];
        $errors = $summary['total_errors'];

        if ($errors === 0) {
            return "Successfully processed {$totalProcessed} available courses. ({$created} created, {$updated} updated).";
        } else {
            return "Import completed with {$totalProcessed} successful ({$created} created, {$updated} updated) and {$errors} failed rows.";
        }
    }

    /**
     * Helpers inlined from pipeline steps
     */

    private function mapRowData(array $row): array
    {
        return [
            'course_code' => $row['course_code'] ?? '',
            'course_name' => $row['course_name'] ?? '',
            'term_code' => $row['term'] ?? '',
            'activity_type' => strtolower($row['activity_type'] ?? 'lecture'),
            'group' => (int)($row['grouping'] ?? 1),
            'day' => $row['day'] ?? null,
            'slot' => $row['slot'] ?? null,
            'time' => $row['time'] ?? null,
            'instructor' => $row['instructor'] ?? null,
            'location' => $row['location'] ?? null,
            'external' => $row['external'] ?? null,
            'level_name' => $row['level'] ?? null,
            'program_code' => $row['program'] ?? null,
            'min_capacity' => (int)($row['min_capacity'] ?? 1),
            'max_capacity' => (int)($row['max_capacity'] ?? 30),
            'schedule_code' => $row['schedule'] ?? null,
        ];
    }

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

    private function findUniversalAvailableCourse($course, $term): ?AvailableCourse
    {
        return AvailableCourse::where('course_id', $course->id)
            ->where('term_id', $term->id)
            ->where('mode', 'universal')
            ->first();
    }

    private function findIndividualAvailableCourse($course, $term, $program, $level, ?int $group = null): ?AvailableCourse
    {
        if (!$program || !$level) {
            return AvailableCourse::where('course_id', $course->id)
                ->where('term_id', $term->id)
                ->where('mode', 'individual')
                ->first();
        }

        $withPair = AvailableCourse::where('course_id', $course->id)
            ->where('term_id', $term->id)
            ->where('mode', 'individual')
            ->whereHas('eligibilities', function ($q) use ($program, $level, $group) {
                $q->where('program_id', $program->id)
                  ->where('level_id', $level->id);
                if (!is_null($group)) {
                    $q->where('group', $group);
                }
            })
            ->first();

        if ($withPair) {
            return $withPair;
        }

        return AvailableCourse::where('course_id', $course->id)
            ->where('term_id', $term->id)
            ->where('mode', 'individual')
            ->first();
    }

    private function ensureEligibilityExists(AvailableCourse $availableCourse, $program, $level, ?int $group = 1): void
    {
        $group = $group ?? 1;

        $exists = $availableCourse->eligibilities()
            ->where('program_id', $program->id)
            ->where('level_id', $level->id)
            ->where('group', $group)
            ->exists();

        if (!$exists) {
            $availableCourse->eligibilities()->create([
                'program_id' => $program->id,
                'level_id' => $level->id,
                'group' => $group,
            ]);
        }
    }

    private function createOrUpdateCourseSchedule($availableCourse, array $mappedData, array $data = []): AvailableCourseSchedule
    {
        $activityType = $mappedData['activity_type'];
        $group = $mappedData['group'];
        $location = $mappedData['location'];
        $minCapacity = $mappedData['min_capacity'];
        $maxCapacity = $mappedData['max_capacity'];

        $scheduleKeys = [
            'available_course_id' => $availableCourse->id,
            'group' => $group,
            'activity_type' => $activityType,
            'location' => $location ?? null,
        ];

        $scheduleValues = [
            'min_capacity' => $minCapacity,
            'max_capacity' => $maxCapacity,
        ];

        $availableCourseSchedule = AvailableCourseSchedule::updateOrCreate($scheduleKeys, $scheduleValues);

        $this->handleScheduleSlotAssignment($availableCourseSchedule, $availableCourse, $mappedData, $data);

        return $availableCourseSchedule;
    }

    private function handleScheduleSlotAssignment(
        AvailableCourseSchedule $courseSchedule,
        $availableCourse,
        array $mappedData,
        array $data = []
    ): void {
        $scheduleId = $data['schedule_id'] ?? null;
        $day = $mappedData['day'] ?? null;
        $slot = $mappedData['slot'] ?? null;

        if (empty($scheduleId) || empty($day) || empty($slot)) {
            Log::info('Skipping schedule slot assignment due to missing scheduleId, day, or slot.', [
                'schedule_id' => $scheduleId,
                'day' => $day,
                'slot' => $slot,
            ]);
            return;
        }

        $schedule = Schedule::find($scheduleId);

        if (!$schedule) {
            Log::info('Schedule not found for scheduleId.', ['schedule_id' => $scheduleId]);
            return;
        }

        $scheduleSlot = $this->findScheduleSlot($schedule, $day, $slot);
        if (!$scheduleSlot) {
            Log::info('Schedule slot not found.', [
                'schedule_id' => $scheduleId,
                'day' => $day,
                'slot' => $slot,
            ]);
            return;
        }

        $this->createOrUpdateScheduleAssignment($courseSchedule, $availableCourse, $scheduleSlot);
    }

    private function findScheduleSlot(Schedule $schedule, string $day, $slot): ?ScheduleSlot
    {
        $scheduleSlot = ScheduleSlot::where('schedule_id', $schedule->id)
            ->where('day_of_week', $day)
            ->where('slot_order', $slot)
            ->first();

        if (!$scheduleSlot) {
            Log::info('No matching schedule slot found.', [
                'schedule_id' => $schedule->id,
                'day_of_week' => $day,
                'slot_order' => $slot,
            ]);
        }

        return $scheduleSlot;
    }

    private function createOrUpdateScheduleAssignment(
        AvailableCourseSchedule $courseSchedule,
        $availableCourse,
        ScheduleSlot $scheduleSlot
    ): ScheduleAssignment {
        $assignmentKeys = [
            'schedule_slot_id' => $scheduleSlot->id,
            'available_course_schedule_id' => $courseSchedule->id,
        ];

        $assignmentValues = [
            'type' => 'available_course',
            'title' => $availableCourse->course->name ?? 'Course Activity',
            'description' => $availableCourse->course->description ?? null,
            'enrolled' => 0,
            'resources' => null,
            'status' => 'scheduled',
            'notes' => null,
        ];

        Log::info('Creating or updating schedule assignment.', [
            'assignment_keys' => $assignmentKeys,
            'assignment_values' => $assignmentValues,
        ]);

        return ScheduleAssignment::updateOrCreate($assignmentKeys, $assignmentValues);
    }
}
