# PRD — Modul Pengeluaran Kios (Petty Cash Kasir) Terpadu dengan POS

**Status:** Tahap 0–2 terimplementasi di balik feature flag `pos_drawer_expense_enabled` (default MATI)
**Tanggal:** 2026-08-05
**Modul terdampak:** POS, Shift Kasir (`cash_sessions`), Kas & Bank (`cash_accounts` / `cash_transactions`), COA, Laporan Laba Rugi, Stok
**Prasyarat baca:** `petycash_pos.md` (desain petty cash lama), `PR-FOLLOWUP-BACKDATE-DAN-PACKAGING.md`

---

## 0. Ringkasan Eksekutif

Kasir mengeluarkan uang tunai dari **laci** untuk keperluan kios (galon, gas, parkir, kuli angkut). Hari ini pengeluaran itu diinput di POS tapi **memotong kantong yang salah**, sehingga saat closing kasir muncul sebagai selisih kurang padahal jujur.

PRD ini mengubah laci kasir menjadi akun kas berneraca, mengikat setiap pengeluaran ke shift, dan menjadikan **setoran sebagai angka turunan** — bukan angka yang diketik kasir.

**Prinsip yang tidak bisa ditawar:**

1. **Bruto.** Pendapatan dicatat penuh, pengeluaran dicatat penuh. Tidak ada yang dinetkan sebelum masuk buku.
2. **Setoran adalah turunan.** Tidak ada field "setoran" yang diinput bebas.
3. **Selisih adalah selisih.** Tidak boleh diserap diam-diam ke akun lain agar closing terlihat pas.
4. **Shift closed dibekukan.** Konsisten dengan aturan yang sudah berlaku di backdate & void.

**Rumus resmi:**

```
Saldo laci seharusnya = Modal awal + Penerimaan tunai − Pengeluaran tunai
Selisih kas           = Hitung fisik − Saldo laci seharusnya
Setoran               = Hitung fisik − Modal disisakan
```

---

## 1. Kondisi Saat Ini (Hasil Pembacaan Kode)

| Aspek | Kondisi sekarang | Lokasi |
|---|---|---|
| Rumus expected | `opening_balance + total_cash` — **tanpa suku pengeluaran** | `app/Services/SaleService.php:586` |
| Selisih | `actual_balance − expected_balance` | `app/Services/CashSessionService.php:108` |
| Petty cash POS | Memotong akun `usage_type = 'petty_cash'`, **kantong terpisah dari laci** | `app/Http/Controllers/POS/PettyCashController.php:200` |
| Ikatan ke shift | **Tidak ada.** `cash_transactions` tanpa `cash_session_id`, index difilter `whereDate(transaction_date, today)` | `PettyCashController.php:44` |
| Edit setelah closing | **Tidak dicegah.** `update()` hanya cek akun + `reference_type` | `PettyCashController.php:242` |
| Setoran / modal disisakan | **Tidak ada** — tidak ada kolom, tabel, maupun konsep | — |
| Posting selisih ke jurnal | **Tidak ada** | — |
| Kas penjualan → buku kas | **Tidak pernah masuk** `cash_transactions`, hanya agregat di `cash_sessions` | `SaleService.php:579` |
| COA pengeluaran POS | Dipaksa tunggal ke `6-135` | `PettyCashController.php:14` |
| Sumber biaya di Laba Rugi | `cash_transactions` + COA `type = expense`, **bukan** tabel `expenses` | `app/Services/ProfitLossReportService.php:61` |
| Satu shift per outlet | **Tidak ditegakkan.** Cek dilakukan per `outlet_id` **+** `user_id`, padahal komentarnya mengklaim per outlet | `app/Http/Requests/POS/OpenCashSessionRequest.php:58`, `CashSessionService.php:41` |
| Shift koreksi backdate | Dibuat berstatus `open` dan **tidak pernah ditutup** | `app/Services/BackdateSaleService.php:661` |
| Otorisasi | Middleware `role:kasir,admin`, belum pakai tabel `permissions` | `routes/web.php:681` |

### 1.1 Bug aktif yang PRD ini perbaiki

Kasir ambil Rp 50.000 dari laci → input di POS > Petty Cash:

- Akun `petty_cash` outlet berkurang Rp 50.000 → **padahal tidak ada uang keluar dari sana**.
- `expected_balance` laci tidak berubah → saat closing kasir **tercatat selisih kurang Rp 50.000**.

Dua buku salah ke arah berlawanan. Semakin sering dipakai, semakin melebar.

---

## 2. Model Data yang Disepakati

### 2.1 Laci kasir menjadi akun kas kelas satu

Tambah nilai baru pada `cash_accounts.usage_type`:

| `usage_type` | Arti | Perubahan |
|---|---|---|
| `operational` | Kas besar / brankas outlet, rekening bank | tetap |
| `petty_cash` | Kantong kas kecil outlet yang dipegang di luar laci (mis. dipegang SPV) | **tetap ada**, tidak dihapus |
| `pos_drawer` | **BARU** — laci kasir fisik, satu per outlet | baru |

> **Alasan `petty_cash` tidak dihapus:** ada outlet yang memang punya kantong terpisah, dan seluruh riwayat transaksi lama menempel di sana. Menghapusnya berarti merusak histori dan Laba Rugi periode lalu.

### 2.2 Perubahan skema

**`cash_sessions`** — tambah kolom (semua `NOT NULL DEFAULT 0` kecuali disebut, agar aman di MySQL strict mode):

| Kolom | Tipe | Arti |
|---|---|---|
| `total_cash_expense` | `decimal(15,2)` | Total pengeluaran **tunai dari laci** pada shift ini |
| `total_noncash_expense` | `decimal(15,2)` | Pengeluaran kios yang dibayar non-tunai — **tidak** masuk rumus laci |
| `retained_balance` | `decimal(15,2)` | Modal disisakan untuk shift berikutnya |
| `deposit_amount` | `decimal(15,2)` | Setoran — **hasil hitung sistem**, bukan input |
| `deposit_status` | `varchar(20)` default `pending` | `pending` / `handed_over` / `verified` |
| `drawer_cash_account_id` | `bigint` nullable | Snapshot akun laci yang dipakai shift ini |

**`cash_transactions`** — tambah kolom:

| Kolom | Tipe | Arti |
|---|---|---|
| `cash_session_id` | `bigint` nullable, index | Shift asal. Nullable karena transaksi admin tidak punya shift. |
| `payment_source` | `varchar(20)` default `drawer` | `drawer` / `noncash` / `personal_advance` |

**Tabel baru `cash_session_expenses`** — sumber kebenaran baris pengeluaran per shift:

