<?php

/**
 * One-time installer: runs the database migration over plain HTTP,
 * since this host has no shell access for `php artisan migrate`.
 *
 * Visit: https://<your-domain>/axisiq/install.php?code=<IQ_INSTALL_CODE>
 *
 * DELETE THIS FILE from the server immediately after it reports success.
 * Leaving it up is a standing (if code-gated) way to re-run migrations.
 */

require __DIR__.'/../axisiq-app/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require __DIR__.'/../axisiq-app/bootstrap/app.php';

header('Content-Type: text/plain; charset=utf-8');

// Bootstrap first so .env is actually loaded — env() is unreliable before this.
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$expected = env('IQ_INSTALL_CODE');
$given = $_GET['code'] ?? null;

if (! $expected || ! $given || ! hash_equals((string) $expected, (string) $given)) {
    http_response_code(403);
    echo "Forbidden. Pass ?code=<IQ_INSTALL_CODE> (see axisiq-app/.env).\n";
    exit;
}

try {
    $status = Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo "Migration exit code: {$status}\n\n";
    echo Illuminate\Support\Facades\Artisan::output();
    echo "\nDone. DELETE this file (axisiq/install.php) now.\n";
} catch (\Throwable $e) {
    http_response_code(500);
    echo 'Migration failed: '.$e->getMessage()."\n";
}
