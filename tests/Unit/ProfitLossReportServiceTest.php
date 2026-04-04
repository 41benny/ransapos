<?php

namespace Tests\Unit;

use App\Services\ProfitLossReportService;
use Tests\TestCase;

class ProfitLossReportServiceTest extends TestCase
{
    public function test_normalize_outlet_ids_accepts_single_int_and_array_inputs(): void
    {
        $service = new ProfitLossReportService();
        $method = new \ReflectionMethod($service, 'normalizeOutletIds');
        $method->setAccessible(true);

        $this->assertSame([5], $method->invoke($service, 5));
        $this->assertSame([2, 4], $method->invoke($service, ['2', '', 4, 2, null, 'x']));
        $this->assertSame([], $method->invoke($service, null));
    }
}
