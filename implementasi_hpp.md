# Implementasi HPP (Moving Average + Period Closing)

## Tujuan
Dokumen ini jadi aturan main penerapan HPP agar:
1. Nilai HPP dan laba transaksi historis tidak berubah sembarangan.
2. Perubahan harga beli bahan baku tetap tercermin ke transaksi berikutnya.
3. Tim operasional (kasir, admin outlet) dan tim akuntansi punya batas proses yang jelas.

## Ringkasan Keputusan
1. Metode biaya persediaan: `Perpetual Moving Average` (rata-rata bergerak).
2. HPP penjualan dicatat sebagai `snapshot` saat transaksi `posted`.
3. Shift closing kasir tetap harian.
4. Tambah `period closing` bulanan untuk lock data akuntansi.
5. Backdate sebelum lock date hanya boleh lewat proses koreksi resmi (otorisasi khusus).
6. Standar rekonsiliasi wajib: perbandingan HPP harus `apple-to-apple` dengan sumber data dan filter yang sama.
7. Target akhir: nilai persediaan di Neraca harus sama dengan nilai persediaan pada laporan mutasi persediaan.

## Definisi Penting
1. `Posting penjualan`: transaksi sale sudah final tersimpan (header, item, HPP item, mutasi stok).
2. `Shift closing`: tutup operasional kasir per shift/hari (kas fisik vs sistem).
3. `Period closing`: penguncian periode akuntansi (mis. Februari 2026) agar laporan periode itu stabil.
4. `Lock date`: tanggal batas terakhir yang sudah dikunci.

## Prinsip Akuntansi yang Dipakai
1. Biaya persediaan dihitung konsisten per metode yang dipilih (moving average).
2. Penjualan historis tidak dihitung ulang dari harga master saat ini.
3. Koreksi periode lampau tidak mengubah data posted lama, tapi lewat adjustment/reversal terkontrol.

## Aturan Main HPP

### 1. Sumber HPP
1. Untuk bahan baku/raw material: HPP pakai `moving average cost` aktif saat transaksi.
2. Untuk menu/bundle dengan BOM: HPP = total `komponen x avg cost komponen` saat transaksi.
3. Untuk service: HPP = 0.

### 2. Kapan avg cost berubah
1. Avg cost berubah saat `barang diterima` (receive purchase), bukan saat PO draft dibuat.
2. Jika harga beli baru berbeda, avg cost baru berlaku ke transaksi setelah receive tersebut.

### 3. Snapshot saat posting sale
1. Saat sale posted, simpan:
   - `sale_items.cogs`
   - `stock_mutations.unit_cost` dan `stock_mutations.total_cost`
2. Nilai ini tidak ikut berubah walau avg cost berubah di hari berikutnya.

### 4. Cancel/void transaksi
1. Cancel sale menggunakan cost referensi transaksi asli (bukan avg cost terbaru).
2. Tujuan: reversal akurat dan tidak mendistorsi laba historis.

### 5. Stock transfer antar outlet
1. Barang keluar dari outlet pengirim dicatat menggunakan `avg cost outlet pengirim` saat transfer.
2. Barang masuk ke outlet penerima menghitung avg cost baru:
   - `avg_baru = ((qty_lama x avg_lama) + (qty_masuk x cost_transfer)) / (qty_lama + qty_masuk)`
3. Transfer tidak menghasilkan laba/rugi — perbedaan avg cost antar outlet dianggap wajar.
4. Snapshot `unit_cost` dan `total_cost` wajib tercatat di `stock_mutations` untuk audit trail.

### 6. Retur pembelian (purchase return)
1. Barang yang diretur mengurangi qty dan menghitung ulang avg cost:
   - `avg_baru = ((qty_lama x avg_lama) - (qty_retur x cost_saat_receive)) / (qty_lama - qty_retur)`
2. Cost yang dipakai untuk retur = cost saat barang awalnya di-receive (snapshot dari purchase receive).
3. Jika qty setelah retur menjadi 0, avg cost di-reset (menunggu receive berikutnya).

### 7. Edge cases
1. **Qty negatif**: Jika stok menjadi negatif (karena kebijakan `ALLOW_NEGATIVE_STOCK=true`), avg cost TIDAK berubah. Cost yang dipakai tetap avg cost terakhir yang valid.
2. **Receive pertama (qty lama = 0)**: avg cost = harga beli neto, tanpa perhitungan rata-rata.
3. **BOM dengan komponen kosong**: Jika salah satu komponen BOM belum punya avg cost, gunakan `purchase_price` terakhir dari master produk sebagai fallback.

