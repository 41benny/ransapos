# PRD: POS Packaging Stock Control Per Shift

## 1. Ringkasan

Fitur ini menambahkan kontrol stok packaging di POS, seperti box, cup/gelas, dan item pendukung lain yang dipakai saat operasional outlet.

Saat ini proses open shift dan closing shift lebih fokus ke saldo cash. Owner juga membutuhkan pencatatan jumlah packaging awal, packaging masuk/keluar, sisa packaging saat closing, dan jumlah packaging yang terpakai selama shift.

Untuk MVP, fitur ini tidak menggunakan resep produk penuh. Sistem memakai mapping sederhana produk ke packaging agar closing dapat membandingkan pemakaian aktual dari stok fisik dengan pemakaian estimasi dari qty penjualan.

```text
Terpakai Aktual = Stok Awal + Adjustment Masuk Approved - Adjustment Keluar Approved - Stok Akhir Fisik
Terpakai Estimasi Sales = SUM(qty produk terjual x qty packaging per produk)
Selisih = Terpakai Aktual - Terpakai Estimasi Sales
```

Nama item packaging seperti jenis box akan diinformasikan kemudian. Untuk cup/gelas, MVP menggunakan satu ukuran cup untuk semua jenis minuman.

## 2. Tujuan

- Owner dapat mengetahui jumlah box/cup yang terpakai setiap closing shift.
- Owner dapat membandingkan stok awal, stok masuk/keluar, dan stok akhir fisik.
- Owner dapat membandingkan packaging terpakai aktual dengan estimasi dari qty penjualan POS.
- Kasir dapat mencatat stok awal packaging saat open shift.
- Kasir dapat membuat request adjustment packaging saat shift berjalan.
- Backoffice dapat approve/reject adjustment packaging.
- Closing shift menghasilkan laporan packaging yang jelas dan bisa diaudit.

## 3. Scope MVP

Fitur MVP mencakup:

1. Master data item packaging.
2. Input stok awal packaging saat open shift.
3. Default stok awal dari closing shift terakhir.
4. Adjustment packaging masuk/keluar saat shift berjalan.
5. Approval adjustment oleh backoffice.
6. Input stok akhir fisik packaging saat closing shift.
7. Perhitungan otomatis jumlah packaging terpakai aktual.
8. Mapping sederhana produk ke item packaging.
9. Perhitungan estimasi pemakaian packaging dari qty penjualan per shift.
10. Perbandingan terpakai aktual vs estimasi sales.
11. Laporan packaging pada closing shift.
12. Audit log user dan waktu untuk open, adjustment, approval, dan closing.

## 4. Out of Scope MVP

Untuk tahap awal, fitur ini belum mencakup:

- Resep packaging per produk.
- Pemotongan otomatis berdasarkan transaksi.
- Prediksi penggunaan packaging dari nama produk.
- Transfer stok gudang ke outlet secara lengkap.
- Inventory gudang penuh.
- Auto-approval berdasarkan batas quantity.
- Lampiran foto/bukti adjustment.

Catatan:

- MVP tetap melihat qty penjualan dari sistem, tetapi melalui mapping sederhana produk ke packaging, bukan resep bahan lengkap.
- Prediksi otomatis dari nama produk tanpa mapping dapat dipertimbangkan di phase berikutnya.

## 5. Role Pengguna

### 5.1 Kasir / Outlet

- Melihat stok awal packaging saat open shift.
- Mengoreksi stok awal jika fisik berbeda dari stok terakhir.
- Membuat request adjustment packaging masuk/keluar.
- Melihat status adjustment pending/approved/rejected.
- Mengisi stok akhir fisik packaging saat closing shift.

### 5.2 Backoffice / Admin

- Mengelola master item packaging.
- Melihat daftar adjustment packaging.
- Approve/reject adjustment packaging.
- Melihat laporan packaging per outlet dan per shift.

### 5.3 Owner

- Melihat ringkasan pemakaian packaging per shift.
- Melihat selisih atau anomali packaging.
- Mengevaluasi outlet/kasir berdasarkan laporan closing.

