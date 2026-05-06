# JOBDESK: FINANCE / ACCOUNTING DEPARTMENT
**Morest App - Sistem POS & Manajemen Keuangan**

---

## 📋 RINGKASAN UMUM
Finance/Accounting bertanggung jawab mengelola semua aspek keuangan perusahaan, mulai dari pencatatan transaksi kas/bank, pengelolaan pengeluaran, pembayaran hutang supplier, rekonsiliasi, pengarsipan dokumen keuangan, hingga laporan keuangan yang akurat untuk keperluan manajemen dan audit.

---

## 👤 PROFIL JABATAN

| Aspek | Deskripsi |
|-------|-----------|
| **Nama Jabatan** | Finance / Accounting Staff (Karyawan Keuangan) |
| **Level Akses** | Admin / Pajak |
| **Departemen** | Finance & Accounting |
| **Supervisor** | Finance Manager / Chief Accountant |
| **Lokasi Kerja** | Kantor Pusat |
| **Jam Kerja** | 08:00 - 17:00 (Senin - Jumat) + End-of-Month Off-site |

---

## 🎯 TUJUAN UTAMA
1. **Mencatat semua transaksi keuangan** dengan akurat dan tepat waktu
2. **Mengelola kas dan bank accounts** untuk memastikan likuiditas optimal
3. **Memproses pengeluaran & pembayaran** dengan workflow approval yang ketat
4. **Merekonsiliasi laporan bank** dengan pembukuan (bank reconciliation)
5. **Mengarsipkan dokumen keuangan** dengan sistem yang terstruktur dan aman
6. **Menyiapkan laporan keuangan** untuk management dan audit
7. **Mengelola piutang & hutang** supplier dengan tertib
8. **Memberikan insight keuangan** untuk keputusan bisnis yang lebih baik

---

## 📌 TANGGUNG JAWAB UTAMA

### 1. MANAJEMEN AKUN KAS & BANK

#### A. SETUP AKUN KAS/BANK (Inisial/Setup Only)
**Deskripsi**: Membuat daftar akun kas dan bank yang akan digunakan untuk recording transaksi

**Aktivitas**:
- ✅ **Membuat Akun Kas/Bank Baru**
  - Masuk ke **Admin → Finance → Cash Accounts**
  - Klik "Buat Akun Kas/Bank"
  - Input data:
    - Nama akun (mis: "Kas Toko Gudang", "BNI Rekening Operasional")
    - Tipe akun: Cash (Tunai) / Bank (Transfer)
    - Nomor rekening (jika bank)
    - Bank name (jika bank)
    - Saldo awal/opening balance
    - Outlet yang menggunakan akun ini
    - Status: Aktif/Tidak Aktif
  - Simpan

- ✅ **Edit Informasi Akun**
  - Update nama akun, nomor rekening
  - Update status (misal: non-aktifkan akun lama)
  - Tidak bisa edit saldo (hanya via transaksi)

- ✅ **Lihat Ringkasan Akun**
  - **Admin → Finance → Cash Accounts**
  - Dashboard dengan summary:
    - Total Kas (all cash accounts)
    - Total Bank (all bank accounts)
    - Total Balance (kas + bank)
  - Per-akun: lihat saldo terakhir, transaksi terakhir 10x

#### Menu yang Diakses:
```
📱 Admin Panel
  ├─ Finance & Accounting
  │  ├─ Cash Accounts
  │  │  ├─ Daftar Akun Kas/Bank
  │  │  ├─ Buat Akun Baru (Create)
  │  │  ├─ Edit Akun (Update)
  │  │  ├─ Detail Akun (Show)
  │  │  ├─ Hapus Akun (Delete) - hanya jika tidak ada transaksi
  │  │  └─ Mutation Report per Akun
```

---

### 2. PENCATATAN TRANSAKSI KAS & BANK

#### A. INPUT TRANSAKSI KAS (In/Out)
**Aktivitas**:
- ✅ **Input Transaksi Kas Masuk**
  - Masuk ke **Admin → Finance → Cash Transactions**
  - Klik "Tambah Transaksi"
  - Input:
    - Akun kas/bank (mana yang terima)
    - Type: **IN** (penerimaan)
    - Tanggal transaksi
    - Jumlah (amount)
    - Deskripsi (mis: "Setoran piutang dari customer A")
    - Kategori (berdasarkan COA): Revenue, Other Income, etc
    - Reference: nomor PO, nomor invoice, bukti transfer
    - Catatan/notes
  - Submit → Otomatis create cash transaction record
  - Saldo akun otomatis bertambah

