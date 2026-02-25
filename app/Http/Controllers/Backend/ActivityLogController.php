<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ActivityLogController extends Controller
{
    /**
     * Main page — returns the shell view with filter options.
     * The actual log data is loaded via the data() AJAX endpoint.
     */
    public function index(Request $request)
    {
        $users   = User::select('id', 'name')->orderBy('name')->get();
        $modules = ActivityLog::select('module')
            ->whereNotNull('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        $stats = $this->getStats();

        return view('backend.activity.index', compact('users', 'modules', 'stats'));
    }

    /**
     * AJAX endpoint — returns paginated, filtered log data as JSON.
     */
    public function data(Request $request)
    {
        $perPage = (int) $request->get('per_page', 50);
        $perPage = min($perPage, 200); // hard cap per page

        $query = ActivityLog::with('user:id,name')
            ->when($request->filled('start_date'), fn ($q) =>
                $q->whereDate('created_at', '>=', $request->start_date)
            )
            ->when($request->filled('end_date'), fn ($q) =>
                $q->whereDate('created_at', '<=', $request->end_date)
            )
            ->when($request->filled('user_id'), fn ($q) =>
                $q->where('user_id', $request->user_id)
            )
            ->when($request->filled('action'), fn ($q) =>
                $q->where('action', $request->action)
            )
            ->when($request->filled('module'), fn ($q) =>
                $q->where('module', $request->module)
            )
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('description', 'like', $term)
                          ->orWhere('ip_address', 'like', $term)
                          ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term));
                });
            })
            ->latest();

        // ── 500-record safety cap ───────────────────────────────────────────────
        // Prevents Electron memory issues on large unfiltered result sets.
        $totalCapped = false;
        $uncappedCount = $query->count();
        if ($uncappedCount > 500 && !$request->hasAny(['start_date', 'end_date', 'user_id', 'action', 'module', 'search'])) {
            $query->limit(500);
            $totalCapped = true;
        }

        $logs = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data'         => $logs->items(),
            'current_page' => $logs->currentPage(),
            'last_page'    => $logs->lastPage(),
            'total'        => $logs->total(),
            'per_page'     => $logs->perPage(),
            'capped'       => $totalCapped,
        ]);
    }

    // ─── Private helpers ────────────────────────────────────────────────────────

    /**
     * Summary statistics for the header cards.
     * Results are cached for 60 seconds to avoid repeated COUNT queries.
     */
    private function getStats(): array
    {
        return Cache::remember('audit_log_stats', 60, function () {
            $todayCount  = ActivityLog::whereDate('created_at', today())->count();
            $totalCount  = ActivityLog::count();
            $activeUsers = ActivityLog::whereNotNull('user_id')
                ->whereDate('created_at', '>=', now()->subDays(7))
                ->distinct('user_id')
                ->count('user_id');

            return [
                'total'        => $totalCount,
                'today'        => $todayCount,
                'active_users' => $activeUsers,
            ];
        });
    }
}
