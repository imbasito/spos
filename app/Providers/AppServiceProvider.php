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
    }
}