| Kolom | Keterangan |
|---|---|
| `id`, `cash_session_id`, `outlet_id` | |
| `coa_account_id` | COA biaya, dipilih dari whitelist |
| `recipient_name`, `description` | |
| `amount` | `decimal(15,2)`, harus `> 0` |
| `payment_source` | `drawer` / `noncash` / `personal_advance` |
| `attachment_path` | nullable — foto nota |
| `status` | `posted` / `void` |
| `cash_transaction_id` | nullable — baris jurnal hasil posting |
| `idempotency_key` | unique — cegah dobel submit |
| `created_by`, `voided_by`, `void_reason`, timestamps | |

> **Kenapa tabel sendiri, bukan langsung `cash_transactions`?** Karena matematika shift butuh baris yang terikat shift, bisa di-void tanpa menghapus jejak, dan punya field operasional (foto nota, penerima) yang tidak layak dijejalkan ke deskripsi string seperti sekarang (`"Penerima: X | Y"` lalu di-`preg_match` balik — rapuh). `cash_transactions` tetap jadi buku besar; tabel ini jadi buku pembantu.

---

## 3. Alur Closing dan Posting Jurnal

Ini bagian paling penting. Tanpa posting selisih, buku laci **tidak akan pernah** sama dengan uang fisik.

### 3.1 Urutan yang dieksekusi saat closing (satu transaksi DB)

```
Diketahui:
  M  = opening_balance         (modal awal)
  P  = total_cash              (penerimaan tunai)
  K  = total_cash_expense      (pengeluaran tunai dari laci)
  F  = actual_balance          (hitung fisik — SATU-SATUNYA yang diinput kasir)
  R  = retained_balance        (modal disisakan — diinput/di-default kebijakan)

Hitung:
  expected_balance = M + P − K
  difference       = F − expected_balance
  deposit_amount   = F − R
```

Lalu posting ke `cash_transactions` pada akun laci:

| # | Baris | Tipe | Nominal | COA | Dibuat bila |
|---|---|---|---|---|---|
| 0 | Modal awal shift | `in` | selisih ke saldo buku laci | `1-190 Ayat Silang Kas/Bank` | saat shift dibuka, bila berbeda |
| 1 | Penerimaan tunai shift | `in` | `P` | `4-100 Pendapatan Penjualan` | `P > 0` |
| 2 | Pengeluaran kios | `out` | per baris | COA biaya per baris | ada baris `drawer` |
| 3 | Selisih kas | `in` / `out` | `\|difference\|` | `6-190 Selisih Kas` (beban) atau `4-900 Pendapatan Lain` (bila lebih) | `difference ≠ 0` |
| 4 | Setoran ke kas besar | `out` | `deposit_amount` | `1-190 Ayat Silang Kas/Bank` | `deposit_amount > 0` |
| 5 | Sisi lawan setoran | `in` | `deposit_amount` | `1-190 Ayat Silang Kas/Bank` | idem, di akun kas besar outlet |

> **Koreksi dari implementasi — baris 1 s/d 5 semuanya diposting saat CLOSING, bukan saat input.**
> Rancangan awal memposting pengeluaran secara real-time. Itu tidak bisa jalan: kas penjualan
> tidak masuk buku besar per struk, jadi selama shift berjalan saldo buku laci hanya berisi modal
> awal. Pengeluaran Rp 500.000 yang didanai penjualan akan membuat saldo buku minus dan ditolak
> `CashAccountService::applyTransaction()` — padahal uangnya jelas ada di laci.
>
> Selama shift berjalan, `cash_session_expenses` yang jadi catatannya. Closing menjadi satu-satunya
> peristiwa posting, dijalankan dalam satu transaksi DB dengan urutan penerimaan lebih dulu supaya
> saldo buku selalu cukup menanggung pengeluaran.
>
> **Konsekuensi yang harus diketahui:** pengeluaran pada shift yang masih terbuka belum muncul di
> Laba Rugi dan di Kas & Bank. Untuk shift harian normal ini tidak masalah, tapi shift yang
> menggantung berhari-hari akan menyembunyikan biayanya. Itu alasan tambahan kenapa force-close
> (SHIFT-11) wajib tersedia sejak awal.

### 3.2 Bukti bahwa identitas tertutup

```
Buku laci setelah #1 dan #2 : M + P − K            = expected
Setelah #3                  : expected + (F − expected) = F      ✓ sama dengan fisik
Setelah #4                  : F − (F − R)          = R           ✓ sama dengan modal disisakan
Opening shift berikutnya    : R                                  ✓ berantai tanpa celah
```

Baris #5 memakai pola ayat silang `1-190` yang **sudah ada** di `CashAccountService::resolveTransferClearingCoaAccountId()` — jadi tidak ada mekanisme baru yang perlu dibangun untuk sisi lawan.

### 3.3 Kenapa penerimaan tunai diposting agregat per shift, bukan per struk

Volume. Satu outlet bisa 300+ struk/hari; memposting per struk membuat `cash_transactions` membengkak dan halaman Kas & Bank tidak terbaca. Satu baris per shift sudah cukup untuk audit karena detail per struk tetap utuh di `sales` + `payments`, dan `cash_transactions` menyimpan `cash_session_id` sebagai jembatan telusur.

---

## 4. Katalog Kasus

Format tiap kasus: **Situasi → Aturan Sistem → Alasan.**

---

### 4.1 JUAL — Penjualan & Penerimaan

**JUAL-01 — Penjualan tunai normal**
*Situasi:* Kasir input penjualan dengan metode Cash.
*Aturan:* `total_cash += payment.amount`. Yang dipakai adalah `amount` (nilai bersih), **bukan** `tendered_amount`. `change_amount` tidak mengurangi apa pun karena kembalian sudah tercermin di selisih tendered−amount.
*Alasan:* Uang yang benar-benar tinggal di laci adalah nilai transaksi, bukan uang yang disodorkan pembeli.

**JUAL-02 — Penjualan non-tunai (QRIS/debit/transfer)**
*Situasi:* Pembayaran QRIS.
*Aturan:* Masuk `total_non_cash`. **Tidak** menambah saldo laci dan tidak masuk rumus expected.
*Alasan:* Uangnya tidak pernah masuk laci.

**JUAL-03 — Multi-payment (sebagian tunai, sebagian QRIS)**
*Situasi:* Total Rp 100.000 → Rp 40.000 tunai + Rp 60.000 QRIS.
*Aturan:* Pecah per baris `payments`. Hanya porsi tunai masuk `total_cash`.
*Alasan:* Sudah didukung struktur `payments`; jangan pernah pakai metode pembayaran pertama sebagai wakil seluruh transaksi.

