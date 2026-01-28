<?php

namespace App\Services;

use App\Models\Order;
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
            $carts = PosCart::with('product')
                ->where('user_id', $userId)
                ->get();

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

            // 3. Process Cart Items
            foreach ($carts as $cart) {
                $mainTotal = $cart->product->price * $cart->quantity;
                $totalAfterDiscount = $cart->product->discounted_price * $cart->quantity;
                $discount = $mainTotal - $totalAfterDiscount;
                $totalAmountOrder += $totalAfterDiscount;

                $order->products()->create([
                    'product_id' => $cart->product->id,
                    'quantity' => $cart->quantity,
                    'price' => $cart->product->price,
                    'purchase_price' => $cart->product->purchase_price,
                    'sub_total' => round((float)$mainTotal, 2),
                    'discount' => round((float)$discount, 2),
                    'total' => round((float)$totalAfterDiscount, 2),
                ]);

                // 4. Deduct Stock (Atomic & Safe)
                // Update only if we have enough stock. This prevents race conditions.
                $affected = DB::table('products')
                    ->where('id', $cart->product->id)
                    ->where('quantity', '>=', $cart->quantity)
                    ->decrement('quantity', $cart->quantity);

                if ($affected === 0) {
                    // Check if it's because of stock or product existence
                    $freshProduct = Product::find($cart->product->id);
                    if (!$freshProduct) {
                         throw new \Exception("Product '" . ($cart->product->name ?? 'Unknown') . "' was removed during checkout.");
                    }
                    throw new \Exception("Insufficient stock for '" . $freshProduct->name . "'. Available: " . $freshProduct->quantity);
                }
            }

            // 5. Finalize Totals
            $total = $totalAmountOrder - $orderDiscount;
            $paid = $data['paid'] ?? 0;
            $due = $total - $paid;

            $order->update([
                'sub_total' => round((float)$totalAmountOrder, 2),
                'discount' => round((float)$orderDiscount, 2),
                'paid' => round((float)$paid, 2),
                'total' => round((float)$total, 2),
                'due' => round((float)$due, 2),
                'status' => round((float)$due, 2) <= 0,
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
