# PRD: Ransapos Multi Payment / Split Payment

Tanggal: 6 Juni 2026

## 1. Ringkasan

Fitur Multi Payment memungkinkan satu transaksi POS dibayar menggunakan lebih dari satu metode pembayaran. Kasus utama yang ingin didukung adalah pelanggan membayar sebagian dengan cash, lalu sisanya menggunakan metode lain seperti QRIS, transfer, debit, credit card, atau e-wallet.

Target utama:
- Kasir bisa menerima pembayaran campuran dalam satu transaksi.
- Sistem menghitung otomatis total terbayar, sisa tagihan, dan status pembayaran.
- Pencatatan pembayaran lebih rapi karena setiap metode bayar disimpan sebagai baris pembayaran terpisah.
- Laporan penjualan, kas, dan metode pembayaran tetap akurat.

Contoh:

```text
Total belanja: Rp250.000

Pembayaran:
- Cash: Rp50.000
- QRIS: Rp200.000

Status: Lunas
```

## 2. Latar Belakang Masalah

Pada operasional kasir, pelanggan sering membayar tidak hanya dengan satu metode. Contoh umum:
- Uang cash pelanggan kurang, sisanya dibayar QRIS.
- Pelanggan ingin memakai sebagian saldo e-wallet dan sisanya cash.
- Transaksi rombongan dibayar gabungan cash dan transfer.
- Outlet perlu mencatat cash masuk terpisah dari pembayaran non-cash.

Jika transaksi hanya memiliki satu kolom `payment_method`, maka pembayaran campuran sulit dicatat dengan benar. Dampaknya:
- Laporan metode pembayaran tidak akurat.
- Cash drawer bisa selisih karena sebagian pembayaran non-cash tercatat sebagai cash.
- Sulit melakukan rekonsiliasi QRIS/transfer/debit.
- Transaksi partial payment tidak punya struktur data yang jelas.

## 3. Tujuan Produk

1. Mendukung split payment dalam satu transaksi POS.
2. Memisahkan pencatatan per metode pembayaran.
3. Menghitung status pembayaran secara otomatis: unpaid, partial, paid, atau overpaid/change.
4. Menjaga laporan pembayaran dan kas tetap akurat.
5. Membuat flow kasir tetap cepat dan mudah digunakan.

## 4. Non-Goals

- Tidak membuat sistem cicilan/piutang kompleks pada MVP.
- Tidak membuat integrasi payment gateway otomatis pada MVP.
- Tidak membuat settlement bank/QRIS otomatis.
- Tidak mengubah flow produk, stok, pajak, atau diskon di luar kebutuhan pembayaran.
- Tidak mengizinkan total pembayaran kurang dari total transaksi untuk transaksi yang harus lunas saat checkout, kecuali mode partial payment memang diaktifkan.

## 5. User Persona

- **Kasir**: menerima pembayaran dari pelanggan dengan kombinasi cash dan non-cash.
- **Supervisor/Manager Shift**: memantau transaksi partial atau pembayaran yang tidak biasa.
- **Finance/Admin**: mencocokkan laporan cash, QRIS, transfer, debit, dan metode lain.
- **Owner**: melihat komposisi metode pembayaran per outlet/periode.

## 6. User Story

- Sebagai kasir, saya ingin memasukkan pembayaran cash sebagian lalu memilih metode lain untuk sisa tagihan.
- Sebagai kasir, saya ingin sistem otomatis menampilkan sisa yang harus dibayar.
- Sebagai kasir, saya ingin sistem mencegah pembayaran yang melebihi aturan, misalnya kembalian hanya boleh untuk cash.
- Sebagai finance, saya ingin laporan pembayaran menampilkan breakdown per metode.
- Sebagai owner, saya ingin total sales tetap sama walaupun pembayaran berasal dari beberapa metode.

## 7. Scope Fitur MVP

Fitur wajib:
1. Tombol/area pembayaran mendukung lebih dari satu metode bayar.
2. Input nominal per metode pembayaran.
3. Sistem otomatis menghitung:
   - total tagihan
   - total dibayar
   - sisa bayar
   - kembalian jika ada
4. Metode cash dapat menghasilkan kembalian.
5. Metode non-cash tidak boleh menghasilkan kembalian, kecuali ada setting khusus.
6. Simpan detail pembayaran ke tabel pembayaran terpisah.
7. Tampilkan detail pembayaran pada struk.
8. Tampilkan breakdown metode bayar pada detail transaksi.
9. Laporan pembayaran dapat menghitung total per metode.

Opsional fase berikutnya:
- Mode partial payment untuk piutang.
- Otorisasi supervisor untuk transaksi partial.
- Integrasi settlement QRIS/bank.
- Refund parsial per metode pembayaran.
- Split bill per pelanggan/meja.

