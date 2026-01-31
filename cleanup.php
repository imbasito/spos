<?php
/**
 * SPOS - Build Sanitization Script
 * This script ensures no developer session data or logs are shipped with the production build.
 */

$root = __DIR__;

echo "--- Starting SPOS Sanitization ---\n";

// 1. Clear Sessions
$sessionPath = $root . '/storage/framework/sessions';
if (is_dir($sessionPath)) {
    $files = glob($sessionPath . '/*');
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== '.gitignore') {
            unlink($file);
            echo "Deleted session: " . basename($file) . "\n";
        }
    }
}

// 2. Clear Logs
$logPath = $root . '/storage/logs';
if (is_dir($logPath)) {
    $files = glob($logPath . '/*.log');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            echo "Deleted log: " . basename($file) . "\n";
        }
    }
}

// 3. Reset License (Safety Check)
$systemConfigPath = $root . '/config/system.php';
if (file_exists($systemConfigPath)) {
    $config = include $systemConfigPath;
    $config['license_key'] = '';
    $config['licensed_to'] = '';
    
    $content = "<?php return " . var_export($config, true) . ";";
    file_put_contents($systemConfigPath, $content);
    echo "License configuration reset.\n";
}

// 4. Ensure Critical Directories Exist (Safety for Build)
$dirsToEnsure = [
    $root . '/storage/framework/sessions',
    $root . '/storage/framework/cache',
    $root . '/storage/framework/views',
    $root . '/storage/logs',
];

foreach ($dirsToEnsure as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created missing directory: $dir\n";
    }
    // Also ensure a .gitignore exists in each to prevent future pruning
    $gitignoreFile = $dir . '/.gitignore';
    if (!file_exists($gitignoreFile)) {
        file_put_contents($gitignoreFile, "*\n!.gitignore\n");
        echo "Created .gitignore in: $dir\n";
    }
}

// 5. Clear Laravel Cache
echo "Clearing application cache...\n";
$phpPath = $root . '/php/php.exe';
if (file_exists($phpPath)) {
    @shell_exec('"' . $phpPath . '" artisan optimize:clear');
} else {
    @shell_exec('php artisan optimize:clear');
}

// 6. Reset Database (remove test data)
$dbPath = $root . '/database/database.sqlite';
if (file_exists($dbPath)) {
    unlink($dbPath);
    echo "Deleted test database: database.sqlite\n";
    // Create fresh empty database
    touch($dbPath);
    echo "Created fresh database.sqlite\n";
}

// 7. Clear MySQL data folder (if exists in dev)
$mysqlDataPath = $root . '/mysql/data';
if (is_dir($mysqlDataPath)) {
    echo "MySQL data folder exists - will be freshly initialized on first run\n";
}

// 8. Copy Production .env
$envProductionPath = $root . '/.env.production';
$envPath = $root . '/.env';
if (file_exists($envProductionPath)) {
    copy($envProductionPath, $envPath);
    echo "Copied .env.production to .env\n";
}

echo "--- Sanitization Complete ---\n";
echo "Ready for production build.\n";
