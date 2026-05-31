# PRD — Penanganan "Page Expired" (HTTP 419) pada Ransa POS

| | |
|---|---|
| **Status** | ✅ Implemented |
| **Pemilik** | Tim Ransa POS |
| **Tanggal** | 2026-06-01 |
| **Versi** | 1.0 |
| **Area terdampak** | Autentikasi (Login & PIN POS), Sesi, PWA (POS & Admin) |

---

## 1. Latar Belakang

Pengguna (kasir & admin) sering menemui pesan **"Page Expired" (HTTP 419)**, terutama pada skenario:

> User **menutup tab**, lalu **membuka tab lagi**, melakukan **login**, namun tetap mendapat **Page Expired**.

Karena aplikasi juga dipasang sebagai **PWA (mode standalone)** — tanpa address bar dan tanpa tombol *back* — ketika error muncul, user **terjebak (mentok)** dan tidak bisa melakukan apa-apa selain menutup paksa aplikasi.

Error 419 di Laravel terjadi ketika **token keamanan CSRF** pada form **tidak cocok** dengan token yang dicatat di sesi server (umumnya karena token sudah kedaluwarsa atau halaman form sudah basi).

---

## 2. Masalah (Problem Statement)

1. **Login gagal dengan 419 setelah tab dibuka kembali.** Browser/PWA menampilkan **halaman login lama dari cache** yang masih membawa token CSRF lama, sementara server sudah memutar token baru → tidak cocok → 419.
2. **User terjebak saat 419 di mode app.** Halaman error bawaan Laravel polos, tanpa tombol navigasi, sehingga di mode standalone (tanpa address bar) user tidak punya jalan keluar.
3. **Umur sesi terlalu pendek untuk operasional POS.** Default 120 menit (2 jam) terlalu singkat untuk perangkat kasir yang dibiarkan menyala lama, memperbesar peluang sesi/token kedaluwarsa.

---

## 3. Tujuan & Metrik Keberhasilan

### Tujuan
- Menghilangkan 419 pada alur **tutup tab → buka lagi → login**.
- Memberi **jalan keluar yang jelas** bila 419 tetap terjadi (tidak ada lagi kondisi "mentok").
- Menyesuaikan **umur sesi** dengan pola pemakaian POS.

### Metrik Keberhasilan
- Kejadian 419 saat login **turun mendekati nol** pada skenario buka-tutup tab.
- **0 laporan** user "mentok tidak bisa apa-apa" akibat 419.
- Sesi aktif bertahan **8 jam** tanpa perlu login ulang di tengah operasional normal.

---

## 4. Analisis Akar Masalah (Root Cause)

### 4.1 Halaman login boleh di-cache (penyebab utama)
Halaman `/login` dan `/pos/pin` tidak mengirim header anti-cache. Akibatnya:

1. User membuka login → menerima token CSRF **A**.
2. Tab ditutup; seiring waktu server memutar token sesi menjadi **B**.
3. User membuka tab lagi → browser/PWA **menyajikan halaman login lama dari cache** (masih membawa token **A**), termasuk dari mekanisme *back-forward cache* (bfcache) saat tab dipulihkan.
4. User submit dengan token **A** → server hanya menerima **B** → **mismatch → 419**.

### 4.2 Tidak ada halaman 419 yang ramah
Halaman error default tidak memiliki tombol navigasi, sehingga di PWA (standalone) user tidak punya cara kembali.

### 4.3 Umur sesi pendek
`SESSION_LIFETIME=120` (menit). Token CSRF mengikuti umur sesi, sehingga sesi idle >2 jam memperbesar peluang 419.

---

## 5. Solusi yang Diterapkan

### 5.1 Middleware `NoCache` pada halaman autentikasi
Halaman `/login` dan `/pos/pin` kini mengirim header yang melarang penyimpanan cache, sehingga **token CSRF selalu segar** setiap halaman dibuka (termasuk setelah tab ditutup & dibuka kembali).

- **File:** `app/Http/Middleware/NoCache.php`
- **Header yang dikirim:**
  - `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`
  - `Pragma: no-cache`
  - `Expires: 0`
- **Dipasang di route** (`routes/web.php`):
  - `GET /login` → middleware `['guest', NoCache]`
  - `GET /pos/pin` → middleware `['guest', NoCache]`
- **Catatan teknis:** `no-store` juga menonaktifkan *bfcache* di mayoritas browser, sehingga halaman login tidak dipulihkan dari memori dengan token basi.

### 5.2 Halaman 419 yang ramah
Halaman error 419 kustom bertema gelap (Ganxie) dengan tombol jelas **"Muat Ulang & Login"** yang mengarah ke `/login` (halaman segar, token baru).

- **File:** `resources/views/errors/419.blade.php`
- **Manfaat:** user di mode app tidak lagi mentok — cukup satu ketukan untuk pulih.

