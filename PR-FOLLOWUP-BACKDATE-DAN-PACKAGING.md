# PR Lanjutan — Backdate & Kontrol Packaging

Catatan temuan yang belum dikerjakan, hasil penelusuran kasus cup Kemiling 20 Juli 2026.
Dibuat 21 Juli 2026. Belum ada satu pun yang diimplementasikan.

Yang **sudah** selesai dan sudah jalan di produksi:

- `ceec36bb` — backdate menempel ke shift kasir aslinya, saldo kas shift tertutup dibekukan
- `95cc5bf4` — riwayat POS mengikuti kepemilikan shift, bukan `sales.user_id`
- 24 transaksi backdate lama sudah dipindah ke shift aslinya lewat `backdate-sales:migrate-to-shift`

---

## 1. Kolom stok fisik diisi otomatis oleh sistem — PRIORITAS TERTINGGI

### Masalah

Di layar tutup shift, kolom "Sisa Fisik" sudah terisi duluan dengan angka hitungan sistem:

```php
$defaultPhysical = max($available - $estimated, 0);
```
`app/Services/PackagingService.php:205`

Angka itu diturunkan dari rumus selisih, jadi kalau kasir tinggal klik simpan,
**selisihnya nol secara matematis** — tanpa ada yang pernah menghitung isi rak.

Ada tiga lapis yang semuanya mengarah ke nol:

1. Kolom terisi otomatis — `resources/views/pos/sessions/close.blade.php:257`
2. Validasi `nullable`, boleh dikosongkan — `app/Http/Requests/POS/CloseCashSessionRequest.php:25`
3. Kalau dikosongkan, service memakai default yang sama — `app/Services/PackagingService.php:243`

### Bukti dari data produksi

```
Total baris closing packaging : 1.137
Selisih tepat 0               : 1.127  (99,1%)
Shift yang semua itemnya 0    :    68 dari 77  (88,3%)
```

Hanya 12 baris di 9 shift yang selisihnya bukan nol. Kalau 15 item benar-benar dihitung
manual di 77 shift, mustahil hasilnya serapi ini. Dikonfirmasi juga oleh keterangan
lapangan: kasir memang tidak pernah mengetik hasil opname.

Artinya fitur kontrol packaging praktis tidak berfungsi sejak dipasang 12 Juni 2026.
Data lama tidak bisa diselamatkan, hanya bisa diperbaiki ke depan.

### Kenapa menyembunyikan kolom Selisih saja tidak cukup

Layar tutup shift menampilkan Awal, Masuk, Keluar, dan Estimasi Sales. Kasir bisa
menghitung angka "aman" di kepala: `60 − 1 − 7 = 52`. Yang bocor bukan kolom Selisih,
tapi semua bahan untuk menghitungnya.

### Usulan: opname buta

Layar tutup shift hanya menampilkan nama item dan kotak kosong:

```
Item              Sisa Fisik
Cup               [        ]
Box 8             [        ]
```

- Tanpa stok awal, masuk/keluar, estimasi, terpakai, maupun selisih
- Wajib diisi, tidak bisa disimpan kalau kosong
- Terkunci setelah simpan, kasir tidak bisa mengedit
- Selisih dihitung di belakang layar, muncul di laporan admin
- Sebelum simpan final, tampilkan ringkasan angka yang diketik saja (tanpa pembanding)
  untuk mengantisipasi salah ketik
- Revisi hanya lewat admin, dengan jejak siapa yang mengubah

### Catatan jujur

Ini tidak 100% rapat. Kasir masih melihat stok awal saat buka shift, jadi kalau niat,
bisa dihafal lalu dihitung kasar. Yang berubah: menyimpang jadi butuh niat sadar dan
hitungan, bukan sekadar menekan tombol simpan seperti sekarang.

### Keputusan yang masih menggantung

