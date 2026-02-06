@echo off
REM ============================================================
REM SPOS Professional Installer Build Script
REM Builds production installer with data preservation
REM ============================================================

echo.
echo ========================================
echo   SPOS Installer Build Script v1.1.0
echo ========================================
echo.

REM Check if we're in the right directory
if not exist "package.json" (
    echo ERROR: package.json not found!
    echo Please run this script from the project root directory.
    pause
    exit /b 1
)

echo [1/5] Cleaning previous builds...
REM Kill any locked processes first
taskkill /F /IM SPOS.exe >nul 2>&1
taskkill /F /IM electron.exe >nul 2>&1
taskkill /F /IM mysqld.exe >nul 2>&1
taskkill /F /IM php.exe >nul 2>&1
echo       Waiting for processes to fully release files...
timeout /t 5 /nobreak >nul

REM Now try to delete directories
if exist "dist_production" (
    echo       Removing old dist_production folder...
    rmdir /s /q "dist_production" 2>nul
    if exist "dist_production" (
        echo       Forcing removal of locked files...
        del /f /s /q "dist_production\*.*" >nul 2>&1
        rmdir /s /q "dist_production" >nul 2>&1
    )
    echo       Removed old dist_production folder
)
if exist "dist" (
    rmdir /s /q "dist" 2>nul
    echo       Removed old dist folder
)

echo.
echo [2/5] Final process check...
echo       Ensuring all background services are stopped...
taskkill /F /IM node.exe >nul 2>&1
taskkill /F /IM php-cgi.exe >nul 2>&1
echo       Waiting for system to stabilize...
timeout /t 2 /nobreak >nul
echo       System ready for build

echo.
echo [3/5] Running cleanup script...
php\php.exe .build-scripts\cleanup.php
if errorlevel 1 (
    echo ERROR: Cleanup script failed!
    pause
    exit /b 1
)
echo       Cleanup completed successfully

echo.
echo [4/5] Building frontend assets...
nodejs\node.exe node_modules\vite\bin\vite.js build --config vite.config.js
if errorlevel 1 (
    echo ERROR: Frontend build failed!
    pause
    exit /b 1
)
echo       Frontend build completed

echo.
echo [5/5] Building Electron installer...
echo       Ensuring no locked files remain...
REM Kill any hidden node/electron processes
wmic process where "name='node.exe' or name='electron.exe' or name='SPOS.exe'" delete >nul 2>&1
timeout /t 3 /nobreak >nul

REM Nuclear deletion with PowerShell and attribute reset
powershell -Command "if (Test-Path 'dist_production\win-unpacked') { attrib -r -s -h 'dist_production\win-unpacked\*.*' /s /d; Remove-Item 'dist_production\win-unpacked' -Force -Recurse -ErrorAction SilentlyContinue }" >nul 2>&1
powershell -Command "if (Test-Path 'dist_production') { Remove-Item 'dist_production' -Force -Recurse -ErrorAction SilentlyContinue }" >nul 2>&1
timeout /t 2 /nobreak >nul

echo       This may take several minutes...
nodejs\node.exe node_modules\electron-builder\out\cli\cli.js --win nsis
if errorlevel 1 (
    echo ERROR: Installer build failed!
    pause
    exit /b 1
)

echo.
echo [5/5] Verifying build artifacts...
if exist "dist_production\SPOS-Setup-1.1.0.exe" (
    echo       ✓ Installer found: dist_production\SPOS-Setup-1.1.0.exe
    
    REM Get file size
    for %%A in ("dist_production\SPOS-Setup-1.1.0.exe") do (
        set size=%%~zA
        set /a sizeMB=!size! / 1048576
        echo       ✓ Installer size: !sizeMB! MB
    )
    
    REM Get checksum
    certutil -hashfile "dist_production\SPOS-Setup-1.1.0.exe" SHA256 > checksum.tmp
    echo       ✓ SHA256 checksum:
    type checksum.tmp | findstr /v ":" | findstr /v "CertUtil"
    del checksum.tmp
    
    echo.
    echo ========================================
    echo   BUILD SUCCESSFUL!
    echo ========================================
    echo.
    echo Installer location:
    echo   dist_production\SPOS-Setup-1.1.0.exe
    echo.
    echo Features included:
    echo   ✓ Professional update system
    echo   ✓ Automatic data preservation
    echo   ✓ License persistence across updates
    echo   ✓ Health checks and auto-recovery
    echo   ✓ Backup/restore functionality
    echo.
    echo Next steps:
    echo   1. Test the installer on a clean machine
    echo   2. Test update from 1.0.6 to 1.1.0
    echo   3. Publish to GitHub releases
    echo.
) else (
    echo ERROR: Installer was not created!
    echo Check the build logs above for errors.
    pause
    exit /b 1
)

pause
