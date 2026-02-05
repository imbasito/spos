@echo off
REM ================================================
REM  POS - Point of Sale System
REM  Double-click this file to start the application
REM ================================================

title POS - Starting...
cd /d "%~dp0"

echo.
echo  ========================================
echo    POS - Point of Sale System
echo  ========================================
echo.
echo  Starting services...
echo.

REM Start MySQL in background
echo  [1/3] Starting MySQL Database...
start /B "" "%~dp0mysql\bin\mysqld.exe" --defaults-file="%~dp0mysql\my.ini" --console

REM Wait for MySQL to start
timeout /t 3 /nobreak > nul

REM Start PHP Laravel server in background
echo  [2/3] Starting Laravel Server...
start /B "" "%~dp0php\php.exe" artisan serve --host=127.0.0.1 --port=8000

REM Wait for Laravel to start
timeout /t 2 /nobreak > nul

REM Start Electron app
echo  [3/3] Starting POS Application...
echo.
echo  ----------------------------------------
echo  Application is starting...
echo  Please wait for the window to appear.
echo  ----------------------------------------
echo.

"%~dp0nodejs\node.exe" "%~dp0node_modules\electron\cli.js" .

REM When Electron closes, clean up
echo.
echo  Shutting down services...
taskkill /F /IM mysqld.exe > nul 2>&1
taskkill /F /IM php.exe > nul 2>&1

echo  Application closed.
exit
