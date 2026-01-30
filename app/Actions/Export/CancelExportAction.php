<?php

namespace App\Actions\Export;

use App\Models\Task;
use Exception;

class CancelExportAction
{
    /**
     * Cancel export task by UUID.
     *
     * @param string $uuid Task UUID
     * @return array<string, mixed>
     * @throws Exception
     */
    public function execute(string $uuid): array
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
}