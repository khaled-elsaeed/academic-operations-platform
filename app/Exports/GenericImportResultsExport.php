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
            $normalized = [];

            if (empty($headings)) {
                $values = array_values((array) $detail);
                foreach ($values as $value) {
                    $normalized[] = $this->stringify($value);
                }
                $rows[] = $normalized;
                continue;
            }

            foreach ($headings as $h) {
                $value = $detail[$h] ?? '';
                $normalized[] = $this->stringify($value);
            }

            $rows[] = $normalized;
        }

        return $rows;
    }

    /**
     * Convert complex values to string for export.
     */
    private function stringify(mixed $value): string
    {
        if (is_scalar($value) || $value === null) {
            return (string) ($value ?? '');
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
