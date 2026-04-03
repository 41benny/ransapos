<?php

namespace Tests\Unit\Exports;

use App\Exports\GeneratorReportExport;
use Tests\TestCase;

class GeneratorReportExportTest extends TestCase
{
    public function test_it_exposes_headings_formats_and_generated_rows(): void
    {
        $export = new GeneratorReportExport(
            headings: ['Col A', 'Col B'],
            generatorFactory: function () {
                yield ['foo', 100];
                yield ['bar', 200];
            },
            columnFormats: ['B' => '#,##0'],
        );

        $this->assertSame(['Col A', 'Col B'], $export->headings());
        $this->assertSame(['B' => '#,##0'], $export->columnFormats());
        $this->assertSame(
            [
                ['foo', 100],
                ['bar', 200],
            ],
            iterator_to_array($export->generator(), false)
        );
    }
}
