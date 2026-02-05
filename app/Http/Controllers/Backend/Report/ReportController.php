<?php

namespace App\Http\Controllers\Backend\Report;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class ReportController extends Controller
{

    public function saleReport(Request $request)
    {
        abort_if(!auth()->user()->can(abilities: 'reports_sales'), 403);

        // Common date logic
        $start_date_input = $request->input('start_date', Carbon::today()->subDays(29)->format('Y-m-d'));
        $end_date_input = $request->input('end_date', Carbon::today()->format('Y-m-d'));
        $start_date = Carbon::createFromFormat('Y-m-d', $start_date_input)->startOfDay();
        $end_date = Carbon::createFromFormat('Y-m-d', $end_date_input)->endOfDay();

        if ($request->ajax()) {
            $orders = Order::whereBetween('created_at', [$start_date, $end_date])->with('customer')->latest()->get();
            return DataTables::of($orders)
                ->addIndexColumn()
                ->addColumn('saleId', fn($data) => "#" . $data->id)
                ->addColumn('customer', fn($data) => $data->customer->name ?? '-')
                ->addColumn('date', fn($data) => $data->created_at->format('d-m-Y'))
                ->addColumn('item', fn($data) => $data->total_item)
                ->addColumn('sub_total', fn($data) => number_format($data->sub_total, 2, '.', ','))
                ->addColumn('discount', fn($data) => number_format($data->discount, 2, '.', ','))
                ->addColumn('total', fn($data) => number_format($data->total, 2, '.', ','))
                ->addColumn('paid', fn($data) => number_format($data->paid, 2, '.', ','))
                ->addColumn('due', fn($data) => number_format($data->due, 2, '.', ','))
                ->addColumn('status', fn($data) => $data->status
                    ? '<span class="badge bg-primary">Paid</span>'
                    : '<span class="badge bg-danger">Due</span>')
                ->rawColumns(['status'])
                ->toJson();
        }

        // Calculate totals for the summary cards
        // Utilizing a separate query for aggregates to be efficient
        // Calculate totals for the summary cards
        // Utilizing a separate query for aggregates to be efficient
        $ordersQuery = Order::whereBetween('orders.created_at', [$start_date, $end_date]);
        
        $total_refunds = \App\Models\ProductReturn::whereBetween('created_at', [$start_date, $end_date])->sum('total_refund');
        
        // Clone query for efficiency to avoid re-building
        $sub_total = (clone $ordersQuery)->sum('sub_total');
        $discount = (clone $ordersQuery)->sum('discount');
        $paid = (clone $ordersQuery)->sum('paid');
        $due = (clone $ordersQuery)->sum('due');
        $total = (clone $ordersQuery)->sum('total');

        $data = [
            'sub_total' => $sub_total,
            'discount' => $discount,
            'paid' => $paid,
            'due' => $due,
            'total' => $total,
            'total_refunds' => $total_refunds,
            'net_revenue' => $total - $total_refunds,
            'start_date' => $start_date->format('M d, Y'),
            'end_date' => $end_date->format('M d, Y'),
        ];

        return view('backend.reports.sale-report', $data);
    }
    public function saleSummery(Request $request)
    {

        abort_if(!auth()->user()->can('reports_summary'), 403);
        // Get user input or set default values
        $start_date_input = $request->input('start_date', Carbon::today()->subDays(29)->format('Y-m-d'));
        $end_date_input = $request->input('end_date', Carbon::today()->format('Y-m-d'));

        // Parse and set start date
        $start_date = Carbon::createFromFormat('Y-m-d', $start_date_input) ?: Carbon::today()->subDays(29)->startOfDay();
        $start_date = $start_date->startOfDay();

        // Parse and set end date
        $end_date = Carbon::createFromFormat('Y-m-d', $end_date_input) ?: Carbon::today()->endOfDay();
        $end_date = $end_date->endOfDay();
        // Retrieve orders within the date range
        $orders = Order::whereBetween('created_at', [$start_date, $end_date])->get();

        // Calculate totals
        $total_refunds = \App\Models\ProductReturn::whereBetween('created_at', [$start_date, $end_date])->sum('total_refund');

        // Calculate payment method totals
        $transactions = \App\Models\OrderTransaction::whereBetween('created_at', [$start_date, $end_date])->get();
        $total_cash = $transactions->where('paid_by', 'cash')->sum('amount');
        $total_card = $transactions->where('paid_by', 'card')->sum('amount');
        $total_online = $transactions->where('paid_by', 'online')->sum('amount');

        $data = [
            'sub_total' => $orders->sum('sub_total'),
            'discount' => $orders->sum('discount'),
            'paid' => $orders->sum('paid'),
            'due' => $orders->sum('due'),
            'total' => $orders->sum('total'),
            'total_refunds' => $total_refunds,
            'net_revenue' => $orders->sum('total') - $total_refunds,
            'total_cash' => $total_cash,
            'total_card' => $total_card,
            'total_online' => $total_online,
            'start_date' => $start_date->format('M d, Y'),
            'end_date' => $end_date->format('M d, Y'),
        ];

        return view('backend.reports.sale-summery', $data);
    }
    function inventoryReport(Request $request)
    {

        abort_if(!auth()->user()->can('reports_inventory'), 403);
        if ($request->ajax()) {
            $products = Product::latest()->active(); // Query builder for DataTables
            return DataTables::of($products)
                ->addIndexColumn()
                ->addColumn('name', fn($data) => $data->name)
                ->addColumn('sku', fn($data) => $data->sku)
                ->addColumn(
                    'price',
                    fn($data) => $data->discounted_price .
                        ($data->price > $data->discounted_price
                            ? '<br><del>' . $data->price . '</del>'
                            : '')
                )
                ->addColumn('quantity', fn($data) => $data->quantity . ' ' . optional($data->unit)->short_name)
                ->rawColumns(['name', 'sku', 'price', 'quantity', 'status'])
                ->toJson();
        }
        return view('backend.reports.inventory');
    }

