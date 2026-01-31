#!/usr/bin/env php
<?php

/**
 * Fresh Install Sanitization Script
 * 
 * Cleans all non-production files and data before installer build.
 * Ensures client gets clean, professional installation without developer artifacts.
 */

echo "\n🧹 SPOS FRESH INSTALL SANITIZER\n";
echo "================================\n\n";

// Define base directory
$basePath = __DIR__;

// ============================================
// 1. CLEANUP DEVELOPMENT FILES
// ============================================
echo "📁 Cleaning development files...\n";

$devFiles = [
    '.env.docker',
    '.env.example',
    '.gitignore',
    '.gitattributes',
    'README.md',
    'LICENSE.md',
    'CREDITS.md',
    'VERSION.md',
    'DEPLOYMENT_CHECKLIST.md',
    'PRODUCTION_READINESS_REPORT.md',
    'QUICK_DEPLOY_REFERENCE.md',
    'APPLICATION_ASSESSMENT_REPORT.md',
    'AUTO_UPDATE_GUIDE.md',
    'USER_DOCUMENTATION_INDEX.md',
    'USER_GUIDE_INSTALLATION.md',
    'USER_GUIDE_DAILY_OPERATIONS.md',
    'USER_GUIDE_TROUBLESHOOTING.md',
    'USER_GUIDE_BACKUP_RESTORE.md',
    '_TRASH_CLEANUP',
    'DOCS_PDF',
    'docker-compose.yml',
    'Dockerfile',
    'Dockerfile.node',
    'Makefile',
    'clear_and_build.bat',
    'install.bat',
    'run_dev.bat',
    'REFRESH_ALL.bat',
    'REFRESH_ALL.ps1',
    'POS.bat',
    'POS.vbs',
    'migrate_runner.ps1',
    'optimize_runner.ps1',
    'safe_runner.ps1',
    'PRE_DEPLOYMENT_CHECK.ps1',
    'crop_splash.py',
    'update_icons.py',
    'last_logs.txt',
    'phpunit.xml',
    'tests',
    'sample_products_import.csv',
];

foreach ($devFiles as $file) {
    $path = $basePath . DIRECTORY_SEPARATOR . $file;
    if (file_exists($path)) {
        if (is_dir($path)) {
            removeDirectory($path);
            echo "  ✓ Removed directory: $file\n";
        } else {
            unlink($path);
            echo "  ✓ Removed file: $file\n";
        }
    }
}

// ============================================
// 2. CLEAR DATABASE (FRESH SCHEMA ONLY)
// ============================================
echo "\n📊 Resetting database to fresh state...\n";

$dbPath = $basePath . '/database/database.sqlite';
if (file_exists($dbPath)) {
    unlink($dbPath);
    echo "  ✓ Removed existing database\n";
}

// Create fresh empty database
touch($dbPath);
echo "  ✓ Created fresh database file\n";

// ============================================
// 3. CLEAR ALL CACHES & LOGS
// ============================================
echo "\n🗑️  Clearing caches and logs...\n";

$clearDirs = [
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
];

foreach ($clearDirs as $dir) {
    $fullPath = $basePath . '/' . $dir;
    if (is_dir($fullPath)) {
        $files = glob($fullPath . '/*');
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== '.gitignore') {
                unlink($file);
            }
        }
        echo "  ✓ Cleared: $dir\n";
    }
}

// ============================================
// 4. RESET .ENV TO PRODUCTION DEFAULTS
// ============================================
echo "\n⚙️  Configuring production environment...\n";

$envPath = $basePath . '/.env';
$envContent = <<<ENV
APP_NAME="SPOS"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_WILL_BE_GENERATED
APP_DEBUG=false
APP_URL=http://127.0.0.1:8000

LOG_CHANNEL=daily
LOG_LEVEL=error
LOG_DEPRECATIONS_CHANNEL=null
LOG_DAILY_DAYS=7

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=10080

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="\${APP_NAME}"
VITE_PUSHER_APP_KEY="\${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="\${PUSHER_HOST}"
VITE_PUSHER_PORT="\${PUSHER_PORT}"
VITE_PUSHER_SCHEME="\${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="\${PUSHER_APP_CLUSTER}"
ENV;

file_put_contents($envPath, $envContent);
echo "  ✓ Reset .env to production defaults\n";

// ============================================
// 5. ENSURE PROPER PERMISSIONS
// ============================================
echo "\n🔒 Setting proper permissions...\n";

$writableDirs = [
    'storage',
    'storage/app',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
    'database',
];

foreach ($writableDirs as $dir) {
    $fullPath = $basePath . '/' . $dir;
    if (is_dir($fullPath)) {
        chmod($fullPath, 0755);
        echo "  ✓ Set writable: $dir\n";
    }
}

// ============================================
// 6. CREATE FIRST-RUN FLAG
// ============================================
echo "\n🚀 Setting up first-run activation...\n";

$flagPath = $basePath . '/storage/app/first_run_pending';
file_put_contents($flagPath, date('Y-m-d H:i:s'));
echo "  ✓ First-run flag created\n";

// ============================================
// HELPER FUNCTIONS
// ============================================
function removeDirectory($dir) {
    if (!file_exists($dir)) return;
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        is_dir($path) ? removeDirectory($path) : unlink($path);
    }
    rmdir($dir);
}

// ============================================
// COMPLETION
// ============================================
echo "\n✅ SANITIZATION COMPLETE!\n";
echo "================================\n";
echo "✓ Development files removed\n";
echo "✓ Database reset to fresh state\n";
echo "✓ Caches and logs cleared\n";
echo "✓ Production .env configured\n";
echo "✓ Permissions set correctly\n";
echo "✓ First-run activation enabled\n";
echo "\n📦 Ready to build installer!\n\n";

exit(0);
