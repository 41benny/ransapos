# JOBDESK: ADMIN BAGIAN PRODUK & GUDANG
**Morest App - Sistem POS & Manajemen Inventory**

---

## 📋 RINGKASAN UMUM
Admin Produk & Gudang bertanggung jawab mengelola semua aspek master data produk, stok inventory, transfer stok antar outlet, dan memastikan ketersediaan barang untuk operasional penjualan.

---

## 👤 PROFIL JABATAN

| Aspek | Deskripsi |
|-------|-----------|
| **Nama Jabatan** | Admin Produk & Gudang (Product & Warehouse Admin) |
| **Level Akses** | Manager / Admin |
| **Departemen** | Operasional / Warehouse / Inventory |
| **Supervisor** | Manager Operasional / Head of Operations |
| **Lokasi Kerja** | Kantor Pusat / Gudang (Outlet Gudang) |

---

## 🎯 TUJUAN UTAMA
1. **Memastikan stok produk tersedia** di setiap outlet sesuai kebutuhan
2. **Menjaga integritas data master produk** (SKU, harga, kategori, spesifikasi)
3. **Mengoptimalkan distribusi stok** melalui transfer yang efisien antar outlet
4. **Mengendalikan dan meminimalkan stok mati** (dead stock)
5. **Memberikan informasi stok real-time** untuk mendukung keputusan penjualan
6. **Menjamin akurasi laporan inventory** untuk keperluan keuangan dan analisis

---

## 📌 TANGGUNG JAWAB UTAMA

### 1. MANAJEMEN MASTER DATA PRODUK
**Deskripsi**: Mengelola database produk lengkap untuk semua outlet

#### Aktivitas:
- ✅ **Membuat/Menambah Produk Baru**
  - Masuk ke menu **Admin → Master Data → Produk**
  - Input data: nama produk, SKU, kategori, harga jual, harga beli, harga HPP
  - Tentukan satuan (pcs, pak, karton, liter, dll)
  - Set minimum stok (reorder point)
  - Upload foto/gambar produk jika ada
  - Pilih status: Aktif/Tidak Aktif
  - Sistem otomatis generate SKU bila diaktifkan

- ✅ **Edit/Update Informasi Produk**
  - Ubah harga jual/beli sesuai perubahan supplier
  - Update kategori produk
  - Ubah minimum stok berdasarkan penjualan
  - Validasi nama produk tidak duplikat
  - Perubahan tercatat untuk audit trail

- ✅ **Kategorisasi Produk**
  - Memastikan setiap produk masuk kategori yang tepat (Dimsum, Minuman, Sauce, dll)
  - Menambah kategori baru jika diperlukan
  - Mengelompokkan produk untuk kemudahan laporan

- ✅ **Import Produk Massal (Bulk)**
  - Upload file Excel untuk menambah produk sekaligus
  - Format: SKU, Nama Produk, Kategori, Harga Jual, Harga Beli, Unit, Min Stock
  - Validasi otomatis sebelum import
  - Laporan hasil import: berapa produk berhasil, error, dll

- ✅ **Menon-aktifkan Produk Lama**
  - Set status "Tidak Aktif" untuk produk yang sudah tidak dijual
  - Mencegah produk lama muncul di POS

#### Menu yang Diakses:
```
📱 Admin Panel
  ├─ Master Data
  │  ├─ Produk
  │  │  ├─ Daftar Produk (View, Search, Filter)
  │  │  ├─ Tambah Produk Baru (Create)
  │  │  ├─ Edit Produk (Update)
  │  │  ├─ Hapus Produk (Delete)
  │  │  ├─ Import Produk dari Excel
  │  │  └─ Generate SKU Otomatis
  │  └─ Kategori Produk
```

---

### 2. MANAJEMEN BUNDLE/PAKET PRODUK (BOM - Bill of Materials)
**Deskripsi**: Membuat produk bundling (paket produk jadi dari komponen bahan baku)

