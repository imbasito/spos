<?php
// fix_active_fk.php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tableName = 'barcode_history';
$constraint = 'barcode_history_user_id_foreign';

echo "Attempting to drop [$constraint] from [$tableName]...\n";

try {
    DB::statement("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$constraint}`");
    echo "SUCCESS: Dropped foreign key.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
