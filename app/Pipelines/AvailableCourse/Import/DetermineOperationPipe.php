<?php

namespace App\Pipelines\AvailableCourse\Import;

use App\Models\AvailableCourse;
use Closure;

class DetermineOperationPipe
{
    /**
     * Handle the pipeline step for determining if this is a create or update operation.
     *
     * @param array $data
     * @param Closure $next
     * @return mixed
     */
    public function handle(array $data, Closure $next)
    {
        $course = $data['course'];
        $term = $data['term'];
        $program = $data['program'];
        $level = $data['level'];
        
        \Log::info('Pipeline: Determining operation type', [
            'row_number' => $data['row_number'],
            'course_id' => $course->id,
            'term_id' => $term->id,
            'has_program' => !is_null($program),
            'has_level' => !is_null($level)
        ]);

        // Determine the mode and find existing available course
        if (is_null($program) && is_null($level)) {
            // Universal mode
            $mode = 'universal';
            $existingCourse = $this->findUniversalAvailableCourse($course, $term);
        } else {
            // Individual mode with specific program/level eligibility
            $mode = 'individual';
            $existingCourse = $this->findIndividualAvailableCourse($course, $term, $program, $level);
        }

        // Determine operation type
        $operation = $existingCourse ? 'update' : 'create';
        
        \Log::info('Operation determined', [
            'row_number' => $data['row_number'],
            'operation' => $operation,
            'mode' => $mode,
            'existing_course_id' => $existingCourse?->id
        ]);

        // Add operation info to pipeline data
        $data['operation'] = $operation;
        $data['mode'] = $mode;
        $data['existing_available_course'] = $existingCourse;

        return $next($data);
    }

    /**
     * Find existing universal available course.
     *
     * @param \App\Models\Course $course
     * @param \App\Models\Term $term
     * @return AvailableCourse|null
     */
    private function findUniversalAvailableCourse($course, $term): ?AvailableCourse
    {
        return AvailableCourse::where('course_id', $course->id)
            ->where('term_id', $term->id)
            ->where('mode', 'universal')
            ->first();
    }

    /**
     * Find existing individual available course with specific eligibility.
     *
     * @param \App\Models\Course $course
     * @param \App\Models\Term $term
     * @param \App\Models\Program|null $program
     * @param \App\Models\Level|null $level
     * @return AvailableCourse|null
     */
    private function findIndividualAvailableCourse($course, $term, $program, $level): ?AvailableCourse
    {
        if (!$program || !$level) {
            // If we don't have both program and level, find any individual course for this course+term
            return AvailableCourse::where('course_id', $course->id)
                ->where('term_id', $term->id)
                ->where('mode', 'individual')
                ->first();
        }

        // Try to find an individual-mode course that ALREADY has this eligibility pair
        $withPair = AvailableCourse::where('course_id', $course->id)
            ->where('term_id', $term->id)
            ->where('mode', 'individual')
            ->whereHas('eligibilities', function ($q) use ($program, $level) {
                $q->where('program_id', $program->id)
                  ->where('level_id', $level->id);
            })
            ->first();

        if ($withPair) {
            return $withPair;
        }

        // Find any existing individual-mode course for this course+term
        return AvailableCourse::where('course_id', $course->id)
            ->where('term_id', $term->id)
            ->where('mode', 'individual')
            ->first();
    }
}
