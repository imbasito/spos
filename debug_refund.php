<?php
// debug_refund.php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductReturn;
use Illuminate\Support\Facades\View;

try {
    echo "--- Starting Refund Receipt Diagnostic ---\n";

    // 1. Get Latest Refund
    $return = ProductReturn::latest()->first();
    
    if (!$return) {
        echo "ERROR: No refunds found in database to test.\n";
        exit(1);
    }
    
    echo "Found Refund ID: " . $return->id . " (Ref: " . $return->return_number . ")\n";

    // 2. Load Relationships (Simulate Controller)
    $return->load([
        'order.customer',
        'order.products.product',
        'order.returns',
        'items.product',
        'items.orderProduct',
        'processedBy'
    ]);
    
    echo "Relationships Loaded.\n";

    // 3. Calculate Logic (Simulate Controller)
    $orderTotalRefunded = $return->order->returns->sum('total_refund');
    $return->order_total_refunded = $orderTotalRefunded;
    $return->original_order_total = $return->order->sub_total - $return->order->discount;
    
    echo "Calculations Complete.\n";

    // 4. Render View
    echo "Attempting to render view 'backend.refunds.receipt'...\n";
    $maxWidth = '300px'; 
    $view = View::make('backend.refunds.receipt', compact('return', 'maxWidth'))->render();
    
    echo "SUCCESS: View rendered cleanly.\n";
    echo "View Size: " . strlen($view) . " bytes.\n";

} catch (\Exception $e) {
    echo "\nCRITICAL ERROR in Refund Logic/View:\n";
    echo $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " : " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
