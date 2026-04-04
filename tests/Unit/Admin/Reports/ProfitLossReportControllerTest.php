<?php

namespace Tests\Unit\Admin\Reports;

use App\Http\Controllers\Admin\Reports\ProfitLossReportController;
use App\Services\ProfitLossReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class ProfitLossReportControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_resolve_outlet_ids_returns_all_allowed_outlets_when_multi_filter_is_empty(): void
    {
        $controller = new ProfitLossReportController(Mockery::mock(ProfitLossReportService::class));

        $method = new \ReflectionMethod($controller, 'resolveOutletIds');
        $method->setAccessible(true);

        $resolved = $method->invoke(
            $controller,
            new Request(['outlet_ids' => []]),
            new Collection([1, 2, 3]),
        );

        $this->assertSame([1, 2, 3], $resolved);
    }

    public function test_resolve_outlet_ids_intersects_requested_ids_with_allowed_outlets(): void
    {
        $controller = new ProfitLossReportController(Mockery::mock(ProfitLossReportService::class));

        $method = new \ReflectionMethod($controller, 'resolveOutletIds');
        $method->setAccessible(true);

        $resolved = $method->invoke(
            $controller,
            new Request(['outlet_ids' => ['2', '99', '3']]),
            new Collection([1, 2, 3]),
        );

        $this->assertSame([2, 3], $resolved);
    }
}