## 6. Master Data Packaging

Backoffice menyediakan master item packaging.

Contoh item sementara:

```text
Box 4
Box 6
Box 8
Box 10
Cup
```

Catatan:

- Nama item final akan diinformasikan kemudian.
- Cup hanya satu ukuran untuk semua jenis minuman pada MVP.
- Item inactive tidak tampil di open shift baru, adjustment baru, dan closing baru.
- Item inactive tetap muncul pada laporan historis jika pernah digunakan.

Field minimal:

```text
id
name
unit
is_active
sort_order
created_at
updated_at
```

## 7. Product Packaging Mapping

MVP membutuhkan mapping sederhana agar sistem bisa menghitung estimasi penggunaan packaging dari qty penjualan POS.

Mapping ini bukan resep lengkap. Setiap produk cukup ditentukan menggunakan packaging apa dan berapa qty packaging per 1 qty produk terjual.

Contoh:

```text
Dimsum Mentai Isi 4      -> Box 4, qty 1
Dimsum Mentai Isi 6      -> Box 6, qty 1
Dimsum Mentai Isi 8      -> Box 8, qty 1
Dimsum Mentai Isi 12     -> Box 12, qty 1
Takoyaki Original Isi 5  -> Box Takoyaki 5, qty 1
Takoyaki Original Isi 8  -> Box Takoyaki 8, qty 1
Kopi Gula Aren           -> Cup, qty 1
Milky Aren               -> Cup, qty 1
Milo                     -> Cup, qty 1
```

Aturan cup:

- Semua produk minuman menggunakan satu item packaging `Cup`.
- Tidak perlu membedakan ukuran cup per jenis minuman pada MVP.
- Produk topping seperti Boba, Milk, dan Chilli Oil tidak otomatis memakai cup kecuali nanti ditentukan oleh owner.

Sumber data:

- Estimasi sales menggunakan `sales.cash_session_id` untuk mengambil transaksi pada shift yang sedang closing.
- Item transaksi menggunakan `sale_items.product_id`, `sale_items.product_name`, dan `sale_items.quantity`.
- Mapping utama harus berdasarkan `product_id` agar aman terhadap perubahan nama produk.
- `product_name` tetap disimpan di laporan sebagai snapshot untuk audit.

Field minimal:

```text
id
product_id
packaging_item_id
qty_per_product
is_active
created_at
updated_at
```

Rules:

- Satu produk boleh punya lebih dari satu mapping jika nanti diperlukan, tetapi MVP cukup mendukung satu packaging utama per produk.
- Produk tanpa mapping tetap bisa dijual.
- Produk tanpa mapping harus muncul di warning closing/report sebagai `Produk Belum Mapping Packaging`.
- Qty packaging hasil estimasi harus dibulatkan sesuai aturan packaging satuan pcs.
- Jika `sale_items.quantity` decimal, sistem perlu membulatkan estimasi packaging ke atas atau mengikuti rule yang ditentukan. Rekomendasi MVP: gunakan `CEIL(quantity * qty_per_product)` per item transaksi.

## 8. Flow Open Shift

Saat kasir membuka shift, setelah input saldo cash awal, sistem menampilkan section:

```text
Stok Awal Packaging
```

Field per item:

```text
Box 4      [ qty ]
Box 6      [ qty ]
Cup        [ qty ]
```

Default value:

- Jika ada closing shift terakhir pada outlet tersebut, sistem mengambil `closing_physical_qty` terakhir sebagai stok awal.
- Jika belum ada data sebelumnya, default `0`.

Aksi:

- `Gunakan Stok Terakhir`
- `Koreksi Manual`
- `Open Shift`

Rules:

- Open shift menyimpan stok awal packaging per item.
- Jika kasir mengubah angka dari stok terakhir, sistem menandai data sebagai manual correction.
- Manual correction harus tersimpan dengan user dan waktu.
- Stok awal packaging tidak otomatis meminta approval, karena ini adalah validasi fisik saat memulai shift.

