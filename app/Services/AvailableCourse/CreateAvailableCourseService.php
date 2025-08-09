<?php

namespace App\Services\AvailableCourse;

use App\Models\AvailableCourse;
use App\Pipelines\AvailableCourse\Create\ValidateCreateDataPipe;
use App\Pipelines\AvailableCourse\Shared\CheckDuplicatesPipe;
use App\Pipelines\AvailableCourse\Create\CreateAvailableCoursePipe;
use App\Pipelines\AvailableCourse\CreateSchedulePipe;
use App\Pipelines\AvailableCourse\Shared\HandleEligibilityPipe;
use App\Exceptions\BusinessValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Pipeline\Pipeline;

class CreateAvailableCourseService
{
    /**
     * Create a single available course with eligibility mode support using Pipeline pattern.
     *
     * @param array $data
     * @return AvailableCourse
     * @throws BusinessValidationException
     */
    public function createAvailableCourseSingle(array $data): AvailableCourse
    {
        \Log::info('Starting creation of available course using Pipeline', ['data' => $data]);

        return DB::transaction(function () use ($data) {
            $result = app(Pipeline::class)
                ->send($data)
                ->through([
                    ValidateCreateDataPipe::class,
                    CheckDuplicatesPipe::class,
                    CreateAvailableCoursePipe::class,
                    CreateSchedulePipe::class,
                    HandleEligibilityPipe::class,
                ])
                ->finally(function ($data) {
                    // Cleanup and logging actions that should happen regardless of success/failure
                    \Log::info('Pipeline execution completed', [
                        'available_course_id' => $data['available_course']->id ?? null,
                        'course_id' => $data['course_id'],
                        'term_id' => $data['term_id']
                    ]);
                    
                    // Clear any temporary cache if needed
                    // Cache::forget("temp-course-creation:{$data['course_id']}-{$data['term_id']}");
                })
                ->then(function ($data) {
                    \Log::info('Available course created successfully via Pipeline', [
                        'available_course_id' => $data['available_course']->id
                    ]);
                    
                    return $data['available_course']->fresh(['programs', 'levels', 'schedules']);
                });

            return $result;
        });
    }
}