<?php
// debug_constraints.php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$dbName = config('database.connections.mysql.database');
echo "Inspecting Constraints for DB: $dbName\n";

$constraints = DB::select("
    SELECT TABLE_NAME, CONSTRAINT_NAME 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = ? 
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ORDER BY TABLE_NAME
", [$dbName]);

if (empty($constraints)) {
    echo "No Foreign Keys found in the entire database.\n";
} else {
    foreach ($constraints as $c) {
        echo "Table: [{$c->TABLE_NAME}] - Constraint: [{$c->CONSTRAINT_NAME}]\n";
    }
}
