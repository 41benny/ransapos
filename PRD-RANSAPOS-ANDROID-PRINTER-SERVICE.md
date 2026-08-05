# PRD: Ransapos Android Printer Service

## 1. Ringkasan

Ransapos Android Printer Service adalah aplikasi Android kecil yang bertugas mencetak struk thermal dari Ransapos ke printer Bluetooth secara native. Aplikasi ini dibuat untuk menghindari batasan Web Bluetooth di Chrome Android, terutama popup pemilihan perangkat yang sering muncul ulang.

Target utama MVP adalah:
- Kasir memilih printer Bluetooth satu kali di aplikasi Android.
- Ransapos web mengirim data struk ESC/POS base64 ke Android app melalui intent.
- Android app mencetak ke printer default yang sudah disimpan.
- Jika app gagal atau belum terpasang, Ransapos tetap bisa memakai mode print lama.

Project ini harus dibuat terpisah dari aplikasi Laravel Ransapos agar tidak merusak POS yang sudah berjalan.

## 2. Problem Statement

Saat ini Ransapos sudah bisa mencetak thermal dengan beberapa mode:
- Browser print.
- Web Bluetooth.
- RawBT Android.
- QZ Tray/Printer Bridge.

Masalah utama Web Bluetooth:
- Chrome Android membatasi akses Bluetooth dari halaman web.
- User sering harus memilih printer ulang lewat popup.
- `navigator.bluetooth.getDevices()` tidak selalu tersedia atau tidak selalu mengembalikan printer yang pernah diizinkan.
- Koneksi hilang saat halaman reload, browser ditutup, atau tab tidur.

RawBT lebih stabil karena berjalan sebagai aplikasi native, tetapi memakai aplikasi pihak ketiga. Ransapos Printer Service dibuat agar pengalaman print native bisa dikontrol penuh oleh Ransapos.

## 3. Tujuan

Tujuan MVP:
- Membuat Android app native khusus untuk menerima struk dari Ransapos dan mencetak ke printer Bluetooth thermal.
- Menyimpan satu printer default per device Android.
- Mendukung test print dari app Android.
- Mendukung print dari web melalui custom URL scheme atau Android intent.
- Menjaga Ransapos tetap aman: tidak mengubah database, transaksi, stok, atau logika pembayaran.

Tujuan non-MVP:
- Tidak membuat aplikasi POS Android penuh.
- Tidak mengganti Ransapos web.
- Tidak membuat sinkronisasi offline.
- Tidak membuat antrean print kompleks.
- Tidak membuat service HTTP lokal di Android untuk versi pertama.

## 4. Prinsip Keamanan Integrasi

Integrasi dengan Ransapos harus bersifat optional dan fallback-safe:
- Mode print lama tetap tersedia.
- Tambahan mode baru tidak boleh mengubah alur simpan transaksi.
- Tidak boleh menambah migration database.
- Tidak boleh mengubah struktur tabel sales, payments, sale_items, cash_sessions.
- Tidak boleh mengubah endpoint `/pos/sales/{sale}/escpos` kecuali benar-benar diperlukan.
- Jika Android app tidak terpasang, Ransapos harus menampilkan pesan yang jelas dan tetap menyediakan fallback print lama.
- Jika Android app gagal print, transaksi tetap dianggap sudah tersimpan. Print adalah efek samping, bukan bagian dari commit transaksi.

## 5. Persona dan Use Case

### Persona

Kasir outlet:
- Menggunakan tablet Android.
- Membuka Ransapos melalui browser.
- Mencetak struk setelah pembayaran.
- Tidak ingin memilih printer setiap transaksi.

Admin teknis:
- Menginstall APK di tablet.
- Memilih printer thermal default.
- Melakukan test print.
- Troubleshoot koneksi Bluetooth.

### Use Case Utama

1. Admin membuka Android app.
2. Admin memilih printer Bluetooth thermal.
3. App menyimpan printer sebagai default.
4. Admin menjalankan test print.
5. Kasir membuka Ransapos web.
6. Kasir memilih mode print `Ransapos Printer Service`.
7. Kasir menyelesaikan pembayaran.
8. Kasir klik `Print Receipt`.
9. Ransapos mengambil ESC/POS base64 dari backend.
10. Ransapos membuka intent ke Android app.
11. Android app mencetak ke printer default.

## 6. Scope MVP

### Android App

Fitur wajib:
- Daftar printer Bluetooth paired.
- Pilih printer default.
- Simpan printer default di storage lokal Android.
- Test print.
- Terima print request dari custom URL scheme.
- Decode base64 ESC/POS.
- Kirim byte ESC/POS ke printer Bluetooth.
- Tampilkan status print sukses/gagal.
- Tampilkan error jika printer default belum dipilih.

