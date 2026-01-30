<?php

namespace App\Actions\Import;

use App\Exports\GenericImportResultsExport;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadImportAction
{
    /**
     * Download import results by task UUID.
     *
     * @param string $uuid Task UUID
     * @param string $filenamePrefix Prefix for the download filename
     * @return BinaryFileResponse|JsonResponse
     */
    public function execute(string $uuid, string $filenamePrefix = 'import_results'): BinaryFileResponse|JsonResponse
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
}