**JUAL-04 — Deteksi metode tunai**
*Situasi:* Sistem perlu tahu mana yang tunai.
*Aturan:* Satu sumber kebenaran: kolom `payment_methods.is_cash`, **nullable tanpa default**. `NULL` berarti "belum ditentukan" dan jatuh ke heuristik nama lama; `true`/`false` adalah keputusan eksplisit yang menang. Migrasi menandai `true` untuk yang jelas tunai dan membiarkan sisanya `NULL`.

  > Kolom ini **tidak boleh** `NOT NULL DEFAULT false`. Kalau begitu, setiap metode pembayaran baru yang dibuat admin lahir sebagai "bukan tunai" tanpa ada yang memutuskan demikian — metode bernama "Tunai Kasir 2" akan membuat kas penjualan tidak terhitung dan memunculkan selisih palsu di setiap closing. Ini sempat terjadi saat implementasi dan tertangkap oleh test suite.
*Alasan:* Saat ini logika deteksi tersebar di `SaleService::isCashPaymentMethod()` dan inline di `BackdateSaleService::adjustCashSession()` dengan aturan berbeda. Komentar di kode itu sendiri mencatat bekas bug `id === 1` yang membuat QRIS terhitung kas. Deteksi berbasis string akan gagal lagi begitu ada metode bernama "Cashback".

**JUAL-05 — Void / refund tunai**
*Situasi:* Transaksi tunai dibatalkan, uang dikembalikan dari laci.
*Aturan:* **BUKAN pengeluaran kios.** `total_cash` berkurang, `total_cash_expense` tidak tersentuh. Dilarang keras diinput lewat menu Pengeluaran.
*Alasan:* Refund adalah kontra-pendapatan. Kalau dicatat sebagai biaya, pendapatan tetap tinggi dan beban ikut naik — laba rugi salah dua kali sekaligus.

**JUAL-06 — Pembulatan (`rounding_amount`)**
*Situasi:* Total dibulatkan ke bawah Rp 200.
*Aturan:* `payments.amount` sudah nilai setelah pembulatan; rumus laci tidak perlu penyesuaian.
*Alasan:* Pembulatan sudah terserap di nilai transaksi.

---

### 4.2 KLR — Pengeluaran Kios

**KLR-01 — Pengeluaran tunai dari laci (kasus utama)**
*Situasi:* Kasir ambil Rp 50.000 dari laci untuk beli galon.
*Aturan:* Buat `cash_session_expenses` dengan `payment_source = 'drawer'`, terikat shift aktif. `total_cash_expense` dihitung ulang dari baris detail. Posting ke `cash_transactions` (akun `pos_drawer`, COA biaya terpilih, `cash_session_id` terisi) dilakukan **saat closing**, lihat catatan koreksi di §3.1.
*Alasan:* Inilah yang membuat `expected_balance` turun sehingga kasir tidak lagi tercatat selisih kurang saat jujur.

**KLR-02 — Pengeluaran non-tunai**
*Situasi:* Kasir bayar galon pakai QRIS rekening outlet.
*Aturan:* `payment_source = 'noncash'`. Masuk `total_noncash_expense`. **Tidak** mengurangi `expected_balance`. Dibukukan `out` di akun bank/e-wallet terkait, bukan di laci.
*Alasan:* Rumus Anda menyebut "Pengeluaran **tunai**". Kalau non-tunai ikut memotong laci, laci akan kelihatan lebih kurang dari kenyataan.

**KLR-03 — Talangan uang pribadi kasir**
*Situasi:* Laci tidak cukup, kasir pakai uang sendiri Rp 30.000.
*Aturan:* Dua peristiwa terpisah. (a) Saat belanja: `payment_source = 'personal_advance'` → biaya diakui, **laci tidak berkurang**, muncul utang ke karyawan. (b) Saat diganti dari laci: baris penggantian `payment_source = 'drawer'` dengan COA **utang karyawan**, bukan COA biaya.
*Alasan:* Kalau digabung jadi satu baris drawer, laci berkurang di saat uangnya belum keluar dari laci. Kalau penggantian dicatat sebagai biaya lagi, beban dobel.

**KLR-04 — Pengeluaran melebihi uang di laci**
*Situasi:* Uang laci Rp 200.000, kasir input pengeluaran Rp 500.000.
*Aturan:* **Blokir**, tampilkan sisa uang laci real-time. Bisa dilanjutkan hanya oleh user ber-permission `pos.expense.override_limit`.
*Alasan:* Laci tidak bisa bersaldo negatif secara fisik. Blokir di titik input jauh lebih murah daripada investigasi saat closing.

**KLR-05 — Batas nominal per transaksi**
*Situasi:* Kasir input Rp 2.000.000 untuk "keperluan outlet".
*Aturan:* Ambang per outlet di `settings`. Di atas ambang → wajib otorisasi (pola token seperti `void_tokens` / diskon manual yang sudah ada).
*Alasan:* Petty cash tanpa plafon adalah lubang kebocoran paling umum di ritel.

**KLR-06 — Pilihan kategori biaya**
*Situasi:* Kasir perlu memilih jenis pengeluaran.
*Aturan:* Dropdown dari **whitelist** COA yang ditandai `is_pos_selectable = true`. Default `6-135 Biaya Keperluan Outlet Lainnya`. Kasir **tidak boleh** melihat seluruh COA.
*Alasan:* Saat ini semua dipaksa ke `6-135`, sehingga Laba Rugi tidak bisa membedakan gas, galon, dan parkir. Tapi membuka seluruh COA ke kasir memindahkan risiko salah posting ke orang yang paling tidak paham akuntansi.

**KLR-07 — Foto nota**
*Situasi:* Pengeluaran perlu bukti.
*Aturan:* `attachment_path` opsional di bawah ambang tertentu, **wajib** di atasnya.
*Alasan:* Wajib foto untuk pengeluaran Rp 5.000 akan membuat kasir berhenti mencatat sama sekali — dan pengeluaran yang tidak dicatat jauh lebih merusak daripada yang tidak berfoto.

**KLR-08 — Koreksi pengeluaran saat shift masih terbuka**
*Situasi:* Kasir salah ketik nominal, shift belum ditutup.
*Aturan:* Boleh diperbaiki. Implementasi: **void baris lama + buat baris baru**, bukan `UPDATE` di tempat. Kedua baris tampil di jejak audit.
*Alasan:* Update di tempat menghapus bukti nominal asli. Untuk uang tunai, angka yang pernah tercatat adalah fakta yang harus bisa ditelusuri.

**KLR-09 — Koreksi setelah shift ditutup**
*Situasi:* Salah nominal baru ketahuan keesokan hari.
*Aturan:* **Kasir tidak bisa.** Hanya admin/manager, lewat jurnal koreksi bertanggal hari ini di modul Kas & Bank. `expected_balance`, `difference`, dan `deposit_amount` shift lama **tidak diubah**.
*Alasan:* Konsisten dengan aturan yang sudah Anda tegakkan di `SaleService.php:585` dan `BackdateSaleService.php:676` — `actual_balance` adalah hasil hitung fisik, sebuah fakta historis. Menaikkan `expected_balance` retroaktif memunculkan selisih kas palsu pada shift yang sebenarnya sudah pas. **Ini menutup lubang nyata di `PettyCashController::update()` yang hari ini tidak mengecek status shift sama sekali.**