#### Aktivitas:
- ✅ **Membuat Produk Bundle**
  - Masuk ke **Admin → Master Data → Produk → Tambah Bundle**
  - Definisikan produk bundel (mis: "Paket Dimsum Favorit Pp30")
  - Tambahkan komponen pembangun (raw materials): 
    - Siomay Ayam (2 pak), Udang (1 pak), Pangsit (1 pak)
  - Set harga jual paket
  - Set harga HPP (otomatis kalkulasi dari komponen)
  - Tentukan outlet mana saja yang bisa menjual paket ini
  - Status: Aktif/Tidak Aktif

- ✅ **Edit Komposisi Bundle**
  - Ubah komponen & jumlah bila ada perubahan resep/komposisi
  - Update harga bundel
  - Validasi: stok bahan mentah mencukupi sebelum penjualan dimulai

- ✅ **Monitoring Ketersediaan Bundle Material**
  - Cek stok komponen pembentuk bundle
  - Alert: jika stok komponen kurang, bundle tidak bisa dijual
  - Koordinasi dengan warehouse untuk supply komponen

#### Menu yang Diakses:
```
📱 Admin Panel
  ├─ Master Data
  │  ├─ Produk
  │  │  ├─ Tambah Bundle Produk
  │  │  ├─ Daftar Bundle Produk
  │  │  └─ Edit Komposisi Bundle
  │  └─ Bill of Materials (BOM)
  │     ├─ Daftar BOM
  │     ├─ Buat/Edit BOM
  │     └─ View Detail Komponen
```

---

### 3. MANAJEMEN STOK & INVENTORY

#### A. MONITORING STOK REAL-TIME
**Aktivitas**:
- ✅ **Lihat Dashboard Stok Keseluruhan**
  - Masuk ke **Admin → Inventory → Stock Overview**
  - Lihat statistik: Total produk, Total nilai stok, Produk stok minimum, Produk habis
  - Monitoring stok per outlet (Gudang, Outlet A, Outlet B, dll)
  - Filter berdasarkan kategori produk
  - Lihat status stok: Normal ✅ / Minimum ⚠️ / Habis ❌

- ✅ **Filter & Pencarian Produk**
  - Cari berdasarkan nama produk, SKU
  - Filter by outlet
  - Filter by kategori
  - Lihat harga beli vs harga jual
  - Last modified tracking

#### B. ADJUSTMENT STOK (OPNAME)
**Aktivitas**:
- ✅ **Melakukan Stock Opname (Fisik)**
  - Koordinasi dengan warehouse untuk stock taking
  - Catat hasil fisik dibanding sistem
  - Masuk ke **Admin → Inventory → Stock Adjustment**
  - Input outlet & produk yang akan di-adjust
  - Sistem tampilkan stok saat ini (sistem)
  - Input stok aktual (hasil fisik opname)
  - Sistem otomatis hitung selisih (Δ)
  - Input alasan/keterangan (rusaknya stok lama, error input, dll)
  - Submit & otomatis buat stock mutation record

- ✅ **Analisis Penyimpangan Stok**
  - Review barang yang sering minus
  - Identifikasi kemungkinan pencurian/kerusakan
  - Report ke supervisor

#### C. RIWAYAT MUTASI STOK
**Aktivitas**:
- ✅ **Lihat Audit Trail Semua Mutasi Stok**
  - Masuk ke **Admin → Inventory → Stock Mutations**
  - Tipe mutasi: Pembelian, Penjualan, Transfer, Adjustment, Cancel Penjualan
  - Filter by tanggal, outlet, tipe mutasi, produk
  - Lihat siapa yang melakukan mutasi (created by)
  - Lihat sebelum & sesudah stok

