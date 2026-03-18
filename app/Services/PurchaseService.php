<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Exception;

class PurchaseService
{
    protected StockService $stockService;
    protected CostService $costService;

    public function __construct(StockService $stockService, CostService $costService)
    {
        $this->stockService = $stockService;
        $this->costService = $costService;
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
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            DB::beginTransaction();

            try {
                // 1. Generate purchase number
                $purchaseNumber = $this->generatePurchaseNumber(
                    outletId: (int) $data['outlet_id'],
                    purchaseDate: $data['purchase_date'] ?? now()->toDateString(),
                );

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
            } catch (QueryException $e) {
                DB::rollBack();

                if ($this->isDuplicatePurchaseNumberException($e) && $attempt < $maxAttempts) {
                    continue;
                }

                throw $e;
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        throw new Exception('Gagal membuat nomor purchase yang unik. Silakan coba lagi.');
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

            // Tambah stok dan update avg cost untuk setiap item
            foreach ($purchase->items as $item) {
                // Harga beli neto per unit (harga - diskon per unit)
                $netUnitPrice = (float) $item->unit_price;
                if ((float) $item->discount_amount > 0 && (float) $item->quantity > 0) {
                    $netUnitPrice -= ((float) $item->discount_amount / (float) $item->quantity);
                }

                $this->stockService->addPurchaseStock(
                    productId: $item->product_id,
                    outletId: $purchase->outlet_id,
                    quantity: $item->quantity,
                    purchaseId: $purchase->id,
                    userId: auth()->id(),
                    unitPrice: $netUnitPrice,
                    mutationDate: optional($purchase->received_at)->toDateString() ?? now()->toDateString()
                );

                // Update moving average cost
                $this->costService->updateAvgCostOnReceive(
                    productId: $item->product_id,
                    outletId: $purchase->outlet_id,
                    receivedQty: $item->quantity,
                    unitPrice: $netUnitPrice,
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
     * @param string $purchaseDate
     * @return string
     */
    protected function generatePurchaseNumber(int $outletId, string $purchaseDate): string
    {
        $purchaseDateObj = Carbon::parse($purchaseDate);
        $date = $purchaseDateObj->format('Ymd');
        $outlet = str_pad($outletId, 3, '0', STR_PAD_LEFT);
        $prefix = "PO-{$outlet}-{$date}-";

        // Cari nomor purchase terakhir berdasarkan prefix (outlet + tanggal pada nomor).
        $lastPurchase = Purchase::where('purchase_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderBy('purchase_number', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastPurchase) {
            $nextNumber = $this->extractPurchaseNumberSequence($lastPurchase->purchase_number) + 1;
        }

        // Safety net untuk data lama yang nomor/date-nya pernah tidak sinkron.
        do {
            $sequence = str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
            $candidate = $prefix . $sequence;
            $nextNumber++;
        } while (Purchase::where('purchase_number', $candidate)->lockForUpdate()->exists());

        return $candidate;
    }

    protected function extractPurchaseNumberSequence(string $purchaseNumber): int
    {
        if (preg_match('/(\d+)$/', $purchaseNumber, $matches) === 1) {
            return (int) $matches[1];
        }

        return 0;
    }

    protected function isDuplicatePurchaseNumberException(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        if (!str_contains($message, 'duplicate entry')) {
            return false;
        }

        return str_contains($message, 'purchases_purchase_number_unique')
            || str_contains($message, 'purchase_number');
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