**KLR-10 — Pengeluaran tanpa shift aktif**
*Situasi:* User buka menu Pengeluaran padahal belum buka shift.
*Aturan:* Blokir dengan pesan "Buka shift dulu". Pengeluaran outlet di luar shift adalah wewenang admin lewat Kas & Bank.
*Alasan:* Setiap rupiah keluar dari laci harus punya shift penanggung jawab. Tanpa itu, selisih tidak bisa dialamatkan ke siapa pun.

---

### 4.3 SHIFT — Shift & Closing

**SHIFT-01 — Buka shift, modal awal**
*Situasi:* Kasir membuka shift.
*Aturan:* `opening_balance` **di-prefill** dari `retained_balance` shift terakhir yang closed di outlet itu, dan bersifat read-only. Mengubahnya butuh permission `pos.session.override_opening` + alasan wajib.
*Alasan:* Hari ini `opening_balance` adalah input bebas. Selama ia bebas, seluruh rantai modal terputus dan siapa pun bisa menutup selisih kemarin dengan mengetik ulang modal hari ini.

**SHIFT-02 — Shift pertama outlet / setelah rantai terputus**
*Situasi:* Belum ada shift closed sebelumnya.
*Aturan:* Input manual diizinkan, ditandai `opening_source = 'manual'` di catatan.
*Alasan:* Harus ada titik awal, tapi titik awal itu harus terlihat sebagai pengecualian di laporan.

**SHIFT-03 — Layar closing**
*Situasi:* Kasir menutup shift.
*Aturan:* Layar menampilkan **berurutan dan tidak bisa diedit** kecuali dua field:

```
  Modal awal                      M      (readonly)
  Penerimaan tunai              + P      (readonly)
  Pengeluaran tunai             − K      (readonly, klik → rincian)
  ──────────────────────────────────
  Saldo laci seharusnya         = M+P−K  (readonly)

  Hitung fisik                    F      ← INPUT
  Selisih kas                   = F−(M+P−K)  (readonly, live)

  Modal disisakan                 R      ← INPUT (default kebijakan)
  ──────────────────────────────────
  SETORAN                       = F−R    (readonly, live)
```

*Alasan:* Hanya `F` dan `R` yang merupakan keputusan manusia. Semua sisanya turunan. Menampilkan setoran sebagai field yang bisa diketik akan mengundang kasir "menyesuaikan" agar selisih nol.

**SHIFT-04 — `actual_balance` tidak boleh di-prefill — DIBATALKAN atas keputusan eksplisit (2026-08-05)**
*Situasi:* Form closing dimuat.
*Aturan desain semula:* Field hitung fisik **kosong**, `required`, `min=0`.
*Alasan desain semula:* Kalau field ini diisi otomatis dengan `expected_balance`, kasir yang buru-buru tinggal klik submit → selisih selalu nol dan seluruh mekanisme rekonsiliasi jadi teater.

  > **Keputusan aktual:** Setelah implementasi berjalan, pemilik sistem secara eksplisit meminta field ini **dikembalikan ke perilaku prefill** (`value = expected_balance`, sama seperti sebelum PRD ini), sejajar dengan default `retained_balance = opening_balance`. Risiko di atas sudah disampaikan sebelum perubahan dilakukan dan tetap dipilih dengan sadar. Implementasinya ada di `close.blade.php` pada input `actual_balance`.
  >
  > **Konsekuensi yang perlu diawasi:** karena field ini sekarang prefilled, sistem **tidak bisa lagi membedakan** "kasir menghitung fisik dan kebetulan pas" dari "kasir tidak menghitung sama sekali dan langsung submit" — keduanya sama-sama menghasilkan selisih Rp 0. Kalau di kemudian hari muncul kebutuhan untuk tetap tahu mana yang mana, opsi paling murah adalah menambah flag `physical_count_confirmed` yang di-set oleh JS hanya saat user benar-benar mengubah nilai field (bukan default value pindah begitu saja).

**SHIFT-05 — Rincian denominasi (opsional, direkomendasikan)**
*Situasi:* Kasir menghitung uang fisik.
*Aturan:* Sediakan kalkulator pecahan (100rb × n, 50rb × n, …) yang menjumlah ke `actual_balance`. Simpan rinciannya.
*Alasan:* Menaikkan akurasi hitung dan memberi bukti bahwa kasir benar-benar menghitung, bukan menyalin angka expected.

**SHIFT-06 — Selisih di atas ambang toleransi**
*Situasi:* Selisih kurang Rp 150.000, toleransi outlet Rp 20.000.
*Aturan:* Catatan **wajib** diisi. Shift tetap bisa ditutup, tapi ditandai `needs_review` dan muncul di dashboard admin.
*Alasan:* Memblokir closing akan membuat kasir memanipulasi `actual_balance` agar bisa pulang. Lebih baik ditutup jujur lalu ditinjau.

**SHIFT-07 — Selisih lebih (uang berlebih)**
*Situasi:* Fisik lebih Rp 30.000 dari seharusnya.
*Aturan:* Diperlakukan simetris — diposting `in` ke COA pendapatan lain-lain, tetap dilaporkan sebagai selisih.
*Alasan:* Selisih lebih biasanya berarti ada transaksi yang tidak diinput. Itu masalah yang sama seriusnya dengan selisih kurang, bukan kabar baik.

**SHIFT-08 — Satu outlet, satu kasir, satu laci (ASUMSI DASAR — DIKONFIRMASI)**
*Situasi:* Model operasional yang berlaku di seluruh outlet.
*Aturan:* **Satu outlet hanya boleh punya satu shift operasional terbuka pada satu waktu.** Ditegakkan sebagai invariant, bukan sekadar konvensi (lihat SHIFT-10). Akun `pos_drawer` dipetakan 1:1 ke outlet.
*Alasan:* Dikonfirmasi pemilik sistem: satu outlet = satu kasir = satu laci. Ini yang membuat seluruh rumus di PRD ini valid — `expected_balance` sebuah shift hanya bermakna kalau shift itu menguasai lacinya secara eksklusif. Karena asumsi ini sekarang menjadi fondasi, ia **wajib dipaksakan oleh sistem**; asumsi yang tidak ditegakkan akan dilanggar cepat atau lambat, dan pelanggarannya tidak akan terlihat sampai angka kas sudah rusak berminggu-minggu.

