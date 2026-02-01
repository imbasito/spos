<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderTransaction;
use App\Models\Product;
use App\Models\SupportTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Redirect non-admin users to POS cart
        if (!auth()->user()->hasRole('Admin')) {
            return redirect('/admin/cart');
        }

        $orders = Order::get();
        // Calculate totals
        $data = [
            'sub_total' => $orders->sum('sub_total'),
            'discount' => $orders->sum('discount'),
            'total' => $orders->sum('total'),
            'paid' => $orders->sum('paid'),
            'due' => $orders->sum('due'),
            'total_customer' => Customer::count(),
            'total_order' => $orders->count(),
            'total_product' => Product::count(),
            'total_sale_item' => OrderProduct::sum('quantity'),
            // New analytics
            'total_profit' => OrderProduct::sum(DB::raw('total - (purchase_price * quantity)')),
            'top_products' => Product::withCount(['orderProducts as sold_qty' => function($query) {
                $query->select(DB::raw('sum(quantity)'));
            }])->orderBy('sold_qty', 'desc')->take(5)->get(),
            'low_stock_products' => Product::where('quantity', '<', 10)->limit(10)->get(),
        ];


        $startDate = Carbon::now()->subDays(30)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');
        if($request->has('daterange')) {
            $dates = explode(' to ', $request->query('daterange'));

            if (count($dates) == 2) {
                $startDate = Carbon::parse($dates[0])->format('Y-m-d');
                $endDate = Carbon::parse($dates[1])->format('Y-m-d');
            }
        }
        $dailyTotals = OrderTransaction::selectRaw('DATE(created_at) as date, SUM(amount) as total_amount')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('date')
        ->orderBy('date', 'DESC')
        ->get();
        $dates = $dailyTotals->pluck('date')->toArray();
        $totalAmounts = $dailyTotals->pluck('total_amount')->toArray();
        $data['dates'] = $dates;
        $data['totalAmounts'] = $totalAmounts;
        $data['dateRange'] = 'from '. $startDate . ' to ' . $endDate;


        $currentYear = now()->year;
        $data['currentYear'] = $currentYear;

        $salesData = OrderTransaction::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total_amount')
        ->whereYear('created_at', $currentYear)
        ->groupBy('month')
        ->orderBy('month', 'ASC')->pluck('total_amount', 'month')->toArray();
        $tempMonths = [];
        $tempTotalAmountMonth = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthKey = Carbon::create($currentYear, $i, 1)->format('Y-m');
            $tempMonths[] = $monthKey;
            $tempTotalAmountMonth[] = $salesData[$monthKey] ?? 0;
        }

        $data['months'] = $tempMonths;
        $data['totalAmountMonth'] = $tempTotalAmountMonth;

        return view('backend.index', $data);
    }

    public function barcode()
    {
        return view('backend.barcode.index');
    }

    public function printBarcode(Request $request)
    {
        $label = $request->query('label');
        $barcode = $request->query('barcode');
        $mfg = $request->query('mfg');
        $exp = $request->query('exp');
        $size = $request->query('size', 'large');
        return view('backend.barcode.print', compact('label', 'barcode', 'mfg', 'exp', 'size'));
    }

    public function storeBarcode(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string|max:20',
            'label' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'label_size' => 'nullable|string',
            'mfg_date' => 'nullable|date',
            'exp_date' => 'nullable|date',
            'show_price' => 'nullable|boolean',
        ]);

        $history = \App\Models\BarcodeHistory::create([
            'barcode' => $request->barcode,
            'label' => $request->label,
            'price' => $request->price,
            'label_size' => $request->label_size ?? 'large',
            'mfg_date' => $request->mfg_date,
            'exp_date' => $request->exp_date,
            'show_price' => $request->show_price ?? false,
            'user_id' => auth()->id(),
        ]);

        return response()->json(['message' => 'Barcode saved', 'data' => $history], 201);
    }

    public function getBarcodeHistory(Request $request)
    {
        $history = \App\Models\BarcodeHistory::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($history);
    }

    public function deleteBarcode($id)
    {
        $barcode = \App\Models\BarcodeHistory::where('user_id', auth()->id())
            ->findOrFail($id);
        $barcode->delete();

        return response()->json(['message' => 'Barcode deleted']);
    }

    public function profile()
    {
        $user = auth()->user();
        return view('backend.profile.index', compact('user'));
    }
}
