<?php

namespace App\Http\Controllers\Backend\Report;

use App\Http\Controllers\Controller;
use App\Models\DailyClosing;
use App\Models\Order;
use App\Models\ProductReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DailyReportController extends Controller
{
    public function index()
    {
        // History Data
        $closings = DailyClosing::latest()->paginate(20);

        // Current Session Data for Modal
        $lastClosing = DailyClosing::latest('closed_at')->first();
        $startTime = $lastClosing ? $lastClosing->closed_at : now()->startOfDay();

        $totalOrders = Order::where('created_at', '>=', $startTime)->count();
        $totalSales = Order::where('created_at', '>=', $startTime)->sum('total');
        $totalReturns = ProductReturn::where('created_at', '>=', $startTime)->sum('total_refund');
        $systemCash = $totalSales - $totalReturns;

        return view('backend.reports.daily.index', compact('closings', 'totalOrders', 'totalSales', 'totalReturns', 'systemCash'));
    }

    public function create()
    {
        // PROFESSIONAL LOGIC: Calculate stats SINCE THE LAST CLOSING
        $lastClosing = DailyClosing::latest('closed_at')->first();
        $startTime = $lastClosing ? $lastClosing->closed_at : now()->startOfDay();

        $totalOrders = Order::where('created_at', '>=', $startTime)->count();
        $totalSales = Order::where('created_at', '>=', $startTime)->sum('total');
        $totalReturns = ProductReturn::where('created_at', '>=', $startTime)->sum('total_refund');
        
        $systemCash = $totalSales - $totalReturns;

        return view('backend.reports.daily.create', compact('totalOrders', 'totalSales', 'totalReturns', 'systemCash'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cash_in_hand' => 'required|numeric|min:0',
        ]);

        // PROFESSIONAL LOGIC: Recalculate exactly as displayed
        $lastClosing = DailyClosing::latest('closed_at')->first();
        $startTime = $lastClosing ? $lastClosing->closed_at : now()->startOfDay();
        
        $totalOrders = Order::where('created_at', '>=', $startTime)->count();
        $totalSales = Order::where('created_at', '>=', $startTime)->sum('total');
        $totalReturns = ProductReturn::where('created_at', '>=', $startTime)->sum('total_refund');
        $systemCash = $totalSales - $totalReturns;

        $difference = $request->cash_in_hand - $systemCash;

        DailyClosing::create([
            'user_id' => Auth::id(),
            'opening_amount' => 0, 
            'cash_in_hand' => $request->cash_in_hand,
            'system_cash' => $systemCash,
            'difference' => $difference,
            'total_sales' => $totalSales,
            'total_returns' => $totalReturns,
            'total_orders' => $totalOrders,
            'closed_at' => now(),
        ]);

        return redirect()->route('backend.admin.dashboard')->with('success', 'Register Closed Successfully! Next shift starts now.');
    }
}