- ✅ **Analisis Penggunaan Bahan Baku** ("Pemakaian Bahan Baku")
  - Pilih filter "Pemakaian Bahan Baku" (dari sales + sales cancellation)
  - Pilih periode tanggal
  - Lihat berapa banyak bahan yang terpakai dari setiap penjualan
  - Export untuk analisis lebih lanjut (cost of goods sold - COGS)

#### D. STOCK CARD (KARTU STOK)
**Aktivitas**:
- ✅ **Lihat Gerak Stok Detail Per Produk**
  - **Admin → Inventory → Stock Card**
  - Pilih produk & outlet
  - Set periode (start date - end date)
  - Lihat IN/OUT columns dengan running balance
  - Summary: Total Masuk, Total Keluar, Perubahan Netto
  - Setiap baris mencatat referensi & catatan mutasi
  - Format: seperti kartu stok manual (untuk dokumentasi)

#### Menu yang Diakses:
```
📱 Admin Panel
  ├─ Inventory & Stock Management
  │  ├─ Stock Overview (Dashboard)
  │  │  ├─ Daftar Stok Semua Produk
  │  │  ├─ Filter by Outlet / Kategori / Search
  │  │  ├─ View Low Stock Alert
  │  │  ├─ View Out of Stock Items
  │  │  └─ Click untuk lihat Stock Card
  │  ├─ Stock Adjustment (Opname)
  │  │  ├─ Form Adjustment
  │  │  └─ History Adjustment
  │  ├─ Stock Mutations (Riwayat)
  │  │  ├─ Tab: Semua Mutasi
  │  │  ├─ Tab: Penjualan & Cancellation
  │  │  ├─ Tab: Pembelian
  │  │  ├─ Tab: Transfer
  │  │  ├─ Tab: Adjustment
  │  │  └─ Filter & Export
  │  ├─ Stock Card
  │  │  ├─ Pilih Produk & Outlet
  │  │  ├─ Set Periode
  │  │  └─ Lihat Gerak Stok In/Out
  │  └─ Export Current Stock
  │     └─ Download Excel/PDF
```

---

### 4. TRANSFER STOK ANTAR OUTLET

#### A. MEMBUAT REQUEST TRANSFER
**Aktivitas**:
- ✅ **Buat Transfer Stok Dari Gudang ke Outlet**
  - Masuk ke **Admin → Inventory → Stock Transfer**
  - Klik "Buat Transfer Baru"
  - Input:
    - **Dari Outlet** (SOURCE): Gudang / Outlet mana
    - **Ke Outlet** (DESTINATION): Ke Outlet mana
    - **Tanggal Transfer**
    - **Produk & Jumlah** (bisa multiple)
    - **Catatan** (untuk apa transfer ini)
  - Sistem cek stok di outlet asal (pastikan available)
  - Sistem auto-generate "Transfer Number": TRF-FROM-TO-YYYYMMDD-XXX
  - Status awal: **PENDING** (belum dikirim)
  - Simpan draft

#### B. WORKFLOW TRANSFER (3 STATUS)
**Status Progression**:

| Status | Artinya | Siapa | Efek Stok |
|--------|---------|-------|-----------|
| **PENDING** | Transfer baru, belum dikirim | Admin/Warehouse | Stok asal tetap, stok tujuan belum bertambah |
| **IN_TRANSIT** | Sedang dalam perjalanan | Warehouse (saat "kirim") | Stok dikurangi dari asal, tujuan belum tambah |
| **RECEIVED** | Sudah diterima | Outlet tujuan (saat "terima") | Stok otomatis bertambah di outlet tujuan |
| **CANCELLED** | Transfer dibatalkan | Admin | Revert stok (jika sudah dikirim, return stok asal) |

#### C. PROSES KIRIM (SEND)
**Aktivitas**:
- ✅ **Mengirim Transfer dari Warehouse**
  - Buka transfer dengan status **PENDING**
  - Verifikasi stok tersedia di outlet asal
  - Klik "KIRIM" / **Send Transfer**
  - Sistem:
    - Kurangi stok outlet asal
    - Buat stock mutation: `transfer_out`
    - Update status → **IN_TRANSIT**
    - Catat waktu & siapa yang kirim (sent_by)
  - Transfer siap dalam perjalanan

