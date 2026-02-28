# Blueprint Implementasi: Perbaikan PIN Setelah Logout Perangkat

## Tujuan
- Menghindari kondisi layar PIN kosong setelah logout.
- Memastikan perangkat yang baru pairing langsung punya 1 user referensi untuk login PIN.
- Saat belum ada user PIN tersimpan, alur diarahkan ke login email (bukan keypad nonaktif).

## Masalah Saat Ini
1. Logout kasir/kitchen dengan device valid diarahkan ke halaman PIN.
2. Halaman PIN mengambil daftar user dari tabel `pos_device_user_logins`.
3. Jika tabel ini belum berisi data untuk device tersebut, layar PIN kosong dan tombol angka dinonaktifkan.

## Implementasi yang Disarankan

### 1) Simpan user ke riwayat device saat pairing sukses
File: `app/Http/Controllers/POS/DeviceController.php`

Tambahkan import:
```php
use Illuminate\Support\Facades\DB;
```

Setelah `$device->save();`, panggil:
```php
$this->rememberDeviceUserLogin($request, $device);
```

Tambahkan method baru di dalam class:
```php
private function rememberDeviceUserLogin(Request $request, PosDevice $device): void
{
    $user = $request->user();
    if (!$user || !$user->hasRole(['kasir', 'admin', 'kitchen'])) {
        return;
    }

    if (!$user->outlet_id || (int) $device->outlet_id !== (int) $user->outlet_id) {
        return;
    }

    if (!$user->is_active || empty($user->attendance_pin)) {
        return;
    }

    DB::table('pos_device_user_logins')->upsert([
        [
            'pos_device_id' => $device->id,
            'user_id' => $user->id,
            'last_login_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ], ['pos_device_id', 'user_id'], ['last_login_at', 'updated_at']);
}
```

### 2) Jika user PIN kosong, langsung fallback ke login email
File: `app/Http/Controllers/POS/PinLoginController.php`

Di method `show()`, setelah query `$users`, tambahkan:
```php
if ($users->isEmpty()) {
    return redirect()->route('login', ['email' => 1])->withErrors([
        'email' => 'Belum ada user PIN tersimpan di perangkat ini. Login dulu dengan email.',
    ]);
}
```

## Uji Manual Setelah Implementasi
1. Pairing device baru sebagai user kasir yang sudah punya PIN.
2. Logout dari POS pada device tersebut.
3. Pastikan halaman PIN menampilkan user (bukan kosong).
4. Uji device yang belum punya user PIN tersimpan.
5. Pastikan sistem redirect ke login email dengan pesan yang jelas.

## Catatan
- Dokumen ini sengaja tanpa file patch/auto-apply, supaya aman untuk alur tim yang sedang aktif push.
