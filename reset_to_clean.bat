@echo off
:: ====================================================================
:: SPOS Complete Reset Script (Batch version)
:: Resets the application to a clean state for first-time installation
:: ====================================================================

echo =====================================
echo   SPOS Complete Clean Reset
echo =====================================
echo.

:: Check for admin rights
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [!] This script requires administrator privileges.
    echo     Right-click and select "Run as Administrator"
    pause
    exit /b 1
)

echo [1/8] Stopping any running SPOS processes...
taskkill /F /IM php.exe >nul 2>&1
taskkill /F /IM mysqld.exe >nul 2>&1
taskkill /F /IM POS.exe >nul 2>&1
timeout /t 2 /nobreak >nul
echo [OK] Processes stopped

echo [2/8] Clearing Laravel cache and logs...
if exist "bootstrap\cache\*.*" (
    del /Q /F "bootstrap\cache\*.*" >nul 2>&1
)
if exist "storage\framework\cache\data" (
    rmdir /S /Q "storage\framework\cache\data" >nul 2>&1
    mkdir "storage\framework\cache\data" >nul 2>&1
)
if exist "storage\framework\sessions" (
    del /Q /F "storage\framework\sessions\*.*" >nul 2>&1
)
if exist "storage\framework\views" (
    del /Q /F "storage\framework\views\*.*" >nul 2>&1
)
if exist "storage\logs" (
    del /Q /F "storage\logs\*.*" >nul 2>&1
)
echo [OK] Laravel cache cleared

echo [3/8] Removing database...
if exist "database\database.sqlite" (
    del /F "database\database.sqlite" >nul 2>&1
    echo [OK] Database removed
) else (
    echo [OK] No database found (already clean)
)

echo [4/8] Clearing MySQL data...
if exist "mysql\data\spos_pos" (
    rmdir /S /Q "mysql\data\spos_pos" >nul 2>&1
)
echo [OK] MySQL data cleared

echo [5/8] Clearing Node.js cache...
if exist "node_modules\.cache" (
    rmdir /S /Q "node_modules\.cache" >nul 2>&1
)
if exist ".vite" (
    rmdir /S /Q ".vite" >nul 2>&1
)
echo [OK] Node cache cleared

echo [6/8] Clearing Electron build artifacts...
if exist "out" (
    rmdir /S /Q "out" >nul 2>&1
)
if exist "dist" (
    rmdir /S /Q "dist" >nul 2>&1
)
if exist "build\portable" (
    rmdir /S /Q "build\portable" >nul 2>&1
)
echo [OK] Build artifacts cleared

echo [7/8] Clearing temporary files...
if exist "storage\app\public" (
    for /d %%i in ("storage\app\public\*") do rmdir /S /Q "%%i" >nul 2>&1
    del /Q /F "storage\app\public\*.*" >nul 2>&1
)
if exist "mysql\tmp" (
    del /Q /F "mysql\tmp\*.*" >nul 2>&1
)
echo [OK] Temp files cleared

echo [8/8] Verifying .env configuration...
if exist ".env" (
    findstr /C:"APP_KEY=base64:" .env >nul
    if %errorLevel% equ 0 (
        echo [OK] APP_KEY preserved
    ) else (
        echo [!] APP_KEY not found in .env
    )
) else (
    echo [!] .env file not found
)

echo.
echo =====================================
echo   [OK] Clean Reset Complete!
echo =====================================
echo.
echo Next steps:
echo   1. Run the application (POS.bat or npm start)
echo   2. Wait for splash screen to complete migrations
echo   3. Login with default credentials:
echo      Email: admin@admin.com
echo      Password: 12345678
echo.
echo Database will be auto-created and seeded on first startup.
echo.

pause
