<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BackdateSalesImport implements ToCollection, WithHeadingRow
{
    private array $rows = [];

    public function collection(Collection $rows): void
    {
        $this->rows = $rows
            ->map(fn ($row) => collect($row)->mapWithKeys(fn ($value, $key) => [trim((string) $key) => $value])->all())
            ->values()
            ->all();
    }

    public function rows(): array
    {
        return $this->rows;
    }
}
