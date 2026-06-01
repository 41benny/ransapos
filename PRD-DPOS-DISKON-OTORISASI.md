# PRD: Diskon dPOS dengan Kode Otorisasi (Tanpa Voucher/Promo Setup)

## 1) Ringkasan
Fitur ini memungkinkan kasir memberikan diskon langsung dari halaman dPOS pada saat transaksi berjalan, tanpa membuat voucher/promo terlebih dahulu.  
Diskon hanya bisa diterapkan setelah verifikasi kode otorisasi/PIN supervisor.

Target utama:
- Proses kasir lebih cepat untuk kasus diskon ad-hoc.
- Kontrol tetap ketat melalui otorisasi, limit, dan audit trail.
- Nilai diskon tetap masuk ke struktur laporan yang sudah existing, khususnya **Potongan Penjualan** dan report katalog **Sales Discount (tab Penjualan)**.

## 2) Latar Belakang Masalah
- User operasional sering butuh diskon situasional (komplain, loyal customer, penyesuaian harga cepat).
- Mekanisme voucher/promo existing cocok untuk campaign terencana, namun kurang fleksibel untuk kasus kasir harian.
- Saat ini user ingin diskon bisa dilakukan langsung lewat tombol diskon di dPOS dengan guard otorisasi.

## 3) Tujuan Produk
1. Menyediakan diskon manual di dPOS yang aman dan cepat.
2. Menjaga integritas laporan keuangan/penjualan (tanpa memecah logika laporan existing).
3. Menyediakan jejak audit lengkap untuk monitoring potensi penyalahgunaan.

## 4) Non-Goals
- Tidak menggantikan modul voucher/promo existing.
- Tidak membangun approval multi-level kompleks pada fase awal.
- Tidak mengubah struktur utama tampilan report sales existing secara drastis.

## 5) User Persona
- **Kasir**: butuh input diskon cepat saat transaksi.
- **Supervisor/Manager Shift**: memberi otorisasi diskon sesuai kebijakan outlet.
- **Admin/Owner/Finance**: memantau total potongan dan asal diskon di laporan.

## 6) User Story
- Sebagai kasir, saya ingin klik tombol Diskon di dPOS lalu memasukkan nilai diskon agar transaksi bisa disesuaikan saat itu juga.
- Sebagai supervisor, saya ingin sistem meminta kode otorisasi/PIN agar diskon tidak bisa dilakukan sembarang orang.
- Sebagai owner/finance, saya ingin diskon ini tetap tercatat ke Potongan Penjualan di report yang sudah ada.

## 7) Scope Fitur (MVP)
1. Tombol **Diskon** di dPOS.
2. Input diskon:
   - Nominal (Rp)
   - Persentase (%)
3. Dialog **kode otorisasi/PIN supervisor** sebelum apply.
4. Validasi limit diskon (konfigurabel per role/outlet).
5. Penyimpanan metadata diskon (sumber, alasan opsional, siapa otorisasi, timestamp).
6. Agregasi diskon ini ke:
   - `Potongan Penjualan` (existing)
   - Report `admin/reports/catalog/sales-discount?tab=penjualan` (existing)

## 8) Flow UX (MVP)
1. Kasir menambahkan item seperti biasa.
2. Kasir klik tombol `Diskon`.
3. Muncul modal:
   - Tipe diskon: `%` / `Rp`
   - Nilai diskon
   - Alasan diskon (opsional di MVP, direkomendasikan wajib di fase lanjut)
4. Klik `Lanjut Otorisasi`.
5. Muncul input `Kode Otorisasi/PIN Supervisor`.
6. Jika valid:
   - Diskon diterapkan ke transaksi aktif.
   - Total & grand total ter-update real-time.
   - Event audit tersimpan.
7. Jika tidak valid:
   - Tampilkan error.
   - Tidak ada perubahan total.

## 9) Aturan Bisnis
1. Diskon tidak boleh membuat subtotal/total negatif.
2. Diskon persentase dibatasi max configurable (contoh default 20%).
3. Diskon nominal dibatasi max configurable (contoh default Rp200.000).
4. Otorisasi wajib dari user ber-permission supervisor/manager/admin.
5. Diskon dapat diterapkan:
   - level transaksi (MVP wajib)
   - level item (opsional fase berikutnya)
6. Diskon manual harus memiliki `source = dpos_authorized`.
7. Perubahan atau pembatalan diskon setelah otorisasi harus tercatat audit terpisah.

