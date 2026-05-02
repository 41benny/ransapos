# Catatan Export Jurnal Bulanan

Dokumen ini merangkum pekerjaan export jurnal bulanan agar sesi berikutnya bisa lanjut tanpa mengulang konteks.

## Lokasi Menu Dan Endpoint

- Menu report: `Laporan > Penjualan > Export Jurnal Bulanan`.
- Slug katalog report: `hpp-journal-monthly`.
- Halaman filter: `GET /admin/reports/hpp-journal`.
- Export XLSX: `GET /admin/reports/hpp-journal/export?month=YYYY-MM&outlet_ids[]=...`.
- Permission: `reports.sales.export`.
- View filter outlet memakai multi-checklist, hanya outlet dengan mapping jurnal aktif yang bisa dipilih.
- Output export memakai satu file XLSX gabungan.

## Urutan Output

Urutan row dalam file export:

1. `SALES`
2. `HPP`
3. `MUTASI_PERSEDIAAN`
4. `PEMBELIAN`

Semua memakai template kolom:

```text
STATUS, NO_AKUN, _VOUCHER, J_TANGGAL, J_JUMLAH, D, K, J_MUTASI, J_NAMA, J_KET1, KET 2
```

Format angka:

- `NO_AKUN`: number, format `0`.
- `J_JUMLAH`, `D`, `K`: number, format Indonesia `[$-421]#,##0.00`.
- Kolom `D`/`K` kosong ditulis `null`, bukan teks `-`.
- Decimal display memakai koma di Excel melalui format number.

## File Implementasi

- Config mapping: `config/sales_journal.php`.
- Generator sales: `app/Services/SalesJournalExportService.php`.
- Generator HPP: `app/Services/HppJournalExportService.php`.
- Generator mutasi persediaan: `app/Services/InventoryMutationJournalExportService.php`.
- Generator pembelian: `app/Services/PurchaseJournalExportService.php`.
- Controller export: `app/Http/Controllers/Admin/Reports/SalesReportController.php`.
- Katalog report/menu: `app/Http/Controllers/Admin/Reports/CatalogReportController.php`.
- Route: `routes/web.php`.
- View filter: `resources/views/admin/reports/hpp-journal.blade.php`.
- XLSX number formatting: `app/Support/ReportExport.php`.

## Mapping Outlet Penting

Mapping outlet ada di `config/sales_journal.php`.

Catatan khusus:

- Transmart wajib:
  - Cashbank: `1102013` - `BRI TRANSMART`
  - Persediaan: `1117011` - `Persediaan Barang Dagang Transmart`
  - Penjualan: `4101011` - `PENJUALAN TRANSMART`
  - HPP: `5101011` - `Hpp Makanan Transmart`
  - Diskon: `5103011` - `DISC PENJUALAN TRANSMART`
  - Meal: `5505011` - `By Meal & Persediaan Rusak Resto Transmart`
- Pahoman menggantikan mapping yang sebelumnya Lampung Walk/LW:
  - Cashbank: `1102006`
  - Persediaan: `1117006`
  - Penjualan: `4101005`
  - HPP: `5101005`
  - Diskon: `5103005`
  - Meal: `5505005`
- `moka` di mapping outlet berarti Mall Kartini.
- `MORESTO CENTRAL PLAZA` / `OUT02` harus masuk CP, bukan CTR.
  - Fix sudah ditambahkan dengan match: `central plaza`, `moresto central plaza`, `out02`.
  - Sebelumnya CP sempat salah masuk CTR karena kata `central`.

## Jurnal Sales

Service: `SalesJournalExportService`.

Filter:

- `sales.status = completed`.
- Periode berdasarkan `sales.sale_date`.
- Agregasi per outlet bulanan.

Aturan:

- Kredit penjualan gross ke akun penjualan outlet.
- Pajak tetap ke akun penjualan outlet, dibuat row terpisah jika ada.
- Service charge tetap ke akun penjualan outlet, dibuat row terpisah jika ada.
- Diskon promo non-meal debit ke akun diskon outlet.
- Promo meal karyawan debit ke akun meal outlet.
- Pembulatan memakai akun `7000007`.
- Pembayaran debit:
  - `CASH`, `DEPOSIT`, `TRANSFER_BANK`, `DEBIT`, `CREDIT`, `TRANSFER` fallback ke cashbank outlet.
  - `CREDIT_CARD`, `DEBIT_CARD`, `GO_FOOD`, `GO_PAY`, `GRAB_FOOD`, `QRIS` ke `1102015 Gofood`.
  - `OVO` ke `1102016 Grab`.
  - `SHOPEE_FOOD`, `SHOPEE_PAY` ke `1102017 Shopee`.

Voucher:

- `SAL` + alias outlet + `MMYY`.
- Contoh Transmart April 2026: `SALTRM0426`.

## Jurnal HPP

Service: `HppJournalExportService`.

Filter:

- `sales.status = completed`.
- Periode berdasarkan `sales.sale_date`.
- Nilai HPP dari `SUM(sale_items.cogs)`.
- Skip jika total HPP `<= 0`.

Aturan:

- Debit akun HPP outlet `5101xxx`.
- Kredit akun persediaan outlet `1117xxx`.

Voucher:

- `HPP` + alias outlet + `MMYY`.
- Contoh Transmart April 2026: `HPPTRM0426`.

## Jurnal Mutasi Persediaan

Service: `InventoryMutationJournalExportService`.

Filter:

