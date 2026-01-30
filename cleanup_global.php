<?php
// cleanup_global.php
// DROPS EVERY FOREIGN KEY IN THE DATABASE
// Use with extreme caution (Safe here because we are about to restore)

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "Starting GLOBAL DB Constraints Cleanup...\n";

$dbName = config('database.connections.mysql.database');
echo "Target DB: $dbName\n";

// Get ALL Foreign Keys
$fks = DB::select("
    SELECT TABLE_NAME, CONSTRAINT_NAME 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = ? 
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'", [$dbName]);

echo "Found " . count($fks) . " foreign keys.\n";

foreach ($fks as $fk) {
    echo "Dropping [{$fk->CONSTRAINT_NAME}] from [{$fk->TABLE_NAME}]... ";
    try {
        DB::statement("ALTER TABLE `{$fk->TABLE_NAME}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        echo "OK\n";
    } catch (\Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
    }
}

echo "Global Cleanup Complete.\n";
