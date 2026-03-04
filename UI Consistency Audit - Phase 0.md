# UI Consistency Audit - Phase 0 (Admin)

Tanggal audit: 2026-03-03  
Area: `resources/views/admin/**` + `resources/views/layouts/admin.blade.php` + `resources/css/app.css`

## 1. Ringkasan Temuan

UI Admin saat ini memakai beberapa sistem style sekaligus:

1. Komponen global lama: `.btn`, `.card`, `.form-input`, `.table-modern`, `.badge`.
1. Komponen legacy lain: `.imperial-table`, `.imperial-btn`, `.imperial-badge`, `.page-card-fill`.
1. Utility Tailwind langsung di Blade (hardcoded class panjang).
1. Inline style per halaman (`<style>` di view).

Akibatnya konsistensi visual tidak stabil antar halaman (font feel, button shape, table density, warna border/background).

## 2. Baseline Metrics (hasil scan repo)

1. File admin yang memakai pola tombol campuran (`btn`, `action-dropdown-btn`, `report-tab-btn`, `bundle-tab-btn`): **21 file**
1. File admin yang memakai pola tabel campuran (`table-modern`, `imperial-table`, `min-w-full divide-y ...`, `border-separate ...`): **42 file**
1. File admin yang memakai `form-input`: **8 file**
1. File admin yang punya `<style>` inline: **16 file**
1. File admin yang memakai `page-card-fill`: **12 file**

## 3. Titik Inkonsistensi Utama

### A. Layout-level hardcoded light + kelas warna langsung

File:
1. `resources/views/layouts/admin.blade.php`

Masalah:
1. Banyak `bg-slate-*`, `text-slate-*`, `border-slate-*` hardcoded.
1. Belum ada tokenized theme di wrapper utama (body/header/content).

### B. Pola card/container tidak tunggal

Pattern yang coexist:
1. `card bg-white p-6`
1. `bg-white rounded-xl shadow-sm border border-gray-100 page-card-fill`
1. `t6-card shadow overflow-hidden`
1. Container ad-hoc per halaman.

### C. Pola tombol beragam dan tidak sinkron

Pattern yang coexist:
1. `btn btn-primary|secondary|danger|warning|info`
1. Tombol utility class penuh (tanpa komponen global)
1. `action-dropdown-btn` (duplikat style custom di beberapa halaman)
1. `report-tab-btn` dan `bundle-tab-btn` masing-masing punya visual sendiri

### D. Pola tabel beragam

Pattern yang coexist:
1. `table-modern`
1. `imperial-table`
1. `min-w-full divide-y divide-slate-200`
1. `min-w-full divide-y divide-gray-200`
1. `min-w-full border-separate border-spacing-0`

### E. Pola form field tidak tunggal

Pattern yang coexist:
1. `form-input`
1. `w-full px-4 py-2.5 ... bg-white border border-slate-200 ...`
1. `w-full px-3 py-2 border border-gray-300 ...`
1. `filter-input ...` varian khusus laporan/transaksi

### F. Inline style tersebar

Contoh area:
1. Dashboard admin
1. Reports (sales/products/shifts/debts)
1. Cash accounts (mutation-report, transactions)
1. Products (index/create_bundle)

Dampak:
1. Sulit menjaga konsistensi global.
1. Sulit dark mode karena style terpecah di banyak file.

## 4. Before -> After Mapping (Target Standar)

Catatan: mapping ini dipakai saat refactor bertahap.

### A. Page Container

1. `bg-white rounded-xl shadow-sm border border-gray-100 page-card-fill` -> `ui-surface ui-page-shell`
1. `card bg-white p-*` -> `ui-card`
1. `t6-card ...` -> `ui-card ui-card-compact` (atau merge ke `ui-card` bila sama)

### B. Button

1. `btn btn-primary` -> `ui-btn ui-btn-primary`
1. `btn btn-secondary` -> `ui-btn ui-btn-secondary`
1. `btn btn-danger` -> `ui-btn ui-btn-danger`
1. `btn btn-warning` -> `ui-btn ui-btn-warning`
1. `btn btn-info` -> `ui-btn ui-btn-info`
1. `action-dropdown-btn ...` -> `ui-btn ui-btn-ghost ui-btn-sm`
1. `report-tab-btn ...` -> `ui-tab-btn` + `is-active`
1. `bundle-tab-btn ...` -> `ui-tab-btn` + `is-active`

