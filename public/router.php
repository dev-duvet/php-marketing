<?php
// Router for PHP's built-in server: serve real files directly, send everything else to index.php.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file) && !str_ends_with($file, '.php') && !str_contains($path, '/uploads/.')) {
    return false;
}
require __DIR__ . '/index.php';
