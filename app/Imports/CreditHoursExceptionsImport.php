<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CreditHoursExceptionsImport implements ToCollection, WithHeadingRow
{
    /** @var Collection */
    public Collection $rows;

    public function collection(Collection $rows)
    {
        $this->rows = $rows;
    }
}


