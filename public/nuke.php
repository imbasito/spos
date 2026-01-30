<?php
// NUCLEAR CACHE CLEAR
echo "<h1>System Deep Clean</h1>";

// 1. Clear Views (Filesystem)
$files = glob(storage_path('framework/views/*'));
$count = 0;
foreach($files as $file){ 
  if(is_file($file) && basename($file) !== '.gitignore') {
    unlink($file); 
    $count++;
  }
}
echo "Deleted $count compiled view files.<br>";

// 2. Clear Config (Filesystem)
$files = glob(base_path('bootstrap/cache/*'));
foreach($files as $file){
  if(is_file($file) && basename($file) !== '.gitignore') {
    unlink($file);
  }
}
echo "Deleted bootstrap cache files.<br>";

// 3. Reset OpCache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OpCache Reset: SUCCESS<br>";
} else {
    echo "OpCache Reset: N/A (Not Enabled)<br>";
}

// 4. Artisan Commands (Just in case)
try {
    \Artisan::call('optimize:clear');
    echo "Artisan Optimize: SUCCESS<br>";
} catch (\Exception $e) {
    echo "Artisan Optimize: FAILED (" . $e->getMessage() . ")<br>";
}

echo "<h2><a href='/admin/dashboard'>Go Back to Dashboard</a></h2>";
echo "<p>Please hard refresh the dashboard (Ctrl+F5).</p>";