- ✅ **Input Transaksi Kas Keluar**
  - Masuk ke **Admin → Finance → Cash Transactions**
  - Klik "Tambah Transaksi"
  - Input:
    - Akun kas/bank (dari mana diambil)
    - Type: **OUT** (pengeluaran)
    - Tanggal transaksi
    - Jumlah (amount)
    - Deskripsi (mis: "Bayar listrik bulanan", "Belanja office supply")
    - Kategori (berdasarkan COA): Utility, Office, etc
    - Reference: nomor invoice supplier
    - Catatan/notes
  - Submit → Otomatis create cash transaction record
  - Saldo akun otomatis berkurang

#### B. LAPORAN TRANSAKSI KAS
**Aktivitas**:
- ✅ **Lihat Daftar Transaksi**
  - **Admin → Finance → Cash Transactions**
  - Lihat semua transaksi (in & out) dari semua akun
  - Filter by akun, tipe (in/out), date range
  - Lihat: tanggal, deskripsi, amount, saldo sebelum/sesudah (running balance)
  - Search by reference no atau deskripsi

- ✅ **Detail Transaksi & Print Voucher**
  - Klik salah satu transaksi → lihat detail lengkap
  - Bisa print voucher (bukti transaksi) untuk dokumentasi
  - Format voucher lengkap dengan header, detail, dan approval

- ✅ **Edit/Hapus Transaksi**
  - Hanya transaksi yang belum di-approve/masih pending bisa diedit
  - Hapus hanya jika diinstruksikan manager (dengan reason)

#### C. EXPORT LAPORAN TRANSAKSI
**Aktivitas**:
- ✅ **Export ke Excel**
  - **Admin → Finance → Cash Transactions**
  - Tombol "Export Excel"
  - Download file dengan semua transaksi + filter yang diterapkan
  - Format: Tanggal, Deskripsi, Type, Amount, Saldo, Reference

- ✅ **Export ke PDF**
  - Tombol "Export PDF"
  - Generate laporan PDF professional
  - Termasuk summary & details
  - Bisa langsung print

#### Menu yang Diakses:
```
📱 Admin Panel
  ├─ Finance & Accounting
  │  ├─ Cash Transactions
  │  │  ├─ Daftar Transaksi (List)
  │  │  ├─ Tambah Transaksi (Create)
  │  │  ├─ Detail Transaksi (Show)
  │  │  ├─ Edit Transaksi (Edit) - hanya pending
  │  │  ├─ Hapus Transaksi (Delete) - with approval
  │  │  ├─ Print Voucher (Print)
  │  │  ├─ Export ke Excel
  │  │  └─ Export ke PDF
  │  └─ Mutation Report per Akun
```

---

### 3. MANAJEMEN PENGELUARAN (EXPENSE MANAGEMENT)

#### A. SETUP KATEGORI PENGELUARAN
**Aktivitas**:
- ✅ **Buat Kategori Pengeluaran**
  - Masuk ke **Admin → Finance → Expense Categories**
  - Klik "Buat Kategori"
  - Input:
    - Nama kategori (mis: "Listrik", "Telepon", "Transportasi")
    - Kode kategori (mis: EXP-001)
    - Parent kategori (optional, untuk kategori hierarki)
    - COA Account (link ke Chart of Accounts)
    - Deskripsi
    - Status: Aktif/Tidak Aktif
  - Simpan

- ✅ **Edit Kategori**
  - Update nama, parent, COA link
  - Non-aktifkan kategori lama

#### B. INPUT PENGELUARAN (EXPENSE)
**Workflow**: PENDING → APPROVED/REJECTED → PAID

**Aktivitas**:

- ✅ **Buat Pengeluaran Baru**
  - **Admin → Finance → Expenses**
  - Klik "Buat Pengeluaran"
  - Input data:
    - Outlet (mana yang keluarkan)
    - Kategori pengeluaran
    - Tanggal pengeluaran
    - Jumlah/amount
    - Metode pembayaran: Cash / Transfer Bank / Kartu Debit / Lainnya
    - Akun kas/bank (jika akan langsung bayar)
    - Nomor referensi (invoice supplier, nomor bukti)
    - Deskripsi pengeluaran
    - Lampiran (receipt/bukti - JPG/PNG/PDF max 2MB)
    - Status awal: **PENDING**
  - Submit → Sistem auto-generate nomor pengeluaran (EXP-OUTLET-YYYYMMDD-XXX)
  - Status: PENDING (waiting approval)

- ✅ **Approval Pengeluaran**
  - Jika ada tanggung jawab approval
  - **Admin → Finance → Expenses**
  - Filter status "PENDING"
  - Review pengeluaran: amount, category, deskripsi, bukti
  - Klik "APPROVE" atau "REJECT"
  - Jika reject: input alasan
  - Jika approve: status → APPROVED, catat approver & waktu
  - Email notif ke requester

