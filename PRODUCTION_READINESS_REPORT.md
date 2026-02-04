# Laporan Pemeriksaan Kesiapan Produksi — Per Modul (morest-app-new)

Tanggal: 2026-01-17  
Lokasi project: `c:\laragon\www\morest-app-new`

## Ringkasan Eksekutif
Project ini **sudah punya banyak modul POS/backoffice** (penjualan, shift kasir, stok, purchase, transfer stok, BOM, kas/bank, COA, expense, customer/CRM, report). Namun untuk dinyatakan **layak produksi operasional harian**, masih ada gap penting di **hardening produksi (env/infrastruktur), integritas proses bisnis (refund/cancel/BOM, penomoran transaksi), serta stabilitas ops**.

Status keseluruhan: **BELUM READY (butuh hardening + perbaikan modul kritikal)**.

Catatan update (2026-01-17):
- Alur **expense dibayar → cash transaction** sudah diperbaiki & diuji.
- Pembatalan penjualan kini mengembalikan stok komponen BOM.
- Penentuan cash/non-cash berbasis code payment method (tidak lagi asumsi ID=1).
- Penomoran invoice/purchase/transfer/cash-session/cash-transaction memakai DB lock untuk hindari tabrakan.
- Default timezone diset ke `Asia/Jakarta`.

## Perubahan Terbaru (Log Pekerjaan)
1) **Expense → Kas/Bank konsisten**
   - `app/Services/ExpenseService.php`: pembayaran expense sekarang lewat `CashAccountService::recordTransaction()` (type `out`, ada nomor transaksi, balance_before/after, COA), validasi wajib COA per kategori, dan pengecekan cash account.
   - Test baru `tests/Feature/ExpensePaymentTest.php` memastikan saldo kas berkurang dan transaksi tercatat.
2) **Refund/Cancel penjualan dengan BOM**
   - `app/Services/SaleService.php`: cancel sale mengembalikan stok komponen BOM via `StockService::restoreSaleStock()` dan mencatat mutasi `sale_cancellation`.
   - `app/Services/StockService.php`: fungsi `restoreSaleStock()` untuk rollback stok + cost.
   - Test baru `tests/Feature/SaleCancellationBomTest.php` memastikan stok komponen kembali.
3) **Penomoran aman paralel**
   - Invoice, purchase, cash session, cash transaction, dan stock transfer kini memakai `lockForUpdate()` sebelum generate nomor (hindari bentrok di multi-kasir/outlet).
4) **Cash vs non-cash tidak hardcoded**
   - Perhitungan cash/non-cash di Sales Report memakai `payment_methods.code` (cash = `CASH`), bukan asumsi ID=1.
   - Cash session update memakai code payment method.
5) **Konfigurasi & operasi**
   - Timezone default di `config/app.php` menjadi `Asia/Jakarta`.
   - Tambah `.gitignore` untuk `storage/framework/*` agar file sesi/cache tidak ter-commit.
6) **Status pengujian**
   - `php artisan test` **lulus** (19 tests, 53 assertions). Ada warning doc-comment metadata (deprecated PHPUnit 12), tidak memblokir.

## Metode
Pemeriksaan ini berbasis **static review**: routes, controllers/services, models, migrations, konfigurasi, tests, dan aset UI. Tidak termasuk uji beban, uji perangkat (printer/EDC), atau audit dependency via CVE.

---

## Modul 1 — Auth & Role (RBAC)
**Scope**: login/logout, pembatasan akses berdasarkan role (admin/manager/kasir/kitchen).

**Implementasi ditemukan**
- Login custom: `app\Http\Controllers\Auth\AuthController.php`.
- Middleware role: `app\Http\Middleware\CheckRole.php` dan alias `role` di `bootstrap\app.php`.
- Struktur role via tabel `roles` dan relasi ke `users` (migration `2025_11_07_012854_create_roles_table.php`, `2025_11_07_012917_add_role_and_outlet_to_users_table.php`).

**Kesiapan produksi**
- **Cukup untuk MVP** (akses admin vs kasir vs kitchen sudah dipisah).

**Risiko / gap**
- `User::hasRole()` mengakses `$this->role->name`; bila user belum punya role, bisa error/500.
- Tidak ada “fine-grained permission” (mis. kasir boleh jual tapi tidak boleh void/refund tanpa approval).

