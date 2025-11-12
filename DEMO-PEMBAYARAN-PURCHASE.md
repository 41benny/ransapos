# 🎯 Demo Fitur Pembayaran Purchase

## Skenario Demo

### 1️⃣ Buat Purchase Order
**Route:** `/admin/purchases/create`

```
Supplier: PT Maju Jaya
Outlet: Cabang Utama  
Tanggal: 08 November 2025

Items:
- Produk A: 10 pcs x Rp 100,000 = Rp 1,000,000
- Produk B: 5 pcs x Rp 200,000 = Rp 1,000,000

Subtotal: Rp 2,000,000
Pajak: Rp 0
Diskon: Rp 0
Total: Rp 2,000,000

Status: Draft
Payment Status: Pending
```

### 2️⃣ Terima Barang
**Route:** `/admin/purchases/{id}`

Klik tombol **"Terima Barang"**

```
✅ Status berubah: Draft → Received
✅ Stok bertambah di sistem
✅ Section "Status Pembayaran" muncul
```

### 3️⃣ Catat Pembayaran Pertama (Parsial)
**Route:** `/admin/purchases/{id}/payment`

```
Akun Kas: Bank BCA - Rekening Operasional
Jumlah: Rp 1,000,000 (50% dari total)
Tanggal: 08 November 2025
Catatan: Pembayaran DP 50%
```

**Submit →**

```
✅ Transaksi kas keluar tercatat
   Nomor Voucher: KAS-BANK-BCA-20251108-001
   
✅ Saldo Bank BCA berkurang: Rp 50,000,000 → Rp 49,000,000

✅ Payment Status berubah: Pending → Partial

✅ Riwayat pembayaran muncul di detail purchase:
   [Icon] KAS-BANK-BCA-20251108-001
   08 Nov 2025 • Bank BCA - Rekening Operasional
   Rp 1,000,000
   Admin User
```

### 4️⃣ Catat Pembayaran Kedua (Pelunasan)
**Route:** `/admin/purchases/{id}/payment`

```
Akun Kas: Kas Toko Utama
Jumlah: Rp 1,000,000 (50% sisanya)
Tanggal: 08 November 2025
Catatan: Pelunasan
```

**Submit →**

```
✅ Transaksi kas keluar tercatat
   Nomor Voucher: KAS-KAS-001-20251108-001
   
✅ Saldo Kas Toko berkurang: Rp 5,000,000 → Rp 4,000,000

✅ Payment Status berubah: Partial → Paid

✅ Badge status: "Lunas" (hijau)

✅ Riwayat pembayaran lengkap:
   1. KAS-BANK-BCA-20251108-001 - Rp 1,000,000
   2. KAS-KAS-001-20251108-001 - Rp 1,000,000
   
   Total Dibayar: Rp 2,000,000 ✅
   Sisa Tagihan: Rp 0 ✅
```

---

## 📸 Screenshot View

### Detail Purchase - Status Pembayaran

```
┌─────────────────────────────────────────────────────┐
│ Status Pembayaran               [Badge: Lunas 🟢]  │
├─────────────────────────────────────────────────────┤
│                                                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐        │
│  │ Total PO │  │ Dibayar  │  │   Sisa   │        │
│  │ Rp 2.0 jt│  │ Rp 2.0 jt│  │   Rp 0   │        │
│  └──────────┘  └──────────┘  └──────────┘        │
│                                                     │
│  Riwayat Pembayaran                                │
│  ────────────────────────────────────────────       │
│                                                     │
│  [💰] KAS-BANK-BCA-20251108-001                    │
│       08 Nov 2025 • Bank BCA                       │
│       Pembayaran DP 50%                            │
│                               Rp 1,000,000         │
│                               Admin User           │
│                                                     │
│  [💰] KAS-KAS-001-20251108-001                     │
│       08 Nov 2025 • Kas Toko Utama                 │
│       Pelunasan                                    │
│                               Rp 1,000,000         │
│                               Admin User           │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## 🧪 Test Results

```bash
php artisan test --filter=PurchasePaymentTest
```

```
✓ can record purchase payment after received (0.36s)
✓ purchase status becomes paid when fully paid (0.04s)  
✓ can make partial payments (0.05s)
✓ cannot pay more than remaining amount (0.04s)
✓ cannot pay draft purchase (0.03s)
✓ payment includes coa account (0.04s)
✓ transaction number increments daily per account (0.05s)
✓ insufficient balance throws exception (0.04s)

