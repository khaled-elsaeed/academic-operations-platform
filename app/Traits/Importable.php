<?php

namespace App\Traits;

use App\Models\Task;
use App\Models\User;
use App\Exports\GenericImportResultsExport;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait Importable
{
    /**
     * Import data from file.
     *
     * @param UploadedFile $file The file to import
     * @param string $jobClass The job class to dispatch
     * @param string $subtype The subtype of the import
     * @param array $additionalParams Additional parameters for the task
     * @return array{task_id:int,uuid:string}
     * @throws Exception
     */
    public function import(UploadedFile $file, string $jobClass, string $subtype, array $additionalParams = []): array
    {
        if (!$file->isValid()) {
            throw new Exception('Invalid or missing file upload.');
        }

        $userId = auth()->id() ?? User::systemUser()->id;
        $uniqueName = uniqid() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('', $uniqueName, 'local');

        if (!$filePath || !Storage::disk('local')->exists($filePath)) {
            throw new Exception('Failed to store uploaded file.');
        }

        $parameters = array_merge([
            'file_path' => $filePath,
            'original_file_name' => $file->getClientOriginalName(),
            'requested_at' => now()->toIso8601String(),
        ], $additionalParams);

        $task = Progressable::createTask(
            type: 'import',
            userId: $userId,
            parameters: $parameters,
            subtype: $subtype,
        );

        $jobClass::dispatch($task);

        return [
            'task_id' => $task->id,
            'uuid' => $task->uuid,
        ];
    }

    /**
     * Get import task status by UUID.
     *
     * @param string $uuid Task UUID
     * @param string|null $downloadRoute The route name for downloading the result
     * @return array<string, mixed>|null
     */
    public function getImportStatus(string $uuid, ?string $downloadRoute = null): ?array
    {
        $task = Task::where('uuid', $uuid)->first();

        if (!$task) {
            return null;
        }

        return [
            'task_id' => $task->id,
            'uuid' => $task->uuid,
            'type' => $task->type,
            'status' => $task->status,
            'progress' => $task->progress,
            'parameters' => $task->parameters,
            'result' => $task->result,
            'error' => $task->error,
            'message' => $task->message ?? $this->getImportStatusMessage($task),
            'status_message' => $task->message ?? $this->getImportStatusMessage($task),
            'download_url' => $this->getImportDownloadUrl($task, $downloadRoute),
            'created_at' => $task->created_at?->toIso8601String(),
            'updated_at' => $task->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Cancel import task by UUID.
     *
     * @param string $uuid Task UUID
     * @return array<string, mixed>
     * @throws Exception
     */
    public function cancelImport(string $uuid): array
    {
        $task = Task::where('uuid', $uuid)->first();

        if (!$task) {
            throw new Exception(__('Import task not found.'));
        }

        if (in_array($task->status, ['completed', 'failed'])) {
            throw new Exception(__('Cannot cancel a completed or failed import.'));
        }

        $task->update([
            'status' => 'cancelled',
            'error' =>(__('Import was cancelled by user.')),
        ]);

        // Clean up uploaded file
        if (isset($task->parameters['file_path']) &&
            Storage::disk('local')->exists($task->parameters['file_path'])) {
            Storage::disk('local')->delete($task->parameters['file_path']);
        }

        return [
            'task_id' => $task->id,
            'uuid' => $task->uuid,
            'status' => 'cancelled',
        ];
    }

    /**
     * Download import results by task UUID.
     *
     * @param string $uuid Task UUID
     * @param string $filenamePrefix Prefix for the filename
     * @return BinaryFileResponse|JsonResponse
     */
    public function downloadImport(string $uuid, string $filenamePrefix = 'import_results'): BinaryFileResponse|JsonResponse
    {
        $task = Task::where('uuid', $uuid)->first();

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => __('Import not found.'),
            ], 404);
        }

        if ($task->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => __('Import is not ready for download.'),
                'current_status' => $task->status,
            ], 400);
        }

        $result = $task->result ?? [];
        $summary = $result['summary'] ?? [];
        $details = $result['rows'] ?? [];

        if (empty($summary) && empty($details)) {
            return response()->json([
                'success' => false,
                'message' => __('No import results available.'),
            ], 404);
        }

        $filename = $filenamePrefix . '_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new GenericImportResultsExport($summary, $details), $filename);
    }

    /**
     * Get a human-readable status message for the task.
     */
    protected function getImportStatusMessage(Task $task): string
    {
        if (!empty($task->message)) {
            return $task->message;
        }

        return match ($task->status) {
            'queued' => __('Import is queued and will start soon.'),
            'processing' => __('Import is being processed...'),
            'completed' => __('Import completed successfully.'),
            'failed' => $task->error ?? __('Import failed. Please try again.'),
            'cancelled' => __('Import was cancelled.'),
            default => __('Unknown status.'),
        };
    }

    /**
     * Get the download URL for the import result.
     */
    protected function getImportDownloadUrl(Task $task, ?string $routeName): ?string
    {
        if ($task->status !== 'completed' || !isset($task->result['summary']) || !$routeName) {
            // Note: Changed from checking 'report_file_path' which seemed specific to checking 'summary' or task status
            // The original action checked 'report_file_path' but GenericImportResultsExport uses data from DB (result json).
            // Let's stick to status == completed.
            return null;
        }

        return route($routeName, ['uuid' => $task->uuid]);
    }
}
