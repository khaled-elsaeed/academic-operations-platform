<?php

namespace App\Services\AvailableCourse;

use App\Models\AvailableCourse;
use App\Pipelines\AvailableCourse\Update\FindAvailableCoursePipe;
use App\Pipelines\AvailableCourse\Update\ValidateUpdateDataPipe;
use App\Pipelines\AvailableCourse\Shared\CheckDuplicatesPipe;
use App\Pipelines\AvailableCourse\Update\UpdateAvailableCoursePipe;
use App\Pipelines\AvailableCourse\Shared\HandleEligibilityPipe;
use App\Pipelines\AvailableCourse\Shared\HandleSchedulePipe;
use App\Exceptions\BusinessValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Pipeline\Pipeline;

class UpdateAvailableCourseService
{
    /**
     * Update an existing available course using Pipeline pattern.
     *
     * @param AvailableCourse|int $availableCourse
     * @param array $data
     * @return AvailableCourse
     * @throws BusinessValidationException
     */
    public function updateAvailableCourse($availableCourse, array $data): AvailableCourse
    {
        $availableCourseId = $availableCourse instanceof AvailableCourse ? $availableCourse->id : $availableCourse;
        
        \Log::info('Starting update of available course using Pipeline', [
            'available_course_id' => $availableCourseId,
            'data' => $data
        ]);

        return DB::transaction(function () use ($availableCourseId, $data) {
            $pipelineData = [
                'available_course_id' => $availableCourseId,
                'update_data' => $data,
            ];

            $result = app(Pipeline::class)
                ->send($pipelineData)
                ->through([
                    FindAvailableCoursePipe::class,
                    ValidateUpdateDataPipe::class,
                    CheckDuplicatesPipe::class,
                    UpdateAvailableCoursePipe::class,
                    HandleEligibilityPipe::class,
                    HandleSchedulePipe::class,
                ])
                ->finally(function ($data) {
                    \Log::info('Update pipeline execution completed', [
                        'available_course_id' => $data['available_course']->id ?? null,
                        'updated_fields' => array_keys($data['update_data'])
                    ]);
                })
                ->then(function ($data) {
                    \Log::info('Available course updated successfully via Pipeline', [
                        'available_course_id' => $data['available_course']->id
                    ]);
                    
                    return $data['available_course']->fresh(['programs', 'levels', 'schedules.scheduleAssignments']);
                });

            return $result;
        });
    }
}