## 8. Flow UX Kasir

### 8.1 Transaksi Lunas dengan Multi Payment

1. Kasir menambahkan item ke cart.
2. Kasir klik `Bayar`.
3. Sistem menampilkan total tagihan.
4. Kasir memilih metode pertama, contoh `Cash`.
5. Kasir input nominal cash, contoh `Rp50.000`.
6. Sistem menampilkan sisa tagihan, contoh `Rp200.000`.
7. Kasir memilih metode kedua, contoh `QRIS`.
8. Sistem bisa otomatis mengisi nominal sisa tagihan.
9. Kasir konfirmasi pembayaran.
10. Jika total bayar sama dengan total tagihan, transaksi tersimpan dengan status `paid`.
11. Struk menampilkan semua metode pembayaran.

### 8.2 Transaksi dengan Kembalian Cash

Contoh:

```text
Total tagihan: Rp247.000
Cash: Rp50.000
QRIS: Rp200.000
Total dibayar: Rp250.000
Kembalian: Rp3.000
```

Aturan:
- Kembalian hanya dihitung dari kelebihan pembayaran cash.
- Jika kelebihan berasal dari QRIS/transfer/debit, sistem harus menolak atau meminta koreksi nominal.

### 8.3 Transaksi Partial

Jika mode partial payment diaktifkan:
1. Kasir input pembayaran yang belum menutup total tagihan.
2. Sistem menampilkan sisa tagihan.
3. Kasir memilih `Simpan sebagai Partial`.
4. Status transaksi menjadi `partial`.
5. Sisa tagihan tercatat sebagai outstanding.

Jika mode partial payment tidak aktif:
- Sistem tidak boleh menyimpan transaksi jika total pembayaran kurang dari total tagihan.

## 9. Aturan Bisnis

1. Satu transaksi dapat memiliki banyak baris pembayaran.
2. Setiap baris pembayaran wajib memiliki metode, nominal, dan waktu pembayaran.
3. Total pembayaran valid dihitung dari penjumlahan semua baris pembayaran.
4. Status pembayaran:
   - `unpaid`: belum ada pembayaran.
   - `partial`: total pembayaran lebih dari 0 tetapi kurang dari total tagihan.
   - `paid`: total pembayaran sama dengan total tagihan.
   - `paid_with_change`: total pembayaran lebih dari total tagihan dan kembalian valid.
5. Nominal pembayaran tidak boleh 0 atau negatif.
6. Metode pembayaran non-cash tidak boleh melebihi sisa tagihan, kecuali ada konfigurasi khusus.
7. Cash boleh melebihi sisa tagihan karena dapat menghasilkan kembalian.
8. Kembalian harus dicatat eksplisit sebagai `change_amount`.
9. Kembalian tidak dihitung sebagai pendapatan.
10. Pembayaran tidak boleh mengubah subtotal item, diskon, pajak, service charge, atau HPP.
11. Pembatalan transaksi harus membatalkan semua payment line terkait.
12. Void/refund harus menyimpan audit trail dan tidak menghapus riwayat payment line begitu saja.

## 10. Data & Teknis Konseptual

### 10.1 Struktur Data yang Disarankan

Gunakan tabel pembayaran terpisah, misalnya `sale_payments`.

```text
sales
- id
- invoice_no
- customer_id
- subtotal
- discount_total
- tax_total
- service_charge_total
- grand_total
- paid_total
- change_amount
- remaining_amount
- payment_status
- created_by
- created_at
```

```text
sale_payments
- id
- sale_id
- payment_method_id
- method_code
- method_name
- amount
- reference_no
- note
- paid_at
- created_by
- voided_at
- voided_by
- void_reason
- created_at
- updated_at
```

Catatan:
- `payment_method_id` mengambil data dari master metode pembayaran.
- `method_code` dan `method_name` dapat disimpan sebagai snapshot agar laporan historis tetap aman jika master metode berubah.
- `reference_no` dipakai untuk nomor referensi QRIS, transfer, debit, atau e-wallet.

### 10.2 Formula

```text
paid_total = sum(sale_payments.amount yang aktif)
remaining_amount = max(grand_total - paid_total, 0)
change_amount = max(paid_total - grand_total, 0), hanya jika kelebihan valid dari cash
```

Untuk laporan:

```text
Total Sales = sum(sales.grand_total)
Total Payment per Method = sum(sale_payments.amount) per payment_method
Cash Drawer In = sum(payment cash) - change_amount
```

## 11. Dampak ke Struk

Struk harus menampilkan detail pembayaran jika lebih dari satu metode.

Contoh:

```text
Total        Rp250.000
Tunai        Rp50.000
QRIS         Rp200.000
Kembali      Rp0
Status       Lunas
```

Jika single payment, struk tetap boleh menampilkan format lama agar ringkas.