#### D. PROSES TERIMA (RECEIVE)
**Aktivitas**:
- ✅ **Menerima Transfer di Outlet Tujuan**
  - Staff outlet tujuan buka aplikasi POS
  - Lihat transfer masuk dengan status **IN_TRANSIT**
  - Verifikasi barang fisik saat diterima
  - Klik "TERIMA" / **Receive Transfer**
  - Input jika ada perbedaan:
    - Jumlah yang diterima vs jumlah yg dikirim
    - Barang rusak/cacat
    - Shortfall (kurang)
  - Sistem:
    - Tambah stok ke outlet tujuan
    - Buat stock mutation: `transfer_in`
    - Update status → **RECEIVED**
    - Catat waktu & siapa yang terima (received_by)
  - Auto-adjust jika ada selisih

#### E. PEMBATALAN TRANSFER
**Aktivitas**:
- ✅ **Membatalkan Transfer (jika ada kendala)**
  - Buka transfer status **PENDING** atau **IN_TRANSIT**
  - Klik "BATALKAN" / **Cancel Transfer**
  - Input alasan pembatalan
  - Jika sudah IN_TRANSIT:
    - Otomatis return stok ke outlet asal
    - Buat adjustment mutation record
  - Update status → **CANCELLED**
  - Catat pembatalnya & alasannya

#### F. LAPORAN & EXPORT TRANSFER
**Aktivitas**:
- ✅ **Lihat Riwayat & Laporan Transfer**
  - **Admin → Inventory → Stock Transfer List**
  - Filter by periode, status (pending/sent/received/cancelled)
  - Filter by outlet asal/tujuan
  - Export ke Excel/PDF: untuk dokumentasi
  - Print transfer note (surat jalan)

#### Menu yang Diakses:
```
📱 Admin Panel
  ├─ Inventory & Stock Management
  │  └─ Stock Transfer
  │     ├─ Daftar Transfer (List)
  │     ├─ Buat Transfer Baru (Create)
  │     ├─ Detail & Edit Transfer (Show/Edit) - status PENDING
  │     ├─ Kirim Transfer (Send) - status PENDING → IN_TRANSIT
  │     ├─ Form Terima Transfer (Receive Form) - status IN_TRANSIT
  │     ├─ Proses Terima (Receive) - status IN_TRANSIT → RECEIVED
  │     ├─ Batalkan Transfer (Cancel) - dari PENDING atau IN_TRANSIT
  │     ├─ Lihat Detail Transfer (Show)
  │     ├─ Print Transfer Note
  │     ├─ Export List ke Excel
  │     └─ Export List ke PDF
```

---

### 5. MANAJEMEN POMOSI & VOUCHER (PROMO)

#### Aktivitas:
- ✅ **Membuat Promosi/Diskon Produk**
  - Masuk ke **Admin → Promo & Voucher**
  - Tipe promosi: 
    - Diskon % untuk kategori produk tertentu
    - Diskon fix untuk produk spesifik
    - Buy X Get Y (BOGO)
  - Set periode promo (start date - end date)
  - Tentukan outlet mana saja berlaku
  - Tentukan kategori produk yang termasuk promo
  - Status: Aktif/Tidak Aktif

- ✅ **Membuat Voucher/Kupon Penjualan**
  - Buat kode voucher (mis: PROMO50)
  - Tipe value: Fix amount atau Percentage
  - Min purchase (misal min 100K)
  - Max discount (cap diskon max 50K)
  - Limit jumlah voucher (misal hanya 100 pcs)
  - Outlet berlaku
  - Periode aktif
  - Toggle aktif/non-aktif kapan saja

- ✅ **Monitoring Penggunaan Promo**
  - Report berapa voucher sudah terpakai
  - Revenue impact dari promosi

