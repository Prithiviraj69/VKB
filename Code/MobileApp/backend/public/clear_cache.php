<?php

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h3>Laravel Cache Clear Script</h3>";

try {
    // Load composer autoload
    if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
        throw new Exception("vendor/autoload.php not found. Please run composer install.");
    }
    require __DIR__.'/../vendor/autoload.php';

    // Bootstrap Laravel application
    if (!file_exists(__DIR__.'/../bootstrap/app.php')) {
        throw new Exception("bootstrap/app.php not found.");
    }
    $app = require_once __DIR__.'/../bootstrap/app.php';

    // Resolve Console Kernel
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

    // Run route:clear
    echo "Clearing route cache... ";
    $statusRoute = $kernel->call('route:clear');
    echo "Done (Exit code: $statusRoute)<br>";

    // Run config:clear
    echo "Clearing config cache... ";
    $statusConfig = $kernel->call('config:clear');
    echo "Done (Exit code: $statusConfig)<br>";

    // Run cache:clear
    echo "Clearing application cache... ";
    $statusCache = $kernel->call('cache:clear');
    echo "Done (Exit code: $statusCache)<br>";

    echo "<br><b>All caches cleared successfully!</b>";
} catch (Exception $e) {
    echo "<br><b style='color:red;'>Error: " . $e->getMessage() . "</b>";
}
