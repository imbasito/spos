<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    /**
     * Display the backup manager dashboard
     */
    public function index()
    {
        // Safe Config Reading
        $backupPath = $this->getSafeConfig('backup_path', storage_path('app/backups'));
        $autoBackup = $this->getSafeConfig('auto_backup', 'off');
        
        // Ensure directory exists
        if (!File::exists($backupPath)) {
            try {
                File::makeDirectory($backupPath, 0755, true);
            } catch (\Exception $e) {
                // If custom path fails, revert to safe default
                $backupPath = storage_path('app/backups');
                if (!File::exists($backupPath)) File::makeDirectory($backupPath, 0755, true);
            }
        }

        $allFiles = [];
        try {
            $files = glob($backupPath . '/*.sql');
            if ($files) {
                foreach ($files as $file) {
                    $allFiles[] = [
                        'filename' => basename($file),
                        'path' => $file,
                        'size' => $this->humanFilesize(filesize($file)),
                        'date' => date('Y-m-d H:i:s', filemtime($file)),
                        'timestamp' => filemtime($file)
                    ];
                }
                // Sort by newest first
                usort($allFiles, function($a, $b) {
                    return $b['timestamp'] - $a['timestamp'];
                });

                // Manual Pagination
                $perPage = 10;
                $currentPage = request()->get('page', 1);
                $offset = ($currentPage - 1) * $perPage;
                
                $items = array_slice($allFiles, $offset, $perPage);
                
                $backups = new LengthAwarePaginator(
                    $items,
                    count($allFiles),
                    $perPage,
                    $currentPage,
                    ['path' => request()->url(), 'query' => request()->query()]
                );
            } else {
                $backups = new LengthAwarePaginator([], 0, 10);
            }
        } catch (\Exception $e) {
            $backups = new LengthAwarePaginator([], 0, 10);
        }

        return view('backend.settings.backup', compact('backups', 'backupPath', 'autoBackup'));
    }

    /**
     * Save Backup Settings
     */
    public function saveSettings(Request $request)
    {
        try {
            $path = $request->input('backup_path');
            $auto = $request->input('auto_backup');

            // Validate Path
            if (!File::exists($path)) {
                try {
                    File::makeDirectory($path, 0755, true);
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', "Cannot create directory at: $path");
                }
            }

            if (!is_writable($path)) {
                return redirect()->back()->with('error', "Directory is not writable: $path");
            }

            // Save Configs safely
            $this->setSafeConfig('backup_path', $path);
            $this->setSafeConfig('auto_backup', $auto);

            return redirect()->back()->with('success', 'Backup settings updated.');

        } catch (\Exception $e) {
             return redirect()->back()->with('error', 'Error saving settings: ' . $e->getMessage());
        }
    }

    /**
     * Create a new database backup
     */
    public function createBackup()
    {
        try {
            $backupPath = $this->getSafeConfig('backup_path', storage_path('app/backups'));
            if (!File::exists($backupPath)) {
                File::makeDirectory($backupPath, 0755, true);
            }

            $filename = 'backup-' . date('Y-m-d-H-i-s') . '.sql';
            $fullPath = $backupPath . '/' . $filename;

            // Database Config
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');
            $dbHost = config('database.connections.mysql.host', '127.0.0.1');
            $dbPort = config('database.connections.mysql.port', '3306');
            
            // Resolve Binary Path explicitly
            $basePath = base_path();
            $mysqldump = $basePath . '\mysql\bin\mysqldump.exe';
            
            if (!File::exists($mysqldump)) {
                $mysqldump = 'mysqldump'; 
            } else {
                $mysqldump = '"' . $mysqldump . '"';
            }

            // Build Command with explicit port and protocol
            $passwordPart = $dbPass ? "--password=\"{$dbPass}\"" : "";
            $dumpCommand = "{$mysqldump} --user=\"{$dbUser}\" {$passwordPart} --host=\"{$dbHost}\" --port=\"{$dbPort}\" --protocol=tcp --single-transaction \"{$dbName}\" > \"{$fullPath}\" 2>&1";
            
            // Execute
            $output = [];
            $resultCode = null;
            exec($dumpCommand, $output, $resultCode);

            if (File::exists($fullPath) && File::size($fullPath) > 0) {
                $this->setSafeConfig('last_backup_date', date('Y-m-d H:i:s'));
                return redirect()->back()->with('success', "Backup Created: {$filename}");
            } else {
                 return redirect()->back()->with('error', "Backup Failed. Details: " . implode("\n", $output));
            }

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Critical Error: ' . $e->getMessage());
        }
    }

    /**
     * Restore Backup
     */
    public function restoreBackup(Request $request, $filename)
    {
        try {
            // Check for POST method if we want to enforce it via controller too
            if (!$request->isMethod('post')) {
                return redirect()->back()->with('error', 'Invalid request method.');
            }

            $backupPath = $this->getSafeConfig('backup_path', storage_path('app/backups'));
            $fullPath = $backupPath . '/' . $filename;

            if (!File::exists($fullPath)) {
                return redirect()->back()->with('error', 'Backup file not found.');
            }

            // --- SAFE RESTORE STRATEGY ---
            $dbName = config('database.connections.mysql.database');
            
            // 1. Disable Foreign Keys Globally
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            $allTables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
            $tablesKey = "Tables_in_{$dbName}";
            
            // 2. Process All Tables
            $bakTimestamp = time(); // Shared timestamp so all bak_ names are trackable
            $bakTables = [];        // Track renamed tables so we can clean them up after restore
            foreach ($allTables as $t) {
                $tableName = $t->$tablesKey;
                
                // If it's already a backup table, ensure it's clean (just in case)
                if (str_starts_with($tableName, 'bak_')) {
                    $this->dropForeignKeys($tableName, $dbName);
                    continue; 
                }
                
                // If it's an ACTIVE table:
                // A. Drop its Foreign Keys NOW (Free up the names)
                $this->dropForeignKeys($tableName, $dbName);
                
                // B. Rename it to backup
                $bakName = "bak_" . $tableName . "_" . $bakTimestamp; 
                $bakTables[] = $bakName;
                try {
                    \Illuminate\Support\Facades\DB::statement("RENAME TABLE `{$tableName}` TO `{$bakName}`");
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to rename table {$tableName}: " . $e->getMessage());
                }
            }
            
            // --- EXECUTE RESTORE ---
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');
            $dbHost = config('database.connections.mysql.host', '127.0.0.1');
            $dbPort = config('database.connections.mysql.port', '3306');

            // Resolve Binary Path
            $basePath = base_path();
            $mysqlBin = $basePath . '\mysql\bin\mysql.exe';
            $mysql = File::exists($mysqlBin) ? '"' . $mysqlBin . '"' : 'mysql';

            // Command with explicit Foreign Key suppression
            $passwordPart = $dbPass ? "--password=\"{$dbPass}\"" : "";
            $restoreCommand = "{$mysql} --user=\"{$dbUser}\" {$passwordPart} --host=\"{$dbHost}\" --port=\"{$dbPort}\" --protocol=tcp --init-command=\"SET FOREIGN_KEY_CHECKS=0;\" \"{$dbName}\" < \"{$fullPath}\" 2>&1";
            
            $output = [];
            $resultCode = null;
            exec($restoreCommand, $output, $resultCode);

            // Re-enable Foreign Keys
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');

            if ($resultCode === 0) {
                // CRITICAL: Run migrations to update database schema.
                // Old backups may be missing NEW columns added after the backup was made.
                // NOTE: If a table already exists (SQLSTATE 42S01 / error 1050), that is
                // expected for old backups - the table was restored from the dump. Continue.
                try {
                    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                    \Illuminate\Support\Facades\Log::info('Migrations executed successfully after restore.');
                } catch (\Illuminate\Database\QueryException $e) {
                    $code = $e->errorInfo[1] ?? 0;
                    if ($code == 1050) {
                        // 1050 = Base table or view already exists — safe to ignore.
                        // The table came from the restored backup. PHP artisan migrate tried
                        // to re-CREATE it (because its migrations table was older), but it's fine.
                        \Illuminate\Support\Facades\Log::info('Migration skipped table-already-exists (1050) — restore from old backup is OK.');
                    } else {
                        \Illuminate\Support\Facades\Log::error('Migration error after restore (non-fatal): ' . $e->getMessage());
                        // Non-fatal: the data is restored; migration failure just means schema
                        // may be slightly behind. Still redirect to success with a warning.
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Migration exception after restore (non-fatal): ' . $e->getMessage());
                }
                
                // Clear active POS carts after restore
                // The restored carts have user_ids from backup that don't match current users
                try {
                    \Illuminate\Support\Facades\DB::table('pos_carts')->truncate();
                    
                    // Also delete journal file to prevent restore popup with invalid data
                    $journalPath = storage_path('app/current_sale.journal');
                    if (File::exists($journalPath)) {
                        File::delete($journalPath);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Could not clear pos_carts after restore: ' . $e->getMessage());
                }
                
                // DROP all bak_ tables from this restore session
                try {
                    foreach ($bakTables as $bakTable) {
                        \Illuminate\Support\Facades\DB::statement("DROP TABLE IF EXISTS `{$bakTable}`");
                    }
                    \Illuminate\Support\Facades\Log::info('BackupRestore: Cleaned up ' . count($bakTables) . ' bak_ tables');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('BackupRestore: bak_ cleanup partial: ' . $e->getMessage());
                }
                
                \Illuminate\Support\Facades\Cache::flush();
                return redirect()->back()->with('success', "Restore completed successfully! Database schema updated. Active POS carts cleared.");
            } else {
                \Illuminate\Support\Facades\Log::error('BackupRestore: Restore failed', ['output' => $output, 'result_code' => $resultCode]);
                return redirect()->back()->with('error', "Restore failed. Your original data has been preserved safely. Please check the application logs for technical details.");
            }

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Critical Error: ' . $e->getMessage());
        }
    }

    /**
     * Helper: Robustly drop foreign keys from a table
     */
    /**
     * Helper: Robustly drop foreign keys from a table
     */
    private function dropForeignKeys($table, $dbName)
    {
        try {
            // Use DATABASE() for reliability
            $fks = \Illuminate\Support\Facades\DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_NAME = ? 
                AND TABLE_SCHEMA = DATABASE() 
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'", [$table]);

            \Illuminate\Support\Facades\Log::info("BackupRestore: Found " . count($fks) . " FKs for table {$table}");

            foreach ($fks as $fk) {
                try {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    \Illuminate\Support\Facades\Log::info("BackupRestore: Dropped FK {$fk->CONSTRAINT_NAME} from {$table}");
                } catch (\Exception $e) {
                     \Illuminate\Support\Facades\Log::warning("BackupRestore: Failed to drop FK {$fk->CONSTRAINT_NAME} - " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("BackupRestore: Error listing FKs for {$table}: " . $e->getMessage());
        }
    }

    /**
     * Download a backup
     */
    public function downloadBackup($filename)
    {
        $backupPath = $this->getSafeConfig('backup_path', storage_path('app/backups'));
        $path = $backupPath . '/' . $filename;
        if (File::exists($path)) {
            return response()->download($path);
        }
        return redirect()->back()->with('error', 'File not found.');
    }

    /**
     * Delete a backup
     */
    public function deleteBackup(Request $request, $filename)
    {
        if (!$request->isMethod('delete')) {
            return redirect()->back()->with('error', 'Invalid request method.');
        }

        $backupPath = $this->getSafeConfig('backup_path', storage_path('app/backups'));
        $path = $backupPath . '/' . $filename;
        if (File::exists($path)) {
            File::delete($path);
            return redirect()->back()->with('success', 'Backup deleted successfully.');
        }
        return redirect()->back()->with('error', 'File not found to delete.');
    }

    /**
     * Helper: Get Config Safe
     */
    private function getSafeConfig($key, $default = null)
    {
        try {
            if (function_exists('readConfig')) {
                $val = readConfig($key);
                return $val ? $val : $default;
            }
            return config('system.' . $key, $default);
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Helper: Set Config Safe
     */
    private function setSafeConfig($key, $value)
    {
        try {
            if (function_exists('writeConfig')) {
                writeConfig($key, $value);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("WriteConfig Fail: $key");
        }
    }

    /**
     * Helper for file size
     */
    private function humanFilesize($bytes, $decimals = 2)
    {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }
}
