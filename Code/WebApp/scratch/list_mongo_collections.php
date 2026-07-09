<?php
$base = dirname(__DIR__);
require $base . '/vendor/autoload.php';
$app = require_once $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== MongoDB Collections in 'vishwakarma_bandhu' ===\n";
try {
    $collections = DB::connection('mongodb')->getMongoDB()->listCollections();
    $count = 0;
    foreach ($collections as $collection) {
        $name = $collection->getName();
        $docs = DB::connection('mongodb')->table($name)->count();
        echo "  ✓ {$name} ({$docs} documents)\n";
        $count++;
    }
    if ($count === 0) {
        echo "  (No collections yet - database is empty)\n";
    }
    echo "\nTotal: {$count} collections\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
