<?php

use App\Models\CashSession;
use App\Models\Sale;
use App\Models\StockMutation;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$options = getopt('', [
    'apply',
    'date-from:',
    'date-to:',
    'outlet-id:',
    'keep-sessions',
    'help',
]);

if (isset($options['help'])) {
    echo <<<TXT
Hapus semua inputan penjualan backdate.

Preview:
  php hapus_backdate_sales.php

Hapus permanen:
  php hapus_backdate_sales.php --apply

Filter opsional:
  php hapus_backdate_sales.php --date-from=2026-06-01 --date-to=2026-06-10 --outlet-id=1 --apply

Opsi:
  --apply          Wajib dipakai untuk benar-benar menghapus.
  --date-from      Filter sale_date awal, format YYYY-MM-DD.
  --date-to        Filter sale_date akhir, format YYYY-MM-DD.
  --outlet-id      Filter outlet tertentu.
  --keep-sessions  Jangan hapus cash session backdate yang kosong.

TXT;
    exit(0);
}

$apply = array_key_exists('apply', $options);
$dateFrom = trim((string) ($options['date-from'] ?? ''));
$dateTo = trim((string) ($options['date-to'] ?? ''));
$outletId = trim((string) ($options['outlet-id'] ?? ''));

$saleQuery = Sale::query()
    ->where('is_backdated', true)
    ->when($dateFrom !== '', fn ($query) => $query->whereDate('sale_date', '>=', $dateFrom))
    ->when($dateTo !== '', fn ($query) => $query->whereDate('sale_date', '<=', $dateTo))
    ->when($outletId !== '', fn ($query) => $query->where('outlet_id', (int) $outletId));

$summary = (clone $saleQuery)
    ->selectRaw('COUNT(*) as total_sales, COALESCE(SUM(total_amount), 0) as total_amount')
    ->first();

$saleIds = (clone $saleQuery)->pluck('id');
$sessionIds = (clone $saleQuery)
    ->whereNotNull('cash_session_id')
    ->pluck('cash_session_id')
    ->unique()
    ->values();

$stockPairs = StockMutation::query()
    ->whereIn('reference_id', $saleIds)
    ->whereIn('reference_type', ['sale', 'sale_cancellation'])
    ->select('product_id', 'outlet_id', DB::raw('MIN(mutation_date) as first_mutation_date'), DB::raw('COUNT(*) as total_mutations'))
    ->groupBy('product_id', 'outlet_id')
    ->orderBy('product_id')
    ->orderBy('outlet_id')
    ->get();

$payload = [
    'mode' => $apply ? 'apply' : 'dry-run',
    'filters' => [
        'date_from' => $dateFrom !== '' ? $dateFrom : null,
        'date_to' => $dateTo !== '' ? $dateTo : null,
        'outlet_id' => $outletId !== '' ? (int) $outletId : null,
    ],
    'backdate_sales' => (int) ($summary->total_sales ?? 0),
    'backdate_total_amount' => (float) ($summary->total_amount ?? 0),
    'cash_sessions_affected' => $sessionIds->count(),
    'stock_mutations_to_delete' => (int) $stockPairs->sum('total_mutations'),
    'stock_pairs_to_recalculate' => $stockPairs->count(),
];

echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

if ($saleIds->isEmpty()) {
    echo "Tidak ada transaksi backdate yang cocok dengan filter." . PHP_EOL;
    exit(0);
}

if (! $apply) {
    echo "Dry-run saja. Jalankan lagi dengan --apply untuk benar-benar menghapus." . PHP_EOL;
    exit(0);
}

DB::transaction(function () use ($saleIds, $sessionIds, $stockPairs): void {
    StockMutation::query()
        ->whereIn('reference_id', $saleIds)
        ->whereIn('reference_type', ['sale', 'sale_cancellation'])
        ->delete();

    Sale::query()
        ->whereIn('id', $saleIds)
        ->delete();

    foreach ($sessionIds as $sessionId) {
        $totalSales = (float) DB::table('sales')
            ->where('cash_session_id', $sessionId)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $paymentTotals = DB::table('payments')
            ->join('sales', 'sales.id', '=', 'payments.sale_id')
            ->leftJoin('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
            ->where('sales.cash_session_id', $sessionId)
            ->where('sales.status', '!=', 'cancelled')
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_methods.code = 'CASH' OR payments.payment_method_id = 1 THEN payments.amount ELSE 0 END), 0) as total_cash")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_methods.code <> 'CASH' AND payments.payment_method_id <> 1 THEN payments.amount ELSE 0 END), 0) as total_non_cash")
            ->first();

        $session = CashSession::query()->find($sessionId);
        if (! $session) {
            continue;
        }

        $session->total_sales = $totalSales;
        $session->total_cash = (float) ($paymentTotals->total_cash ?? 0);
        $session->total_non_cash = (float) ($paymentTotals->total_non_cash ?? 0);
        $session->expected_balance = (float) $session->opening_balance + (float) $session->total_cash;
        $session->save();
    }

    $stockService = app(StockService::class);
    foreach ($stockPairs as $pair) {
        $stockService->recalculateMutationBalances(
            (int) $pair->product_id,
            (int) $pair->outlet_id,
            (string) $pair->first_mutation_date
        );
    }
});

$deletedSessions = 0;
if (! array_key_exists('keep-sessions', $options)) {
    $deletedSessions = CashSession::query()
        ->whereIn('id', $sessionIds)
        ->where('session_type', 'backdate_correction')
        ->whereDoesntHave('sales')
        ->delete();
}

echo "Selesai hapus {$saleIds->count()} transaksi backdate." . PHP_EOL;
echo "Cash session backdate kosong yang dihapus: {$deletedSessions}." . PHP_EOL;
