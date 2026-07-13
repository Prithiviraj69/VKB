<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Laravel 12 Environment Diagnostics</h1>";

// 1. PHP Version
echo "<h2>1. PHP Version Check</h2>";
echo "Current PHP Version: <strong>" . PHP_VERSION . "</strong><br>";
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    echo "<span style='color:red;'>✘ Error: Laravel 12 requires PHP 8.2 or higher. Please upgrade PHP in cPanel (MultiPHP Manager).</span><br>";
} else {
    echo "<span style='color:green;'>✔ OK: PHP version is compatible.</span><br>";
}

// 2. Directory Permissions
echo "<h2>2. Directory Writable Check</h2>";
$dirs = [
    '../storage' => 'storage',
    '../storage/logs' => 'storage/logs',
    '../storage/framework' => 'storage/framework',
    '../storage/framework/views' => 'storage/framework/views',
    '../storage/framework/cache' => 'storage/framework/cache',
    '../storage/framework/sessions' => 'storage/framework/sessions',
    '../bootstrap/cache' => 'bootstrap/cache'
];

foreach ($dirs as $path => $label) {
    $realPath = realpath(__DIR__ . '/' . $path);
    if ($realPath === false) {
        echo "<span style='color:red;'>✘ Error: Directory '$label' ($path) does not exist!</span><br>";
        continue;
    }
    
    if (is_writable($realPath)) {
        echo "<span style='color:green;'>✔ OK: '$label' is writable (" . substr(sprintf('%o', fileperms($realPath)), -4) . ").</span><br>";
    } else {
        echo "<span style='color:red;'>✘ Error: '$label' is NOT writable! Current permissions: " . substr(sprintf('%o', fileperms($realPath)), -4) . ". Please set it to 755 or 775.</span><br>";
    }
}

// 3. PHP Extensions
echo "<h2>3. PHP Extensions Check</h2>";
$required_extensions = ['openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'curl', 'mongodb'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<span style='color:green;'>✔ OK: Extension '$ext' is loaded.</span><br>";
    } else {
        if ($ext === 'mongodb') {
            echo "<span style='color:red;'>✘ Error: Extension '$ext' is NOT loaded. This is required for MongoDB! Check MultiPHP INI Editor in cPanel and enable extension=mongodb.so.</span><br>";
        } else {
            echo "<span style='color:red;'>✘ Error: Extension '$ext' is NOT loaded.</span><br>";
        }
    }
}

// 4. Vendor / Autoload check
echo "<h2>4. Composer Autoloader Check</h2>";
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "<span style='color:green;'>✔ OK: Vendor autoload.php found.</span><br>";
    try {
        require $autoloadPath;
        echo "<span style='color:green;'>✔ OK: Successfully included autoload.php.</span><br>";
    } catch (\Throwable $e) {
        echo "<span style='color:red;'>✘ Error: Failed to include autoload.php: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "</span><br>";
    }
} else {
    echo "<span style='color:red;'>✘ Error: vendor/autoload.php not found! Did you run 'composer install' or upload the vendor folder?</span><br>";
}

// 5. Bootstrap Laravel
echo "<h2>5. Laravel Bootstrap Check</h2>";
$appPath = __DIR__ . '/../bootstrap/app.php';
if (file_exists($appPath)) {
    echo "<span style='color:green;'>✔ OK: bootstrap/app.php found.</span><br>";
    try {
        if (class_exists('Illuminate\Foundation\Application')) {
            $app = require_once $appPath;
            echo "<span style='color:green;'>✔ OK: Laravel Application bootstrapped successfully!</span><br>";
        } else {
            echo "<span style='color:red;'>✘ Error: Illuminate\Foundation\Application class not found. Autoloading issues.</span><br>";
        }
    } catch (\Throwable $e) {
        echo "<span style='color:red;'>✘ Error: Laravel bootstrap failed: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "<br><pre>" . $e->getTraceAsString() . "</pre></span><br>";
    }
} else {
    echo "<span style='color:red;'>✘ Error: bootstrap/app.php not found!</span><br>";
}
?>
