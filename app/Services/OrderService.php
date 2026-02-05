<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\PosCart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OrderService
{
    /**
     * Create a new order from cart items.
     */
    public function createOrder(array $data, $userId)
    {
        return DB::transaction(function () use ($data, $userId) {
            $items = $data['items'] ?? null;
            
            if ($items) {
                // Map frontend items to a consistent format
                $carts = collect($items)->map(function($item) {
                    $product = Product::find($item['id']);
                    if (!$product) throw new \Exception("Product not found");
                    
                    return (object)[
                        'product' => $product,
                        'quantity' => $item['qty'],
                        'price_override' => $item['price'],
                        'row_total_override' => $item['row_total']
                    ];
                });
            } else {
                $carts = PosCart::with('product')
                    ->where('user_id', $userId)
                    ->get();
            }

            if ($carts->isEmpty()) {
                throw new \Exception('Cart is empty');
            }

            // 1. Pre-Transaction Stock Check
            foreach ($carts as $cart) {
                if (!$cart->product || !$cart->product->status) {
                    throw new \Exception("Product '" . ($cart->product->name ?? 'Unknown') . "' is no longer available");
                }
                if ($cart->product->quantity < $cart->quantity) {
                    throw new \Exception("Insufficient stock for '" . $cart->product->name . "'. Available: " . $cart->product->quantity);
                }
            }

            // 2. Initial Order Creation
            $order = Order::create([
                'customer_id' => $data['customer_id'],
                'user_id' => $userId,
            ]);

            $totalAmountOrder = 0;
            $orderDiscount = $data['order_discount'] ?? 0;

            // 3. Process Items
            foreach ($carts as $cart) {
                // Use overridden price if provided, otherwise product default
                $price = $cart->price_override ?? $cart->product->price;
                $mainTotal = $price * $cart->quantity;
                
                // If we have row_total_override (Frontend net), use it. 
                // Otherwise use product's default discounted price.
                $totalAfterDiscount = isset($cart->row_total_override) 
                    ? round($cart->row_total_override, 2)
                    : round($cart->product->discounted_price * $cart->quantity, 2);
                
                $discount = max(0, $mainTotal - $totalAfterDiscount);
                $totalAmountOrder += $totalAfterDiscount;

                $order->products()->create([
                    'product_id' => $cart->product->id,
                    'quantity' => $cart->quantity,
                    'price' => round($price, 2),
                    'purchase_price' => $cart->product->purchase_price,
                    'sub_total' => round((float)$mainTotal, 2),
                    'discount' => round((float)$discount, 2),
                    'total' => round((float)$totalAfterDiscount, 2),
                ]);

                // 4. Deduct Stock
                $affected = DB::table('products')
                    ->where('id', $cart->product->id)
                    ->where('quantity', '>=', $cart->quantity)
                    ->decrement('quantity', $cart->quantity);

                if ($affected === 0) {
                    throw new \Exception("Insufficient stock for '" . $cart->product->name . "'. Available: " . Product::find($cart->product->id)->quantity);
                }
            }

            // 5. Finalize Totals
            // Note: If the frontend already included all line discounts in 'totalSavings', 
            // and we are using those line discounts in $totalAmountOrder, 
            // then $orderDiscount (extra manual) should be handled carefully.
            
            // Actually, in the frontend we calculated finalDiscount = displayDiscount (Total savings).
            // But displayDiscount includes both line-item savings AND manual extras.
            
            // To be perfectly accurate:
            // total = $totalAmountOrder (Total of line net amounts) - extraDiscount?
            // Wait, if frontend passes finalDiscount, it's TotalSavingsVisible = DisplayDiscount + Rounding.
            // This is complex. Let's simplify:
            // Total Payable = Frontend Sum(Line Amounts) - EXTRA Manual Discount - Rounding.
            
            // If the frontend passes totalSavings as order_discount, we need to know Gross.
            // Gross = $totalGrossOrder (Sum of Price * Qty)
            // Final Total = Gross - TotalSavings
            
            // 5. Finalize Totals (Simplified Net-Sum Model)
            // sub_total: Sum of (DiscountedPrice * Qty) - the actual prices from the cart
            // discount: Strictly the manual/fractional discount from the footer
            // total: sub_total - discount
            
            $totalNetLineItems = $order->products()->sum('total'); // Sum of (DiscountedPrice * Qty)
            
            $finalDiscount = (float)$orderDiscount;
            
            // Calculate Total (before tax consideration)
            $taxableAmount = max(0, round($totalNetLineItems - $finalDiscount, 2));
            
            // 5a. Tax Calculation (Snapshot at time of sale)
            // Tax is INCLUSIVE in Pakistan (prices already include GST)
            // We calculate the GST component for reporting purposes
            $taxGstEnabled = readConfig('tax_gst_enabled') == 1;
            $taxRate = $taxGstEnabled ? floatval(readConfig('tax_gst_rate') ?: 17) : 0;
            
            // For inclusive tax: taxAmount = taxableAmount * rate / (100 + rate)
            // This extracts the tax already embedded in the price
            $taxAmount = $taxGstEnabled 
                ? round(($taxableAmount * $taxRate) / (100 + $taxRate), 2) 
                : 0;
            
            // Total remains the same (tax is inclusive, not added)
            $total = $taxableAmount;
            
            $paid = $data['paid'] ?? 0;
            
            // Due Calculation with Safeguard
            $due = round($total - $paid, 2);
            if (abs($due) < 0.01) $due = 0; 
            
            // Optimization: If derived due is suspiciously close to manual discount, force 0.
            // But strict math above should suffice.

            $order->update([
                'sub_total' => round((float)$totalNetLineItems, 2),
                'discount' => round((float)$finalDiscount, 2),
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'paid' => round((float)$paid, 2),
                'total' => round((float)$total, 2),
                'due' => $due,
                'status' => $due <= 0,
            ]);

            // 6. Create Transaction Log
            if ($paid > 0) {
                $order->transactions()->create([
                    'amount' => $paid,
                    'customer_id' => $order->customer_id,
                    'user_id' => $userId,
                    'paid_by' => $data['payment_method'] ?? 'cash',
                    'transaction_id' => $data['transaction_id'] ?? null,
                ]);
            }

            // 7. Cleanup
            PosCart::where('user_id', $userId)->delete();
            Cache::flush();

            return $order;
        });
    }

    /**
     * Process a due collection for an existing order.
     */
    public function collectDue(Order $order, array $data, $userId)
    {
        return DB::transaction(function () use ($order, $data, $userId) {
            $amount = $data['amount'];
            
            $newDue = $order->due - $amount;
            $newPaid = $order->paid + $amount;

            $order->update([
                'due' => round((float)$newDue, 2),
                'paid' => round((float)$newPaid, 2),
                'status' => round((float)$newDue, 2) <= 0,
            ]);

            $transaction = $order->transactions()->create([
                'amount' => $amount,
                'customer_id' => $order->customer_id,
                'user_id' => $userId,
                'paid_by' => $data['payment_method'] ?? 'cash',
                'transaction_id' => $data['transaction_id'] ?? null,
            ]);

            return $transaction;
        });
    }
}