**SHIFT-09 — Serah terima antar kasir dalam satu hari**
*Situasi:* Kasir pagi digantikan kasir siang di laci yang sama.
*Aturan:* Bukan pergantian di tengah shift — kasir pagi **closing penuh** (hitung fisik, setoran, sisa modal), lalu kasir siang membuka shift baru dengan `opening_balance` = `retained_balance` shift pagi.
*Alasan:* Satu kasir per laci pada satu waktu tetap berarti bisa ada beberapa shift berurutan dalam sehari. Serah terima lewat closing penuh membuat tanggung jawab tiap kasir terpisah tegas: selisih kasir pagi tidak bisa terbawa ke kasir siang.

**SHIFT-10 — Penegakan satu shift terbuka per outlet**
*Situasi:* Kasir B mencoba buka shift saat shift kasir A di outlet yang sama masih terbuka.
*Aturan:* **Blokir.** Cek di `openSession()` diubah dari per-`user_id` menjadi per-`outlet_id`, dengan `lockForUpdate()` pada baris outlet (bukan baris user). Pengecualian wajib: `session_type = 'backdate_correction'` **dikecualikan** dari aturan ini.
*Alasan:* Dua hal yang harus diperbaiki bersamaan:

  1. `OpenCashSessionRequest.php:58` berkomentar *"Cek apakah sudah ada session yang open untuk outlet ini"* tetapi query-nya memfilter `outlet_id` **dan** `user_id` sekaligus — jadi tidak melakukan apa yang komentarnya klaim. `CashSessionService.php:41` juga hanya mengunci baris user. Akibatnya dua kasir di outlet sama bisa punya shift terbuka bersamaan. Hari ini dampaknya "hanya" rekap ganda; setelah laci punya buku besar, dampaknya dua shift sama-sama mengklaim satu laci fisik dan `expected_balance` keduanya salah tanpa ada yang bisa dipercaya.
  2. `BackdateSaleService::getCorrectionSession()` membuat shift `backdate_correction` berstatus `open` dan **tidak pernah menutupnya**. Aturan "satu shift terbuka per outlet" yang polos akan langsung mematikan input backdate begitu satu shift koreksi pernah dibuat. Pengecualian ini bukan opsional. Pola pengecualiannya sudah ada di `BackdateSaleService.php:610` dan `BackdateSaleController.php:211` yang memakai `where('session_type', '!=', 'backdate_correction')` — pakai predikat yang sama.

**SHIFT-11 — Shift lupa ditutup**
*Situasi:* Shift kemarin masih `open` pagi ini.
*Aturan:* Blokir buka shift baru. Admin melakukan force-close dengan `actual_balance` hasil hitung, ditandai `closed_by_admin` + alasan wajib. Force-close **wajib tersedia sejak Tahap 0**, bukan ditunda ke Tahap 4.
*Alasan:* Setelah SHIFT-10 berlaku, blokirnya menjadi **selevel outlet, bukan selevel user** — kasir mana pun tidak bisa mulai bekerja sampai shift yang tertinggal ditutup. Jadi shift yang lupa ditutup berubah dari gangguan satu orang menjadi outlet berhenti beroperasi. Force-close karenanya naik dari fitur pelengkap menjadi prasyarat: menegakkan invariant tanpa menyediakan jalan keluarnya sama saja memasang kunci tanpa kunci cadangan. Tapi tanpa penandaan, force-close jadi jalan pintas untuk menghapus selisih — karena itu wajib beralasan dan teraudit.

**SHIFT-12 — Shift koreksi backdate**
*Situasi:* `session_type = 'backdate_correction'` dibuat otomatis oleh `BackdateSaleService`.
*Aturan:* Menu Pengeluaran **tidak berlaku** untuk shift jenis ini. Shift koreksi tidak punya laci fisik (`opening_balance = 0`), tidak punya closing, tidak menghasilkan setoran.
*Alasan:* Shift koreksi adalah wadah pembukuan, bukan shift operasional. Kalau pengeluaran boleh menempel di sana, ia akan menghasilkan setoran negatif yang tidak punya arti fisik.

---

### 4.4 SETOR — Setoran

**SETOR-01 — Setoran normal**
*Situasi:* Fisik Rp 3.000.000, modal disisakan Rp 300.000.
*Aturan:* `deposit_amount = 2.700.000`, `deposit_status = 'pending'`. Diposting keluar dari akun laci masuk ke kas besar outlet lewat ayat silang `1-190`.
*Alasan:* Memakai pola pindah buku yang sudah ada, sehingga tidak ada baris berkolom COA kosong di Kas & Bank.

**SETOR-02 — Setoran menyerap selisih**
*Situasi:* Seharusnya Rp 3.050.000, fisik Rp 3.000.000, disisakan Rp 300.000.
*Aturan:* Selisih dilaporkan **kurang Rp 50.000**. Setoran tetap Rp 2.700.000 (`F − R`), bukan `expected − R`.
*Alasan:* Ini konsekuensi yang benar dan disengaja dari rumus. Yang bisa disetor hanyalah uang yang benar-benar ada. Kalau setoran dihitung dari expected, sistem akan mengklaim uang yang tidak eksis, dan kas besar akan selalu kurang tanpa penjelasan. Selisih tetap muncul sebagai angka tersendiri di baris jurnal #3 — jadi diserap ke setoran **tetapi tidak disembunyikan**.

**SETOR-03 — Modal disisakan melebihi uang fisik**
*Situasi:* Fisik Rp 200.000, kasir isi disisakan Rp 300.000.
*Aturan:* Blokir. `R` harus `≤ F`. Setoran tidak boleh negatif.
*Alasan:* Tidak bisa menyisakan uang yang tidak ada.

**SETOR-04 — Semua uang disetor**
*Situasi:* `R = 0`.
*Aturan:* Diizinkan. Shift berikutnya harus input modal manual (SHIFT-02).
*Alasan:* Sebagian outlet menyetor bersih tiap hari dan mengambil modal pagi dari brankas.

**SETOR-05 — Verifikasi penerimaan setoran**
*Situasi:* Setoran diserahkan ke SPV/owner.
*Aturan:* SPV menandai `verified` dengan nominal diterima. Kalau berbeda dari `deposit_amount` → catat sebagai **selisih serah terima** (baris tersendiri), jangan ubah nilai setoran shift.
*Alasan:* Selisih di laci dan selisih di serah terima adalah dua kejadian berbeda dengan penanggung jawab berbeda. Menggabungkannya membuat keduanya tidak bisa diinvestigasi.

**SETOR-06 — Setoran belum diverifikasi berhari-hari**
*Situasi:* `deposit_status` masih `pending` 3 hari.
*Aturan:* Muncul di dashboard admin sebagai kas dalam perjalanan.
*Alasan:* Saldo ayat silang `1-190` yang tidak nol dalam waktu lama adalah indikator klasik uang tertahan atau hilang.

