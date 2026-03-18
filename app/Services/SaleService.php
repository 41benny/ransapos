<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\CashSession;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Promotion;
use App\Models\Voucher;
use App\Support\SpecialPromotion;
use Illuminate\Support\Facades\DB;
use Exception;

class SaleService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Buat transaksi penjualan baru
     * 
     * @param array $data
     * @return Sale
     * @throws Exception
     */
    public function createSale(array $data): Sale
    {
        DB::beginTransaction();

        try {
            $cashSession = CashSession::findOrFail($data['cash_session_id']);
            if ((int) $cashSession->outlet_id !== (int) $data['outlet_id']) {
                throw new Exception('Sesi kasir tidak sesuai dengan outlet transaksi.');
            }

            if ($cashSession->status !== 'open') {
                throw new Exception('Sesi kasir sudah ditutup. Silakan buka sesi baru.');
            }

            $customer = null;
            $resolvedCustomerName = trim((string) ($data['customer_name'] ?? ''));
            if (!empty($data['customer_id'])) {
                $customer = Customer::query()
                    ->select(['id', 'name'])
                    ->find($data['customer_id']);

                if (!$customer) {
                    throw new Exception('Customer tidak ditemukan.');
                }

                if ($resolvedCustomerName === '') {
                    $resolvedCustomerName = trim((string) $customer->name);
                }
            }

            // 1. Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber($data['outlet_id']);

            // 2. Resolve promo kategori (opsional)
            $promotion = null;
            if (!empty($data['promotion_id'])) {
                $promotion = Promotion::query()
                    ->with(['categoryRules' => function ($query) {
                        $query->select(['id', 'promotion_id', 'product_category_id', 'discount_percent']);
                    }])
                    ->find($data['promotion_id']);

                if (!$promotion || !$promotion->isValidFor((int) $data['outlet_id'])) {
                    throw new Exception('Promo tidak valid atau tidak aktif untuk outlet ini.');
                }
            }

            // 3. Preload products (sekali query) untuk hitung diskon + proses stok/BOM
            $productIds = collect($data['items'])
                ->pluck('product_id')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values();

            $products = Product::with(['bomHeader' => function ($q) {
                $q->where('is_active', true)->with('details.component');
            }])
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            if ($products->count() !== $productIds->count()) {
                throw new Exception('Satu atau lebih produk pada transaksi tidak ditemukan.');
            }

            $promotionRuleByCategory = [];
            if ($promotion) {
                $promotionRuleByCategory = $promotion->categoryRules
                    ->mapWithKeys(function ($rule) {
                        return [(int) $rule->product_category_id => (float) $rule->discount_percent];
                    })
                    ->all();
            }

            $resolvedSalesType = trim((string) ($data['sales_type'] ?? 'regular'));
            if ($resolvedSalesType === '' || SpecialPromotion::isSpecialSalesType($resolvedSalesType)) {
                $resolvedSalesType = 'regular';
            }

            // 4. Hitung subtotal dari items (termasuk diskon item dari promo kategori)
            $subtotal = 0;
            $normalizedItems = [];

            foreach ($data['items'] as $item) {
                $productId = (int) $item['product_id'];
                /** @var Product|null $product */
                $product = $products->get($productId);
                if (!$product) {
                    throw new Exception('Produk transaksi tidak valid.');
                }

                $quantity = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $baseAmount = $quantity * $unitPrice;

                $manualDiscount = max(0, (float) ($item['discount_amount'] ?? 0));
                $promoDiscount = 0.0;
                $promoRate = $promotionRuleByCategory[(int) $product->category_id] ?? null;
                if ($promoRate !== null && $promoRate > 0) {
                    $promoDiscount = round($baseAmount * ($promoRate / 100), 2);
                }

                // Tidak stack manual+promo. Ambil nilai terbesar agar aman dari diskon ganda.
                $itemDiscount = min($baseAmount, max($manualDiscount, $promoDiscount));
                $itemSubtotal = $baseAmount - $itemDiscount;

                $subtotal += $itemSubtotal;

                $normalizedItems[] = [
                    'product' => $product,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $itemDiscount,
                    'subtotal' => $itemSubtotal,
                    'notes' => $item['notes'] ?? null,
                ];
            }

            // 5. Hitung diskon global (manual/voucher)
            $discountAmount = 0;
            $discountType = $data['discount_type'] ?? 'none';
            $discountValue = (float) ($data['discount_value'] ?? 0);
            $voucher = null;

            if (!empty($data['voucher_code'])) {
                $voucherCode = strtoupper(trim((string) $data['voucher_code']));
                $voucher = Voucher::query()
                    ->where('code', $voucherCode)
                    ->lockForUpdate()
                    ->first();

                if (!$voucher || !$voucher->isValidFor((int) $data['outlet_id'], $subtotal)) {
                    throw new Exception('Voucher tidak valid, tidak aktif, atau tidak memenuhi syarat minimum belanja.');
                }

                $discountAmount = $voucher->calculateDiscountAmount($subtotal);
                $discountType = $voucher->discount_type;
                $discountValue = (float) $voucher->discount_value;
            } elseif ($discountType === 'percentage') {
                $discountAmount = $subtotal * ($discountValue / 100);
            } elseif ($discountType === 'fixed') {
                $discountAmount = $discountValue;
            }

            $discountAmount = min($discountAmount, $subtotal);

            // 6. Hitung Service Charge & Tax (PB1)
            $outlet = \App\Models\Outlet::find($data['outlet_id']);

            // Tax base (DPP) = Subtotal - Discount
            $taxBase = max(0, $subtotal - $discountAmount);

            // Service Charge: X% dari Tax Base
            $serviceChargeRate = $outlet->service_charge_rate ?? 0;
            $serviceChargeAmount = $taxBase * ($serviceChargeRate / 100);

            // Tax: Y% dari (Tax Base + Service Charge)
            // Note: PB1 biasanya dikenakan atas total layanan
            $taxableAmount = $taxBase + $serviceChargeAmount;
            $taxRate = $outlet->tax_rate ?? 10; // Default 10% jika null
            $taxAmount = $taxableAmount * ($taxRate / 100);

            // Total sebelum pembulatan
            $rawTotalAmount = $taxBase + $serviceChargeAmount + $taxAmount;

            // Pembulatan final ke rupiah utuh agar nilai charge dan laporan konsisten
            $totalAmount = (float) round($rawTotalAmount, 0);
            $roundingAmount = (float) round($totalAmount - $rawTotalAmount, 2);

            // 7. Buat record sale
            $sale = Sale::create([
                'invoice_number' => $invoiceNumber,
                'outlet_id' => $data['outlet_id'],
                'cash_session_id' => $data['cash_session_id'],
                // Gunakan auth()->id() (helper standar Laravel) jika tersedia
                'user_id' => \Illuminate\Support\Facades\Auth::id() ?? $data['user_id'] ?? null,
                'customer_id' => $customer?->id,
                'promotion_id' => $promotion?->id,
                'voucher_id' => $voucher?->id,
                'voucher_code' => $voucher?->code,
                'sale_date' => now()->toDateString(),
                'sales_type' => $resolvedSalesType,
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'service_charge_amount' => $serviceChargeAmount,
                'rounding_amount' => $roundingAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'customer_name' => $resolvedCustomerName !== '' ? $resolvedCustomerName : null,
                'notes' => $data['notes'] ?? null,
                'status' => 'completed',
            ]);

            // 8. Buat sale items & logika BOM / pengurangan stok
            $allowNegativeStock = (bool) config('app.allow_negative_stock', false);

            foreach ($normalizedItems as $item) {
                /** @var Product $product */
                $product = $item['product'];

                $itemSubtotal = (float) $item['subtotal'];
                // Hitung COGS per item
                $itemCogs = $this->calculateItemCogs($product, $item['quantity'], (int) $data['outlet_id']);

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'],
                    'subtotal' => $itemSubtotal,
                    'cogs' => $itemCogs,
                    'notes' => $item['notes'] ?? null,
                ]);

                // Penentuan tipe produk
                $type = $product->product_type ?? 'finished_good';

                if ($type === 'raw_material') {
                    // Perilaku lama: kurangi stok produk langsung
                    $this->stockService->reduceSaleStock(
                        productId: $product->id,
                        outletId: $data['outlet_id'],
                        quantity: $item['quantity'],
                        saleId: $sale->id,
                        userId: $sale->user_id,
                        notes: "Penjualan: {$product->name}",
                        mutationDate: $sale->sale_date->toDateString()
                    );
                } elseif ($type === 'finished_good') {
                    // Cek BOM aktif
                    $bom = $product->bomHeader && $product->bomHeader->is_active ? $product->bomHeader : null;
                    if ($bom) {
                        if (! $allowNegativeStock) {
                            foreach ($bom->details as $detail) {
                                $consumeQty = (float) $detail->quantity * (float) $item['quantity'];
                                $availableQty = (float) $this->stockService->getAvailableStock(
                                    productId: (int) $detail->component_product_id,
                                    outletId: (int) $data['outlet_id'],
                                );

                                if ($availableQty < $consumeQty) {
                                    $componentName = $detail->component?->name ?? 'komponen';
                                    throw new Exception(
                                        'Stok bahan ' . $componentName . ' tidak mencukupi. Tersedia: ' .
                                            rtrim(rtrim(number_format($availableQty, 4, '.', ''), '0'), '.')
                                    );
                                }
                            }
                        }

                        foreach ($bom->details as $detail) {
                            $consumeQty = $detail->quantity * $item['quantity'];
                            $this->stockService->reduceSaleStock(
                                productId: $detail->component_product_id,
                                outletId: $data['outlet_id'],
                                quantity: $consumeQty,
                                saleId: $sale->id,
                                userId: $sale->user_id,
                                notes: "Penjualan: {$product->name}",
                                mutationDate: $sale->sale_date->toDateString()
                            );
                        }
                    } else {
                        $behavior = config('sales.finished_good_without_bom', 'reduce');
                        if ($behavior === 'block') {
                            throw new Exception("Produk {$product->name} belum memiliki BOM aktif. Silakan buat BOM terlebih dahulu.");
                        }
                        if ($behavior === 'skip') {
                            // Made-to-order menu without BOM: do not mutate stock.
                        } else {
                            // Legacy behavior: reduce finished_good stock directly.
                            $this->stockService->reduceSaleStock(
                                productId: $product->id,
                                outletId: $data['outlet_id'],
                                quantity: $item['quantity'],
                                saleId: $sale->id,
                                userId: $sale->user_id,
                                notes: "Penjualan: {$product->name}",
                                mutationDate: $sale->sale_date->toDateString()
                            );
                        }
                    }
                } elseif ($type === 'service') {
                    // Tidak mengurangi stok
                }
            }

            // 9. Catat pembayaran
            Payment::create([
                'sale_id' => $sale->id,
                'payment_method_id' => $data['payment_method_id'],
                'amount' => $totalAmount,
                'reference_number' => $data['payment_reference'] ?? null,
                'notes' => $data['payment_notes'] ?? null,
            ]);

            // 10. Update cash session
            $this->updateCashSession($data['cash_session_id'], $totalAmount, $data['payment_method_id']);

            // 11. Mark voucher usage
            if ($voucher) {
                $voucher->used_count = (int) $voucher->used_count + 1;
                $voucher->save();
            }

            // 12. Loyalty points & customer stats (jika ada customer)
            if (!empty($data['customer_id'])) {
                /** @var Customer|null $customer */
                $customer = $sale->customer()->first();
                if ($customer) {
                    $pointsEarned = (int) floor($totalAmount * 0.01); // 1 point per Rp 100

                    $sale->loyalty_points_earned = $pointsEarned;
                    $sale->save();

                    $customer->addPoints($pointsEarned);
                    $customer->updateStats((float) $totalAmount);
                }
            }

            DB::commit();

            return $sale->load(['items', 'payments.paymentMethod', 'outlet', 'user', 'customer', 'promotion', 'voucher']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate invoice number unik
     * 
     * @param int $outletId
     * @return string
     */
    protected function generateInvoiceNumber(int $outletId): string
    {
        $datePart = now()->format('ymd'); // contoh: 260214
        $outlet = str_pad($outletId, 3, '0', STR_PAD_LEFT);

        // Cari invoice terakhir hari ini untuk outlet ini dengan locking supaya sequence aman
        $lastSale = Sale::where('outlet_id', $outletId)
            ->whereDate('sale_date', now())
            ->lockForUpdate()
            ->orderBy('id', 'desc')
            ->first();

        if ($lastSale) {
            // Extract nomor urut terakhir
            $lastNumber = (int) substr($lastSale->invoice_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $sequence = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // Format baru: INV-001260214-0001
        return "INV-{$outlet}{$datePart}-{$sequence}";
    }

    /**
     * Update informasi cash session setelah transaksi
     * 
     * @param int $sessionId
     * @param float $saleAmount
     * @param int $paymentMethodId
     * @return void
     */
    protected function updateCashSession(int $sessionId, float $saleAmount, int $paymentMethodId): void
    {
        $session = CashSession::findOrFail($sessionId);
        if ($session->status !== 'open') {
            throw new Exception('Sesi kasir sudah ditutup. Silakan buka sesi baru.');
        }

        // Update total sales
        $session->total_sales += $saleAmount;

        // Update total cash atau non-cash (berbasis code payment method, fallback ke id)
        $paymentMethod = PaymentMethod::find($paymentMethodId);
        $isCash = $paymentMethod?->code === 'CASH' || $paymentMethodId === 1;

        $session->total_cash += $isCash ? $saleAmount : 0;
        $session->total_non_cash += $isCash ? 0 : $saleAmount;

        // Update expected balance
        $session->expected_balance = $session->opening_balance + $session->total_cash;

        $session->save();
    }

    /**
     * Batalkan transaksi (untuk refund)
     * 
     * @param int $saleId
     * @param string $reason
     * @return Sale
     * @throws Exception
     */
    public function cancelSale(int $saleId, string $reason = ''): Sale
    {
        DB::beginTransaction();

        try {
            $sale = Sale::with(['items', 'voucher'])->findOrFail($saleId);

            // Cek apakah sudah dibatalkan
            if ($sale->status === 'cancelled') {
                throw new Exception('Transaksi sudah dibatalkan sebelumnya');
            }

            // Kembalikan stok sesuai tipe produk & BOM
            foreach ($sale->items as $item) {
                $product = Product::with(['bomHeader' => function ($q) {
                    $q->where('is_active', true)->with('details.component');
                }])->find($item->product_id);

                $type = $product?->product_type ?? 'finished_good';

                if ($type === 'finished_good' && $product?->bomHeader && $product->bomHeader->is_active) {
                    foreach ($product->bomHeader->details as $detail) {
                        $consumeQty = $detail->quantity * $item->quantity;
                        $this->stockService->restoreSaleStock(
                            productId: $detail->component_product_id,
                            outletId: $sale->outlet_id,
                            quantity: $consumeQty,
                            saleId: $sale->id,
                            userId: \Illuminate\Support\Facades\Auth::id(),
                            notes: "Batal Jual (Menu: {$product->name}): {$reason}"
                        );
                    }
                } elseif ($type === 'finished_good') {
                    $behavior = config('sales.finished_good_without_bom', 'reduce');
                    if ($behavior === 'reduce') {
                        $this->stockService->restoreSaleStock(
                            productId: $item->product_id,
                            outletId: $sale->outlet_id,
                            quantity: $item->quantity,
                            saleId: $sale->id,
                            userId: \Illuminate\Support\Facades\Auth::id(),
                            notes: "Batal Jual (Menu: {$product->name}): {$reason}"
                        );
                    }
                } elseif ($type !== 'service') {
                    $this->stockService->restoreSaleStock(
                        productId: $item->product_id,
                        outletId: $sale->outlet_id,
                        quantity: $item->quantity,
                        saleId: $sale->id,
                        userId: \Illuminate\Support\Facades\Auth::id(),
                        notes: "Batal Jual (Menu: {$product->name}): {$reason}"
                    );
                }
            }

            // Update status sale
            $sale->status = 'cancelled';
            $sale->notes = ($sale->notes ? $sale->notes . "\n\n" : '') . "Dibatalkan: {$reason}";
            $sale->save();

            // Update cash session (kurangi total)
            $session = CashSession::find($sale->cash_session_id);
            if ($session) {
                $session->total_sales -= $sale->total_amount;

                $payment = $sale->payments->first();
                if ($payment && $payment->payment_method_id == 1) {
                    $session->total_cash -= $sale->total_amount;
                } else {
                    $session->total_non_cash -= $sale->total_amount;
                }

                $session->expected_balance = $session->opening_balance + $session->total_cash;
                $session->save();
            }

            // Jika transaksi memakai voucher, kembalikan kuota penggunaan
            if ($sale->voucher && $sale->voucher->used_count > 0) {
                $sale->voucher->used_count = max(0, (int) $sale->voucher->used_count - 1);
                $sale->voucher->save();
            }

            DB::commit();

            return $sale->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Hitung COGS (Cost of Goods Sold) per item menggunakan moving average cost.
     * Delegasi ke CostService untuk konsistensi perhitungan.
     *
     * @param Product $product
     * @param float $quantity
     * @param int $outletId
     * @return float
     */
    protected function calculateItemCogs(Product $product, float $quantity, int $outletId): float
    {
        return app(CostService::class)->calculateItemCogs($product, $quantity, $outletId);
    }
}
