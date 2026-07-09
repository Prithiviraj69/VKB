<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n--- STARTING DATA MIGRATION: MYSQL TO MONGODB ---\n";

// Configure temporary connection to source MySQL database
config(['database.connections.mysql_temp' => [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'vishwakarma_bandhu',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
]]);

$tables = [
    'states',
    'districts',
    'trades',
    'schemes',
    'users',
    'workers',
    'worker_family',
    'worker_skills',
    'worker_documents',
    'notifications',
    'otp_logs',
    'scheme_applications',
    'grievances'
];

foreach ($tables as $table) {
    echo "Migrating table: {$table}...\n";
    try {
        // Fetch all rows from MySQL
        $rows = DB::connection('mysql_temp')->table($table)->get();
        
        if ($rows->isEmpty()) {
            echo "-> No data found in MySQL for table '{$table}'. Skipping.\n";
            continue;
        }

        echo "-> Found " . $rows->count() . " rows. Inserting into MongoDB...\n";
        
        // Clear target MongoDB collection
        DB::connection('mongodb')->table($table)->delete();

        // Prepare data (convert objects to arrays)
        $data = [];
        foreach ($rows as $row) {
            $array = (array) $row;
            $data[] = $array;
        }

        // Insert in chunks
        foreach (array_chunk($data, 500) as $chunk) {
            DB::connection('mongodb')->table($table)->insert($chunk);
        }
        
        echo "-> Successfully migrated table '{$table}'!\n";
    } catch (\Exception $e) {
        echo "-> ERROR migrating table '{$table}': " . $e->getMessage() . "\n";
    }
}

echo "\n--- DATA MIGRATION COMPLETED SUCCESSFULLY ---\n";
