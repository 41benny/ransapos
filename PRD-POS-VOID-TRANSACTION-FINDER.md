# PRD — Pencarian Transaksi untuk Void POS

## Ringkasan

Kasir saat ini memilih transaksi void dari daftar riwayat. Alur ini terlalu bergantung pada posisi transaksi di daftar dan pengetahuan kasir tentang nomor faktur. Pada layar kecil, daftar yang panjang juga sulit ditelusuri sampai transaksi lama.

Fitur ini membuat transaksi yang akan di-void dapat ditemukan menggunakan informasi yang biasanya masih diingat kasir: waktu/tanggal, produk, nominal, pelanggan, atau metode pembayaran.

## Masalah

- Kasir belum tentu mengetahui nomor faktur.
- Daftar transaksi terbaru dibatasi sehingga transaksi lama terlihat seperti tidak tersedia.
- Aksi void tersembunyi di detail transaksi dan akses ke modal riwayat kurang jelas.
- Modal dua kolom belum nyaman digunakan pada layar kasir atau perangkat seluler.

## Tujuan

- Menyediakan pintasan yang jelas dari Dashboard POS untuk mencari transaksi yang akan di-void.
- Tetap menampilkan transaksi terbaru tanpa mewajibkan kata pencarian.
- Mendukung pencarian berdasarkan nomor faktur, produk/SKU, nominal, pelanggan, jenis penjualan, dan metode pembayaran.
- Membatasi hasil berdasarkan tanggal agar kasir dapat mempersempit pencarian.
- Memungkinkan kasir memuat transaksi berikutnya tanpa kehilangan hasil yang sudah terlihat.
- Membuat daftar dan detail transaksi dapat digulir dengan baik pada desktop maupun layar kecil.

## Di Luar Cakupan

- Perubahan aturan otorisasi token void.
- Perubahan proses pengembalian stok dan pembalikan transaksi.
- Void massal lebih dari satu transaksi.
- Pencarian transaksi milik outlet atau shift kasir lain.

## Alur Pengguna

1. Kasir membuka Dashboard POS dan memilih **Cari / Void**.
2. Sistem menampilkan transaksi milik shift kasir, dengan tanggal hari ini sebagai nilai awal.
3. Kasir dapat langsung memilih transaksi terbaru atau mencari memakai informasi yang diketahui.
4. Jika hasil belum ditemukan, kasir dapat mengganti tanggal atau memilih **Muat lebih banyak**.
5. Kasir memeriksa waktu, produk, pembayaran, dan total pada detail transaksi.
6. Kasir memilih **Void / Batal**, mengisi alasan dan token admin, lalu mengonfirmasi.

## Kebutuhan Fungsional

### Dashboard

- Tersedia tombol **Cari / Void** yang membuka modal riwayat.
- Kolom pencarian memiliki petunjuk bahwa faktur, menu, nominal, pelanggan, dan pembayaran dapat dicari.
- Filter tanggal diterapkan ke `date_from` dan `date_to` pada hari yang sama.
- Tombol **Reset** mengembalikan pencarian kosong dan tanggal hari ini.
- Hasil ditampilkan dari transaksi terbaru ke terlama.
- Tombol **Muat lebih banyak** tampil selama server masih memiliki halaman berikutnya.

### API Riwayat

- Respons JSON menerima `q`, `date_from`, `date_to`, `page`, dan `per_page`.
- `q` mencari nomor faktur, pelanggan, jenis penjualan, nominal, produk/SKU, serta metode pembayaran.
- Data tetap dibatasi pada outlet dan shift milik kasir yang sedang login.
- Respons menyertakan metadata halaman tanpa mengubah bentuk `data` sebagai array transaksi.

### Responsivitas

- Pada layar kecil, daftar dan detail ditumpuk secara vertikal.
- Area daftar dan isi detail mempunyai scroll sendiri dan tidak terpotong oleh modal.
- Tombol cetak dan void tetap dapat dijangkau tanpa menggulir halaman di belakang modal.

## Kriteria Penerimaan

- Kasir dapat menemukan transaksi tanpa memasukkan nomor faktur.
- Pencarian nama produk, nominal, pelanggan, atau metode pembayaran mengembalikan transaksi yang sesuai.
- Filter tanggal membatasi hasil ke tanggal yang dipilih.
- Lebih dari satu halaman transaksi dapat dibuka melalui **Muat lebih banyak**.
- Kasir tidak dapat melihat transaksi milik shift kasir lain.
- Proses void yang sudah ada tetap meminta alasan dan token yang valid.
- Modal dapat digunakan pada lebar desktop dan seluler tanpa bagian utama terpotong.

## Pengujian

- Feature test pencarian JSON berdasarkan produk, metode pembayaran, dan nominal.
- Feature test metadata serta halaman berikutnya.
- Regression test cakupan riwayat berdasarkan kepemilikan shift kasir.
