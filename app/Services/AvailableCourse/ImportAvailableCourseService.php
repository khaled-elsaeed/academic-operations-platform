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
use App\Pipelines\AvailableCourse\Import\ValidateImportDataPipe;
use App\Pipelines\AvailableCourse\Import\DetermineOperationPipe;
use App\Pipelines\AvailableCourse\Import\ProcessAvailableCoursePipe;
use App\Pipelines\AvailableCourse\Shared\HandleEligibilityPipe;
use App\Pipelines\AvailableCourse\Import\HandleImportSchedulePipe;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Pipeline\Pipeline;
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
        \Log::info('Starting import of available courses', [
            'total_rows' => $rows->count()
        ]);

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
                    
                    if ($result['operation'] === 'create') {
                        $results['created'][] = $result;
                        $results['summary']['total_created']++;
                    } else {
                        $results['updated'][] = $result;
                        $results['summary']['total_updated']++;
                    }
                    
                    $results['summary']['total_processed']++;
                    
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

            \Log::info('Import completed', $results['summary']);
            
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
     * Import a single available course using Pipeline pattern.
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return array
     * @throws BusinessValidationException
     */
    public function importSingleAvailableCourse(array $rowData, int $rowNumber): array
    {
        \Log::info('Processing single available course import', [
            'row_number' => $rowNumber,
            'data' => $rowData
        ]);

        $pipelineData = [
            'row_data' => $rowData,
            'row_number' => $rowNumber,
        ];

        $result = app(Pipeline::class)
            ->send($pipelineData)
            ->through([
                ValidateImportDataPipe::class,
                DetermineOperationPipe::class,
                ProcessAvailableCoursePipe::class,
                HandleEligibilityPipe::class,
                HandleImportSchedulePipe::class,
            ])
            ->finally(function ($data) {
                \Log::info('Import pipeline execution completed', [
                    'row_number' => $data['row_number'],
                    'operation' => $data['operation'] ?? 'unknown',
                    'available_course_id' => $data['available_course']->id ?? null,
                ]);
            })
            ->then(function ($data) {
                \Log::info('Available course import processed successfully', [
                    'row_number' => $data['row_number'],
                    'operation' => $data['operation'],
                    'available_course_id' => $data['available_course']->id
                ]);
                
                return [
                    'operation' => $data['operation'],
                    'available_course' => $data['available_course']->fresh(['programs', 'levels', 'schedules.scheduleAssignments']),
                    'row_number' => $data['row_number'],
                    'course_code' => $data['course']->code ?? null,
                    'term_name' => $data['term']->name ?? null
                ];
            });

        return $result;
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
        \Log::info('Starting batch import of available courses', [
            'total_rows' => $rows->count(),
            'batch_size' => $batchSize
        ]);

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
            \Log::info('Processing batch', [
                'batch_number' => $batchIndex + 1,
                'batch_size' => $batch->count()
            ]);

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

        \Log::info('Batch import completed', $results['summary']);
        
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
}