- ✅ **Pembayaran Pengeluaran**
  - **Admin → Finance → Expenses**
  - Filter status "APPROVED"
  - Pilih pengeluaran → Klik "BAYAR"
  - Konfirmasi:
    - Amount sesuai?
    - Akun kas/bank sudah dipilih?
  - Submit → Sistem:
    - Auto-create cash transaction (OUT) dari akun yang dipilih
    - Saldo akun berkurang
    - Catat COA account (dari kategori → COA)
    - Status expense → PAID
    - Catat pembayar & waktu

- ✅ **Laporan Pengeluaran**
  - **Admin → Finance → Expenses Reports**
  - Dashboard analytics:
    - Total pengeluaran (semua)
    - Pending amount & count
    - Approved amount
    - Paid amount
    - Rejected count
  - Chart: Pengeluaran by Kategori (Doughnut)
  - Chart: Pengeluaran by Bulan (Line chart - 12 bulan)
  - Filter by outlet, kategori, date range
  - Table detail dengan breakdown

#### Menu yang Diakses:
```
📱 Admin Panel
  ├─ Finance & Accounting
  │  ├─ Expense Categories
  │  │  ├─ Daftar Kategori (List)
  │  │  ├─ Buat Kategori (Create)
  │  │  ├─ Edit Kategori (Edit)
  │  │  ├─ Hapus Kategori (Delete)
  │  │  └─ Link dengan COA
  │  ├─ Expenses
  │  │  ├─ Daftar Pengeluaran (List)
  │  │  ├─ Buat Pengeluaran (Create)
  │  │  ├─ Detail Pengeluaran (Show)
  │  │  ├─ Edit Pengeluaran (Edit) - hanya pending
  │  │  ├─ Approve Pengeluaran (Approve)
  │  │  ├─ Reject Pengeluaran (Reject)
  │  │  ├─ Bayar Pengeluaran (Pay)
  │  │  ├─ Hapus Pengeluaran (Delete) - hanya pending
  │  │  └─ Export Pengeluaran
  │  └─ Expense Reports & Analytics
```

---

### 4. SETUP CHART OF ACCOUNTS (COA)

#### A. STRUKTUR COA (SETUP)
**Aktivitas**:
- ✅ **Membuat Rekening Akun (COA)**
  - Masuk ke **Admin → Finance → Chart of Accounts**
  - Klik "Buat Rekening Baru"
  - Input:
    - Nomor akun (mis: 1-1010 untuk Kas, 4-4010 untuk Revenue)
    - Nama akun (mis: "Kas", "Penjualan")
    - Tipe: Assets / Liabilities / Equity / Revenue / Expense / Cost of Goods Sold
    - Deskripsi
    - Status: Aktif/Tidak Aktif
  - Simpan

- ✅ **Edit COA**
  - Update nama, deskripsi
  - Status on/off

- ✅ **Generate Balance Template**
  - **Admin → Finance → Chart of Accounts**
  - Tombol "Generate Balance Template"
  - Sistem membuat template trial balance dari semua COA aktif
  - Bisa di-input untuk keperluan month-end closing

#### Menu yang Diakses:
```
📱 Admin Panel
  ├─ Finance & Accounting
  │  └─ Chart of Accounts (COA)
  │     ├─ Daftar COA (List)
  │     ├─ Buat COA Baru (Create)
  │     ├─ Detail COA (Show)
  │     ├─ Edit COA (Edit)
  │     ├─ Hapus COA (Delete)
  │     └─ Generate Balance Template
```

---

### 5. PEMBAYARAN SUPPLIER / PURCHASE PAYMENT

#### A. TRACKING PEMBAYARAN SUPPLIER
**Aktivitas**:
- ✅ **Lihat Daftar Purchase Order (PO)**
  - **Admin → Purchasing → Purchase Orders**
  - Lihat PO yang sudah received (stok sudah masuk)
  - Filter by supplier, date, status (received)

- ✅ **Input Pembayaran PO**
  - Klik PO → Lihat detail
  - Klik "BAYAR" / Payment button
  - Input:
    - Akun kas/bank yang digunakan untuk bayar
    - Amount (biasanya = Total PO)
    - Tanggal pembayaran
    - Metode pembayaran: Cash / Transfer / Cek / Lainnya
    - Reference: nomor bukti transfer, nomor cek, dll
  - Submit → Sistem:
    - Auto-create cash transaction (OUT)
    - Saldo akun berkurang
    - Status PO → PAID
    - Catat pembayar & waktu