    public function refundReport(Request $request)
    {
        // Get user input or set default values
        $start_date_input = $request->input('start_date', Carbon::today()->subDays(29)->format('Y-m-d'));
        $end_date_input = $request->input('end_date', Carbon::today()->format('Y-m-d'));

        // Parse and set start date
        $start_date = Carbon::createFromFormat('Y-m-d', $start_date_input) ?: Carbon::today()->subDays(29)->startOfDay();
        $start_date = $start_date->startOfDay();

        // Parse and set end date
        $end_date = Carbon::createFromFormat('Y-m-d', $end_date_input) ?: Carbon::today()->endOfDay();
        $end_date = $end_date->endOfDay();

        // Retrieve refunds within the date range
        $refunds = \App\Models\ProductReturn::whereBetween('created_at', [$start_date, $end_date])
            ->with(['order.customer', 'processedBy'])
            ->get();

        // Calculate totals
        $data = [
            'refunds' => $refunds,
            'total_refund' => $refunds->sum('total_refund'),
            'total_count' => $refunds->count(),
            'start_date' => $start_date->format('M d, Y'),
            'end_date' => $end_date->format('M d, Y'),
        ];

        return view('backend.reports.refund-report', $data);
    }
    public function supplierLedger(Request $request)
    {
        // abort_if(!auth()->user()->can('reports_supplier_ledger'), 403); // Permission check to be added later

        if ($request->ajax()) {
            $suppliers = \App\Models\Supplier::query();
            
            return DataTables::of($suppliers)
                ->addIndexColumn()
                ->addColumn('name', fn($data) => $data->name)
                ->addColumn('phone', fn($data) => $data->phone)
                ->addColumn('total_purchase', function($data) {
                    return number_format($data->purchases()->sum('grand_total'), 2);
                })
                ->addColumn('total_paid', function($data) {
                    return number_format($data->purchases()->sum('paid_amount'), 2);
                })
                ->addColumn('balance_due', function($data) {
                    $total = $data->purchases()->sum('grand_total');
                    $paid = $data->purchases()->sum('paid_amount');
                    $due = $total - $paid;
                    return $due > 0 ? '<span class="text-danger font-weight-bold">' . number_format($due, 2) . '</span>' : number_format($due, 2);
                })
                ->addColumn('action', function ($data) {
                    return '<a href="' . route('backend.admin.suppliers.orders', $data->id) . '" class="btn btn-sm btn-info">View History</a>';
                })
                ->rawColumns(['balance_due', 'action'])
                ->toJson();
        }

        // Summary Cards Data
        $allSuppliers = \App\Models\Supplier::with('purchases')->get();
        $totalDebt = 0;
        foreach ($allSuppliers as $supplier) {
             $total = $supplier->purchases->sum('grand_total');
             $paid = $supplier->purchases->sum('paid_amount');
             $totalDebt += max(0, $total - $paid);
        }

        return view('backend.reports.supplier-ledger', compact('totalDebt'));
    }
}