#### Menu yang Diakses:
```
📱 Admin Panel
  ├─ Marketing & Promosi
  │  └─ Promo & Voucher
  │     ├─ Daftar Promosi (List)
  │     ├─ Buat Promosi Baru (Store)
  │     ├─ Toggle Promosi (Aktif/Tidak)
  │     ├─ Hapus Promosi (Delete)
  │     ├─ Daftar Voucher (List)
  │     ├─ Buat Voucher Baru (Store)
  │     ├─ Toggle Voucher (Aktif/Tidak)
  │     └─ Hapus Voucher (Delete)
```

---

### 6. MANAJEMEN SUPPLIER & PEMBELIAN

#### A. SUPPLIER DAN DATA PEMBELIAN
**Aktivitas**:
- ✅ **Lihat Daftar Supplier**
  - **Admin → Master Data → Supplier**
  - Lihat informasi supplier: nama, kontak, alamat, payment term
  - Filter supplier aktif

- ✅ **Tracking Pembelian (Purchase Order)**
  - **Admin → Inventory → Purchase Orders**
  - Lihat PO: nomor, supplier, tanggal, status (draft/pending/received/paid)
  - Filter by supplier, date range
  - Lihat purchase items (detail barang yang dibeli)
  - Status pembelian: 
    - **DRAFT**: Belum final
    - **PENDING**: Sudah submit tapi belum diterima
    - **RECEIVED**: Stok sudah masuk
    - **PAID**: Sudah dibayar

- ✅ **Received Goods (Penerimaan Barang dari Supplier)**
  - Buka PO status PENDING
  - Verifikasi barang fisik saat tiba
  - Catat jumlah yang diterima
  - Jika ada shortage/rusak, update qty
  - Sistem otomatis update stok di Gudang

- ✅ **Process Payment Pembelian**
  - Lihat PO yang sudah RECEIVED
  - Input pembayaran (via kas, cek, transfer)
  - Status berubah → PAID

#### Menu yang Diakses:
```
📱 Admin Panel
  ├─ Master Data
  │  └─ Supplier
  │     └─ Daftar Supplier (View)
  ├─ Purchasing & Inventory
  │  └─ Purchase Orders
  │     ├─ Daftar PO (List)
  │     ├─ Detail PO (Show)
  │     ├─ Terima PO / Receiving (Receive)
  │     ├─ Print PO (Print)
  │     × Bayar PO (Payment) - bisa dilakukan finance
  │     └─ Cancel PO (Cancel)
```

---

### 7. LAPORAN & ANALISIS

#### Aktivitas:
- ✅ **Laporan Stok per Outlet**
  - Export daftar stok semua outlet
  - Format: Excel/PDF dengan tampilan rapi
  - Include: SKU, Produk, Qty, Min Stock, Status, Last Update

- ✅ **Laporan Stok & Nilai Inventory**
  - Total nilai stok (qty × harga beli)
  - Breakdown per kategori
  - Breakdown per outlet

- ✅ **Laporan Pergerakan Stok**
  - Periode: bulan, quarter
  - Lihat produk best seller (most moved)
  - Lihat produk slow moving (jarang terjual)
  - Identifikasi dead stock candidate

- ✅ **Laporan Pemakaian Bahan Baku**
  - Khusus untuk produk bundle
  - Berapa banyak setiap komponen terpakai
  - Untuk cost calculation & planning

#### Menu yang Diakses:
```
📱 Admin Panel
  ├─ Reports
  │  ├─ Stock Reports
  │  │  ├─ Current Stock List (Export)
  │  │  ├─ Stock Value Report
  │  │  ├─ Stock Card Report
  │  │  └─ Low Stock Alert
  │  ├─ Inventory Movement
  │  │  ├─ Product Movement Report
  │  │  ├─ Slow Moving Items
  │  │  ├─ Dead Stock Analysis
  │  │  └─ Bahan Baku Pemakaian (BOM Usage)
  │  ├─ Transfer Report
  │  │  └─ Stock Transfer History
  │  └─ Purchase Report
  │     └─ Purchase Summary
```

