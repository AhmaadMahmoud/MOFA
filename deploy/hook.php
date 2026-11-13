<?php
// Called by the GitHub deploy workflow after the FTP upload: runs migrations and rebuilds Laravel's caches.
// Only answers a POST carrying the right X-Deploy-Key header (its SHA-256 is baked in at build time).
if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || ! hash_equals('__KEY_HASH__', hash('sha256', $_SERVER['HTTP_X_DEPLOY_KEY'] ?? ''))) {
    http_response_code(404);
    exit;
}
@set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');

$base = __DIR__.'/../mofa';
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$failed = false;
foreach ([['migrate', ['--force' => true]], ['view:clear', []], ['optimize', []]] as [$cmd, $args]) {
    echo "== $cmd\n";
    try {
        $code = Illuminate\Support\Facades\Artisan::call($cmd, $args);
        echo Illuminate\Support\Facades\Artisan::output()."exit=$code\n\n";
        $failed = $failed || $code !== 0;
    } catch (Throwable $e) {
        echo 'ERROR: '.$e->getMessage()."\n";
        $failed = true;
        break;
    }
}
http_response_code($failed ? 500 : 200);
echo $failed ? "DEPLOY HOOK FAILED\n" : "DEPLOY OK\n";
