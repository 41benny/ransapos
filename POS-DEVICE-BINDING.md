# Fitur Pembatasan POS per Perangkat (Device Binding)

Dokumen ini menjelaskan cara memakai fitur **Device Binding** agar POS hanya bisa diakses dari tablet yang sudah dipairing. Fitur ini **opsional** dan bisa dinyalakan/dimatikan dari Back Office.

## Ringkasan
- **Nonaktif**: POS bisa diakses dari perangkat mana saja (seperti biasa).
- **Aktif**: POS **hanya** bisa diakses dari perangkat yang sudah didaftarkan (paired).

## Prasyarat
1. Jalankan migrasi database:
```bash
php artisan migrate
```
2. Pastikan admin punya akses ke menu **Perangkat POS** di Back Office.

## Cara Mengaktifkan / Menonaktifkan Fitur
1. Login sebagai **Admin/Manager**.
2. Buka menu **Perangkat POS**.
3. Gunakan toggle **Aktif/Nonaktif**.

Catatan:
- Status toggle disimpan di tabel `settings` dengan key `pos_device_enforce`.
- Jika toggle **nonaktif**, enforcement otomatis mati walau `.env` diset `true`.

## Cara Pairing Tablet Kasir
### A. Buat Kode Pairing (Admin)
1. Buka **Perangkat POS**.
2. Pilih **Outlet** dan isi **Nama Perangkat** (opsional).
3. Klik **Buat Kode Pairing**.
4. Kode pairing muncul di halaman dan **berlaku 15 menit** (default).

### B. Daftarkan Tablet (Kasir)
1. Login di tablet kasir seperti biasa.
2. Jika fitur aktif dan tablet belum terdaftar, otomatis diarahkan ke:
   - `/pos/device/register`
3. Masukkan **kode pairing** dan **nama perangkat** (opsional).
4. Klik **Aktifkan Perangkat**.
5. Setelah berhasil, tablet akan kembali ke POS.

## Cara Pakai Harian (Kasir)
Tidak ada langkah tambahan. Kasir cukup login seperti biasa.

## Menonaktifkan / Cabut Akses Perangkat
1. Admin buka **Perangkat POS**.
2. Temukan device di daftar.
3. Klik **Nonaktifkan**.
4. Device akan langsung ditolak saat mengakses POS.

## Reset Perangkat
Jika tablet ganti / cookie terhapus:
1. Admin **Nonaktifkan** perangkat lama.
2. Buat **kode pairing** baru.
3. Pairing ulang di tablet.

## Aturan Outlet
- Jika user punya `outlet_id`, perangkat harus terdaftar di **outlet yang sama**.
- Admin/Manager tanpa `outlet_id` tidak dibatasi outlet.

## Konfigurasi (Opsional)
Di `.env` atau `.env.example`:
```
POS_DEVICE_ENFORCE=false
POS_DEVICE_COOKIE=pos_device_token
POS_DEVICE_PAIRING_TTL=15
POS_DEVICE_TOKEN_TTL_DAYS=365
```
Keterangan:
- `POS_DEVICE_ENFORCE` hanya dipakai sebagai **default** saat toggle belum diset di DB.
- `POS_DEVICE_PAIRING_TTL` = masa berlaku kode pairing (menit).
- `POS_DEVICE_TOKEN_TTL_DAYS` = masa berlaku token perangkat (hari).

## Troubleshooting
**1) “Perangkat POS belum terdaftar”**
- Pastikan fitur **Aktif** di menu **Perangkat POS**.
- Pastikan tablet sudah pairing.
- Coba pairing ulang jika cookie terhapus.

**2) “Kode pairing tidak valid / kedaluwarsa”**
- Kode hanya berlaku 15 menit (default).
- Buat kode baru dari admin.

**3) “Kode pairing bukan untuk outlet Anda”**
- User kasir terikat outlet tertentu.
- Pastikan kode dibuat untuk outlet yang sama.

## Batasan Keamanan
Fitur ini **mencegah akses dari perangkat lain**, tapi **tidak** membuktikan lokasi fisik toko.
Jika tablet asli dikendalikan via remote-control, sistem tetap menganggap valid.

## Referensi Teknis (Singkat)
- Middleware: `app/Http/Middleware/EnsurePosDevice.php`
- Admin UI: `resources/views/admin/pos-devices/index.blade.php`
- Kasir UI: `resources/views/pos/device/register.blade.php`
- Tabel perangkat: `pos_devices`
- Toggle status: tabel `settings` key `pos_device_enforce`
