<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DashboardController;
use App\Models\CashSession;
use App\Models\CashTransaction;
use App\Models\CoaAccount;
use App\Models\Outlet;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\StockMutation;
use App\Models\User;
use App\Services\CashAccountService;
use App\Services\CashSessionService;
use App\Services\PosDrawerExpenseService;
use App\Services\PosExpenseReportingService;
use App\Services\ProfitLossReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosExpenseReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Dashboard hourly charts use MySQL HOUR(); provide its equivalent in SQLite tests.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('HOUR', fn ($date) => (int) substr($date, 11, 2), 1);
        }
    }

    private function shift(): array
    {
        Setting::setValue('pos_drawer_expense_enabled', true);
        $outlet = Outlet::factory()->create();
        $role = Role::firstOrCreate(['name' => 'kasir'], ['display_name' => 'Kasir']);
        $role->permissions()->syncWithoutDetaching(Permission::whereIn('key', [
            'pos.expense.view', 'pos.expense.create',
        ])->pluck('id'));
        $user = User::factory()->create(['outlet_id' => $outlet->id, 'role_id' => $role->id]);
        $this->actingAs($user);
        $session = app(CashSessionService::class)->openSession([
            'outlet_id' => $outlet->id, 'opening_balance' => 2000000,
        ], $user);

        return [$outlet, $user, $session];
    }

    private function coa(string $code, string $name, string $type = 'expense', string $group = 'BIAYA OPERASIONAL'): CoaAccount
    {
        return CoaAccount::updateOrCreate(['code' => $code], [
            'name' => $name, 'type' => $type, 'group' => $group,
            'is_active' => true, 'is_pos_selectable' => true,
        ]);
    }

    private function expense(CashSession $session, User $user, CoaAccount $coa, float $amount, array $extra = [])
    {
        return app(PosDrawerExpenseService::class)->record($session, array_merge([
            'coa_account_id' => $coa->id, 'amount' => $amount,
            'recipient_name' => 'Supplier', 'description' => 'Belanja outlet',
            'payment_source' => 'drawer', 'idempotency_key' => (string) Str::uuid(),
        ], $extra), $user);
    }

    private function close(CashSession $session): void
    {
        $session->refresh();
        $actual = $session->calculateExpectedBalance();
        app(CashSessionService::class)->closeSession($session, $actual, null, null, null, $actual);
    }

    private function sale(CashSession $session, User $user): Sale
    {
        $product = Product::factory()->create();
        $sale = Sale::create([
            'invoice_number' => 'INV-'.Str::uuid(), 'outlet_id' => $session->outlet_id,
            'cash_session_id' => $session->id, 'user_id' => $user->id,
            'sale_date' => today(), 'sales_type' => 'regular', 'subtotal' => 1000000,
            'discount_type' => 'none', 'discount_value' => 0, 'discount_amount' => 0,
            'tax_amount' => 0, 'service_charge_amount' => 0, 'rounding_amount' => 0,
            'total_amount' => 1000000, 'status' => 'completed', 'kitchen_status' => 'done',
        ]);
        SaleItem::create([
            'sale_id' => $sale->id, 'product_id' => $product->id,
            'product_name' => $product->name, 'product_sku' => $product->sku,
            'quantity' => 1, 'unit_price' => 1000000, 'discount_amount' => 0,
            'subtotal' => 1000000, 'cogs' => 300000,
        ]);
        StockMutation::create([
            'product_id' => $product->id, 'outlet_id' => $session->outlet_id,
            'mutation_type' => 'out', 'quantity' => 1, 'unit_cost' => 300000,
            'total_cost' => 300000, 'stock_before' => 2, 'stock_after' => 1,
            'reference_type' => 'sale', 'reference_id' => $sale->id,
            'mutation_date' => today(), 'created_by' => $user->id,
        ]);

        return $sale;
    }

    private function report(?array $outlets = null): array
    {
        return app(ProfitLossReportService::class)->generate(today()->toDateString(), today()->toDateString(), $outlets);
    }

    public function test_same_cashier_input_posts_once_and_separates_hpp_from_operating_expenses(): void
    {
        [$outlet, $user, $session] = $this->shift();
        $this->sale($session, $user);
        $gas = $this->coa('6-137', 'Biaya Gas LPG');
        // Exact existing cashier fields: no cost treatment or purchase selector.
        $payload = [
            'coa_account_id' => $gas->id, 'recipient_name' => 'Penjual gas',
            'description' => 'Isi gas memasak', 'amount' => 20000,
            'payment_source' => 'drawer', 'idempotency_key' => (string) Str::uuid(),
        ];
        $this->post(route('pos.expenses.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->post(route('pos.expenses.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->expense($session, $user, $this->coa('6-136', 'Biaya Galon & Air Minum'), 10000);
        $this->expense($session, $user, $this->coa('6-138', 'Biaya Transport & Parkir'), 5000);
        $this->expense($session, $user, $this->coa('6-135', 'Biaya Keperluan Outlet Lainnya'), 7000);
        $this->expense($session, $user, $this->coa('6-139', 'Biaya Kebersihan & Habis Pakai'), 8000);
        $this->expense($session, $user, $this->coa('PLN-TEST', 'TOKEN PLN'), 9000);
        $this->expense($session, $user, $this->coa('PULSA-TEST', 'Pulsa Data'), 10000);
        $this->expense($session, $user, $this->coa('1-145', 'Belanja Stok/Barang Dagangan', 'asset', 'ASET LANCAR'), 80000);
        $this->expense($session, $user, $this->coa('HPP-TEST', 'HPP Bahan', 'expense', 'HPP'), 300000);

        // No double counting the shift subledger before posting at closing.
        $this->assertEquals(300000, $this->report([$outlet->id])['total_cogs']);
        $this->assertEquals(0, $this->report([$outlet->id])['pos_additional_cogs']);
        $this->close($session);
        $count = CashTransaction::count();
        $report = $this->report([$outlet->id]);
        $this->assertEquals(300000, $report['stock_cogs']);
        $this->assertEquals(35000, $report['pos_additional_cogs']);
        $this->assertEquals(335000, $report['total_cogs']);
        $this->assertEquals(34000, $report['total_expenses']);
        $this->assertEquals(665000, $report['gross_profit']);
        $this->assertEquals(631000, $report['net_profit']);
        $this->assertEquals(335000, $report['outlet_comparison']['outlets'][0]['cogs']);
        $this->assertEquals(35000, collect($report['pos_cogs_by_account'])->sum('amount'));
        $this->assertEquals(34000, collect($report['expenses_by_group'])->sum('total'));
        $this->assertSame($report, $this->report([$outlet->id]));
        $this->assertSame($count, CashTransaction::count());

        $this->withoutMiddleware();
        $this->getJson(route('admin.dashboard.summary', [
            'date_from' => today()->toDateString(), 'date_to' => today()->toDateString(),
            'outlet_ids' => [$outlet->id],
        ]))->assertOk()->assertJsonPath('kpis.total_cogs', 335000)
            ->assertJsonPath('kpis.pos_additional_cogs', 35000)
            ->assertJsonPath('kpis.total_expenses', 34000)
            ->assertJsonPath('kpis.net_profit', 631000);

        $this->get(route('admin.reports.profit-loss.index', [
            'date_from' => today()->toDateString(), 'date_to' => today()->toDateString(),
            'outlet_ids' => [$outlet->id],
        ]))->assertOk()->assertSee('HPP tambahan POS')->assertSee('335.000')->assertSee('cost_scope=pos_cogs', false);

        $this->get(route('admin.cash-transactions.index', [
            'date_from' => today()->toDateString(), 'date_to' => today()->toDateString(),
            'outlet_ids' => [$outlet->id], 'cost_scope' => 'pos_cogs',
        ]))->assertOk()->assertViewHas('totals', fn ($totals) => (float) $totals['credit'] === 35000.0);
    }

    public function test_void_cash_in_and_non_pos_payments_are_not_added_to_hpp(): void
    {
        [$outlet, $user, $session] = $this->shift();
        $gas = $this->coa('6-137', 'Biaya Gas LPG');
        $voided = $this->expense($session, $user, $gas, 20000);
        $drawer = app(PosDrawerExpenseService::class);
        $drawer->void($voided, $user, 'Batal beli');
        $drawer->recordCashIn($session, [
            'amount' => 100000, 'from_owner' => true, 'recipient_name' => 'Owner',
            'description' => 'Modal tambahan',
        ], $user);
        $this->close($session);
        app(CashAccountService::class)->recordTransaction([
            'cash_account_id' => $session->drawer_cash_account_id, 'coa_account_id' => $gas->id,
            'type' => 'out', 'transaction_date' => today()->toDateString(), 'amount' => 15000,
            'description' => 'Biaya backoffice', 'reference_type' => 'expense', 'created_by' => $user->id,
        ]);
        $report = $this->report([$outlet->id]);
        $this->assertEquals(0, $report['total_cogs']);
        $this->assertEquals(15000, $report['total_expenses']);
    }

    public function test_non_cash_pos_cost_uses_same_classification_and_does_not_reduce_drawer_twice(): void
    {
        [$outlet, $user, $session] = $this->shift();
        $source = app(PosDrawerExpenseService::class)->ensureMainCashAccount($outlet->id);
        $before = (float) $source->current_balance;
        $this->expense($session, $user, $this->coa('6-137', 'Biaya Gas LPG'), 20000, [
            'payment_source' => 'noncash', 'source_cash_account_id' => $source->id,
        ]);
        $this->assertEquals(2000000, $session->fresh()->calculateExpectedBalance());
        $this->close($session);
        $this->assertEquals($before - 20000, $source->fresh()->current_balance);
        $this->assertEquals(20000, $this->report([$outlet->id])['total_cogs']);
        $this->assertEquals(0, $this->report([$outlet->id])['total_expenses']);
    }

    public function test_outlet_and_period_filters_and_outlet_without_sales_include_only_its_cost(): void
    {
        [$first, $user, $session] = $this->shift();
        $gas = $this->coa('6-137', 'Biaya Gas LPG');
        $this->expense($session, $user, $gas, 20000);
        $this->close($session);
        [$second, $other, $otherSession] = $this->shift();
        $this->expense($otherSession, $other, $gas, 30000);
        $this->close($otherSession);
        $this->assertEquals(20000, $this->report([$first->id])['total_cogs']);
        $this->assertEquals(30000, $this->report([$second->id])['total_cogs']);
        $this->assertEquals(50000, $this->report()['total_cogs']);
        $metrics = new \ReflectionMethod(DashboardController::class, 'calculateOutletProfitMetrics');
        $rows = $metrics->invoke(null, today()->toDateString(), today()->toDateString(), null);
        $this->assertEquals(20000, $rows[$first->id]['cogs']);
        $this->assertEquals(30000, $rows[$second->id]['cogs']);
        $this->assertEquals(-50000, collect($rows)->sum('net_profit'));
        $this->assertEquals(0, app(PosExpenseReportingService::class)->additionalCogsTotal(
            today()->subDay()->toDateString(), today()->subDay()->toDateString(),
        ));
    }

    public function test_cost_already_in_product_recipe_is_excluded_from_both_cost_buckets(): void
    {
        [$outlet, $user, $session] = $this->shift();
        $this->sale($session, $user);
        config(['pos_expense_reporting.included_in_product_cost_coa_codes' => ['6-137']]);
        $this->expense($session, $user, $this->coa('6-137', 'Biaya Gas LPG'), 20000);
        $this->close($session);
        $report = $this->report([$outlet->id]);
        $this->assertEquals(300000, $report['total_cogs']);
        $this->assertEquals(0, $report['total_expenses']);
        $this->assertEquals(700000, $report['net_profit']);
    }

    public function test_previous_period_dashboard_uses_the_same_pos_cost_classification(): void
    {
        $this->travelTo(now()->subDay()->startOfDay()->addHours(10));
        [$outlet, $user, $session] = $this->shift();
        $gas = $this->coa('6-137', 'Biaya Gas LPG');
        $this->expense($session, $user, $gas, 30000);
        $this->close($session);
        $this->travel(1)->days();
        $session = app(CashSessionService::class)->openSession([
            'outlet_id' => $outlet->id, 'opening_balance' => 1970000,
        ], $user);
        $this->expense($session, $user, $gas, 20000);
        $this->close($session);
        $this->withoutMiddleware();
        $this->getJson(route('admin.dashboard.summary', [
            'date_from' => today()->toDateString(), 'date_to' => today()->toDateString(),
            'outlet_ids' => [$outlet->id],
        ]))->assertOk()->assertJsonPath('kpis.total_cogs', 20000)
            ->assertJsonPath('kpis.total_expenses', 0)
            ->assertJsonPath('trend_vs_prev_day.prev_total_cogs', 30000)
            ->assertJsonPath('trend_vs_prev_day.prev_total_expenses', 0);
    }

    public function test_ledger_drilldown_matches_hpp_and_operating_totals(): void
    {
        [$outlet, $user, $session] = $this->shift();
        $this->expense($session, $user, $this->coa('6-137', 'Biaya Gas LPG'), 20000);
        $this->expense($session, $user, $this->coa('6-139', 'Biaya Kebersihan & Habis Pakai'), 8000);
        $this->close($session);
        $cash = app(CashAccountService::class);
        $filters = ['date_from' => today()->toDateString(), 'date_to' => today()->toDateString(), 'outlet_ids' => [$outlet->id]];
        $this->assertEquals(20000, $cash->getExportTransactions($filters + ['cost_scope' => 'pos_cogs'])->sum('amount'));
        $this->assertEquals(8000, $cash->getExportTransactions($filters + ['cost_scope' => 'operating'])->sum('amount'));
        $this->assertEquals(20000, $cash->getTransactionTotals($filters + ['cost_scope' => 'pos_cogs'])['credit']);
    }

    public function test_old_cached_configuration_still_preserves_operating_exceptions(): void
    {
        [$outlet, $user, $session] = $this->shift();
        config(['pos_expense_reporting' => null]);
        $this->expense($session, $user, $this->coa('6-137', 'Biaya Gas LPG'), 20000);
        $this->expense($session, $user, $this->coa('6-139', 'Biaya Kebersihan & Habis Pakai'), 8000);
        $this->close($session);
        $report = $this->report([$outlet->id]);
        $this->assertEquals(20000, $report['total_cogs']);
        $this->assertEquals(8000, $report['total_expenses']);
    }
}