## Standar Rekonsiliasi HPP (Apple-to-Apple)

### 1. Definisi perbandingan yang benar
1. Jika tujuan audit adalah `HPP penjualan saja`, maka bandingkan:
   - HPP di laporan laba rugi (komponen penjualan),
   - dengan total `mutasi out` yang `reference_type = sale`.
2. Jangan campur dengan transfer, adjustment, opname, atau pembelian.
3. Gunakan parameter filter yang sama persis:
   - outlet yang sama,
   - rentang tanggal yang sama,
   - basis tanggal yang sama (`mutation_date`).

### 2. Opsional metrik net (jika ingin menampilkan void/cancel)
1. HPP penjualan kotor:
   - `sum(total_cost where mutation_type = out and reference_type = sale)`
2. Reversal void/cancel:
   - `sum(total_cost where mutation_type = in and reference_type = sale_cancellation)`
3. HPP penjualan bersih:
   - `HPP kotor - reversal`

Catatan:
1. Agar tidak menimbulkan salah tafsir, tampilkan label metrik secara eksplisit: `kotor` vs `bersih`.
2. Untuk pembandingan dengan "mutasi out penjualan", gunakan metrik `HPP kotor`.

## Formula Moving Average (Perpetual)
`avg_baru = ((qty_lama x avg_lama) + (qty_masuk x harga_beli_neto)) / (qty_lama + qty_masuk)`

Catatan:
1. `harga_beli_neto` = harga beli setelah diskon item + alokasi biaya terkait pembelian.
2. Jika qty lama nol, avg baru = harga beli neto.

## Contoh Kasus Tanggal 16 vs 17
1. Tanggal 16 Februari 2026:
   - HPP penjualan tercatat Rp500
   - Margin tercatat Rp100
2. Tanggal 17 Februari 2026 ada pembelian baru dengan harga berbeda:
   - Avg cost berubah untuk transaksi setelahnya.
   - HPP transaksi tanggal 16 tetap Rp500 (tidak berubah), selama tidak ada backdate/koreksi ke masa lalu.

## Aturan Period Closing

### 1. Tujuan close period
1. Mengunci laporan periode agar tidak berubah.
2. Mencegah backdate transaksi yang mengganggu laba periode lampau.

### 2. Data yang ikut terkunci
1. Penjualan.
2. Pembelian/receive.
3. Retur/void/cancel.
4. Stok adjustment/opname.
5. Mutasi kas/bank dan jurnal akuntansi.

### 3. Kebijakan lock
1. Setelah close Februari 2026, lock date = `2026-02-29`.
2. User biasa tidak boleh input/edit/delete transaksi dengan tanggal <= lock date.
3. Jika perlu koreksi:
   - pakai menu koreksi khusus,
   - wajib approval role tertentu,
   - sistem simpan audit trail.

## Shift Closing vs Period Closing
1. Shift closing:
   - frekuensi harian/shift,
   - fokus operasional kasir.
2. Period closing:
   - frekuensi bulanan,
   - fokus stabilitas angka akuntansi.
3. Keduanya wajib berjalan bersamaan, karena fungsi berbeda.

## Target Konsistensi Nilai Persediaan (Neraca vs Mutasi Persediaan)

### 1. Prinsip target
1. Nilai persediaan di Neraca per tanggal cut-off harus sama dengan nilai persediaan di laporan mutasi persediaan per tanggal cut-off yang sama.
2. Sumber data nilai harus satu jalur (single source of truth), bukan dua rumus yang berbeda.

### 2. Rumus nilai persediaan akhir
1. Per produk per outlet:
   - `saldo_qty_akhir x cost_akhir`
2. Total persediaan:
   - `sum(nilai persediaan akhir seluruh produk/outlet)`

### 3. Aturan implementasi
1. Akun Persediaan di Neraca harus diturunkan dari ledger persediaan yang sama dengan laporan mutasi persediaan.
2. Jurnal manual langsung ke akun Persediaan dibatasi ketat (hanya adjustment terotorisasi).
3. Semua perubahan stok bernilai harus melewati event yang mencatat kuantitas dan cost (receive, sale, cancel, adjustment, transfer jika bernilai).

### 4. Kontrol rekonsiliasi bulanan
1. Buat laporan rekonsiliasi otomatis:
   - `Nilai Persediaan Neraca`
   - `Nilai Persediaan Mutasi Persediaan`
   - `Selisih`
