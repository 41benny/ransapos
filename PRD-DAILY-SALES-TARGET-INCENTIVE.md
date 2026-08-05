# PRD: Target Penjualan Harian Bergulir dan Insentif per Kategori

## 1. Status

| Atribut | Nilai |
|---|---|
| Produk | Ransapos |
| Modul | Backoffice dan Dashboard POS |
| Versi | Draft 1.1 |
| Tanggal | 6 Juli 2026 |
| Status | Validasi bisnis; belum untuk implementasi |

Perubahan 1.1: penutupan celah fraud — settlement adjustment, guard backdate/void terhadap periode terkunci, denominator insentif memakai target dasar, mapping maju, freeze target, dan klarifikasi minimum achievement.

## 2. Ringkasan

Ransapos membutuhkan target penjualan harian per outlet yang terlihat terus pada dashboard kasir. Target terdiri dari target total dan target kategori: Dimsum, Takoyaki, Minuman, dan Topping.

Kekurangan maupun kelebihan dibawa ke hari berikutnya dalam periode yang sama. Target total menjadi sasaran utama agar tim bisa switching antar-kategori. Target kategori mengarahkan komposisi penjualan dan menjadi dasar kontribusi insentif.

Minuman menggunakan satu target. Coffee dan Non-Coffee tetap ditampilkan sebagai breakdown analitik.

## 3. Masalah

Saat ini arahan omzet disampaikan lewat grup tanpa angka harian yang selalu terlihat. Tim tidak memiliki acuan yang sama untuk target, realisasi, kekurangan, kategori yang perlu didorong, dan dampaknya terhadap insentif.

## 4. Tujuan

1. Menetapkan target harian yang jelas per outlet.
2. Menampilkan progres langsung pada dashboard kasir.
3. Memberi fleksibilitas switching kategori.
4. Mengotomatisasi rollover kekurangan dan kelebihan.
5. Menghasilkan dasar insentif transparan per kategori.
6. Menyediakan laporan dan audit perubahan.

## 5. Prinsip Bisnis

- Target total adalah indikator utama hasil outlet.
- Target kategori tidak membatalkan keberhasilan total.
- Kekurangan menambah target berikutnya.
- Kelebihan mengurangi target berikutnya.
- Saldo total dan tiap kategori dihitung terpisah.
- Kasir melihat target dasar, saldo bawaan, target efektif, realisasi, dan sisa/lebih.
- Perubahan historis wajib memiliki alasan dan audit trail.

## 6. Scope MVP

### Termasuk

- Periode target bulanan per outlet.
- Target dasar per tanggal untuk total dan kategori.
- Pengisian massal dan revisi per hari.
- Rollover dua arah.
- Widget always-on pada dashboard POS.
- Realisasi otomatis dari transaksi completed.
- Breakdown Coffee dan Non-Coffee.
- Mapping produk ke kategori target.
- Laporan harian dan bulanan.
- Formula insentif configurable per kategori.
- Settlement insentif outlet.
- Permission dan audit log.

### Belum termasuk

- Target atau insentif individu.
- Payroll otomatis.
- Target per jam, shift, atau SKU.
- Prediksi AI dan notifikasi WhatsApp.
- Leaderboard karyawan.

## 7. Hierarki Target

```text
Target Total Outlet
├── Dimsum
├── Takoyaki
├── Minuman
│   ├── Coffee      (analitik)
│   └── Non-Coffee  (analitik)
└── Topping
```

Target resmi menggunakan rupiah penjualan bersih. Quantity tetap ditampilkan sebagai informasi, bukan basis target utama, karena harga antarproduk dan kategori berbeda jauh.

Nilai target kategori tidak wajib sama dengan target total. Sistem memberi warning jika berbeda, tetapi tidak memblokir penyimpanan.

## 8. Periode dan Hari Operasional

- Default periode: satu bulan kalender per outlet.
- Zona waktu: Asia/Jakarta.
- Semua shift outlet pada tanggal yang sama digabung.
- Tanggal mengikuti `sales.sale_date`, bukan waktu input.
- Backdate masuk tanggal transaksi.
- Cancelled/void tidak dihitung.

