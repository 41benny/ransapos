# Implementation Plan - UI Consistency Baseline (Admin Area)

Dokumen ini menjadi acuan standar untuk merapikan tampilan Admin agar konsisten, siap dark mode, dan mudah di-maintain.

## 1. Latar Belakang Masalah

Saat ini UI Admin belum konsisten karena:

1. Beberapa pola styling dipakai bersamaan:
1. Utility class Tailwind langsung di Blade.
1. Komponen custom di `resources/css/app.css` (`.btn`, `.card`, `.table-modern`, `.form-input`).
1. Inline style per halaman.
1. Layout berbeda dengan aturan berbeda.
1. Warna masih banyak hardcoded (terutama light-only), sehingga sulit dark mode.
1. Font, spacing, radius, shadow, dan state komponen belum punya aturan tunggal.

Efeknya:

1. Tombol antar halaman terlihat beda.
1. Tabel dan input tidak seragam.
1. Kualitas visual terasa “campur” dan kurang arah tema.
1. Biaya maintenance tinggi untuk perubahan UI berikutnya.

## 2. Tujuan

1. Menetapkan satu design baseline untuk Admin (typography, color, spacing, komponen).
1. Menyatukan tampilan komponen inti: Button, Card, Form, Table, Badge, Header section.
1. Mengurangi inline style dan hardcoded color.
1. Menyiapkan pondasi dark mode global tanpa patching per halaman berulang.

## 3. Scope

### In Scope

1. Semua halaman yang memakai layout `resources/views/layouts/admin.blade.php`.
1. Styling global di `resources/css/app.css`.
1. Komponen UI inti yang paling sering dipakai.
1. Refactor class di halaman prioritas tinggi (dashboard + CRUD utama).

### Out of Scope (Phase terpisah)

1. Layout POS (`pos`, `pos_theme`, `pos_v2`) kecuali disepakati ikut distandardisasi.
1. Halaman print (voucher/invoice/thermal) karena punya kebutuhan visual khusus.
1. Redesign UX flow (informasi & interaksi), fokus dokumen ini adalah konsistensi visual.

## 4. Prinsip Arsitektur UI

1. **Single source of truth**: token visual ada di satu tempat (`app.css`).
1. **Component-first**: halaman menggunakan class komponen standar, bukan styling ad-hoc.
1. **Theme-ready**: semua komponen menggunakan variable/token agar light/dark konsisten.
1. **Minimal inline style**: inline hanya untuk kebutuhan dinamis yang tidak bisa dihindari.
1. **Incremental rollout**: tidak perlu big-bang, migrasi bertahap per modul.

## 5. Standar Design Token

Definisikan token global di `:root` dan `.dark`:

1. `--ui-bg`, `--ui-surface`, `--ui-surface-muted`
1. `--ui-text`, `--ui-text-muted`
1. `--ui-border`, `--ui-ring`
1. `--ui-primary`, `--ui-primary-hover`
1. `--ui-success`, `--ui-warning`, `--ui-danger`
1. `--ui-radius-sm`, `--ui-radius-md`, `--ui-radius-lg`
1. `--ui-shadow-sm`, `--ui-shadow-md`

### Typography baseline

1. Font utama: Inter (sudah dipakai) dan dijadikan default tunggal untuk Admin.
1. Ukuran teks standar:
1. Heading page: `text-xl`/`font-black` (atau setara, disepakati tetap).
1. Heading section/card: `text-sm`/`font-semibold`.
1. Body: `text-sm`.
1. Caption/meta: `text-xs`.
1. Hindari campuran banyak gaya font-weight/letter-spacing tanpa aturan.

## 6. Standar Komponen (Target Kelas)

Semua komponen berikut distabilkan di `app.css`:

1. `.ui-card`
1. `.ui-btn`, `.ui-btn-primary`, `.ui-btn-secondary`, `.ui-btn-danger`, `.ui-btn-ghost`
1. `.ui-input`, `.ui-select`, `.ui-textarea`, `.ui-label`, `.ui-help`
1. `.ui-table`, `.ui-table-wrap`
1. `.ui-badge`, variant status
1. `.ui-page-header`, `.ui-section-title`

Catatan:

1. Class lama (`.btn`, `.card`, `.form-input`, `.table-modern`) bisa dipertahankan sementara sebagai alias/backward compatible.
1. Setelah migrasi selesai, class lama bisa dideprecate.