- ✅ **Laporan Pembayaran Supplier**
  - Export daftar pembayaran supplier (by period)
  - Untuk reconciliation dengan supplier statement

#### Menu yang Diakses:
```
📱 Admin Panel
  ├─ Purchasing & Inventory
  │  └─ Purchase Orders
  │     ├─ Daftar PO
  │     ├─ Detail PO
  │     ├─ Form Pembayaran (Payment)
  │     └─ Proses Pembayaran
```

---

### 6. TRANSFER BANK & BANK RECONCILIATION

#### A. PENCATATAN TRANSFER BANK
**Aktivitas**:
- ✅ **Input Transfer Bank Keluar**
  - **Admin → Finance → Bank Transfers**
  - Klik "Buat Transfer"
  - Input:
    - Bank/akun asal
    - Nama penerima
    - Bank penerima
    - No rekening penerima
    - Amount transfer
    - Deskripsi/tujuan transfer
    - Reference: nomor bukti transfer
    - Tanggal transfer
  - Submit → Sistem create cash transaction (OUT)

- ✅ **Lihat Daftar Transfer Bank**
  - **Admin → Finance → Bank Transfers**
  - Daftar semua transfer keluar
  - Detail: tanggal, dari, ke, amount, reference

#### Menu yang Diakses:
```
📱 Admin Panel
  ├─ Finance & Accounting
  │  └─ Bank Transfers
  │     ├─ Daftar Transfer (List)
  │     ├─ Buat Transfer Baru (Create)
  │     └─ Detail Transfer (Show)
```

---

### 7. CASH SESSIONS (LAPORAN SHIFT KASIR)

#### A. MONITORING SHIFT KASIR
**Aktivitas**:
- ✅ **Lihat Laporan Shift Kasir Harian**
  - **Admin → Finance → Cash Sessions**
  - Lihat ringkasan setiap shift kasir harian:
    - Tanggal, outlet, kasir
    - Opening balance & closing balance
    - Total penjualan (cash vs non-cash)
    - Total expense (dari shift)
    - Difference (selisih kas fisik vs sistem)
  - Filter by outlet, date range

#### Menu yang Diakses:
```
📱 Admin Panel
  ├─ Finance & Accounting
  │  └─ Cash Sessions (History)
  │     └─ Daftar Shift Kasir (List)
```

---

### 8. LAPORAN KEUANGAN & ANALYTICS

#### A. DASHBOARD FINANCE
**Deskripsi**: Overview ringkas situasi keuangan perusahaan

**Informasi yang Ditampilkan**:
- 💰 **Posisi Kas/Bank**
  - Total saldo kas (all cash accounts)
  - Total saldo bank (all bank accounts)
  - Total likuiditas
  - Trend 30 hari

- 📊 **Penjualan & Revenue**
  - Total penjualan (harian/bulanan)
  - Cash vs non-cash split
  - Revenue trend

- 💸 **Pengeluaran**
  - Total pengeluaran (pending vs paid)
  - Top spending categories
  - Expense trend

- 📈 **Dashboard Metrics**
  - Working capital
  - Cash flow projection
  - Outstanding payables
  - Outstanding receivables

#### B. LAPORAN DETAIL
**Aktivitas**:
- ✅ **Laporan Cash Flow**
  - Cash in: Penjualan (cash), Transfer masuk, Lainnya
  - Cash out: Pengeluaran, Pembayaran supplier, Expense bayar, Lainnya
  - Net cash flow periode

- ✅ **Laporan P&L (Profit & Loss)**
  - Revenue (dari penjualan)
  - Cost of Goods Sold (COGS)
  - Gross Profit
  - Operating Expenses (dari expense module)
  - Net Income

- ✅ **Laporan Hutang (Payables)**
  - Daftar supplier yang belum dibayar
  - Outstanding amount
  - Age of payables

- ✅ **Laporan Piutang (Receivables)** (dari CRM)
  - Daftar customer dengan sisa piutang
  - Outstanding amount
  - Age of receivables

#### Menu yang Diakses:
```
📱 Admin Panel
  ├─ Finance & Accounting
  │  └─ Financial Reports
  │     ├─ Dashboard Finance
  │     ├─ Cash Flow Report
  │     ├─ P&L Statement
  │     ├─ Payables Report
  │     ├─ Receivables Report
  │     ├─ Account Balance Report
  │     └─ Export Reports
```

---

### 9. RECONCILIATION & ADJUSTMENT