---

### 4.5 WKT — Waktu & Periode

**WKT-01 — Shift melewati tengah malam**
*Situasi:* Shift buka 18:00, tutup 02:00 keesokan hari.
*Aturan:* Pengeluaran diikat ke `cash_session_id`, **bukan** ke tanggal.
*Alasan:* Index petty cash sekarang memakai `whereDate(transaction_date, today)` (`PettyCashController.php:47`). Lewat tengah malam, pengeluaran yang baru diinput langsung menghilang dari layar kasir sementara nominalnya sudah memotong saldo. Ikatan ke shift menghilangkan seluruh kelas bug ini.

**WKT-02 — Tanggal pembukuan pengeluaran**
*Situasi:* Perlu menentukan tanggal untuk Laba Rugi.
*Aturan:* `transaction_date` = `business_date` shift, **diisi sistem**, tidak bisa diubah kasir. Simpan juga `created_at` presisi detik.
*Alasan:* Saat ini `transaction_date` divalidasi dari input user (`StorePettyCashPosRequest`) dan bertipe `date`, sehingga kasir bisa memindahkan biaya ke periode lain dan urutan dalam satu hari tidak bisa direkonstruksi.

**WKT-03 — Periode akuntansi sudah dikunci**
*Situasi:* Koreksi menyangkut bulan yang laporannya sudah terbit.
*Aturan:* Jurnal koreksi bertanggal periode berjalan, dengan referensi ke shift asal.
*Alasan:* Mengubah periode tertutup membatalkan laporan yang sudah dipakai mengambil keputusan.

**WKT-04 — Zona waktu**
*Situasi:* Perangkat POS salah setel jam.
*Aturan:* Seluruh cap waktu dari server, bukan perangkat.
*Alasan:* Perangkat kasir bisa diubah jamnya oleh siapa saja.

---

### 4.6 STK — Interaksi dengan Stok

**STK-01 — Petty cash untuk membeli barang dagangan**
*Situasi:* Kasir beli 5 kg ayam Rp 250.000 dari laci.
*Aturan jangka panjang (belum dikerjakan):* **Tidak boleh lewat menu Pengeluaran biasa.** COA barang dagangan/bahan baku dikeluarkan dari whitelist POS. Alurnya idealnya: buat Purchase (bisa disederhanakan jadi "Pembelian Cepat Outlet") yang menaikkan stok **dan** memotong laci lewat `cash_session_expenses` dengan referensi ke purchase tersebut.
*Alasan:* **Ini risiko terbesar di modul ini.** Kalau dicatat sebagai beban `6-135`: stok tidak naik → saat ayam terjual, HPP diambil dari stok yang tidak ada → stok minus dan HPP salah, sementara Rp 250.000 sudah masuk beban operasional. Laba rugi salah di dua baris sekaligus (HPP terlalu rendah, beban operasional terlalu tinggi), dan neraca kehilangan persediaan. Kalau kemudian admin juga menginput purchase untuk barang yang sama, biayanya dobel.

> **Jalan pintas yang diimplementasikan (2026-08-05), atas keputusan eksplisit pemilik sistem:** dibuat kategori COA `1-145 Belanja Stok/Barang Dagangan (Petty Cash)` bertipe `asset` (bukan `expense`), sengaja **tidak** terintegrasi ke modul stok. Kasir bisa mencatat "uang ini dipakai belanja stok" sebagai catatan, uang tetap terpotong dari laci secara akurat, tapi nominalnya **tidak** mengurangi laba di Laba Rugi maupun kartu Laba Owner — karena secara akuntansi membeli stok memang memindahkan aset dari kas ke barang, bukan beban.
>
> Nama & kode akun sengaja menghindari kata "persedia"/"inventory" dan prefix `1-13x`, karena `CatalogReportController::inventoryReconciliationReport()` mengumpulkan semua akun asset yang namanya mengandung kata itu sebagai "Nilai Persediaan Neraca" dan membandingkannya dengan nilai stok riil. Kalau akun ini dinamai "Persediaan", ia akan ikut tersapu ke laporan itu dan memunculkan selisih neraca palsu tiap bulan (uangnya tercatat sebagai persediaan, tapi stok fisiknya tidak pernah naik).
>
> **Konsekuensi yang perlu diketahui:** karena stok tidak naik dan HPP tidak tercatat, kategori ini **hanya cocok untuk pencatatan kasar** ("owner tahu ada uang yang lari ke belanja stok"), bukan pengganti pembelian resmi. Kalau barang yang dibeli lewat sini nanti terjual di POS, HPP-nya tetap diambil dari data stok/cost yang ada — kalau stok itu tidak pernah dimasukkan lewat jalur lain, HPP-nya akan salah atau stok jadi minus, persis risiko yang dijelaskan di paragraf Alasan di atas. Risiko ini sudah disampaikan sebelum implementasi dan tetap dipilih dengan sadar sebagai solusi sementara.

**STK-02 — Petty cash untuk barang habis pakai non-stok**
*Situasi:* Beli sabun cuci, tisu, plastik non-packaging.
*Aturan:* Boleh sebagai beban langsung.
*Alasan:* Tidak pernah masuk perhitungan HPP, tidak dilacak stoknya.

**STK-03 — Petty cash untuk item packaging yang dilacak**
*Situasi:* Beli kardus yang terdaftar di `packaging_items`.
*Aturan:* Harus lewat mekanisme penerimaan packaging (`packaging_adjustments`, status `pending` approval), bukan menu Pengeluaran biasa. Kas tetap keluar dari laci lewat `cash_session_expenses` yang direferensikan ke adjustment tersebut.
*Alasan:* Modul kontrol stok packaging per shift sudah ada dan menghitung terpakai/estimasi/selisih. Menambah kardus tanpa lewat jalur itu langsung merusak angka selisih packaging shift tersebut.

---

### 4.7 TEK — Teknis

**TEK-01 — Dobel submit**
*Situasi:* Kasir klik Simpan dua kali / jaringan lambat.
*Aturan:* `idempotency_key` unique di `cash_session_expenses`, digenerate di klien saat form dibuka. Submit kedua mengembalikan baris yang sama.
*Alasan:* Pola ini sudah dipakai di `sales.idempotency_key`. Dobel pengeluaran akan langsung muncul sebagai selisih lebih saat closing.

**TEK-02 — Race condition input vs closing**
*Situasi:* Kasir menyimpan pengeluaran tepat saat closing diproses.
*Aturan:* `closeSession()` melakukan `lockForUpdate()` pada baris shift, lalu **menghitung ulang** `total_cash_expense` dari `SUM(cash_session_expenses)` di dalam transaksi — bukan memakai nilai kolom yang sudah tersimpan.
*Alasan:* Kolom agregat yang di-`+=` seperti sekarang bisa melenceng dari jumlah baris sebenarnya. Menghitung ulang saat closing membuat kolom agregat sekadar cache, dan angka final selalu bisa dibuktikan dari baris detailnya.

