<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalesType;
use App\Models\User;
use App\Support\SpecialPromotion;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class BackdateSaleService
{
    private const MAX_BACKDATE_DAYS = 10;

    public function __construct(
        private SaleService $saleService,
        private StockService $stockService,
        private CostService $costService,
    )
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

        $items = $this->normalizeItemsWithAutomaticPrices($payload);

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
            'sales_type' => $this->resolveSalesType($payload['sales_type'] ?? 'regular'),
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

    public function updateBackdateSale(Sale $sale, array $payload, User $user): Sale
    {
        if (! $sale->is_backdated) {
            throw new Exception('Hanya transaksi backdate yang bisa diedit dari menu ini.');
        }

        if ($sale->status === 'cancelled') {
            throw new Exception('Transaksi yang sudah dibatalkan tidak bisa diedit.');
        }

        $saleDate = $this->validateSaleDate((string) $payload['sale_date']);
        $manualReference = strtoupper(trim((string) $payload['manual_reference']));

        if ($manualReference === '') {
            throw new Exception('Kode transaksi manual wajib diisi.');
        }

        $duplicateExists = Sale::query()
            ->where('manual_reference', $manualReference)
            ->where('is_backdated', true)
            ->whereKeyNot($sale->id)
            ->exists();

        if ($duplicateExists) {
            throw new Exception("Kode transaksi manual {$manualReference} sudah pernah dipakai.");
        }

        $items = $this->normalizeItemsWithAutomaticPrices($payload);

        if (count($items) === 0) {
            throw new Exception('Minimal harus ada 1 item produk.');
        }

        return DB::transaction(function () use ($sale, $payload, $user, $saleDate, $manualReference, $items): Sale {
            $sale->load(['items.product.bomHeader.details.component', 'payments', 'cashSession']);

            $this->restoreExistingSaleStock($sale, $user);
            $this->removeSaleFromCashSession($sale);

            $session = $this->getCorrectionSession(
                outletId: (int) $payload['outlet_id'],
                saleDate: $saleDate,
                user: $user
            );

            $summary = $this->calculateSummary($payload, $items);

            $sale->items()->delete();
            $sale->payments()->delete();

            $sale->fill([
                'outlet_id' => (int) $payload['outlet_id'],
                'cash_session_id' => $session->id,
                'user_id' => $user->id,
                'customer_id' => $payload['customer_id'] ?? null,
                'sale_date' => $saleDate,
                'sales_type' => $this->resolveSalesType($payload['sales_type'] ?? 'regular'),
                'subtotal' => $summary['subtotal'],
                'discount_type' => $payload['discount_type'] ?? 'none',
                'discount_value' => (float) ($payload['discount_value'] ?? 0),
                'discount_amount' => $summary['discount_amount'],
                'service_charge_amount' => $summary['service_charge_amount'],
                'rounding_amount' => $summary['rounding_amount'],
                'tax_amount' => $summary['tax_amount'],
                'total_amount' => $summary['total_amount'],
                'customer_name' => $this->resolveCustomerName($payload),
                'notes' => $payload['notes'] ?? null,
                'backdated_by' => $user->id,
                'backdated_at' => now(),
                'backdate_reason' => $payload['backdate_reason'],
                'manual_reference' => $manualReference,
            ]);
            $sale->save();

            $this->createItemsAndReduceStock($sale, $items, (int) $payload['outlet_id'], $user);

            Payment::create([
                'sale_id' => $sale->id,
                'payment_method_id' => (int) $payload['payment_method_id'],
                'amount' => $summary['total_amount'],
                'reference_number' => $payload['payment_reference'] ?? null,
                'notes' => $this->buildPaymentNotes($payload),
            ]);

            $this->addSaleToCashSession($session, (float) $summary['total_amount'], (int) $payload['payment_method_id']);

            return $sale->fresh(['items', 'payments.paymentMethod', 'outlet', 'user', 'customer']);
        });
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
        $columns = ['id', 'sku', 'name', 'selling_price'];
        if (Schema::hasColumn('products', 'price_levels')) {
            $columns[] = 'price_levels';
        }

        return Product::query()
            ->where('is_active', true)
            ->where('is_sellable', true)
            ->whereIn('product_type', ['finished_good', 'service'])
            ->orderBy('name')
            ->get($columns);
    }

    public function salesTypeOptions(): array
    {
        if (! Schema::hasTable('sales_types')) {
            return ['regular' => 'Regular'];
        }

        $salesTypes = SpecialPromotion::filterRuntimeSalesTypes(SalesType::priceLevels());

        return !empty($salesTypes) ? $salesTypes : ['regular' => 'Regular'];
    }

    private function normalizeItemsWithAutomaticPrices(array $payload): array
    {
        $salesType = $this->resolveSalesType($payload['sales_type'] ?? 'regular');
        $outletId = (int) ($payload['outlet_id'] ?? 0);

        $productIds = collect($payload['items'] ?? [])
            ->pluck('product_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        return collect($payload['items'] ?? [])
            ->filter(fn (array $item) => !empty($item['product_id']))
            ->map(function (array $item) use ($products, $salesType, $outletId): array {
                $product = $products->get((int) $item['product_id']);
                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => 'Satu atau lebih produk tidak ditemukan.',
                    ]);
                }

                return [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (float) $item['quantity'],
                    'unit_price' => $product->getPriceByLevelAndOutlet($salesType, $outletId),
                    'discount_amount' => (float) ($item['discount_amount'] ?? 0),
                    'notes' => $item['notes'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    private function resolveSalesType(?string $salesType): string
    {
        $salesType = trim((string) $salesType);
        $allowed = array_keys($this->salesTypeOptions());

        if ($salesType === '') {
            $salesType = 'regular';
        }

        if (! in_array($salesType, $allowed, true)) {
            throw ValidationException::withMessages([
                'sales_type' => 'Metode penjualan tidak valid.',
            ]);
        }

        return $salesType;
    }

    private function calculateSummary(array $payload, array $items): array
    {
        $subtotal = collect($items)->sum(function (array $item): float {
            $baseAmount = (float) $item['quantity'] * (float) $item['unit_price'];
            $discount = min($baseAmount, max(0, (float) ($item['discount_amount'] ?? 0)));

            return $baseAmount - $discount;
        });

        $discountType = $payload['discount_type'] ?? 'none';
        $discountValue = (float) ($payload['discount_value'] ?? 0);
        $discountAmount = match ($discountType) {
            'percentage' => $subtotal * ($discountValue / 100),
            'fixed' => $discountValue,
            default => 0,
        };
        $discountAmount = min($discountAmount, $subtotal);

        $outlet = Outlet::query()->findOrFail((int) $payload['outlet_id']);
        $taxBase = max(0, $subtotal - $discountAmount);
        $serviceChargeAmount = $taxBase * (((float) ($outlet->service_charge_rate ?? 0)) / 100);
        $taxableAmount = $taxBase + $serviceChargeAmount;
        $taxAmount = $taxableAmount * (((float) ($outlet->tax_rate ?? 10)) / 100);
        $rawTotalAmount = $taxBase + $serviceChargeAmount + $taxAmount;
        $totalAmount = (float) round($rawTotalAmount, 0);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'service_charge_amount' => $serviceChargeAmount,
            'tax_amount' => $taxAmount,
            'rounding_amount' => (float) round($totalAmount - $rawTotalAmount, 2),
            'total_amount' => $totalAmount,
        ];
    }

    private function restoreExistingSaleStock(Sale $sale, User $user): void
    {
        foreach ($sale->items as $item) {
            $product = $item->product;
            $type = $product?->product_type ?? 'finished_good';

            if ($type === 'finished_good' && $product?->bomHeader && $product->bomHeader->is_active) {
                foreach ($product->bomHeader->details as $detail) {
                    $this->stockService->restoreSaleStock(
                        productId: (int) $detail->component_product_id,
                        outletId: (int) $sale->outlet_id,
                        quantity: (float) $detail->quantity * (float) $item->quantity,
                        saleId: (int) $sale->id,
                        userId: $user->id,
                        notes: "Edit backdate: rollback {$product->name}",
                        mutationDate: $sale->sale_date->toDateString()
                    );
                }
            } elseif ($type === 'finished_good') {
                if (config('sales.finished_good_without_bom', 'reduce') === 'reduce') {
                    $this->stockService->restoreSaleStock(
                        productId: (int) $item->product_id,
                        outletId: (int) $sale->outlet_id,
                        quantity: (float) $item->quantity,
                        saleId: (int) $sale->id,
                        userId: $user->id,
                        notes: "Edit backdate: rollback {$item->product_name}",
                        mutationDate: $sale->sale_date->toDateString()
                    );
                }
            } elseif ($type === 'raw_material') {
                $this->stockService->restoreSaleStock(
                    productId: (int) $item->product_id,
                    outletId: (int) $sale->outlet_id,
                    quantity: (float) $item->quantity,
                    saleId: (int) $sale->id,
                    userId: $user->id,
                    notes: "Edit backdate: rollback {$item->product_name}",
                    mutationDate: $sale->sale_date->toDateString()
                );
            }
        }
    }

    private function createItemsAndReduceStock(Sale $sale, array $items, int $outletId, User $user): void
    {
        $products = Product::with(['bomHeader' => function ($q) {
            $q->where('is_active', true)->with('details.component');
        }])
            ->whereIn('id', collect($items)->pluck('product_id')->all())
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            /** @var Product $product */
            $product = $products->get((int) $item['product_id']);
            $baseAmount = (float) $item['quantity'] * (float) $item['unit_price'];
            $itemDiscount = min($baseAmount, max(0, (float) ($item['discount_amount'] ?? 0)));
            $itemSubtotal = $baseAmount - $itemDiscount;

            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => (float) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'discount_amount' => $itemDiscount,
                'subtotal' => $itemSubtotal,
                'cogs' => $this->costService->calculateItemCogs($product, (float) $item['quantity'], $outletId),
                'notes' => $item['notes'] ?? null,
            ]);

            $type = $product->product_type ?? 'finished_good';
            if ($type === 'finished_good' && $product->bomHeader && $product->bomHeader->is_active) {
                $this->assertBomStockAvailable($product, (float) $item['quantity'], $outletId);

                foreach ($product->bomHeader->details as $detail) {
                    $this->stockService->reduceSaleStock(
                        productId: (int) $detail->component_product_id,
                        outletId: $outletId,
                        quantity: (float) $detail->quantity * (float) $item['quantity'],
                        saleId: (int) $sale->id,
                        userId: $user->id,
                        notes: "Edit backdate penjualan: {$product->name}",
                        mutationDate: $sale->sale_date->toDateString()
                    );
                }
            } elseif ($type === 'finished_good') {
                $behavior = config('sales.finished_good_without_bom', 'reduce');
                if ($behavior === 'block') {
                    throw new Exception("Produk {$product->name} belum memiliki BOM aktif. Silakan buat BOM terlebih dahulu.");
                }
                if ($behavior === 'reduce') {
                    $this->stockService->reduceSaleStock(
                        productId: $product->id,
                        outletId: $outletId,
                        quantity: (float) $item['quantity'],
                        saleId: (int) $sale->id,
                        userId: $user->id,
                        notes: "Edit backdate penjualan: {$product->name}",
                        mutationDate: $sale->sale_date->toDateString()
                    );
                }
            } elseif ($type === 'raw_material') {
                $this->stockService->reduceSaleStock(
                    productId: $product->id,
                    outletId: $outletId,
                    quantity: (float) $item['quantity'],
                    saleId: (int) $sale->id,
                    userId: $user->id,
                    notes: "Edit backdate penjualan: {$product->name}",
                    mutationDate: $sale->sale_date->toDateString()
                );
            }
        }
    }

    private function assertBomStockAvailable(Product $product, float $quantity, int $outletId): void
    {
        if ((bool) config('app.allow_negative_stock', false)) {
            return;
        }

        foreach ($product->bomHeader->details as $detail) {
            $consumeQty = (float) $detail->quantity * $quantity;
            $availableQty = (float) $this->stockService->getAvailableStock((int) $detail->component_product_id, $outletId);

            if ($availableQty < $consumeQty) {
                $componentName = $detail->component?->name ?? 'komponen';
                throw new Exception(
                    'Stok bahan ' . $componentName . ' tidak mencukupi. Tersedia: ' .
                    rtrim(rtrim(number_format($availableQty, 4, '.', ''), '0'), '.')
                );
            }
        }
    }

    private function removeSaleFromCashSession(Sale $sale): void
    {
        $session = $sale->cashSession;
        if (! $session) {
            return;
        }

        $paymentMethodId = (int) optional($sale->payments->first())->payment_method_id;
        $this->adjustCashSession($session, -1 * (float) $sale->total_amount, $paymentMethodId);
    }

    private function addSaleToCashSession(CashSession $session, float $amount, int $paymentMethodId): void
    {
        $this->adjustCashSession($session, $amount, $paymentMethodId);
    }

    private function adjustCashSession(CashSession $session, float $amount, int $paymentMethodId): void
    {
        $paymentMethod = PaymentMethod::query()->find($paymentMethodId);
        $isCash = $paymentMethod?->code === 'CASH' || $paymentMethodId === 1;

        $session->total_sales = (float) $session->total_sales + $amount;
        $session->total_cash = (float) $session->total_cash + ($isCash ? $amount : 0);
        $session->total_non_cash = (float) $session->total_non_cash + ($isCash ? 0 : $amount);
        $session->expected_balance = (float) $session->opening_balance + (float) $session->total_cash;
        $session->save();
    }

    private function resolveCustomerName(array $payload): ?string
    {
        $customerName = trim((string) ($payload['customer_name'] ?? ''));
        if ($customerName !== '') {
            return $customerName;
        }

        if (!empty($payload['customer_id'])) {
            return \App\Models\Customer::query()->whereKey($payload['customer_id'])->value('name');
        }

        return null;
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