## 10) Dampak Laporan (Kritis)
### 10.1 Prinsip
Walau pola diskonnya berbeda dari voucher/promo, hasil akhirnya harus masuk ke jalur akuntansi/laporan yang sama:
- **Potongan Penjualan**
- **Sales Discount report (tab Penjualan)**

### 10.2 Formula Tetap
- Gross Sales = total sebelum diskon
- Potongan Penjualan = diskon existing (promo/voucher) + diskon dPOS otorisasi
- Net Sales = Gross Sales - Potongan Penjualan

### 10.3 Tampilan Report
- Rekap total existing tetap dipertahankan (agar user flow lama tidak berubah).
- Tambahan breakdown (opsional tapi direkomendasikan):
  - Potongan Promo/Voucher
  - Potongan Manual Otorisasi dPOS

## 11) Data & Teknis (Konseptual)
### 11.1 Opsi Simpan Data
Pilihan implementasi yang disarankan:
- Tambahkan kolom/atribut pada entitas transaksi penjualan untuk nilai diskon manual dan metadata otorisasi; atau
- Simpan pada tabel log diskon terpisah yang relasi ke `sales`.

Rekomendasi:
- Gunakan tabel log terpisah untuk audit yang kaya, sambil tetap menyimpan nilai final diskon pada `sales` untuk performa query report.

### 11.2 Field Minimum (konseptual)
- `sale_id`
- `discount_type` (`percent|amount`)
- `discount_value`
- `discount_amount_applied`
- `source` (`dpos_authorized`)
- `authorized_by_user_id`
- `authorized_at`
- `reason` (nullable MVP)
- `created_by_cashier_user_id`

## 12) Permission & Security
1. Permission baru (contoh):
   - `pos.apply_manual_discount`
   - `pos.authorize_manual_discount`
2. PIN/kode otorisasi tidak disimpan plaintext.
3. Rate limiting untuk percobaan PIN gagal.
4. Semua event otorisasi dicatat ke audit log.

## 13) Acceptance Criteria (MVP)
1. Kasir tidak bisa apply diskon manual tanpa otorisasi valid.
2. Setelah otorisasi sukses, total transaksi langsung berubah sesuai diskon.
3. Transaksi tersimpan dengan metadata `source = dpos_authorized`.
4. Nilai diskon muncul di komponen `Potongan Penjualan` seperti transaksi diskon existing.
5. Diskon tampil dalam rekap report:
   - `admin/reports/catalog/sales-discount?tab=penjualan`
6. Audit log memuat minimal: kasir, supervisor, nominal/%, waktu, outlet.

## 14) UAT Scenario
1. **Happy Path**: kasir input 10%, supervisor otorisasi valid, transaksi sukses, report bertambah.
2. **Invalid Auth**: kode salah, diskon tidak terpasang.
3. **Exceed Limit**: diskon > batas role, sistem tolak.
4. **Boundary**: diskon nominal tepat di batas maksimum diterima.
5. **Report Consistency**: total potongan report sebelum/ sesudah fitur tetap akurat.

## 15) Risiko & Mitigasi
- Risiko penyalahgunaan diskon manual:
  - Mitigasi: permission ketat, limit, audit, monitoring per kasir/shift.
- Risiko ketidaksesuaian laporan:
  - Mitigasi: pakai formula report existing, tambah test regresi report.
- Risiko friction operasional:
  - Mitigasi: UX modal ringkas, PIN cepat, timeout session otorisasi terukur.

## 16) Rencana Rilis
### Phase 1 (MVP)
- Diskon manual transaksi + otorisasi PIN supervisor + masuk Potongan Penjualan + audit dasar.

### Phase 2
- Otorisasi dinamis (OTP/approval device supervisor), alasan wajib, item-level discount.

### Phase 3
- Dashboard monitoring diskon manual (per user, per shift, per outlet, anomali).

## 17) KPI Keberhasilan
- Waktu rata-rata proses diskon di kasir menurun.
- Persentase transaksi diskon tanpa otorisasi = 0%.
- Selisih data potongan antara transaksi dan report = 0.
- Jumlah dispute/complaint terkait diskon manual turun.

## 18) Catatan Integrasi Existing Report
Fitur ini **tidak mengubah cara user membuka report**.  
User tetap memakai halaman:
- `https://ransapos.web.id/admin/reports/catalog/sales-discount?tab=penjualan`

Diskon manual dPOS akan terlihat di rekap selama mapping ke komponen potongan penjualan di backend sudah dihubungkan sesuai PRD ini.
