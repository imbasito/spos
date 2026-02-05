<?php

namespace App\Http\Controllers\Backend\Pos;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Models\PosCart;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;

use Yajra\DataTables\DataTables;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $orders = Order::with('customer', 'products.product')->orderBy('id', 'desc'); 


            return DataTables::of($orders)
                ->addIndexColumn()
                ->filter(function ($query) {
                    if (request()->has('search.value')) {
                        $keyword = request('search.value');
                        if (!empty($keyword)) {
                            $query->where(function ($q) use ($keyword) {
                                $q->where('id', 'like', "%{$keyword}%")
                                  ->orWhere('total', 'like', "%{$keyword}%")
                                  ->orWhere('status', 'like', "%{$keyword}%")
                                  ->orWhereHas('customer', function($sub) use ($keyword) {
                                      $sub->where('name', 'like', "%{$keyword}%");
                                  });
                            });
                        }
                    }
                }, true)

                ->addColumn('saleId', fn($data) => "#" . $data->id)
                ->addColumn('customer', fn($data) => $data->customer->name ?? '-')
                ->addColumn('item', fn($data) => $data->total_item)
                ->addColumn('sub_total', fn($data) => number_format($data->sub_total, 2, '.', ','))
                ->addColumn('discount', fn($data) => number_format($data->discount, 2, '.', ','))
                ->addColumn('total', fn($data) => number_format($data->total, 2, '.', ','))
                ->addColumn('paid', fn($data) => number_format($data->paid, 2, '.', ','))
                ->addColumn('due', fn($data) => number_format($data->due, 2, '.', ','))
                ->addColumn('status', fn($data) => $data->status
                    ? '<span class="badge bg-primary">Paid</span>'
                    : '<span class="badge bg-danger">Due</span>')
                ->addColumn('action', function ($data) {
                    $buttons = '';

                    $buttons .= '<button type="button" class="btn btn-success btn-sm" onclick="openPosInvoice(' . $data->id . ')"><i class="fas fa-print"></i> POS Receipt</button>';
                    
                    $buttons .= '<a class="btn btn-secondary btn-sm" href="' . route('backend.admin.orders.invoice', $data->id) . '"><i class="fas fa-file-pdf"></i> Invoice</a>';
                    if (!$data->status) {
                        $buttons .= '<a class="btn btn-warning btn-sm" href="' . route('backend.admin.due.collection', $data->id) . '"><i class="fas fa-receipt"></i> Due Collection</a>';
                    }
                    $buttons .= '<a class="btn btn-primary btn-sm" href="' . route('backend.admin.orders.transactions', $data->id) . '"><i class="fas fa-exchange-alt"></i> Transactions</a>';
                    
                    // Refund button - only show if not already fully refunded
                    if (!$data->is_returned) {
                        $buttons .= '<a class="btn btn-danger btn-sm" href="#" onclick="openRefundModal(' . $data->id . ')"><i class="fas fa-undo"></i> Refund</a>';
                    } else {
                        $buttons .= '<span class="btn btn-outline-danger btn-sm disabled"><i class="fas fa-check"></i> Refunded</span>';
                    }
                    
                    return $buttons;
                })
                ->rawColumns(['saleId', 'customer', 'item', 'sub_total', 'discount', 'total', 'paid', 'due', 'status', 'action'])
                ->toJson();
        }
        return view('backend.orders.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id|integer',
            'order_discount' => 'nullable|numeric|min:0',
            'paid' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,card,online',
            'transaction_id' => 'nullable|string|max:255',
            'items' => 'nullable|array',
            'items.*.id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.row_total' => 'required|numeric|min:0',
        ]);

        try {
            $order = $this->orderService->createOrder($data, auth()->id());
            
            // Clear product cache to ensure stock quantities refresh immediately in POS
            for ($i = 1; $i <= 10; $i++) {
                \Illuminate\Support\Facades\Cache::forget("pos_products_page_{$i}");
            }

            // Clear journal on successful sale
            $path = storage_path('app/current_sale.journal');
            if (\Illuminate\Support\Facades\File::exists($path)) {
                \Illuminate\Support\Facades\File::delete($path);
            }
            
            // Submit to FBR if integration is enabled
            $fbrResult = null;
            try {
                $fbrService = new \App\Services\FbrService();
                if ($fbrService->isConfigured()) {
                    $orderData = $this->prepareFbrOrderData($order);
                    $fbrResult = $fbrService->submitInvoice($orderData);
                    
                    // Update order with FBR invoice ID if received
                    if ($fbrResult['success'] && !empty($fbrResult['fbr_invoice_id'])) {
                        $order->fbr_invoice_id = $fbrResult['fbr_invoice_id'];
                        $order->fbr_synced_at = now();
                        $order->save();
                    }
                }
            } catch (\Exception $fbrError) {
                // Log but don't fail the order
                \Illuminate\Support\Facades\Log::warning('FBR submission error', ['error' => $fbrError->getMessage()]);
            }
            
            return response()->json([
                'message' => 'Order completed successfully', 
                'order' => $order,
                'fbr' => $fbrResult
            ], 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function invoice($id)
    {
        $order = Order::with(['customer', 'products.product'])->findOrFail($id);
        return view('backend.orders.print-invoice', compact('order'));
    }
    public function collection(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'amount' => 'required|numeric|min:1',
                'payment_method' => 'nullable|string|in:cash,card,online',
                'transaction_id' => 'nullable|string|max:255',
            ]);

            try {
                $transaction = $this->orderService->collectDue($order, $data, auth()->id());
                return to_route('backend.admin.collectionInvoice', $transaction->id);
            } catch (\Exception $e) {
                return back()->withErrors(['amount' => $e->getMessage()]);
            }
        }
        return view('backend.orders.collection.create', compact('order'));
    }


    //collection invoice by order_transaction id
    public function collectionInvoice($id)
    {
        $transaction = OrderTransaction::findOrFail($id);
        $collection_amount = $transaction->amount;
        $order = $transaction->order;
        return view('backend.orders.collection.invoice', compact('order', 'collection_amount', 'transaction'));
    }
    //transactions by order id
    public function transactions($id)
    {
        $order = Order::with('transactions')->findOrFail($id);
        return view('backend.orders.collection.index', compact('order'));
    }

    public function posInvoice($id)
    {
        $order = Order::with(['customer', 'products.product', 'transactions'])->findOrFail($id);
        $maxWidth = readConfig('receiptMaxwidth')??'300px';
        return view('backend.orders.pos-invoice', compact('order', 'maxWidth'));
    }

    // New API for Headless Printing
    public function receiptDetails($id)
    {
        $order = Order::with(['customer', 'products.product', 'transactions'])->findOrFail($id);
        
        // Structure data specifically for the JS template
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'date' => $order->created_at->format('d/m/Y h:i A'),
                'staff' => auth()->user()->name ?? 'Staff',
                'customer' => $order->customer ? [
                    'name' => $order->customer->name,
                    'phone' => $order->customer->phone ?? ''
                ] : null,
                'items' => $order->products->map(function($item) {
                    return [
                        'name' => $item->product->name,
                        'qty' => $item->quantity,
                        'price' => number_format($item->price, 2),
                        'total' => number_format($item->total, 2)
                    ];
                }),
                'sub_total' => number_format($order->sub_total, 2),
                'discount' => number_format($order->discount, 2),
                'total' => number_format($order->total, 2),
                'paid' => number_format($order->paid, 2),
                'due' => number_format($order->due, 2),
                'change' => number_format(max(0, $order->paid - $order->total), 2),
                'payment_method' => ucfirst($order->transactions->last()->paid_by ?? 'Cash'),
                // Config
                'config' => [
                    'site_name' => readConfig('site_name'),
                    'ntn' => readConfig('tax_ntn') ?: '',
                    'strn' => readConfig('tax_strn') ?: '',
                    'gst_rate' => floatval(readConfig('tax_gst_rate') ?: 17),
                    'gst_enabled' => readConfig('tax_gst_enabled') == 1,
                    'show_tax' => readConfig('tax_show_on_receipt') == 1,
                    'fbr_pos_id' => readConfig('fbr_pos_id') ?: '',
                    'address' => readConfig('contact_address'),
                    'phone' => readConfig('contact_phone'),
                    'email' => readConfig('contact_email'),
                    'footer_note' => readConfig('note_to_customer_invoice'),
                    'show_logo' => readConfig('is_show_logo_invoice'),
                    'show_site' => readConfig('is_show_site_invoice'),
                    'show_address' => readConfig('is_show_address_invoice'),
                    'show_phone' => readConfig('is_show_phone_invoice'),
                    'show_email' => readConfig('is_show_email_invoice'),
                    'show_customer' => readConfig('is_show_customer_invoice'),
                    'show_note' => readConfig('is_show_note_invoice'),
                    'logo_url' => assetImage(readconfig('site_logo'))
                ]
            ]
        ]);
    }

    /**
     * Prepare order data for FBR submission
     */
    protected function prepareFbrOrderData($order): array
    {
        // Use stored tax if available (Snapshot approach), else fallback to config (Legacy)
        if (isset($order->tax_amount) && isset($order->tax_rate) && $order->tax_rate > 0) {
            $gstRate = (float)$order->tax_rate;
            $taxAmount = (float)$order->tax_amount;
        } else {
            $gstRate = floatval(readConfig('tax_gst_rate') ?: 17);
            $taxableAmount = $order->sub_total - $order->discount;
            $taxAmount = readConfig('tax_gst_enabled') == 1 ? ($taxableAmount * $gstRate) / 100 : 0;
        }

        $items = [];
        foreach ($order->products as $product) {
            $items[] = [
                'product_id' => $product->product_id,
                'sku' => $product->product->sku ?? '',
                'name' => $product->product->name ?? 'Product',
                'quantity' => $product->quantity,
                'price' => $product->price,
                'total' => $product->total,
                'tax_rate' => $gstRate,
                'tax_amount' => ($product->total * $gstRate) / 100,
                'discount' => 0
            ];
        }

        return [
            'order_id' => $order->id,
            'invoice_number' => $order->id,
            'date' => $order->created_at->format('Y-m-d H:i:s'),
            'customer_name' => $order->customer->name ?? 'Walk-in Customer',
            'customer_phone' => $order->customer->phone ?? '',
            'buyer_ntn' => '',
            'buyer_cnic' => '',
            'sub_total' => $order->sub_total,
            'discount' => $order->discount,
            'tax_amount' => $taxAmount,
            'total' => $order->total,
            'total_quantity' => $order->products->sum('quantity'),
            'payment_method' => $order->transactions->last()->paid_by ?? 'cash',
            'items' => $items
        ];
    }

}