**Rekomendasi**
- Pastikan semua user selalu memiliki `role_id` valid (DB constraint + validasi).
- Tambah permission level/action-based (minimal untuk refund/void/discount).

---

## Modul 2 — Master Data (Outlet, Produk, Kategori, Supplier, Payment Method)
**Implementasi ditemukan**
- Outlet: `app\Models\Outlet.php`, daftar di admin: `app\Http\Controllers\Admin\OutletController.php`.
- Produk & kategori: `app\Models\Product.php`, `app\Models\ProductCategory.php`, CRUD produk: `app\Http\Controllers\Admin\ProductController.php`.
- Supplier: `app\Models\Supplier.php`, list: `app\Http\Controllers\Admin\SupplierController.php` (baru index).
- Payment Method: `app\Models\PaymentMethod.php` + migration `2025_11_07_012926_create_payment_methods_table.php`.

**Kesiapan produksi**
- **MVP-ready** untuk operasional dasar (produk/kategori/outlet ada).

**Risiko / gap**
- Banyak logic mengasumsikan **“Cash = payment_method_id 1”** (mis. laporan/shift). Ini rapuh bila data tidak konsisten.
- Supplier/outlet masih minim CRUD (beberapa controller hanya index).

**Rekomendasi**
- Ganti asumsi ID dengan `code` (mis. `CASH`) dan buat seeder yang memastikan konsisten.
- Lengkapi CRUD untuk outlet/supplier + validasi data (unique code, status aktif, dll).

---

## Modul 3 — POS Kasir (Shift, Penjualan, Pembayaran)
**Implementasi ditemukan**
- Shift/cash session: `app\Models\CashSession.php`, `app\Services\CashSessionService.php`, controller POS: `app\Http\Controllers\POS\CashSessionController.php`.
- Penjualan: `app\Services\SaleService.php`, controller POS: `app\Http\Controllers\POS\SaleController.php`, model: `app\Models\Sale.php`, `app\Models\SaleItem.php`, `app\Models\Payment.php`.
- UI POS: `resources\views\pos\...` (sales create, dashboard, sessions open/close).

**Kesiapan produksi**
- **MVP-ready** untuk transaksi dasar dengan 1 payment method per sale.

**Perbaikan yang sudah dilakukan**
- Penomoran invoice memakai locking (`lockForUpdate`) untuk mencegah tabrakan.
- Cancel/refund mengembalikan stok komponen BOM (mutasi `sale_cancellation`), bukan hanya stok produk jadi.
- Cash vs non-cash dihitung dari `payment_methods.code` (cash = code `CASH`), tidak lagi hardcoded ID.

**Risiko / gap tersisa**
- Belum ada multi-payment (split bill), rounding, pajak configurable, dan mekanisme “kembalian” (cash change).
- Refund belum menyesuaikan pencatatan kas/COA (hanya stok & status sale).

**Rekomendasi**
- Tambah flow refund yang juga membalik payment/cash session dan membuat mutasi kas/COA.
- Tambah fitur POS yang umum: change, rounding, tax config, split payment, approval untuk void/refund.

---

## Modul 4 — Kitchen Display
**Implementasi ditemukan**
- Kitchen list/print/status: `app\Http\Controllers\POS\KitchenController.php`.
- Status kitchen di sale (`kitchen_status`) via migration `2025_11_21_071709_add_kitchen_status_to_sales_table.php`.

**Kesiapan produksi**
- **MVP-ready** (basic status: `new / in_progress / done`).

**Risiko / gap**
- Belum ada printer integration real (ESC/POS), hanya view print.
- Belum ada pembatasan data (limit 50) tanpa pagination/archiving; untuk operasional padat perlu strategi.

**Rekomendasi**
- Tambah filter & paging, dan SOP “closing kitchen queue”.
- Kalau butuh printing fisik: tambahkan integrasi printer + retry queue.

---

## Modul 5 — Inventory & Stock (Stok, Mutasi, Adjustment, Kartu Stok)
**Implementasi ditemukan**
- Stok per outlet: `stocks` + unique `(product_id, outlet_id)`.
- Mutasi stok: `stock_mutations` (`in/out/adjustment/transfer_in/transfer_out`) + cost fields.
- Admin UI: `app\Http\Controllers\Admin\StockController.php` (overview, mutations, adjustment, stock card).
- Guard negative stock via `ALLOW_NEGATIVE_STOCK` (`config\app.php`).