2. Syarat close period: selisih harus 0 (atau dalam toleransi yang disetujui manajemen).

## Desain Implementasi (Disarankan)

### 1. Biaya per outlet (direkomendasikan)
Karena stok dikelola per outlet, avg cost lebih akurat jika disimpan per outlet:
1. `product_costs` (product_id, outlet_id, avg_cost, last_calculated_at).
2. Fallback ke nilai global hanya jika outlet belum punya histori.

### 2. Field/struktur minimum
1. `sale_items.cogs` tetap dipakai sebagai snapshot HPP.
2. `stock_mutations.unit_cost/total_cost` wajib terisi untuk outflow sale dan reversal.
3. Tambah konfigurasi `settings.lock_date` atau tabel `accounting_periods`.
4. Tambah output metrik HPP terpisah: `hpp_penjualan_kotor`, `hpp_reversal_void`, `hpp_penjualan_bersih` (untuk audit lintas laporan).

### 3. Trigger proses
1. `receive purchase`:
   - update stok,
   - hitung avg cost baru,
   - simpan ke cost ledger.
2. `post sale`:
   - ambil avg cost aktif,
   - hitung cogs per item (untuk BOM: sum komponen x avg cost masing-masing),
   - simpan snapshot ke `sale_items.cogs` dan `stock_mutations.unit_cost/total_cost`.
3. `cancel sale`:
   - reversal berdasarkan snapshot cost transaksi asal.
4. `stock transfer`:
   - outlet pengirim: mutasi out dengan avg cost pengirim,
   - outlet penerima: mutasi in, hitung avg cost baru penerima.
5. `purchase return`:
   - reversal stok dan recalculate avg cost berdasarkan cost saat receive awal.
6. `stock adjustment/opname`:
   - selisih qty dicatat menggunakan avg cost aktif saat adjustment.
   - jurnal penyesuaian persediaan otomatis dibuat.

## SOP Operasional Bulanan
1. Tanggal H+1 awal bulan: rekonsiliasi stok dan kas.
2. Final review laporan penjualan vs HPP.
3. CFO/manager set lock date periode lalu.
4. Setelah lock, koreksi hanya via jurnal penyesuaian + approval.

## Checklist UAT Sebelum Go-Live
1. Penjualan tanggal lama tidak berubah setelah pembelian baru.
2. Backdate transaksi sebelum lock date ditolak.
3. Cancel sale mereverse nilai cost yang sama dengan transaksi awal.
4. Laporan sales vs HPP konsisten dengan mutasi stok.
5. Multi outlet tidak saling mencampur avg cost.
6. HPP laba rugi (penjualan kotor) sama dengan total mutasi `out sale` pada filter yang sama.
7. Nilai persediaan Neraca sama dengan nilai persediaan laporan mutasi persediaan pada tanggal cut-off yang sama.
8. Transfer antar outlet menggunakan cost pengirim dan menghitung avg cost baru di penerima.
9. Retur pembelian menghitung ulang avg cost dengan benar.
10. Stok negatif (jika diizinkan) tidak mendistorsi avg cost.

## Kondisi Sistem Saat Ini (Baseline)
1. Sistem sudah menyimpan `sale_items.cogs`.
2. Sistem sudah mencatat mutasi stok in/out.
3. Sistem sudah mendukung stock transfer antar outlet.
4. Sistem belum otomatis update `purchase_price` sebagai moving average saat receive purchase.
5. Sistem belum punya lock period akuntansi formal.
6. Sistem belum mencatat cost pada stock transfer (transfer tanpa valuasi).
7. Sistem belum punya mekanisme retur pembelian dengan recalculate avg cost.

## Prioritas Implementasi
1. **Fase 1** — Moving average cost engine:
   - Tabel `product_costs` (per product, per outlet).
   - Update avg cost saat receive purchase.
   - Snapshot cogs saat post sale.
2. **Fase 2** — Valuasi transfer & retur:
   - Catat cost pada stock transfer.
   - Hitung ulang avg cost saat retur pembelian.
3. **Fase 3** — Period closing:
   - Tabel `accounting_periods` + lock date.
   - Validasi backdate terhadap lock date.
   - Laporan rekonsiliasi otomatis (Neraca vs Mutasi Persediaan).

Dokumen ini menjadi acuan diskusi final sebelum implementasi coding berikutnya.