Status hari:

- `active`: target dan rollover berlaku.
- `closed`: outlet libur; target dasar nol dan saldo pindah ke hari aktif berikutnya.
- `excluded`: hari dikeluarkan melalui keputusan admin; target dasar dan saldo bawaan hari itu hangus (tidak diteruskan), realisasi hari itu tidak dihitung ke target maupun insentif.

Default saldo di-reset pada awal bulan. Opsi carry ke periode berikutnya disediakan dan harus dipilih sebelum periode dikunci.

## 9. Rollover

Variabel:

```text
B(d) = target dasar
C(d) = saldo bawaan
T(d) = target efektif yang ditampilkan
A(d) = realisasi
S(d) = saldo akhir
```

Formula:

```text
T(d) = MAX(0, B(d) + C(d))
S(d) = B(d) + C(d) - A(d)
C(d+1) = S(d)
```

Makna saldo:

- Positif: kekurangan.
- Nol: tepat target.
- Negatif: surplus.

Saldo akhir memakai target dasar + saldo bawaan, bukan target tampil, agar surplus tidak hilang saat target efektif sudah nol.

Rollover adalah alat operasional harian pada dashboard. Rollover **tidak** dipakai sebagai denominator perhitungan insentif (lihat Bagian 14) agar surplus awal bulan tidak bisa dipakai mengecilkan target bulanan.

Contoh:

| Hari | Dasar | Bawaan | Efektif | Realisasi | Saldo akhir |
|---|---:|---:|---:|---:|---:|
| Senin | Rp3 jt | Rp0 | Rp3 jt | Rp2,4 jt | Rp600 rb |
| Selasa | Rp3 jt | Rp600 rb | Rp3,6 jt | Rp4 jt | -Rp400 rb |
| Rabu | Rp3 jt | -Rp400 rb | Rp2,6 jt | Rp2,8 jt | -Rp200 rb |

Formula berlaku pada total dan tiap kategori.

## 10. Realisasi Penjualan

Transaksi eligible:

```text
sales.outlet_id = outlet target
sales.sale_date = tanggal target
sales.status = completed
```

Nilai kategori direkomendasikan memakai net item sales:

```text
Net Item Sales = sale_items.subtotal - alokasi diskon transaksi
```

Aturan:

- Diskon item mengurangi kategori item.
- Diskon header/voucher dialokasikan proporsional ke item.
- Pajak, service charge, rounding, dan tip tidak dihitung.
- Komisi merchant (GoFood/GrabFood/dll) **tidak** mengurangi realisasi target harian; komisi ditampilkan sebagai kolom informasi pada laporan settlement agar owner melihat dampak margin per channel.
- Total menggunakan jumlah net item sales agar konsisten dengan kategori.
- Void memicu penghitungan ulang tanggal asal hingga akhir periode.
- Quantity memakai `sale_items.quantity`.

### Guard terhadap periode terkunci/settled

Untuk mencegah manipulasi setelah insentif dihitung:

1. Backdate **ditolak** jika `sale_date` jatuh pada periode target berstatus `locked` atau periode yang settlement-nya berstatus `approved`/`paid`.
2. Void transaksi yang `sale_date`-nya jatuh pada periode tersebut **ditolak** melalui alur POS biasa. Koreksi hanya boleh lewat alur khusus dengan permission unlock, dan otomatis membuat entri koreksi pada settlement periode berjalan (lihat Bagian 14 — Adjustment).
3. Guard ini diimplementasikan pada `BackdateSaleService::validateSaleDate` dan alur void di `SaleController`.

## 11. Mapping Produk

Master kategori produk existing tidak selalu identik dengan kategori target, sehingga diperlukan mapping efektif per tanggal.

Aturan:

