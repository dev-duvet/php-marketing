<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * SQLite locally; PostgreSQL when DATABASE_URL (or POSTGRES_URL) is set, e.g. on Vercel with Neon.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function url(): ?string
    {
        return env('DATABASE_URL') ?? env('POSTGRES_URL');
    }

    public static function driver(): string
    {
        return self::url() ? 'pgsql' : 'sqlite';
    }

    public static function path(): string
    {
        $path = env('DB_PATH', 'storage/createza.sqlite');
        if ($path === ':memory:' || preg_match('#^([A-Za-z]:)?[\\\\/]#', $path)) {
            return $path;
        }
        return BASE_PATH . '/' . $path;
    }

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            self::driver() === 'pgsql' ? self::connectPostgres() : self::connectSqlite();
        }
        return self::$pdo;
    }

    private static function connectSqlite(): void
    {
        $path = self::path();
        $isNew = $path === ':memory:' || !is_file($path);
        if ($path !== ':memory:' && !is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        self::$pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        self::$pdo->exec('PRAGMA foreign_keys = ON');
        self::$pdo->exec('PRAGMA journal_mode = WAL');
        self::$pdo->exec('PRAGMA busy_timeout = 5000');
        // Same function name as the PostgreSQL migration defines, so queries stay portable.
        self::$pdo->sqliteCreateFunction('now_local', fn () => date('Y-m-d H:i:s'), 0);
        // First run: build the schema and load demo data so the app works out of the box.
        if (env('DB_AUTO_SETUP', 'true') === 'true') {
            if ($isNew) {
                self::migrate();
                Seeder::run();
            } else {
                self::migrate();
            }
        }
    }

    private static function connectPostgres(): void
    {
        $parts = parse_url((string) self::url());
        if (!$parts || empty($parts['host'])) {
            throw new \RuntimeException('DATABASE_URL is not a valid postgres:// URL.');
        }
        parse_str($parts['query'] ?? '', $query);
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
            $parts['host'],
            $parts['port'] ?? 5432,
            ltrim($parts['path'] ?? '/postgres', '/'),
            $query['sslmode'] ?? 'require'
        );
        // Neon routes connections by endpoint ID; pass it explicitly for clients without SNI support.
        if (str_contains($parts['host'], '.neon.tech')) {
            $endpoint = str_replace('-pooler', '', explode('.', $parts['host'])[0]);
            $dsn .= ";options='endpoint={$endpoint}'";
        }
        self::$pdo = new PDO($dsn, urldecode($parts['user'] ?? ''), urldecode($parts['pass'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Emulated prepares keep us compatible with transaction-mode connection poolers.
            PDO::ATTR_EMULATE_PREPARES => true,
        ]);

        if (env('DB_AUTO_SETUP', 'true') === 'true') {
            // Serialise first-run setup across concurrent serverless cold starts.
            self::$pdo->exec('SELECT pg_advisory_lock(727274)');
            try {
                $isNew = self::$pdo->query("SELECT to_regclass('public.users') IS NULL")->fetchColumn();
                self::migrate();
                if ($isNew) {
                    Seeder::run();
                }
            } finally {
                self::$pdo->exec('SELECT pg_advisory_unlock(727274)');
            }
        }
    }

    public static function migrate(): void
    {
        $pdo = self::connection();
        $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (name TEXT PRIMARY KEY, ran_at TEXT)');
        $done = $pdo->query('SELECT name FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
        $files = glob(BASE_PATH . '/database/migrations/' . self::driver() . '/*.sql');
        sort($files);
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $done, true)) {
                continue;
            }
            $pdo->exec(file_get_contents($file));
            $pdo->prepare('INSERT INTO migrations (name, ran_at) VALUES (?, ?)')->execute([$name, now()]);
        }
    }

    /** Drop the database (SQLite file, or every table on Postgres) so it can be rebuilt. */
    public static function reset(): void
    {
        if (self::driver() === 'pgsql') {
            $pdo = self::connection();
            $pdo->exec('DROP SCHEMA public CASCADE; CREATE SCHEMA public');
            return;
        }
        self::$pdo = null;
        $path = self::path();
        if ($path !== ':memory:') {
            foreach ([$path, $path . '-wal', $path . '-shm'] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}
