<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\User;
use App\Models\Outlet;
use App\Services\SaleService;
use App\Models\Sale;
use App\Models\StockMutation;

try {
    // 1. Get a random user & outlet
    $user = User::first();
    $cashSession = App\Models\CashSession::latest()->first();
    $outlet = Outlet::find($cashSession->outlet_id);
    
    // 2. Mock a sale item with Bom ('Tori miso ramen' is known to use 'Siaw May Ayam')
    $toriRamen = Product::where('name', 'like', '%tori miso ramen%')->first();
    
    if (!$toriRamen) {
        echo "Could not find 'tori miso ramen' product.\n";
        exit;
    }

    echo "Found Product: {$toriRamen->name} (ID: {$toriRamen->id})\n";

    // Request data similar to what SaleController would receive
    $saleData = [
        'outlet_id' => $outlet->id,
        'customer_name' => 'Tester',
        'cash_session_id' => 55,
        'items' => [
            [
                'product_id' => $toriRamen->id,
                'quantity' => 1,
                'unit_price' => $toriRamen->price ?? 50000,
            ]
        ],
        'subtotal' => $toriRamen->price ?? 50000,
        'tax_amount' => 0,
        'total_amount' => $toriRamen->price ?? 50000,
        'payment_method' => 'cash',
        'payment_method_id' => App\Models\PaymentMethod::first()->id ?? null,
        'amount_paid' => $toriRamen->price ?? 50000,
    ];

    // Mock authentication
    Auth::login($user);

    // Call service to create sale
    $saleService = app(SaleService::class);
    $sale = $saleService->createSale($saleData, clone $user);

    echo "Sale Created successfully! ID: {$sale->id}\n";
    echo "Invoice Number: {$sale->invoice_number}\n\n";
    
    // Let's check stock mutations
    $mutations = StockMutation::where('reference_type', 'sale')
        ->where('reference_id', $sale->id)
        ->get();
        
    echo "Stock Mutations:\n";
    foreach ($mutations as $m) {
        $p = Product::find($m->product_id);
        echo "- Product: {$p->name}, Qty: {$m->quantity}, Notes: {$m->notes}\n";
    }

} catch (\Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n";
}
