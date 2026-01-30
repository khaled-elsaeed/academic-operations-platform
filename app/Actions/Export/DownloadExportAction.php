<?php

namespace App\Actions\Export;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadExportAction
{
    /**
     * Download export file by task UUID.
     *
     * @param string $uuid Task UUID
     * @return BinaryFileResponse|JsonResponse
     */
    public function execute(string $uuid): BinaryFileResponse|JsonResponse
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
}