**Kesiapan produksi**
- **MVP-ready**, dengan catatan costing masih sederhana.

**Risiko / gap**
- Costing masih memakai `purchase_price` di product sebagai unit cost; belum FIFO/average cost per batch.
- Mutasi `out` menyimpan quantity negatif (sesuai comment), pastikan semua report menghitung konsisten.

**Rekomendasi**
- Tentukan metode costing yang dibutuhkan (average/FIFO) sebelum produksi skala besar.
- Tambah audit: siapa adjust, alasan, approval untuk adjustment besar.

---

## Modul 6 — Purchase (Pembelian, Receiving, Pembayaran Purchase)
**Implementasi ditemukan**
- Purchase header/items + status (draft/received/cancelled) dan payment status.
- Service: `app\Services\PurchaseService.php`.
- Admin controller: `app\Http\Controllers\Admin\PurchaseController.php` termasuk receive & payment form.

**Kesiapan produksi**
- **Cukup untuk alur dasar** (buat PO, receive, catat pembayaran).

**Risiko / gap**
- Penomoran PO juga berisiko collision saat paralel (mirip invoice).
- Beberapa tempat masih ada fallback `auth()->id() ?? 1` (TODO).

**Rekomendasi**
- Perkuat numbering + hilangkan fallback user id.
- Tambah audit dan approval flow bila dibutuhkan (cancel PO, edit PO setelah dibuat).

---

## Modul 7 — Stock Transfer (Antar Outlet)
**Implementasi ditemukan**
- Transfer status (pending → in_transit → received / cancelled).
- Service: `app\Services\StockTransferService.php` + admin UI `app\Http\Controllers\Admin\StockTransferController.php`.

**Kesiapan produksi**
- **MVP-ready** untuk perpindahan sederhana.

**Risiko / gap**
- Penanganan selisih saat receive ada pencatatan mutasi, tapi belum jelas SOP rekonsiliasi (shortage/damage) dan siapa yang approve.

**Rekomendasi**
- Tambah approval/notes wajib untuk selisih + laporan selisih transfer.

---

## Modul 8 — BOM (Made-to-Order / Resep)
**Implementasi ditemukan**
- Tabel: `bom_headers`, `bom_details`.
- Model: `app\Models\BomHeader.php`, `app\Models\BomDetail.php`, relasi di `Product::bomHeader()`.
- Logic konsumsi komponen saat sale: `app\Services\SaleService.php`.
- Admin UI + API-like JSON response: `app\Http\Controllers\Admin\BomController.php`.
- Ada test BOM: `tests\Feature\BomConsumptionTest.php`.

**Kesiapan produksi**
- **MVP-ready** untuk konsumsi bahan dasar.

**Risiko / gap**
- Refund/cancel harus benar-benar membalik konsumsi komponen (lihat Modul 3).
- Validasi BOM belum mencegah circular reference yang lebih kompleks (bila nanti ada nested BOM).

**Rekomendasi**
- Implement reverse-stock yang konsisten berbasis `stock_mutations` per sale.
- Tambah validasi BOM (tidak boleh loop, tidak boleh komponen non-raw bila belum didukung).

---

## Modul 9 — Kas/Bank, COA, Expense, Profit & Loss
**Implementasi ditemukan**
- Cash account & transaksi kas: `app\Models\CashAccount.php`, `app\Models\CashTransaction.php`, service `app\Services\CashAccountService.php`.
- COA: `app\Models\CoaAccount.php` + UI: `app\Http\Controllers\Admin\CoaAccountController.php`.
- Expense & kategori: migration `2025_11_20_000002_create_expenses_table.php`, controller `app\Http\Controllers\Admin\ExpenseController.php`, service `app\Services\ExpenseService.php`.
- P&L: `app\Services\ProfitLossReportService.php` + `app\Http\Controllers\Admin\Reports\ProfitLossReportController.php`.

**Kesiapan produksi**
- **MVP-ready**, tapi masih butuh hardening & audit trail yang lebih rapi.

