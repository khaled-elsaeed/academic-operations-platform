<?php

namespace App\Jobs\Student;

use App\Models\Task;
use App\Traits\Progressable;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Batchable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\ImportStaging;
use App\Services\GenericExcelImporter;
use App\Services\Student\Operations\ProcessImportService;
use Throwable;

class ImportStudentsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Progressable;

    public int $timeout = 3600;
    public int $tries = 3;
    public int $maxExceptions = 3;
    public bool $failOnTimeout = true;

    private const SUPPORTED_EXTENSIONS = ['xlsx', 'xls'];
    private const CHUNK_SIZE = 2000;

    private const PROGRESS_VALIDATION = 5;
    private const PROGRESS_COUNTING = 15;
    private const PROGRESS_STAGING_END = 40;

    protected int $totalRows = 0;

    public function __construct(Task $task)
    {
        $this->setTask($task);
        $this->onQueue('background');
    }

    public function handle(): void
    {
        try {
            $this->validateFileExists();

            $this->initProgress($this->task, 100, 'Initializing import...');

            $this->setProgress(self::PROGRESS_VALIDATION, 'Validating file format...');
            $extension = $this->validateAndGetExtension();

            $this->setProgress(self::PROGRESS_COUNTING, 'Counting total rows...');
            $this->totalRows = $this->countRowsAndStage();

            if ($this->totalRows === 0) {
                $this->completeEmptyImport();
                return;
            }

            $this->setImportMetadata($extension);

            $this->setProgress(
                self::PROGRESS_STAGING_END,
                sprintf('Staged %s rows. Starting processing...', number_format($this->totalRows))
            );

            $remainingProgress = 100 - self::PROGRESS_STAGING_END;
            $numChunks = (int) ceil($this->totalRows / self::CHUNK_SIZE);
            $progressPerChunk = $numChunks > 0 ? $remainingProgress / $numChunks : 0;

            $this->initializeChunkTracking($numChunks, $progressPerChunk);
            $this->processAllChunks($progressPerChunk);
            $this->finalizeImport();

        } catch (Throwable $e) {
            $this->handleImportFailure($e);
            throw $e;
        }
    }

    private function initializeChunkTracking(int $numChunks, float $progressPerChunk): void
    {
        DB::transaction(function () use ($numChunks, $progressPerChunk) {
            $this->task->refresh();
            $this->task->lockForUpdate();

            $result = $this->task->result ?? [];
            $result['chunks_total'] = $numChunks;
            $result['chunks_completed'] = 0;
            $result['progress_per_chunk'] = $progressPerChunk;
            $result['summary'] = [
                'total_processed' => 0,
                'created' => 0,
                'updated' => 0,
                'failed' => 0,
            ];
            $result['rows'] = [];

            $this->task->update(['result' => $result]);
        });
    }

    private function processAllChunks(float $progressPerChunk): void
    {
        ImportStaging::where('task_id', $this->task->id)
            ->where('import_type', 'student')
            ->orderBy('id')
            ->chunk(self::CHUNK_SIZE, function ($stagedRows) use ($progressPerChunk) {

                $buffer = $stagedRows->map(fn ($row) => $row->row_data)->toArray();

                if (empty($buffer)) {
                    $this->incrementChunkCounter();
                    return;
                }

                $service = new ProcessImportService($buffer);

                // Process and get results
                $chunkResults = $service->process();

                // Merge results and increment counter in ONE transaction
                $this->mergeChunkResultsAndIncrement($chunkResults);

                // Update progress
                $this->task->refresh();
                $currentProgress = self::PROGRESS_STAGING_END +
                    (($this->task->result['chunks_completed'] ?? 0) * $progressPerChunk);

                $this->setProgress(
                    min(99, $currentProgress),
                    sprintf(
                        'Processing chunk %d of %d (%s rows processed, %s created, %s updated, %s failed)',
                        $this->task->result['chunks_completed'] ?? 0,
                        $this->task->result['chunks_total'] ?? 0,
                        number_format($this->task->result['summary']['total_processed'] ?? 0),
                        number_format($this->task->result['summary']['created'] ?? 0),
                        number_format($this->task->result['summary']['updated'] ?? 0),
                        number_format($this->task->result['summary']['failed'] ?? 0)
                    )
                );
            });
    }

    private function mergeChunkResultsAndIncrement(array $chunkResults): void
    {
        DB::transaction(function () use ($chunkResults) {
            $this->task->refresh();
            $this->task->lockForUpdate();

            $result = $this->task->result ?? [];
            $currentSummary = $result['summary'] ?? [];
            $currentRows = $result['rows'] ?? [];

            $result['summary'] = [
                'total_processed' => ($currentSummary['total_processed'] ?? 0) + ($chunkResults['summary']['total_processed'] ?? 0),
                'created' => ($currentSummary['created'] ?? 0) + ($chunkResults['summary']['created'] ?? 0),
                'updated' => ($currentSummary['updated'] ?? 0) + ($chunkResults['summary']['updated'] ?? 0),
                'failed' => ($currentSummary['failed'] ?? 0) + ($chunkResults['summary']['failed'] ?? 0),
            ];

            $result['rows'] = array_merge($currentRows, $chunkResults['rows'] ?? []);

            $result['chunks_completed'] = ($result['chunks_completed'] ?? 0) + 1;

            $this->task->update(['result' => $result]);
        });
    }

    private function finalizeImport(): void
    {
        // Refresh to get the latest data
        $this->task->refresh();

        $summary = $this->task->result['summary'] ?? [];
        $rows = $this->task->result['rows'] ?? [];

        $this->completeProgress(
            [
                'task_uuid' => $this->task->uuid,
                'total_rows' => $this->totalRows,
                'summary' => $summary,
                'rows' => $rows,
            ],
            sprintf(
                'Import completed: %s processed - %s created, %s updated, %s failed',
                number_format($summary['total_processed'] ?? 0),
                number_format($summary['created'] ?? 0),
                number_format($summary['updated'] ?? 0),
                number_format($summary['failed'] ?? 0)
            )
        );
    }

    protected function validateFileExists(): void
    {
        $filePath = $this->task->parameters['file_path'] ?? null;

        if (!$filePath) {
            throw new Exception(__('File path not provided in task parameters.'));
        }

        if (!Storage::disk('local')->exists($filePath)) {
            throw new Exception(__('File not found: :path', ['path' => $filePath]));
        }
    }

    protected function validateAndGetExtension(): string
    {
        $filePath = $this->task->parameters['file_path'] ?? null;
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (!in_array($ext, self::SUPPORTED_EXTENSIONS, true)) {
            throw new Exception("Unsupported file format: {$ext}. Supported formats: " . implode(', ', self::SUPPORTED_EXTENSIONS));
        }

        return $ext;
    }

    protected function countRowsAndStage(): int
    {
        $filePath = $this->task->parameters['file_path'] ?? null;
        $fullPath = Storage::disk('local')->path($filePath);

        $importer = new GenericExcelImporter(
            $this->task,
            $fullPath,
            'student',
        );

        return $importer->import();
    }

    protected function setImportMetadata(string $extension): void
    {
        $filePath = $this->task->parameters['file_path'] ?? null;

        $this->addMetadata('total_rows', $this->totalRows);
        $this->addMetadata('file_name', basename($filePath));
        $this->addMetadata('file_extension', $extension);
    }

    private function incrementChunkCounter(): void
    {
        DB::transaction(function () {
            $this->task->refresh();
            $this->task->lockForUpdate();

            $result = $this->task->result ?? [];
            $result['chunks_completed'] = ($result['chunks_completed'] ?? 0) + 1;

            $this->task->update(['result' => $result]);
        });
    }

    protected function completeEmptyImport(): void
    {
        $this->completeProgress(
            [
                'task_uuid' => $this->task->uuid,
                'total_rows' => 0,
                'summary' => [
                    'total_processed' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'failed' => 0,
                ],
                'rows' => [],
            ],
            'No data to import'
        );
    }

    protected function handleImportFailure(Throwable $e): void
    {
        $filePath = $this->task->parameters['file_path'] ?? null;

        $this->failProgress($e, [
            'file_path' => $filePath,
            'total_rows' => $this->totalRows,
            'error_message' => $e->getMessage(),
            'error_trace' => $e->getTraceAsString(),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $this->handleImportFailure($exception);
    }
}
