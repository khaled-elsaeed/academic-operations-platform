<?php

namespace App\Actions\Import;

use App\Models\Task;
use App\Models\User;
use App\Traits\Progressable;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImportAction
{
    use Progressable;

    /**
     * Start an async import task.
     *
     * @param UploadedFile $file
     * @param string $jobClass Fully qualified job class name
     * @param string $subtype Task subtype (e.g., 'enrollment', 'available_course')
     * @param array $additionalParams Optional additional parameters
     * @return array{task_id:int,uuid:string}
     * @throws Exception
     */
    public function execute(
        UploadedFile $file,
        string $jobClass,
        string $subtype,
        array $additionalParams = []
    ): array {
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

        // Dispatch the job dynamically
        $jobClass::dispatch($task);

        return [
            'task_id' => $task->id,
            'uuid' => $task->uuid,
        ];
    }
}