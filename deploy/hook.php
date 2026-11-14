<?php
// The deploy endpoint, called by the GitHub workflow over HTTPS (no FTP needed):
//
//   POST _deploy.php?action=upload&part=N&offset=B   body = one piece of mofa-release.zip, written at byte B
//                                           (part 0 starts a new file; a retried piece just overwrites itself)
//   POST _deploy.php?action=deploy&sha256=… checks the uploaded zip, unpacks it over mofa/ and public_html/,
//                                           then runs migrations and rebuilds Laravel's caches.
//
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
$partial = $root.'/mofa-release.zip.part';
$zipPath = $root.'/mofa-release.zip';

function fail(string $message): never
{
    http_response_code(500);
    exit("DEPLOY FAILED: $message\n");
}

$action = $_GET['action'] ?? 'deploy';

// 0. Receive the zip in pieces (small requests stay under the host's upload limits)
if ($action === 'upload') {
    $part = (int) ($_GET['part'] ?? -1);
    $offset = (int) ($_GET['offset'] ?? -1);
    if ($part < 0 || $offset < 0) {
        fail('missing part number or offset');
    }
    $in = fopen('php://input', 'rb');
    $out = fopen($partial, $part === 0 ? 'wb' : 'c+b');
    if (! $in || ! $out || fseek($out, $offset) !== 0) {
        fail('cannot write the upload');
    }
    $bytes = stream_copy_to_stream($in, $out);
    fclose($in);
    fclose($out);
    if (! $bytes) {
        fail("part $part arrived empty");
    }
    clearstatcache();
    exit("part $part: $bytes bytes, ".filesize($partial)." so far\n");
}

if ($action !== 'deploy') {
    fail('unknown action');
}

// 1. Check the upload is complete, then unpack it
if (is_file($partial)) {
    $expected = strtolower($_GET['sha256'] ?? '');
    if ($expected === '' || ! hash_equals($expected, hash_file('sha256', $partial))) {
        @unlink($partial);
        fail('the uploaded zip does not match its checksum; nothing was changed');
    }
    rename($partial, $zipPath);
}
if (! is_file($zipPath)) {
    fail('mofa-release.zip not found');
}
$zip = new ZipArchive;
if ($zip->open($zipPath) !== true) {
    fail('cannot open mofa-release.zip');
}
$count = $zip->numFiles;
$shipped = [];
for ($i = 0; $i < $count; $i++) {
    $shipped[rtrim($zip->getNameIndex($i), '/')] = true;
}
if (! $zip->extractTo($root)) {
    fail('extract failed');
}
$zip->close();
@unlink($zipPath);
echo "unpacked $count entries\n";

// 1b. Files deleted from the project must go from the server too: unpacking only adds and
//     overwrites, so an old page or class would linger and still be loaded.
$removed = 0;
foreach (['app', 'config', 'database', 'lang', 'resources', 'routes'] as $dir) {
    if (! is_dir("$base/$dir") || ! isset($shipped["mofa/$dir"])) {
        continue; // never sweep a folder this zip did not bring
    }
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$base/$dir", FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        $relative = 'mofa/'.substr($file->getPathname(), strlen($base) + 1);
        if ($file->isFile() && ! isset($shipped[$relative]) && @unlink($file->getPathname())) {
            echo "removed $relative\n";
            $removed++;
        }
    }
}
echo "removed $removed old files\n";

// 2. Drop caches built from the old code, so the new config/routes are read —
//    including Filament's list of dashboard pages, or a new page would stay hidden.
foreach ([...(glob($base.'/bootstrap/cache/*.php') ?: []), ...(glob($base.'/bootstrap/cache/filament/panels/*.php') ?: [])] as $file) {
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
