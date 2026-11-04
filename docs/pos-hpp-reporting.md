# Klasifikasi HPP Pengeluaran POS

Cara input kasir tetap sama: pilih kategori, sumber pembayaran, penerima, keperluan, dan jumlah. Perubahan ini hanya menghitung ulang kelompok biaya pada laporan, tanpa mengubah akun, transaksi historis, stok, atau jurnal yang sudah tersimpan.

## Perhitungan

- HPP total = HPP barang dari penjualan + HPP tambahan POS yang belum termasuk biaya produk.
- HPP tambahan berasal hanya dari satu baris `cash_transactions` dengan referensi `pos_drawer_expense` yang cocok dengan baris sumber `cash_session_expenses`: ID sumber, ID posting, akun, dan shift harus cocok; status sumber harus `posted`, arah `out`.
- Detail shift tidak dijumlahkan lagi di samping posting kas. Pengeluaran shift terbuka belum masuk laporan keuangan sampai closing, sama seperti sebelumnya.
- Kategori biaya POS selain pengecualian masuk HPP tambahan. Pengeluaran lainnya, kebersihan/habis pakai, token PLN/listrik, dan pulsa tetap biaya operasional. Kategori dikenali melalui kode akun bawaan dan pola nama pada `config/pos_expense_reporting.php`; akun baru dengan nama sama juga mengikuti pengecualian.
- Akun bertipe aset, termasuk Belanja Stok/Barang Dagangan `1-145`, tidak dihitung sebagai beban. Akun kelompok `HPP` yang sudah menjadi HPP stok tidak dijumlahkan ulang dari pembayaran kas. Nama kategori POS yang menunjukkan stok/persediaan/bahan baku/barang dagangan juga dilindungi dari pembebanan ulang.
- Kas masuk pemilik, perpindahan kas, setoran shift dan pembayaran pembelian tidak menjadi HPP tambahan POS. Transaksi non-POS mempertahankan klasifikasi sebelumnya.
- Pengeluaran tunai maupun non-tunai memakai klasifikasi yang sama. Saldo laci, rekening sumber, nominal pembayaran dan perhitungan closing tetap mengikuti alur sebelumnya.
- Dashboard (periode berjalan, periode pembanding, dan per outlet), laba rugi, serta ekspor laba rugi menggunakan klasifikasi POS yang sama. Laba rugi menampilkan rincian HPP stok dan tambahan POS. Klik rincian biaya menampilkan daftar dan ekspor kas dengan cakupan biaya dan outlet yang sama.

Contoh: penjualan Rp1.000.000, HPP stok Rp300.000, gas/galon/transport Rp35.000, empat kategori pengecualian Rp34.000. HPP total Rp335.000, laba kotor sebelum komisi Rp665.000, laba bersih sebelum komisi Rp631.000. Belanja stok Rp80.000 tidak menambah HPP lagi.

## Biaya yang sudah ada di resep

Jika biaya kategori tertentu sudah benar-benar termasuk dalam harga pokok produk/resep, masukkan kode kategori itu ke `included_in_product_cost_coa_codes` pada konfigurasi. Pembayaran POS kategori tersebut tidak dibebankan lagi sebagai HPP tambahan maupun biaya operasional. Pemetaan ini harus didasarkan pada komponen biaya produk, bukan kemiripan nominal atau kata dalam keterangan transaksi.

Sistem tidak dapat menyimpulkan bahwa dua nota terpisah adalah pengeluaran yang sama jika pengguna memasukkannya ulang dengan kategori keliru. Kunci idempotensi yang sudah ada tetap melindungi kirim ulang form yang sama. Pengaman laporan mencegah penjumlahan stok + pembayaran stok dan detail POS + posting kas untuk biaya yang sama; tidak melakukan pencocokan nominal yang bisa menghapus transaksi sah.

HPP per SKU dan ekspor jurnal HPP penjualan tetap memuat biaya produk; tambahan POS diperlihatkan terpisah pada laporan agregat, tidak dibagi secara fiktif ke produk. Dasar HPP barang yang sudah ada tetap dipertahankan: laba rugi menggunakan mutasi stok, dashboard menggunakan biaya item penjualan. Selisih historis di kedua sumber tersebut memerlukan rekonsiliasi tersendiri.

## Pemasangan

Tidak ada migrasi database atau build frontend baru. Setelah kode diperbarui di server:

```bash
php artisan config:clear
php artisan view:clear
```

Jika server memakai konfigurasi yang di-cache, jalankan kembali `php artisan config:cache` setelah `config:clear`. Laporan periode sebelumnya juga mengikuti klasifikasi baru saat dibuka ulang, tanpa menulis ulang sumber transaksi.