---

### 8. PENGATURAN OUTLET & KETERSEDIAAN PRODUK

#### Aktivitas:
- ✅ **Mengatur Outlet**
  - Tambah outlet baru (jika ada ekspansi)
  - Update nama, alamat, kontak outlet
  - Set default cash account per outlet
  - Set status outlet: Aktif/Tidak Aktif

- ✅ **Set Ketersediaan Produk per Outlet**
  - Tentukan produk mana saja yang tersedia di setiap outlet
  - Contoh: Outlet Gudang punya semua produk (lengkap)
  - Outlet A hanya stok produk tertentu saja
  - Sistem enforce: produk yang tidak di-list tidak bisa dijual di outlet tersebut (tidak hardcoded)

#### Menu yang Diakses:
```
📱 Admin Panel
  ├─ Master Data
  │  └─ Outlet
  │     ├─ Daftar Outlet (List)
  │     ├─ Tambah Outlet Baru (Create)
  │     ├─ Edit Outlet (Update)
  │     └─ Set Product Availability per Outlet
```

---

## 📊 DASHBOARD & ANALYTICS

### Admin Dashboard
**Menu**: Admin → Dashboard

**Informasi yang Ditampilkan**:
- 📦 **Statistik Inventory**
  - Total produk (aktif)
  - Total nilai stok (semua outlet)
  - Produk dengan stok minimum (alert)
  - Produk yang habis (out of stock)

- 📈 **Sales Overview**
  - Total penjualan (periode harian/bulanan)
  - Top 10 produk paling laris
  - Revenue per outlet

- 💰 **Financial Summary**
  - Total HPP (harga pokok) penjualan
  - Gross profit estimate
  - Cash position

- 🚚 **Transfer & Logistik**
  - Transfer pending (belum dikirim)
  - Transfer in transit (dalam perjalanan)
  - Transfer received (selesai)

---

## 🔐 PERMISSION & ACCESS CONTROL

### Permissions yang Dimiliki Admin Produk:
```
✅ PRODUCTS
   - products.view ............. Lihat daftar produk
   - products.create ........... Tambah produk baru
   - products.update ........... Edit produk
   - products.delete ........... Hapus produk
   - products.import ........... Import massal dari Excel

✅ STOCKS
   - stocks.view ............... Lihat stok overview
   - stocks.adjust ............. Adjustment stok (opname)

✅ STOCK TRANSFERS
   - stock-transfers.create .... Buat transfer baru
   - stock-transfers.view ...... Lihat daftar transfer
   - stock-transfers.update .... Edit, kirim, terima transfer
   - stock-transfers.cancel .... Batalkan transfer

✅ BILL OF MATERIALS (BOM)
   - boms.view ................. Lihat BOM
   - boms.create ............... Buat BOM/Bundle produk
   - boms.update ............... Edit BOM
   - boms.delete ............... Hapus BOM

✅ SUPPLIERS
   - suppliers.view ............ Lihat daftar supplier

✅ PURCHASES
   - purchases.view ............ Lihat PO
   - purchases.create .......... Buat PO
   - purchases.update .......... Edit PO
   - purchases.receive ......... Terima barang (PO)
   - purchases.payment ......... Bayar PO

✅ OUTLETS
   - outlets.view .............. Lihat outlet
   - outlets.create ............ Tambah outlet
   - outlets.update ............ Edit outlet

✅ PROMO & VOUCHER
   - promo-vouchers.view ....... Lihat promo/voucher
   - promo-vouchers.manage .... Kelola promo/voucher

✅ DASHBOARD
   - dashboard.view ............ Lihat admin dashboard

✅ REPORTS
   - reports.view .............. Lihat laporan
   - stocks.export ............. Export stok
```

