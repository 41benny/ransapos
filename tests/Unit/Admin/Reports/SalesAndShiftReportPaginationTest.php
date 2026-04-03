<?php

namespace Tests\Unit\Admin\Reports;

use App\Http\Controllers\Admin\Reports\SalesReportController;
use App\Http\Controllers\Admin\Reports\ShiftReportController;
use App\Models\CashSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class SalesAndShiftReportPaginationTest extends TestCase
{
    public function test_sales_paginate_collection_returns_length_aware_paginator(): void
    {
        $controller = new SalesReportController();
        $request = Request::create('/admin/reports/sales', 'GET', ['page' => 2]);

        $method = new \ReflectionMethod($controller, 'paginateCollection');
        $method->setAccessible(true);

        LengthAwarePaginator::currentPageResolver(fn () => 2);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $method->invoke(
            $controller,
            collect(range(1, 205)),
            $request,
            100
        );

        LengthAwarePaginator::currentPageResolver(fn () => 1);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertSame(205, $paginator->total());
        $this->assertCount(100, $paginator->items());
        $this->assertSame(101, $paginator->items()[0]);
        $this->assertSame(200, $paginator->items()[99]);
    }

    public function test_shift_build_session_diagnostics_uses_aggregate_metrics_without_loaded_sales_relation(): void
    {
        $controller = new ShiftReportController();

        $cashSession = new CashSession([
            'total_sales' => 150000,
            'opened_pos_device_id' => 11,
            'closed_pos_device_id' => 12,
        ]);
        $cashSession->opened_at = Carbon::parse('2026-04-03 08:00:00');
        $cashSession->closed_at = Carbon::parse('2026-04-03 16:00:00');
        $cashSession->setRelation('openedDevice', null);
        $cashSession->setRelation('closedDevice', null);

        $salesMetrics = (object) [
            'first_sale_date' => '2026-04-03',
            'last_sale_date' => '2026-04-04',
            'total_sales' => 140000,
        ];

        $method = new \ReflectionMethod($controller, 'buildSessionDiagnostics');
        $method->setAccessible(true);

        $diagnostics = $method->invoke($controller, $cashSession, $salesMetrics);

        $this->assertSame('2026-04-03 s/d 2026-04-04', $diagnostics['sale_date_range_label']);
        $this->assertSame(480, $diagnostics['duration_minutes']);
        $this->assertSame(10000.0, $diagnostics['delta_total_vs_sum']);
        $this->assertTrue($diagnostics['device_mismatch']);
        $this->assertSame('Device #11', $diagnostics['opened_device_name']);
        $this->assertSame('Device #12', $diagnostics['closed_device_name']);
    }
}