Data yang disimpan:

```text
shift_id
packaging_item_id
opening_qty
source_last_closing_qty
is_manual_corrected
created_by
created_at
```

## 9. Flow Adjustment Packaging Saat Shift Berjalan

Saat shift aktif, POS menyediakan menu:

```text
Packaging Adjustment
```

Letak yang disarankan:

- Dropdown/menu shift di header POS.
- Dekat tombol close shift.
- Menu samping POS bagian shift management.

Form adjustment:

```text
Tipe    : Masuk / Keluar
Item    : Packaging item
Qty     : Jumlah
Alasan  : Tambahan dari gudang / rusak / hilang / dipakai event / lainnya
Catatan : Optional
```

Setelah submit:

```text
Status: Pending Approval
```

Rules:

- Adjustment masuk manual perlu approval backoffice.
- Adjustment keluar manual wajib approval backoffice.
- Adjustment pending tidak mempengaruhi stok resmi sampai approved.
- Adjustment pending tetap tampil di closing sebagai informasi.
- Adjustment rejected tidak mempengaruhi stok resmi, tetapi tetap tersimpan sebagai histori.

Data yang disimpan:

```text
id
shift_id
outlet_id
packaging_item_id
type: in/out
qty
reason
note
status: pending/approved/rejected
requested_by
approved_by
approved_at
rejected_by
rejected_at
created_at
updated_at
```

## 10. Flow Approval Backoffice

Backoffice memiliki halaman:

```text
Packaging Adjustment Approval
```

Kolom:

```text
Tanggal
Outlet
Shift
Kasir
Tipe
Item
Qty
Alasan
Catatan
Status
Action
```

Action:

- `Approve`
- `Reject`

Rules:

- Hanya user backoffice/admin yang memiliki permission dapat approve/reject.
- Approved adjustment masuk menambah stok resmi shift.
- Approved adjustment keluar mengurangi stok resmi shift.
- Rejected adjustment tidak mempengaruhi stok resmi.
- Approval/rejection harus mencatat user dan waktu.

## 11. Flow Closing Shift

Saat closing shift, setelah bagian cash, sistem menampilkan section:

```text
Closing Packaging
```

Tampilan per item:

```text
Box 6
Awal: 80
Adjustment Approved Masuk: 20
Adjustment Approved Keluar: 0
Adjustment Pending Masuk: 10
Adjustment Pending Keluar: 0
Sisa Fisik: [ 63 ]
Terpakai Aktual: 37
Estimasi Sales: 35
Selisih: +2
```

Formula:

```text
Terpakai Aktual =
Stok Awal
+ Adjustment Masuk Approved
- Adjustment Keluar Approved
- Stok Akhir Fisik

Terpakai Estimasi Sales =
SUM(qty produk terjual x qty packaging per produk)

Selisih =
Terpakai Aktual - Terpakai Estimasi Sales
```

Contoh:

```text
Awal: 80
Masuk Approved: 20
Keluar Approved: 0
Akhir Fisik: 63

Terpakai Aktual = 80 + 20 - 0 - 63 = 37
Estimasi Sales = 35
Selisih = 37 - 35 = +2
```

Jika ada pending adjustment, tampilkan warning:

```text
Ada adjustment pending yang belum disetujui backoffice.
```

Rules:

- Kasir wajib mengisi stok akhir fisik per item packaging aktif.
- Closing tetap boleh dilakukan walaupun ada pending adjustment.
- Jika ada pending adjustment, closing report diberi status/catatan `Closing With Pending Adjustment`.
- Sistem menghitung estimasi sales dari item transaksi pada `cash_session_id` shift tersebut.
- Produk yang belum punya mapping packaging ditampilkan sebagai warning saat closing.
- Stok awal shift berikutnya mengambil stok akhir fisik dari closing ini.

Data yang disimpan:

```text
id
shift_id
packaging_item_id
opening_qty
approved_adjustment_in_qty
approved_adjustment_out_qty
pending_adjustment_in_qty
pending_adjustment_out_qty
closing_physical_qty
actual_used_qty
estimated_sales_used_qty
difference_qty
created_at
updated_at
```

