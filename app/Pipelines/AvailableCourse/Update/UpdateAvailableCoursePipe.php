<?php

namespace App\Pipelines\AvailableCourse\Update;

use Closure;

class UpdateAvailableCoursePipe
{
    /**
     * Handle the pipeline step for updating the AvailableCourse record.
     *
     * @param array $data
     * @param Closure $next
     * @return mixed
     */
    public function handle(array $data, Closure $next)
    {
        $availableCourse = $data['available_course'];
        $updateData = $data['update_data'];

        \Log::info('Pipeline: Updating available course record', [
            'available_course_id' => $availableCourse->id,
            'old_mode' => $availableCourse->mode,
            'new_mode' => $updateData['mode'] ?? $availableCourse->mode
        ]);

        $this->updateAvailableCourseRecord($availableCourse, $updateData);

        return $next($data);
    }

    /**
     * Update the AvailableCourse record.
     *
     * @param \App\Models\AvailableCourse $availableCourse
     * @param array $updateData
     * @return void
     */
    private function updateAvailableCourseRecord($availableCourse, array $updateData): void
    {
        $fieldsToUpdate = [];

        if (isset($updateData['course_id'])) {
            $fieldsToUpdate['course_id'] = $updateData['course_id'];
        }

        if (isset($updateData['term_id'])) {
            $fieldsToUpdate['term_id'] = $updateData['term_id'];
        }

        if (isset($updateData['mode'])) {
            $fieldsToUpdate['mode'] = $updateData['mode'];
        }

        if (!empty($fieldsToUpdate)) {
            $availableCourse->update($fieldsToUpdate);
        }
    }
}
