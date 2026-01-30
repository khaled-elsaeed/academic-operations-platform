<?php

namespace App\Traits;

use App\Actions\Export\{
    ExportAction,
    GetExportStatusAction,
    CancelExportAction,
    DownloadExportAction
};
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait Exportable
{
    /**
     * Get export configuration.
     * Must be implemented by the service using this trait.
     *
     * @return array{job:string, subtype:string, download_route:string}
     */
    abstract protected function getExportConfig(): array;

    /**
     * Export data.
     *
     * @param array $data Export parameters (filters, options, etc.)
     * @return array{task_id:int,uuid:string}
     */
    public function export(array $data = []): array
    {
        $config = $this->getExportConfig();
        $action = app(ExportAction::class);

        return $action->execute(
            jobClass: $config['job'],
            subtype: $config['subtype'],
            parameters: $data
        );
    }

    /**
     * Get export task status by UUID.
     *
     * @param string $uuid Task UUID
     * @return array<string, mixed>|null
     */
    public function getExportStatus(string $uuid): ?array
    {
        $config = $this->getExportConfig();
        $action = app(GetExportStatusAction::class);

        return $action->execute($uuid, $config['download_route']);
    }

    /**
     * Cancel export task by UUID.
     *
     * @param string $uuid Task UUID
     * @return array<string, mixed>
     */
    public function cancelExport(string $uuid): array
    {
        $action = app(CancelExportAction::class);
        return $action->execute($uuid);
    }

    /**
     * Download export file by task UUID.
     *
     * @param string $uuid Task UUID
     * @return BinaryFileResponse|JsonResponse
     */
    public function downloadExport(string $uuid): BinaryFileResponse|JsonResponse
    {
        $action = app(DownloadExportAction::class);
        return $action->execute($uuid);
    }
}