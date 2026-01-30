<?php

namespace App\Traits;

use App\Actions\Import\{
    ImportAction,
    GetImportStatusAction,
    CancelImportAction,
    DownloadImportAction
};
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait Importable
{
    /**
     * Get import configuration.
     * Must be implemented by the service using this trait.
     *
     * @return array{job:string, subtype:string, download_route:string, filename_prefix?:string}
     */
    abstract protected function getImportConfig(): array;

    /**
     * Import data from file.
     *
     * @param array $data Must contain 'file' key with UploadedFile
     * @return array{task_id:int,uuid:string}
     */
    public function import(array $data): array
    {
        $config = $this->getImportConfig();
        $action = app(ImportAction::class);

        return $action->execute(
            file: $data['file'],
            jobClass: $config['job'],
            subtype: $config['subtype']
        );
    }

    /**
     * Get import task status by UUID.
     *
     * @param string $uuid Task UUID
     * @return array<string, mixed>|null
     */
    public function getImportStatus(string $uuid): ?array
    {
        $config = $this->getImportConfig();
        $action = app(GetImportStatusAction::class);

        return $action->execute($uuid, $config['download_route']);
    }

    /**
     * Cancel import task by UUID.
     *
     * @param string $uuid Task UUID
     * @return array<string, mixed>
     */
    public function cancelImport(string $uuid): array
    {
        $action = app(CancelImportAction::class);
        return $action->execute($uuid);
    }

    /**
     * Download import results by task UUID.
     *
     * @param string $uuid Task UUID
     * @return BinaryFileResponse|JsonResponse
     */
    public function downloadImport(string $uuid): BinaryFileResponse|JsonResponse
    {
        $config = $this->getImportConfig();
        $action = app(DownloadImportAction::class);

        $prefix = $config['filename_prefix'] ?? 'import_results';

        return $action->execute($uuid, $prefix);
    }
}