1. Satu produk memiliki satu mapping aktif pada tanggal yang sama.
2. Produk tanpa mapping tetap masuk target total tetapi tidak masuk insentif kategori.
3. Produk tanpa mapping muncul sebagai warning admin.
4. Bundle mengikuti mapping produk bundle, bukan komponen BOM.
5. Topping dipetakan eksplisit.
6. Coffee dan Non-Coffee merupakan child Minuman.
7. Pada MVP, mapping hanya berlaku maju: `effective_from` minimal hari ini. Mapping retroaktif membutuhkan permission khusus (`sales-target-mappings.backdate`), wajib beralasan, memicu recalculation, dan otomatis mengubah settlement `estimated`/`pending_review` periode terdampak menjadi `pending_review` dengan flag review mapping. Settlement `approved`/`paid` tidak berubah; selisihnya masuk adjustment periode berjalan.

Field konseptual:

```text
product_id
target_category_id
effective_from
effective_until
is_active
created_by
timestamps
```

## 12. Pengaturan Backoffice

Halaman periode menyediakan filter bulan, outlet, dan status `draft/published/locked`.

Tabel harian:

```text
Tanggal | Status Hari | Target Total | Dimsum | Takoyaki
Minuman | Topping | Saldo Bawaan | Efektif | Realisasi
Selisih | Achievement | Status
```

Aksi:

- Isi satu hari.
- Copy ke rentang tanggal.
- Atur weekday/weekend.
- Copy bulan sebelumnya.
- Revisi dengan alasan.
- Publish.
- Lock/unlock.
- Export.

Draft hanya terlihat admin. Published terlihat kasir. Revisi masa lalu menghitung ulang semua hari setelah tanggal yang berubah. Periode locked hanya dapat dibuka oleh permission khusus.

### Freeze target

1. Saat settlement periode masuk status `pending_review`, seluruh target periode itu otomatis terkunci dari revisi (freeze). Revisi hanya bisa dilakukan setelah settlement dikembalikan ke `estimated`.
2. Segregasi tugas: user yang melakukan `sales-incentives.approve` pada suatu periode tidak boleh menjadi user yang melakukan `sales-targets.revise-past` pada periode yang sama. Sistem memblokir, bukan sekadar warning.
3. Laporan settlement wajib menampilkan riwayat revisi target periode itu (tanggal, nilai lama/baru, alasan, user) sebagai bahan review sebelum approval.

## 13. Dashboard Kasir

Widget diletakkan setelah header kasir/outlet dan sebelum ringkasan sesi.

Ringkasan utama:

- Realisasi / target efektif.
- Progress dan persentase.
- Sisa target atau surplus.
- Target dasar.
- Saldo bawaan.
- Waktu pembaruan terakhir.

Kartu Dimsum, Takoyaki, Minuman, dan Topping menampilkan realisasi rupiah, target efektif, progress, kurang/lebih, dan quantity. Detail Minuman dapat dibuka untuk melihat Coffee dan Non-Coffee.

Data diperbarui setelah sale, void, halaman kembali aktif, dan polling maksimal setiap 60 detik.

Jika belum ada target published, tampilkan `Target belum ditetapkan`, bukan progress nol yang menyesatkan.

## 14. Insentif

### Prinsip

- MVP menghitung pool insentif outlet/tim, bukan individu.
- Kontribusi dihitung per kategori.
- Basis default adalah net sales.
- Gross profit disiapkan setelah kualitas HPP tervalidasi.
- Nilai sebelum akhir periode berstatus estimasi.
- Rate tidak di-hardcode.

### Gate total

Konfigurasi:

```text
overall_gate_enabled
overall_gate_percentage
```

Rekomendasi: target total kumulatif bulanan menjadi gate 100%. Tim tetap bisa switching kategori, tetapi outlet harus mencapai hasil total sebelum insentif dibayarkan.

### Denominator insentif

Seluruh perhitungan insentif (gate, achievement, cap) memakai **jumlah target dasar periode**:

```text
Period Base Target = SUM(B(d)) untuk hari active dalam periode
```