- Source `stock_mutations`.
- `reference_type = stock_transfer`.
- Periode berdasarkan `stock_mutations.mutation_date`.
- Hanya `mutation_type in transfer_out, adjustment`.
- `transfer_in` sengaja tidak dijurnal terpisah agar tidak double, karena bisnis menyatakan transfer stok hanya sekali input dan biasanya langsung received.
- Nominal `SUM(ABS(stock_mutations.total_cost))`.
- Skip nominal `<= 0`.

Aturan final:

- Transfer gudang/outlet asal ke outlet tujuan dijurnal langsung:
  - Debit persediaan outlet tujuan.
  - Kredit persediaan outlet asal.
- Tidak memakai akun transit `1117999` karena user menolak logika persediaan dalam perjalanan untuk kondisi sistem saat ini.
- Adjustment positif terkait transfer:
  - Debit persediaan outlet asal.
  - Kredit persediaan outlet tujuan.
- Adjustment negatif terkait transfer:
  - Debit persediaan outlet tujuan.
  - Kredit persediaan outlet asal.

Voucher:

- `MUT` + alias outlet asal + alias outlet tujuan + `MMYY`.
- Contoh Central ke Transmart April 2026: `MUTCTRTRM0426`.

## Jurnal Pembelian

Service: `PurchaseJournalExportService`.

Filter:

- Source `purchases`.
- `purchases.status = received`.
- Periode berdasarkan `purchases.purchase_date`.
- Nominal `SUM(purchases.total_amount)`.
- Agregasi per outlet dan supplier.
- Skip nominal `<= 0`.

Aturan:

- Debit akun persediaan outlet `1117xxx`.
- Kredit akun hutang supplier `2102xxx`.
- Supplier tidak ada mapping masuk default `2102020 Hutang Usaha Lain-lain`.

Voucher:

- `PUR` + alias outlet + `MMYY`.
- Contoh Transmart April 2026: `PURTRM0426`.

Mapping hutang supplier yang sudah dimasukkan:

- `2102001` Hutang Usaha Amir Sayur
- `2102002` Hutang Usaha Anugerah Jaya Sentosa
- `2102003` Hutang Usaha Anugerah Jaya Tjemerlang
- `2102004` Hutang Usaha Aroma
- `2102005` Hutang Usaha Ayam Jangkung
- `2102006` Hutang Usaha Belanja
- `2102007` Hutang Usaha Cedea
- `2102008` Hutang Usaha Central Kitchen
- `2102009` Hutang Usaha Gemilang Kencana Makmur
- `2102010` Hutang Usaha Gudang
- `2102011` Hutang Usaha Indoyasa
- `2102012` Hutang Usaha Lokatara
- `2102013` Hutang Usaha Mie Aming
- `2102014` Hutang Usaha Mie Ayam
- `2102015` Hutang Usaha Nakama Packaging
- `2102016` Hutang Usaha Nestle
- `2102017` Hutang Usaha Prima Frozen
- `2102018` Hutang Usaha Pt Kopi Nusantara
- `2102019` Hutang Usaha Pt. Elpinas
- `2102020` Hutang Usaha Lain-lain

Audit April 2026 menunjukkan supplier berikut masih jatuh ke `2102020` karena belum ada nomor akun khusus:

- RETINA
- SABLON LAMPUNG
- SHOPEE
- WATERINDEX TIRTA LESTARI

## Validasi Yang Sudah Dilakukan

Unit test:

- `php artisan test --filter=SalesJournalExportServiceTest`
- `php artisan test --filter=HppJournalExportServiceTest`
- `php artisan test --filter=InventoryMutationJournalExportServiceTest`
- `php artisan test --filter=PurchaseJournalExportServiceTest`

Syntax check:

- `php -l config/sales_journal.php`
- `php -l app/Services/SalesJournalExportService.php`
- `php -l app/Services/HppJournalExportService.php`
- `php -l app/Services/InventoryMutationJournalExportService.php`
- `php -l app/Services/PurchaseJournalExportService.php`
- `php -l app/Http/Controllers/Admin/Reports/SalesReportController.php`

Cache Laravel sudah dibersihkan:

```bash
php artisan optimize:clear
```

## Audit Kasus Transmart

User sempat melihat screenshot dengan voucher `SALCTR0426`, akun `4101010`, gross `17.902.000`, cash `2.951.000`, QRIS `12.217.000`, debit card `2.659.000`.

Hasil audit DB:

- Angka tersebut adalah `OUT02 - MORESTO CENTRAL PLAZA`, bukan `OUT010 - MORESTO TRANSMART`.
- Transmart April 2026 actual dari service:
  - Sales: `SALTRM0426`, akun penjualan `4101011`.
  - HPP: debit `5101011`, kredit `1117011`.
  - Mutasi transfer ke Transmart: debit `1117011`, kredit gudang/central `1117001`.

Bug yang ditemukan dan diperbaiki:

- `MORESTO CENTRAL PLAZA` sebelumnya match ke CTR karena mengandung kata `central`.
- Fix: tambahkan alias CP `central plaza`, `moresto central plaza`, dan `out02`.

## Catatan Next: Jurnal Kas Bank

Tahap berikutnya adalah jurnal kas/bank. Belum diimplementasikan di commit ini.

Yang perlu dipastikan sebelum implementasi:

- Source transaksi kas/bank yang benar: kemungkinan `cash_transactions`, pembayaran pembelian, pengeluaran, setoran, transfer bank, atau tabel lain.
- Status transaksi yang boleh dijurnal.
- Akun debit/kredit per jenis transaksi.
- Perlakuan pembayaran hutang supplier terhadap jurnal pembelian yang sudah dibuat.
- Perlakuan cash settlement/outlet deposit jika ada.
