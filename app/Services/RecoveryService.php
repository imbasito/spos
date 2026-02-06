<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use ZipArchive;

class RecoveryService
{
    private UpdateService $updateService;
    private VersionService $versionService;
    private HealthCheckService $healthCheckService;

    public function __construct(
        UpdateService $updateService,
        VersionService $versionService,
        HealthCheckService $healthCheckService
    ) {
        $this->updateService = $updateService;
        $this->versionService = $versionService;
        $this->healthCheckService = $healthCheckService;
    }

    /**
     * Attempt automatic recovery from failed update/migration
     * 
     * @return array{success: bool, action_taken: string, message: string}
     */
    public function attemptAutoRecovery(): array
    {
        Log::info("Attempting automatic recovery");

        // Check if update was in progress
        if ($this->versionService->isUpdateInProgress()) {
            return $this->recoverFromFailedUpdate();
        }

        // Check migration failures
        $state = $this->versionService->loadSystemState();
        $migrationFailures = $state['migration_failures'] ?? 0;

        if ($migrationFailures > 0 && $migrationFailures < 3) {
            // Try to re-run migrations with cache clearing
            return $this->retryMigrations();
        }

        if ($migrationFailures >= 3) {
            // Too many failures, suggest rollback
            return [
                'success' => false,
                'action_taken' => 'none',
                'message' => 'Multiple migration failures detected. Please use rollback or contact support.',
                'suggested_action' => 'rollback'
            ];
        }

        return [
            'success' => false,
            'action_taken' => 'none',
            'message' => 'No recovery action needed'
        ];
    }