Target efektif hasil rollover **tidak** dipakai sebagai denominator. Alasan: jika denominator memakai target efektif, surplus awal bulan mengecilkan target hari berikutnya sehingga achievement bulanan bisa digelembungkan (termasuk lewat penjualan yang di-void belakangan) tanpa penjualan riil tambahan. Rollover tetap tampil di dashboard sebagai alat kerja harian.

### Achievement kategori

```text
Achievement = Actual Category / Period Base Category Target
```

Jika target nol, achievement tampil `N/A` dan kategori itu tidak menghasilkan insentif (Eligible Sales = 0).

### Formula yang direkomendasikan

```text
Jika Achievement < minimum_achievement_percent:
    Category Incentive = 0
Jika tidak:
    Eligible Sales = MIN(Actual Category, Cap × Period Base Category Target)
    Category Incentive = Eligible Sales × Category Rate

Total Outlet Incentive = SUM(Category Incentive) + Adjustment Periode Ini
```

Parameter per kategori:

```text
basis = net_sales/gross_profit
rate_percent
minimum_achievement_percent   (di bawah minimum: insentif kategori = 0, bukan prorata)
maximum_achievement_cap       (rem utama terhadap penjualan fiktif; wajib diisi, tidak boleh unlimited)
is_active
```

Laporan membedakan:

```text
Sales Contribution % = Actual Category / Actual Eligible Total
Incentive Contribution % = Category Incentive / Total Incentive
```

Rate, minimum achievement, dan cap belum ditetapkan dan harus configurable.

### Status settlement

- `estimated`
- `pending_review`
- `approved`
- `paid`
- `cancelled`

Settlement approved menyimpan snapshot sehingga perubahan rate berikutnya tidak mengubah histori.

### Adjustment (koreksi lintas periode)

Snapshot settlement `approved`/`paid` tidak pernah diubah. Jika setelah approval terjadi perubahan data yang menyentuh periode tersebut (void lewat alur khusus, backdate berizin, mapping retroaktif, unlock + revisi), sistem membuat **entri adjustment**:

```text
sales_incentive_adjustments:
  settlement_id_asal
  periode_pembebanan (periode berjalan)
  nilai (bisa negatif)
  tipe (void/backdate/mapping/revision)
  referensi transaksi/perubahan
  alasan, created_by, timestamps
```

Aturan:

1. Adjustment dibebankan ke settlement periode berjalan sebagai baris terpisah, terlihat jelas pada laporan (bukan digabung diam-diam ke angka kategori).
2. Adjustment negatif yang melebihi insentif periode berjalan dibawa ke periode berikutnya (tidak ditagih tunai ke tim).
3. Setiap adjustment wajib punya referensi ke perubahan pemicunya dan masuk audit trail.
4. Settlement tidak dapat di-approve jika masih ada adjustment berstatus belum direview.

### Checklist wajib sebelum approval

Halaman approval settlement menampilkan dan mewajibkan review:

1. Daftar void dan backdate yang `sale_date`-nya jatuh dalam periode settlement (jumlah, nilai, user, alasan, token void).
2. Riwayat revisi target periode itu.
3. Perubahan mapping yang menyentuh periode itu.
4. Daftar adjustment dari periode sebelumnya.

Owner meng-approve setelah checklist ditandai selesai. Audit mencatat siapa dan kapan.

### Pembagian individu

MVP belum membagi ke karyawan karena transaksi atas nama kasir belum tentu mencerminkan kerja tim. Phase berikutnya dapat memakai kehadiran, jam kerja, role, atau poin kontribusi.

## 15. Laporan

### Harian

- Target dasar, saldo bawaan, target efektif.
- Realisasi dan achievement total.
- Saldo akhir.
- Realisasi dan achievement kategori.
- Estimasi insentif.

### Bulanan

- Target dan realisasi kumulatif.
- Saldo akhir periode.
- Jumlah hari tercapai.
- Kontribusi penjualan dan insentif per kategori.
- Coffee vs Non-Coffee.
- Produk belum mapping.
- Riwayat revisi.
- Estimasi dan nilai final insentif.

Export CSV/XLSX mengikuti filter aktif.

## 16. Permission

