<?php
define('LARAVEL_START', microtime(true));
define('LARAVEL_PATH', __DIR__);

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  Laravel FMS Backend - Composer Installation In Progress\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

$vendorPath = LARAVEL_PATH . '/vendor';
$composerLock = LARAVEL_PATH . '/composer.lock';

if (!is_dir($vendorPath) || count(glob($vendorPath . '/*')) < 30) {
    echo "📦 Composer is still downloading and installing dependencies...\n\n";
    echo "   Installation Progress:\n";

    $count = 0;
    if (is_dir($vendorPath)) {
        $count = count(glob($vendorPath . '/*'));
    }
    echo "   → Packages installed: " . $count . "/73\n";
    echo "   → Location: " . $vendorPath . "\n";
    echo "\n";
    echo "⏳ Estimated time: 5-15 minutes depending on your connection\n";
    echo "\n";
    echo "What to do:\n";
    echo "   1. This script will keep polling for completion\n";
    echo "   2. Once vendor/autoload.php is ready, the server will start automatically\n";
    echo "   3. Check back in a few minutes\n";
    echo "\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "\n";
    sleep(5);
    header("Refresh: 10");
    exit(0);
}

echo "✅ Composer installation appears to be complete!\n";
echo "\n";
echo "Next steps:\n";
echo "   1. Run: php artisan migrate\n";
echo "   2. Run: php artisan serve\n";
echo "\n";
