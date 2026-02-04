<?php

namespace Tests\Feature;

use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\CoaAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Outlet;
use App\Models\User;
use App\Services\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpensePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_pay_expense_creates_cash_transaction_and_updates_balance(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $outlet = Outlet::factory()->create();

        $coa = CoaAccount::create([
            'code' => '6-100',
            'name' => 'Biaya Operasional',
            'type' => 'expense',
            'group' => 'BIAYA OPERASIONAL',
            'is_active' => true,
        ]);

        $category = ExpenseCategory::create([
            'name' => 'Listrik',
            'code' => 'LSTRK',
            'parent_id' => null,
            'coa_account_id' => $coa->id,
            'description' => null,
            'is_active' => true,
            'order' => 0,
        ]);

        $cashAccount = CashAccount::create([
            'name' => 'Kas Test',
            'code' => 'KAS-TEST',
            'type' => 'cash',
            'is_active' => true,
            'opening_balance' => 1000000,
            'current_balance' => 1000000,
            'created_by' => $user->id,
        ]);

        $expense = Expense::create([
            'expense_number' => 'EXP-001-20250101-001',
            'outlet_id' => $outlet->id,
            'expense_category_id' => $category->id,
            'expense_date' => now()->toDateString(),
            'amount' => 250000,
            'payment_method' => 'cash',
            'cash_account_id' => null,
            'reference_no' => 'INV-PLN-001',
            'description' => 'Bayar listrik',
            'attachment_path' => null,
            'status' => 'approved',
            'created_by' => $user->id,
            'approved_at' => now(),
            'approved_by' => $user->id,
            'approval_notes' => null,
        ]);

        $service = app(ExpenseService::class);
        $service->payExpense($expense, $cashAccount->id);

        $expense->refresh();
        $this->assertSame('paid', $expense->status);
        $this->assertSame($cashAccount->id, $expense->cash_account_id);

        $transaction = CashTransaction::where('reference_type', 'expense')
            ->where('reference_id', $expense->id)
            ->first();

        $this->assertNotNull($transaction);
        $this->assertSame('out', $transaction->type);
        $this->assertSame($cashAccount->id, $transaction->cash_account_id);
        $this->assertSame($coa->id, $transaction->coa_account_id);
        $this->assertEquals(250000, (float) $transaction->amount);

        $cashAccount->refresh();
        $this->assertEquals(750000, (float) $cashAccount->current_balance);
    }
}