```text
sales-targets.view
sales-targets.manage
sales-targets.publish
sales-targets.revise-past
sales-targets.lock
sales-targets.unlock
sales-target-mappings.manage
sales-target-mappings.backdate
sales-incentives.view
sales-incentives.manage-rules
sales-incentives.approve
sales-incentives.mark-paid
```

Kasir hanya melihat outlet sesi aktif. Owner memiliki publish, revisi historis, lock/unlock, dan approval.

Segregasi tugas ditegakkan sistem: approver settlement suatu periode tidak boleh merevisi target masa lalu periode yang sama (lihat Bagian 12 — Freeze target).

## 17. Model Data Konseptual

- `sales_target_periods`: outlet, tanggal awal/akhir, status, carry policy.
- `sales_target_categories`: hierarchy, targetable, active, order.
- `sales_target_days`: tanggal, status hari, target total.
- `sales_target_day_categories`: target per kategori.
- `sales_target_product_mappings`: mapping efektif per tanggal.
- `sales_target_daily_ledgers`: snapshot dasar, bawaan, efektif, actual, saldo.
- `sales_incentive_rules`: basis, rate, minimum, cap, gate.
- `sales_incentive_settlements`: status dan snapshot final.
- `sales_incentive_adjustments`: koreksi lintas periode dengan referensi pemicu.

Coffee dan Non-Coffee memiliki parent Minuman dan `is_targetable=false` pada MVP.

## 18. Recalculation dan Audit

Pemicu recalculation:

1. Sale completed.
2. Sale di-void.
3. Backdate dibuat/diubah.
4. Target direvisi.
5. Mapping berubah.
6. Status hari berubah.

Cakupan recalculation dibedakan agar tidak boros:

- Sale completed hari ini (kasus mayoritas): cukup memperbarui ledger tanggal berjalan; saldo hari berikutnya belum terbentuk sehingga tidak ada recalc berantai.
- Void, backdate, revisi target, perubahan mapping, dan perubahan status hari: recalculation berantai dari tanggal terdampak sampai akhir periode.

Proses per outlet/periode harus memakai lock untuk mencegah race condition. Kegagalan refresh ledger tidak boleh menggagalkan transaksi POS; sistem melakukan retry dan menampilkan waktu kalkulasi terakhir.

Semua pemicu tunduk pada guard periode terkunci/settled (Bagian 10) — perubahan yang menyentuh periode `locked` atau settlement `approved`/`paid` ditolak atau dialihkan ke mekanisme adjustment (Bagian 14).

Audit wajib untuk create, update, publish, revision, rebuild, lock/unlock, approval, dan paid.

## 19. Edge Cases

- Tidak ada target: tampilkan status kosong yang jelas.
- Target nol: achievement N/A dan insentif kategori nol.
- Hari libur: saldo ke hari aktif berikutnya.
- Hari excluded: target dasar dan saldo hangus, realisasi tidak dihitung.
- Surplus besar: target tampil nol, sisa surplus tetap tersimpan; denominator insentif tetap target dasar sehingga surplus tidak menggelembungkan achievement.
- Produk tanpa mapping: masuk total, tidak masuk kategori.
- Void lama/backdate: recalculation berantai, ditolak jika menyentuh periode terkunci/settled.
- Void setelah settlement approved/paid (lewat alur khusus berizin): snapshot tetap, selisih menjadi adjustment negatif periode berjalan.
- Backdate lintas bulan ke periode yang sudah settled: ditolak.
- Adjustment negatif melebihi insentif periode berjalan: sisa dibawa ke periode berikutnya.
- Dua shift sehari: digabung per outlet/tanggal.
- Target kategori tidak sama dengan total: warning, bukan error.
- Periode published tidak boleh overlap.
- Rule berubah: settlement approved tetap.
- HPP kosong: mode gross profit tidak eligible.

## 20. Acceptance Criteria

