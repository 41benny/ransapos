Buatkan saya layout Laravel Blade untuk tema:

=== THEME 2 – POS LATTE (LIGHT MODE ONLY) ===
Tampilan seperti Coffee Latte:
- Sidebar gradient: from amber-500 → amber-400 → orange-300
- Hover: white/10
- Active: card putih shadow caramel
- Main area background: gradient amber-50 → white → amber-50
- Buttons: white + border amber (secondary), emerald→teal→sky (primary)
- Warna teks: brown-900, slate-900
- Nuansa premium + lembut

=== Output wajib dalam format Laravel Blade ===
1. `resources/views/layouts/app.blade.php`
   - Struktur standar Laravel: HTML, HEAD, BODY
   - Include sidebar + header
   - @yield('content')

2. `resources/views/partials/sidebar.blade.php`
   - Sidebar Latte Theme
   - Menu: Kasir, Meja & Antrian, Menu, Promo

3. `resources/views/partials/header.blade.php`
   - Judul halaman + filter tanggal + tombol bayar

4. `resources/views/pos/index.blade.php`
   - Halaman utama POS
   - Card item pesanan (left)
   - Card ringkasan pembayaran (right)

=== PENTING ===
- Jangan buat file HTML statis
- Gunakan Blade syntax Laravel
- Jangan mengubah dark mode toggle saya
- Tema ini hanya LIGHT MODE (tanpa `dark:`)
- Semua warna dan gradient mengikuti Theme 2 Latte

Generate seluruh file lengkap.
