<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
session_set_save_handler(new App\Services\DatabaseSessionHandler(), true);
session_name('createza_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $https || env('SESSION_SECURE', 'false') === 'true',
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; media-src 'self' blob:; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-ancestors 'self'; form-action 'self'; base-uri 'self'");

$router = new App\Services\Router();
require BASE_PATH . '/config/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