Fitur optional untuk MVP:
- Auto reconnect sederhana sebelum print.
- Tombol hapus printer default.
- Tampilan log print terakhir.

Tidak termasuk MVP:
- Print queue persistent.
- Background service permanen.
- Local HTTP server.
- Multi-printer routing.
- Sinkronisasi ke server Ransapos.
- Login di Android app.
- Reprint history dari Android app.

### Ransapos Web

Fitur wajib:
- Tambah mode print baru: `Ransapos Printer Service`.
- Saat mode ini aktif, alur print:
  - Fetch `/pos/sales/{sale}/escpos`.
  - Ambil field `base64`.
  - Buka custom scheme intent ke Android app.
- Jika intent gagal atau app tidak terpasang, tampilkan pesan dan fallback ke mode print lain.

Tidak boleh:
- Menghapus mode print lama.
- Mengubah alur pembayaran.
- Mengubah format data transaksi.

## 7. Kontrak Integrasi

### Endpoint Ransapos Existing

Endpoint:

```http
GET /pos/sales/{sale}/escpos
Accept: application/json
```

Response sukses:

```json
{
  "base64": "G0AbdAAbaQEbRQ..."
}
```

Keterangan:
- `base64` berisi byte ESC/POS yang sudah siap dikirim ke printer.
- Android app tidak perlu membentuk layout struk.
- Android app hanya decode base64 dan kirim byte ke printer.

### Custom URL Scheme

Scheme MVP:

```text
ransaposprint://print?base64={BASE64_URL_ENCODED}
```

Parameter:
- `base64`: required, string base64 URL encoded.
- `job_id`: optional, string, misalnya invoice number atau sale id.
- `source`: optional, default `ransapos`.

Contoh:

```text
ransaposprint://print?job_id=INV-001260603-0012&source=ransapos&base64=G0AbdAAbaQ...
```

Android app behavior:
- Jika path bukan `/print`, tampilkan error.
- Jika `base64` kosong atau invalid, tampilkan error.
- Jika printer default belum ada, buka halaman pilih printer dan tampilkan pesan.
- Jika printer default ada, langsung print.

### Android Intent Filter

Android app harus mendaftarkan intent filter:

```xml
<intent-filter>
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data android:scheme="ransaposprint" android:host="print" />
</intent-filter>
```

Catatan implementasi:
- Jika memakai host `print`, URL berbentuk `ransaposprint://print?...`.
- Jika memakai path `/print`, sesuaikan intent filter dan parser.
- Pilih satu format dan konsisten. Rekomendasi MVP: `host=print`.

## 8. Android Permission

Target Android minimal:
- Minimum SDK: 23 atau sesuai kebutuhan tim.
- Target SDK: versi modern yang dipakai Android Studio saat project dibuat.

Permission wajib:

Untuk Android 12+:
```xml
<uses-permission android:name="android.permission.BLUETOOTH_CONNECT" />
<uses-permission android:name="android.permission.BLUETOOTH_SCAN" />
```

Untuk Android lama:
```xml
<uses-permission android:name="android.permission.BLUETOOTH" android:maxSdkVersion="30" />
<uses-permission android:name="android.permission.BLUETOOTH_ADMIN" android:maxSdkVersion="30" />
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" android:maxSdkVersion="30" />
```

Runtime permission:
- Android 12+: request `BLUETOOTH_CONNECT` sebelum membaca paired devices atau connect.
- Android 11 ke bawah: jika melakukan discovery, mungkin perlu location permission. Untuk MVP, cukup membaca paired devices, jadi discovery aktif bisa dihindari.

## 9. Bluetooth Mode

Prioritas MVP:
- Bluetooth Classic SPP.

Alasan:
- Banyak printer thermal murah memakai Bluetooth Classic Serial Port Profile.
- RawBT biasanya stabil karena memakai jalur native ini.
- Web Bluetooth hanya mendukung BLE, sedangkan banyak printer thermal bukan BLE murni.

SPP UUID umum:

```text
00001101-0000-1000-8000-00805F9B34FB
```

Print flow native:
1. Ambil bonded devices.
2. Pilih printer default berdasarkan MAC address.
3. Buat RFCOMM socket dengan UUID SPP.
4. Connect.
5. Tulis byte ESC/POS ke output stream.
6. Flush.
7. Delay singkat jika perlu.
8. Close socket.

Fallback BLE:
- Tidak wajib di MVP.
- Jika printer ternyata BLE-only, MVP bisa gagal dan perlu fase lanjutan.

## 10. UI Android App

### Screen Utama

