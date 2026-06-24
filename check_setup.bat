@echo off
setlocal

set "PHP_BIN=C:\wamp64\bin\php\php8.3.28\php.exe"

echo Backend PHP:
"%PHP_BIN%" -v
echo.

echo Laravel:
cd /d "%~dp0backend"
"%PHP_BIN%" artisan --version
echo.

echo MongoDB PHP extension:
"%PHP_BIN%" -r "exit(extension_loaded('mongodb') ? 0 : 1);"
if errorlevel 1 (
  echo Missing: mongodb
  echo Install php_mongodb.dll for PHP 8.3 TS VS16 x64 and enable extension=mongodb in WAMP PHP 8.3.
) else (
  echo mongodb enabled
)
echo.

echo Flutter:
where flutter
if errorlevel 1 echo Missing: flutter command is not available in PATH.
echo.

echo Node/NPM:
where node
where npm
if errorlevel 1 echo Missing: node/npm command is not available in PATH.
