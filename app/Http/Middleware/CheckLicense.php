<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\LicenseHelper;

class CheckLicense
{
    /**
     * Routes that don't require license check
     */
    protected $except = [
        'license.activate.show',
        'license.activate.public',
        'frontend.home',
        // 'login',  // Removed so license check is enforced before login
    ];

    /**
     * Check if app is licensed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip license check for excepted routes
        $currentRoute = $request->route() ? $request->route()->getName() : null;
        
        if ($currentRoute && in_array($currentRoute, $this->except)) {
            return $next($request);
        }

        // Check if license is valid
        if (!LicenseHelper::isActivated()) {
            return redirect()->route('license.activate.show')
                ->with('warning', 'Please activate your license to unlock the application.');
        }

        return $next($request);
    }
}
