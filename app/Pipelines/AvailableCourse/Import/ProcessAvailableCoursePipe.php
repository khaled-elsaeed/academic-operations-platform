<?php

namespace App\Pipelines\AvailableCourse\Import;

use App\Models\AvailableCourse;
use Closure;

class ProcessAvailableCoursePipe
{
    /**
     * Handle the pipeline step for creating or updating the available course.
     *
     * @param array $data
     * @param Closure $next
     * @return mixed
     */
    public function handle(array $data, Closure $next)
    {
        $operation = $data['operation'];
        $rowNumber = $data['row_number'];
        
        \Log::info('Pipeline: Processing available course', [
            'row_number' => $rowNumber,
            'operation' => $operation
        ]);

        if ($operation === 'create') {
            $this->handleCreate($data);
        } else {
            $this->handleUpdate($data);
        }

        return $next($data);
    }

    /**
     * Handle creating a new available course.
     *
     * @param array &$data
     * @return void
     */
    private function handleCreate(array &$data): void
    {
        $course = $data['course'];
        $term = $data['term'];
        $mode = $data['mode'];
        $program = $data['program'];
        $level = $data['level'];

        \Log::info('Creating new available course', [
            'course_id' => $course->id,
            'term_id' => $term->id,
            'mode' => $mode
        ]);

        // Create the available course directly
        $availableCourse = AvailableCourse::create([
            'course_id' => $course->id,
            'term_id' => $term->id,
            'mode' => $mode,
        ]);

        \Log::info('Available course created successfully', [
            'available_course_id' => $availableCourse->id
        ]);

        $data['available_course'] = $availableCourse;
    }

    /**
     * Handle updating an existing available course.
     *
     * @param array &$data
     * @return void
     */
    private function handleUpdate(array &$data): void
    {
        $existingCourse = $data['existing_available_course'];
        $program = $data['program'];
        $level = $data['level'];
        $mode = $data['mode'];

        \Log::info('Updating existing available course', [
            'available_course_id' => $existingCourse->id,
            'current_mode' => $existingCourse->mode,
            'target_mode' => $mode
        ]);

        // For individual mode, ensure the eligibility exists
        if ($mode === 'individual' && $program && $level) {
            $this->ensureEligibilityExists($existingCourse, $program, $level);
        }

        // Refresh the model to get latest data
        $data['available_course'] = $existingCourse->fresh();
    }

    /**
     * Ensure eligibility exists for the available course.
     *
     * @param AvailableCourse $availableCourse
     * @param \App\Models\Program $program
     * @param \App\Models\Level $level
     * @return void
     */
    private function ensureEligibilityExists(AvailableCourse $availableCourse, $program, $level): void
    {
        $exists = $availableCourse->eligibilities()
            ->where('program_id', $program->id)
            ->where('level_id', $level->id)
            ->exists();

        if (!$exists) {
            \Log::info('Adding missing eligibility to existing available course', [
                'available_course_id' => $availableCourse->id,
                'program_id' => $program->id,
                'level_id' => $level->id
            ]);

            $availableCourse->eligibilities()->create([
                'program_id' => $program->id,
                'level_id' => $level->id,
            ]);
        }
    }
}