## 12. Perhitungan

### 12.1 Stok Resmi Tersedia

```text
Stok Resmi Tersedia =
Stok Awal
+ Adjustment Masuk Approved
- Adjustment Keluar Approved
```

### 12.2 Terpakai Aktual

```text
Terpakai Aktual =
Stok Resmi Tersedia
- Stok Akhir Fisik
```

### 12.3 Terpakai Estimasi Sales

```text
Terpakai Estimasi Sales =
SUM(sale_items.quantity x product_packaging_mappings.qty_per_product)
```

Ruang lingkup transaksi:

```text
sales.cash_session_id = current_shift_id
sales.status = completed
```

Rounding MVP:

```text
estimated_qty_per_sale_item = CEIL(quantity x qty_per_product)
```

Contoh:

```text
Dimsum Mentai Isi 6 terjual 10 qty
Mapping: Dimsum Mentai Isi 6 -> Box 6 qty 1
Estimasi Box 6 = 10

Kopi Gula Aren terjual 7 qty
Mapping: Kopi Gula Aren -> Cup qty 1
Estimasi Cup = 7
```

### 12.4 Selisih Packaging

```text
Selisih =
Terpakai Aktual
- Terpakai Estimasi Sales
```

Interpretasi:

```text
Selisih +2 berarti stok fisik berkurang 2 pcs lebih banyak dari estimasi sales.
Selisih -2 berarti stok fisik berkurang 2 pcs lebih sedikit dari estimasi sales.
```

### 12.5 Informasi Pending

```text
Net Pending =
Adjustment Pending Masuk
- Adjustment Pending Keluar
```

Pending tidak masuk ke stok resmi, tetapi ditampilkan agar laporan tidak membingungkan.

## 13. Tampilan POS

### 13.1 Open Shift

Section baru setelah saldo cash awal:

```text
Stok Awal Packaging
```

Komponen:

- Table/list item packaging.
- Input quantity per item.
- Indikator nilai dari closing terakhir.
- Tombol gunakan stok terakhir.

### 13.2 POS Aktif

Menu baru:

```text
Packaging Adjustment
```

Komponen:

- Form adjustment masuk/keluar.
- Riwayat adjustment shift berjalan.
- Badge status pending/approved/rejected.

### 13.3 Closing Shift

Section baru setelah ringkasan cash:

```text
Closing Packaging
```

Komponen:

- Ringkasan stok awal.
- Ringkasan adjustment approved.
- Ringkasan adjustment pending.
- Input stok akhir fisik.
- Hasil terpakai aktual.
- Hasil estimasi pemakaian dari penjualan.
- Selisih aktual vs estimasi sales.
- Warning produk belum mapping packaging.
- Warning jika ada pending adjustment.

## 14. Tampilan Backoffice

### 14.1 Master Packaging Item

Fungsi:

- Create item packaging.
- Edit nama/unit/sort order.
- Set active/inactive.

### 14.2 Product Packaging Mapping

Fungsi:

- Memilih produk.
- Memilih item packaging.
- Mengisi qty packaging per 1 qty produk.
- Mengaktifkan/nonaktifkan mapping.
- Melihat daftar produk yang belum punya mapping.

Default mapping yang direkomendasikan:

- Produk kategori DIMSUM dengan nama `Isi N` dimapping ke box sesuai isi/ukuran yang ditentukan owner.
- Produk kategori TAKOYAKI dengan nama `Isi N` dimapping ke box takoyaki sesuai isi/ukuran yang ditentukan owner.
- Semua produk kategori COFFEE, NON COFFEE, dan Minuman dimapping ke `Cup` qty 1.
- Topping tidak dimapping otomatis kecuali ditentukan owner.

### 14.3 Packaging Adjustment Approval

Fungsi:

- Filter berdasarkan tanggal, outlet, status, tipe, dan item.
- Approve adjustment.
- Reject adjustment.
- Melihat detail alasan dan catatan.

