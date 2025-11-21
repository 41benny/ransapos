<?php

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Stock;
use App\Models\StockMutation;
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
     * Send/dispatch transfer (from outlet perspective)
     */
    public function sendTransfer(StockTransfer $transfer): StockTransfer
    {
        if (!$transfer->canBeSent()) {
            throw new Exception('Transfer tidak dapat dikirim. Status: ' . $transfer->status);
        }

        DB::beginTransaction();

        try {
            // Kurangi stok di outlet pengirim
            foreach ($transfer->items as $item) {
                // Cek ketersediaan stok
                $stock = Stock::where('product_id', $item->product_id)
                    ->where('outlet_id', $transfer->from_outlet_id)
                    ->first();

                if (!$stock || $stock->quantity < $item->quantity) {
                    throw new Exception(
                        "Stok {$item->product->name} tidak mencukupi di outlet pengirim. " .
                        "Tersedia: " . ($stock ? $stock->quantity : 0) . ", Dibutuhkan: {$item->quantity}"
                    );
                }

                // Kurangi stok
                $stockBefore = $stock->quantity;
                $stock->quantity -= $item->quantity;
                $stock->last_mutation_at = now();
                $stock->save();

                // Catat mutasi (transfer_out)
                StockMutation::create([
                    'product_id' => $item->product_id,
                    'outlet_id' => $transfer->from_outlet_id,
                    'mutation_type' => 'transfer_out',
                    'quantity' => -$item->quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stock->quantity,
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'mutation_date' => $transfer->transfer_date,
                    'notes' => "Transfer ke {$transfer->toOutlet->name}",
                    'created_by' => auth()->id(),
                ]);
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

                // Catat mutasi (transfer_in)
                StockMutation::create([
                    'product_id' => $item->product_id,
                    'outlet_id' => $transfer->to_outlet_id,
                    'mutation_type' => 'transfer_in',
                    'quantity' => $receivedQty,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stock->quantity,
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'mutation_date' => now()->toDateString(),
                    'notes' => "Transfer dari {$transfer->fromOutlet->name}",
                    'created_by' => auth()->id(),
                ]);

                // Jika ada selisih (shortage/damage), catat sebagai adjustment di outlet pengirim
                $difference = $receivedQty - $item->quantity;
                if ($difference != 0) {
                    $senderStock = Stock::where('product_id', $item->product_id)
                        ->where('outlet_id', $transfer->from_outlet_id)
                        ->first();

                    if ($senderStock) {
                        StockMutation::create([
                            'product_id' => $item->product_id,
                            'outlet_id' => $transfer->from_outlet_id,
                            'mutation_type' => 'adjustment',
                            'quantity' => $difference,
                            'stock_before' => $senderStock->quantity,
                            'stock_after' => $senderStock->quantity,
                            'reference_type' => 'stock_transfer',
                            'reference_id' => $transfer->id,
                            'mutation_date' => now()->toDateString(),
                            'notes' => $difference < 0 ? 'Selisih transfer (shortage/damage)' : 'Selisih transfer (excess)',
                            'created_by' => auth()->id(),
                        ]);
                    }
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

                        // Catat mutasi pembatalan
                        StockMutation::create([
                            'product_id' => $item->product_id,
                            'outlet_id' => $transfer->from_outlet_id,
                            'mutation_type' => 'adjustment',
                            'quantity' => $item->quantity,
                            'stock_before' => $stockBefore,
                            'stock_after' => $stock->quantity,
                            'reference_type' => 'stock_transfer',
                            'reference_id' => $transfer->id,
                            'mutation_date' => now()->toDateString(),
                            'notes' => "Pembatalan transfer: {$reason}",
                            'created_by' => auth()->id(),
                        ]);
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
