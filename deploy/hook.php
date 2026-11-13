<?php
// Called by the GitHub deploy workflow after it uploads mofa-release.zip next to public_html:
// unpacks it over mofa/ and public_html/, then runs migrations and rebuilds Laravel's caches.
// .env, storage/ and public_html/storage are never in the zip, so they are left alone.
// Only answers a POST carrying the right X-Deploy-Key header (its SHA-256 is baked in at build time).
if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || ! hash_equals('__KEY_HASH__', hash('sha256', $_SERVER['HTTP_X_DEPLOY_KEY'] ?? ''))) {
    http_response_code(404);
    exit;
}
@set_time_limit(600);
ignore_user_abort(true);
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(__DIR__);           // domains/mofasando.store
$base = $root.'/mofa';
$zipPath = $root.'/mofa-release.zip';

function fail(string $message): never
{
    http_response_code(500);
    exit("DEPLOY FAILED: $message\n");
}

// 1. Unpack the release
if (! is_file($zipPath)) {
    fail('mofa-release.zip not found');
}
$zip = new ZipArchive;
if ($zip->open($zipPath) !== true) {
    fail('cannot open mofa-release.zip');
}
$count = $zip->numFiles;
if (! $zip->extractTo($root)) {
    fail('extract failed');
}
$zip->close();
@unlink($zipPath);
echo "unpacked $count entries\n";

// 2. Drop caches built from the old code, so the new config/routes are read
foreach (glob($base.'/bootstrap/cache/*.php') ?: [] as $file) {
    @unlink($file);
}
if (function_exists('opcache_reset')) {
    @opcache_reset();
}

// 3. Boot the new code: migrate and rebuild caches
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach ([['migrate', ['--force' => true]], ['view:clear', []], ['optimize', []]] as [$cmd, $args]) {
    echo "\n== $cmd\n";
    try {
        $code = Illuminate\Support\Facades\Artisan::call($cmd, $args);
        echo Illuminate\Support\Facades\Artisan::output()."exit=$code\n";
        if ($code !== 0) {
            fail("$cmd exited with $code");
        }
    } catch (Throwable $e) {
        fail("$cmd: ".$e->getMessage());
    }
}
echo "\nDEPLOY OK\n";