1. Admin dapat membuat periode bulanan per outlet.
2. Admin dapat mengisi total dan empat kategori per hari.
3. Pengisian massal mendukung rentang dan weekday/weekend.
4. Draft tidak terlihat kasir; published terlihat.
5. Dashboard menampilkan dasar, bawaan, efektif, realisasi, persentase, dan sisa/lebih.
6. Minuman memiliki detail Coffee dan Non-Coffee.
7. Completed sale menambah realisasi sesuai outlet dan sale_date.
8. Void mengurangi realisasi dan menghitung ulang rollover.
9. Kekurangan menambah target berikutnya.
10. Kelebihan mengurangi target berikutnya.
11. Surplus tidak hilang saat target efektif nol.
12. Total dapat tercapai walau satu kategori kurang.
13. Produk tanpa mapping masuk total dan menghasilkan warning.
14. Revisi masa lalu membutuhkan alasan.
15. Locked tidak dapat diedit tanpa unlock berizin.
16. Estimasi insentif dihitung per kategori dengan rule configurable.
17. Settlement approved immutable tanpa proses pembatalan/re-approval.
18. Laporan dapat diekspor.
19. Semua perubahan penting memiliki audit trail.
20. Achievement dan cap insentif dihitung terhadap jumlah target dasar periode, bukan target efektif hasil rollover.
21. Backdate dan void ditolak jika sale_date jatuh pada periode locked atau settlement approved/paid.
22. Perubahan berizin yang menyentuh periode settled menghasilkan entri adjustment pada settlement periode berjalan.
23. Settlement dengan achievement di bawah minimum menghasilkan insentif kategori nol.
24. Settlement tidak dapat di-approve sebelum checklist review (void/backdate, revisi target, mapping, adjustment) selesai.
25. Approver settlement tidak dapat merevisi target masa lalu pada periode yang sama.

## 21. UAT Utama

- Kekurangan Senin Rp600 ribu membuat target efektif Selasa bertambah Rp600 ribu.
- Kelebihan Selasa Rp400 ribu membuat target efektif Rabu berkurang Rp400 ribu.
- Dimsum kurang tetapi Minuman lebih dan total 105%: total tercapai, status kategori tetap masing-masing.
- Sale Senin di-void Rabu: saldo Senin sampai Rabu dihitung ulang.
- Hari Selasa closed: saldo Senin pindah ke hari aktif berikutnya.
- Produk baru tanpa mapping: total bertambah, kategori tidak, warning muncul.
- Gate total lolos: kategori eligible menghasilkan estimasi insentif.
- Settlement Juli approved dan rate Agustus berubah: Juli tidak berubah.
- Surplus besar minggu pertama: target efektif harian mengecil, tetapi achievement bulanan tetap dihitung dari target dasar (tidak menggelembung).
- Settlement Juli sudah paid, lalu ada void berizin atas transaksi 28 Juli: snapshot Juli tetap, muncul adjustment negatif di settlement Agustus dengan referensi transaksi.
- Tanggal 3 Agustus mencoba backdate ke 30 Juli (periode Juli locked): sistem menolak.
- Admin mencoba ubah mapping berlaku mundur tanpa permission backdate: sistem menolak.
- Approver settlement mencoba merevisi target periode yang sama: sistem menolak.

## 22. KPI

- Seluruh outlet aktif memiliki target published sebelum operasional.
- Perubahan sale tercermin maksimal dua menit.
- Rekonsiliasi dengan laporan penjualan memiliki selisih Rp0.
- Omzet produk tanpa mapping di bawah 1%.
- Semua revisi historis memiliki audit.
- Tim dapat menjelaskan target dan sisa hari itu tanpa hitung manual.

## 23. Risiko dan Mitigasi

