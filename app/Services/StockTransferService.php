<?php

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Stock;
use App\Models\StockMutation;
use App\Services\CostService;
use Illuminate\Support\Facades\DB;
use Exception;

class StockTransferService
{
    /**
     * Generate transfer number
     */
    public function generateTransferNumber(int $fromOutletId, int $toOutletId): string
    {
        $date = now()->format('Ymd');

        $lastTransfer = StockTransfer::where('transfer_number', 'like', "TRF-{$fromOutletId}-{$toOutletId}-{$date}-%")
            ->lockForUpdate()
            ->orderBy('transfer_number', 'desc')
            ->first();

        if ($lastTransfer) {
            $lastNumber = (int) substr($lastTransfer->transfer_number, -3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return "TRF-{$fromOutletId}-{$toOutletId}-{$date}-{$newNumber}";
    }

    /**
     * Create new stock transfer
     */
    public function createTransfer(array $data): StockTransfer
    {
        DB::beginTransaction();

        try {
            // Validasi outlet berbeda
            if ($data['from_outlet_id'] == $data['to_outlet_id']) {
                throw new Exception('Outlet pengirim dan penerima tidak boleh sama.');
            }

            // Generate transfer number
            $transferNumber = $this->generateTransferNumber(
                $data['from_outlet_id'],
                $data['to_outlet_id']
            );

            // Create transfer header
            $transfer = StockTransfer::create([
                'transfer_number' => $transferNumber,
                'from_outlet_id' => $data['from_outlet_id'],
                'to_outlet_id' => $data['to_outlet_id'],
                'transfer_date' => $data['transfer_date'],
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Create transfer items
            foreach ($data['items'] as $item) {
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();
            return $transfer->load('items.product');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update transfer yang masih pending.
     */
    public function updateTransfer(StockTransfer $transfer, array $data): StockTransfer
    {
        if (!$transfer->isPending()) {
            throw new Exception('Transfer hanya bisa diedit saat status masih pending.');
        }

        DB::beginTransaction();

        try {
            if ($data['from_outlet_id'] == $data['to_outlet_id']) {
                throw new Exception('Outlet pengirim dan penerima tidak boleh sama.');
            }

            $transfer->update([
                'from_outlet_id' => $data['from_outlet_id'],
                'to_outlet_id' => $data['to_outlet_id'],
                'transfer_date' => $data['transfer_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $transfer->load('items');

            $submittedItems = collect($data['items'] ?? [])
                ->map(function ($item) {
                    return [
                        'product_id' => (int) $item['product_id'],
                        'quantity' => (float) $item['quantity'],
                        'notes' => $item['notes'] ?? null,
                    ];
                })
                ->filter(fn ($item) => $item['product_id'] > 0 && $item['quantity'] > 0)
                ->keyBy('product_id');

            if ($submittedItems->isEmpty()) {
                throw new Exception('Minimal harus ada 1 produk.');
            }

            $existingByProduct = $transfer->items->keyBy('product_id');

            $existingByProduct->each(function (StockTransferItem $existingItem) use ($submittedItems) {
                if (!$submittedItems->has((int) $existingItem->product_id)) {
                    $existingItem->delete();
                }
            });

            $submittedItems->each(function ($itemData, $productId) use ($existingByProduct, $transfer) {
                $existingItem = $existingByProduct->get((int) $productId);

                if ($existingItem) {
                    $existingItem->update([
                        'quantity' => $itemData['quantity'],
                        'notes' => $itemData['notes'],
                        'received_quantity' => null,
                    ]);
                    return;
                }

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'notes' => $itemData['notes'],
                ]);
            });

            DB::commit();

            return $transfer->fresh()->load('items.product');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Send/dispatch transfer (from outlet perspective)
     */
    public function sendTransfer(StockTransfer $transfer): StockTransfer
    {
        if (!$transfer->canBeSent()) {
            throw new Exception('Transfer tidak dapat dikirim. Status: ' . $transfer->status);
        }

        DB::beginTransaction();

        try {
            $stockService = app(StockService::class);

            // Kurangi stok di outlet pengirim
            foreach ($transfer->items as $item) {
                $stock = Stock::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'outlet_id' => $transfer->from_outlet_id,
                    ],
                    [
                        'quantity' => 0,
                        'last_mutation_at' => now(),
                    ]
                );

                // Kurangi stok
                $stockBefore = $stock->quantity;
                $stock->quantity -= $item->quantity;
                $stock->last_mutation_at = now();
                $stock->save();

                // Ambil avg cost dari outlet pengirim
                $costService = app(CostService::class);
                $unitCost = $costService->getAvgCost($item->product_id, $transfer->from_outlet_id);

                // Catat mutasi (transfer_out) dengan valuasi cost
                StockMutation::create([
                    'product_id' => $item->product_id,
                    'outlet_id' => $transfer->from_outlet_id,
                    'mutation_type' => 'transfer_out',
                    'quantity' => -$item->quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $unitCost * $item->quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stock->quantity,
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'mutation_date' => $transfer->transfer_date,
                    'notes' => "Transfer ke {$transfer->toOutlet->name}",
                    'created_by' => auth()->id(),
                ]);

                $stockService->recalculateMutationBalances(
                    (int) $item->product_id,
                    (int) $transfer->from_outlet_id,
                    (string) $transfer->transfer_date
                );
            }

            // Update transfer status
            $transfer->update([
                'status' => 'in_transit',
                'sent_at' => now(),
                'sent_by' => auth()->id(),
            ]);

            DB::commit();
            return $transfer->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Receive transfer (to outlet perspective)
     */
    public function receiveTransfer(StockTransfer $transfer, array $receivedItems): StockTransfer
    {
        if (!$transfer->canBeReceived()) {
            throw new Exception('Transfer tidak dapat diterima. Status: ' . $transfer->status);
        }

        DB::beginTransaction();

        try {
            $stockService = app(StockService::class);

            // Update received quantities dan tambah stok di outlet penerima
            foreach ($receivedItems as $itemId => $receivedQty) {
                $item = StockTransferItem::findOrFail($itemId);

                // Update received quantity
                $item->update([
                    'received_quantity' => $receivedQty,
                ]);

                // Tambah stok di outlet penerima
                $stock = Stock::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'outlet_id' => $transfer->to_outlet_id,
                    ],
                    [
                        'quantity' => 0,
                        'last_mutation_at' => now(),
                    ]
                );

                $stockBefore = $stock->quantity;
                $stock->quantity += $receivedQty;
                $stock->last_mutation_at = now();
                $stock->save();

                // Ambil unit cost dari mutasi transfer_out (cost outlet pengirim)
                $outMutation = StockMutation::where('product_id', $item->product_id)
                    ->where('outlet_id', $transfer->from_outlet_id)
                    ->where('reference_type', 'stock_transfer')
                    ->where('reference_id', $transfer->id)
                    ->where('mutation_type', 'transfer_out')
                    ->first();
                $unitCost = $outMutation->unit_cost ?? 0;

                // Catat mutasi (transfer_in) dengan valuasi cost
                StockMutation::create([
                    'product_id' => $item->product_id,
                    'outlet_id' => $transfer->to_outlet_id,
                    'mutation_type' => 'transfer_in',
                    'quantity' => $receivedQty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $unitCost * $receivedQty,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stock->quantity,
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'mutation_date' => now()->toDateString(),
                    'notes' => "Transfer dari {$transfer->fromOutlet->name}",
                    'created_by' => auth()->id(),
                ]);

                $stockService->recalculateMutationBalances(
                    (int) $item->product_id,
                    (int) $transfer->to_outlet_id,
                    now()->toDateString()
                );

                // Update avg cost outlet penerima (seperti menerima pembelian)
                $costService = app(CostService::class);
                $costService->updateAvgCostOnReceive(
                    productId: $item->product_id,
                    outletId: $transfer->to_outlet_id,
                    receivedQty: $receivedQty,
                    unitPrice: $unitCost,
                );

                // Jika ada selisih (shortage/damage), catat sebagai adjustment di outlet pengirim
                $difference = $receivedQty - $item->quantity;
                if ($difference != 0) {
                    // shortage (difference < 0): sisa barang diasumsikan kembali ke outlet pengirim
                    // excess   (difference > 0): koreksi stok pengirim agar sesuai barang yang benar-benar diterima
                    $senderStock = Stock::firstOrCreate(
                        [
                            'product_id' => $item->product_id,
                            'outlet_id' => $transfer->from_outlet_id,
                        ],
                        [
                            'quantity' => 0,
                            'last_mutation_at' => now(),
                        ]
                    );

                    $senderStockBefore = $senderStock->quantity;
                    $adjustQtySender = -$difference;
                    $senderStock->quantity += $adjustQtySender;
                    $senderStock->last_mutation_at = now();
                    $senderStock->save();

                    StockMutation::create([
                        'product_id' => $item->product_id,
                        'outlet_id' => $transfer->from_outlet_id,
                        'mutation_type' => 'adjustment',
                        'quantity' => $adjustQtySender,
                        'unit_cost' => $unitCost,
                        'total_cost' => abs($adjustQtySender) * $unitCost,
                        'stock_before' => $senderStockBefore,
                        'stock_after' => $senderStock->quantity,
                        'reference_type' => 'stock_transfer',
                        'reference_id' => $transfer->id,
                        'mutation_date' => now()->toDateString(),
                        'notes' => $difference < 0
                            ? 'Selisih terima kurang: stok selisih dikembalikan ke outlet pengirim'
                            : 'Selisih terima lebih: koreksi stok outlet pengirim',
                        'created_by' => auth()->id(),
                    ]);

                    $stockService->recalculateMutationBalances(
                        (int) $item->product_id,
                        (int) $transfer->from_outlet_id,
                        now()->toDateString()
                    );
                }
            }

            // Update transfer status
            $transfer->update([
                'status' => 'received',
                'received_at' => now(),
                'received_by' => auth()->id(),
            ]);

            DB::commit();
            return $transfer->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel transfer
     */
    public function cancelTransfer(StockTransfer $transfer, string $reason): StockTransfer
    {
        if (!$transfer->canBeCancelled()) {
            throw new Exception('Transfer tidak dapat dibatalkan. Status: ' . $transfer->status);
        }

        DB::beginTransaction();

        try {
            $stockService = app(StockService::class);

            // Jika sudah dikirim (in_transit), kembalikan stok ke outlet pengirim
            if ($transfer->status === 'in_transit') {
                foreach ($transfer->items as $item) {
                    $stock = Stock::where('product_id', $item->product_id)
                        ->where('outlet_id', $transfer->from_outlet_id)
                        ->first();

                    if ($stock) {
                        $stockBefore = $stock->quantity;
                        $stock->quantity += $item->quantity;
                        $stock->last_mutation_at = now();
                        $stock->save();

                        // Ambil cost dari mutasi transfer_out asli
                        $outMutation = StockMutation::where('product_id', $item->product_id)
                            ->where('outlet_id', $transfer->from_outlet_id)
                            ->where('reference_type', 'stock_transfer')
                            ->where('reference_id', $transfer->id)
                            ->where('mutation_type', 'transfer_out')
                            ->first();
                        $unitCost = $outMutation->unit_cost ?? 0;

                        // Catat mutasi pembatalan dengan cost
                        StockMutation::create([
                            'product_id' => $item->product_id,
                            'outlet_id' => $transfer->from_outlet_id,
                            'mutation_type' => 'adjustment',
                            'quantity' => $item->quantity,
                            'unit_cost' => $unitCost,
                            'total_cost' => $unitCost * $item->quantity,
                            'stock_before' => $stockBefore,
                            'stock_after' => $stock->quantity,
                            'reference_type' => 'stock_transfer',
                            'reference_id' => $transfer->id,
                            'mutation_date' => now()->toDateString(),
                            'notes' => "Pembatalan transfer: {$reason}",
                            'created_by' => auth()->id(),
                        ]);

                        $stockService->recalculateMutationBalances(
                            (int) $item->product_id,
                            (int) $transfer->from_outlet_id,
                            now()->toDateString()
                        );
                    }
                }
            }

            // Update transfer status
            $transfer->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancel_reason' => $reason,
            ]);

            DB::commit();
            return $transfer->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