#### A. BANK RECONCILIATION
**Aktivitas**:
- ✅ **Matching Laporan Bank vs Sistem**
  - Setiap bulan (akhir bulan), terima laporan bank (bank statement)
  - Buka **Admin → Finance → Bank Reconciliation**
  - List transaksi di sistem vs bank statement
  - Identify discrepancies:
    - Transaksi di sistem tapi belum di bank (outstanding)
    - Transaksi di bank tapi belum di sistem (pendapatan yang belum tercatat)
    - Error/kesalahan pada amount
  - Mark yang sudah matched
  - Input adjustment jika ada selisih

#### B. CASH COUNT & ADJUSTMENT
**Aktivitas**:
- ✅ **Opname Kas (Cash Count)**
  - Hitung kas fisik di tangan (dari POS, kasir)
  - Bandingkan dengan saldo sistem
  - Jika ada selisih:
    - Input "Cash Adjustment" transaksi
    - Alasan: kerusakan, shortage, dll
    - Catat di cash transaction

#### Menu yang Diakses:
```
📱 Admin Panel
  ├─ Finance & Accounting
  │  ├─ Bank Reconciliation
  │  │  ├─ List Transactions (System vs Bank)
  │  │  ├─ Mark Matched Items
  │  │  └─ Record Adjustment
  │  └─ Cash Adjustment
  │     ├─ Opname Form
  │     └─ Record Adjustment
```

---

### 10. PENGARSIPAN DOKUMEN KEUANGAN

#### A. PENGELOLAAN ARSIP FISIK
**Deskripsi**: Mengorganisir dan menyimpan dokumen fisik dengan sistem filing yang efisien

**Aktivitas**:
- ✅ **Menerima & Verifikasi Dokumen**
  - Terima voucher kas/bank, receipt expense, invoice supplier, laporan bank
  - Verifikasi kelengkapan: tanggal, nomor, tanda tangan, amount
  - Log penerimaan di register arsip

- ✅ **Filing Dokumen Fisik**
  - Klasifikasi berdasarkan kategori: Cash, Expense, Purchase, Bank, Tax, Reports
  - Sistem filing: By tahun → bulan → kategori → nomor urut
  - Label folder dengan kode standar (mis: "2026-01-CASH", "2026-12-TAX")
  - Simpan di filing cabinet terkunci

- ✅ **Print & Arsip Voucher**
  - Setelah input transaksi kas/bank keluar, wajib print voucher
  - Voucher berisi: detail transaksi, tanda tangan approval, tanggal
  - Arsip voucher fisik di folder sesuai periode
  - Scan voucher untuk backup digital

#### B. DIGITALISASI & BACKUP
**Aktivitas**:
- ✅ **Scanning Dokumen**
  - Scan semua dokumen fisik dengan scanner
  - Format PDF searchable (OCR-enabled)
  - Naming: [Kategori]-[Tanggal]-[Nomor]-[Deskripsi].pdf
  - Contoh: "CASH-20260115-001-VoucherKasKeluar.pdf"

- ✅ **Upload ke Sistem Digital**
  - Upload file ke folder terstruktur: /Finance/[Tahun]/[Bulan]/[Kategori]/
  - Link dengan transaksi di sistem jika memungkinkan
  - Backup ke external HDD harian, cloud bulanan

- ✅ **Maintenance Arsip**
  - Inventory arsip bulanan (cross-check fisik vs digital)
  - Reorganisasi filing cabinet quarterly
  - Monitor retensi dokumen (7 tahun untuk pajak, 5 tahun operasional)

#### C. RETENSI & PEMUSNAHAN
**Aktivitas**:
- ✅ **Klasifikasi Retensi**
  - Dokumen penting (7 tahun): Faktur pajak, SPT, laporan keuangan
  - Dokumen operasional (5 tahun): Voucher kas, receipt expense
  - Dokumen internal (3 tahun): Memo, approval notes

- ✅ **Pemusnahan Dokumen Expired**
  - Approval dari Finance Manager sebelum pemusnahan
  - Shredding fisik, secure delete digital
  - Log pemusnahan dengan certificate

#### D. REQUEST & RETRIEVAL DOKUMEN
**Aktivitas**:
- ✅ **Handle Internal Request**
  - Dari management, auditor, atau departemen lain
  - Verifikasi authorization
  - Retrieve dokumen fisik/digital
  - Log access: siapa, kapan, untuk apa

- ✅ **Persiapan Audit**
  - Koordinasi dengan auditor eksternal
  - Siapkan dokumen yang diminta
  - Buat index untuk audit trail

#### Menu yang Diakses:
```
📱 Admin Panel
  ├─ Finance & Accounting
  │  └─ Document Archive
  │     ├─ Upload Dokumen Digital
  │     ├─ Search Dokumen
  │     ├─ Download Dokumen
  │     └─ View Arsip History
```

