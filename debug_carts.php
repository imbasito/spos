<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PosCart;

$carts = PosCart::all();
echo "Carts in DB:\n";
foreach($carts as $cart) {
    echo "ID: {$cart->id}, Product ID: {$cart->product_id}, Qty: {$cart->quantity}, User ID: {$cart->user_id}\n";
}