Elemen:
- Nama app: `Ransapos Printer Service`.
- Status printer default:
  - `Belum dipilih`
  - `Printer: {name}`
  - `Terakhir print: sukses/gagal`
- Tombol `Pilih Printer`.
- Tombol `Test Print`.
- Tombol `Hapus Printer`.
- Area status/error singkat.

### Screen Pilih Printer

Elemen:
- List paired Bluetooth devices.
- Setiap item menampilkan:
  - Nama device.
  - MAC address.
- Tombol refresh paired devices.
- Jika kosong, tampilkan instruksi pairing dari Android Settings.

Behavior:
- Klik device menyimpan sebagai default.
- Setelah tersimpan, kembali ke screen utama.

### Intent Print UI

Saat app dibuka dari intent print:
- Jika printer default ada:
  - Tampilkan layar singkat `Mencetak...`
  - Print.
  - Tampilkan `Berhasil dicetak` atau `Gagal mencetak`.
  - Optional: auto close setelah 1 sampai 2 detik.
- Jika printer default belum ada:
  - Tampilkan `Printer belum dipilih`.
  - Arahkan ke screen pilih printer.

## 11. Ransapos Web UI

Tambahan di setting print POS:
- Tambah option print engine:

```text
Ransapos Printer Service (Android)
```

Status text:
- Jika mode aktif: `Mode Android Service aktif. Struk dikirim ke aplikasi Ransapos Printer Service di tablet.`

Print behavior:
- Fetch `/pos/sales/{sale}/escpos`.
- Build URL:

```javascript
const url = 'ransaposprint://print'
  + '?job_id=' + encodeURIComponent(invoiceOrSaleId)
  + '&source=ransapos'
  + '&base64=' + encodeURIComponent(base64);
window.location.href = url;
```

Fallback:
- Jika setelah membuka intent halaman tetap aktif dan user melapor tidak ada respon, tampilkan bantuan:
  - `Pastikan Ransapos Printer Service sudah terinstall.`
  - `Pastikan printer default sudah dipilih di aplikasi.`
- Browser tidak selalu bisa mendeteksi intent gagal dengan akurat. Untuk MVP cukup tampilkan status setelah mencoba membuka intent.

## 12. Data Storage Android

Storage lokal app:

```json
{
  "defaultPrinterName": "RPP02N",
  "defaultPrinterAddress": "00:11:22:33:44:55",
  "lastPrintJobId": "INV-001260603-0012",
  "lastPrintStatus": "success",
  "lastPrintAt": "2026-06-05T20:15:00+07:00"
}
```

Catatan:
- MAC address hanya disimpan lokal di tablet.
- Tidak perlu dikirim ke server Ransapos.
- Tidak perlu database Android. SharedPreferences/DataStore cukup.

## 13. Error Handling

Error yang harus ditangani:
- App dibuka tanpa parameter `base64`.
- Base64 invalid.
- Printer default belum dipilih.
- Permission Bluetooth belum diberikan.
- Bluetooth device tidak paired.
- Socket connect gagal.
- Output stream gagal ditulis.
- Printer mati atau di luar jangkauan.
- Android versi baru menolak permission.

Pesan error harus singkat dan operasional:
- `Printer belum dipilih.`
- `Izin Bluetooth belum diberikan.`
- `Printer tidak tersambung atau mati.`
- `Gagal mengirim data ke printer.`
- `Data struk tidak valid.`

## 14. Acceptance Criteria

Android app:
- Bisa menampilkan paired Bluetooth devices.
- Bisa menyimpan printer default.
- Bisa test print ke printer thermal.
- Bisa menerima `ransaposprint://print?...`.
- Bisa mencetak ESC/POS base64 dari intent.
- Tidak crash jika printer mati.
- Tidak crash jika base64 invalid.
- Menampilkan status sukses/gagal.

Ransapos web:
- Mode print baru muncul di setting print.
- Mode lama tetap tersedia.
- Saat mode Android Service aktif, print mengambil base64 dari endpoint existing.
- Intent ke app Android terbuka.
- Jika app tidak terpasang, user tetap bisa pindah ke mode print lama.

Operasional:
- Setelah printer default dipilih satu kali, print berikutnya tidak meminta pilih printer dari web.
- Print tetap tidak mempengaruhi status transaksi.
- Jika print gagal, transaksi tetap tersimpan dan bisa reprint dari history.

## 15. Test Plan

### Test Android App Manual

1. Install APK.
2. Buka app.
3. Grant Bluetooth permission.
4. Pilih printer paired.
5. Klik test print.
6. Matikan printer, klik test print, pastikan muncul error.
7. Nyalakan printer lagi, test print ulang.

### Test Intent Manual

Gunakan browser atau adb untuk membuka:

