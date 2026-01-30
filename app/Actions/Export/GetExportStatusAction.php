<?php

namespace App\Actions\Export;

use App\Models\Task;

class GetExportStatusAction
{
    /**
     * Get export task status by UUID.
     *
     * @param string $uuid Task UUID
     * @param string $downloadRouteName Route name for download URL
     * @return array<string, mixed>|null
     */
    public function execute(string $uuid, string $downloadRouteName): ?array
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
            'message' => $task->message ?? $this->getStatusMessage($task),
            'status_message' => $task->message ?? $this->getStatusMessage($task),
            'download_url' => $this->getDownloadUrl($task, $downloadRouteName),
            'created_at' => $task->created_at?->toIso8601String(),
            'updated_at' => $task->updated_at?->toIso8601String(),
        ];
    }

    protected function getStatusMessage(Task $task): string
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

    protected function getDownloadUrl(Task $task, string $routeName): ?string
    {
        if ($task->status !== 'completed' || !isset($task->result['file_path'])) {
            return null;
        }

        return route($routeName, ['uuid' => $task->uuid]);
    }
}