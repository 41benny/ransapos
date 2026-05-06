# Dokumentasi Proses Bisnis - Morest App

Dokumen ini menyajikan gambaran detil mengenai ekosistem dan alur kerja aplikasi Morest. Dokumentasi ini disusun untuk kebutuhan presentasi klien, menonjolkan integrasi operasional, finansial, dan administratif yang solid.

---

## 1. Arsitektur Modul Utama

### 1.1 Penjualan & POS (Point of Sale)
Modul garda depan yang menangani interaksi langsung dengan pelanggan.
*   **Manajemen Produk**: Basis data terpusat untuk produk dengan kategori, varian, dan skema harga. Mendukung manajemen bahan baku dan produk jadi.
*   **Transaksi Real-time**: Mendukung berbagai jenis penjualan (Dine-in, Takeaway, Delivery) dengan sinkronisasi stok instan.
*   **Sistem Voucher & Promo**: Pengaturan promo dinamis dan manajemen voucher untuk meningkatkan loyalitas pelanggan.
*   **Manajemen Customer**: Pencatatan riwayat transaksi untuk analisis perilaku belanja dan personalisasi layanan.

### 1.2 Inventori & Produksi (Gudang)
Modul yang memastikan ketersediaan stok dan akurasi biaya produksi.
*   **Stok Kontrol & Opname**: Pemantauan stok real-time dengan fitur penyesuaian (Stock Adjustment) untuk sinkronisasi fisik vs sistem secara akurat.
*   **HPP Moving Average**: Penghitungan Harga Pokok Penjualan (HPP) menggunakan metode rata-rata bergerak (Moving Average), memastikan akurasi margin keuntungan meskipun harga beli dari supplier berfluktuasi.
*   **Bill of Materials (BOM)**: Manajemen resep dan produksi. Sistem secara otomatis melakukan *backflushing* (pemotongan stok bahan baku) saat produk jadi terjual.
*   **Mutasi Stok & Transfer**: Alur perpindahan barang antar outlet atau gudang yang terdokumentasi lengkap dengan status pengiriman dan penerimaan.

### 1.3 Keuangan & Akuntansi (Finance)
Jantung pelaporan bisnis yang mengacu pada standar akuntansi keuangan.
*   **Chart of Accounts (COA)**: Struktur akun (Neraca & Laba Rugi) yang fleksibel untuk memetakan seluruh transaksi ke dalam buku besar.
*   **Laporan Keuangan Terpadu**:
    *   **Neraca (Balance Sheet)**: Gambaran posisi aset, kewajiban, dan ekuitas perusahaan.
    *   **Laba Rugi (Profit & Loss)**: Analisis pendapatan vs biaya operasional & HPP secara real-time.
    *   **Trial Balance**: Validasi keseimbangan saldo debit dan kredit di seluruh akun untuk memastikan integritas data.
*   **Manajemen Kas & Bank**: Pelacakan mutasi uang, transfer antar rekening, dan rekonsiliasi kas harian per shift (Cash Session).
*   **Hutang & Piutang (AP/AR)**: Monitoring tagihan supplier dan piutang pelanggan yang terintegrasi langsung dengan arus kas.

### 1.4 Sumber Daya Manusia (HR) & Presensi
Modul untuk meningkatkan produktivitas dan kedisiplinan staf.
*   **Dashboard Presensi**: Visualisasi kehadiran staf secara harian dengan data masuk/pulang yang akurat.
*   **Leaderboard Performa**: Analisis otomatis untuk mendeteksi staf dengan performa terbaik serta pemantauan frekuensi keterlambatan dan total jam kerja.
*   **Manajemen Lembur & Libur**: Perhitungan otomatis jam kerja pada hari libur nasional atau akhir pekan dengan sistem pelabelan "Overtime" yang jelas.
*   **Filter Status Karyawan**: Kemampuan memisahkan data karyawan aktif dan non-aktif untuk menjaga kebersihan data laporan penggajian dan performa.

---

## 2. Alur Proses Bisnis Terintegrasi

