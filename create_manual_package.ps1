# Manual Portable Package Creator for SPOS
# This bypasses electron-builder and creates a portable package directly

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "    SPOS MANUAL PACKAGE CREATOR" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Step 1: Run sanitization
Write-Host "[1/4] Running sanitization script..." -ForegroundColor Yellow
& "php\php.exe" sanitize.php

if ($LASTEXITCODE -ne 0) {
    Write-Host "`n❌ Sanitization failed!" -ForegroundColor Red
    exit 1
}

# Step 2: Create package folder structure
Write-Host "`n[2/4] Creating portable package folder..." -ForegroundColor Yellow
$packageName = "SPOS-v1.0.5-Portable"
$packagePath = "dist_production\$packageName"

if (Test-Path $packagePath) {
    Write-Host "  Removing existing package..." -ForegroundColor Gray
    Remove-Item -Recurse -Force $packagePath
}

New-Item -ItemType Directory -Path $packagePath | Out-Null

# Step 3: Copy all necessary application files
Write-Host "`n[3/4] Copying application files (this may take a few minutes)..." -ForegroundColor Yellow

$filesToCopy = @(
    "main.cjs",
    "preload.cjs",
    "forge.config.js",
    "package.json",
    "vite.config.js",
    "splash.html",
    "receipt_template.html",
    "artisan",
    "composer.json",
    "composer.lock",
    ".env"
)

$foldersTo = @(
    "php",
    "mysql",
    "nodejs",
    "vendor",
    "app",
    "bootstrap",
    "config",
    "database",
    "public",
    "resources",
    "routes",
    "services",
    "storage"
)

$totalItems = $filesToCopy.Count + $foldersTo.Count
$current = 0

foreach ($file in $filesToCopy) {
    $current++
    Write-Progress -Activity "Copying files" -Status "$current of $totalItems" -PercentComplete (($current / $totalItems) * 100)
    if (Test-Path $file) {
        Copy-Item $file -Destination $packagePath -Force
    }
}

foreach ($folder in $foldersTo) {
    $current++
    Write-Progress -Activity "Copying folders" -Status "$current of $totalItems - $folder" -PercentComplete (($current / $totalItems) * 100)
    if (Test-Path $folder) {
        Copy-Item -Recurse $folder -Destination $packagePath -Force
    }
}

Write-Progress -Activity "Copying files" -Completed

# Step 4: Create launcher scripts
Write-Host "`n[4/4] Creating launcher and documentation..." -ForegroundColor Yellow

# Windows Batch Launcher
$batchLauncher = @"
@echo off
setlocal enabledelayedexpansion

title SPOS - Starting...
color 0B

echo.
echo ================================================
echo         SPOS - Point of Sale System
echo ================================================
echo.
echo Starting MySQL database...
start /B "" "%~dp0mysql\bin\mysqld.exe" --defaults-file="%~dp0mysql\my.ini"

timeout /t 3 /nobreak >nul

echo Starting PHP server...
start /B "" "%~dp0php\php.exe" -S 127.0.0.1:8000 -t "%~dp0public"

timeout /t 2 /nobreak >nul

echo Starting SPOS application...
echo.
echo ================================================
echo   SPOS is now running in your browser!
echo   Press Ctrl+C in this window to stop SPOS
echo ================================================
echo.

REM Open browser
start "" "http://127.0.0.1:8000"

REM Keep window open
pause
"@

Set-Content -Path "$packagePath\Start SPOS.bat" -Value $batchLauncher -Encoding ASCII

# PowerShell Launcher (more robust)
$psLauncher = @'
# SPOS Launcher - PowerShell Version
$host.UI.RawUI.WindowTitle = "SPOS - Starting..."

Write-Host "`n================================================" -ForegroundColor Cyan
Write-Host "       SPOS - Point of Sale System" -ForegroundColor Cyan
Write-Host "================================================`n" -ForegroundColor Cyan

$basePath = $PSScriptRoot

# Start MySQL
Write-Host "Starting MySQL database..." -ForegroundColor Yellow
$mysqlProcess = Start-Process -FilePath "$basePath\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=$basePath\mysql\my.ini" -WindowStyle Hidden -PassThru
Start-Sleep -Seconds 3

# Start PHP Server
Write-Host "Starting PHP server..." -ForegroundColor Yellow
$phpProcess = Start-Process -FilePath "$basePath\php\php.exe" -ArgumentList "-S","127.0.0.1:8000","-t","$basePath\public" -WindowStyle Hidden -PassThru
Start-Sleep -Seconds 2

# Open Browser
Write-Host "Opening SPOS in browser..." -ForegroundColor Yellow
Start-Process "http://127.0.0.1:8000"

Write-Host "`n================================================" -ForegroundColor Green
Write-Host "   ✓ SPOS is now running!" -ForegroundColor Green
Write-Host "================================================`n" -ForegroundColor Green
Write-Host "Press any key to stop SPOS and close this window..." -ForegroundColor Gray

