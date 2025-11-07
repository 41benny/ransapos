<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Exception;

class PurchaseService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Buat purchase baru (status: draft)
     * 
     * @param array $data
     * @return Purchase
     * @throws Exception
     */
    public function createPurchase(array $data): Purchase
    {
        DB::beginTransaction();
        
        try {
            // 1. Generate purchase number
            $purchaseNumber = $this->generatePurchaseNumber($data['outlet_id']);

            // 2. Hitung total dari items
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $itemSubtotal -= $item['discount_amount'] ?? 0;
                $subtotal += $itemSubtotal;
            }

            // 3. Hitung total amount
            $taxAmount = $data['tax_amount'] ?? 0;
            $discountAmount = $data['discount_amount'] ?? 0;
            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            // 4. Buat purchase header
            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,
                'outlet_id' => $data['outlet_id'],
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'],
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'payment_status' => $data['payment_status'] ?? 'pending',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // 5. Buat purchase items
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $itemSubtotal -= $item['discount_amount'] ?? 0;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'subtotal' => $itemSubtotal,
                ]);
            }

            DB::commit();

            return $purchase->load(['items.product', 'outlet', 'supplier', 'creator']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update purchase
     * 
     * @param Purchase $purchase
     * @param array $data
     * @return Purchase
     * @throws Exception
     */
    public function updatePurchase(Purchase $purchase, array $data): Purchase
    {
        DB::beginTransaction();
        
        try {
            // Hanya bisa update jika masih draft
            if (!$purchase->isDraft()) {
                throw new Exception('Hanya purchase dengan status draft yang bisa diubah');
            }

            // 1. Hitung ulang total dari items
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $itemSubtotal -= $item['discount_amount'] ?? 0;
                $subtotal += $itemSubtotal;
            }

            // 2. Hitung total amount
            $taxAmount = $data['tax_amount'] ?? 0;
            $discountAmount = $data['discount_amount'] ?? 0;
            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            // 3. Update purchase header
            $purchase->update([
                'outlet_id' => $data['outlet_id'],
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'payment_status' => $data['payment_status'] ?? $purchase->payment_status,
                'notes' => $data['notes'] ?? $purchase->notes,
            ]);

            // 4. Hapus items lama
            $purchase->items()->delete();

            // 5. Buat items baru
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $itemSubtotal -= $item['discount_amount'] ?? 0;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'subtotal' => $itemSubtotal,
                ]);
            }

            DB::commit();

            return $purchase->fresh()->load(['items.product', 'outlet', 'supplier', 'creator']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Terima purchase (ubah status jadi received & tambah stok)
     * 
     * @param Purchase $purchase
     * @return Purchase
     * @throws Exception
     */
    public function receivePurchase(Purchase $purchase): Purchase
    {
        DB::beginTransaction();
        
        try {
            // Validasi status
            if (!$purchase->isDraft()) {
                throw new Exception('Hanya purchase dengan status draft yang bisa diterima');
            }

            // Update purchase status
            $purchase->update([
                'status' => 'received',
                'received_at' => now(),
                'received_by' => auth()->id(),
            ]);

            // Tambah stok untuk setiap item
            foreach ($purchase->items as $item) {
                $this->stockService->addPurchaseStock(
                    productId: $item->product_id,
                    outletId: $purchase->outlet_id,
                    quantity: $item->quantity,
                    purchaseId: $purchase->id,
                    userId: auth()->id()
                );
            }

            DB::commit();

            return $purchase->fresh()->load(['items.product', 'outlet', 'supplier', 'creator', 'receiver']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Batalkan purchase
     * 
     * @param Purchase $purchase
     * @param string $reason
     * @return Purchase
     * @throws Exception
     */
    public function cancelPurchase(Purchase $purchase, string $reason = ''): Purchase
    {
        DB::beginTransaction();
        
        try {
            // Tidak bisa cancel jika sudah received
            if ($purchase->isReceived()) {
                throw new Exception('Purchase yang sudah diterima tidak bisa dibatalkan');
            }

            $purchase->update([
                'status' => 'cancelled',
                'notes' => ($purchase->notes ? $purchase->notes . "\n\n" : '') . "Dibatalkan: {$reason}",
            ]);

            DB::commit();

            return $purchase->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate purchase number unik
     * 
     * @param int $outletId
     * @return string
     */
    protected function generatePurchaseNumber(int $outletId): string
    {
        $date = now()->format('Ymd');
        $outlet = str_pad($outletId, 3, '0', STR_PAD_LEFT);
        
        // Cari purchase terakhir hari ini untuk outlet ini
        $lastPurchase = Purchase::where('outlet_id', $outletId)
            ->whereDate('purchase_date', now())
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPurchase) {
            // Extract nomor urut terakhir
            $lastNumber = (int) substr($lastPurchase->purchase_number, -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $sequence = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        return "PO-{$outlet}-{$date}-{$sequence}";
    }

    /**
     * Get purchases dengan filter
     * 
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPurchases(array $filters = [])
    {
        $query = Purchase::with(['outlet', 'supplier', 'creator', 'items']);

        // Filter by outlet
        if (!empty($filters['outlet_id'])) {
            $query->where('outlet_id', $filters['outlet_id']);
        }

        // Filter by supplier
        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $query->whereDate('purchase_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('purchase_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('purchase_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);
    }
}