```text
ransaposprint://print?job_id=TEST-001&source=test&base64=G0AbYQFUZXN0IFByaW50Cg==
```

Expected:
- App terbuka.
- App mencoba print.
- Status ditampilkan.

### Test dari Ransapos

1. Login POS di tablet.
2. Pilih mode print `Ransapos Printer Service`.
3. Buat transaksi kecil.
4. Klik print receipt.
5. App Android terbuka dan mencetak.
6. Reprint dari history.
7. Ganti mode kembali ke Web Bluetooth atau RawBT, pastikan tetap bekerja.

### Regression Test Ransapos

Pastikan tidak berubah:
- Simpan transaksi.
- Payment cash.
- Payment non-cash.
- Reprint history.
- Print browser.
- Print Web Bluetooth.
- Print RawBT.

## 16. Rollout Plan

Tahap 1: Android app standalone.
- Buat project Android baru.
- Implement pilih printer dan test print.
- Tidak mengubah Ransapos.

Tahap 2: Intent print.
- Tambah intent receiver.
- Test dengan base64 statis.
- Test dengan ESC/POS dari Ransapos secara manual.

Tahap 3: Integrasi Ransapos optional.
- Tambah mode print baru.
- Pakai endpoint ESC/POS existing.
- Fallback tetap tersedia.

Tahap 4: Uji outlet.
- Install APK di satu tablet.
- Test satu printer.
- Test selama satu shift.
- Catat error Bluetooth dan print gagal.

## 17. Rekomendasi Stack

Pilihan sederhana:
- Kotlin.
- Android native views atau Jetpack Compose.
- SharedPreferences atau DataStore.
- BluetoothAdapter dan BluetoothSocket untuk SPP.

Rekomendasi MVP:
- Kotlin + Jetpack Compose jika tim nyaman.
- Kotlin + XML layout jika ingin paling sederhana untuk agent Android lama.

Library ESC/POS:
- Tidak wajib karena layout sudah dibuat oleh Laravel.
- App hanya perlu decode base64 dan kirim byte.
- Library hanya diperlukan jika nanti app membentuk struk sendiri.

## 18. Risiko dan Mitigasi

Risiko: Printer bukan Bluetooth Classic SPP.
- Mitigasi: cek model printer. Jika BLE-only, buat fase BLE setelah MVP.

Risiko: Permission Android berbeda antar versi.
- Mitigasi: test minimal di Android target tablet kasir.

Risiko: Intent URL terlalu panjang.
- Mitigasi: struk thermal umumnya kecil. Jika ada struk sangat panjang dan intent gagal, fase berikutnya pakai file share atau local HTTP service.

Risiko: Browser tidak bisa mendeteksi app tidak terpasang.
- Mitigasi: tampilkan instruksi fallback di Ransapos dan biarkan user pindah mode print.

Risiko: App native gagal print tapi transaksi sudah selesai.
- Mitigasi: reprint dari history tetap tersedia.

## 19. Future Enhancement

Setelah MVP stabil:
- Local HTTP service agar web bisa menerima status print real-time.
- Print queue retry.
- Multi-printer.
- Auto-cut command configuration.
- Paper width 58mm/80mm setting.
- Open cash drawer command.
- App update flow.
- Device binding dengan outlet.

## 20. Prompt Singkat untuk Agent Project Android

Gunakan prompt ini saat membuat project Android baru:

```text
Buat Android app native bernama Ransapos Printer Service.

Tujuan MVP:
- App memilih dan menyimpan satu printer Bluetooth thermal default.
- App menerima custom URL scheme ransaposprint://print?job_id=...&source=ransapos&base64=...
- App decode base64 ESC/POS dan mengirim byte ke printer default via Bluetooth Classic SPP.
- App menyediakan tombol Pilih Printer, Test Print, dan Hapus Printer.
- App menangani permission Android 12+ BLUETOOTH_CONNECT dan BLUETOOTH_SCAN.
- App tidak perlu login, tidak perlu database, tidak perlu membentuk layout struk.

Kontrak print:
- base64 adalah byte ESC/POS siap cetak dari endpoint Ransapos /pos/sales/{sale}/escpos.
- Jika printer default belum ada, tampilkan screen pilih printer.
- Jika print sukses/gagal, tampilkan status jelas.

Prioritas teknis:
- Kotlin.
- Bluetooth Classic SPP UUID 00001101-0000-1000-8000-00805F9B34FB.
- Simpan default printer di SharedPreferences/DataStore.
- Jangan membuat local HTTP service untuk MVP.

Acceptance:
- Test print manual berhasil.
- Intent print berhasil.
- App tidak crash saat printer mati, permission ditolak, atau base64 invalid.
```

