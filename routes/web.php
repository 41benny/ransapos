<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OutletController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\PosDeviceController as AdminPosDeviceController;
use App\Http\Controllers\POS\DashboardController as POSDashboardController;
use App\Http\Controllers\POS\SaleController;
use App\Http\Controllers\POS\KitchenController;
use App\Http\Controllers\POS\CashSessionController;
use App\Http\Controllers\POS\DeviceController as PosDeviceController;

// Redirect root ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Back Office (Admin) Routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,manager'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Master Data
    Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
    Route::get('products/create-bundle', [ProductController::class, 'createBundle'])->name('products.create-bundle');
    Route::resource('products', ProductController::class);
    Route::resource('outlets', OutletController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::resource('suppliers', SupplierController::class)->only(['index', 'create', 'store']);

    // POS Device Management
    Route::get('pos-devices', [AdminPosDeviceController::class, 'index'])->name('pos-devices.index');
    Route::post('pos-devices/pairing', [AdminPosDeviceController::class, 'storePairing'])->name('pos-devices.pairing');
    Route::post('pos-devices/enforce', [AdminPosDeviceController::class, 'updateEnforcement'])->name('pos-devices.enforce');
    Route::post('pos-devices/{posDevice}/revoke', [AdminPosDeviceController::class, 'revoke'])->name('pos-devices.revoke');

    // Purchases
    Route::resource('purchases', \App\Http\Controllers\Admin\PurchaseController::class);
    Route::post('purchases/{purchase}/receive', [\App\Http\Controllers\Admin\PurchaseController::class, 'receive'])->name('purchases.receive');
    Route::post('purchases/{purchase}/cancel', [\App\Http\Controllers\Admin\PurchaseController::class, 'cancel'])->name('purchases.cancel');
    Route::get('purchases/{purchase}/payment', [\App\Http\Controllers\Admin\PurchaseController::class, 'showPaymentForm'])->name('purchases.payment');
    Route::post('purchases/{purchase}/payment', [\App\Http\Controllers\Admin\PurchaseController::class, 'storePayment'])->name('purchases.payment.store');

    // Cash Accounts & Transactions
    Route::resource('cash-accounts', \App\Http\Controllers\Admin\CashAccountController::class);
    Route::get('cash-transactions', [\App\Http\Controllers\Admin\CashAccountController::class, 'transactions'])->name('cash-transactions.index');
    Route::get('cash-transactions/create', [\App\Http\Controllers\Admin\CashAccountController::class, 'createTransaction'])->name('cash-transactions.create');
    Route::post('cash-transactions', [\App\Http\Controllers\Admin\CashAccountController::class, 'storeTransaction'])->name('cash-transactions.store');
    Route::get('cash-accounts/{cashAccount}/mutation-report', [\App\Http\Controllers\Admin\CashAccountController::class, 'mutationReport'])->name('cash-accounts.mutation-report');

    // Bank Transfers
    Route::resource('bank-transfers', \App\Http\Controllers\Admin\BankTransferController::class)->only(['index', 'create', 'store', 'show']);
    // COA (Chart of Accounts)
    Route::resource('coa-accounts', \App\Http\Controllers\Admin\CoaAccountController::class);

    // Bill of Materials (BOM) - sederhana
    Route::resource('boms', \App\Http\Controllers\Admin\BomController::class);

    // Inventory & Stock Management
    Route::get('/stocks', [\App\Http\Controllers\Admin\StockController::class, 'index'])->name('stocks.index');
    Route::get('/stocks/mutations', [\App\Http\Controllers\Admin\StockController::class, 'mutations'])->name('stocks.mutations');
    Route::get('/stocks/adjustment', [\App\Http\Controllers\Admin\StockController::class, 'adjustment'])->name('stocks.adjustment');
    Route::post('/stocks/adjustment', [\App\Http\Controllers\Admin\StockController::class, 'storeAdjustment'])->name('stocks.adjustment.store');
    Route::get('/stocks/card', [\App\Http\Controllers\Admin\StockController::class, 'stockCard'])->name('stocks.card');
    Route::get('/stocks/current', [\App\Http\Controllers\Admin\StockController::class, 'getCurrentStock'])->name('stocks.current');
    Route::get('/stocks/export', [\App\Http\Controllers\Admin\StockController::class, 'export'])->name('stocks.export');

    // Stock Transfers
    Route::resource('stock-transfers', \App\Http\Controllers\Admin\StockTransferController::class);
    Route::post('stock-transfers/{stockTransfer}/send', [\App\Http\Controllers\Admin\StockTransferController::class, 'send'])->name('stock-transfers.send');
    Route::get('stock-transfers/{stockTransfer}/receive-form', [\App\Http\Controllers\Admin\StockTransferController::class, 'receiveForm'])->name('stock-transfers.receive-form');
    Route::post('stock-transfers/{stockTransfer}/receive', [\App\Http\Controllers\Admin\StockTransferController::class, 'receive'])->name('stock-transfers.receive');
    Route::post('stock-transfers/{stockTransfer}/cancel', [\App\Http\Controllers\Admin\StockTransferController::class, 'cancel'])->name('stock-transfers.cancel');
    Route::get('stock-transfers/available-stock', [\App\Http\Controllers\Admin\StockTransferController::class, 'getAvailableStock'])->name('stock-transfers.available-stock');

    // Expense Management
    Route::resource('expense-categories', \App\Http\Controllers\Admin\ExpenseCategoryController::class);
    Route::resource('expenses', \App\Http\Controllers\Admin\ExpenseController::class);
    Route::post('expenses/{expense}/approve', [\App\Http\Controllers\Admin\ExpenseController::class, 'approve'])->name('expenses.approve');
    Route::post('expenses/{expense}/reject', [\App\Http\Controllers\Admin\ExpenseController::class, 'reject'])->name('expenses.reject');
    Route::post('expenses/{expense}/pay', [\App\Http\Controllers\Admin\ExpenseController::class, 'pay'])->name('expenses.pay');
    Route::get('expenses-reports', [\App\Http\Controllers\Admin\ExpenseController::class, 'reports'])->name('expenses.reports');

    // Customer Management (CRM)
    Route::resource('customers', \App\Http\Controllers\Admin\CustomerController::class);
    Route::post('customers/{customer}/add-points', [\App\Http\Controllers\Admin\CustomerController::class, 'addPoints'])->name('customers.add-points');
    Route::post('customers/{customer}/redeem-points', [\App\Http\Controllers\Admin\CustomerController::class, 'redeemPoints'])->name('customers.redeem-points');
    Route::get('customers-reports', [\App\Http\Controllers\Admin\CustomerController::class, 'reports'])->name('customers.reports');

    // Cash Sessions (History)
    Route::get('/cash-sessions', [\App\Http\Controllers\Admin\CashSessionController::class, 'index'])->name('cash-sessions.index');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/sales', [\App\Http\Controllers\Admin\Reports\SalesReportController::class, 'index'])->name('sales.index');
        Route::get('/sales-products', [\App\Http\Controllers\Admin\Reports\SalesReportController::class, 'products'])->name('sales.products');
        Route::get('/shifts', [\App\Http\Controllers\Admin\Reports\ShiftReportController::class, 'index'])->name('shifts.index');
        Route::get('/shifts/{cashSession}', [\App\Http\Controllers\Admin\Reports\ShiftReportController::class, 'show'])->name('shifts.show');
        Route::get('/profit-loss', [\App\Http\Controllers\Admin\Reports\ProfitLossReportController::class, 'index'])->name('profit-loss.index');
    });

    // Attendance Monitoring & Reports
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\AttendanceReportController::class, 'dashboard'])->name('dashboard');
        Route::get('/outlet/{outlet}', [\App\Http\Controllers\Admin\AttendanceReportController::class, 'outletReport'])->name('outlet');
        Route::get('/export', [\App\Http\Controllers\Admin\AttendanceReportController::class, 'exportReport'])->name('export');
        Route::post('/anomalies/{id}/dismiss', [\App\Http\Controllers\Admin\AttendanceReportController::class, 'dismissAnomaly'])->name('anomalies.dismiss');
    });

    // User Management
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->except(['show']);

    // Employee PIN Management
    Route::post('/users/{user}/set-pin', [\App\Http\Controllers\Admin\UserController::class, 'setAttendancePin'])->name('users.set-pin');
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

    // Demo Tema Latte
    Route::get('/latte-demo', function () {
        return view('pos.latte-demo');
    })->name('latte-demo');

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