### 2.1 Alur "Order-to-Cash" (Penjualan ke Kas)
1.  **Input Penjualan**: Staf POS memasukkan pesanan pelanggan.
2.  **Validasi Stok**: Sistem memeriksa ketersediaan stok secara real-time.
3.  **Pembayaran**: Transaksi diselesaikan via Kas, Bank, atau Digital Payment.
4.  **Otomasi Akuntansi**: 
    *   **Inventori**: Stok berkurang otomatis berdasarkan komposisi produk.
    *   **Pendapatan**: Penjualan tercatat di Laporan Laba Rugi.
    *   **Kas/Bank**: Saldo akun keuangan bertambah secara instan di buku besar.

### 2.2 Alur "Procure-to-Pay" (Pembelian ke Hutang)
1.  **Pengadaan (Purchase)**: Barang dipesan dan diterima dari supplier.
2.  **Inventory In**: Stok gudang bertambah, dan nilai HPP Moving Average diperbarui otomatis berdasarkan harga beli terbaru.
3.  **Pencatatan Hutang**: Tagihan supplier (Vendor Bill) otomatis masuk ke daftar hutang usaha.
4.  **Pelunasan**: Pembayaran tagihan akan memotong saldo Kas/Bank dan memperbarui status hutang di laporan keuangan.

### 2.3 Alur "Production" (Produksi & BOM)
1.  **Definisi Resep**: Admin mengatur komposisi bahan baku untuk setiap produk jadi di modul BOM.
2.  **Produksi & Pengurangan Stok**: Saat produk terjual, sistem memotong stok bahan baku secara otomatis (Auto-deduct).
3.  **Analisis Margin**: Sistem membandingkan biaya bahan baku terbaru dengan harga jual untuk memberikan laporan profitabilitas per produk yang akurat.

---

## 3. Keamanan & Tata Kelola (Governance)

### 3.1 Granular RBAC (Role-Based Access Control)
Sistem keamanan tingkat tinggi dengan kontrol akses mendetail:
*   **Metode Checklist**: Admin dapat menentukan hak akses (View, Create, Edit, Delete) secara spesifik untuk setiap modul.
*   **Akses Per-User**: Selain berdasarkan jabatan (Role), izin akses juga dapat disesuaikan untuk individu tertentu.
*   **Privasi Data**: Memastikan data sensitif seperti margin keuntungan dan laporan keuangan hanya dapat diakses oleh pihak yang berwenang.

### 3.2 Multi-Entity & Branding
*   **Multi-Company**: Kelola banyak entitas bisnis atau cabang dalam satu sistem terpusat.
*   **Custom Branding**: Logo dan profil perusahaan pada invoice dan laporan menyesuaikan dengan entitas bisnis yang melakukan transaksi.

### 3.3 Audit Trail & Changelog
*   **Transparansi Sistem**: Seluruh pembaruan fitur dan perbaikan terdokumentasi dalam **System Changelog**, memastikan klien selalu mendapatkan informasi terbaru mengenai perkembangan aplikasi.

---

## 4. Nilai Tambah Untuk Klien (Value Proposition)

1.  **Efisiensi Operasional**: Menghilangkan duplikasi kerja karena semua modul (Sales, Inventory, Finance, HR) sudah saling terhubung.
2.  **Akurasi Finansial**: Menggunakan prinsip akuntansi standar (Standard-compliant) untuk laporan keuangan yang dapat dipertanggungjawabkan.
3.  **Analitik Cerdas**: Dashboard yang informatif membantu pemilik bisnis mengambil keputusan berdasarkan data nyata (Data-driven).
4.  **Skalabilitas Bisnis**: Sistem siap mendukung pertumbuhan bisnis dari satu outlet hingga banyak cabang dengan manajemen yang tetap terkontrol.
5.  **Pengalaman Pengguna Premium**: Desain antarmuka modern yang cepat dan responsif, mengurangi waktu pelatihan staf dan meningkatkan profesionalisme di mata pelanggan.