## 12. Dampak ke Laporan

Laporan yang terdampak:
- Laporan penjualan harian.
- Laporan metode pembayaran.
- Laporan kas kasir/cash session.
- Detail invoice/transaksi.
- Export penjualan.

Prinsip laporan:
1. Nilai penjualan tetap mengikuti `grand_total`.
2. Nilai pembayaran mengikuti `sale_payments`.
3. Cash drawer menghitung cash bersih setelah kembalian.
4. Non-cash dihitung sesuai nominal payment line.
5. Satu invoice boleh muncul satu kali di laporan penjualan, tetapi bisa muncul beberapa baris di laporan metode pembayaran.

## 13. Permission & Audit

Permission yang disarankan:
- `pos.use_multi_payment`
- `pos.allow_partial_payment`
- `pos.void_payment`
- `pos.refund_payment`

Audit minimal:
- siapa kasir yang membuat pembayaran
- kapan pembayaran dibuat
- metode pembayaran
- nominal pembayaran
- nomor referensi non-cash
- siapa yang void/refund
- alasan void/refund

Untuk MVP, partial payment dapat dibatasi hanya role tertentu atau dinonaktifkan default.

## 14. Validasi Error

Pesan validasi yang diperlukan:
- `Nominal pembayaran wajib diisi.`
- `Nominal pembayaran tidak boleh 0.`
- `Pembayaran non-cash tidak boleh melebihi sisa tagihan.`
- `Total pembayaran masih kurang.`
- `Metode pembayaran sudah dipilih, masukkan nominal atau hapus baris.`
- `Nomor referensi wajib diisi untuk metode pembayaran ini.`
- `Kembalian hanya dapat diproses dari pembayaran cash.`

## 15. Acceptance Criteria

1. Kasir bisa menyelesaikan transaksi dengan kombinasi cash + QRIS.
2. Kasir bisa menyelesaikan transaksi dengan kombinasi cash + transfer.
3. Sistem otomatis mengisi sisa tagihan saat kasir memilih metode pembayaran berikutnya.
4. Sistem menolak nominal non-cash yang lebih besar dari sisa tagihan.
5. Sistem menghitung kembalian dengan benar jika cash melebihi total tagihan.
6. Detail transaksi menampilkan semua payment line.
7. Struk menampilkan semua metode pembayaran yang digunakan.
8. Laporan metode pembayaran menjumlahkan nominal berdasarkan payment line.
9. Laporan penjualan tidak menggandakan invoice walaupun invoice memiliki lebih dari satu payment line.
10. Cash session menghitung cash bersih setelah kembalian.
11. Void transaksi ikut membatalkan payment line terkait dengan audit trail.
12. Existing single payment tetap berjalan.

## 16. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Laporan penjualan menggandakan omzet karena join payment line | Omzet terlihat lebih besar | Query sales harus agregasi per invoice, bukan per payment line |
| Cash drawer selisih karena kembalian tidak dipisah | Rekonsiliasi kas salah | Simpan `change_amount` eksplisit dan hitung cash net |
| Non-cash overpayment | Rekonsiliasi QRIS/bank sulit | Validasi non-cash tidak boleh melebihi sisa |
| Data historis metode bayar berubah | Laporan lama berubah nama/metode | Simpan snapshot `method_code` dan `method_name` |
| Partial payment disalahgunakan | Piutang tidak terkontrol | Default off atau perlu permission/otorisasi |

## 17. Rekomendasi Implementasi Bertahap

### Fase 1 - MVP Split Payment Lunas

- Tambah struktur `sale_payments`.
- Ubah checkout POS agar bisa menerima array pembayaran.
- Validasi total pembayaran harus lunas.
- Tampilkan payment line di struk dan detail transaksi.
- Update laporan metode pembayaran.

### Fase 2 - Cash Session & Rekonsiliasi

- Pastikan cash drawer memakai cash net.
- Tambahkan filter laporan per metode bayar.
- Tambahkan export payment breakdown.

### Fase 3 - Partial Payment

- Aktifkan status `partial`.
- Tambahkan permission/otorisasi partial.
- Tambahkan layar pelunasan sisa pembayaran.
- Tambahkan laporan outstanding.

### Fase 4 - Refund/Void Lanjutan

- Refund parsial per payment line.
- Audit void/refund detail.
- Rekonsiliasi non-cash.

## 18. Catatan Keputusan Produk

Rekomendasi utama adalah tidak menyimpan pembayaran hanya sebagai satu kolom `payment_method` pada transaksi. Ransapos sebaiknya memakai model `sales` + `sale_payments`, karena pola ini lebih fleksibel untuk:
- single payment
- multi payment
- partial payment
- refund
- laporan per metode bayar
- rekonsiliasi cash dan non-cash

