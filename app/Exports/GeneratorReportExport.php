<?php

namespace App\Exports;

use Closure;
use Generator;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GeneratorReportExport implements FromGenerator, WithHeadings, WithColumnFormatting
{
    use Exportable;

    /**
     * @param  array<int, string>  $headings
     * @param  Closure(): Generator  $generatorFactory
     * @param  array<string, string>  $columnFormats
     */
    public function __construct(
        private readonly array $headings,
        private readonly Closure $generatorFactory,
        private readonly array $columnFormats = [],
    ) {}

    public function headings(): array
    {
        return $this->headings;
    }

    public function generator(): Generator
    {
        return ($this->generatorFactory)();
    }

    public function columnFormats(): array
    {
        return $this->columnFormats;
    }
}