## 7. Strategi Migrasi

### Phase 0 - Audit (Wajib)

1. Inventaris semua pattern class untuk tombol, input, tabel, card.
1. Kelompokkan halaman:
1. Prioritas A: dashboard, users, products, purchases, reports index.
1. Prioritas B: module finansial, inventory, permissions.
1. Prioritas C: sisa halaman admin.
1. Identifikasi inline style yang perlu dihapus/diubah.

Output:

1. Daftar “before -> after class mapping”.
1. Daftar halaman prioritas rollout.

### Phase 1 - Foundation

1. Tambah/rapikan token light-dark di `app.css`.
1. Definisikan class komponen `.ui-*`.
1. Samakan base body/background/text di `admin.blade.php`.
1. Pastikan focus ring, border, hover, disabled state konsisten.

Output:

1. Design baseline reusable siap dipakai lintas halaman Admin.

### Phase 2 - Refactor Halaman Prioritas A

1. Ganti style ad-hoc dengan class `.ui-*`.
1. Hilangkan hardcoded warna yang tidak perlu.
1. Samakan struktur section header, filter bar, action button area.
1. Uji visual light + dark (jika dark mode aktif).

Output:

1. 20-40% area Admin sudah konsisten dan jadi contoh implementasi.

### Phase 3 - Refactor Prioritas B/C

1. Lanjut migrasi modul lain berdasarkan traffic/criticality.
1. Selesaikan kasus edge (table complex, form panjang, badge status khusus).
1. Bersihkan duplikasi class atau CSS dead rule.

Output:

1. Konsistensi mayoritas halaman Admin.

### Phase 4 - Stabilization & Cleanup

1. Freeze style rule untuk mencegah class liar baru.
1. Dokumentasi “do/don’t” styling.
1. Deprecation class lama jika sudah tidak dipakai.

## 8. Aturan Teknis Implementasi

1. Dilarang menambah warna hardcoded baru di Blade untuk UI umum (kecuali kebutuhan khusus/branding yang disetujui).
1. Komponen baru wajib memakai class `.ui-*`.
1. Jika butuh variant baru, tambahkan di `app.css` dulu, baru dipakai di view.
1. Inline style hanya untuk nilai dinamis runtime yang tidak bisa diwakili class.
1. Setiap perubahan UI wajib cek responsive minimal mobile + desktop.

## 9. Quality Gate (Acceptance Criteria)

Fitur dianggap selesai bila:

1. Halaman Admin prioritas memakai komponen standar dan visualnya seragam.
1. Font, radius, shadow, border, spacing komponen inti tidak “lompat-lompat”.
1. Tidak ada regression kontras utama (teks, tombol, table header, input focus).
1. Tidak ada broken layout di breakpoint umum.
1. Jika dark mode diaktifkan, komponen inti tetap terbaca dan usable.

## 10. QA Checklist Manual

Untuk setiap halaman yang dimigrasi:

1. Header halaman konsisten.
1. Action buttons konsisten ukuran + state hover/focus/disabled.
1. Form fields konsisten tinggi, border, label, helper/error text.
1. Tabel konsisten struktur, padding, zebra/hover, empty state.
1. Card konsisten background, border, shadow, radius.
1. Cek mobile width dan overflow horizontal.
1. Cek dark mode (jika sudah aktif): text contrast, panel contrast, ring visibility.

## 11. Risiko & Mitigasi

1. **Risiko**: Perubahan global memengaruhi halaman lama.
1. **Mitigasi**: rollout bertahap + verifikasi per modul.
1. **Risiko**: Bentrok class lama dan class baru.
1. **Mitigasi**: alias sementara + naming `.ui-*` yang eksplisit.
1. **Risiko**: Tim menambah styling ad-hoc lagi.
1. **Mitigasi**: guideline singkat + review checklist saat PR.

## 12. Rencana Eksekusi Praktis (Disarankan)

Week 1:

1. Audit class & halaman.
1. Finalisasi token + komponen dasar di `app.css`.
1. Update layout admin baseline.

Week 2:

1. Refactor prioritas A.
1. QA manual + perbaikan cepat.

Week 3:

1. Refactor prioritas B.
1. Cleanup CSS duplikat.

Week 4:

1. Refactor prioritas C.
1. Dokumentasi final + freeze baseline.

## 13. Deliverables

1. `resources/css/app.css` dengan token + komponen `.ui-*` konsisten.
1. `resources/views/layouts/admin.blade.php` baseline visual yang stabil.
1. Halaman prioritas yang sudah dimigrasi.
1. Dokumen guideline singkat penggunaan komponen.
1. Checklist QA yang dipakai tim.

## 14. Definition of Done

1. Admin area memiliki visual direction tunggal.
1. Komponen utama tidak lagi terlihat “berasal dari sistem berbeda”.
1. Penambahan halaman baru bisa mengikuti template komponen tanpa styling ulang.
1. Dark mode rollout menjadi jauh lebih mudah dan minim regression.

## 15. Progress Aktual (2026-03-03)

Status implementasi:

1. Phase 0 Audit: **Selesai** (lihat `UI Consistency Audit - Phase 0.md`).
1. Phase 1 Foundation: **Selesai baseline**.
1. Phase 2 Refactor halaman prioritas: **In Progress** (mayoritas modul utama sudah dimigrasi).

Perubahan yang sudah diimplementasikan:

1. Foundation CSS `.ui-*` + token light/dark + alias legacy class di `resources/css/app.css`.
1. Scope fallback untuk halaman admin lama melalui `body.ui-admin-body`.
1. Layout admin diperbarui agar theme-ready + toggle dark mode + anti-FOUC di `resources/views/layouts/admin.blade.php`.
1. Refactor awal halaman prioritas:
1. `resources/views/admin/reports/index.blade.php` (tab standard `is-active`).
1. `resources/views/admin/products/index.blade.php` (aksi utama ke `ui-btn`).
1. `resources/views/admin/users/partials/user-card-table.blade.php` (dropdown action button ke `ui-btn`).
1. Batch lanjutan prioritas A:
1. `resources/views/admin/users/index.blade.php` (header action, filter card, reset button).
1. `resources/views/admin/purchases/index.blade.php` (action buttons, filter card, table card, `ui-table`).
1. `resources/views/admin/purchases/create.blade.php` (info/item/summary card, tombol aksi, `ui-table`).
1. `resources/views/admin/purchases/edit.blade.php` (info/item/summary card, tombol aksi, `ui-table`).
1. `resources/views/admin/products/create.blade.php` (form card utama, tombol copy/simpan/batal).
1. `resources/views/admin/products/edit.blade.php` (form card utama, tombol copy/simpan/batal).
1. Batch Priority B (lanjutan):
1. `resources/views/admin/suppliers/index.blade.php` (`ui-card`, `ui-table`, tombol aksi/list).
1. `resources/views/admin/payment-methods/index.blade.php` (`ui-input`, `ui-btn`, `ui-table`).
1. `resources/views/admin/sales-types/index.blade.php` (`ui-input`, `ui-btn`, `ui-table`).
1. `resources/views/admin/outlets/index.blade.php` (`ui-card`, tombol list dan detail).
1. `resources/views/admin/pos-devices/index.blade.php` (form controls, pairing button, table + row actions).
1. `resources/views/admin/cash-accounts/index.blade.php` (action buttons, table/action list, card harmonization).
1. Batch Priority B (lanjutan 2):
1. `resources/views/admin/customers/index.blade.php` (`ui-card`, `ui-input`, `ui-btn`, `ui-table`).
1. `resources/views/admin/expenses/index.blade.php` (`ui-card`, filters/actions, `ui-table`).
1. `resources/views/admin/cash-sessions/index.blade.php` (`ui-card`, filter controls, `ui-table`).
1. Batch Reports (lanjutan):
1. `resources/views/admin/reports/sales/index.blade.php` (standarisasi card/filter/action + `ui-table`).
1. `resources/views/admin/reports/sales/daily.blade.php` (harmonisasi action/filter + table wrapper).
1. `resources/views/admin/reports/sales/products.blade.php` (header/filter/table action ke pola `ui-*`).
1. `resources/views/admin/reports/debts/index.blade.php` (filter/action/table ke baseline komponen).
1. `resources/views/admin/reports/debts/show.blade.php` (detail card/action/table konsisten).
1. `resources/views/admin/reports/shifts/index.blade.php` (filter/action/table `is-active` + `ui-*`).
1. `resources/views/admin/reports/shifts/show.blade.php` (detail/action/export/table ke baseline `ui-card/ui-btn/ui-table`).
1. Batch Inventory & Transfer (lanjutan):
1. `resources/views/admin/stocks/index.blade.php` (`ui-card`, `ui-input`, `ui-btn`, `ui-table`).
1. `resources/views/admin/stocks/mutations.blade.php` (`ui-card`, controls filter, action buttons, `ui-table`).
1. `resources/views/admin/stocks/adjustment.blade.php` (form controls, table, action ke `ui-*`).
1. `resources/views/admin/stocks/stock-card.blade.php` (header/filter/log table + summary cards harmonized).
1. `resources/views/admin/stock-transfers/index.blade.php` (filter/action/table migrated ke `ui-*`).
1. `resources/views/admin/stock-transfers/create.blade.php` (form route/item/action migrated ke `ui-*`).
1. `resources/views/admin/stock-transfers/show.blade.php` (action/status/info/table/modal migrated ke `ui-*`).
1. `resources/views/admin/stock-transfers/receive.blade.php` (verifikasi form/table/action migrated ke `ui-*`).
1. Batch Access & User Management (lanjutan):
1. `resources/views/admin/permissions/index.blade.php` (stats card, matrix table, action button ke baseline `ui-*`).
1. `resources/views/admin/permissions/edit.blade.php` (toolbar sticky, duplikasi role, module cards, CTA ke `ui-*`).
1. `resources/views/admin/users/create.blade.php` (form controls + footer actions ke `ui-input/ui-btn`).
1. `resources/views/admin/users/edit.blade.php` (form utama, permission checklist, PIN section ke `ui-*`).
1. Batch Purchasing Detail (lanjutan):
1. `resources/views/admin/purchases/show.blade.php` (action bar, detail cards, items table, modal action ke `ui-*`).
1. `resources/views/admin/purchases/payment.blade.php` (summary/history/form cards, controls, CTA ke `ui-*`).
1. `resources/views/admin/products/index.blade.php` (main table wrapper + reset control ke baseline `ui-card/ui-table/ui-btn`).
1. Batch Master Form (lanjutan):
1. `resources/views/admin/payment-methods/create.blade.php` (form create ke `ui-card/ui-input/ui-btn`).
1. `resources/views/admin/payment-methods/edit.blade.php` (form edit ke `ui-card/ui-input/ui-btn`).
1. `resources/views/admin/sales-types/create.blade.php` (form create ke `ui-card/ui-input/ui-btn`).
1. `resources/views/admin/sales-types/edit.blade.php` (form edit ke `ui-card/ui-input/ui-btn`).
1. `resources/views/admin/outlets/create.blade.php` (form create ke `ui-card/ui-input/ui-btn`).
1. `resources/views/admin/outlets/edit.blade.php` (form edit ke `ui-card/ui-input/ui-btn`).