### C. Form Controls

1. `form-input` -> `ui-input` (alias sementara: `.form-input { @apply ui-input }` atau style equivalent)
1. `w-full px-4 py-2.5 ... border-slate-200 ...` -> `ui-input`
1. `w-full px-3 py-2 ... border-gray-300 ...` -> `ui-input`
1. `filter-input ...` -> `ui-input ui-input-sm` (plus helper class per kolom bila perlu)
1. Label custom tersebar -> `ui-label`

### D. Table

1. `table-modern` -> `ui-table`
1. `imperial-table` -> `ui-table`
1. `min-w-full divide-y divide-slate-200` -> `ui-table`
1. `min-w-full divide-y divide-gray-200` -> `ui-table`
1. `border-separate border-spacing-0` -> `ui-table ui-table-compact` (jika memang butuh density berbeda)

### E. Badge

1. `badge badge-success|warning|danger|gray` -> `ui-badge ui-badge-*`
1. `imperial-badge` -> `ui-badge` (alias sementara)

## 5. Prioritas Refactor Halaman (A/B/C)

## Priority A (mulai dulu)

1. `resources/views/layouts/admin.blade.php`
1. `resources/views/admin/dashboard.blade.php`
1. `resources/views/admin/products/index.blade.php`
1. `resources/views/admin/products/create.blade.php`
1. `resources/views/admin/products/edit.blade.php`
1. `resources/views/admin/purchases/index.blade.php`
1. `resources/views/admin/purchases/create.blade.php`
1. `resources/views/admin/purchases/edit.blade.php`
1. `resources/views/admin/users/index.blade.php`
1. `resources/views/admin/reports/index.blade.php`

Alasan:
1. Trafik tinggi.
1. Mewakili hampir semua komponen inti.
1. Menjadi reference style untuk modul lain.

## Priority B

1. Cash accounts (kecuali print pages)
1. Stock transfers (kecuali print page)
1. Suppliers, customers, payment methods, sales types
1. COA + expense categories
1. BOM regular (kecuali style eksperimental `create_clean` bila ingin dipertahankan terpisah)

## Priority C

1. Semua halaman laporan detail dengan filter/table kompleks.
1. Halaman dengan inline style padat.
1. Halaman edge-case yang punya layout khusus.

## 6. Daftar File “Risk Tinggi” untuk Inkonsistensi

File-file berikut paling berpotensi memunculkan perbedaan visual:

1. `resources/views/layouts/admin.blade.php`
1. `resources/views/admin/dashboard.blade.php`
1. `resources/views/admin/reports/sales/index.blade.php`
1. `resources/views/admin/reports/sales/daily.blade.php`
1. `resources/views/admin/reports/sales/products.blade.php`
1. `resources/views/admin/products/create_bundle.blade.php`
1. `resources/views/admin/products/edit_bundle.blade.php`
1. `resources/views/admin/cash-accounts/transactions.blade.php`
1. `resources/views/admin/cash-accounts/mutation-report.blade.php`

## 7. Keputusan Teknis Phase Berikutnya

Pada Phase 1 (Foundation), lakukan:

1. Tambah komponen standar `.ui-*` di `resources/css/app.css`.
1. Pertahankan class legacy sebagai alias sementara (biar tidak langsung break).
1. Satukan visual shell layout admin (`body`, `header`, `main`, container) agar semua halaman langsung “nada” yang sama.
1. Kurangi inline style yang seharusnya bisa pindah ke CSS global.

## 8. Exit Criteria Phase 0 (terpenuhi)

1. Pola style campuran sudah teridentifikasi.
1. Mapping `before -> after` sudah ditetapkan.
1. Prioritas halaman rollout sudah ditetapkan.
1. Daftar area berisiko tinggi sudah tersedia untuk fokus refactor.