---

## 📱 TOOLS & SISTEM YANG DIGUNAKAN

### Hardware/Software:
- 🖥️ **Komputer/Laptop** (akses admin portal)
- 📱 **Mobile** (optional, untuk access via smartphone saat survey/stock check)
- 🖨️ **Printer** (untuk print transfer note, PO, laporan)

### Software/Aplikasi:
- **Morest App Admin Panel** (Browser: Chrome, Firefox, Edge)
- **Microsoft Excel** (import/export data)
- **Email** (komunikasi dengan supplier, outlet)

---

## 📅 WORKFLOW HARIAN / MINGGUAN / BULANAN

### HARIAN:
- ⏰ **Pagi (08:00-09:00)**
  - Login ke admin panel
  - Review dashboard: ada alert stok minimum atau habis?
  - Chat dengan manager: ada order khusus atau promotasi?

- ⏰ **Siang (10:00-12:00)**
  - Monitor pemesanan (PO) dari supplier yang sedang in process
  - Terima stok/barang masuk dari supplier
  - Review laporan penjualan kemarin (untuk barang fast moving)
  - Planning: outlet mana yang perlu restok

- ⏰ **Sore (13:00-16:00)**
  - Buat transfer stok ke outlet yang perlu
  - Verifikasi transfer barang (cek fisik)
  - Input data adjustment jika ada opname

- ⏰ **Akhir hari (16:00-17:00)**
  - Rekonsiliasi stok sistem vs barang fisik jika ada kendala
  - Export laporan untuk review manager
  - Koordinasi dengan warehouse: ada produk yang stuck?

### MINGGUAN:
- **Senin-Jumat**: Aktivitas harian sebagai di atas
- **Jumat Sore**: 
  - Export laporan pergerakan stok minggu ini
  - Analisis: produk apa yang paling cepat habis?
  - Plan pemesanan supplier untuk minggu depan
- **Sabtu/Minggu**: 
  - Jika perlu (tergantung jam operasi outlet)
  - Stock check fisik di warehouse
  - Update data master jika ada masukan

### BULANAN:
- **Minggu Pertama**: 
  - Stock opname lengkap (fisik count semua produk semua outlet)
  - Buat adjustment entries
  - Rekonsiliasi sistem vs fisik (selisih < 5%)
  
- **Minggu Kedua**:
  - Review laporan inventory bulanan
  - Analisis: produk slow moving, dead stock candidate
  - Report HPP vs aktual ke finance
  
- **Minggu Ketiga**:
  - Plan pembelian supplier untuk bulan depan
  - Verifikasi stok minimum (reorder point) masih sesuai?
  - Review supplier performance (kecepatan, kualitas, harga)
  
- **Akhir Bulan**:
  - Close inventory register
  - Catat nilai akhir stok untuk laporan keuangan
  - Buat summary report untuk management

---

## 🎓 KEAHLIAN & KOMPETENSI YANG DIBUTUHKAN

### Technical Skills:
- ✅ Profisiensi Microsoft Excel (pivot table, formula)
- ✅ Penggunaan sistem aplikasi web-based (browser)
- ✅ Basic inventory management concept (FIFO, reorder point, SKU)
- ✅ Quality control (memeriksa barang masuk)

### Soft Skills:
- ✅ Komunikasi yang baik (dengan warehouse, supplier, outlet manager)
- ✅ Detail-oriented (stok harus akurat)
- ✅ Problem-solving (jika ada selisih/discrepancy stok)
- ✅ Time management (banyak task, harus prioritas)
- ✅ Integritas tinggi (tidak ada markup stok pribadi)

### Domain Knowledge:
- ✅ Pemahaman produk dimsum & frozen food
- ✅ Manajemen rantai pasokan (supply chain) dasar
- ✅ Perhitungan HPP (cost of goods)
- ✅ Pemahaman multi-outlet operations

---

