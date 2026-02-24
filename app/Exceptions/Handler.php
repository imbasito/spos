<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // ============================================
        // DYNAMIC DATABASE SELF-HEALING
        // ============================================
        // Detect "Column not found" errors (SQLSTATE[42S22])
        // This commonly happens after restoring a legacy backup.
        if ($e instanceof \Illuminate\Database\QueryException && str_contains($e->getMessage(), '42S22')) {
            
            $lockKey = 'last_auto_migrate_attempt';
            $now = time();
            $lastAttempt = \Illuminate\Support\Facades\Cache::get($lockKey, 0);

            // Throttle: Only try to auto-migrate once every 60 seconds to prevent infinite loops
            if ($now - $lastAttempt > 60) {
                try {
                    \Illuminate\Support\Facades\Cache::put($lockKey, $now, 60);
                    
                    // Run migrations in the background
                    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                    
                    // Clear cache to ensure schema changes are recognized
                    \Illuminate\Support\Facades\Artisan::call('cache:clear');

                    // If it was an AJAX/DataTables request, return a special response to trigger a reload
                    if ($request->ajax()) {
                        return response()->json([
                            'error' => 'Database schema updated. Please retry your action.',
                            'auto_repair' => true
                        ], 500);
                    }

                    // For standard requests, just refresh the page
                    return back()->with('success', 'Database structure updated automatically. Please try again.');
                } catch (\Exception $migrationError) {
                    // If migration fails, let the original error pass through to avoid masking serious issues
                }
            }
        }

        return parent::render($request, $e);
    }
}
