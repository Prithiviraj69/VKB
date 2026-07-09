<?php
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel application
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting Family Members Healing/Synchronization Script...\n";

// Get all workers
$workers = DB::table('workers')->get();
$healedCount = 0;
$skippedCount = 0;

foreach ($workers as $worker) {
    $workerId = (string)($worker->_id ?? $worker->id ?? '');
    if (empty($workerId)) {
        continue;
    }

    $familyMembers = $worker->family_members ?? [];
    if (is_string($familyMembers)) {
        $familyMembers = json_decode($familyMembers, true) ?: [];
    }

    if (!is_array($familyMembers) || empty($familyMembers)) {
        $skippedCount++;
        continue;
    }

    echo "Processing Worker ID: {$workerId} ({$worker->full_name}) with " . count($familyMembers) . " family members...\n";

    foreach ($familyMembers as $member) {
        $name = trim($member['name'] ?? '');
        if (empty($name)) {
            continue;
        }

        // Check if this family member already exists in worker_family for this worker
        $exists = DB::table('worker_family')
            ->where('worker_id', $workerId)
            ->where('name', $name)
            ->exists();

        if (!$exists) {
            DB::table('worker_family')->insert([
                'worker_id' => $workerId,
                'name' => $name,
                'relation' => $member['relation'] ?? 'Member',
                'gender' => $member['gender'] ?? 'male',
                'age' => isset($member['age']) ? (int)$member['age'] : null,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);
            echo "  -> Inserted family member: {$name} ({$member['relation']})\n";
            $healedCount++;
        } else {
            echo "  -> Family member already exists: {$name}\n";
        }
    }
}

echo "\nSynchronization Complete!\n";
echo "Total Worker Records Processed: " . count($workers) . "\n";
echo "Total New Family Member Records Synchronized: {$healedCount}\n";
echo "Total Workers Skipped (No Family Members): {$skippedCount}\n";
