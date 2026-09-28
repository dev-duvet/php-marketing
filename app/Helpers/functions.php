<?php
declare(strict_types=1);

use App\Services\Auth;
use App\Services\Database;

function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? getenv($key);
    return ($value === false || $value === null || $value === '') ? $default : (string) $value;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function db(): PDO
{
    return Database::connection();
}

/** Run a prepared query and return the statement. */
function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function row(string $sql, array $params = []): ?array
{
    $result = q($sql, $params)->fetch();
    return $result === false ? null : $result;
}

function scalar(string $sql, array $params = []): mixed
{
    return q($sql, $params)->fetchColumn();
}

/** Current local time as stored in the database (TEXT, sortable). */
function now(): string
{
    return date('Y-m-d H:i:s');
}

/** String aggregation that works on SQLite and PostgreSQL. */
function group_concat(string $expr, string $separator = ','): string
{
    $sep = db()->quote($separator);
    return Database::driver() === 'pgsql' ? "string_agg({$expr}, {$sep})" : "GROUP_CONCAT({$expr}, {$sep})";
}

function set_setting(string $key, string $value): void
{
    q('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT (key) DO UPDATE SET value = excluded.value', [$key, $value]);
}

/** The visitor's IP, honouring the proxy header on Vercel. */
function client_ip(): string
{
    if (env('VERCEL') && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function url(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = BASE_PATH . '/public/' . ltrim($path, '/');
    $version = is_file($file) ? '?v=' . filemtime($file) : '';
    return url($path) . $version;
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function back(string $fallback = '/'): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    // Only follow same-host referers to avoid open redirects.
    if ($ref !== '' && parse_url($ref, PHP_URL_HOST) === explode(':', $host)[0]) {
        $path = parse_url($ref, PHP_URL_PATH) ?: $fallback;
        $query = parse_url($ref, PHP_URL_QUERY);
        redirect($path . ($query ? '?' . $query : ''));
    }
    redirect($fallback);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function old(string $key, mixed $default = ''): string
{
    return (string) ($_SESSION['old'][$key] ?? $default);
}

function remember_input(array $input): void
{
    unset($input['_token'], $input['password']);
    $_SESSION['old'] = $input;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

function csrf_token(): string
{
    if (empty($_SESSION['_token'])) {
        $_SESSION['_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function money(int $cents): string
{
    return 'R' . number_format($cents / 100, $cents % 100 === 0 ? 0 : 2, '.', ' ');
}

function fmt_date(?string $date, string $format = 'j M Y'): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '—';
}

function slugify(string $text): string
{
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
    return $slug !== '' ? $slug : bin2hex(random_bytes(4));
}

function unique_slug(string $table, string $text, ?int $ignoreId = null): string
{
    $base = slugify($text);
    $slug = $base;
    $n = 2;
    while (scalar("SELECT COUNT(*) FROM {$table} WHERE slug = ? AND id != ?", [$slug, $ignoreId ?? 0]) > 0) {
        $slug = $base . '-' . $n++;
    }
    return $slug;
}

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        try {
            $cache = array_column(rows('SELECT key, value FROM settings'), 'value', 'key');
        } catch (Throwable) {
            $cache = [];
        }
    }
    return (string) ($cache[$key] ?? $default);
}

function user(): ?array
{
    return Auth::user();
}

function can(string ...$roles): bool
{
    $user = Auth::user();
    return $user !== null && in_array($user['role'], $roles, true);
}

function log_activity(string $action, string $description): void
{
    q('INSERT INTO activity_logs (user_id, action, description, ip) VALUES (?, ?, ?, ?)', [
        Auth::id(), $action, $description, PHP_SAPI === 'cli' ? 'cli' : client_ip(),
    ]);
}

function icon(string $name, string $class = 'icon'): string
{
    return '<svg class="' . e($class) . '" aria-hidden="true"><use href="#i-' . e($name) . '"></use></svg>';
}

function status_label(string $status): string
{
    return ucfirst(str_replace('_', ' ', $status));
}

function active(string $prefix, bool $exact = false): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $match = $exact ? $path === $prefix : ($path === $prefix || str_starts_with($path, rtrim($prefix, '/') . '/'));
    return $match ? 'is-active' : '';
}

function media_url(?string $path): string
{
    if (!$path) {
        return asset('assets/img/placeholder.svg');
    }
    return str_starts_with($path, 'http') ? $path : url($path);
}

function human_size(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $size = (float) $bytes;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return round($size, 1) . ' ' . $units[$i];
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    return match (true) {
        $diff < 60 => 'just now',
        $diff < 3600 => floor($diff / 60) . ' min ago',
        $diff < 86400 => floor($diff / 3600) . ' hours ago',
        $diff < 604800 => floor($diff / 86400) . ' days ago',
        default => fmt_date($datetime),
    };
}
