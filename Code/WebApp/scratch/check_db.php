<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $collections = DB::connection()->getMongoDB()->listCollections();
    echo "COLLECTIONS:\n";
    foreach ($collections as $col) {
        echo "- " . $col->getName() . "\n";
    }

    echo "\nWORKERS COUNT: " . DB::table('workers')->count() . "\n";

    echo "\nSAMPLE WORKERS:\n";
    $samples = DB::table('workers')->take(2)->get();
    print_r($samples);

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