### 14.4 Report Packaging Closing

Kolom laporan:

```text
Tanggal
Outlet
Shift
Kasir
Item
Stok Awal
Adjustment Masuk Approved
Adjustment Keluar Approved
Adjustment Masuk Pending
Adjustment Keluar Pending
Stok Akhir Fisik
Terpakai Aktual
Estimasi Sales
Selisih
Status Closing
```

### 14.5 Report Produk Belum Mapping Packaging

Kolom laporan:

```text
Tanggal
Outlet
Shift
Kasir
Product ID
Nama Produk
SKU
Qty Terjual
Kategori
```

Fungsi:

- Membantu backoffice melengkapi mapping produk.
- Mencegah laporan estimasi sales tidak lengkap.

## 15. Permission

Permission minimal:

```text
packaging_items.view
packaging_items.create
packaging_items.update
packaging_adjustments.create
packaging_adjustments.view
packaging_adjustments.approve
packaging_adjustments.reject
packaging_mappings.view
packaging_mappings.create
packaging_mappings.update
packaging_reports.view
```

Role awal:

- Kasir: create adjustment, view own shift adjustment.
- Supervisor outlet: view outlet adjustment/report.
- Backoffice/Admin: manage item, manage mapping, approve/reject, view all reports.
- Owner: view all reports.

## 16. Acceptance Criteria MVP

1. Kasir bisa input stok awal packaging saat open shift.
2. Sistem otomatis mengisi stok awal dari closing shift terakhir jika tersedia.
3. Kasir bisa mengoreksi stok awal packaging sebelum open shift.
4. Kasir bisa membuat adjustment masuk/keluar saat shift aktif.
5. Adjustment manual tersimpan sebagai pending.
6. Backoffice bisa approve/reject adjustment.
7. Adjustment approved mempengaruhi perhitungan stok resmi.
8. Adjustment rejected tidak mempengaruhi stok resmi.
9. Saat closing, kasir wajib input stok akhir fisik packaging.
10. Sistem menghitung terpakai aktual per item.
11. Backoffice bisa mapping produk ke packaging.
12. Semua produk minuman bisa dimapping ke satu item `Cup`.
13. Sistem menghitung estimasi pemakaian packaging dari qty penjualan pada shift tersebut.
14. Sistem menghitung selisih antara terpakai aktual dan estimasi sales.
15. Closing report menampilkan stok awal, adjustment approved, adjustment pending, stok akhir fisik, terpakai aktual, estimasi sales, dan selisih.
16. Jika ada pending adjustment saat closing, laporan menampilkan warning/status khusus.
17. Jika ada produk terjual yang belum mapping packaging, laporan menampilkan warning.
18. Semua aktivitas menyimpan user dan timestamp.
19. Master packaging item bisa dikelola dari backoffice.

## 17. Phase Berikutnya

Setelah MVP stabil, fitur lanjutan yang bisa ditambahkan:

1. Auto-suggest mapping produk ke packaging berdasarkan nama produk.
2. Transfer packaging dari gudang ke outlet.
3. Auto-approval adjustment kecil berdasarkan role dan batas quantity.
4. Lampiran foto/bukti untuk adjustment tertentu.
5. Export laporan packaging ke Excel/PDF.
6. Support lebih dari satu ukuran cup jika dibutuhkan di masa depan.

## 18. Catatan Pending Dari Owner

Data yang masih perlu dilengkapi:

```text
1. Daftar nama item packaging final.
2. Unit tiap item, misalnya pcs, pack, sleeve, atau dus.
3. Apakah semua item wajib dihitung saat open/closing.
4. Role mana saja yang boleh membuat adjustment.
5. Apakah closing boleh dilakukan jika ada adjustment pending.
6. Apakah stok awal boleh dikoreksi oleh kasir biasa atau hanya supervisor.
7. Mapping final produk dimsum/takoyaki ke jenis box.
8. Konfirmasi kategori produk minuman yang wajib menggunakan Cup.
```
