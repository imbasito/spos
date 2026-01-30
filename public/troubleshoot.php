<?php
// Force clear OpCache
$opcache_status = "Not Enabled";
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        $opcache_status = "CLEARED SUCCESSFULLY";
    } else {
        $opcache_status = "FAILED TO CLEAR (Check config)";
    }
}

// Clear Realpath Cache
clearstatcache(true);

echo "<h1>System Troubleshooter</h1>";
echo "<h2>1. OpCache Status</h2>";
echo "<strong>Action:</strong> " . $opcache_status . "<br>";
echo "<strong>Enabled:</strong> " . (ini_get('opcache.enable') ? 'Yes' : 'No') . "<br>";

echo "<h2>2. Session Debug</h2>";
echo "<strong>Session Driver:</strong> " . env('SESSION_DRIVER') . "<br>";
echo "<strong>Session Lifetime:</strong> " . env('SESSION_LIFETIME') . " minutes<br>";
echo "<strong>Cookie Name:</strong> " . config('session.cookie') . "<br>";
echo "<strong>Secure Cookie:</strong> " . (config('session.secure') ? 'Yes' : 'No (Correct for Localhost)') . "<br>";
echo "<strong>Session Path:</strong> " . storage_path('framework/sessions') . "<br>";
echo "<strong>Writable?</strong> " . (is_writable(storage_path('framework/sessions')) ? 'Yes' : 'NO (FIX PERMISSIONS)') . "<br>";

echo "<h2>3. App Environment</h2>";
echo "<strong>Env:</strong> " . app()->environment() . "<br>";
echo "<strong>Debug:</strong> " . (config('app.debug') ? 'True' : 'False') . "<br>";

echo "<h2>4. View Cache</h2>";
$viewPath = storage_path('framework/views');
$files = glob("$viewPath/*");
echo "<strong>Cached Views Count:</strong> " . count($files) . "<br>";

echo "<hr>";
echo "<h3>Instructions</h3>";
echo "<p>If OpCache said 'CLEARED SUCCESSFULLY', try reloading your app now.</p>";
