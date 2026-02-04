<?php

namespace App\Jobs\AvailableCourse;

use App\Models\Task;
use App\Traits\Progressable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Batchable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AvailableCoursesExport;
use Throwable;

class ExportAvailableCoursesJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Progressable;

    public int $timeout = 1800; // 30 minutes
    public int $tries = 3;
    public int $maxExceptions = 3;
    public bool $failOnTimeout = true;

    public function __construct(Task $task)
    {
        $this->setTask($task);
        $this->onQueue('background');
    }

    public function handle(): void
    {
        try {
            $this->initProgress($this->task, 100, 'Initializing export...');

            $this->setProgress(10, 'Preparing data for export...');

            $parameters = $this->task->parameters ?? [];
            $termId = $parameters['term_id'] ?? null;

            $this->setProgress(50, 'Generating export file...');

            $export = new AvailableCoursesExport($termId);
            $term = $termId ? \App\Models\Term::find($termId) : null;
            $filename = 'available_courses_' . ($term ? str_replace(' ', '_', strtolower($term->code)) : 'all_terms') . '_' . now()->format('Ymd_His') . '.xlsx';

            // Store the file temporarily
            $tempPath = 'temp/' . $filename;
            Excel::store($export, $tempPath, 'local', \Maatwebsite\Excel\Excel::XLSX);

            $this->setProgress(90, 'Finalizing export...');

            // Move to permanent location
            $permanentPath = 'exports/' . $filename;
            Storage::disk('local')->move($tempPath, $permanentPath);

            $this->task->update([
                'status' => 'completed',
                'progress' => 100,
                'result' => [
                    'file_path' => $permanentPath,
                    'filename' => $filename,
                    'download_url' => route('available_courses.export.download', ['uuid' => $this->task->uuid]),
                ],
                'message' => 'Export completed successfully.',
            ]);

        } catch (Throwable $e) {
            $this->task->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'message' => 'Export failed.',
            ]);
            throw $e;
        }
    }
}
