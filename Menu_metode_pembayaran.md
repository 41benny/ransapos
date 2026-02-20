# Update Pekerjaan: Menu Metode Pembayaran

Tanggal: 20 Februari 2026

## Ringkasan
Fitur menu manajemen metode pembayaran di Back Office sudah ditambahkan, mencakup:
- Menu sidebar `Master Data > Metode Bayar`
- CRUD metode pembayaran (index, create, edit, delete)
- Integrasi permission per aksi (view/create/update/delete)
- Integrasi landing route admin berbasis permission

## Fitur yang Ditambahkan
1. Halaman daftar metode pembayaran:
- Tabel metode pembayaran
- Informasi jumlah transaksi pemakaian per metode
- Filter pencarian kode/nama

2. Form tambah metode pembayaran:
- Input `code`
- Input `name`
- Opsi aktif/nonaktif

3. Form edit metode pembayaran:
- Update `code`, `name`, status aktif
- Proteksi khusus metode `CASH` agar tidak bisa dinonaktifkan/diubah kodenya

4. Proteksi hapus:
- Tidak bisa hapus metode `CASH`
- Tidak bisa hapus metode yang sudah dipakai transaksi
- Menjaga minimal ada satu metode aktif

## Routing Baru
Prefix: `admin/payment-methods`

Route names:
- `admin.payment-methods.index`
- `admin.payment-methods.create`
- `admin.payment-methods.store`
- `admin.payment-methods.edit`
- `admin.payment-methods.update`
- `admin.payment-methods.destroy`

## Permission Baru
Ditambahkan permission berikut:
- `payment-methods.view`
- `payment-methods.create`
- `payment-methods.update`
- `payment-methods.delete`

Migration juga meng-assign default permission ini ke role:
- `admin`
- `manager`

## File yang Ditambah
- `app/Http/Controllers/Admin/PaymentMethodController.php`
- `resources/views/admin/payment-methods/index.blade.php`
- `resources/views/admin/payment-methods/create.blade.php`
- `resources/views/admin/payment-methods/edit.blade.php`
- `database/migrations/2026_02_20_100000_add_payment_method_permissions.php`
- `Menu_metode_pembayaran.md`

## File yang Diubah
- `routes/web.php`
- `resources/views/layouts/admin.blade.php`
- `app/Http/Controllers/Auth/AuthController.php`
- `app/Http/Controllers/Admin/PermissionController.php`

## Verifikasi
Sudah dicek:
- Syntax check controller dan migration (`php -l`) valid
- Route `admin/payment-methods*` muncul di `php artisan route:list`

## Catatan Deploy
Jalankan migration setelah pull:

```bash
php artisan migrate
```
