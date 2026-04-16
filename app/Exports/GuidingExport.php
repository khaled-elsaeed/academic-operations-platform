<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GuidingExport implements WithMultipleSheets
{
    /**
     * Constructor.
     *
     * @param int|null $termId
     * @param int|null $programId
     * @param int|null $levelId
     */
    public function __construct(
        protected ?int $termId = null,
        protected ?int $programId = null,
        protected ?int $levelId = null,
    ) {}

    /**
     * Build one sheet that streams rows via a generator.
     *
     * @return array
     */
    public function sheets(): array
    {
        // Build a base query and pass only the IDs to the sheet
        // so we never hold all Student models in memory at once.
        $query = Student::query();

        if ($this->programId) {
            $query->where('program_id', $this->programId);
        }

        if ($this->levelId) {
            $query->where('level_id', $this->levelId);
        }

        $studentIds = $query->orderBy('name_en')->pluck('id')->toArray();

        return [
            new GuidingDataSheet($studentIds, $this->termId),
        ];
    }
}
