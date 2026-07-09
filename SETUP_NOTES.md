# Vishwakarma Bandhu Setup

This project has two parts:

- `backend`: Laravel 12 API
- `app`: Flutter app

## Backend

Use PHP 8.3 from WAMP:

```bat
start_backend.bat
```

The API is configured for:

```text
http://127.0.0.1:8000
```

## Required Local Tools

- PHP 8.2 or newer
- MongoDB PHP extension enabled for the PHP version used by Laravel
- Node.js and npm for backend Vite assets
- Flutter SDK for the mobile/web app

## Current Local Notes

The backend runs with:

```text
C:\wamp64\bin\php\php8.3.28\php.exe
```

The default command-line PHP is older, so avoid plain `php artisan ...` unless your PATH is updated.

MongoDB extension is required before migrations/API database calls can work:

```ini
extension=mongodb
```

For this WAMP PHP build, use the PHP 8.3 Thread Safe VS16 x64 MongoDB DLL.

Check your machine setup with:

```bat
check_setup.bat
```