---

## 🔐 PERMISSION & ACCESS CONTROL

### Permissions yang Dimiliki Finance Staff:
```
✅ CASH ACCOUNTS
   - cash-accounts.create ........ Buat akun kas/bank
   - cash-accounts.view ......... Lihat daftar akun
   - cash-accounts.update ....... Edit akun
   - cash-accounts.delete ....... Hapus akun (hanya jika no transaksi)
   - cash-accounts.mutation-report.view .... Lihat mutasi per akun

✅ CASH TRANSACTIONS
   - cash-transactions.create ... Input transaksi kas (IN/OUT)
   - cash-transactions.view ..... Lihat daftar transaksi
   - cash-transactions.update ... Edit transaksi (pending only)
   - cash-transactions.delete ... Hapus transaksi (with approval)
   - cash-transactions.print ... Print voucher

✅ EXPENSES
   - expense-categories.create .. Buat kategori pengeluaran
   - expense-categories.view ... Lihat kategori
   - expense-categories.update .. Edit kategori
   - expense-categories.delete .. Hapus kategori
   - expenses.create ........... Buat pengeluaran baru
   - expenses.view ............ Lihat daftar pengeluaran
   - expenses.update .......... Edit pengeluaran (pending only)
   - expenses.approve ......... Approve pengeluaran (approval role)
   - expenses.reject .......... Reject pengeluaran (approval role)
   - expenses.pay ............ Bayar pengeluaran
   - expenses.delete ......... Hapus pengeluaran (pending only)
   - expenses.report.view .... Lihat laporan expense

✅ COA (CHART OF ACCOUNTS)
   - coa-accounts.create ....... Buat COA
   - coa-accounts.view ....... Lihat COA
   - coa-accounts.update ...... Edit COA
   - coa-accounts.delete ...... Hapus COA
   - coa-accounts.balance-template.generate .... Generate balance template

✅ BANK TRANSFERS
   - bank-transfers.create .... Input transfer bank
   - bank-transfers.view ..... Lihat transfer bank

✅ PURCHASES (untuk pembayaran)
   - purchases.view ......... Lihat PO
   - purchases.payment ..... Input pembayaran PO

✅ REPORTS
   - reports.view .......... Lihat laporan
   - reports.financial.view . Lihat laporan keuangan

✅ DOCUMENT ARCHIVE
   - document-archive.view ........ Lihat daftar dokumen
   - document-archive.upload ...... Upload dokumen digital
   - document-archive.download ... Download dokumen
   - document-archive.search ..... Search dokumen
   - document-archive.delete ..... Hapus dokumen (dengan approval)
```
```

---

## 📱 TOOLS & SISTEM YANG DIGUNAKAN

### Hardware/Software:
- 🖥️ **Komputer/Laptop** (akses admin portal)
- 💾 **External HDD** (backup data keuangan)
- � **Scanner** (untuk digitalisasi dokumen)
- 📁 **Filing Cabinet** (penyimpanan fisik dokumen)
- �📱 **Calculator** (untuk double-check)
- 🖨️ **Printer** (untuk print laporan, voucher)

### Software/Aplikasi:
- **Morest App Admin Panel** (Browser: Chrome, Firefox, Edge)
- **Microsoft Excel** (untuk analisis, export data)
- **Microsoft Word/PDF** (untuk report)
- **Adobe Acrobat** (untuk PDF processing dan OCR)
- **Google Drive/OneDrive** (cloud backup arsip)
- **Bank Online** (untuk verify transfer, lihat statement)
- **Email** (komunikasi dengan supplier, internal)

---

## 📅 WORKFLOW HARIAN / MINGGUAN / BULANAN

### HARIAN:
- ⏰ **Pagi (08:30-09:30)**
  - Login ke admin panel
  - Review cash position: total kas + bank
  - Ada pending expense yang perlu diapprove?
  - Ada PO yang siap dibayar?

- ⏰ **Siang (10:00-12:00)**
  - Input transaksi kas yang masuk (dari penjualan, transfer)
  - Input expense dari manager (kalau ada reimbursement, office supplies, dll)
  - Verifikasi bukti/receipt dari expense
  - Update kategori & COA jika ada tambahan

- ⏰ **Siang-Sore (13:00-16:00)**
  - Review expense approval (jika ada HR role atau manager approval)
  - Bayar expense yang sudah approved
  - Bayar PO supplier yang due
  - Input bank transfer jika ada
  - Export laporan harian untuk manager

- ⏰ **Akhir Hari (16:00-17:00)**
  - Reconcile: cash saldo sistem vs ekspektasi
  - Update dashboard finance
  - Backup data transaksi harian
  - Filing dokumen harian: voucher, receipt, invoice
  - Komunikasi dengan kasir/POS: ada issue?

### MINGGUAN (Setiap Jumat):
- **Senin-Kamis**: Aktivitas harian sebagai di atas
- **Jumat Pagi (09:00)**:
  - Export laporan cash flow mingguan
  - Review pengeluaran minggu ini
  - Bandingkan dengan budget mingguan (jika ada)
  - Identifikasi pengeluaran unusual/abnormal
  
- **Jumat Sore (15:00)**:
  - Siapkan laporan P&L mingguan
  - Lihat piutang/hutang outstanding
  - Briefing dengan Finance Manager hasil mingguan
  - Plan untuk minggu depan

### BULANAN (Akhir Bulan):
- **Tanggal 25-27**:
  - **Bank Reconciliation** lengkap
  - Terima laporan bank statement
  - Matching transaksi bank vs sistem
  - Record adjustment jika ada perbedaan
  - Clear outstanding items
  
- **Tanggal 28-30**:
  - **Final Cash Count & Opname**
  - Hitung kas fisik all outlets
  - Input adjustment transaksi kas
  - Confirm saldo cash akurat
  
  - **Close Month**:
  - Generate laporan P&L bulanan
  - Generate laporan Payables (hutang supplier)
  - Generate laporan Receivables (piutang customer)
  - Generate trial balance
  
  - **Accrual & Adjustment**:
  - Catat akrual expense (listrik estimated, dll)
  - Adjustment deposit/prepaid jika ada
  - Depreciation (jika ada fixed asset)
  
  - **Month-end Close Report**:
  - Siapkan cash flow statement
  - Siapkan balance sheet
  - Siapkan income statement
  - Review vs budget / prior month
  
  - **Audit/Approval**:
  - Review dengan Chief Accountant / Finance Manager
  - Approval untuk finalisasi
  - Submit ke management/tax authority jika diperlukan

- **Tanggal 30/31**:
  - Finalkan laporan keuangan
  - Backup lengkap data bulanan
  - Arsip hardcopy bukti transaksi: organize by kategori, label folders
  - Digitalisasi dokumen bulanan: scan voucher, receipt, laporan
  - Inventory arsip: cross-check fisik vs digital
  - Siap untuk audit & reporting

---

## 🎓 KEAHLIAN & KOMPETENSI YANG DIBUTUHKAN

### Technical Skills:
- ✅ Penguasaan akuntansi dasar (debit-kredit, double-entry, COA)
- ✅ Profisiensi Microsoft Excel (formula, pivot table, Vlookup)
- ✅ Penggunaan sistem aplikasi web-based (browser)
- ✅ Understanding of cash flow & liquidity management
- ✅ Bank reconciliation & cash count

### Soft Skills:
- ✅ Akurasi tinggi (angka harus presisi)
- ✅ Attention to detail (tidak boleh ada typo/error)
- ✅ Organized & systematic (dokumen terarsip baik)
- ✅ Integritas tinggi (tidak ada fraud/kecurangan)
- ✅ Time management (deadline-oriented, especially month-end)
- ✅ Communication: koordinasi dengan supplier, kasir, manager

### Domain Knowledge:
- ✅ Pemahaman struktur bisnis F&B/restaurant multi-outlet
- ✅ Payment method types (cash, transfer, kartu debit, etc)
- ✅ Supplier payment terms & negotiation dasar
- ✅ Pajak & compliance dasar (PPN, PPh)

---

## ⚠️ TANGGUNGJAWAB KHUSUS

### JANGAN DILAKUKAN:
- ❌ **Jangan edit transaksi yang sudah approved/paid** (audit trail)
- ❌ **Jangan hapus cash transaction sembarangan** (akan corrupt saldo)
- ❌ **Jangan input transaksi dengan tanggal yang tidak sesuai** (reporting period error)
- ❌ **Jangan bayar expense/PO tanpa approval** (control)
- ❌ **Jangan share username/password** dengan orang lain
- ❌ **Jangan simpan file keuangan di tempat sembarangan** (security)
- ❌ **Jangan rusak atau hilangkan dokumen arsip** (audit compliance)
- ❌ **Jangan berikan dokumen ke unauthorized personnel** (confidentiality)

### WAJIB DILAKUKAN:
- ✅ **Catat SETIAP transaksi keuangan** (completeness)
- ✅ **Verifikasi bukti/receipt untuk setiap transaksi** (authenticity)
- ✅ **Reconcile kas harian dengan POS shift** (accuracy)
- ✅ **Backup data harian** (disaster recovery)
- ✅ **Monthly bank reconciliation** (completeness & accuracy)
- ✅ **Month-end closing tepat waktu** (reporting deadline)
- ✅ **Maintain audit trail** (jangan hapus/edit transaksi lama)
- ✅ **Segregation of duties**: approval ≠ payment execution
- ✅ **Print & arsip voucher untuk setiap transaksi kas keluar** (documentation)
- ✅ **Organize dokumen fisik dengan sistem filing yang konsisten** (accessibility)
- ✅ **Digitalisasi dokumen untuk backup** (disaster recovery)
- ✅ **Follow retention policy untuk pemusnahan dokumen** (compliance)

---

## 📞 ESCALATION & REPORTING

### Laporan Langsung Kepada:
- **Finance Manager** / **Chief Accountant**

### Koordinasi Dengan:
- 🤝 **POS Kasir**: Daily cash reconciliation
- 🤝 **Warehouse/Gudang**: Expense verification (barang masuk vs invoice)
- 🤝 **Purchasing**: PO payment status, supplier communication
- 🤝 **HR/Payroll**: Salary expense, reimbursement
- 🤝 **Management**: Financial reporting, cash forecast
- 🤝 **Tax Consultant**: Tax calculation, compliance
- 🤝 **Bank**: Reconciliation, loan/credit facility

---

## 📋 PERFORMANCE METRICS & KPI

### Yang Diukur:
| KPI | Target | Tolak Ukur |
|-----|--------|-----------|
| **Akurasi Data** | 100% | Zero error rate in recording |
| **Timeliness** | 100% | Transaksi recorded same day (D+0) |
| **Bank Reconciliation** | 100% | Monthly reconciliation complete by 30th |
| **Month-end Close** | 2 hari | Close report ready by 2nd of next month |
| **Expense Approval Time** | < 2 hari | Approve/reject expense < 48 jam |
| **Cash Discrepancy** | < 1% | Daily cash count difference < 1% |
| **Compliance** | 100% | All transaksi per policy & audit |
| **Backup Frequency** | Daily | Data backup setiap hari end-of-business |
| **Document Filing** | 100% | All dokumen filed within 24 jam |
| **Archive Inventory** | Monthly | Inventory arsip complete by 5th each month |
| **Audit Readiness** | 100% | Documents ready for audit within 2 jam request |

---

## 🚀 PENGEMBANGAN KARIR

### Peluang Promosi:
- 📈 **Finance Manager**: Manage finance team, strategic financial planning
- 📈 **Controller**: Oversee accounting, internal control, audit readiness
- 📈 **CFO (Chief Financial Officer)**: Lead finance strategy & investor relations

### Development Needs:
- 📚 Training: Advanced Excel & Financial Analysis
- 📚 Training: Internal Control & Risk Management
- 📚 Sertifikasi: Brevet Pajak A/B (untuk tax compliance)
- 📚 Sertifikasi: ACCA / CA (optional, for accounting qualification)

---

## 📝 DOKUMENTASI & REFERENCE

### Dokumen Referensi Internal:
- ✅ [EXPENSE-MANAGEMENT-SUMMARY.txt](./EXPENSE-MANAGEMENT-SUMMARY.txt) - Detail modul pengeluaran
- ✅ [PRODUCTION_READINESS_REPORT.md](./PRODUCTION_READINESS_REPORT.md) - Status sistem & data integrity
- ✅ [CUSTOMER-MANAGEMENT-SUMMARY.txt](./CUSTOMER-MANAGEMENT-SUMMARY.txt) - Untuk receivables tracking

### Dokumen Eksternal (Setup Manual):
- ✅ Chart of Accounts Template (Standard Indonesia GAAP)
- ✅ Expense Policy & Approval Matrix
- ✅ Cash Handling Procedure
- ✅ Bank Reconciliation Template
- ✅ Month-end Close Checklist
- ✅ Filing System Manual (untuk pengarsipan dokumen)
- ✅ Document Retention Policy (peraturan perpajakan)
- ✅ Archive Inventory Template

---

## 🎯 KESIMPULAN

Finance staff adalah posisi kunci dalam operasional finansial perusahaan. Tanggung jawab utama adalah **mencatat akurat, melaporkan tepat waktu, dan menjaga integritas data keuangan** untuk mendukung keputusan manajemen yang lebih baik dan audit yang smooth.

Dengan sistem terintegrasi di Morest App, semua transaksi tercatat secara real-time, approval workflow jelas, dan reporting menjadi lebih cepat dan akurat.

---

**Status**: ✅ Siap Operasional  
**Last Updated**: 2026-04-18  
**Version**: 1.1  
**Prepared by**: Admin / Finance Manager
