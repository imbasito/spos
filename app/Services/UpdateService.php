<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UpdateService
{
    private string $backupPath;
    private string $databasePath;
    private string $configPath;

    public function __construct()
    {
        $this->backupPath = storage_path('app/backups');
        $this->databasePath = database_path('database.sqlite');
        $this->configPath = base_path('config');
        
        // Ensure backup directory exists
        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    /**
     * Create a complete backup before update
     * 
     * @return array{success: bool, backup_id: string|null, message: string, paths: array}
     */
    public function createBackup(): array
    {
        try {
            $backupId = date('Y-m-d_His') . '_v' . config('app.version', '1.0.0');
            $backupDir = $this->backupPath . '/' . $backupId;

            // Create backup directory
            if (!File::makeDirectory($backupDir, 0755, true)) {
                throw new \Exception("Failed to create backup directory: {$backupDir}");
            }

            $backupPaths = [];

            // 1. Backup database
            $dbBackupPath = $this->backupDatabase($backupDir);
            if ($dbBackupPath) {
                $backupPaths['database'] = $dbBackupPath;
            }

            // 2. Backup config directory
            $configBackupPath = $this->backupConfig($backupDir);
            if ($configBackupPath) {
                $backupPaths['config'] = $configBackupPath;
            }

            // 3. Backup system state
            $stateBackupPath = $this->backupSystemState($backupDir);
            if ($stateBackupPath) {
                $backupPaths['state'] = $stateBackupPath;
            }

            // 4. Create backup manifest
            $this->createBackupManifest($backupDir, $backupPaths);

            // 5. Verify backup integrity
            if (!$this->verifyBackupIntegrity($backupDir, $backupPaths)) {
                throw new \Exception("Backup integrity verification failed");
            }

            // 6. Prune old backups (keep last 10)
            $this->pruneOldBackups(10);

            Log::info("Backup created successfully", ['backup_id' => $backupId, 'paths' => $backupPaths]);

            return [
                'success' => true,
                'backup_id' => $backupId,
                'message' => "Backup created successfully",
                'paths' => $backupPaths
            ];

        } catch (\Exception $e) {
            Log::error("Backup creation failed", ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'backup_id' => null,
                'message' => "Backup failed: " . $e->getMessage(),
                'paths' => []
            ];
        }
    }

    /**
     * Backup database with checksum (supports both SQLite and MySQL)
     */
    private function backupDatabase(string $backupDir): ?string
    {
        try {
            $dbConnection = config('database.default');
            
            // MySQL backup using mysqldump
            if ($dbConnection === 'mysql') {
                $dbHost = config('database.connections.mysql.host');
                $dbPort = config('database.connections.mysql.port');
                $dbName = config('database.connections.mysql.database');
                $dbUser = config('database.connections.mysql.username');
                $dbPass = config('database.connections.mysql.password');
                
                $mysqlDumpPath = base_path('mysql/bin/mysqldump.exe');
                $dbBackupPath = $backupDir . '/database.sql';
                
                if (!File::exists($mysqlDumpPath)) {
                    Log::error("mysqldump not found at: {$mysqlDumpPath}");
                    return null;
                }
                
                // Build mysqldump command
                $command = sprintf(
                    '"%s" -h %s -P %s -u %s --password="%s" --protocol=TCP --single-transaction --routines --triggers %s > "%s" 2>&1',
                    $mysqlDumpPath,
                    escapeshellarg($dbHost),
                    escapeshellarg($dbPort),
                    escapeshellarg($dbUser),
                    $dbPass,
                    escapeshellarg($dbName),
                    $dbBackupPath
                );
                
                exec($command, $output, $returnCode);
                
                if ($returnCode !== 0) {
                    Log::error("MySQL backup failed", ['output' => implode("\n", $output)]);
                    throw new \Exception("mysqldump failed with code {$returnCode}");
                }
                
                // Create checksum
                $checksum = md5_file($dbBackupPath);
                File::put($dbBackupPath . '.md5', $checksum);
                
                Log::info("MySQL database backed up successfully", ['size' => filesize($dbBackupPath)]);
                return $dbBackupPath;
            }
            
            // SQLite backup (original logic)
            if (!File::exists($this->databasePath)) {
                Log::warning("Database file not found: {$this->databasePath}");
                return null;
            }

            $dbBackupPath = $backupDir . '/database.sqlite';
            
            // Copy database file
            if (!File::copy($this->databasePath, $dbBackupPath)) {
                throw new \Exception("Failed to copy database");
            }

            // Create checksum
            $checksum = md5_file($dbBackupPath);
            File::put($dbBackupPath . '.md5', $checksum);

            return $dbBackupPath;

        } catch (\Exception $e) {
            Log::error("Database backup failed", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Backup config directory
     */
    private function backupConfig(string $backupDir): ?string
    {
        try {
            $configBackupPath = $backupDir . '/config';
            
            if (!File::copyDirectory($this->configPath, $configBackupPath)) {
                throw new \Exception("Failed to copy config directory");
            }

            return $configBackupPath;

        } catch (\Exception $e) {
            Log::error("Config backup failed", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Backup system state file
     */
    private function backupSystemState(string $backupDir): ?string
    {
        try {
            $stateFile = storage_path('app/system_state.json');
            
            if (!File::exists($stateFile)) {
                // State file doesn't exist yet, create empty one
                return null;
            }

            $stateBackupPath = $backupDir . '/system_state.json';
            
            if (!File::copy($stateFile, $stateBackupPath)) {
                throw new \Exception("Failed to copy system state");
            }

            return $stateBackupPath;

        } catch (\Exception $e) {
            Log::error("System state backup failed", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create backup manifest with metadata
     */
    private function createBackupManifest(string $backupDir, array $backupPaths): void
    {
        $manifest = [
            'created_at' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'backup_paths' => $backupPaths,
            'file_sizes' => []
        ];

        // Add file sizes
        foreach ($backupPaths as $key => $path) {
            if (File::exists($path)) {
                $manifest['file_sizes'][$key] = File::size($path);
            }
        }

        File::put($backupDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
    }

    /**
     * Verify backup integrity using checksums
     */
    private function verifyBackupIntegrity(string $backupDir, array $backupPaths): bool
    {
        try {
            // Verify database backup if exists
            if (isset($backupPaths['database']) && File::exists($backupPaths['database'])) {
                $checksumFile = $backupPaths['database'] . '.md5';
                
                if (!File::exists($checksumFile)) {
                    Log::warning("Database checksum file not found");
                    return false;
                }

                $expectedChecksum = File::get($checksumFile);
                $actualChecksum = md5_file($backupPaths['database']);

                if ($expectedChecksum !== $actualChecksum) {
                    Log::error("Database backup checksum mismatch");
                    return false;
                }
            }

            // Verify manifest exists
            if (!File::exists($backupDir . '/manifest.json')) {
                Log::warning("Backup manifest not found");
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error("Backup verification failed", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Prune old backups to avoid disk bloat
     */
    private function pruneOldBackups(int $keepCount = 10): void
    {
        try {
            $backupDirs = File::directories($this->backupPath);
            rsort($backupDirs); // newest first by name

            if (count($backupDirs) <= $keepCount) {
                return;
            }

            $dirsToDelete = array_slice($backupDirs, $keepCount);
            foreach ($dirsToDelete as $dir) {
                File::deleteDirectory($dir);
                Log::info("Pruned old backup", ['path' => $dir]);
            }
        } catch (\Exception $e) {
            Log::warning("Backup pruning failed", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Restore from backup
     * 
     * @param string $backupId
     * @return array{success: bool, message: string}
     */
    public function restoreBackup(string $backupId): array
    {
        try {
            $backupDir = $this->backupPath . '/' . $backupId;

            if (!File::exists($backupDir)) {
                throw new \Exception("Backup not found: {$backupId}");
            }

            // Load manifest
            $manifestPath = $backupDir . '/manifest.json';
            if (!File::exists($manifestPath)) {
                throw new \Exception("Backup manifest not found");
            }

            $manifest = json_decode(File::get($manifestPath), true);

            // Verify backup integrity before restore
            if (!$this->verifyBackupIntegrity($backupDir, $manifest['backup_paths'])) {
                throw new \Exception("Backup integrity check failed");
            }

            // Restore database
            if (isset($manifest['backup_paths']['database'])) {
                $this->restoreDatabase($manifest['backup_paths']['database']);
            }

            // Restore config
            if (isset($manifest['backup_paths']['config'])) {
                $this->restoreConfig($manifest['backup_paths']['config']);
            }

            // Restore system state
            if (isset($manifest['backup_paths']['state'])) {
                $this->restoreSystemState($manifest['backup_paths']['state']);
            }

            Log::info("Backup restored successfully", ['backup_id' => $backupId]);

            return [
                'success' => true,
                'message' => "Backup restored successfully from {$backupId}"
            ];

        } catch (\Exception $e) {
            Log::error("Backup restoration failed", ['error' => $e->getMessage(), 'backup_id' => $backupId]);
            
            return [
                'success' => false,
                'message' => "Restore failed: " . $e->getMessage()
            ];
        }
    }

    /**
     * Restore database from backup
     */
    private function restoreDatabase(string $backupPath): void
    {
        if (!File::exists($backupPath)) {
            throw new \Exception("Database backup not found: {$backupPath}");
        }

        // Close database connections
        DB::disconnect();

        // Backup current database before overwrite
        $currentBackup = $this->databasePath . '.before_restore';
        if (File::exists($this->databasePath)) {
            File::copy($this->databasePath, $currentBackup);
        }

        // Restore database
        if (!File::copy($backupPath, $this->databasePath)) {
            // Restore the backup if copy failed
            if (File::exists($currentBackup)) {
                File::copy($currentBackup, $this->databasePath);
            }
            throw new \Exception("Failed to restore database");
        }

        // Reconnect
        DB::reconnect();
    }

    /**
     * Restore config from backup
     */
    private function restoreConfig(string $backupPath): void
    {
        if (!File::exists($backupPath)) {
            throw new \Exception("Config backup not found: {$backupPath}");
        }

        // Remove current config (but keep a backup)
        $configBackup = $this->configPath . '_before_restore';
        if (File::exists($this->configPath)) {
            File::copyDirectory($this->configPath, $configBackup);
            File::deleteDirectory($this->configPath);
        }

        // Restore config
        if (!File::copyDirectory($backupPath, $this->configPath)) {
            // Restore the backup if copy failed
            if (File::exists($configBackup)) {
                File::copyDirectory($configBackup, $this->configPath);
            }
            throw new \Exception("Failed to restore config");
        }
    }

    /**
     * Restore system state from backup
     */
    private function restoreSystemState(string $backupPath): void
    {
        if (!File::exists($backupPath)) {
            return; // State file is optional
        }

        $stateFile = storage_path('app/system_state.json');
        
        if (!File::copy($backupPath, $stateFile)) {
            throw new \Exception("Failed to restore system state");
        }
    }

    /**
     * List available backups
     * 
     * @return array
     */
    public function listBackups(): array
    {
        try {
            $backups = [];
            $directories = File::directories($this->backupPath);

            foreach ($directories as $dir) {
                $manifestPath = $dir . '/manifest.json';
                
                if (File::exists($manifestPath)) {
                    $manifest = json_decode(File::get($manifestPath), true);
                    $backups[] = [
                        'id' => basename($dir),
                        'created_at' => $manifest['created_at'] ?? null,
                        'version' => $manifest['version'] ?? 'unknown',
                        'size' => array_sum($manifest['file_sizes'] ?? []),
                        'path' => $dir
                    ];
                }
            }

            // Sort by created_at descending
            usort($backups, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            return $backups;

        } catch (\Exception $e) {
            Log::error("Failed to list backups", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Clean old backups (keep last N backups)
     * 
     * @param int $keep Number of backups to keep
     * @return int Number of backups deleted
     */
    public function cleanOldBackups(int $keep = 5): int
    {
        try {
            $backups = $this->listBackups();
            $deleted = 0;

            // Keep only the specified number of recent backups
            $toDelete = array_slice($backups, $keep);

            foreach ($toDelete as $backup) {
                if (File::deleteDirectory($backup['path'])) {
                    $deleted++;
                    Log::info("Deleted old backup", ['backup_id' => $backup['id']]);
                }
            }

            return $deleted;

        } catch (\Exception $e) {
            Log::error("Failed to clean old backups", ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Check if there's enough disk space for backup
     * 
     * @param int $requiredMB Required space in MB
     * @return bool
     */
    public function hasEnoughDiskSpace(int $requiredMB = 500): bool
    {
        try {
            $freeSpace = disk_free_space(storage_path());
            $requiredBytes = $requiredMB * 1024 * 1024;

            return $freeSpace >= $requiredBytes;

        } catch (\Exception $e) {
            Log::error("Failed to check disk space", ['error' => $e->getMessage()]);
            return false;
        }
    }
}
