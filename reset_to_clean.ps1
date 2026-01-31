# ====================================================================
# SPOS Complete Reset Script
# Resets the application to a clean state for first-time installation
# ====================================================================

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  SPOS Complete Clean Reset" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

$rootPath = $PSScriptRoot

# 1. Stop any running processes
Write-Host "[1/8] Stopping any running SPOS processes..." -ForegroundColor Yellow
Get-Process | Where-Object {$_.ProcessName -match "php|mysql|POS"} | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 2
Write-Host "✓ Processes stopped" -ForegroundColor Green

# 2. Clear Laravel cache and logs
Write-Host "[2/8] Clearing Laravel cache and logs..." -ForegroundColor Yellow
if (Test-Path "$rootPath\bootstrap\cache") {
    Get-ChildItem "$rootPath\bootstrap\cache" -Exclude ".gitignore" | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
}
if (Test-Path "$rootPath\storage\framework\cache\data") {
    Get-ChildItem "$rootPath\storage\framework\cache\data" -Recurse | Remove-Item -Force -ErrorAction SilentlyContinue
}
if (Test-Path "$rootPath\storage\framework\sessions") {
    Get-ChildItem "$rootPath\storage\framework\sessions" -Exclude ".gitignore" | Remove-Item -Force -ErrorAction SilentlyContinue
}
if (Test-Path "$rootPath\storage\framework\views") {
    Get-ChildItem "$rootPath\storage\framework\views" -Exclude ".gitignore" | Remove-Item -Force -ErrorAction SilentlyContinue
}
if (Test-Path "$rootPath\storage\logs") {
    Get-ChildItem "$rootPath\storage\logs" -Exclude ".gitignore" | Remove-Item -Force -ErrorAction SilentlyContinue
}
Write-Host "✓ Laravel cache cleared" -ForegroundColor Green

# 3. Delete database file
Write-Host "[3/8] Removing database..." -ForegroundColor Yellow
if (Test-Path "$rootPath\database\database.sqlite") {
    Remove-Item "$rootPath\database\database.sqlite" -Force
    Write-Host "✓ Database removed" -ForegroundColor Green
} else {
    Write-Host "✓ No database found (already clean)" -ForegroundColor Green
}

# Remove activation markers and create first-run flag
Remove-Item "$rootPath\storage\app\activated_at" -Force -ErrorAction SilentlyContinue
New-Item "$rootPath\storage\app\first_run_pending" -ItemType File -Force | Out-Null
Write-Host "✓ Activation markers reset" -ForegroundColor Green

# 4. Clear MySQL data (if embedded MySQL is used)
Write-Host "[4/8] Clearing MySQL data..." -ForegroundColor Yellow
if (Test-Path "$rootPath\mysql\data") {
    $mysqlDataDirs = Get-ChildItem "$rootPath\mysql\data" -Directory -Exclude "performance_schema", "mysql", "sys"
    foreach ($dir in $mysqlDataDirs) {
        if ($dir.Name -ne "spos_pos") { continue }
        Remove-Item $dir.FullName -Recurse -Force -ErrorAction SilentlyContinue
        Write-Host "  ✓ Removed database: $($dir.Name)" -ForegroundColor Gray
    }
}
Write-Host "✓ MySQL data cleared" -ForegroundColor Green

# 5. Clear node_modules cache
Write-Host "[5/8] Clearing Node.js cache..." -ForegroundColor Yellow
if (Test-Path "$rootPath\node_modules\.cache") {
    Remove-Item "$rootPath\node_modules\.cache" -Recurse -Force -ErrorAction SilentlyContinue
}
if (Test-Path "$rootPath\.vite") {
    Remove-Item "$rootPath\.vite" -Recurse -Force -ErrorAction SilentlyContinue
}
Write-Host "✓ Node cache cleared" -ForegroundColor Green

# 6. Clear Electron build artifacts
Write-Host "[6/8] Clearing Electron build artifacts..." -ForegroundColor Yellow
if (Test-Path "$rootPath\out") {
    Remove-Item "$rootPath\out" -Recurse -Force -ErrorAction SilentlyContinue
}
if (Test-Path "$rootPath\dist") {
    Remove-Item "$rootPath\dist" -Recurse -Force -ErrorAction SilentlyContinue
}
if (Test-Path "$rootPath\build\portable") {
    Remove-Item "$rootPath\build\portable" -Recurse -Force -ErrorAction SilentlyContinue
}
Write-Host "✓ Build artifacts cleared" -ForegroundColor Green

# 7. Clear temp and uploaded files
Write-Host "[7/8] Clearing temporary files..." -ForegroundColor Yellow
if (Test-Path "$rootPath\storage\app\public") {
    Get-ChildItem "$rootPath\storage\app\public" -Exclude ".gitignore" | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
}
if (Test-Path "$rootPath\public\storage") {
    Get-ChildItem "$rootPath\public\storage" -Exclude ".gitignore" | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
}
if (Test-Path "$rootPath\mysql\tmp") {
    Get-ChildItem "$rootPath\mysql\tmp" | Remove-Item -Force -ErrorAction SilentlyContinue
}
Write-Host "✓ Temp files cleared" -ForegroundColor Green

# 8. Reset .env to defaults (keep APP_KEY if exists)
Write-Host "[8/8] Verifying .env configuration..." -ForegroundColor Yellow
if (Test-Path "$rootPath\.env") {
    $envContent = Get-Content "$rootPath\.env" -Raw
    if ($envContent -match "APP_KEY=base64:([A-Za-z0-9+/=]+)") {
        $appKey = $matches[1]
        Write-Host "✓ APP_KEY preserved" -ForegroundColor Green
    } else {
        Write-Host "⚠ APP_KEY not found in .env" -ForegroundColor Yellow
    }
} else {
    Write-Host "⚠ .env file not found" -ForegroundColor Yellow
}

# Clear license from config file
if (Test-Path "$rootPath\config\system.php") {
    $configContent = Get-Content "$rootPath\config\system.php" -Raw
    $configContent = $configContent -replace "'license_key' => '[^']*'", "'license_key' => ''"
    $configContent = $configContent -replace "'licensed_to' => '[^']*'", "'licensed_to' => ''"
    Set-Content "$rootPath\config\system.php" -Value $configContent
    Write-Host "✓ License cleared from config" -ForegroundColor Green
}

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  ✓ Clean Reset Complete!" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor White
Write-Host "  1. Run the application (POS.bat or npm start)" -ForegroundColor Gray
Write-Host "  2. Wait for splash screen to complete migrations" -ForegroundColor Gray
Write-Host "  3. Login with default credentials:" -ForegroundColor Gray
Write-Host "     Email: admin@admin.com" -ForegroundColor Cyan
Write-Host "     Password: 12345678" -ForegroundColor Cyan
Write-Host ""
Write-Host "Database will be auto-created and seeded on first startup." -ForegroundColor Yellow
Write-Host ""

# Pause for user to read
Read-Host "Press Enter to exit"
