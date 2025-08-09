<?php

namespace App\Pipelines\AvailableCourse;

use App\Models\AvailableCourse;
use Closure;

class CreateAvailableCoursePipe
{
    /**
     * Handle the pipeline step for creating the AvailableCourse record.
     *
     * @param array $data
     * @param Closure $next
     * @return mixed
     */
    public function handle(array $data, Closure $next)
    {
        \Log::info('Pipeline: Creating available course record', [
            'course_id' => $data['course_id'],
            'term_id' => $data['term_id']
        ]);

        $availableCourse = $this->createAvailableCourseRecord($data);
        
        // Add the created course to the data array for the next pipes
        $data['available_course'] = $availableCourse;

        return $next($data);
    }

    /**
     * Create the AvailableCourse record.
     *
     * @param array $data
     * @return AvailableCourse
     */
    private function createAvailableCourseRecord(array $data): AvailableCourse
    {
        return AvailableCourse::create([
            'course_id' => $data['course_id'],
            'term_id' => $data['term_id'],
            'mode' => $data['mode'] ?? 'individual',
        ]);
    }
}
