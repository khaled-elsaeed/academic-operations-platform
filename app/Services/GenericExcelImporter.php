<?php

namespace App\Services;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use App\Models\ImportStaging;
use App\Models\Task;

class GenericExcelImporter
{
    protected Task $task;
    protected string $filePath;
    protected string $importType;

    const SUPPORTED = ['xls','xlsx'];
    const DEFAULT_CHUNK_SIZE = 2000;

    public function __construct(Task $task, string $filePath, string $importType = 'enrollment')
    {
        $this->task = $task;
        $this->filePath = $filePath;
        $this->importType = $importType;
    }

    public function import()
    {
        $this->validateFile();

        $reader = ReaderEntityFactory::createReaderFromFile($this->filePath);
        $reader->open($this->filePath);

        $total = 0;
        $batch = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            $isHeader = true;

            foreach ($sheet->getRowIterator() as $row) {
                if ($isHeader) {
                    $isHeader = false;
                    continue; 
                }

                $rowArray = $row->toArray();

                if (empty(array_filter($rowArray))) {
                    continue;
                }

                $batch[] = $rowArray;
                $total++;

                if (count($batch) >= self::DEFAULT_CHUNK_SIZE) {
                    $this->storeBatch($batch);
                    $batch = [];
                }
            }
            break;
        }

        if (!empty($batch)) {
            $this->storeBatch($batch);
        }

        $reader->close();

        $this->task->update(['total_rows'=> $total]);

        return $total;
    }

    protected function validateFile()
    {
        $ext = strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION));
        if (!in_array($ext, self::SUPPORTED)) {
            throw new \Exception("Unsupported file type: {$ext}");
        }
    }

    protected function storeBatch(array $rows)
    {
        $insert = [];
        foreach ($rows as $row) {
            $insert[] = [
                'task_id' => $this->task->id,
                'import_type' => $this->importType,
                'row_data'=> json_encode($row),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        ImportStaging::insert($insert);
    }
}
