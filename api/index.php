<?php
declare(strict_types=1);

/*
 * Vercel serverless entry point (runtime: vercel-php). Static files are served from public/;
 * every other request is rewritten here.
 *
 * With DATABASE_URL / POSTGRES_URL set (e.g. Neon from the Vercel Marketplace) all data persists.
 * Without it the app runs in demo mode on a temporary SQLite database in /tmp that resets
 * whenever Vercel recycles the function.
 */

$defaults = [
    'APP_ENV' => 'production',
    'UPLOAD_DRIVER' => 'database',
    'UPLOAD_MAX_MB' => '4',
];
if (!getenv('DATABASE_URL') && !getenv('POSTGRES_URL')) {
    $defaults['DB_PATH'] = '/tmp/createza.sqlite';
}
foreach ($defaults as $key => $value) {
    if (getenv($key) === false) {
        putenv("{$key}={$value}");
    }
}

require dirname(__DIR__) . '/public/index.php';
