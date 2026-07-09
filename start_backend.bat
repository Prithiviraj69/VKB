@echo off
setlocal

set "PHP_BIN=C:\wamp64\bin\php\php8.3.28\php.exe"
set "BACKEND_DIR=%~dp0backend"

if not exist "%PHP_BIN%" (
  echo PHP 8.3 was not found at: %PHP_BIN%
  echo Install/enable PHP 8.3+ in WAMP, then update this file if needed.
  exit /b 1
)

cd /d "%BACKEND_DIR%"
echo Starting Laravel backend at http://127.0.0.1:8000
echo Keep this window open while using the app.
echo.
"%PHP_BIN%" artisan serve --host=127.0.0.1 --port=8000
