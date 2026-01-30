<?php

namespace App\Actions\Import;

use App\Models\Task;
use Exception;
use Illuminate\Support\Facades\Storage;

class CancelImportAction
{
    /**
     * Cancel import task by UUID.
     *
     * @param string $uuid Task UUID
     * @return array<string, mixed>
     * @throws Exception
     */
    public function execute(string $uuid): array
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
            'error' => __('Import was cancelled by user.'),
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
}