**TEK-03 — Konsistensi transaksi database**
*Situasi:* Posting closing melibatkan 5 baris jurnal + update shift.
*Aturan:* Satu `DB::transaction()`. Gagal sebagian = rollback penuh.
*Alasan:* Setoran tanpa sisi lawan akan membuat ayat silang `1-190` menggantung permanen.

**TEK-04 — Perhitungan ulang saldo akun**
*Situasi:* Ada jurnal koreksi bertanggal mundur di akun laci.
*Aturan:* Panggil `CashAccountService::recalculateBalances($account, $fromDate)` yang sudah ada.
*Alasan:* `balance_before` / `balance_after` di `cash_transactions` bersifat berantai; tanpa recalculate, seluruh baris sesudahnya salah.

**TEK-05 — Permission granular**
*Situasi:* Tidak semua kasir boleh semua hal.
*Aturan:* Tambah ke tabel `permissions`: `pos.expense.view`, `pos.expense.create`, `pos.expense.void`, `pos.expense.override_limit`, `pos.session.override_opening`, `pos.deposit.verify`.
*Alasan:* Sekarang aksesnya `role:kasir,admin` di route. Sistem sudah punya tabel `permissions` + `role_permissions` + `user_permissions`; petty cash adalah area paling sensitif untuk tetap ditinggal di luar sistem itu.

**TEK-06 — Jejak audit**
*Situasi:* Perlu telusur siapa melakukan apa.
*Aturan:* Log via `ActivityLogger` untuk create/void pengeluaran, closing, override modal awal, force-close, dan verifikasi setoran. Rekam `pos_device_id` dan IP (kolomnya sudah ada di `cash_sessions`).
*Alasan:* Modul yang menyentuh uang tunai tanpa jejak audit tidak bisa dipakai untuk menegakkan tanggung jawab.

**TEK-07 — MySQL strict mode**
*Situasi:* Deploy ke produksi.
*Aturan:* Semua kolom baru punya default eksplisit; kolom numerik `NOT NULL DEFAULT 0`, bukan nullable.
*Alasan:* Produksi berjalan dengan strict mode; kolom numerik nullable akan memunculkan `NULL` di rumus dan membuat seluruh perhitungan jadi `NULL`.

**TEK-08 — Struk closing**
*Situasi:* Kasir mencetak laporan shift.
*Aturan:* Struk memuat blok penuh: modal awal, penerimaan, pengeluaran (dengan rincian baris), saldo seharusnya, hitung fisik, selisih, modal disisakan, setoran. Sediakan kolom tanda tangan penyetor dan penerima.
*Alasan:* Struk closing adalah bukti serah terima fisik. Kalau ia tidak memuat setoran, serah terima uang tidak punya dokumen pendamping.

**TEK-09 — Perilaku offline**
*Situasi:* Jaringan putus saat input pengeluaran.
*Aturan:* Gagalkan secara eksplisit dengan pesan jelas. **Tidak ada antrean offline** untuk pengeluaran di rilis ini.
*Alasan:* Antrean offline untuk kas tunai memerlukan resolusi konflik yang benar; setengah jadi lebih berbahaya daripada tidak ada.

**TEK-10 — Kompatibilitas data historis**
*Situasi:* Ada ribuan baris `reference_type = 'petty_cash_pos'` lama di akun `petty_cash`.
*Aturan:* **Jangan dipindahkan, jangan dihitung ulang.** Tetapkan tanggal cutover; baris sebelum cutover tetap di akun lama apa adanya. Laporan yang memotong periode cutover diberi anotasi.
*Alasan:* Tidak ada cara mengetahui secara retroaktif mana pengeluaran lama yang uangnya benar-benar dari laci dan mana dari kantong terpisah. Menebak lalu menulis ulang riwayat kas akan merusak data yang saat ini setidaknya konsisten secara internal.

**TEK-11 — Perlakuan modul `expenses` yang sudah ada**
*Situasi:* Ada tabel `expenses` dengan alur approval dan lampiran.
*Aturan:* **Tidak dipakai** untuk pengeluaran POS. `cash_session_expenses` + `cash_transactions` yang jadi jalur resminya.
*Alasan:* `ExpenseService` sudah memposting ke `cash_transactions` saat approve. Memakai `expenses` untuk pengeluaran kasir berarti mempertahankan dua jalur menuju buku besar yang sama dengan aturan approval berbeda — dan Laba Rugi (`ProfitLossReportService.php:61`) membaca `cash_transactions`, sehingga pengeluaran yang tertahan `pending` di `expenses` akan tak terlihat sama sekali sampai di-approve. Untuk uang yang sudah fisik keluar dari laci, "belum terlihat" bukan status yang boleh ada.

---

## 5. Dampak ke Sistem

**Database.** Enam kolom baru di `cash_sessions`, dua di `cash_transactions`, satu tabel baru, satu kolom `is_cash` di `payment_methods`, satu kolom `is_pos_selectable` di `coa_accounts`. Satu akun `pos_drawer` per outlet dan dua COA baru (selisih kas, utang karyawan). Tidak ada perubahan destruktif.

**UI.** Layar closing dirombak (SHIFT-03, SHIFT-04). Menu POS > Petty Cash berganti nama jadi **Pengeluaran Kios** dengan form baru. Dashboard admin dapat kartu setoran pending dan shift `needs_review`.

**Laporan.** Laba Rugi mulai memisahkan biaya per COA, bukan menumpuk di `6-135`. Laporan shift dapat kolom pengeluaran, setoran, dan selisih. Muncul laporan rekonsiliasi setoran.

**Audit trail.** Bertambah signifikan — inilah tujuannya. Void-lalu-buat-baru menggantikan update di tempat.

**Permission.** Enam permission baru; route berpindah dari cek `role` ke cek permission.

**Performa.** Posting agregat per shift menahan pertumbuhan `cash_transactions` di kisaran 5 baris/shift, bukan ratusan. Perlu index `(cash_session_id)` dan `(cash_account_id, transaction_date)`.

**Operasional.** Perubahan kebiasaan yang nyata bagi kasir: hitung fisik tidak lagi diisikan otomatis, dan modal awal tidak lagi bisa diketik bebas. Perlu sosialisasi, karena SHIFT-04 akan langsung memunculkan selisih yang selama ini tersembunyi. **Antisipasi: minggu pertama setelah rilis akan terlihat seperti lonjakan masalah. Itu bukan regresi — itu masalah yang selama ini tidak terukur.**

---

## 6. Rencana Implementasi