**Perbaikan yang sudah dilakukan**
- `ExpenseService` mencatat pembayaran expense melalui `CashAccountService::recordTransaction()` (type `out`, ada `transaction_number`, `balance_before/after`, `coa_account_id`).
- Ditambah test: `tests\\Feature\\ExpensePaymentTest.php`.

**Rekomendasi**
- Tambah aturan audit: larang delete paid expense (atau buat reversal transaction resmi).
- Pastikan setiap `ExpenseCategory` punya `coa_account_id` agar P&L konsisten.

---

## Modul 10 — Customer/CRM & Loyalty
**Implementasi ditemukan**
- CRUD customer + tier/points: `app\Models\Customer.php`, `app\Http\Controllers\Admin\CustomerController.php`.
- Penjualan bisa menambah poin (1% dari total) di `SaleService`.

**Kesiapan produksi**
- **MVP-ready** untuk loyalty sederhana.

**Risiko / gap**
- Belum ada mekanisme redeem points di alur pembayaran POS (baru fitur admin/manual).
- Aturan points/tier hardcoded; belum configurable per outlet/promo.

**Rekomendasi**
- Tambah integrasi redeem points di POS (dengan audit).
- Pindahkan rule points/tier ke config/tabel.

---

## Modul 11 — Reporting (Sales, Shift, P&L)
**Implementasi ditemukan**
- Sales report (summary + per produk): `app\Http\Controllers\Admin\Reports\SalesReportController.php`.
- Shift report: `app\Http\Controllers\Admin\Reports\ShiftReportController.php`.
- P&L: `ProfitLossReportService` (revenue dari sales, COGS dari stock mutations, expenses dari cash transactions + COA).

**Kesiapan produksi**
- **MVP-ready**, dengan catatan kualitas data tergantung modul upstream (expense/cash transaction & costing).

**Risiko / gap**
- Jika expense/cash transaction masih belum di-audit, P&L bisa salah.

**Rekomendasi**
- Pastikan payment method “CASH” hadir di master (code `CASH`) untuk konsistensi laporan.
- Tambah rekonsiliasi data (mis. cek `sale.total_amount == sum(payments.amount)`).

---

## Modul 12 — Infrastruktur Produksi (Env, DB, Queue, Log, Deploy, UI Build)
**Temuan kunci**
- `.env` masih `APP_ENV=local` dan `APP_DEBUG=true` (harus off di produksi).
- `DB_CONNECTION` tidak diset → default `sqlite` (tidak direkomendasikan untuk POS multi-user).
- `QUEUE_CONNECTION` default `database` → produksi butuh worker selalu hidup.
- Timezone sudah diubah ke `Asia/Jakarta` (config).
- Layout memakai `@vite(...)` **dan** `https://cdn.tailwindcss.com` (`resources\views\layouts\app.blade.php`) → sebaiknya pilih satu (hindari double).
- Ada workflow CI untuk menjalankan test (`.github/workflows/tests.yml`) dan ada feature tests (termasuk BOM, COGS, negative stock, purchase payment, expense payment, cancel sale BOM).

**Rekomendasi minimum sebelum produksi**
- Set `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=info|warning`, dan pakai HTTPS cookie (`SESSION_SECURE_COOKIE=true`) di environment produksi.
- Migrasi ke MySQL/Postgres + strategi backup/restore dan uji restore.
- Siapkan proses worker queue (Supervisor/systemd/Windows service) + alerting untuk failed_jobs.
- Rapikan pipeline build frontend (hindari tailwind CDN; build via Vite).

---

## Catatan Tambahan (Data Dummy / Seeder)
- Ada `database\seeders\DatabaseSeeder.php` yang membuat roles, outlet, user demo, payment methods, cash accounts, COA, dll.
- Seeder belum idempotent (menggunakan `create()` langsung) → jika dijalankan ulang bisa duplikasi / error unique.
- Password demo masih `password` → **tidak boleh** dipakai untuk produksi.

---

## Kesimpulan
Secara modul, POS ini sudah cukup lengkap untuk tahap MVP. Untuk bisa “aman produksi”, prioritasnya:
1) bereskan **modul Expense ↔ CashTransaction** (kritikal),  
2) hardening env & DB produksi,  
3) benahi alur **refund/cancel + audit** (terutama saat BOM aktif),  
4) hilangkan asumsi ID hardcoded (payment cash), dan  
5) tambahkan monitoring/backup/worker ops.
