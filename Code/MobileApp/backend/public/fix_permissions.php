<?php
/**
 * Fix ALL File Permissions - Full Laravel Project
 * Fixes: vendor/, app/, routes/, config/, bootstrap/, storage/
 * DELETE this file after use!
 */
set_time_limit(300);
ini_set('display_errors', 1);
error_reporting(E_ALL);

$base = dirname(__DIR__); // /home/labour/public_html/vkb

echo "<!DOCTYPE html><html><head><title>Fix Permissions</title>
<style>
  body { font-family: monospace; background: #1a1a2e; color: #eee; padding: 20px; }
  h2 { color: #e65100; }
  .ok   { color: #4caf50; }
  .fail { color: #f44336; }
  .warn { color: #ff9800; }
  .box  { background: #16213e; border-radius: 8px; padding: 16px; margin: 10px 0; }
</style></head><body>";

echo "<h2>🔧 Full Permissions Fix</h2>";
echo "<div class='box'>";

$fixed_files = 0;
$fixed_dirs  = 0;
$errors      = 0;

function fixPath($path, &$fixed_files, &$fixed_dirs, &$errors, $dirPerm = 0755, $filePerm = 0644) {
    if (!file_exists($path)) return;
    if (is_link($path)) return;

    if (is_dir($path)) {
        if (@chmod($path, $dirPerm)) {
            $fixed_dirs++;
        } else {
            $errors++;
        }
        $items = @scandir($path);
        if ($items) {
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                fixPath($path . '/' . $item, $fixed_files, $fixed_dirs, $errors, $dirPerm, $filePerm);
            }
        }
    } else {
        if (@chmod($path, $filePerm)) {
            $fixed_files++;
        } else {
            $errors++;
        }
    }
}

// Directories to fix with standard permissions (dirs: 755, files: 644)
$standardDirs = ['app', 'routes', 'config', 'database', 'resources', 'vendor'];
foreach ($standardDirs as $dir) {
    $fullPath = $base . '/' . $dir;
    if (file_exists($fullPath)) {
        echo "Fixing <b>$dir/</b>... ";
        fixPath($fullPath, $fixed_files, $fixed_dirs, $errors);
        echo "<span class='ok'>✅ Done</span><br>";
    } else {
        echo "<span class='warn'>⚠️ $dir/ not found, skipping</span><br>";
    }
}

// Storage & bootstrap/cache need writable permissions (dirs: 775, files: 664)
$writableDirs = ['storage', 'bootstrap/cache'];
foreach ($writableDirs as $dir) {
    $fullPath = $base . '/' . $dir;
    if (file_exists($fullPath)) {
        echo "Fixing <b>$dir/</b> (writable)... ";
        fixPath($fullPath, $fixed_files, $fixed_dirs, $errors, 0775, 0664);
        echo "<span class='ok'>✅ Done</span><br>";
    }
}

// Fix root-level PHP files
$rootFiles = ['artisan', 'composer.json', 'composer.lock'];
foreach ($rootFiles as $file) {
    $fullPath = $base . '/' . $file;
    if (file_exists($fullPath)) {
        @chmod($fullPath, 0644);
        $fixed_files++;
    }
}

echo "<br><b>📊 Summary:</b><br>";
echo "  Files fixed : <span class='ok'>$fixed_files</span><br>";
echo "  Dirs  fixed : <span class='ok'>$fixed_dirs</span><br>";
echo "  Errors      : <span class='" . ($errors > 0 ? 'fail' : 'ok') . "'>$errors</span><br>";
echo "</div>";

// Verify the specific failing file
$corsFile = $base . '/app/Http/Middleware/CorsMiddleware.php';
echo "<div class='box'><b>🔍 CorsMiddleware.php Check:</b><br>";
if (!file_exists($corsFile)) {
    echo "<span class='fail'>❌ File does not exist! Path: $corsFile</span><br>";
    echo "<span class='warn'>This file is missing from the upload. Check your zip.</span>";
} elseif (!is_readable($corsFile)) {
    echo "<span class='fail'>❌ Still not readable. Trying force chmod...</span><br>";
    @chmod($corsFile, 0644);
    echo is_readable($corsFile) ? "<span class='ok'>✅ Fixed!</span>" : "<span class='fail'>❌ chmod failed — contact hosting support</span>";
} else {
    $perm = substr(sprintf('%o', fileperms($corsFile)), -4);
    echo "<span class='ok'>✅ Readable! (perms: $perm)</span>";
}
echo "</div>";

echo "<div class='box'>";
if ($errors === 0) {
    echo "<span class='ok'><b>✅ All permissions fixed!</b></span><br><br>";
    echo "Next step: <a href='clear_cache.php' style='color:#e65100'>Open clear_cache.php</a> to clear Laravel route cache.";
} else {
    echo "<span class='warn'><b>⚠️ Some errors occurred ($errors). Try running again or contact hosting support.</b></span>";
}
echo "</div>";

echo "<p style='color:#555;font-size:11px'>⚠️ Delete /public_html/vkb/public/fix_permissions.php after use!</p>";
echo "</body></html>";
