@echo off
echo ============================================
echo   POS System - Clear Cache ^& Rebuild
echo ============================================
echo.

cd /d "%~dp0"

echo [1/6] Clearing Laravel caches...
php\php.exe artisan optimize:clear
echo.

echo [2/6] Clearing Vite cache...
if exist "node_modules\.vite" (
    rmdir /s /q "node_modules\.vite"
    echo      Vite cache cleared!
) else (
    echo      No Vite cache found.
)
echo.

echo [3/6] Removing old build files...
if exist "public\build" (
    rmdir /s /q "public\build"
    echo      Build folder cleared!
) else (
    echo      No build folder found.
)
echo.

echo [4/6] Building frontend assets...
call nodejs\npm.cmd run build
echo.

echo [5/6] Re-optimizing Laravel...
php\php.exe artisan config:clear
php\php.exe artisan view:clear
echo.

echo [6/6] Clearing log file...
if exist "storage\logs\laravel.log" del "storage\logs\laravel.log"
echo      Done!
echo.

echo ============================================
echo   BUILD COMPLETE! 
echo   Now restart the app (Ctrl+Shift+R in browser)
echo ============================================
pause
