<?php
$file = 'app/Http/Controllers/Web/ApproverDashboardController.php';
$content = file_get_contents($file);
$old = "Auth::user()->role !== 'approver'";
$new = "!in_array(Auth::user()->role, ['approver', 'dept-approver'])";
$count = substr_count($content, $old);
$content = str_replace($old, $new, $content);
file_put_contents($file, $content);
echo "Replaced $count occurrences. Done.\n";
