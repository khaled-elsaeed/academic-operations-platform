<?php

namespace App\Services\AvailableCourse\Operations;



use App\Models\AvailableCourse;
use App\Models\AvailableCourseSchedule;
use App\Models\CourseEligibility;
use App\Models\Schedule\ScheduleAssignment;
use App\Models\Schedule\ScheduleSlot;
use App\Models\Program;
use App\Models\Level;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CreateAvailableCourseService
{
    /**
     * Create a new available course with its eligibilities and schedules.
     *
     * @param array $data
     * @return AvailableCourse
     * @throws Exception
     */
    public function create(array $data): AvailableCourse
    {
        return DB::transaction(function () use ($data) {
            try {
                // 1. Create Available Course
                $availableCourse = AvailableCourse::create([
                    'course_id' => $data['course_id'],
                    'term_id'   => $data['term_id'],
                    'mode'      => $data['mode'],
                ]);

                // 2. Handle Eligibility
                $this->handleEligibility($availableCourse, $data);

                // 3. Handle Schedule Details
                if (!empty($data['schedule_details'])) {
                    foreach ($data['schedule_details'] as $detail) {
                        $this->createSchedulesAndAssignments($availableCourse, $detail);
                    }
                }

                return $availableCourse;

            } catch (Exception $e) {
                Log::error('Failed to create available course', [
                    'error' => $e->getMessage(),
                    'data'  => $data,
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Handle eligibility records based on the mode.
     */
    private function handleEligibility(AvailableCourse $availableCourse, array $data): void
    {
        switch ($data['mode']) {
            case AvailableCourse::MODE_INDIVIDUAL:
                if (!empty($data['eligibility'])) {
                    foreach ($data['eligibility'] as $entry) {
                        $groupIds = $entry['group_ids'] ?? [1];
                        foreach ($groupIds as $groupId) {
                            CourseEligibility::create([
                                'available_course_id' => $availableCourse->id,
                                'program_id'          => $entry['program_id'],
                                'level_id'            => $entry['level_id'],
                                'group'               => $groupId,
                            ]);
                        }
                    }
                }
                break;

            case AvailableCourse::MODE_ALL_PROGRAMS:
                $levelId = $data['level_id'];
                $programs = Program::all();
                foreach ($programs as $program) {
                    CourseEligibility::create([
                        'available_course_id' => $availableCourse->id,
                        'program_id'          => $program->id,
                        'level_id'            => $levelId,
                        'group'               => 1, // Default group
                    ]);
                }
                break;

            case AvailableCourse::MODE_ALL_LEVELS:
                $programId = $data['program_id'];
                $levels = Level::all();
                foreach ($levels as $level) {
                    CourseEligibility::create([
                        'available_course_id' => $availableCourse->id,
                        'program_id'          => $programId,
                        'level_id'            => $level->id,
                        'group'               => 1, // Default group
                    ]);
                }
                break;

            case AvailableCourse::MODE_UNIVERSAL:
                // No eligibility records needed as per scopeAvailable logic
                break;
        }
    }

    /**
     * Create schedules and their corresponding assignments.
     */
    private function createSchedulesAndAssignments(AvailableCourse $availableCourse, array $detail): void
    {
        $groupNumbers = $detail['group_numbers'] ?? [1];
        
        foreach ($groupNumbers as $group) {
            // Create Available Course Schedule
            $courseSchedule = AvailableCourseSchedule::create([
                'available_course_id' => $availableCourse->id,
                'group'               => (int) $group,
                'activity_type'       => $detail['activity_type'],
                'location'            => $detail['location'] ?? 'N/A',
                'min_capacity'        => $detail['min_capacity'] ?? 1,
                'max_capacity'        => $detail['max_capacity'] ?? 30,
                'program_id'          => $detail['program_id'] ?? null,
                'level_id'            => $detail['level_id'] ?? null,
            ]);

            // Create assignments for each slot
            if (!empty($detail['schedule_slot_ids'])) {
                foreach ($detail['schedule_slot_ids'] as $slotId) {
                    ScheduleAssignment::create([
                        'schedule_slot_id'             => $slotId,
                        'available_course_schedule_id' => $courseSchedule->id,
                        'type'                         => 'available_course',
                        'title'                        => $availableCourse->course->name ?? 'Course Activity',
                        'description'                  => $availableCourse->course->description ?? null,
                        'enrolled'                     => 0,
                        'status'                       => 'scheduled',
                    ]);
                }
            }
        }
    }
}
