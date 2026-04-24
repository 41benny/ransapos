<?php

namespace Tests\Feature;

use App\Models\CashAccount;
use App\Models\CoaAccount;
use App\Models\User;
use App\Services\CashAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashTransactionBackdateTest extends TestCase
{
    use RefreshDatabase;

    protected CashAccountService $cashAccountService;
    protected User $user;
    protected CashAccount $cashAccount;
    protected CoaAccount $expenseCoa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashAccountService = app(CashAccountService::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->cashAccount = CashAccount::create([
            'name' => 'Petty Cash Moka Test',
            'code' => 'PCM-T',
            'type' => 'cash',
            'is_active' => true,
            'opening_balance' => 1000,
            'current_balance' => 1000,
            'created_by' => $this->user->id,
        ]);

        $this->expenseCoa = CoaAccount::create([
            'code' => '6-100',
            'name' => 'Biaya Operasional Test',
            'type' => 'expense',
            'group' => 'OPERASIONAL',
            'is_active' => true,
        ]);
    }

    public function test_backdated_transaction_recalculates_subsequent_running_balances(): void
    {
        $march10 = $this->cashAccountService->recordTransaction([
            'cash_account_id' => $this->cashAccount->id,
            'coa_account_id' => $this->expenseCoa->id,
            'type' => 'out',
            'transaction_date' => '2026-03-10',
            'amount' => 100,
            'description' => 'Belanja 10 Maret',
            'created_by' => $this->user->id,
        ]);

        $march12 = $this->cashAccountService->recordTransaction([
            'cash_account_id' => $this->cashAccount->id,
            'type' => 'in',
            'transaction_date' => '2026-03-12',
            'amount' => 500,
            'description' => 'Top up 12 Maret',
            'created_by' => $this->user->id,
        ]);

        $march11 = $this->cashAccountService->recordTransaction([
            'cash_account_id' => $this->cashAccount->id,
            'coa_account_id' => $this->expenseCoa->id,
            'type' => 'out',
            'transaction_date' => '2026-03-11',
            'amount' => 200,
            'description' => 'Belanja backdate 11 Maret',
            'created_by' => $this->user->id,
        ]);

        $march10->refresh();
        $march11->refresh();
        $march12->refresh();
        $this->cashAccount->refresh();

        $this->assertSame(1000.0, (float) $march10->balance_before);
        $this->assertSame(900.0, (float) $march10->balance_after);

        $this->assertSame(900.0, (float) $march11->balance_before);
        $this->assertSame(700.0, (float) $march11->balance_after);

        $this->assertSame(700.0, (float) $march12->balance_before);
        $this->assertSame(1200.0, (float) $march12->balance_after);

        $this->assertSame(1200.0, (float) $this->cashAccount->current_balance);
    }

    public function test_backdated_bulk_transaction_recalculates_future_balances(): void
    {
        $march12 = $this->cashAccountService->recordTransaction([
            'cash_account_id' => $this->cashAccount->id,
            'coa_account_id' => $this->expenseCoa->id,
            'type' => 'out',
            'transaction_date' => '2026-03-12',
            'amount' => 100,
            'description' => 'Belanja 12 Maret',
            'created_by' => $this->user->id,
        ]);

        $march13 = $this->cashAccountService->recordTransaction([
            'cash_account_id' => $this->cashAccount->id,
            'type' => 'in',
            'transaction_date' => '2026-03-13',
            'amount' => 300,
            'description' => 'Isi saldo 13 Maret',
            'created_by' => $this->user->id,
        ]);

        $batchTransactions = $this->cashAccountService->recordTransactionsBulk(
            [
                'cash_account_id' => $this->cashAccount->id,
                'type' => 'out',
                'transaction_date' => '2026-03-11',
                'created_by' => $this->user->id,
            ],
            [
                [
                    'amount' => 100,
                    'description' => 'Batch baris 1',
                    'coa_account_id' => $this->expenseCoa->id,
                ],
                [
                    'amount' => 50,
                    'description' => 'Batch baris 2',
                    'coa_account_id' => $this->expenseCoa->id,
                ],
            ]
        );

        $rows = $this->cashAccount->transactions()
            ->orderBy('transaction_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $this->assertCount(4, $rows);
        $this->assertCount(2, $batchTransactions);

        $this->assertSame(1000.0, (float) $rows[0]->balance_before);
        $this->assertSame(900.0, (float) $rows[0]->balance_after);

        $this->assertSame(900.0, (float) $rows[1]->balance_before);
        $this->assertSame(850.0, (float) $rows[1]->balance_after);

        $march12->refresh();
        $march13->refresh();
        $this->cashAccount->refresh();

        $this->assertSame(850.0, (float) $march12->balance_before);
        $this->assertSame(750.0, (float) $march12->balance_after);

        $this->assertSame(750.0, (float) $march13->balance_before);
        $this->assertSame(1050.0, (float) $march13->balance_after);

        $this->assertSame(1050.0, (float) $this->cashAccount->current_balance);
    }

    public function test_petty_cash_account_can_record_expense_even_if_balance_becomes_negative(): void
    {
        $this->cashAccount->update([
            'usage_type' => 'petty_cash',
            'opening_balance' => 100,
            'current_balance' => 100,
        ]);

        $transaction = $this->cashAccountService->recordTransaction([
            'cash_account_id' => $this->cashAccount->id,
            'coa_account_id' => $this->expenseCoa->id,
            'type' => 'out',
            'transaction_date' => '2026-03-10',
            'amount' => 250,
            'description' => 'Belanja melebihi saldo kas kecil',
            'reference_type' => 'petty_cash_pos',
            'created_by' => $this->user->id,
        ]);

        $transaction->refresh();
        $this->cashAccount->refresh();

        $this->assertSame(100.0, (float) $transaction->balance_before);
        $this->assertSame(-150.0, (float) $transaction->balance_after);
        $this->assertSame(-150.0, (float) $this->cashAccount->current_balance);
    }

    public function test_non_petty_cash_account_still_rejects_negative_balance(): void
    {
        $this->expectExceptionMessage('Saldo tidak mencukupi');

        $this->cashAccountService->recordTransaction([
            'cash_account_id' => $this->cashAccount->id,
            'coa_account_id' => $this->expenseCoa->id,
            'type' => 'out',
            'transaction_date' => '2026-03-10',
            'amount' => 1500,
            'description' => 'Belanja melebihi saldo akun biasa',
            'created_by' => $this->user->id,
        ]);
    }
}
