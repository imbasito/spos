# SPOS Portable Package Creator
# Creates a portable version ready for distribution

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "    SPOS PORTABLE PACKAGE CREATOR" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Step 1: Run sanitization
Write-Host "[1/5] Running sanitization script..." -ForegroundColor Yellow
& "php\php.exe" sanitize.php

if ($LASTEXITCODE -ne 0) {
    Write-Host "`n❌ Sanitization failed!" -ForegroundColor Red
    exit 1
}

# Step 2: Build electron app (win-unpacked only)
Write-Host "`n[2/5] Building Electron application..." -ForegroundColor Yellow

if (Test-Path "dist_production\win-unpacked\SPOS.exe") {
    Write-Host "  ✓ Found existing win-unpacked build, skipping..." -ForegroundColor Green
} else {
    $env:PATH = "$(Get-Location)\nodejs;$env:PATH"
    npx electron-builder --win dir --config.compression=store

    if ($LASTEXITCODE -ne 0) {
        Write-Host "`n❌ Build failed!" -ForegroundColor Red
        exit 1
    }
}

# Step 3: Create portable package folder
Write-Host "`n[3/5] Creating portable package..." -ForegroundColor Yellow
$packageName = "SPOS-v1.0.5-Portable"
$packagePath = "dist_production\$packageName"

if (Test-Path $packagePath) {
    Remove-Item -Recurse -Force $packagePath
}

New-Item -ItemType Directory -Path $packagePath | Out-Null
Copy-Item -Recurse "dist_production\win-unpacked\*" $packagePath

# Step 4: Create user-friendly launcher and instructions
Write-Host "`n[4/5] Creating launcher and instructions..." -ForegroundColor Yellow

# Create simple launcher batch file
$launcherContent = @"
@echo off
title SPOS - Point of Sale System
echo.
echo ========================================
echo    Starting SPOS...
echo ========================================
echo.
start "" "%~dp0SPOS.exe"
exit
"@

Set-Content -Path "$packagePath\Start SPOS.bat" -Value $launcherContent -Encoding ASCII

# Create README
$readmeContent = @"
========================================
   SPOS v1.0.5 - Point of Sale System
========================================

SINYX Software Solutions
Support: support@sinyx.com
Website: www.sinyx.com

INSTALLATION INSTRUCTIONS:
--------------------------

1. Extract this folder to your desired location
   (e.g., C:\Program Files\SPOS or D:\SPOS)

2. Double-click "Start SPOS.bat" to launch the application

3. On first run, the app will:
   - Set up the database
   - Create necessary files
   - Show the activation screen

4. Enter your license key when prompted
   - License keys are provided by SINYX
   - Format: MPOS-XXXX-XXXX-XXXX

5. Default login credentials:
   Email: admin@spos.com
   Password: admin123

TROUBLESHOOTING:
----------------

If the app doesn't start:
- Run as Administrator
- Check Windows Defender/Antivirus settings
- Ensure you have extracted ALL files

If activation doesn't work:
- Contact SINYX support with your Machine ID
- Machine ID is shown on the activation screen

SYSTEM REQUIREMENTS:
--------------------
- Windows 10/11 (64-bit)
- 4GB RAM minimum
- 500MB free disk space
- Internet connection (for activation only)

BACKUP INSTRUCTIONS:
--------------------
To backup your data:
1. Copy the entire SPOS folder
2. Important files:
   - database/database.sqlite (all your data)
   - .env (configuration)

To restore:
1. Copy backed-up folder to new location
2. Run "Start SPOS.bat"

For complete user guide, refer to:
SPOS_COMPLETE_DOCUMENTATION.md

========================================
Copyright © 2026 SINYX. All Rights Reserved.
"@

Set-Content -Path "$packagePath\README.txt" -Value $readmeContent -Encoding UTF8

# Copy documentation
if (Test-Path "SPOS_COMPLETE_DOCUMENTATION.md") {
    Copy-Item "SPOS_COMPLETE_DOCUMENTATION.md" $packagePath
}

# Step 5: Create ZIP archive
Write-Host "`n[5/5] Creating ZIP archive..." -ForegroundColor Yellow
$zipPath = "dist_production\$packageName.zip"

if (Test-Path $zipPath) {
    Remove-Item -Force $zipPath
}

Add-Type -Assembly "System.IO.Compression.FileSystem"
[System.IO.Compression.ZipFile]::CreateFromDirectory($packagePath, $zipPath, "Optimal", $false)

$zipSize = [math]::Round((Get-Item $zipPath).Length / 1MB, 2)

# Summary
Write-Host "`n========================================" -ForegroundColor Green
Write-Host "    ✅ PACKAGE CREATED SUCCESSFULLY!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "`nPackage Details:" -ForegroundColor White
Write-Host "  Location: $zipPath" -ForegroundColor Cyan
Write-Host "  Size: $zipSize MB" -ForegroundColor Cyan
Write-Host "`nDistribution:" -ForegroundColor White
Write-Host "  1. Send $packageName.zip to clients" -ForegroundColor Yellow
Write-Host "  2. Provide license keys using tools/license-generator.html" -ForegroundColor Yellow
Write-Host "  3. Share README.txt for installation instructions" -ForegroundColor Yellow
Write-Host "`nDefault Credentials:" -ForegroundColor White
Write-Host "  Email: admin@spos.com" -ForegroundColor Cyan
Write-Host "  Password: admin123" -ForegroundColor Cyan
Write-Host "`n========================================`n" -ForegroundColor Green
