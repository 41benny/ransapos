<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OutletController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\POS\DashboardController as POSDashboardController;
use App\Http\Controllers\POS\SaleController;

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
    Route::resource('products', ProductController::class);
    Route::resource('outlets', OutletController::class)->only(['index']);
    Route::resource('suppliers', SupplierController::class)->only(['index']);
    
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
    
    // COA (Chart of Accounts)
    Route::resource('coa-accounts', \App\Http\Controllers\Admin\CoaAccountController::class);
    
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
});

// POS (Kasir) Routes
Route::prefix('pos')->name('pos.')->middleware(['auth', 'role:kasir,admin'])->group(function () {
    Route::get('/dashboard', [POSDashboardController::class, 'index'])->name('dashboard');
    
    // Cash Session Management
    Route::get('/sessions/open', [\App\Http\Controllers\POS\CashSessionController::class, 'open'])->name('sessions.open');
    Route::post('/sessions', [\App\Http\Controllers\POS\CashSessionController::class, 'store'])->name('sessions.store');
    Route::get('/sessions/close', [\App\Http\Controllers\POS\CashSessionController::class, 'close'])->name('sessions.close');
    Route::post('/sessions/{cashSession}/close', [\App\Http\Controllers\POS\CashSessionController::class, 'closeStore'])->name('sessions.close.store');
    
    // Sales Transactions
    Route::get('/sales', [SaleController::class, 'create'])->name('sales.create');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
});