## 16. Finalisasi Scope "Semua File" (2026-03-03)

Status final scope:

1. Seluruh file `resources/views/admin/**/*.blade.php` non-print sudah tersentuh baseline komponen (`ui-card`, `ui-btn`, `ui-input`, `ui-table`).
1. Migrasi massal dilanjutkan dengan patch manual pada edge-case agar class `ui-*` dipakai di elemen yang tepat.
1. Dark mode readiness meningkat karena halaman admin kini mengikuti token/style foundation yang sama.

Hasil verifikasi otomatis pasca-migrasi:

1. `MISSING_CORE_UI=0` untuk seluruh file admin non-print.
1. `BAD_INPUT_TAG=0`, `BAD_TABLE_TAG=0`, `BAD_BTN_TAG=0` (tidak ada class `ui-*` menempel di elemen yang salah).
1. Tidak ditemukan duplikasi token core (`ui-input/ui-btn/ui-card/ui-table`) dalam satu atribut class.

Catatan penting sebelum merge:

1. Masih ada class legacy (`btn`, `card`, `form-input`, `table-modern`) di sebagian halaman sebagai backward compatibility.
1. Tahap berikutnya adalah deprecation bertahap class legacy setelah QA visual light/dark per modul.
1. Validasi berbasis `php artisan` belum dijalankan di sesi ini karena binary `php` tidak tersedia di environment terminal saat ini.
