<?php
// cleanup.php - Drop Foreign Keys from all 'bak_' tables

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting Cleanup...\n";

$dbName = config('database.connections.mysql.database');
$allTables = DB::select('SHOW TABLES');
// Key might be 'Tables_in_laravel' or 'Tables_in_spos' etc. 
// We'll inspect the first object to find the dynamic key name.

if (empty($allTables)) {
    echo "No tables found.\n";
    exit;
}

$first = (array)$allTables[0];
$key = array_key_first($first); 

echo "Found DB Key: $key\n";

foreach ($allTables as $t) {
    $tableName = $t->$key;

    // Only target backup tables
    if (str_starts_with($tableName, 'bak_')) {
        echo "Processing backup table: $tableName\n";
        
        $fks = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_NAME = ? 
            AND TABLE_SCHEMA = ? 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'", [$tableName, $dbName]);

        if (empty($fks)) {
            echo "  - No FKs found.\n";
            continue;
        }

        foreach ($fks as $fk) {
            $name = $fk->CONSTRAINT_NAME;
            try {
                DB::statement("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$name}`");
                echo "  SUCCESS: Dropped FK [$name]\n";
            } catch (\Exception $e) {
                echo "  ERROR: Failed to drop [$name] - " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "Cleanup Complete.\n";
