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
            $json = json_decode(file_get_contents(base_path('package.json')), true);
            $version = $json['version'] ?? '1.0.0';
            config(['app.version' => $version]);
        } catch (\Exception $e) {
            config(['app.version' => '1.0.0']);
        }

        // PROFESSIONAL CLIENT-SIDE PROTECTION
        // Self-Healing Database: Detects if tables were renamed/corrupted and restores them automatically.
        try {
            // Check if critical table 'users' is missing
            if (\Illuminate\Support\Facades\DB::connection()->getPdo() && 
                !\Illuminate\Support\Facades\Schema::hasTable('users')) {
                
                \Illuminate\Support\Facades\Artisan::call('db:repair');
                \Illuminate\Support\Facades\Log::info("Self-healing triggered: Tables restored.");
            }
        } catch (\Exception $e) {
            // Database might not be ready yet (e.g. during installation), ignore.
        }
    }
}
