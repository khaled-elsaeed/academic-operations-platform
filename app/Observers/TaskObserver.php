<?php

namespace App\Observers;

use App\Models\Task;
use Illuminate\Support\Str;

class TaskObserver
{
    /**
     * Handle the Task "creating" event.
     * Automatically generates a UUID before the task is created.
     */
    public function creating(Task $task): void
    {
        if (empty($task->uuid)) {
            $task->uuid = Str::uuid()->toString();
        }
    }
}
