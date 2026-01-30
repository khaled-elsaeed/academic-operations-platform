<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class GenericImportResultsExport implements FromArray
{
    protected array $summary;
    protected array $details;
    protected array $headings;

    public function __construct(array $summary = [], array $details = [], array $headings = [])
    {
        $this->summary = $summary;
        $this->details = $details;
        $this->headings = $headings;
    }

    public function array(): array
    {
        $rows = [];

        if (!empty($this->summary)) {
            $rows[] = ['Import Summary'];
            foreach ($this->summary as $key => $value) {
                $rows[] = [ucwords(str_replace(['_', '-'], ' ', $key)), $value];
            }
            $rows[] = [];
        }

        // Determine headings
        $headings = $this->headings;
        if (empty($headings) && !empty($this->details)) {
            $first = $this->details[0];
            if (is_array($first)) {
                $headings = array_keys($first);
            }
        }

        if (!empty($headings)) {
            $rows[] = $headings;
        }

        foreach ($this->details as $detail) {
            if (empty($headings)) {
                $rows[] = array_values((array) $detail);
            } else {
                $row = [];
                foreach ($headings as $h) {
                    $row[] = $detail[$h] ?? '';
                }
                $rows[] = $row;
            }
        }

        return $rows;
    }
}
