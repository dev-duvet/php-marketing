<?php
declare(strict_types=1);

namespace App\Services;

final class Auth
{
    public const MAX_ATTEMPTS = 5;
    public const LOCKOUT_MINUTES = 15;

    private static ?array $user = null;
    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = $_SESSION['user_id'] ?? null;
            if ($id) {
                self::$user = row('SELECT * FROM users WHERE id = ? AND is_active = 1', [$id]);
            }
        }
        return self::$user;
    }

    public static function id(): ?int
    {
        return isset(self::user()['id']) ? (int) self::user()['id'] : null;
    }

    public static function isLockedOut(string $email, string $ip): bool
    {
        $since = date('Y-m-d H:i:s', time() - self::LOCKOUT_MINUTES * 60);
        $count = (int) scalar(
            'SELECT COUNT(*) FROM login_attempts WHERE (email = ? OR ip = ?) AND attempted_at >= ?',
            [strtolower($email), $ip, $since]
        );
        return $count >= self::MAX_ATTEMPTS;
    }

    /** @return string|null error message, or null on success */
    public static function attempt(string $email, string $password, string $ip): ?string
    {
        $email = strtolower(trim($email));
        if (self::isLockedOut($email, $ip)) {
            return 'Too many failed attempts. Please wait ' . self::LOCKOUT_MINUTES . ' minutes and try again.';
        }

        $user = row('SELECT * FROM users WHERE email = ? AND is_active = 1', [$email]);
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            q('INSERT INTO login_attempts (email, ip, attempted_at) VALUES (?, ?, ?)', [$email, $ip, date('Y-m-d H:i:s')]);
            return 'Those details do not match an active account.';
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }

        q('DELETE FROM login_attempts WHERE email = ?', [$email]);
        q('UPDATE users SET last_login_at = ? WHERE id = ?', [date('Y-m-d H:i:s'), $user['id']]);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id'] = (int) $user['id'];
        self::$user = $user;
        self::$loaded = true;
        return null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        self::$user = null;
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /** Client users only see projects that belong to their own client record. */
    public static function clientScope(string $projectColumn = 'p.client_id'): array
    {
        $user = self::user();
        if ($user && $user['role'] === 'client') {
            return [" AND {$projectColumn} = ?", [(int) $user['client_id']]];
        }
        return ['', []];
    }
}