### 5.3 Perpanjangan umur sesi menjadi 8 jam
- **Default kode:** `config/session.php` → `env('SESSION_LIFETIME', 480)`
- **Template:** `.env.example` → `SESSION_LIFETIME=480`
- **Server:** `.env` di-set `SESSION_LIFETIME=480` (480 menit = 8 jam).
- **Driver sesi:** `database` (tidak berubah).

> **Pekerjaan terkait (di luar fokus utama, sudah diterapkan):** halaman **403 ramah** + route `/beranda` untuk mencegah user "nyasar/mentok" antar aplikasi POS & Admin yang berbagi sesi pada origin sama. Lihat `resources/views/errors/403.blade.php`.

---

## 6. Ruang Lingkup

### Termasuk
- Header anti-cache pada halaman login & PIN POS.
- Halaman error 419 (dan 403) yang ramah.
- Penyesuaian `SESSION_LIFETIME` ke 8 jam.

### Tidak termasuk
- Perubahan driver sesi (tetap `database`).
- Perubahan mekanisme autentikasi inti / hashing.
- Auto-refresh token CSRF via AJAX di latar belakang (kandidat peningkatan ke depan).

---

## 7. Dampak & Risiko

| Aspek | Dampak | Mitigasi |
|---|---|---|
| **Keamanan perangkat bersama** | Sesi aktif 8 jam → risiko penyalahgunaan bila perangkat ditinggal | Biasakan **logout** saat ganti shift; andalkan **PIN** POS |
| **Tabel `sessions` (DB)** | Baris sesi tersimpan lebih lama sebelum dibersihkan | Dampak kecil pada skala POS; *garbage collection* Laravel tetap berjalan |
| **Caching halaman login** | Hanya halaman login & PIN yang tidak di-cache | Terbatas pada 2 halaman, tidak memengaruhi performa aplikasi keseluruhan |
| **Toko buka > 8 jam nonstop** | Sesi bisa habis di tengah shift bila perangkat lama idle | Bisa dinaikkan (mis. 720 = 12 jam) bila diperlukan |

---

## 8. Rencana Pengujian (Test Plan)

| # | Skenario | Hasil yang diharapkan |
|---|---|---|
| 1 | Buka login → tutup tab → buka tab lagi → login | Berhasil login, **tanpa** 419 |
| 2 | Diamkan halaman login sangat lama lalu submit | Bila token basi, tampil **halaman 419 ramah**; tap "Muat Ulang & Login" → kembali ke login segar |
| 3 | Login normal (email & PIN) | Berfungsi seperti biasa, tidak ada regresi |
| 4 | Sesi aktif | Bertahan ~8 jam tanpa login ulang pada pemakaian normal |
| 5 | 419 di mode PWA (standalone) | User punya tombol keluar, tidak mentok |
| 6 | Inspeksi header `GET /login` | Mengandung `Cache-Control: no-store, ...` |

---

## 9. Rollout / Deployment

Dijalankan di server pada folder aplikasi (`ransapos.web.id`):

```bash
# 1. Ambil kode terbaru
git fetch origin && git reset --hard origin/main

# 2. Set umur sesi 8 jam di .env (backup otomatis lalu verifikasi)
cp .env .env.bak
grep -q '^SESSION_LIFETIME=' .env \
  && sed -i 's/^SESSION_LIFETIME=.*/SESSION_LIFETIME=480/' .env \
  || echo 'SESSION_LIFETIME=480' >> .env
grep SESSION_LIFETIME .env   # harus menampilkan SESSION_LIFETIME=480

# 3. Bersihkan cache config, route, dan view
php artisan config:clear && php artisan route:clear && php artisan view:clear
```

> Bila server memakai `config:cache` untuk produksi, gunakan `php artisan config:cache` pada langkah 3.

### Rollback
- Kembalikan `.env` dari backup: `cp .env.bak .env`
- `git reset --hard <commit_sebelumnya>` lalu ulangi langkah cache clear.

---

## 10. Peningkatan ke Depan (Future Work)

1. **Auto-refresh token CSRF** di latar belakang (mis. ambil token baru via `meta` + AJAX) agar form panjang tidak pernah basi.
2. **Deteksi sesi habis lebih dini** dengan notifikasi "sesi akan berakhir" sebelum 419.
3. **Pemisahan sesi per aplikasi** (POS vs Admin) bila ke depan ingin kebijakan keamanan berbeda per area.
4. **Auto-logout perangkat bersama** setelah idle tertentu sebagai opsi keamanan tambahan.

---

## 11. Referensi Berkas & Commit

**Berkas terkait:**
- `app/Http/Middleware/NoCache.php`
- `resources/views/errors/419.blade.php`
- `resources/views/errors/403.blade.php`
- `routes/web.php`
- `config/session.php`
- `.env.example`

**Commit terkait:**
- `5ed7dd65` — Prevent 419 on login: no-cache login pages + friendly 419 page
- `49c1618c` — Default session lifetime to 8 hours (480 min)
- `ecf10485` — Fix cross-app login redirect and add friendly 403 page (pekerjaan terkait)
