<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderTransaction;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\ReturnItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class RefundController extends Controller
{
    /**
     * Display list of all refunds
     */
    public function index(Request $request)
    {
        abort_if(!auth()->user()->can('refund_view'), 403);

        if ($request->ajax()) {
            $returns = ProductReturn::with(['order', 'processedBy'])->latest('id'); // Simplified: Eloquent handles table resolution
            return DataTables::of($returns)
                ->addIndexColumn()
                ->addColumn('return_number', fn($data) => '<span class="text-maroon font-weight-bold">' . $data->return_number . '</span>')
                ->addColumn('order_id', fn($data) => '<strong>#' . $data->order_id . '</strong>')
                ->addColumn('total_refund', fn($data) => '<span class="text-danger font-weight-bold">' . number_format($data->total_refund, 2) . '</span>')
                ->addColumn('processed_by', fn($data) => '<span class="badge badge-light shadow-sm px-2">' . (optional($data->processedBy)->name ?? 'N/A') . '</span>')
                ->addColumn('created_at', fn($data) => '<span class="text-muted">' . $data->created_at->format('d M, Y') . '</span><br><small>' . $data->created_at->format('h:i A') . '</small>')
                ->addColumn('action', function ($data) {
                    $url = route('backend.admin.refunds.receipt', $data->id);
                    return '<button type="button" onclick="openRefundReceiptV3(\'' . $url . '\')" class="btn btn-sm btn-info px-3 font-weight-bold shadow-sm">
                        <i class="fas fa-receipt mr-1"></i> View Receipt
                    </button>';
                })
                ->rawColumns(['return_number', 'order_id', 'total_refund', 'processed_by', 'created_at', 'action'])
                ->toJson();
        }

        return view('backend.refunds.index_v3');
    }

    /**
     * Show refund form for an order (AJAX modal)
     */
    public function create($orderId)
    {
        abort_if(!auth()->user()->can('refund_create'), 403);

        $order = Order::with(['products.product', 'customer'])->findOrFail($orderId);
        
        // Calculate already returned quantities for each order product
        foreach($order->products as $item) {
            $item->returned_qty = ReturnItem::where('order_product_id', $item->id)->sum('quantity');
            $item->available_qty = $item->quantity - $item->returned_qty;
        }

        // Check if order already has a full refund
        if ($order->is_returned || $order->products->sum('available_qty') <= 0) {
            return response()->json(['error' => 'This order has already been fully refunded.'], 400);
        }

        return view('backend.refunds.create', compact('order'));
    }

    /**
     * Process refund
     */
    public function store(Request $request)
    {
        abort_if(!auth()->user()->can('refund_create'), 403);

        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'items' => 'required|array|min:1',
            'items.*.order_product_id' => 'required|exists:order_products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $order = Order::findOrFail($request->order_id);
            $totalRefund = 0;

            // Create return record
            $return = ProductReturn::create([
                'order_id' => $order->id,
                'return_number' => ProductReturn::generateReturnNumber(),
                'processed_by' => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                $orderProduct = OrderProduct::findOrFail($item['order_product_id']);
                
                // Calculate already returned quantity to verify availability
                $alreadyReturned = ReturnItem::where('order_product_id', $orderProduct->id)->sum('quantity');
                $availableQty = $orderProduct->quantity - $alreadyReturned;
                
                $quantity = min($item['quantity'], $availableQty); 

                if ($quantity <= 0) continue;

                // Calculate refund amount for this item (exact proportional refund based on original line total)
                $pricePerUnit = $orderProduct->total / $orderProduct->quantity;
                $lineRefundAmount = $pricePerUnit * $quantity;
                
                // ADJUST FOR GLOBAL ORDER DISCOUNT:
                // If there was an order-level discount, we must refund proportionally to what was actually paid.
                // Formula: Adjusted Refund = LineRefund * (OrderTotal / OrderSubTotal)
                $adjustmentFactor = ($order->sub_total > 0) ? ($order->total / $order->sub_total) : 1;
                $refundAmount = round($lineRefundAmount * $adjustmentFactor, 2);
                
                $totalRefund += $refundAmount;

                // Create return item
                ReturnItem::create([
                    'return_id' => $return->id,
                    'order_product_id' => $orderProduct->id,
                    'product_id' => $orderProduct->product_id,
                    'quantity' => $quantity,
                    'refund_amount' => $refundAmount,
                ]);

                // Update product stock (add back to inventory) - ATOMIC
                $product = Product::find($orderProduct->product_id);
                if ($product) {
                    $product->increment('quantity', $quantity);
                    $product->increment('total_returned', $quantity);
                }
            }

            // 1. Update Order Total
            $oldTotal = $order->total;
            $newTotal = max(0, $oldTotal - $totalRefund);
            $order->total = $newTotal;

            // 2. Adjust Balance (The "Professional" Due Logic)
            // If the customer has a Due balance, we should clear the debt FIRST 
            // before giving them any actual cash back.
            $cashBack = 0;
            if ($order->due > 0) {
                if ($totalRefund <= $order->due) {
                    // Refund is less than or equal to debt: Just reduce the debt
                    $order->due -= $totalRefund;
                    $cashBack = 0; // No cash leaves the drawer
                } else {
                    // Refund is more than debt: Clear debt and return remaining as cash
                    $cashBack = $totalRefund - $order->due;
                    $order->due = 0;
                }
            } else {
                // No debt: Full refund amount is cash back
                $cashBack = $totalRefund;
            }
            
            // 3. Update Order Paid (Reflection of actual money kept)
            $order->paid = max(0, $newTotal - $order->due);
            $order->status = $order->due <= 0;
            $order->save();

            // Note: We are no longer creating a negative OrderTransaction to avoid SQL errors 
            // and keep the system simple as per user request. The Sales List will show the adjusted totals.

            // Update return total
            $return->update(['total_refund' => $totalRefund]);

            // Mark order as returned if cumulative returns cover the full order total
            $cumulativeItemsReturned = ReturnItem::whereHas('productReturn', function($q) use ($order) {
                $q->where('order_id', $order->id);
            })->sum('quantity');
            
            $originalTotalItems = $order->products()->sum('quantity');
            
            if ($cumulativeItemsReturned >= $originalTotalItems) {
                $order->update(['is_returned' => true]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully!',
                'return_id' => $return->id,
                'total_refund' => $totalRefund,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to process refund: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show return receipt
     */
    public function receipt($id)
    {
        $return = ProductReturn::with([
            'order.customer',
            'order.products.product',
            'order.returns', // Load all returns for this order
            'items.product',
            'items.orderProduct',
            'processedBy'
        ])->findOrFail($id);

        // Calculate total refunded for this order across all return instances
        $orderTotalRefunded = $return->order->returns->sum('total_refund');
        $return->order_total_refunded = $orderTotalRefunded;
        
        // Calculate absolute original total (before any refunds)
        $return->original_order_total = $return->order->sub_total - $return->order->discount;

        $maxWidth = readConfig('receiptMaxwidth') ?? '300px';
        return view('backend.refunds.receipt', compact('return', 'maxWidth'));
    }

    public function refundDetails($id)
    {
        try {
            $return = ProductReturn::with([
                'order.customer',
                'order.products.product',
                'items.product',
                'processedBy'
            ])->findOrFail($id);

            $order = $return->order;
            // Sum all refunds for this order
            $totalRefundedSoFar = \App\Models\ProductReturn::where('order_id', $order->id)->sum('total_refund');

            $data = [
                'success' => true,
                'data' => [
                    'id' => $return->return_number,
                    'order_id' => $order->order_number ?? $order->id,
                    'date' => $return->created_at->format('d M Y h:i A'),
                    'staff' => $return->processedBy->name ?? 'Admin',
                    'customer' => [
                        'name' => $order->customer->name ?? 'Walk-in',
                        'phone' => $order->customer->phone ?? ''
                    ],
                    'items' => $return->items->map(function($item) {
                        $qty = (float) $item->quantity;
                        $refundAmt = (float) $item->refund_amount;
                        $unitPrice = ($qty > 0.00001) ? ($refundAmt / $qty) : 0.00;

                        return [
                            'name' => $item->product->name ?? 'Product',
                            'qty' => $item->quantity,
                            'price' => number_format($unitPrice, 2),
                            'total' => number_format($refundAmt, 2)
                        ];
                    }),
                    'total' => number_format($return->total_refund, 2),
                    'order_summary' => [
                        'original_total' => number_format($order->sub_total - $order->discount, 2),
                        'total_refunded' => number_format($totalRefundedSoFar, 2),
                        'adjusted_total' => number_format($order->total, 2)
                    ],
                    'config' => [
                        'site_name' => readConfig('site_name'),
                        'address' => readConfig('contact_address'),
                        'phone' => readConfig('contact_phone'),
                        'email' => readConfig('contact_email'),
                        'logo_url' => assetImage(readConfig('site_logo')),
                        'show_logo' => readConfig('is_show_logo_invoice'),
                        'show_address' => readConfig('is_show_address_invoice'),
                        'show_phone' => readConfig('is_show_phone_invoice'),
                        'show_email' => readConfig('is_show_email_invoice')
                    ]
                ]
            ];

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
