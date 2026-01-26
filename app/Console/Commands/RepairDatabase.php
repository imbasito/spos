<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:repair';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically restore tables that were accidentally renamed to bak_';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Checking database integrity...");
        
        try {
            $tables = DB::select('SHOW TABLES'); 
            $dbName = config('database.connections.mysql.database');
            $key = "Tables_in_{$dbName}";
            
            $repairedCount = 0;

            foreach ($tables as $table) {
                // Handle different fetch modes just in case
                $tableName = is_object($table) ? $table->$key : array_values((array)$table)[0];
                
                // Pattern: bak_{name}_{timestamp}
                if (preg_match('/^bak_(.+)_\d+$/', $tableName, $matches)) {
                    $originalName = $matches[1];
                    
                    // Safety: Only restore if original is MISSING
                    if (!Schema::hasTable($originalName)) {
                        $this->warn("Corruption detected: $tableName found, but $originalName is missing.");
                        $this->info("Restoring $originalName...");
                        
                        DB::statement("RENAME TABLE `{$tableName}` TO `{$originalName}`");
                        $repairedCount++;
                    } else {
                        // Optional: Clean up old backups? User didn't ask, so leave them safer.
                    }
                }
            }
            
            if ($repairedCount > 0) {
                $this->info("Successfully repaired $repairedCount tables.");
                return 1; // Signal that repair happened
            } else {
                $this->info("Database is healthy.");
                return 0;
            }

        } catch (\Exception $e) {
            $this->error("Repair failed: " . $e->getMessage());
            return -1;
        }
    }
}
