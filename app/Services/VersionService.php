<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class VersionService
{
    private string $stateFilePath;
    private string $installMetadataPath;

    public function __construct()
    {
        $this->stateFilePath = storage_path('app/system_state.json');
        $this->installMetadataPath = storage_path('app/install_metadata.json');
    }

    /**
     * Detect installation type: clean install or update
     * 
     * @return array{type: string, from_version: string|null, to_version: string, is_first_run: bool}
     */
    public function detectInstallationType(): array
    {
        $currentVersion = $this->getCurrentVersion();
        
        // Check if database exists and has data
        $hasDatabaseWithData = $this->hasDatabaseWithData();
        
        // Check if system state file exists
        $hasSystemState = File::exists($this->stateFilePath);
        
        // Check if activated_at marker file exists
        $hasActivationMarker = File::exists(storage_path('app/activated_at'));
        
        // Determine installation type
        if (!$hasDatabaseWithData && !$hasSystemState && !$hasActivationMarker) {
            // Clean install - no database, no state, no activation
            return [
                'type' => 'clean_install',
                'from_version' => null,
                'to_version' => $currentVersion,
                'is_first_run' => true
            ];
        }
        
        // Get previous version from state
        $previousVersion = $this->getPreviousVersion();
        
        if ($previousVersion && $previousVersion !== $currentVersion) {
            // Update detected - version changed
            return [
                'type' => 'update',
                'from_version' => $previousVersion,
                'to_version' => $currentVersion,
                'is_first_run' => false
            ];
        }
        
        if ($hasDatabaseWithData && !$hasSystemState) {
            // Existing installation without state file (legacy)
            return [
                'type' => 'existing_no_state',
                'from_version' => 'unknown',
                'to_version' => $currentVersion,
                'is_first_run' => false
            ];
        }
        
        // Normal run - same version
        return [
            'type' => 'normal',
            'from_version' => $previousVersion,
            'to_version' => $currentVersion,
            'is_first_run' => false
        ];
    }

    /**
     * Check if database exists and has data
     */
    private function hasDatabaseWithData(): bool
    {
        try {
            $dbPath = database_path('database.sqlite');
            
            if (!File::exists($dbPath)) {
                return false;
            }

            // Check if database has any tables
            if (!Schema::hasTable('migrations')) {
                return false;
            }

            // Check if migrations table has records
            $migrationCount = DB::table('migrations')->count();
            
            return $migrationCount > 0;

        } catch (\Exception $e) {
            Log::error("Failed to check database", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get current app version
     */
    public function getCurrentVersion(): string
    {
        // Try to get from config
        $version = config('app.version');
        
        if (!empty($version)) {
            return $version;
        }

        // Try to get from package.json
        $packageJsonPath = base_path('package.json');
        if (File::exists($packageJsonPath)) {
            $packageJson = json_decode(File::get($packageJsonPath), true);
            if (isset($packageJson['version'])) {
                return $packageJson['version'];
            }
        }

        return '1.0.0'; // Fallback
    }

    /**
     * Get previous version from system state
     */
    public function getPreviousVersion(): ?string
    {
        $state = $this->loadSystemState();
        return $state['installed_version'] ?? null;
    }

    /**
     * Load system state from file
     */
    public function loadSystemState(): array
    {
        if (!File::exists($this->stateFilePath)) {
            return [];
        }

        try {
            $content = File::get($this->stateFilePath);
            return json_decode($content, true) ?? [];
        } catch (\Exception $e) {
            Log::error("Failed to load system state", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Save system state to file
     */
    public function saveSystemState(array $state): bool
    {
        try {
            // Ensure directory exists
            $directory = dirname($this->stateFilePath);
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            // Merge with existing state
            $existingState = $this->loadSystemState();
            $mergedState = array_merge($existingState, $state);

            // Add timestamp
            $mergedState['updated_at'] = now()->toIso8601String();

            File::put($this->stateFilePath, json_encode($mergedState, JSON_PRETTY_PRINT));
            
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to save system state", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Update installed version in system state
     */
    public function updateInstalledVersion(string $version): bool
    {
        return $this->saveSystemState([
            'installed_version' => $version,
            'version_updated_at' => now()->toIso8601String()
        ]);
    }

    /**
     * Mark activation in system state
     */
    public function markActivated(string $licenseKey, string $licensedTo): bool
    {
        return $this->saveSystemState([
            'activated' => true,
            'activated_at' => now()->toIso8601String(),
            'license_key' => $licenseKey,
            'licensed_to' => $licensedTo
        ]);
    }

    /**
     * Check if system is activated from state
     */
    public function isActivated(): bool
    {
        $state = $this->loadSystemState();
        return $state['activated'] ?? false;
    }

    /**
     * Get activation info from state
     */
    public function getActivationInfo(): array
    {
        $state = $this->loadSystemState();
        
        return [
            'activated' => $state['activated'] ?? false,
            'activated_at' => $state['activated_at'] ?? null,
            'license_key' => $state['license_key'] ?? null,
            'licensed_to' => $state['licensed_to'] ?? null
        ];
    }

    /**
     * Mark migration as successful
     */
    public function markMigrationSuccess(): bool
    {
        return $this->saveSystemState([
            'last_migration_success' => true,
            'last_migration_at' => now()->toIso8601String(),
            'migration_failures' => 0
        ]);
    }

    /**
     * Mark migration as failed
     */
    public function markMigrationFailed(string $error): bool
    {
        $state = $this->loadSystemState();
        $failures = ($state['migration_failures'] ?? 0) + 1;

        return $this->saveSystemState([
            'last_migration_success' => false,
            'last_migration_at' => now()->toIso8601String(),
            'last_migration_error' => $error,
            'migration_failures' => $failures
        ]);
    }

    /**
     * Set update in progress flag
     */
    public function setUpdateInProgress(bool $inProgress): bool
    {
        return $this->saveSystemState([
            'update_in_progress' => $inProgress,
            'update_started_at' => $inProgress ? now()->toIso8601String() : null
        ]);
    }

    /**
     * Check if update is in progress
     */
    public function isUpdateInProgress(): bool
    {
        $state = $this->loadSystemState();
        return $state['update_in_progress'] ?? false;
    }

    /**
     * Clear update in progress flag
     */
    public function clearUpdateInProgress(): bool
    {
        return $this->saveSystemState([
            'update_in_progress' => false,
            'update_completed_at' => now()->toIso8601String()
        ]);
    }

    /**
     * Save installation metadata (for installer to write)
     */
    public function saveInstallationMetadata(array $metadata): bool
    {
        try {
            $data = array_merge([
                'installed_at' => now()->toIso8601String(),
                'install_type' => 'unknown'
            ], $metadata);

            File::put($this->installMetadataPath, json_encode($data, JSON_PRETTY_PRINT));
            
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to save installation metadata", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Load installation metadata
     */
    public function loadInstallationMetadata(): array
    {
        if (!File::exists($this->installMetadataPath)) {
            return [];
        }

        try {
            $content = File::get($this->installMetadataPath);
            return json_decode($content, true) ?? [];
        } catch (\Exception $e) {
            Log::error("Failed to load installation metadata", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get version comparison result
     * 
     * @param string $version1
     * @param string $version2
     * @return int Returns -1 if v1 < v2, 0 if equal, 1 if v1 > v2
     */
    public function compareVersions(string $version1, string $version2): int
    {
        return version_compare($version1, $version2);
    }

    /**
     * Check if this is a major version upgrade
     */
    public function isMajorUpgrade(?string $fromVersion, string $toVersion): bool
    {
        if (!$fromVersion) {
            return false;
        }

        $fromMajor = $this->getMajorVersion($fromVersion);
        $toMajor = $this->getMajorVersion($toVersion);

        return $toMajor > $fromMajor;
    }

    /**
     * Extract major version number
     */
    private function getMajorVersion(string $version): int
    {
        $parts = explode('.', $version);
        return (int) ($parts[0] ?? 0);
    }

    /**
     * Initialize system state on first run
     */
    public function initializeSystemState(): bool
    {
        $installType = $this->detectInstallationType();
        
        $initialState = [
            'installed_version' => $this->getCurrentVersion(),
            'installation_type' => $installType['type'],
            'initialized_at' => now()->toIso8601String(),
            'activated' => false,
            'last_migration_success' => false,
            'update_in_progress' => false,
            'migration_failures' => 0
        ];

        return $this->saveSystemState($initialState);
    }

    /**
     * Get system state summary
     */
    public function getSystemStateSummary(): array
    {
        $state = $this->loadSystemState();
        $installType = $this->detectInstallationType();

        return [
            'current_version' => $this->getCurrentVersion(),
            'installed_version' => $state['installed_version'] ?? 'unknown',
            'installation_type' => $installType['type'],
            'is_activated' => $state['activated'] ?? false,
            'update_in_progress' => $state['update_in_progress'] ?? false,
            'last_migration_success' => $state['last_migration_success'] ?? null,
            'migration_failures' => $state['migration_failures'] ?? 0,
            'state_file_exists' => File::exists($this->stateFilePath)
        ];
    }
}
