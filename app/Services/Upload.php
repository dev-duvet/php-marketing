<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class Upload
{
    /** Allowed MIME types (detected from file contents, not the browser) mapped to extension and asset type. */
    public const ALLOWED = [
        'image/jpeg' => ['jpg', 'image'],
        'image/png' => ['png', 'image'],
        'image/webp' => ['webp', 'image'],
        'image/gif' => ['gif', 'image'],
        'video/mp4' => ['mp4', 'video'],
        'video/webm' => ['webm', 'video'],
        'video/quicktime' => ['mov', 'video'],
        'application/pdf' => ['pdf', 'document'],
        'image/vnd.adobe.photoshop' => ['psd', 'design'],
        'application/postscript' => ['ai', 'design'],
    ];

    public static function maxBytes(): int
    {
        return (int) env('UPLOAD_MAX_MB', '50') * 1024 * 1024;
    }

    /** Normalise $_FILES['x'] (single or multiple) into a list of file arrays. */
    public static function files(string $field): array
    {
        $raw = $_FILES[$field] ?? null;
        if (!$raw) {
            return [];
        }
        if (!is_array($raw['name'])) {
            return $raw['error'] === UPLOAD_ERR_NO_FILE ? [] : [$raw];
        }
        $list = [];
        foreach ($raw['name'] as $i => $name) {
            if ($raw['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $list[] = [
                'name' => $name,
                'tmp_name' => $raw['tmp_name'][$i],
                'error' => $raw['error'][$i],
                'size' => $raw['size'][$i],
            ];
        }
        return $list;
    }

    /**
     * Validate and store an uploaded file under public/uploads with a random name.
     * @return array{path: string, mime: string, type: string, size: int}
     */
    public static function store(array $file, array $onlyTypes = []): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The upload failed. Check the file size and try again.');
        }
        if ($file['size'] <= 0 || $file['size'] > self::maxBytes()) {
            throw new RuntimeException('Files must be smaller than ' . env('UPLOAD_MAX_MB', '50') . 'MB.');
        }
        if (!is_uploaded_file($file['tmp_name']) && PHP_SAPI !== 'cli') {
            throw new RuntimeException('Invalid upload.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
        if (!isset(self::ALLOWED[$mime])) {
            throw new RuntimeException('That file type is not allowed. Use JPG, PNG, WEBP, GIF, MP4, WEBM, MOV, PDF, PSD or AI.');
        }
        [$ext, $type] = self::ALLOWED[$mime];
        if ($onlyTypes !== [] && !in_array($type, $onlyTypes, true)) {
            throw new RuntimeException('That file type is not allowed here.');
        }

        $name = bin2hex(random_bytes(16)) . '.' . $ext;

        // Serverless hosts have no persistent disk, so files can live in the database instead.
        if (self::usesDatabase()) {
            q('INSERT INTO stored_files (name, mime, size, data) VALUES (?, ?, ?, ?)', [
                $name, $mime, (int) $file['size'], base64_encode((string) file_get_contents($file['tmp_name'])),
            ]);
            return ['path' => 'uploads/' . $name, 'mime' => $mime, 'type' => $type, 'size' => (int) $file['size']];
        }

        $dir = BASE_PATH . '/public/uploads';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $target = $dir . '/' . $name;
        $moved = PHP_SAPI === 'cli' ? copy($file['tmp_name'], $target) : move_uploaded_file($file['tmp_name'], $target);
        if (!$moved) {
            throw new RuntimeException('Could not save the file.');
        }

        return ['path' => 'uploads/' . $name, 'mime' => $mime, 'type' => $type, 'size' => (int) $file['size']];
    }

    /** Remove a stored upload. Bundled demo media outside /uploads is never deleted. */
    public static function delete(string $path): void
    {
        if (!str_starts_with($path, 'uploads/') || str_contains($path, '..')) {
            return;
        }
        q('DELETE FROM stored_files WHERE name = ?', [substr($path, strlen('uploads/'))]);
        $file = BASE_PATH . '/public/' . $path;
        if (is_file($file)) {
            unlink($file);
        }
    }

    public static function usesDatabase(): bool
    {
        return env('UPLOAD_DRIVER', 'local') === 'database';
    }

    /** Stream a database-stored upload (GET /uploads/{name}). */
    public static function serve(string $name): bool
    {
        if (!preg_match('/^[a-f0-9]{32}\.[a-z0-9]{2,4}$/', $name)) {
            return false;
        }
        $file = row('SELECT mime, size, data FROM stored_files WHERE name = ?', [$name]);
        if (!$file || !isset(self::ALLOWED[$file['mime']])) {
            return false;
        }
        header('Content-Type: ' . $file['mime']);
        header('Content-Length: ' . (int) $file['size']);
        header('Content-Disposition: inline; filename="' . $name . '"');
        header('Cache-Control: public, max-age=31536000, immutable');
        header('X-Content-Type-Options: nosniff');
        echo base64_decode($file['data']);
        return true;
    }
}