| Risiko | Mitigasi |
|---|---|
| Rollover membengkak | Target dasar dan bawaan ditampilkan terpisah; revisi beralasan |
| Tim fokus produk termudah | Target dan rate kategori terpisah |
| Omzet mendorong margin rendah | Mode gross profit setelah HPP valid |
| Mapping salah | Effective dating, warning, audit, review settlement |
| Void/backdate mengubah histori | Recalculation, settlement snapshot, guard periode terkunci, adjustment lintas periode |
| Jual lalu void setelah insentif dibayar | Guard void pada periode settled + adjustment negatif otomatis + checklist void/backdate sebelum approval |
| Backdate menembus periode settled | Validasi tanggal menolak periode locked/approved/paid |
| Surplus rollover menggelembungkan achievement | Denominator insentif memakai target dasar, bukan target efektif |
| Mapping dipindah ke kategori rate tinggi menjelang settlement | Mapping hanya maju di MVP; retroaktif butuh permission khusus + settlement kembali pending_review |
| Target direvisi turun menjelang settlement | Freeze target saat pending_review + segregasi approver vs perevisi + riwayat revisi tampil di halaman approval |
| Penjualan fiktif demi insentif | Cap wajib per kategori, gate total, checklist review, adjustment |
| Manipulasi target | Permission, alasan, audit, lock |
| Attribution individu tidak adil | MVP menggunakan pool outlet |

## 24. Tahapan Rilis

### Phase 1 — Target

Mapping (maju), periode, target harian, rollover, dashboard, laporan, dan guard backdate/void terhadap periode locked (dipasang sejak awal — murah sekarang, mahal jika ditambal setelah data berjalan).

### Phase 2 — Insentif Outlet

Rule configurable, estimasi, settlement, approval, paid, **dan mekanisme adjustment dirilis bersamaan** — jeda antara settlement dan adjustment adalah celah fraud terbesar. Sebelum pembayaran pertama, jalankan satu bulan paralel run: insentif dihitung dan direview tetapi belum dibayar, untuk validasi angka.

### Phase 3 — Optimasi

Pembagian individu, target Coffee/Non-Coffee bila diperlukan, target shift/jam, dan notifikasi pace.

## 25. Keputusan Disepakati

1. Target harian.
2. Kekurangan dan kelebihan digulung.
3. Target total tetap ada.
4. Tim boleh switching kategori.
5. Kategori: Dimsum, Takoyaki, Minuman, Topping.
6. Coffee dan Non-Coffee satu target Minuman dengan breakdown analitik.
7. Insentif berdasarkan kontribusi pencapaian per kategori.
8. Denominator insentif memakai jumlah target dasar periode, bukan target efektif rollover.
9. Backdate/void ditolak pada periode locked atau settlement approved/paid; koreksi berizin lewat adjustment lintas periode.
10. Mapping produk hanya berlaku maju di MVP.
11. Freeze target saat settlement pending_review dan segregasi approver vs perevisi target.
12. Komisi merchant tidak mengurangi realisasi target; tampil sebagai informasi margin di laporan settlement.

## 26. Keputusan yang Masih Perlu Disahkan

1. Reset bulanan atau wajib carry lintas bulan.
2. Gate total insentif; rekomendasi 100% kumulatif.
3. Rate, minimum achievement, dan cap tiap kategori. Cap wajib ditetapkan sebelum Phase 2 — tanpa cap, setiap rupiah penjualan fiktif langsung menjadi insentif.
4. Basis final MVP; rekomendasi net sales.
5. Pembagian pool: rata, hari hadir, jam kerja, atau role.
6. Kebijakan hari libur; rekomendasi saldo diteruskan.
7. Role yang boleh merevisi target hari berjalan dan masa lalu.
8. Role yang memegang permission unlock/void berizin pada periode settled; rekomendasi hanya owner.

## 27. Rekomendasi Default MVP

```text
Periode              Bulanan
Reset saldo          Awal bulan
Basis target         Net sales (sebelum komisi merchant)
Denominator insentif Jumlah target dasar periode
Gate insentif        100% kumulatif total
Target kategori      Dasar kontribusi insentif
Minuman              Satu target; child untuk analitik
Settlement           Bulanan, dengan checklist review + adjustment
Attribution          Pool outlet/tim
Hari libur           Saldo ke hari aktif berikutnya
Hari excluded        Target dan saldo hangus
Mapping              Berlaku maju
Guard periode        Backdate/void ditolak pada periode locked/settled
```

