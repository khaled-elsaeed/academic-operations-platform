<?php

namespace App\Traits;

use App\Models\Task;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait Exportable
{
    /**
     * Export data.
     *
     * @param string $jobClass Fully qualified job class name
     * @param string $subtype Task subtype
     * @param array $parameters Export parameters
     * @return array{task_id:int,uuid:string}
     */
    public function export(string $jobClass, string $subtype, array $parameters = []): array
    {
        $userId = auth()->id() ?? User::systemUser()->id;

        $exportParameters = array_merge([
            'requested_at' => now()->toIso8601String(),
        ], $parameters);

        $task = Progressable::createTask(
            type: 'export',
            userId: $userId,
            parameters: $exportParameters,
            subtype: $subtype,
        );

        $jobClass::dispatch($task);

        return [
            'task_id' => $task->id,
            'uuid' => $task->uuid,
        ];
    }

    /**
     * Get export task status by UUID.
     *
     * @param string $uuid Task UUID
     * @param string|null $downloadRoute The route name for downloading the result
     * @return array<string, mixed>|null
     */
    public function getExportStatus(string $uuid, ?string $downloadRoute = null): ?array
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
            'message' => $task->message ?? $this->getExportStatusMessage($task),
            'status_message' => $task->message ?? $this->getExportStatusMessage($task),
            'download_url' => $this->getExportDownloadUrl($task, $downloadRoute),
            'created_at' => $task->created_at?->toIso8601String(),
            'updated_at' => $task->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Cancel export task by UUID.
     *
     * @param string $uuid Task UUID
     * @return array<string, mixed>
     * @throws Exception
     */
    public function cancelExport(string $uuid): array
    {
        $task = Task::where('uuid', $uuid)->first();

        if (!$task) {
            throw new Exception(__('Export task not found.'));
        }

        if (in_array($task->status, ['completed', 'failed'])) {
            throw new Exception(__('Cannot cancel a completed or failed export.'));
        }

        $task->update([
            'status' => 'cancelled',
            'error' => __('Export was cancelled by user.'),
        ]);

        return [
            'task_id' => $task->id,
            'uuid' => $task->uuid,
            'status' => 'cancelled',
        ];
    }

    /**
     * Download export file by task UUID.
     *
     * @param string $uuid Task UUID
     * @return BinaryFileResponse|JsonResponse
     */
    public function downloadExport(string $uuid): BinaryFileResponse|JsonResponse
    {
        $task = Task::where('uuid', $uuid)->first();

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => __('Export not found.'),
            ], 404);
        }

        if ($task->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => __('Export is not ready for download.'),
                'current_status' => $task->status,
            ], 400);
        }

        $result = $task->result ?? [];
        $filePath = $result['file_path'] ?? null;
        $filename = $result['filename'] ?? 'export.xlsx';

        if (!$filePath || !Storage::disk('local')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => __('Export file not found.'),
            ], 404);
        }

        return response()
            ->download(Storage::disk('local')->path($filePath), $filename)
            ->deleteFileAfterSend(false);
    }

    /**
     * Get a human-readable status message for the export task.
     */
    protected function getExportStatusMessage(Task $task): string
    {
        if (!empty($task->message)) {
            return $task->message;
        }

        return match ($task->status) {
            'queued' => __('Export is queued and will start soon.'),
            'processing' => __('Export is being processed...'),
            'completed' => __('Export completed successfully.'),
            'failed' => $task->error ?? __('Export failed. Please try again.'),
            'cancelled' => __('Export was cancelled.'),
            default => __('Unknown status.'),
        };
    }

    /**
     * Get the download URL for the export result.
     */
    protected function getExportDownloadUrl(Task $task, ?string $routeName): ?string
    {
        if ($task->status !== 'completed' || !isset($task->result['file_path']) || !$routeName) {
            return null;
        }

        return route($routeName, ['uuid' => $task->uuid]);
    }
}