Tests: 8 passed (26 assertions)
Duration: 2.26s
```

---

## 📊 Database Impact

### Sebelum Pembayaran
```sql
-- purchases
payment_status: 'pending'

-- cash_accounts (Bank BCA)
current_balance: 50000000.00

-- cash_transactions
(belum ada transaksi pembayaran purchase)
```

### Setelah Pembayaran Pertama
```sql
-- purchases  
payment_status: 'partial'

-- cash_accounts (Bank BCA)
current_balance: 49000000.00  -- berkurang 1jt

-- cash_transactions
id: 1
transaction_number: 'KAS-BANK-BCA-20251108-001'
type: 'out'
amount: 1000000.00
reference_type: 'purchase'
reference_id: 1
coa_account_id: 3  -- HPP
```

### Setelah Pelunasan
```sql
-- purchases
payment_status: 'paid'  -- berubah jadi LUNAS

-- cash_accounts (Kas Toko)
current_balance: 4000000.00  -- berkurang 1jt

-- cash_transactions (2 records)
1. KAS-BANK-BCA-20251108-001 | 1000000 | purchase | ref_id:1
2. KAS-KAS-001-20251108-001  | 1000000 | purchase | ref_id:1

Total: 2000000 = Purchase Total ✅
```

---

## 🔐 Business Rules Validation

✅ **Rule 1:** Purchase harus sudah received sebelum bisa dibayar
```php
❌ Draft Purchase → Tidak bisa dibayar
✅ Received Purchase → Bisa dibayar
```

✅ **Rule 2:** Tidak bisa bayar lebih dari sisa tagihan
```php
Total Purchase: Rp 2,000,000
Sudah Dibayar: Rp 1,500,000
Sisa: Rp 500,000

❌ Bayar Rp 600,000 → Error! Melebihi sisa
✅ Bayar Rp 500,000 → OK
```

✅ **Rule 3:** Saldo kas harus mencukupi
```php
Saldo Kas: Rp 100,000
Pembayaran: Rp 500,000

❌ Error! Saldo tidak mencukupi
```

✅ **Rule 4:** Setiap transaksi tercatat di COA (HPP)
```php
coa_account_id: 3  // Harga Pokok Penjualan
✅ Terintegrasi dengan sistem akuntansi
```

✅ **Rule 5:** Nomor voucher unik per hari per akun
```php
KAS-BCA-20251108-001
KAS-BCA-20251108-002
KAS-BCA-20251108-003
...
KAS-BCA-20251109-001  // Reset di hari berikutnya
```

---

## 🎨 UI Features

### Badge Status
- 🔴 **Belum Dibayar** - `bg-red-100 text-red-800`
- 🟡 **Dibayar Sebagian** - `bg-yellow-100 text-yellow-800`  
- 🟢 **Lunas** - `bg-green-100 text-green-800`

### Card Pembayaran
- Background color-coded untuk summary
- Icon yang jelas untuk setiap pembayaran
- Timeline view untuk riwayat
- Responsive design

### Form Input
- Select akun dengan info saldo real-time
- Input amount dengan validasi max
- Date picker
- Auto-focus pada field penting

---

## 🚀 Ready to Production!

Fitur pembayaran purchase sudah **PRODUCTION READY**:

✅ Fully Tested (8 test cases, 26 assertions)
✅ Code Formatted (Laravel Pint)
✅ Database Optimized (Indexes, Foreign Keys)
✅ UI/UX Polished (Tailwind CSS)
✅ Security Validated (DB Transactions)
✅ Documentation Complete
✅ Error Handling Comprehensive
✅ Business Rules Enforced

**Status:** ✅ SELESAI & SIAP DIGUNAKAN! 🎉

