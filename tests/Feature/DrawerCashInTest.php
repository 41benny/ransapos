<?php

namespace Tests\Feature;

use App\Models\CashAccount;
use App\Models\CashSession;
use App\Models\CashSessionExpense;
use App\Models\CashTransaction;
use App\Models\CoaAccount;
use App\Models\Outlet;
use App\Models\Setting;
use App\Models\User;
use App\Services\CashSessionService;
use App\Services\PosDrawerExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kas masuk laci — uang tambahan yang masuk ke laci di luar penjualan.
 *
 * Yang diuji bukan sekadar "angkanya tersimpan", tapi bahwa setiap rupiah yang
 * masuk ke laci punya asal yang sah. Kebocoran yang pernah terjadi di produksi
 * bentuknya persis begitu: uang bertambah di satu akun tanpa berkurang di mana
 * pun, dan saldo ayat silang menumpuk tanpa ada yang bisa menjelaskannya.
 */
class DrawerCashInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'pos_drawer_expense_enabled'], ['value' => '1']);
    }

    private function makeShift(float $opening = 210000): array
    {
        $outlet = Outlet::factory()->create();
        $user = User::factory()->create(['outlet_id' => $outlet->id]);

        $session = app(CashSessionService::class)->openSession([
            'outlet_id' => $outlet->id,
            'opening_balance' => $opening,
        ], $user);

        return [$outlet, $user, $session->fresh()];
    }

    public function test_kas_masuk_dari_kas_besar_menaikkan_laci_dan_menurunkan_kas_besar(): void
    {
        [$outlet, $user, $session] = $this->makeShift();

        $service = app(PosDrawerExpenseService::class);
        $kasBesar = $service->ensureMainCashAccount($outlet->id);

        $service->recordCashIn($session, [
            'amount' => 500000,
            'description' => 'Tambah modal pagi untuk kembalian',
            'recipient_name' => 'Pak Beny',
            'source_cash_account_id' => $kasBesar->id,
        ], $user);

        $session->refresh();

        $this->assertEquals(500000, (float) $session->total_cash_in);
        $this->assertEquals(710000, $session->calculateExpectedBalance());
        $this->assertEquals(710000, $service->currentDrawerOnHand($session));

        // Belum diposting sampai closing — sama seperti pengeluaran kios.
        $this->assertSame(0, CashTransaction::where('reference_type', 'pos_drawer_cash_in')->count());

        app(CashSessionService::class)->closeSession($session, 710000, null, null, null, 210000);

        $drawer = $service->resolveDrawerAccount($outlet->id);
        $kasBesar->refresh();

        // Laci tersisa persis modal disisakan; sisanya disetor kembali.
        $this->assertEquals(210000, (float) $drawer->fresh()->current_balance);

        // Kas besar: -210.000 modal awal, -500.000 kas masuk, +500.000 setoran
        // balik saat closing. Bersihnya -210.000, yaitu uang yang memang masih
        // ada di laci sebagai modal shift berikutnya.
        $this->assertEquals(-210000, (float) $kasBesar->current_balance);
    }

    public function test_kas_masuk_dari_uang_pribadi_pemilik_dibukukan_ke_modal_disetor(): void
    {
        [$outlet, $user, $session] = $this->makeShift();

        $service = app(PosDrawerExpenseService::class);
        $kasBesar = $service->ensureMainCashAccount($outlet->id);
        $kasBesarBefore = (float) $kasBesar->current_balance;

        $service->recordCashIn($session, [
            'amount' => 300000,
            'description' => 'Modal tambahan dari owner',
            'recipient_name' => 'Pak Beny',
            'from_owner' => true,
        ], $user);

        app(CashSessionService::class)->closeSession($session, 510000, null, null, null, 510000);

        $modalCoaId = CoaAccount::where('code', '3-100')->value('id');
        $this->assertNotNull($modalCoaId, 'COA Modal Disetor Pemilik harus ada setelah migrasi.');

        $jurnal = CashTransaction::where('reference_type', 'pos_drawer_cash_in')->get();

        // Satu baris saja: uang pemilik tidak keluar dari akun kas mana pun,
        // jadi memaksakan sisi lawan di kas besar justru membuatnya minus palsu.
        $this->assertCount(1, $jurnal);
        $this->assertSame('in', $jurnal->first()->type);
        $this->assertEquals($modalCoaId, $jurnal->first()->coa_account_id);

        // Seluruh uang fisik disisakan sebagai modal besok (setoran nol), jadi
        // kas besar harus persis sama seperti sebelum kas masuk dicatat.
        $kasBesar->refresh();
        $this->assertEquals(
            $kasBesarBefore,
            (float) $kasBesar->current_balance,
            'Kas besar tidak boleh ikut terpotong oleh uang pribadi pemilik.'
        );
    }

    public function test_ayat_silang_kembali_nol_setelah_kas_masuk_dari_akun_kas(): void
    {
        [$outlet, $user, $session] = $this->makeShift();

        $service = app(PosDrawerExpenseService::class);
        $kasBesar = $service->ensureMainCashAccount($outlet->id);

        $service->recordCashIn($session, [
            'amount' => 250000,
            'description' => 'Ambil dari kas besar',
            'recipient_name' => 'Supervisor',
            'source_cash_account_id' => $kasBesar->id,
        ], $user);

        app(CashSessionService::class)->closeSession($session, 460000, null, null, null, 210000);

        $clearingCoaId = CoaAccount::where('code', '1-190')->value('id');

        $masuk = (float) CashTransaction::where('coa_account_id', $clearingCoaId)->where('type', 'in')->sum('amount');
        $keluar = (float) CashTransaction::where('coa_account_id', $clearingCoaId)->where('type', 'out')->sum('amount');

        $this->assertEquals(
            0.0,
            round($masuk - $keluar, 2),
            'Ayat silang harus berpasangan sempurna; sisa berarti ada uang tanpa asal.'
        );
    }

    public function test_kas_masuk_yang_dibatalkan_tidak_ikut_menaikkan_saldo(): void
    {
        [$outlet, $user, $session] = $this->makeShift();

        $service = app(PosDrawerExpenseService::class);
        $kasBesar = $service->ensureMainCashAccount($outlet->id);

        $cashIn = $service->recordCashIn($session, [
            'amount' => 400000,
            'description' => 'Salah input',
            'recipient_name' => 'Pak Beny',
            'source_cash_account_id' => $kasBesar->id,
        ], $user);

        $service->void($cashIn, $user, 'Salah nominal');

        $session->refresh();

        $this->assertEquals(0, (float) $session->total_cash_in);
        $this->assertEquals(210000, $session->calculateExpectedBalance());
        $this->assertEquals(210000, $service->currentDrawerOnHand($session));

        // Baris aslinya tetap ada sebagai jejak audit.
        $this->assertDatabaseHas('cash_session_expenses', [
            'id' => $cashIn->id,
            'status' => CashSessionExpense::STATUS_VOID,
        ]);
    }

    public function test_kas_masuk_tidak_terhitung_sebagai_pengeluaran(): void
    {
        [$outlet, $user, $session] = $this->makeShift();

        $service = app(PosDrawerExpenseService::class);
        $kasBesar = $service->ensureMainCashAccount($outlet->id);
        $coa = CoaAccount::where('is_pos_selectable', true)->where('type', 'expense')->first();

        $service->recordCashIn($session, [
            'amount' => 500000,
            'description' => 'Modal tambahan',
            'recipient_name' => 'Pak Beny',
            'source_cash_account_id' => $kasBesar->id,
        ], $user);

        $service->record($session, [
            'coa_account_id' => $coa->id,
            'recipient_name' => 'Tukang galon',
            'description' => 'Beli galon',
            'amount' => 50000,
            'payment_source' => CashSessionExpense::SOURCE_DRAWER,
        ], $user);

        $session->refresh();

        $this->assertEquals(500000, (float) $session->total_cash_in);
        $this->assertEquals(50000, (float) $session->total_cash_expense);
        $this->assertEquals(0, (float) $session->total_noncash_expense);
        $this->assertEquals(660000, $session->calculateExpectedBalance());

        // Layar closing dan laporan tidak boleh mencampur keduanya.
        $this->assertCount(1, $service->expensesFor($session));
        $this->assertCount(1, $service->cashInsFor($session));
        $this->assertCount(2, $service->ledgerFor($session));
    }

    public function test_kas_masuk_tanpa_asal_dana_ditolak(): void
    {
        [$outlet, $user, $session] = $this->makeShift();

        $this->expectExceptionMessage('Pilih rekening sumber dananya');

        app(PosDrawerExpenseService::class)->recordCashIn($session, [
            'amount' => 100000,
            'description' => 'Entah dari mana',
            'recipient_name' => 'Tidak jelas',
        ], $user);
    }

    public function test_kas_masuk_tidak_bisa_menunjuk_rekening_outlet_lain(): void
    {
        [$outlet, $user, $session] = $this->makeShift();

        $outletLain = Outlet::factory()->create();
        $kasOutletLain = app(PosDrawerExpenseService::class)->ensureMainCashAccount($outletLain->id);

        $this->expectExceptionMessage('Rekening sumber tidak valid untuk outlet ini.');

        app(PosDrawerExpenseService::class)->recordCashIn($session, [
            'amount' => 100000,
            'description' => 'Curi dari outlet sebelah',
            'recipient_name' => 'X',
            'source_cash_account_id' => $kasOutletLain->id,
        ], $user);
    }

    public function test_kunci_idempotensi_mencegah_kas_masuk_kembar(): void
    {
        [$outlet, $user, $session] = $this->makeShift();

        $service = app(PosDrawerExpenseService::class);
        $kasBesar = $service->ensureMainCashAccount($outlet->id);

        $payload = [
            'amount' => 500000,
            'description' => 'Tambah modal pagi',
            'recipient_name' => 'Pak Beny',
            'source_cash_account_id' => $kasBesar->id,
            'idempotency_key' => 'kunci-yang-sama',
        ];

        $pertama = $service->recordCashIn($session, $payload, $user);
        $kedua = $service->recordCashIn($session, $payload, $user);

        $this->assertSame($pertama->id, $kedua->id);
        $this->assertSame(1, CashSessionExpense::where('cash_session_id', $session->id)->count());

        $session->refresh();
        $this->assertEquals(500000, (float) $session->total_cash_in);
    }

    public function test_laci_yang_kosong_tetap_bisa_dipakai_belanja_setelah_kas_masuk(): void
    {
        [$outlet, $user, $session] = $this->makeShift(opening: 0);

        $service = app(PosDrawerExpenseService::class);
        $kasBesar = $service->ensureMainCashAccount($outlet->id);
        $coa = CoaAccount::where('is_pos_selectable', true)->where('type', 'expense')->first();

        $service->recordCashIn($session, [
            'amount' => 200000,
            'description' => 'Modal awal tambahan',
            'recipient_name' => 'Pak Beny',
            'source_cash_account_id' => $kasBesar->id,
        ], $user);

        $session->refresh();

        // Tanpa suku kas masuk di rumus sisa laci, pengeluaran ini akan ditolak
        // dengan alasan "uang di laci tidak cukup" padahal uangnya jelas ada.
        $expense = $service->record($session, [
            'coa_account_id' => $coa->id,
            'recipient_name' => 'Tukang galon',
            'description' => 'Beli galon',
            'amount' => 150000,
            'payment_source' => CashSessionExpense::SOURCE_DRAWER,
        ], $user);

        $this->assertEquals(150000, (float) $expense->amount);
        $this->assertEquals(50000, $service->currentDrawerOnHand($session->fresh()));
    }
}