    /**
     * Recover from failed update
     */
    private function recoverFromFailedUpdate(): array
    {
        Log::info("Recovering from failed update");

        try {
            // Get available backups
            $backups = $this->updateService->listBackups();

            if (empty($backups)) {
                return [
                    'success' => false,
                    'action_taken' => 'none',
                    'message' => 'No backups available for recovery'
                ];
            }

            // Get the most recent backup
            $latestBackup = $backups[0];

            // Restore from backup
            $restoreResult = $this->updateService->restoreBackup($latestBackup['id']);

            if ($restoreResult['success']) {
                // Clear the update in progress flag
                $this->versionService->clearUpdateInProgress();
                
                // Reset migration failures
                $this->versionService->saveSystemState(['migration_failures' => 0]);

                return [
                    'success' => true,
                    'action_taken' => 'rollback',
                    'message' => "Restored from backup: {$latestBackup['id']}",
                    'backup_version' => $latestBackup['version']
                ];
            }

            return [
                'success' => false,
                'action_taken' => 'rollback_failed',
                'message' => $restoreResult['message']
            ];

        } catch (\Exception $e) {
            Log::error("Failed to recover from update", ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'action_taken' => 'recovery_error',
                'message' => 'Recovery failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Retry migrations with cache clearing
     */
    private function retryMigrations(): array
    {
        Log::info("Retrying migrations with cache clearing");

        try {
            // Clear all caches
            $this->clearAllCaches();

            // Run migrations
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            // Check for errors
            if (str_contains($output, 'error') || str_contains($output, 'failed')) {
                throw new \Exception("Migration retry failed: " . $output);
            }

            // Mark as successful
            $this->versionService->markMigrationSuccess();

            return [
                'success' => true,
                'action_taken' => 'retry_migration',
                'message' => 'Migrations retried successfully',
                'output' => $output
            ];

        } catch (\Exception $e) {
            // Increment failure count
            $this->versionService->markMigrationFailed($e->getMessage());

            Log::error("Migration retry failed", ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'action_taken' => 'retry_failed',
                'message' => 'Migration retry failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Clear all application caches
     */
    private function clearAllCaches(): void
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            
            // Clear permission cache if Spatie is installed
            try {
                Artisan::call('permission:cache-reset');
            } catch (\Exception $e) {
                // Ignore if permission package not installed
            }

            Log::info("All caches cleared successfully");

        } catch (\Exception $e) {
            Log::warning("Cache clearing encountered errors", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Rollback to last known good state
     * 
     * @param string|null $backupId Specific backup to rollback to, or null for latest
     * @return array{success: bool, message: string}
     */
    public function rollbackToLastGoodState(?string $backupId = null): array
    {
        try {
            Log::info("Initiating rollback to last good state", ['backup_id' => $backupId]);

            $backups = $this->updateService->listBackups();

            if (empty($backups)) {
                return [
                    'success' => false,
                    'message' => 'No backups available for rollback'
                ];
            }

            // Use specified backup or latest
            if ($backupId) {
                $backup = collect($backups)->firstWhere('id', $backupId);
                if (!$backup) {
                    return [
                        'success' => false,
                        'message' => "Backup not found: {$backupId}"
                    ];
                }
            } else {
                $backup = $backups[0]; // Most recent
            }

            // Restore backup
            $result = $this->updateService->restoreBackup($backup['id']);

            if ($result['success']) {
                // Clear update flags
                $this->versionService->clearUpdateInProgress();
                $this->versionService->saveSystemState([
                    'migration_failures' => 0,
                    'last_rollback_at' => now()->toIso8601String(),
                    'rollback_from_version' => $this->versionService->getCurrentVersion(),
                    'rollback_to_version' => $backup['version']
                ]);

                // Clear caches after rollback
                $this->clearAllCaches();
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("Rollback failed", ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'message' => 'Rollback failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Export diagnostic bundle for support
     * 
     * @return array{success: bool, file_path: string|null, message: string}
     */
    public function exportDiagnosticBundle(): array
    {
        try {
            $timestamp = date('Y-m-d_His');
            $bundleName = "diagnostic_bundle_{$timestamp}.zip";
            $bundlePath = storage_path("app/diagnostics/{$bundleName}");

            // Ensure diagnostics directory exists
            $diagnosticsDir = storage_path('app/diagnostics');
            if (!File::exists($diagnosticsDir)) {
                File::makeDirectory($diagnosticsDir, 0755, true);
            }

            // Create ZIP archive
            $zip = new ZipArchive();
            if ($zip->open($bundlePath, ZipArchive::CREATE) !== true) {
                throw new \Exception("Failed to create ZIP archive");
            }

            // Add system state
            $this->addSystemStateToZip($zip);

            // Add logs
            $this->addLogsToZip($zip);

            // Add health check results
            $this->addHealthCheckToZip($zip);

            // Add database structure (no data)
            $this->addDatabaseStructureToZip($zip);

            // Add config summary
            $this->addConfigSummaryToZip($zip);

            // Add system info
            $this->addSystemInfoToZip($zip);

            $zip->close();

            Log::info("Diagnostic bundle created", ['path' => $bundlePath]);

            return [
                'success' => true,
                'file_path' => $bundlePath,
                'message' => "Diagnostic bundle created: {$bundleName}",
                'size' => File::size($bundlePath)
            ];

        } catch (\Exception $e) {
            Log::error("Failed to create diagnostic bundle", ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'file_path' => null,
                'message' => 'Failed to create diagnostic bundle: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Add system state to diagnostic ZIP
     */
    private function addSystemStateToZip(ZipArchive $zip): void
    {
        $state = $this->versionService->loadSystemState();
        $summary = $this->versionService->getSystemStateSummary();
        
        $data = [
            'state' => $state,
            'summary' => $summary,
            'timestamp' => now()->toIso8601String()
        ];

        $zip->addFromString('system_state.json', json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Add recent logs to diagnostic ZIP
     */
    private function addLogsToZip(ZipArchive $zip): void
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (File::exists($logPath)) {
            // Get last 5000 lines
            $lines = file($logPath);
            $recentLines = array_slice($lines, -5000);
            $zip->addFromString('logs/laravel_recent.log', implode('', $recentLines));
        }
    }

    /**
     * Add health check results to diagnostic ZIP
     */
    private function addHealthCheckToZip(ZipArchive $zip): void
    {
        $healthCheck = $this->healthCheckService->runAllChecks();
        $zip->addFromString('health_check.json', json_encode($healthCheck, JSON_PRETTY_PRINT));
    }

    /**
     * Add database structure to diagnostic ZIP
     */
    private function addDatabaseStructureToZip(ZipArchive $zip): void
    {
        try {
            // Get table list
            $tables = \DB::select("SELECT name FROM sqlite_master WHERE type='table'");
            
            $structure = [];
            foreach ($tables as $table) {
                $tableName = $table->name;
                // Get table schema
                $schema = \DB::select("PRAGMA table_info({$tableName})");
                $structure[$tableName] = $schema;
            }

            $zip->addFromString('database/structure.json', json_encode($structure, JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            Log::warning("Failed to add database structure to diagnostic bundle", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Add config summary to diagnostic ZIP
     */
    private function addConfigSummaryToZip(ZipArchive $zip): void
    {
        $configSummary = [
            'app_name' => config('app.name'),
            'app_version' => config('app.version'),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'database_connection' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version()
        ];

        $zip->addFromString('config_summary.json', json_encode($configSummary, JSON_PRETTY_PRINT));
    }

    /**
     * Add system info to diagnostic ZIP
     */
    private function addSystemInfoToZip(ZipArchive $zip): void
    {
        $systemInfo = [
            'os' => PHP_OS,
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'disk_free_space' => disk_free_space(storage_path()),
            'disk_total_space' => disk_total_space(storage_path()),
            'timestamp' => now()->toIso8601String()
        ];

        $zip->addFromString('system_info.json', json_encode($systemInfo, JSON_PRETTY_PRINT));
    }

    /**
     * Reset to factory defaults (with confirmation)
     * WARNING: This will delete all data
     * 
     * @param bool $confirmed Must be true to proceed
     * @return array{success: bool, message: string}
     */
    public function resetToFactoryDefaults(bool $confirmed = false): array
    {
        if (!$confirmed) {
            return [
                'success' => false,
                'message' => 'Factory reset requires confirmation. All data will be lost.'
            ];
        }

        try {
            Log::warning("Factory reset initiated");

            // Create emergency backup first
            $backup = $this->updateService->createBackup();
            
            if (!$backup['success']) {
                return [
                    'success' => false,
                    'message' => 'Cannot proceed: Failed to create backup'
                ];
            }

            // Run migrations fresh (drops all tables and recreates)
            Artisan::call('migrate:fresh', ['--force' => true]);

            // Clear system state
            $stateFile = storage_path('app/system_state.json');
            if (File::exists($stateFile)) {
                File::delete($stateFile);
            }

            // Clear activation marker
            $activationMarker = storage_path('app/activated_at');
            if (File::exists($activationMarker)) {
                File::delete($activationMarker);
            }

            // Clear all caches
            $this->clearAllCaches();

            // Initialize fresh system state
            $this->versionService->initializeSystemState();

            Log::warning("Factory reset completed", ['backup_id' => $backup['backup_id']]);

            return [
                'success' => true,
                'message' => 'System reset to factory defaults. Backup created: ' . $backup['backup_id']
            ];

        } catch (\Exception $e) {
            Log::error("Factory reset failed", ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'message' => 'Factory reset failed: ' . $e->getMessage()
            ];
        }
    }
}
