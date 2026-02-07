<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\CashSession;
use App\Models\Customer;
use App\Models\PaymentMethod;
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
            // 1. Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber($data['outlet_id']);

            // 2. Hitung subtotal dari items
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $itemSubtotal -= $item['discount_amount'] ?? 0;
                $subtotal += $itemSubtotal;
            }

            // 3. Hitung diskon global
            $discountAmount = 0;
            if ($data['discount_type'] === 'percentage') {
                $discountAmount = $subtotal * ($data['discount_value'] / 100);
            } elseif ($data['discount_type'] === 'fixed') {
                $discountAmount = $data['discount_value'];
            }

            // 4. Hitung Service Charge & Tax (PB1)
            $outlet = \App\Models\Outlet::find($data['outlet_id']);
            
            // Tax base (DPP) = Subtotal - Discount
            $taxBase = $subtotal - $discountAmount;
            
            // Service Charge: X% dari Tax Base
            $serviceChargeRate = $outlet->service_charge_rate ?? 0;
            $serviceChargeAmount = $taxBase * ($serviceChargeRate / 100);
            
            // Tax: Y% dari (Tax Base + Service Charge)
            // Note: PB1 biasanya dikenakan atas total layanan
            $taxableAmount = $taxBase + $serviceChargeAmount;
            $taxRate = $outlet->tax_rate ?? 10; // Default 10% jika null
            $taxAmount = $taxableAmount * ($taxRate / 100);

            // Total: Tax Base + Service + Tax
            $totalAmount = $taxBase + $serviceChargeAmount + $taxAmount;

            // 5. Buat record sale
            $sale = Sale::create([
                'invoice_number' => $invoiceNumber,
                'outlet_id' => $data['outlet_id'],
                'cash_session_id' => $data['cash_session_id'],
                // Gunakan auth()->id() (helper standar Laravel) jika tersedia
                'user_id' => \Illuminate\Support\Facades\Auth::id() ?? $data['user_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'sale_date' => now()->toDateString(),
                'sales_type' => $data['sales_type'] ?? 'regular',
                'subtotal' => $subtotal,
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'] ?? 0,
                'discount_amount' => $discountAmount,
                'service_charge_amount' => $serviceChargeAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'customer_name' => $data['customer_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'completed',
            ]);

            // 6. Buat sale items & logika BOM / pengurangan stok
            foreach ($data['items'] as $item) {
                $product = Product::with(['bomHeader' => function($q){ $q->where('is_active', true)->with('details.component'); }])->findOrFail($item['product_id']);

                $itemSubtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount_amount'] ?? 0);
                
                // Hitung COGS per item
                $itemCogs = $this->calculateItemCogs($product, $item['quantity']);

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
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
                        userId: $sale->user_id
                    );
                } elseif ($type === 'finished_good') {
                    // Cek BOM aktif
                    $bom = $product->bomHeader && $product->bomHeader->is_active ? $product->bomHeader : null;
                    if ($bom) {
                        // Validasi stok komponen BOM (kecuali jika allow negative stock)
                        $allowNegativeStock = config('app.allow_negative_stock', false);
                        if (!$allowNegativeStock) {
                            foreach ($bom->details as $detail) {
                                $consumeQty = $detail->quantity * $item['quantity'];
                                $available = $this->stockService->getAvailableStock($detail->component_product_id, $data['outlet_id']);
                                if ($available < $consumeQty) {
                                    throw new Exception("Stok bahan {$detail->component->name} tidak mencukupi. Dibutuhkan: {$consumeQty}, tersedia: {$available}");
                                }
                            }
                        }
                        // Jika semua cukup, lakukan pengurangan stok per komponen
                        foreach ($bom->details as $detail) {
                            $consumeQty = $detail->quantity * $item['quantity'];
                            $this->stockService->reduceSaleStock(
                                productId: $detail->component_product_id,
                                outletId: $data['outlet_id'],
                                quantity: $consumeQty,
                                saleId: $sale->id,
                                userId: $sale->user_id
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
                                userId: $sale->user_id
                            );
                        }
                    }
                } elseif ($type === 'service') {
                    // Tidak mengurangi stok
                }
            }

            // 7. Catat pembayaran
            Payment::create([
                'sale_id' => $sale->id,
                'payment_method_id' => $data['payment_method_id'],
                'amount' => $data['payment_amount'],
                'reference_number' => $data['payment_reference'] ?? null,
                'notes' => $data['payment_notes'] ?? null,
            ]);

            // 8. Update cash session
            $this->updateCashSession($data['cash_session_id'], $totalAmount, $data['payment_method_id']);

            // 9. Loyalty points & customer stats (jika ada customer)
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

            return $sale->load(['items', 'payments.paymentMethod', 'outlet', 'user', 'customer']);

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
        $date = now()->format('Ymd');
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

        return "INV-{$outlet}-{$date}-{$sequence}";
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
            $sale = Sale::with('items')->findOrFail($saleId);

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
                            notes: "Pembatalan transaksi (komponen BOM): {$reason}"
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
                            notes: "Pembatalan transaksi: {$reason}"
                        );
                    }
                } elseif ($type !== 'service') {
                    $this->stockService->restoreSaleStock(
                        productId: $item->product_id,
                        outletId: $sale->outlet_id,
                        quantity: $item->quantity,
                        saleId: $sale->id,
                        userId: \Illuminate\Support\Facades\Auth::id(),
                        notes: "Pembatalan transaksi: {$reason}"
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

            DB::commit();

            return $sale->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Hitung COGS (Cost of Goods Sold) per item berdasarkan BOM atau purchase_price
     *
     * @param Product $product
     * @param float $quantity
     * @return float
     */
    protected function calculateItemCogs(Product $product, float $quantity): float
    {
        $type = $product->product_type ?? 'finished_good';

        if ($type === 'service') {
            return 0; // Service tidak ada COGS
        }

        if ($type === 'raw_material') {
            // Raw material: COGS = purchase_price × quantity
            return ($product->purchase_price ?? 0) * $quantity;
        }

        // finished_good: Cek ada BOM aktif atau tidak
        $bom = $product->bomHeader && $product->bomHeader->is_active ? $product->bomHeader : null;
        
        if ($bom) {
            // Ada BOM: COGS = sum(component.purchase_price × bom_detail.quantity × sale_quantity)
            $totalCogs = 0;
            foreach ($bom->details as $detail) {
                $componentCost = ($detail->component->purchase_price ?? 0) * $detail->quantity * $quantity;
                $totalCogs += $componentCost;
            }
            return $totalCogs;
        } else {
            // Tidak ada BOM: COGS = purchase_price × quantity (fallback)
            return ($product->purchase_price ?? 0) * $quantity;
        }
    }
}

