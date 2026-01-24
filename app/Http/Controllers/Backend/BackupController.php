<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
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

        $backups = [];
        try {
            $files = glob($backupPath . '/*.sql');
            if ($files) {
                foreach ($files as $file) {
                    $backups[] = [
                        'filename' => basename($file),
                        'path' => $file,
                        'size' => $this->humanFilesize(filesize($file)),
                        'date' => date('Y-m-d H:i:s', filemtime($file)),
                        'timestamp' => filemtime($file)
                    ];
                }
                // Sort by newest first
                usort($backups, function($a, $b) {
                    return $b['timestamp'] - $a['timestamp'];
                });
            }
        } catch (\Exception $e) {
            // Log error but show empty list
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
    public function restoreBackup($filename)
    {
        try {
            $backupPath = $this->getSafeConfig('backup_path', storage_path('app/backups'));
            $fullPath = $backupPath . '/' . $filename;

            if (!File::exists($fullPath)) {
                return redirect()->back()->with('error', 'Backup file not found.');
            }

            // Database Config
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');
            $dbHost = config('database.connections.mysql.host', '127.0.0.1');
            $dbPort = config('database.connections.mysql.port', '3306');

            // Resolve Binary Path - STRICT WINDOWS PATHS
            $basePath = base_path();
            // Force backslashes for Windows
            $mysqlBin = $basePath . '\mysql\bin\mysql.exe';
            
            // Normalize path just in case
            $mysqlBin = str_replace('/', '\\', $mysqlBin);

            if (!File::exists($mysqlBin)) {
                // If strictly not found, try to assume it's there anyway or fall back
                // But better to log this anomaly
                \Illuminate\Support\Facades\Log::warning("MySQL binary not found at checked path: $mysqlBin");
                $mysql = 'mysql';
            } else {
                $mysql = '"' . $mysqlBin . '"';
            }

            // Restore Command
            // IMPORTANT: No space between -p and password if used, but --password= works better
            $passwordPart = $dbPass ? "--password=\"{$dbPass}\"" : "";
            
            // On some Windows systems, 127.0.0.1 needs explicit protocol
            $restoreCommand = "{$mysql} --user=\"{$dbUser}\" {$passwordPart} --host=\"{$dbHost}\" --port=\"{$dbPort}\" --protocol=tcp \"{$dbName}\" < \"{$fullPath}\" 2>&1";
            
            $output = [];
            $resultCode = null;
            exec($restoreCommand, $output, $resultCode);

            if ($resultCode === 0) {
                return redirect()->back()->with('success', "System Restored from: {$filename}");
            } else {
                \Illuminate\Support\Facades\Log::error("Restore Fail Output: " . implode("\n", $output));
                
                // Friendly Error Message
                $err = implode(" ", $output);
                if (str_contains($err, 'Using a password')) {
                    // This is just a warning, maybe the error is separate
                     return redirect()->back()->with('error', "Restore process finished with warnings. Check database data.");
                }
                
                return redirect()->back()->with('error', "Restore Failed: " . substr($err, 0, 150) . "...");
            }

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Restore Error: ' . $e->getMessage());
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
    public function deleteBackup($filename)
    {
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
