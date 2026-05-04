<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class BackdateSaleService
{
    private const MAX_BACKDATE_DAYS = 7;

    public function __construct(private SaleService $saleService)
    {
    }

    public function maxBackdateDays(): int
    {
        return self::MAX_BACKDATE_DAYS;
    }

    public function validateSaleDate(string $saleDate): string
    {
        $date = Carbon::parse($saleDate)->toDateString();
        $minDate = today()->subDays(self::MAX_BACKDATE_DAYS)->toDateString();
        $today = today()->toDateString();

        if ($date < $minDate || $date > $today) {
            throw new Exception("Tanggal penjualan harus dalam rentang {$minDate} sampai {$today}.");
        }

        return $date;
    }

    public function createBackdateSale(array $payload, User $user): Sale
    {
        $saleDate = $this->validateSaleDate((string) $payload['sale_date']);
        $manualReference = strtoupper(trim((string) $payload['manual_reference']));

        if ($manualReference === '') {
            throw new Exception('Kode transaksi manual wajib diisi.');
        }

        $duplicateExists = Sale::query()
            ->where('manual_reference', $manualReference)
            ->where('is_backdated', true)
            ->exists();

        if ($duplicateExists) {
            throw new Exception("Kode transaksi manual {$manualReference} sudah pernah dipakai.");
        }

        $session = $this->getCorrectionSession(
            outletId: (int) $payload['outlet_id'],
            saleDate: $saleDate,
            user: $user
        );

        $items = collect($payload['items'] ?? [])
            ->filter(fn (array $item) => !empty($item['product_id']))
            ->map(function (array $item): array {
                return [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (float) $item['quantity'],
                    'unit_price' => (float) $item['unit_price'],
                    'discount_amount' => (float) ($item['discount_amount'] ?? 0),
                    'notes' => $item['notes'] ?? null,
                ];
            })
            ->values()
            ->all();

        if (count($items) === 0) {
            throw new Exception('Minimal harus ada 1 item produk.');
        }

        return $this->saleService->createSale([
            'outlet_id' => (int) $payload['outlet_id'],
            'cash_session_id' => $session->id,
            'user_id' => $user->id,
            'customer_id' => $payload['customer_id'] ?? null,
            'customer_name' => $payload['customer_name'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'sales_type' => $payload['sales_type'] ?? 'regular',
            'discount_type' => $payload['discount_type'] ?? 'none',
            'discount_value' => (float) ($payload['discount_value'] ?? 0),
            'items' => $items,
            'payment_method_id' => (int) $payload['payment_method_id'],
            'payment_amount' => (float) ($payload['payment_amount'] ?? 0),
            'payment_reference' => $payload['payment_reference'] ?? null,
            'payment_notes' => $this->buildPaymentNotes($payload),
            'sale_date' => $saleDate,
            'is_backdated' => true,
            'backdated_by' => $user->id,
            'backdated_at' => now(),
            'backdate_reason' => $payload['backdate_reason'],
            'manual_reference' => $manualReference,
        ]);
    }

    public function createMany(array $payloads, User $user): array
    {
        return DB::transaction(function () use ($payloads, $user): array {
            $sales = [];

            foreach ($payloads as $payload) {
                $sales[] = $this->createBackdateSale($payload, $user);
            }

            return $sales;
        });
    }

    public function productOptions()
    {
        return Product::query()
            ->where('is_active', true)
            ->where('is_sellable', true)
            ->whereIn('product_type', ['finished_good', 'service'])
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'selling_price']);
    }

    private function getCorrectionSession(int $outletId, string $saleDate, User $user): CashSession
    {
        $outlet = Outlet::findOrFail($outletId);
        $outletCode = $outlet->code ?: str_pad((string) $outlet->id, 3, '0', STR_PAD_LEFT);
        $dateKey = Carbon::parse($saleDate)->format('Ymd');
        $sessionNumber = "CS-BACKDATE-{$outletCode}-{$dateKey}-U{$user->id}";

        return CashSession::query()->firstOrCreate(
            ['session_number' => $sessionNumber],
            [
                'session_type' => 'backdate_correction',
                'business_date' => $saleDate,
                'outlet_id' => $outletId,
                'user_id' => $user->id,
                'opening_balance' => 0,
                'expected_balance' => 0,
                'actual_balance' => null,
                'difference' => 0,
                'total_sales' => 0,
                'total_cash' => 0,
                'total_non_cash' => 0,
                'opened_at' => Carbon::parse($saleDate)->startOfDay(),
                'closed_at' => null,
                'notes' => 'Sesi koreksi otomatis untuk input penjualan backdate dari admin.',
                'status' => 'open',
            ]
        );
    }

    private function buildPaymentNotes(array $payload): ?string
    {
        $notes = trim((string) ($payload['payment_notes'] ?? ''));
        $manualAmount = (float) ($payload['payment_amount'] ?? 0);

        if ($manualAmount <= 0) {
            return $notes !== '' ? $notes : null;
        }

        $amountNote = 'Jumlah bayar catatan manual: Rp ' . number_format($manualAmount, 0, ',', '.');

        return $notes !== '' ? $notes . "\n" . $amountNote : $amountNote;
    }
}
