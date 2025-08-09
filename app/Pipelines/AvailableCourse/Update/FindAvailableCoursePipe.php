<?php

namespace App\Pipelines\AvailableCourse\Update;

use App\Models\AvailableCourse;
use App\Exceptions\BusinessValidationException;
use Closure;

class FindAvailableCoursePipe
{
    /**
     * Handle the pipeline step for finding the available course to update.
     *
     * @param array $data
     * @param Closure $next
     * @return mixed
     * @throws BusinessValidationException
     */
    public function handle(array $data, Closure $next)
    {
        $availableCourseId = $data['available_course_id'];
        
        \Log::info('Pipeline: Finding available course for update', [
            'available_course_id' => $availableCourseId
        ]);

        $availableCourse = $this->findAvailableCourse($availableCourseId);
        
        // Add the found course to the data array for the next pipes
        $data['available_course'] = $availableCourse;

        return $next($data);
    }

    /**
     * Find the available course or throw exception.
     *
     * @param int $availableCourseId
     * @return AvailableCourse
     * @throws BusinessValidationException
     */
    private function findAvailableCourse(int $availableCourseId): AvailableCourse
    {
        $availableCourse = AvailableCourse::find($availableCourseId);

        if (!$availableCourse) {
            throw new BusinessValidationException('Available course not found.');
        }

        return $availableCourse;
    }
}
