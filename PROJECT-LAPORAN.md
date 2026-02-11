# Project Laporan - Master Tracker

Update terakhir: 11 Februari 2026
Owner: Tim Morest + Codex

## Tujuan
Menyelesaikan modul laporan bisnis yang rapi, konsisten, dan aman dari salah hitung, dimulai dari tab **Ikhtisar Bisnis**.

## Scope Fase Sekarang
1. Menyediakan halaman katalog laporan per item (bukan placeholder tunggal).
2. Mengaktifkan laporan prioritas pada tab Ikhtisar Bisnis.
3. Menyediakan checklist validasi agar angka laporan tidak menyesatkan.

## Status Implementasi Saat Ini

### A. Infrastruktur Katalog Laporan
- [x] Route katalog utama: `admin.reports.index`
- [x] Route detail per item laporan: `admin.reports.catalog.show`
- [x] Halaman katalog dengan tab kategori laporan
- [x] Halaman detail dinamis berdasarkan slug laporan

File utama:
- `routes/web.php`
- `app/Http/Controllers/Admin/Reports/CatalogReportController.php`
- `resources/views/admin/reports/index.blade.php`
- `resources/views/admin/reports/catalog-show.blade.php`

### B. Ikhtisar Bisnis (sesuai desain referensi)

#### 1) Neraca
- [x] Halaman Neraca final v1
- [x] Struktur Aset / Kewajiban / Ekuitas
- [x] Balance check: Aset vs (Kewajiban + Ekuitas)
- [x] Kontrol rekonsiliasi Kas/Bank

File utama:
- `app/Services/BalanceSheetReportService.php`
- `app/Http/Controllers/Admin/Reports/CatalogReportController.php`
- `resources/views/admin/reports/catalog-show.blade.php`

#### 2) Laba & Rugi
- [x] Versi lama aktif (route existing)
- [ ] Integrasi ke tampilan katalog detail baru (UI seragam)
- [ ] Validasi sinkron metode tanggal (`sale_date` vs `created_at`)

File existing:
- `app/Services/ProfitLossReportService.php`
- `app/Http/Controllers/Admin/Reports/ProfitLossReportController.php`
- `resources/views/admin/reports/profit-loss.blade.php`

#### 3) Kas dan Bank
- [x] Ringkasan saldo kas/bank per akun aktif
- [x] Filter tanggal + outlet

#### 4) Kas dan Bank Detil
- [x] Saldo awal, masuk, keluar, saldo akhir per akun
- [x] Agregat total periode

#### 5) Detil Ledger
- [x] Daftar detail mutasi transaksi
- [x] Menampilkan COA, akun kas/bank, outlet, jenis mutasi
- [x] Ringkasan total masuk/keluar/net

#### 6) Arus Kas
- [x] Ringkasan arus kas per grup COA
- [x] Total masuk/keluar/net

### C. Persiapan Master Akun (COA)
- [x] Tombol generate template akun Neraca
- [x] Idempotent (akun existing tidak diubah)

File utama:
- `app/Http/Controllers/Admin/CoaAccountController.php`
- `resources/views/admin/coa-accounts/index.blade.php`
- `routes/web.php`

## Task List Next (Prioritas)

### Prioritas Tinggi
- [ ] Finalisasi kebijakan posting transaksi ke COA neraca (asset/liability/equity).
- [ ] Samakan logika periode di seluruh laporan (tanggal sumber data konsisten).
- [ ] Standarisasi filter global laporan (tanggal, outlet, export).

### Prioritas Menengah
- [ ] Integrasi `Laba & Rugi` ke halaman detail katalog agar satu UI.
- [ ] Tambah export CSV/Excel untuk laporan Ikhtisar.
- [ ] Tambah pagination untuk `Detil Ledger` (saat ini limit 500 baris).

### Prioritas Lanjutan
- [ ] Implementasi laporan tab Penjualan non-aktif (order, pelanggan, kategori, dst).
- [ ] Implementasi laporan tab Pembelian.
- [ ] Implementasi laporan tab Produk.

## Checklist QA / Review Data

### QA Fungsional
- [ ] Buka `Laporan > Ikhtisar Bisnis`, pastikan semua item bisa diakses.
- [ ] Uji filter tanggal & outlet di setiap laporan aktif.
- [ ] Uji kondisi data kosong (empty state) tetap tampil normal.

### QA Akurasi Angka
- [ ] Cocokkan saldo `Kas dan Bank` dengan saldo akun kas/bank operasional.
- [ ] Cek `Kas dan Bank Detil`: saldo awal + masuk - keluar = saldo akhir.
- [ ] Cek `Neraca`: Aset = Kewajiban + Ekuitas (target selisih 0).
- [ ] Cek `Arus Kas`: total net sesuai mutasi transaksi periode.

### QA Teknis
- [ ] Pastikan tidak ada error di `php artisan view:cache`.
- [ ] Pastikan route laporan terdaftar di `php artisan route:list`.
- [ ] Tambah automated test untuk service laporan utama.

## Catatan Risiko
1. Jika transaksi belum disiplin diposting ke COA yang benar, laporan keuangan bisa bias.
2. Saat ini beberapa laporan masih menggunakan sumber data berbeda (potensi mismatch).
3. Transfer antar rekening tanpa mapping COA neraca dapat mengaburkan komposisi akun.

## Definisi Selesai Fase Ikhtisar
Fase Ikhtisar dianggap selesai jika:
1. Semua 6 laporan Ikhtisar tampil stabil.
2. Rumus rekonsiliasi dasar lulus QA.
3. Tidak ada selisih material pada data uji outlet.
4. Ada SOP input transaksi + mapping COA yang dipatuhi user.
