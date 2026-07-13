<?php
/**
 * Show Laravel Error Log - Last 100 lines
 * DELETE after use!
 */
ini_set('display_errors', 1);

$logFile = dirname(__DIR__) . '/storage/logs/laravel.log';

echo "<!DOCTYPE html><html><head><title>Laravel Logs</title>
<style>
  body { font-family: monospace; background: #1a1a2e; color: #eee; padding: 20px; }
  h2 { color: #e65100; }
  pre { background: #0f0f23; padding: 16px; border-radius: 8px; font-size: 11px; overflow-x: auto; white-space: pre-wrap; word-break: break-all; }
  .error { color: #f44336; }
  .warn  { color: #ff9800; }
</style></head><body>";

echo "<h2>📋 Laravel Error Log</h2>";

if (!file_exists($logFile)) {
    echo "<p class='warn'>⚠️ Log file nahi mila: $logFile</p>";
} else {
    $size = filesize($logFile);
    echo "<p>Log size: " . round($size/1024, 1) . " KB &nbsp;|&nbsp; Path: $logFile</p>";

    // Read last 150 lines
    $lines = file($logFile);
    $last  = array_slice($lines, -150);
    $text  = implode('', $last);

    // Highlight errors
    $text = htmlspecialchars($text);
    $text = preg_replace('/(\[ERROR\].*)/i', '<span class="error">$1</span>', $text);
    $text = preg_replace('/(SQLSTATE.*)/i', '<span class="error">$1</span>', $text);
    $text = preg_replace('/(Exception.*)/i', '<span class="warn">$1</span>', $text);

    echo "<pre>$text</pre>";
}

// Also show Apache error if accessible
echo "<h2>📋 PHP/Apache Error Log (if accessible)</h2>";
$apacheLog = '/home/labour/logs/error_log';
if (file_exists($apacheLog) && is_readable($apacheLog)) {
    $lines = file($apacheLog);
    $last  = array_slice($lines, -50);
    echo "<pre>" . htmlspecialchars(implode('', $last)) . "</pre>";
} else {
    echo "<p class='warn'>Apache log not accessible (normal on shared hosting)</p>";
}

echo "<p style='color:#555;font-size:11px'>⚠️ Delete /public_html/vkb/public/show_log.php after use!</p>";
echo "</body></html>";