Diterapkan ke semua 15 item sekaligus, atau bertahap mulai dari Cup dengan pengaturan
per item? Perlu dipertimbangkan tambahan 5–10 menit waktu tutup shift dan sosialisasi ke
kasir, karena minggu pertama pasti ramai selisih — itu wajar dan bukan tanda kasir mencuri.

---

## 2. Shift yang melewati tengah malam

### Masalah

Pencocokan shift tujuan backdate memakai `DATE(opened_at)`:

```php
->whereDate('opened_at', $date)
```
`app/Services/BackdateSaleService.php` — `availableSessions()`

Untuk shift yang buka sebelum tengah malam dan tutup setelahnya, transaksi setelah pukul
00:00 punya `sale_date` hari berikutnya, sehingga tidak akan cocok dengan shift yang
menaunginya. Backdate untuk transaksi seperti itu akan membuat sesi koreksi baru —
persis masalah yang sedang diberantas.

### Bukti dari data produksi

9 shift reguler melewati tengah malam. Tiga di antaranya punya transaksi dengan
`sale_date` berbeda dari tanggal bukanya:

```
sesi#7   buka 2026-06-01  →  ada sale_date 2026-06-02
sesi#8   buka 2026-06-02  →  ada sale_date 2026-06-03, 2026-06-04
sesi#10  buka 2026-06-02  →  ada sale_date 2026-06-03
```

Tidak mempengaruhi migrasi yang sudah dijalankan, tapi akan menggigit untuk input berikutnya.

### Usulan

Cocokkan `sale_date` ke rentang `opened_at`–`closed_at`, bukan hanya tanggal bukanya.
Untuk shift yang masih terbuka, pakai `opened_at` sampai sekarang.

---

## 3. Outlet Enggal belum pernah membuka shift

### Masalah

```
Outlet 4 (Ganxie Enggal): 11 sesi kas — reguler 0, backdate 11
```

Sejak buka 3 Juli 2026, Enggal tidak pernah sekali pun kasirnya membuka shift di POS.
Seluruh transaksinya diinput backdate oleh admin.

Konsekuensinya:

- Kontrol packaging di Enggal tidak berjalan sama sekali — tidak ada opening, closing,
  maupun hitung cup
- 10 sesi backdate berisi 17 transaksi tidak bisa dimigrasi karena tidak ada shift tujuan
- Rekap kas Enggal tidak punya pembanding

### Catatan

Ini masalah operasional, bukan kode. Perlu dipastikan dulu apakah Enggal memang belum
memakai POS, atau kasirnya belum pernah diajari membuka shift. Tidak ada perbaikan
teknis yang masuk akal sebelum itu jelas.

---

## Lain-lain

### Selisih packaging jadi bukan nol di 5 baris

Setelah migrasi, shift 62, 84, dan 134 memunculkan selisih `-1` di beberapa item.
Ini bukan kesalahan kasir — muncul karena transaksinya telat diinput admin. Perlu
diluruskan ke kasir kalau sampai jadi bahan pertanyaan.

### Selisih 7 jam antar kolom waktu

`opened_at`/`closed_at` disimpan WIB, sementara `created_at`/`updated_at` UTC.
Contoh sesi 134: buka `10:20:41`, dibuat `03:20:41`. Tidak mengganggu jalannya aplikasi,
tapi menyesatkan siapa pun yang menganalisis data langsung dari database.

### Koreksi stok awal vs adjustment

Keduanya sama-sama tersedia dan hasil angkanya sama, tapi maknanya beda:

- **Koreksi stok awal saat buka shift** — untuk "hitungan shift kemarin salah, aslinya 51".
  Tersimpan sebagai `is_manual_corrected` dengan angka asal di `source_last_closing_qty`.
- **Adjustment masuk/keluar** — untuk perpindahan barang sungguhan: rusak, terbuang,
  dipinjam outlet lain.

Adjustment `-1` cup yang diinput 21 Juli sebenarnya kasus koreksi stok awal. Angkanya
benar, hanya catatannya berbunyi "1 cup keluar" padahal yang terjadi salah hitung.
Tidak perlu dibatalkan.
