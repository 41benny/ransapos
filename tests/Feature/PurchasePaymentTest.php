<?php

namespace Tests\Feature;

use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\CoaAccount;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CashAccountService;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected CashAccountService $cashAccountService;
    protected PurchaseService $purchaseService;
    protected User $user;
    protected Outlet $outlet;
    protected Supplier $supplier;
    protected Product $product;
    protected CashAccount $cashAccount;
    protected CoaAccount $coaAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize services
        $this->cashAccountService = app(CashAccountService::class);
        $this->purchaseService = app(PurchaseService::class);

        // Create test data
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->outlet = Outlet::factory()->create();
        $this->supplier = Supplier::factory()->create();
        $this->product = Product::factory()->create();

        // Create Cash Account
        $this->cashAccount = CashAccount::create([
            'name' => 'Kas Test',
            'code' => 'KAS-TEST',
            'type' => 'cash',
            'is_active' => true,
            'opening_balance' => 10000000,
            'current_balance' => 10000000,
            'created_by' => $this->user->id,
        ]);

        // Create COA Account for HPP
        $this->coaAccount = CoaAccount::create([
            'code' => '5-100',
            'name' => 'Harga Pokok Penjualan (HPP)',
            'type' => 'expense',
            'group' => 'HPP',
            'is_active' => true,
        ]);
    }

    public function test_can_record_purchase_payment_after_received(): void
    {
        // Create and receive purchase
        $purchase = $this->createAndReceivePurchase();

        // Record payment
        $paymentData = [
            'cash_account_id' => $this->cashAccount->id,
            'amount' => 1000000,
            'transaction_date' => now()->format('Y-m-d'),
            'notes' => 'Pembayaran pertama',
            'created_by' => $this->user->id,
        ];

        $transaction = $this->cashAccountService->recordPurchasePayment($purchase, $paymentData);

        // Assert transaction created
        $this->assertNotNull($transaction);
        $this->assertEquals('out', $transaction->type);
        $this->assertEquals(1000000, $transaction->amount);
        $this->assertEquals('purchase', $transaction->reference_type);
        $this->assertEquals($purchase->id, $transaction->reference_id);

        // Assert transaction number format
        $this->assertStringStartsWith('KAS-', $transaction->transaction_number);
        $this->assertStringContainsString($this->cashAccount->code, $transaction->transaction_number);

        // Assert cash account balance reduced
        $this->cashAccount->refresh();
        $this->assertEquals(9000000, $this->cashAccount->current_balance);

        // Assert purchase payment status updated
        $purchase->refresh();
        $this->assertEquals('partial', $purchase->payment_status);
    }

    public function test_purchase_status_becomes_paid_when_fully_paid(): void
    {
        // Create purchase with amount 1,000,000
        $purchase = $this->createAndReceivePurchase(1000000);

        // Pay full amount
        $paymentData = [
            'cash_account_id' => $this->cashAccount->id,
            'amount' => 1000000,
            'transaction_date' => now()->format('Y-m-d'),
            'created_by' => $this->user->id,
        ];

        $this->cashAccountService->recordPurchasePayment($purchase, $paymentData);

        // Assert purchase status is paid
        $purchase->refresh();
        $this->assertEquals('paid', $purchase->payment_status);
    }

    public function test_can_make_partial_payments(): void
    {
        // Create purchase with amount 3,000,000
        $purchase = $this->createAndReceivePurchase(3000000);

        // First payment - 1,000,000
        $this->cashAccountService->recordPurchasePayment($purchase, [
            'cash_account_id' => $this->cashAccount->id,
            'amount' => 1000000,
            'transaction_date' => now()->format('Y-m-d'),
            'created_by' => $this->user->id,
        ]);

        $purchase->refresh();
        $this->assertEquals('partial', $purchase->payment_status);

        // Second payment - 1,000,000
        $this->cashAccountService->recordPurchasePayment($purchase, [
            'cash_account_id' => $this->cashAccount->id,
            'amount' => 1000000,
            'transaction_date' => now()->format('Y-m-d'),
            'created_by' => $this->user->id,
        ]);

        $purchase->refresh();
        $this->assertEquals('partial', $purchase->payment_status);

        // Third payment - 1,000,000 (remaining)
        $this->cashAccountService->recordPurchasePayment($purchase, [
            'cash_account_id' => $this->cashAccount->id,
            'amount' => 1000000,
            'transaction_date' => now()->format('Y-m-d'),
            'created_by' => $this->user->id,
        ]);

        $purchase->refresh();
        $this->assertEquals('paid', $purchase->payment_status);

        // Assert 3 transactions created
        $this->assertEquals(3, $purchase->cashTransactions()->count());

        // Assert total paid equals purchase total
        $totalPaid = $purchase->cashTransactions()->sum('amount');
        $this->assertEquals($purchase->total_amount, $totalPaid);
    }

    public function test_cannot_pay_more_than_remaining_amount(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Jumlah pembayaran melebihi sisa tagihan');

        // Create purchase with amount 1,000,000
        $purchase = $this->createAndReceivePurchase(1000000);

        // Try to pay more than total
        $this->cashAccountService->recordPurchasePayment($purchase, [
            'cash_account_id' => $this->cashAccount->id,
            'amount' => 1500000, // More than purchase total
            'transaction_date' => now()->format('Y-m-d'),
            'created_by' => $this->user->id,
        ]);
    }

    public function test_cannot_pay_draft_purchase(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Purchase harus sudah diterima sebelum bisa dibayar');

        // Create purchase but don't receive it
        $purchase = $this->createPurchase();

        // Try to pay
        $this->cashAccountService->recordPurchasePayment($purchase, [
            'cash_account_id' => $this->cashAccount->id,
            'amount' => 1000000,
            'transaction_date' => now()->format('Y-m-d'),
            'created_by' => $this->user->id,
        ]);
    }

    public function test_payment_includes_coa_account(): void
    {
        // Create and receive purchase
        $purchase = $this->createAndReceivePurchase();

        // Record payment
        $transaction = $this->cashAccountService->recordPurchasePayment($purchase, [
            'cash_account_id' => $this->cashAccount->id,
            'amount' => 1000000,
            'transaction_date' => now()->format('Y-m-d'),
            'created_by' => $this->user->id,
        ]);

        // Assert COA account is set
        $this->assertNotNull($transaction->coa_account_id);
        $this->assertEquals($this->coaAccount->id, $transaction->coa_account_id);
    }

    public function test_transaction_number_increments_daily_per_account(): void
    {
        // Create purchase
        $purchase = $this->createAndReceivePurchase(5000000);

        // Make 3 payments on same day
        for ($i = 1; $i <= 3; $i++) {
            $transaction = $this->cashAccountService->recordPurchasePayment($purchase, [
                'cash_account_id' => $this->cashAccount->id,
                'amount' => 1000000,
                'transaction_date' => now()->format('Y-m-d'),
                'created_by' => $this->user->id,
            ]);

            // Assert transaction number has correct sequence
            $expectedSequence = str_pad($i, 3, '0', STR_PAD_LEFT);
            $this->assertStringEndsWith($expectedSequence, $transaction->transaction_number);
        }
    }

    public function test_insufficient_balance_throws_exception(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Saldo tidak mencukupi');

        // Create cash account with low balance
        $lowBalanceAccount = CashAccount::create([
            'name' => 'Kas Kecil',
            'code' => 'KAS-KECIL',
            'type' => 'cash',
            'is_active' => true,
            'opening_balance' => 100000,
            'current_balance' => 100000,
            'created_by' => $this->user->id,
        ]);

        // Create purchase with amount higher than balance
        $purchase = $this->createAndReceivePurchase(1000000);

        // Try to pay with insufficient balance
        $this->cashAccountService->recordPurchasePayment($purchase, [
            'cash_account_id' => $lowBalanceAccount->id,
            'amount' => 1000000,
            'transaction_date' => now()->format('Y-m-d'),
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * Helper: Create purchase
     */
    protected function createPurchase(float $amount = 2000000): Purchase
    {
        $data = [
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_price' => $amount / 10,
                    'discount_amount' => 0,
                ],
            ],
            'tax_amount' => 0,
            'discount_amount' => 0,
        ];

        return $this->purchaseService->createPurchase($data);
    }

    /**
     * Helper: Create and receive purchase
     */
    protected function createAndReceivePurchase(float $amount = 2000000): Purchase
    {
        $purchase = $this->createPurchase($amount);
        return $this->purchaseService->receivePurchase($purchase);
    }
}
