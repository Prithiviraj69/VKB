<?php
/**
 * Laravel Server Diagnostic Script
 * Upload to /public_html/vkb/public/diagnose.php
 * Open in browser to see what's wrong
 * DELETE after use!
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$base = dirname(__DIR__); // /home/labour/public_html/vkb

echo "<!DOCTYPE html><html><head><title>VKB Diagnostics</title>
<style>
  body { font-family: monospace; padding: 20px; background: #1a1a2e; color: #eee; }
  h2 { color: #e65100; }
  .ok   { color: #4caf50; }
  .fail { color: #f44336; }
  .warn { color: #ff9800; }
  .box  { background: #16213e; border-radius: 8px; padding: 16px; margin: 10px 0; }
  pre   { background: #0f0f23; padding: 12px; border-radius: 4px; overflow-x: auto; }
  a     { color: #e65100; }
</style></head><body>";

echo "<h2>🔍 VKB Server Diagnostics</h2>";

// ── 1. Check PHP version ──
echo "<div class='box'><b>PHP Version:</b> " . PHP_VERSION . "<br>";
echo "<b>Server:</b> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</div>";

// ── 2. Check .env file ──
echo "<div class='box'><b>📄 .env File:</b> ";
$envPath = $base . '/.env';
if (file_exists($envPath)) {
    echo "<span class='ok'>✅ EXISTS</span><br>";
    // Show keys (not values) present in .env
    $envContent = file_get_contents($envPath);
    $lines = explode("\n", $envContent);
    $keys = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && strpos($line, '#') !== 0 && strpos($line, '=') !== false) {
            $keys[] = explode('=', $line)[0];
        }
    }
    echo "<small>Keys found: " . implode(', ', $keys) . "</small><br>";

    // Check critical values
    $appKey = '';
    $dbConn = '';
    foreach ($lines as $line) {
        if (strpos($line, 'APP_KEY=') === 0) $appKey = trim(substr($line, 8));
        if (strpos($line, 'DB_CONNECTION=') === 0) $dbConn = trim(substr($line, 14));
    }
    echo "APP_KEY: " . (empty($appKey) ? "<span class='fail'>❌ MISSING! Run: php artisan key:generate</span>" : "<span class='ok'>✅ SET</span>") . "<br>";
    echo "DB_CONNECTION: " . (empty($dbConn) ? "<span class='warn'>⚠️ Not set</span>" : "<span class='ok'>✅ $dbConn</span>") . "<br>";
} else {
    echo "<span class='fail'>❌ MISSING! .env file nahi hai — ye sabse badi problem hai!</span>";
    echo "<br><br><b>Solution:</b> Neeche diya hua .env content copy karo aur <code>/public_html/vkb/.env</code> file banao";
}
echo "</div>";

// ── 3. Check vendor/autoload.php ──
echo "<div class='box'><b>📦 Vendor/Autoload:</b> ";
if (file_exists($base . '/vendor/autoload.php')) {
    echo "<span class='ok'>✅ EXISTS</span>";
} else {
    echo "<span class='fail'>❌ MISSING! composer install nahi hua</span>";
}
echo "</div>";

// ── 4. Check writable directories ──
echo "<div class='box'><b>📂 Directory Permissions:</b><br>";
$dirs = [
    'storage'                      => $base . '/storage',
    'storage/logs'                 => $base . '/storage/logs',
    'storage/framework'            => $base . '/storage/framework',
    'storage/framework/cache'      => $base . '/storage/framework/cache',
    'storage/framework/sessions'   => $base . '/storage/framework/sessions',
    'storage/framework/views'      => $base . '/storage/framework/views',
    'bootstrap/cache'              => $base . '/bootstrap/cache',
];
foreach ($dirs as $label => $path) {
    $exists   = file_exists($path);
    $writable = is_writable($path);
    $perm     = $exists ? substr(sprintf('%o', fileperms($path)), -4) : '----';
    if (!$exists) {
        echo "  <span class='fail'>❌ $label — NOT FOUND</span><br>";
        @mkdir($path, 0775, true);
        echo "  <span class='warn'>   → Created automatically</span><br>";
    } elseif (!$writable) {
        echo "  <span class='fail'>❌ $label — NOT WRITABLE (perms: $perm)</span><br>";
        @chmod($path, 0775);
        echo "  <span class='warn'>   → chmod 775 applied</span><br>";
    } else {
        echo "  <span class='ok'>✅ $label — OK (perms: $perm)</span><br>";
    }
}
echo "</div>";

// ── 5. Check vendor permissions (quick test) ──
echo "<div class='box'><b>🔐 Vendor File Readable:</b> ";
$testFile = $base . '/vendor/symfony/deprecation-contracts/function.php';
if (!file_exists($testFile)) {
    echo "<span class='fail'>❌ File not found: vendor/symfony/deprecation-contracts/function.php</span><br>";
    echo "<span class='warn'>Run fix_permissions.php first!</span>";
} elseif (!is_readable($testFile)) {
    echo "<span class='fail'>❌ File exists but NOT READABLE — permissions issue</span><br>";
    @chmod($testFile, 0644);
    echo "<span class='warn'>→ chmod 644 applied. Reload page.</span>";
} else {
    echo "<span class='ok'>✅ Readable</span>";
}
echo "</div>";

// ── 6. Try to boot Laravel ──
echo "<div class='box'><b>🚀 Laravel Boot Test:</b><br>";
if (file_exists($base . '/vendor/autoload.php') && file_exists($envPath)) {
    try {
        require $base . '/vendor/autoload.php';
        $app = require $base . '/bootstrap/app.php';
        echo "<span class='ok'>✅ Laravel booted successfully!</span><br>";
        echo "<br>Now open: <a href='clear_cache.php'>clear_cache.php</a> to clear route cache.<br>";
    } catch (\Throwable $e) {
        echo "<span class='fail'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</span><br>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
} else {
    echo "<span class='warn'>⚠️ Skipped — fix above issues first</span>";
}
echo "</div>";

// ── 7. Show .env template if missing ──
if (!file_exists($envPath)) {
    $envExample = $base . '/.env.example';
    echo "<div class='box'><b>📋 .env Template (copy karo, edit karo, save karo as .env):</b><br>";
    echo "<pre>";
    if (file_exists($envExample)) {
        echo htmlspecialchars(file_get_contents($envExample));
    } else {
        echo htmlspecialchars("APP_NAME=VishwakarmaBandhu
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://labourdnhdd.in/vkb/public

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

FILESYSTEM_DISK=public
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync

IFSC_API_KEY=your_ifsc_api_key_here
");
    }
    echo "</pre></div>";
}

echo "<p style='color:#555; font-size:11px'>⚠️ Delete this file after fixing: /public_html/vkb/public/diagnose.php</p>";
echo "</body></html>";
