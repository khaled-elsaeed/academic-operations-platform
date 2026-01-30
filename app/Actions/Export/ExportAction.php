<?php

namespace App\Actions\Export;

use App\Models\Task;
use App\Models\User;
use App\Traits\Progressable;

class ExportAction
{
    use Progressable;

    /**
     * Start an async export task.
     *
     * @param string $jobClass Fully qualified job class name
     * @param string $subtype Task subtype (e.g., 'enrollment')
     * @param array $parameters Export parameters (filters, etc.)
     * @return array{task_id:int,uuid:string}
     */
    public function execute(
        string $jobClass,
        string $subtype,
        array $parameters = []
    ): array {
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
}