## ⚠️ TANGGUNGJAWAB KHUSUS

### JANGAN DILAKUKAN:
- ❌ **Jangan edit harga tanpa sepengetahuan manager**
- ❌ **Jangan hapus produk sembarangan** (bisa error di laporan jual)
- ❌ **Jangan approve transfer tanpa verifikasi stok fisik**
- ❌ **Jangan input adjustment opname tanpa catatan** (audit trail)
- ❌ **Jangan share akses admin ke orang lain** (security risk)

### WAJIB DILAKUKAN:
- ✅ **Catat setiap aktivitas** (mutasi stok, transfer, adjustment tercatat di sistem)
- ✅ **Verifikasi barang masuk dari supplier** (cek kualitas, jumlah vs invoice)
- ✅ **Laporan stok harus akurat** (vital untuk financial reporting)
- ✅ **Koordinasi dengan warehouse** (jika ada masalah stok)
- ✅ **Backup data** atau jaga integritas data (jangan accidental delete)

---

## 📞 ESCALATION & REPORTING

### Laporan Langsung Kepada:
- **Manager Operasional** / **Head of Warehouse**

### Koordinasi Dengan:
- 🤝 **Warehouse Staff**: Penerimaan barang, stock check, packing
- 🤝 **POS Kasir/Outlet**: Keluhan ketersediaan stok
- 🤝 **Finance**: Laporan inventory untuk akun kas
- 🤝 **Supplier**: Tracking PO, negosiasi
- 🤝 **Marketing**: Promo & voucher coordination

---

## 📋 PERFORMANCE METRICS & KPI

### Yang Diukur:
| KPI | Target | Tolak Ukur |
|-----|--------|-----------|
| **Akurasi Stok** | > 98% | Selisih stock opname < 2% |
| **Waktu Penerimaan Barang** | < 1 hari | Barang masuk → input sistem |
| **Ketersediaan Produk** | > 95% | Produk ready untuk penjualan |
| **Update Master Data** | Real-time | Harga/info terupdate di sistem |
| **Stock Transfer Success** | > 99% | Transfer berjalan sesuai plan |
| **Dead Stock Reduction** | Minimize | Review & dispose slow moving items |
| **Responsiveness** | < 2 jam | Reply inquiry stok dari outlet |

---

## 🚀 PENGEMBANGAN KARIR

### Peluang Promosi:
- 📈 **Warehouse Manager**: Manage team warehouse, planning supply chain
- 📈 **Operations Manager**: Manage multi-outlet operations
- 📈 **Logistics Coordinator**: Optimize supplier & distribution network

### Development Needs:
- 📚 Training: Supply Chain Management
- 📚 Training: Inventory Management Best Practices
- 📚 Sertifikasi: APICS (CSCP) opsional

---

## 📝 DOKUMENTASI & SOP

### Dokumen Referensi:
- ✅ [INVENTORY-STOCK-MANAGEMENT-SUMMARY.txt](./INVENTORY-STOCK-MANAGEMENT-SUMMARY.txt) - Detail fitur inventory
- ✅ [TUGAS-12-BOM-ENHANCEMENTS-SUMMARY.txt](./TUGAS-12-BOM-ENHANCEMENTS-SUMMARY.txt) - Detail BOM/Bundle
- ✅ Setup Produk & Supplier (terlampir)
- ✅ Stock Transfer Workflow (terlampir)

---

## 🎯 KESIMPULAN

Admin Produk & Gudang adalah posisi kunci dalam operasional Morest App. Tanggung jawab utama adalah **memastikan stok akurat, tersedia, dan efisien distribusinya** agar POS dapat beroperasi lancar dan profit optimal.

Dengan sistem terintegrasi ini, semua aktivitas inventory tercatat dan ter-audit, sehingga memudahkan planning dan mengurangi kesalahan manual.

**Status**: ✅ Siap Operasional  
**Last Updated**: 2026-01-30  
**Version**: 1.0