$null = $host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

# Cleanup
Write-Host "`nStopping SPOS..." -ForegroundColor Yellow
Stop-Process -Id $phpProcess.Id -Force -ErrorAction SilentlyContinue
Stop-Process -Id $mysqlProcess.Id -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 2
Write-Host "SPOS stopped. Goodbye!" -ForegroundColor Green
'@

Set-Content -Path "$packagePath\Start SPOS.ps1" -Value $psLauncher -Encoding UTF8

# Create README
$readme = @"
========================================
   SPOS v1.0.5 - Point of Sale System
========================================

SINYX Software Solutions
Support: support@sinyx.com

QUICK START:
------------
1. Extract this entire folder to your desired location
2. Double-click "Start SPOS.bat" (Windows)
   OR Run "Start SPOS.ps1" in PowerShell (Recommended)
3. Your browser will open automatically
4. Enter your license key when prompted

DEFAULT LOGIN:
--------------
Email: admin@spos.com
Password: admin123

FIRST RUN:
----------
On first launch, SPOS will:
- Initialize the database
- Set up necessary folders
- Show the activation screen

Your Machine ID will be displayed on the activation screen.
Contact SINYX support with this ID to get your license key.

LICENSE KEY FORMAT:
-------------------
MPOS-XXXX-XXXX-XXXX

(Keys are generated by SINYX and tied to your machine)

STOPPING SPOS:
--------------
- If using .bat: Close the command window or press Ctrl+C
- If using .ps1: Press any key in the PowerShell window

TROUBLESHOOTING:
----------------
Issue: "Port 8000 already in use"
Solution: Stop any other programs using port 8000, or kill existing SPOS processes

Issue: "MySQL won't start"
Solution: Check if another MySQL is running on port 3306. Kill it first.

Issue: "Can't access SPOS"
Solution: Wait 5-10 seconds after starting, then manually go to: http://127.0.0.1:8000

Issue: "Activation not working"
Solution: Ensure you have internet connection. Contact SINYX support with your Machine ID.

BACKUP YOUR DATA:
-----------------
Important files to backup:
- database/database.sqlite (ALL your business data)
- .env (configuration)
- storage/app/public/products/ (product images)

To backup: Simply copy the entire SPOS folder to a safe location.

SYSTEM REQUIREMENTS:
--------------------
- Windows 10/11 (64-bit)
- 4GB RAM minimum
- 1GB free disk space
- Internet connection (for activation only)

For complete documentation, see:
SPOS_COMPLETE_DOCUMENTATION.md

========================================
Copyright © 2026 SINYX. All Rights Reserved.
"@

Set-Content -Path "$packagePath\README.txt" -Value $readme -Encoding UTF8

# Copy documentation if exists
if (Test-Path "SPOS_COMPLETE_DOCUMENTATION.md") {
    Copy-Item "SPOS_COMPLETE_DOCUMENTATION.md" $packagePath
}

# Create version info file
$versionInfo = @"
SPOS Version: 1.0.5
Build Date: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
Package Type: Portable (Manual Distribution)
Architecture: x64
Database: SQLite 3
PHP Version: 8.x
MySQL Version: 8.x
Node.js Version: Bundled

This is a portable version of SPOS.
No installation required - just extract and run!
"@

Set-Content -Path "$packagePath\VERSION.txt" -Value $versionInfo -Encoding UTF8

# Summary
$packageSize = (Get-ChildItem -Recurse $packagePath | Measure-Object -Property Length -Sum).Sum / 1GB

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "    ✅ PACKAGE CREATED SUCCESSFULLY!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "`nPackage Details:" -ForegroundColor White
Write-Host "  Location: $packagePath" -ForegroundColor Cyan
Write-Host "  Size: $([math]::Round($packageSize, 2)) GB" -ForegroundColor Cyan
Write-Host "  Files: $(( Get-ChildItem -Recurse $packagePath | Measure-Object).Count)" -ForegroundColor Cyan
Write-Host "`nHow to distribute:" -ForegroundColor White
Write-Host "  1. Compress the folder: $packageName" -ForegroundColor Yellow
Write-Host "  2. Send ZIP to clients via file sharing (Google Drive, Dropbox, etc.)" -ForegroundColor Yellow
Write-Host "  3. Provide license keys using tools/license-generator.html" -ForegroundColor Yellow
Write-Host "  4. Share README.txt for instructions" -ForegroundColor Yellow
Write-Host "`nDefault Credentials:" -ForegroundColor White
Write-Host "  Email: admin@spos.com" -ForegroundColor Cyan
Write-Host "  Password: admin123" -ForegroundColor Cyan
Write-Host "`n========================================`n" -ForegroundColor Green

Write-Host "To create a ZIP archive, run:" -ForegroundColor Gray
Write-Host "Compress-Archive -Path '$packagePath' -DestinationPath '$packagePath.zip'" -ForegroundColor Gray
Write-Host ""
