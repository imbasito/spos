<?php

namespace App\Providers;

use App\Models\Page;
use App\Models\Product;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();

        // Load version from package.json
        try {
            $version = null;
            $candidatePaths = [
                base_path('package.json'),
                base_path('app/package.json'),
                dirname(base_path()) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'package.json',
            ];

            foreach ($candidatePaths as $packageJsonPath) {
                if (!file_exists($packageJsonPath)) {
                    continue;
                }

                $json = json_decode(file_get_contents($packageJsonPath), true);
                if (is_array($json) && !empty($json['version'])) {
                    $version = $json['version'];
                    break;
                }
            }

            $version = $version ?: env('APP_VERSION', config('app.version', '1.0.0'));
            config(['app.version' => $version]);
        } catch (\Exception $e) {
            config(['app.version' => env('APP_VERSION', config('app.version', '1.0.0'))]);
        }

        // PROFESSIONAL CLIENT-SIDE PROTECTION
        // Self-Healing Database: Detects if tables were renamed/corrupted and restores them automatically.
        try {
            // Check if critical table 'users' is missing (cache result for 60 seconds to avoid DB query on every request)
            if (\Illuminate\Support\Facades\DB::connection()->getPdo()) {
                $hasUsersTable = \Illuminate\Support\Facades\Cache::remember('db_has_users_table', 60, function () {
                    return \Illuminate\Support\Facades\Schema::hasTable('users');
                });
                
                if (!$hasUsersTable) {
                    \Illuminate\Support\Facades\Artisan::call('db:repair');
                    \Illuminate\Support\Facades\Log::info("Self-healing triggered: Tables restored.");
                    \Illuminate\Support\Facades\Cache::forget('db_has_users_table');
                }
            }
        } catch (\Exception $e) {
            // Database might not be ready yet (e.g. during installation), ignore.
        }
    }
}
