<?php
declare(strict_types=1);

namespace App\Services;

final class Csrf
{
    public static function valid(): bool
    {
        $sent = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return is_string($sent) && !empty($_SESSION['_token']) && hash_equals($_SESSION['_token'], $sent);
    }
}
