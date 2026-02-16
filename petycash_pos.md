# Implementasi Petty Cash POS + Kas & Bank (Skema Satu Alur)

## Prinsip Utama
1. `Petty cash` outlet tetap terpisah dari kas sales POS (beda kantong).
2. Untuk transaksi keuangan admin, entry point tetap dari `Kas & Bank > Tambah Transaksi` (bukan alur terpisah).
3. Kasir POS tetap cukup isi form sederhana untuk pengeluaran kecil outlet.

## Alur Input Admin (Kas & Bank)
Input dilakukan di satu halaman: `admin/cash-transactions/create`.

Pada form yang sama user memilih `Kategori Transaksi`:
1. `Transaksi Umum`
2. `Bayar Hutang Purchase`
3. `Pindah Buku`

Tidak perlu pindah ke halaman lain untuk proses bayar hutang atau transfer antar rekening.

### Aturan per kategori
1. `Transaksi Umum`
   - User pilih `Kas Masuk/Kas Keluar`.
   - User isi baris detail (COA, deskripsi, jumlah).
2. `Bayar Hutang Purchase`
   - User pilih purchase outstanding.
   - User isi jumlah bayar dan catatan.
   - Sistem paksa tipe transaksi `Kas Keluar` otomatis.
3. `Pindah Buku`
   - User pilih rekening sumber dan rekening tujuan.
   - User isi jumlah dan deskripsi transfer.
   - Sistem buat mutasi berpasangan (`out` di sumber, `in` di tujuan) otomatis.

## Alur Kasir POS (Petty Cash)
### Navigasi
1. Kasir masuk ke `POS > Petty Cash` (halaman index).
2. Di index, kasir bisa:
   - lihat saldo petty cash outlet saat ini
   - lihat total pengeluaran hari ini dan bulan ini
   - lihat riwayat input petty cash (hanya transaksi hari ini agar ringan)
   - filter kata kunci deskripsi
   - klik `Input Baru` untuk create
   - klik `Edit` pada transaksi untuk koreksi data

### Form input (create/edit) yang disederhanakan
Kasir cukup isi:
1. Tanggal transaksi (default hari ini)
2. Nama penerima
3. Deskripsi
4. Jumlah

Kolom lain diisi sistem otomatis:
1. `cash_account_id` -> akun petty cash outlet kasir
2. `coa_account_id` -> `EXP-OUTLET-LAINNYA` (`Keperluan Outlet Lainnya`)
3. `type` -> `out`
4. `reference_type` -> `petty_cash_pos`

## Setup Master Data
1. Admin membuat akun kas/bank per outlet.
2. Akun yang dipakai kantong petty cash ditandai `usage_type = petty_cash`.
3. Kode akun boleh sama antar outlet; validasi unik memakai kombinasi `outlet_id + code`.
4. COA default petty cash: `EXP-OUTLET-LAINNYA - Keperluan Outlet Lainnya`.

## Nomor Voucher
Nomor voucher mengikuti engine kasbank yang sama:
1. Format: `{AAA}{K|M}{YYMM}{SEQ4}`
2. `AAA` diambil otomatis dari ID akun kas/bank (3 digit)
3. `K` = kas keluar, `M` = kas masuk

Tujuan: minim input manual dan mengurangi human error.

## Dampak Laporan
1. Semua transaksi tetap tercatat di `cash_transactions`.
2. Input petty cash POS muncul di index transaksi kasbank admin.
3. Bisa difilter dari `reference_type` (contoh: `petty_cash_pos`, `purchase`, `bank_transfer`).
