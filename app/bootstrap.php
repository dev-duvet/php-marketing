<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

require BASE_PATH . '/app/Helpers/functions.php';

App\Services\Env::load(BASE_PATH . '/.env');
date_default_timezone_set(env('APP_TIMEZONE', 'Africa/Johannesburg'));

$debug = env('APP_DEBUG', 'false') === 'true';
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
// Serverless hosts have a read-only project directory; send logs to the platform instead.
ini_set('error_log', env('VERCEL') ? 'php://stderr' : BASE_PATH . '/storage/logs/app.log');

set_exception_handler(function (Throwable $e) use ($debug): void {
    error_log((string) $e);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $e . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    if ($debug) {
        echo '<pre>' . e((string) $e) . '</pre>';
    } else {
        echo App\Services\View::render('errors/500', [], 'layouts/public');
    }
});
