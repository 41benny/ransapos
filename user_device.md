# Rule User Login, Outlet, dan Perangkat Terdaftar

Dokumen ini merangkum aturan yang saat ini dipakai aplikasi untuk login, outlet, dan device pairing POS.

## 1) Definisi
- `Perangkat terdaftar`: browser/perangkat yang sudah pairing dan menyimpan cookie `pos_device_token`.
- `Device enforcement`: status paksa perangkat dari setting `pos_device_enforce`.
- `User outlet`:
  - user yang punya `outlet_id` (mis. kasir/admin/kitchen terikat outlet),
  - role `karyawan_outlet` (khusus absensi, tanpa akses login aplikasi).

## 2) Rule Login (Email + Password)
- Hanya akun `is_active = true` yang bisa login.
- Jika user punya `outlet_id` dan outlet nonaktif, login ditolak.
- Rate limit login: 5 kali percobaan gagal, blokir 15 menit.
- Role `karyawan_outlet` selalu ditolak login.
- Jika ada token device di browser:
  - outlet device harus sama dengan outlet user,
  - untuk role `kasir` dan `kitchen`, akun hanya boleh aktif di 1 device pada saat yang sama.
  - login di device baru akan mengambil alih sesi aktif, lalu device lama dipaksa logout saat request berikutnya.

## 3) Rule Pairing Device
- Kode pairing dibuat oleh `admin/manager` dari menu Admin `Perangkat POS`.
- Kode pairing default berlaku 15 menit dan hanya bisa dipakai 1 kali.
- Kode pairing terikat ke 1 outlet.
- Saat registrasi device:
  - jika user login punya `outlet_id`, maka kode pairing wajib dari outlet yang sama,
  - setelah berhasil, device mendapat token cookie (default masa berlaku 365 hari).

## 4) Rule Akses POS vs Device Enforcement
- Jika enforcement `OFF`:
  - route POS tetap bisa dibuka walau device belum pairing.
- Jika enforcement `ON`:
  - route POS wajib device terdaftar, aktif, dan tidak revoked,
  - jika token tidak ada/tidak valid, user diarahkan ke `/pos/device/register`,
  - jika user punya `outlet_id`, outlet device harus sama dengan outlet user.

## 5) Rule Single-Device (Kasir/Kitchen, Dengan Takeover)
- Berlaku untuk role `kasir` dan `kitchen`.
- Satu user hanya boleh punya 1 `active_pos_device_id` aktif.
- Jika user login di device B, `active_pos_device_id` langsung pindah ke device B.
- Device A (lama) akan otomatis logout saat mengakses POS lagi.
- Cara pindah device:
  1. Cukup login di device baru (takeover otomatis), atau
  2. Admin revoke device lama dari menu `Perangkat POS`.

## 6) Rule PIN Login di Device POS
- PIN login hanya menampilkan user yang:
  - pernah login di device tersebut (`pos_device_user_logins`),
  - `users.outlet_id` sama dengan outlet device,
  - role termasuk `kasir`, `admin`, atau `kitchen`,
  - punya `attendance_pin` dan akun aktif.

## 7) Jawaban Pertanyaan Utama
Pertanyaan: apakah user outlet bisa buka di perangkat mana saja selama dapat kode pairing?

Jawaban:
- `Bisa`, dengan syarat kode pairing valid dan outlet pada kode pairing sama dengan outlet user.
- Role `kasir` dan `kitchen` tidak bisa aktif bersamaan di banyak perangkat.
- Saat login di perangkat baru, sesi perangkat lama akan diputus otomatis.
- Role `karyawan_outlet` tetap `tidak bisa login`, walaupun punya kode pairing.
