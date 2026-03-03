<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OutletController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\SalesTypeController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\PosDeviceController as AdminPosDeviceController;
use App\Http\Controllers\Admin\PromoVoucherController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\POS\DashboardController as POSDashboardController;
use App\Http\Controllers\POS\SaleController;
use App\Http\Controllers\POS\KitchenController;
use App\Http\Controllers\POS\CashSessionController;
use App\Http\Controllers\POS\DeviceController as PosDeviceController;
use App\Http\Controllers\POS\PinLoginController as PosPinLoginController;

// Redirect root ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('guest');
Route::get('/pos/pin', [PosPinLoginController::class, 'show'])->name('pos.pin.show')->middleware('guest');
Route::post('/pos/pin', [PosPinLoginController::class, 'login'])->name('pos.pin.login')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Back Office (Admin) Routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,manager,superadmin,pajak'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('permission:dashboard.view');
    Route::get('/dashboard/summary', [AdminDashboardController::class, 'summary'])
        ->name('dashboard.summary')
        ->middleware('permission:dashboard.view');

    // Master Data
    Route::post('products/import', [ProductController::class, 'import'])
        ->name('products.import')
        ->middleware('permission:products.import');
    Route::get('products/create-bundle', [ProductController::class, 'createBundle'])
        ->name('products.create-bundle')
        ->middleware('permission:products.create');
    Route::resource('products', ProductController::class)
        ->only(['create', 'store'])
        ->middleware('permission:products.create');
    Route::resource('products', ProductController::class)
        ->only(['index', 'show'])
        ->middleware('permission:products.view');
    Route::resource('products', ProductController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:products.update');
    Route::resource('products', ProductController::class)
        ->only(['destroy'])
        ->middleware('permission:products.delete');

    Route::resource('outlets', OutletController::class)
        ->only(['create', 'store'])
        ->middleware('permission:outlets.create');
    Route::resource('outlets', OutletController::class)
        ->only(['index', 'show'])
        ->middleware('permission:outlets.view');
    Route::resource('outlets', OutletController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:outlets.update');

    Route::resource('suppliers', SupplierController::class)
        ->only(['index'])
        ->middleware('permission:suppliers.view');
    Route::resource('suppliers', SupplierController::class)
        ->only(['create', 'store'])
        ->middleware('permission:suppliers.create');

    Route::resource('payment-methods', PaymentMethodController::class)
        ->only(['create', 'store'])
        ->middleware('permission:payment-methods.create');
    Route::resource('payment-methods', PaymentMethodController::class)
        ->only(['index'])
        ->middleware('permission:payment-methods.view');
    Route::resource('payment-methods', PaymentMethodController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:payment-methods.update');
    Route::resource('payment-methods', PaymentMethodController::class)
        ->only(['destroy'])
        ->middleware('permission:payment-methods.delete');

    Route::resource('sales-types', SalesTypeController::class)
        ->only(['create', 'store'])
        ->middleware('permission:sales-types.create');
    Route::resource('sales-types', SalesTypeController::class)
        ->only(['index'])
        ->middleware('permission:sales-types.view');
    Route::resource('sales-types', SalesTypeController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:sales-types.update');
    Route::resource('sales-types', SalesTypeController::class)
        ->only(['destroy'])
        ->middleware('permission:sales-types.delete');

    // POS Device Management
    Route::get('pos-devices', [AdminPosDeviceController::class, 'index'])
        ->name('pos-devices.index')
        ->middleware('permission:pos-devices.view');
    Route::post('pos-devices/pairing', [AdminPosDeviceController::class, 'storePairing'])
        ->name('pos-devices.pairing')
        ->middleware('permission:pos-devices.manage');
    Route::post('pos-devices/enforce', [AdminPosDeviceController::class, 'updateEnforcement'])
        ->name('pos-devices.enforce')
        ->middleware('permission:pos-devices.manage');
    Route::post('pos-devices/{posDevice}/revoke', [AdminPosDeviceController::class, 'revoke'])
        ->name('pos-devices.revoke')
        ->middleware('permission:pos-devices.manage');
    Route::delete('pos-devices/{posDevice}', [AdminPosDeviceController::class, 'destroy'])
        ->name('pos-devices.destroy')
        ->middleware('permission:pos-devices.manage');

    // Purchases
    Route::resource('purchases', \App\Http\Controllers\Admin\PurchaseController::class)
        ->only(['create', 'store'])
        ->middleware('permission:purchases.create');
    Route::resource('purchases', \App\Http\Controllers\Admin\PurchaseController::class)
        ->only(['index', 'show'])
        ->middleware('permission:purchases.view');
    Route::resource('purchases', \App\Http\Controllers\Admin\PurchaseController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:purchases.update');
    Route::resource('purchases', \App\Http\Controllers\Admin\PurchaseController::class)
        ->only(['destroy'])
        ->middleware('permission:purchases.delete');
    Route::get('purchases/{purchase}/print', [\App\Http\Controllers\Admin\PurchaseController::class, 'printPo'])
        ->name('purchases.print')
        ->middleware('permission:purchases.print');
    Route::post('purchases/{purchase}/receive', [\App\Http\Controllers\Admin\PurchaseController::class, 'receive'])
        ->name('purchases.receive')
        ->middleware('permission:purchases.receive');
    Route::post('purchases/{purchase}/cancel', [\App\Http\Controllers\Admin\PurchaseController::class, 'cancel'])
        ->name('purchases.cancel')
        ->middleware('permission:purchases.cancel');
    Route::get('purchases/{purchase}/payment', [\App\Http\Controllers\Admin\PurchaseController::class, 'showPaymentForm'])
        ->name('purchases.payment')
        ->middleware('permission:purchases.payment');
    Route::post('purchases/{purchase}/payment', [\App\Http\Controllers\Admin\PurchaseController::class, 'storePayment'])
        ->name('purchases.payment.store')
        ->middleware('permission:purchases.payment');

    // Promo & Voucher
    Route::get('promo-vouchers', [PromoVoucherController::class, 'index'])
        ->name('promo-vouchers.index')
        ->middleware('permission:promo-vouchers.view');
    Route::post('promo-vouchers/promotions', [PromoVoucherController::class, 'storePromotion'])
        ->name('promo-vouchers.promotions.store')
        ->middleware('permission:promo-vouchers.manage');
    Route::post('promo-vouchers/promotions/{promotion}/toggle', [PromoVoucherController::class, 'togglePromotion'])
        ->name('promo-vouchers.promotions.toggle')
        ->middleware('permission:promo-vouchers.manage');
    Route::delete('promo-vouchers/promotions/{promotion}', [PromoVoucherController::class, 'destroyPromotion'])
        ->name('promo-vouchers.promotions.destroy')
        ->middleware('permission:promo-vouchers.manage');
    Route::post('promo-vouchers/vouchers', [PromoVoucherController::class, 'storeVoucher'])
        ->name('promo-vouchers.vouchers.store')
        ->middleware('permission:promo-vouchers.manage');
    Route::post('promo-vouchers/vouchers/{voucher}/toggle', [PromoVoucherController::class, 'toggleVoucher'])
        ->name('promo-vouchers.vouchers.toggle')
        ->middleware('permission:promo-vouchers.manage');
    Route::delete('promo-vouchers/vouchers/{voucher}', [PromoVoucherController::class, 'destroyVoucher'])
        ->name('promo-vouchers.vouchers.destroy')
        ->middleware('permission:promo-vouchers.manage');

    // Cash Accounts & Transactions
    Route::resource('cash-accounts', \App\Http\Controllers\Admin\CashAccountController::class)
        ->only(['create', 'store'])
        ->middleware('permission:cash-accounts.create');
    Route::resource('cash-accounts', \App\Http\Controllers\Admin\CashAccountController::class)
        ->only(['index', 'show'])
        ->middleware('permission:cash-accounts.view');
    Route::resource('cash-accounts', \App\Http\Controllers\Admin\CashAccountController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:cash-accounts.update');
    Route::resource('cash-accounts', \App\Http\Controllers\Admin\CashAccountController::class)
        ->only(['destroy'])
        ->middleware('permission:cash-accounts.delete');

    Route::get('cash-transactions', [\App\Http\Controllers\Admin\CashAccountController::class, 'transactions'])
        ->name('cash-transactions.index')
        ->middleware('permission:cash-transactions.view');
    Route::get('cash-transactions/create', [\App\Http\Controllers\Admin\CashAccountController::class, 'createTransaction'])
        ->name('cash-transactions.create')
        ->middleware('permission:cash-transactions.create');
    Route::post('cash-transactions', [\App\Http\Controllers\Admin\CashAccountController::class, 'storeTransaction'])
        ->name('cash-transactions.store')
        ->middleware('permission:cash-transactions.create');
    Route::get('cash-transactions/{cashTransaction}/edit', [\App\Http\Controllers\Admin\CashAccountController::class, 'editTransaction'])
        ->name('cash-transactions.edit')
        ->middleware('permission:cash-transactions.update');
    Route::put('cash-transactions/{cashTransaction}', [\App\Http\Controllers\Admin\CashAccountController::class, 'updateTransaction'])
        ->name('cash-transactions.update')
        ->middleware('permission:cash-transactions.update');
    Route::delete('cash-transactions/{cashTransaction}', [\App\Http\Controllers\Admin\CashAccountController::class, 'destroyTransaction'])
        ->name('cash-transactions.destroy')
        ->middleware('permission:cash-transactions.delete');
    Route::get('cash-transactions/{cashTransaction}/print', [\App\Http\Controllers\Admin\CashAccountController::class, 'printVoucher'])
        ->name('cash-transactions.print')
        ->middleware('permission:cash-transactions.print');
    Route::get('cash-transactions/{cashTransaction}', [\App\Http\Controllers\Admin\CashAccountController::class, 'showTransaction'])
        ->name('cash-transactions.show')
        ->middleware('permission:cash-transactions.view');
    Route::get('cash-accounts/{cashAccount}/mutation-report', [\App\Http\Controllers\Admin\CashAccountController::class, 'mutationReport'])
        ->name('cash-accounts.mutation-report')
        ->middleware('permission:cash-accounts.mutation-report.view');

    // Bank Transfers
    Route::resource('bank-transfers', \App\Http\Controllers\Admin\BankTransferController::class)
        ->only(['create', 'store'])
        ->middleware('permission:bank-transfers.create');
    Route::resource('bank-transfers', \App\Http\Controllers\Admin\BankTransferController::class)
        ->only(['index', 'show'])
        ->middleware('permission:bank-transfers.view');

    // COA (Chart of Accounts)
    Route::post('coa-accounts/generate-balance-template', [\App\Http\Controllers\Admin\CoaAccountController::class, 'generateBalanceTemplate'])
        ->name('coa-accounts.generate-balance-template')
        ->middleware('permission:coa-accounts.balance-template.generate');
    Route::resource('coa-accounts', \App\Http\Controllers\Admin\CoaAccountController::class)
        ->only(['create', 'store'])
        ->middleware('permission:coa-accounts.create');
    Route::resource('coa-accounts', \App\Http\Controllers\Admin\CoaAccountController::class)
        ->only(['index', 'show'])
        ->middleware('permission:coa-accounts.view');
    Route::resource('coa-accounts', \App\Http\Controllers\Admin\CoaAccountController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:coa-accounts.update');
    Route::resource('coa-accounts', \App\Http\Controllers\Admin\CoaAccountController::class)
        ->only(['destroy'])
        ->middleware('permission:coa-accounts.delete');

    // Bill of Materials (BOM)
    Route::resource('boms', \App\Http\Controllers\Admin\BomController::class)
        ->only(['create', 'store'])
        ->middleware('permission:boms.create');
    Route::resource('boms', \App\Http\Controllers\Admin\BomController::class)
        ->only(['index', 'show'])
        ->middleware('permission:boms.view');
    Route::resource('boms', \App\Http\Controllers\Admin\BomController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:boms.update');
    Route::resource('boms', \App\Http\Controllers\Admin\BomController::class)
        ->only(['destroy'])
        ->middleware('permission:boms.delete');

    // Inventory & Stock Management
    Route::get('/stocks', [\App\Http\Controllers\Admin\StockController::class, 'index'])
        ->name('stocks.index')
        ->middleware('permission:stocks.view');
    Route::get('/stocks/mutations', [\App\Http\Controllers\Admin\StockController::class, 'mutations'])
        ->name('stocks.mutations')
        ->middleware('permission:stocks.view');
    Route::get('/stocks/adjustment', [\App\Http\Controllers\Admin\StockController::class, 'adjustment'])
        ->name('stocks.adjustment')
        ->middleware('permission:stocks.view');
    Route::post('/stocks/adjustment', [\App\Http\Controllers\Admin\StockController::class, 'storeAdjustment'])
        ->name('stocks.adjustment.store')
        ->middleware('permission:stocks.adjust');
    Route::get('/stocks/card', [\App\Http\Controllers\Admin\StockController::class, 'stockCard'])
        ->name('stocks.card')
        ->middleware('permission:stocks.view');
    Route::get('/stocks/current', [\App\Http\Controllers\Admin\StockController::class, 'getCurrentStock'])
        ->name('stocks.current')
        ->middleware('permission:stocks.view');
    Route::get('/stocks/export', [\App\Http\Controllers\Admin\StockController::class, 'export'])
        ->name('stocks.export')
        ->middleware('permission:stocks.export');

    // Stock Transfers
    Route::get('stock-transfers/available-stock', [\App\Http\Controllers\Admin\StockTransferController::class, 'getAvailableStock'])
        ->name('stock-transfers.available-stock')
        ->middleware('permission:stock-transfers.create|stock-transfers.view');
    Route::resource('stock-transfers', \App\Http\Controllers\Admin\StockTransferController::class)
        ->only(['create', 'store'])
        ->middleware('permission:stock-transfers.create');
    Route::resource('stock-transfers', \App\Http\Controllers\Admin\StockTransferController::class)
        ->only(['index', 'show'])
        ->middleware('permission:stock-transfers.view');
    Route::resource('stock-transfers', \App\Http\Controllers\Admin\StockTransferController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:stock-transfers.update');
    Route::resource('stock-transfers', \App\Http\Controllers\Admin\StockTransferController::class)
        ->only(['destroy'])
        ->middleware('permission:stock-transfers.cancel');
    Route::post('stock-transfers/{stockTransfer}/send', [\App\Http\Controllers\Admin\StockTransferController::class, 'send'])
        ->name('stock-transfers.send')
        ->middleware('permission:stock-transfers.update');
    Route::get('stock-transfers/{stockTransfer}/receive-form', [\App\Http\Controllers\Admin\StockTransferController::class, 'receiveForm'])
        ->name('stock-transfers.receive-form')
        ->middleware('permission:stock-transfers.update');
    Route::post('stock-transfers/{stockTransfer}/receive', [\App\Http\Controllers\Admin\StockTransferController::class, 'receive'])
        ->name('stock-transfers.receive')
        ->middleware('permission:stock-transfers.update');
    Route::post('stock-transfers/{stockTransfer}/cancel', [\App\Http\Controllers\Admin\StockTransferController::class, 'cancel'])
        ->name('stock-transfers.cancel')
        ->middleware('permission:stock-transfers.cancel');
    Route::get('stock-transfers/{stockTransfer}/print', [\App\Http\Controllers\Admin\StockTransferController::class, 'print'])
        ->name('stock-transfers.print')
        ->middleware('permission:stock-transfers.view');

    // Expense Management
    Route::resource('expense-categories', \App\Http\Controllers\Admin\ExpenseCategoryController::class)
        ->only(['create', 'store'])
        ->middleware('permission:expense-categories.create');
    Route::resource('expense-categories', \App\Http\Controllers\Admin\ExpenseCategoryController::class)
        ->only(['index', 'show'])
        ->middleware('permission:expense-categories.view');
    Route::resource('expense-categories', \App\Http\Controllers\Admin\ExpenseCategoryController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:expense-categories.update');
    Route::resource('expense-categories', \App\Http\Controllers\Admin\ExpenseCategoryController::class)
        ->only(['destroy'])
        ->middleware('permission:expense-categories.delete');

    Route::resource('expenses', \App\Http\Controllers\Admin\ExpenseController::class)
        ->only(['create', 'store'])
        ->middleware('permission:expenses.create');
    Route::resource('expenses', \App\Http\Controllers\Admin\ExpenseController::class)
        ->only(['index', 'show'])
        ->middleware('permission:expenses.view');
    Route::resource('expenses', \App\Http\Controllers\Admin\ExpenseController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:expenses.update');
    Route::resource('expenses', \App\Http\Controllers\Admin\ExpenseController::class)
        ->only(['destroy'])
        ->middleware('permission:expenses.delete');
    Route::post('expenses/{expense}/approve', [\App\Http\Controllers\Admin\ExpenseController::class, 'approve'])
        ->name('expenses.approve')
        ->middleware('permission:expenses.approve');
    Route::post('expenses/{expense}/reject', [\App\Http\Controllers\Admin\ExpenseController::class, 'reject'])
        ->name('expenses.reject')
        ->middleware('permission:expenses.reject');
    Route::post('expenses/{expense}/pay', [\App\Http\Controllers\Admin\ExpenseController::class, 'pay'])
        ->name('expenses.pay')
        ->middleware('permission:expenses.pay');
    Route::get('expenses-reports', [\App\Http\Controllers\Admin\ExpenseController::class, 'reports'])
        ->name('expenses.reports')
        ->middleware('permission:expenses.report.view');

    // Customer Management (CRM)
    Route::resource('customers', \App\Http\Controllers\Admin\CustomerController::class)
        ->only(['create', 'store'])
        ->middleware('permission:customers.create');
    Route::resource('customers', \App\Http\Controllers\Admin\CustomerController::class)
        ->only(['index', 'show'])
        ->middleware('permission:customers.view');
    Route::resource('customers', \App\Http\Controllers\Admin\CustomerController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:customers.update');
    Route::resource('customers', \App\Http\Controllers\Admin\CustomerController::class)
        ->only(['destroy'])
        ->middleware('permission:customers.delete');
    Route::post('customers/{customer}/add-points', [\App\Http\Controllers\Admin\CustomerController::class, 'addPoints'])
        ->name('customers.add-points')
        ->middleware('permission:customers.points.adjust');
    Route::post('customers/{customer}/redeem-points', [\App\Http\Controllers\Admin\CustomerController::class, 'redeemPoints'])
        ->name('customers.redeem-points')
        ->middleware('permission:customers.points.adjust');
    Route::get('customers-reports', [\App\Http\Controllers\Admin\CustomerController::class, 'reports'])
        ->name('customers.reports')
        ->middleware('permission:customers.report.view');

    // Cash Sessions (History)
    Route::get('/cash-sessions', [\App\Http\Controllers\Admin\CashSessionController::class, 'index'])
        ->name('cash-sessions.index')
        ->middleware('permission:cash-sessions.view');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        // Katalog (semua yang punya reports.view bisa lihat)
        Route::get('/', [\App\Http\Controllers\Admin\Reports\CatalogReportController::class, 'index'])->name('index')
            ->middleware('permission:reports.view');
        Route::get('/catalog/{slug}', [\App\Http\Controllers\Admin\Reports\CatalogReportController::class, 'show'])->name('catalog.show')
            ->middleware('permission:reports.view');

        // Laporan Penjualan (reports.sales.view ATAU reports.view lama)
        Route::get('/sales', [\App\Http\Controllers\Admin\Reports\SalesReportController::class, 'index'])->name('sales.index')
            ->middleware('permission:reports.sales.view|reports.view');
        Route::get('/sales/export', [\App\Http\Controllers\Admin\Reports\SalesReportController::class, 'exportIndex'])
            ->name('sales.export')
            ->middleware('permission:reports.sales.export|reports.export');

        // Laporan Penjualan Harian
        Route::get('/sales-daily', [\App\Http\Controllers\Admin\Reports\SalesReportController::class, 'dailySummary'])->name('sales.daily')
            ->middleware('permission:reports.daily.view|reports.view');
        Route::get('/sales-daily/export', [\App\Http\Controllers\Admin\Reports\SalesReportController::class, 'exportDailySummary'])
            ->name('sales.daily.export')
            ->middleware('permission:reports.daily.export|reports.export');

        // Laporan Per Produk
        Route::get('/sales-products', [\App\Http\Controllers\Admin\Reports\SalesReportController::class, 'products'])->name('sales.products')
            ->middleware('permission:reports.product.view|reports.view');
        Route::get('/sales-products/export', [\App\Http\Controllers\Admin\Reports\SalesReportController::class, 'exportProducts'])
            ->name('sales.products.export')
            ->middleware('permission:reports.product.export|reports.export');
        Route::get('/sales-products/export-old', [\App\Http\Controllers\Admin\Reports\SalesReportController::class, 'exportProductsOld'])
            ->name('sales.products.export-old')
            ->middleware('permission:reports.product.export|reports.export');

        // Laporan Shift
        Route::get('/shifts', [\App\Http\Controllers\Admin\Reports\ShiftReportController::class, 'index'])->name('shifts.index')
            ->middleware('permission:reports.shift.view|reports.view');
        Route::get('/shifts/{cashSession}', [\App\Http\Controllers\Admin\Reports\ShiftReportController::class, 'show'])->name('shifts.show')
            ->middleware('permission:reports.shift.view|reports.view');
        Route::get('/shifts/export', [\App\Http\Controllers\Admin\Reports\ShiftReportController::class, 'exportIndex'])
            ->name('shifts.export')
            ->middleware('permission:reports.shift.export|reports.export');
        Route::get('/shifts/{cashSession}/export', [\App\Http\Controllers\Admin\Reports\ShiftReportController::class, 'exportShow'])
            ->name('shifts.show.export')
            ->middleware('permission:reports.shift.export|reports.export');

        // Laporan Laba Rugi
        Route::get('/profit-loss', [\App\Http\Controllers\Admin\Reports\ProfitLossReportController::class, 'index'])->name('profit-loss.index')
            ->middleware('permission:reports.profit.view|reports.view');
        Route::get('/profit-loss/export', [\App\Http\Controllers\Admin\Reports\ProfitLossReportController::class, 'export'])
            ->name('profit-loss.export')
            ->middleware('permission:reports.profit.export|reports.export');

        // Laporan Kehadiran
        Route::get('/attendance', [\App\Http\Controllers\Admin\AttendanceReportController::class, 'reportIndex'])->name('attendance.index')
            ->middleware('permission:reports.attendance.view|reports.view');
        Route::get('/attendance/export', [\App\Http\Controllers\Admin\AttendanceReportController::class, 'exportReport'])
            ->name('attendance.export')
            ->middleware('permission:reports.attendance.export|reports.export');

        // Buku Hutang Supplier
        Route::get('/debts', [\App\Http\Controllers\Admin\DebtReportController::class, 'index'])->name('debts.index')
            ->middleware('permission:reports.debts.view|reports.view');
        Route::get('/debts/{supplier}', [\App\Http\Controllers\Admin\DebtReportController::class, 'show'])->name('debts.show')
            ->middleware('permission:reports.debts.view|reports.view');
    });

    // User Management
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class)
        ->only(['index'])
        ->middleware('permission:users.view');
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class)
        ->only(['create', 'store'])
        ->middleware('permission:users.create');
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:users.update');
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class)
        ->only(['destroy'])
        ->middleware('permission:users.delete');

    // Employee PIN Management
    Route::post('/users/{user}/set-pin', [\App\Http\Controllers\Admin\UserController::class, 'setAttendancePin'])
        ->name('users.set-pin')
        ->middleware('permission:users.pin.manage');

    // Void Tokens (One-Time PIN)
    Route::get('/void-tokens', [\App\Http\Controllers\Admin\VoidTokenController::class, 'index'])
        ->name('void-tokens.index')
        ->middleware('permission:void-tokens.view');
    Route::post('/void-tokens', [\App\Http\Controllers\Admin\VoidTokenController::class, 'store'])
        ->name('void-tokens.store')
        ->middleware('permission:void-tokens.create');

    // Role Permission Management (khusus superadmin)
    Route::prefix('permissions')->name('permissions.')->middleware(['role:superadmin', 'permission:permissions.manage'])->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::get('/{role}/edit', [PermissionController::class, 'edit'])->name('edit');
        Route::put('/{role}', [PermissionController::class, 'update'])->name('update');
        Route::post('/{role}/duplicate', [PermissionController::class, 'duplicate'])->name('duplicate');
    });
});

// POS Device Registration (Kasir/Admin/Kitchen)
Route::prefix('pos')->name('pos.')->middleware(['auth'])->group(function () {
    Route::get('/device/register', [PosDeviceController::class, 'showRegister'])
        ->name('device.register')
        ->middleware('role:kasir,admin,manager,kitchen');
    Route::post('/device/register', [PosDeviceController::class, 'register'])
        ->name('device.register.store')
        ->middleware('role:kasir,admin,manager,kitchen');
});

// POS (Kasir / Kitchen) Routes
Route::prefix('pos')->name('pos.')->middleware(['auth', 'pos.device'])->group(function () {
    Route::get('/dashboard', [POSDashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('role:kasir,admin');

    // Cash Session Management (Kasir/Admin)
    Route::get('/sessions/open', [\App\Http\Controllers\POS\CashSessionController::class, 'open'])
        ->name('sessions.open')
        ->middleware('role:kasir,admin');
    Route::post('/sessions', [CashSessionController::class, 'store'])
        ->name('sessions.store')
        ->middleware('role:kasir,admin');
    Route::get('/sessions/close', [CashSessionController::class, 'close'])
        ->name('sessions.close')
        ->middleware('role:kasir,admin');
    Route::post('/sessions/{cashSession}/close', [CashSessionController::class, 'closeStore'])
        ->name('sessions.close.store')
        ->middleware('role:kasir,admin');
    Route::get('/sessions/{cashSession}/print', [CashSessionController::class, 'print'])
        ->name('sessions.print')
        ->middleware('role:kasir,admin');

    // Sales Transactions (Kasir/Admin)
    Route::get('/sales', [SaleController::class, 'create'])
        ->name('sales.create')
        ->middleware('role:kasir,admin');
    Route::post('/sales', [SaleController::class, 'store'])
        ->name('sales.store')
        ->middleware('role:kasir,admin');

    Route::get('/sales/{sale}/print', [SaleController::class, 'print'])
        ->name('sales.print')
        ->middleware('role:kasir,admin');

    // History and Void
    Route::get('/sales/history', [SaleController::class, 'history'])
        ->name('sales.history')
        ->middleware('role:kasir,admin');
    Route::post('/sales/{sale}/void', [SaleController::class, 'void'])
        ->name('sales.void')
        ->middleware('role:kasir,admin');

    // Petty Cash POS (Kas Kecil Outlet, terpisah dari kas sales)
    Route::get('/petty-cash', [\App\Http\Controllers\POS\PettyCashController::class, 'index'])
        ->name('petty-cash.index')
        ->middleware('role:kasir,admin');
    Route::get('/petty-cash/create', [\App\Http\Controllers\POS\PettyCashController::class, 'create'])
        ->name('petty-cash.create')
        ->middleware('role:kasir,admin');
    Route::post('/petty-cash', [\App\Http\Controllers\POS\PettyCashController::class, 'store'])
        ->name('petty-cash.store')
        ->middleware('role:kasir,admin');
    Route::get('/petty-cash/{cashTransaction}/edit', [\App\Http\Controllers\POS\PettyCashController::class, 'edit'])
        ->name('petty-cash.edit')
        ->middleware('role:kasir,admin');
    Route::put('/petty-cash/{cashTransaction}', [\App\Http\Controllers\POS\PettyCashController::class, 'update'])
        ->name('petty-cash.update')
        ->middleware('role:kasir,admin');

    // Simple Kitchen Display (Admin/Kasir/Kitchen)
    Route::get('/kitchen', [KitchenController::class, 'index'])
        ->name('kitchen.index')
        ->middleware('role:admin,kasir,kitchen');
    Route::post('/kitchen/{sale}/status', [KitchenController::class, 'updateStatus'])
        ->name('kitchen.update-status')
        ->middleware('role:admin,kasir,kitchen');
    Route::get('/kitchen/{sale}/print', [KitchenController::class, 'print'])
        ->name('kitchen.print')
        ->middleware('role:admin,kasir,kitchen');

    // Attendance (Kasir/Admin)
    Route::prefix('attendance')->name('attendance.')->middleware('role:kasir,admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\POS\AttendanceController::class, 'index'])->name('index');
        Route::post('/clock-in', [\App\Http\Controllers\POS\AttendanceController::class, 'clockIn'])->name('clock-in');
        Route::post('/clock-out', [\App\Http\Controllers\POS\AttendanceController::class, 'clockOut'])->name('clock-out');
    });
});