**Tahap 0 — Persiapan & penegakan invariant**
Migrasi kolom & tabel baru dengan default aman. Seed COA selisih kas + utang karyawan. Tandai whitelist COA POS. Buat akun `pos_drawer` per outlet dengan saldo awal 0. Isi `payment_methods.is_cash`. Satukan deteksi tunai ke satu helper (JUAL-04).

Lalu — **sepasang, tidak boleh dipisah** — tegakkan satu shift terbuka per outlet (SHIFT-10) **bersama** force-close teraudit (SHIFT-11). Sebelum menyalakan penegakannya, jalankan audit data: cari outlet yang saat ini punya lebih dari satu shift operasional terbuka, dan tutup manual dulu. Kalau tidak, migrasi menyala di atas data yang sudah melanggar invariant dan outlet-outlet itu langsung terkunci di pagi pertama.

*Selain penegakan di atas, sistem masih berperilaku persis seperti sekarang — rumus kas belum berubah.*

**Tahap 1 — Pencatatan pengeluaran**
Bangun `cash_session_expenses` + layar Pengeluaran Kios baru (KLR-01, 02, 04, 06, 07, 08, 10). Isi `total_cash_expense`, **tapi belum masukkan ke rumus expected**. Jalankan paralel di satu outlet percontohan minimal 1 minggu, bandingkan angkanya dengan hitungan manual.

**Tahap 2 — Aktifkan rumus (cutover)**
Ubah `expected_balance` jadi `M + P − K`. Rombak layar closing (SHIFT-03, SHIFT-04). Aktifkan posting jurnal #1–#3. Tetapkan tanggal cutover (TEK-10). Nonaktifkan menu petty cash lama untuk kasir. **Titik tanpa balik — butuh persetujuan eksplisit dan komunikasi ke seluruh outlet.**

**Tahap 3 — Setoran**
`retained_balance`, `deposit_amount`, posting jurnal #4–#5, prefill modal awal (SHIFT-01), layar verifikasi setoran (SETOR-05), struk closing baru (TEK-08).

**Tahap 4 — Pengetatan**
Permission granular (TEK-05), ambang otorisasi (KLR-05), jalur pembelian stok (STK-01, STK-03), dashboard `needs_review`.

**Tahap 5 — Penajaman**
Kalkulator denominasi (SHIFT-05), talangan pribadi (KLR-03), laporan tren selisih per kasir.

---

## 6b. Status Implementasi

**Sudah jalan** (di balik flag `pos_drawer_expense_enabled`, default mati):
migrasi skema & seed COA/permission/akun laci; `cash_session_expenses` + service; layar
Pengeluaran Kios (input, daftar, void beralasan); rumus `expected_balance` baru; layar closing
dengan modal disisakan & setoran live; posting jurnal closing lengkap termasuk selisih dan ayat
silang setoran; rantai `retained → opening`; penyatuan deteksi tunai (JUAL-04); idempotensi
(TEK-01); penguncian baris shift (TEK-02).

**Belum dikerjakan** — perlu dijadwalkan sebelum cutover sungguhan:

| Kasus | Kenapa penting |
|---|---|
| SHIFT-01 prefill modal awal di UI | Service-nya sudah ada (`suggestedOpeningBalance()`), formnya belum memakainya — modal awal masih bisa diketik bebas |
| SHIFT-10 + SHIFT-11 | Harus rilis berpasangan; belum dikerjakan supaya tidak mengunci outlet |
| KLR-02 & KLR-03 posting | Pengeluaran non-tunai dan talangan tercatat di buku pembantu tapi belum diposting ke buku besar, jadi belum masuk Laba Rugi |
| KLR-07 unggah foto nota | Kolomnya sudah ada, UI unggahnya belum |
| SETOR-05 verifikasi setoran | `deposit_status` selalu `pending` |
| STK-01 & STK-03 | Jalur pembelian stok lewat petty cash belum dibuat; sementara ini dicegah lewat whitelist COA saja |
| SHIFT-05 kalkulator denominasi | Penajaman |
| TEK-08 struk closing | Struk belum memuat blok pengeluaran/setoran |

## 7. Catatan Penting

1. **Yang paling sering terlewat: SHIFT-04.** Selama field hitung fisik masih di-prefill dengan nilai expected, seluruh modul ini tidak menghasilkan informasi apa pun. Ini perubahan satu baris dengan dampak terbesar di seluruh dokumen.

2. **Modal awal yang bisa diketik bebas membatalkan semuanya.** Rantai `retained → opening` adalah yang membuat selisih tidak bisa dipindahkan diam-diam ke hari berikutnya. SHIFT-01 bukan fitur tambahan, ia bagian dari integritas rumus.

3. **STK-01 adalah lubang yang paling mahal.** Pembelian bahan baku lewat petty cash yang dibukukan sebagai beban akan merusak HPP, stok, dan neraca sekaligus — dan kerusakannya menumpuk diam-diam selama berbulan-bulan sebelum ketahuan.

4. **Selisih kas wajib punya baris jurnalnya sendiri.** Kalau tidak, saldo buku akun laci tidak akan pernah sama dengan uang fisik, dan Tahap 3 akan mewarisi dasar yang tidak bisa direkonsiliasi.

5. **Jangan tulis ulang riwayat (TEK-10).** Data lama tidak punya informasi yang dibutuhkan untuk dimigrasikan dengan benar. Garis cutover yang jujur lebih baik daripada migrasi yang menebak.

6. **Asumsi "satu outlet, satu kasir, satu laci" sudah dikonfirmasi — dan justru karena itu harus ditegakkan mesin.** Asumsi yang benar hari ini tetap akan dilanggar suatu saat: outlet ramai menambah kasir kedua, atau seseorang lupa menutup shift lalu login dari perangkat lain. Bedanya, sekarang seluruh rumus kas bergantung padanya, sehingga pelanggarannya tidak lagi menghasilkan sekadar rekap ganda melainkan angka laci yang salah tanpa ada yang tahu shift mana yang benar. SHIFT-10 mengubahnya dari kesepakatan lisan menjadi aturan yang tidak bisa dilanggar. Dua hal yang menyertainya tidak boleh dilupakan: **kecualikan `backdate_correction`**, dan **sediakan force-close bersamaan** — menegakkan invariant tanpa jalan keluar akan mengunci outlet di pagi pertama.

7. **Angka bertambah, kepercayaan belum.** Modul ini menghasilkan angka selisih yang bisa dipertanggungjawabkan. Ia tidak menentukan apa konsekuensinya bagi kasir yang selisih. Kebijakan itu keputusan manajemen, dan sebaiknya sudah diputuskan sebelum Tahap 2 — kalau tidak, kasir akan menebak sendiri dan mulai menyesuaikan angka.
