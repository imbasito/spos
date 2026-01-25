<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:create {--silent}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup using internal mysqldump';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting Backup...");

        $backupPath = storage_path('app/backups');
        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        // Always save as 'latest.sql' for the auto-backup, or timestamped if needed.
        // User requested: "silent backup... backup/latest.sql"
        if ($this->option('silent')) {
            $filename = 'latest.sql';
        } else {
            $filename = 'backup-' . date('Y-m-d-H-i-s') . '.sql';
        }
        
        $fullPath = $backupPath . '/' . $filename;

        // Database Config
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', '3306');
        
        // Resolve Binary Path
        $basePath = base_path();
        $mysqldump = $basePath . '\mysql\bin\mysqldump.exe';
        
        if (!File::exists($mysqldump)) {
             $mysqldump = 'mysqldump'; 
        } else {
             $mysqldump = '"' . $mysqldump . '"';
        }

        $passwordPart = $dbPass ? "--password=\"{$dbPass}\"" : "";
        $dumpCommand = "{$mysqldump} --user=\"{$dbUser}\" {$passwordPart} --host=\"{$dbHost}\" --port=\"{$dbPort}\" --protocol=tcp --single-transaction \"{$dbName}\" > \"{$fullPath}\" 2>&1";

        $output = [];
        $resultCode = null;
        exec($dumpCommand, $output, $resultCode);

        if ($resultCode === 0) {
            $this->info("Backup Successful: $fullPath");
            return 0;
        } else {
            $this->error("Backup Failed: " . implode("\n", $output));
            return 1;
        }
    }
}
