# Dashboard Admin Guide

## Tujuan
Dokumen ini jadi acuan cepat untuk agent/dev berikutnya saat mengubah dashboard admin tanpa merusak performa, tema, dan perilaku auto-refresh.

## Scope Halaman
1. Halaman utama: `resources/views/admin/dashboard.blade.php`
2. API ringkasan: `app/Http/Controllers/Admin/DashboardController.php`
3. Style global tombol/tema: `resources/css/app.css`

## Perubahan yang Sudah Ada
1. Card Top Produk sudah punya indikator pergerakan rank:
- `NEW`
- `+N` (naik)
- `-N` (turun)
- `0` (tetap)
2. Ada animasi ringan saat rank berubah (`top-rank-up`, `top-rank-down`, `top-rank-new`).
3. Tema dashboard sudah diselaraskan ke palet oranye.
4. Tombol `Refresh` sudah pakai style global: `btn btn-primary`.

## Mekanisme Auto Refresh
1. Interval refresh saat ini: `15000 ms` (15 detik).
2. Fetch menggunakan endpoint: `route('admin.dashboard.summary')`.
3. Ada guard `isLoading` supaya request tidak menumpuk saat koneksi lambat.
4. Status loading dan timestamp update ditampilkan di header panel.

## Logika Rank Top Produk
Implementasi di script `dashboard.blade.php`:
1. `prevTopRankByKey` menyimpan rank sebelumnya per produk.
2. `lastTopProductsSignature` untuk reset state saat filter berubah (`date`/`outlet`).
3. Key produk:
- prioritas `product_id`
- fallback nama produk lower-case trimmed
4. Render badge rank movement:
- naik: badge hijau + animasi up
- turun: badge merah + animasi down
- new: badge oranye + animasi new
- tetap: badge netral

## Aturan Tema UI (Wajib Jaga Konsistensi)
1. Gunakan class tombol global:
- primary: `btn btn-primary`
- secondary: `btn btn-secondary`
2. Hindari hardcode style tombol per-halaman kalau tidak diperlukan.
3. Untuk panel/card dashboard, gunakan variabel accent:
- `--dash-accent`
- `--dash-accent-2`
- `--dash-accent-soft`
4. Gunakan palet warm/orange agar satu arah dengan branding.

## Batasan Performa
1. Jangan tambah query baru di frontend saat auto-refresh.
2. Minimalkan DOM berat per refresh (hindari nested render yang tidak perlu).
3. Untuk perubahan visual, utamakan CSS statis/transisi ringan.
4. Jika user dashboard aktif banyak dan endpoint melambat, opsi pertama:
- naikkan interval ke `20000-30000 ms`.

## Checklist Sebelum Commit
1. Uji filter `Outlet` dan `Tanggal`:
- data berubah benar
- rank state reset benar
2. Uji manual refresh (`Refresh`) dan auto-refresh.
3. Pastikan tidak ada request dobel saat loading.
4. Jalankan build lokal:
```bash
cmd /c npm run build
```
5. Commit termasuk update `public/build/*` jika server produksi tidak bisa build.

## Catatan Deploy
Jika produksi tidak menjalankan build npm:
1. Build di lokal.
2. Pastikan file ini ikut ter-commit:
- `public/build/manifest.json`
- `public/build/assets/app-*.css`
- `public/build/assets/app-*.js`

## Saran Lanjutan (Opsional)
1. Tambah toggle interval refresh di UI (mis. 15s / 30s / manual).
2. Tambah mode `compact` untuk visual minimal.
3. Pertimbangkan SSE/WebSocket jika trafik dashboard tinggi.
