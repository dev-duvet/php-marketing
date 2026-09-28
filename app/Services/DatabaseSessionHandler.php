<?php
declare(strict_types=1);

namespace App\Services;

use SessionHandlerInterface;

/** Stores sessions in the database so they survive across serverless instances. */
final class DatabaseSessionHandler implements SessionHandlerInterface
{
    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $lifetime = (int) ini_get('session.gc_maxlifetime') ?: 7200;
        $data = scalar('SELECT data FROM sessions WHERE id = ? AND last_activity > ?', [$id, time() - $lifetime]);
        return $data === false || $data === null ? '' : (string) $data;
    }

    public function write(string $id, string $data): bool
    {
        q('INSERT INTO sessions (id, data, last_activity) VALUES (?, ?, ?)
           ON CONFLICT (id) DO UPDATE SET data = excluded.data, last_activity = excluded.last_activity', [$id, $data, time()]);
        return true;
    }

    public function destroy(string $id): bool
    {
        q('DELETE FROM sessions WHERE id = ?', [$id]);
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return q('DELETE FROM sessions WHERE last_activity < ?', [time() - $max_lifetime])->rowCount();
    }
}
