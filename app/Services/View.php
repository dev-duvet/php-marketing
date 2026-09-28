<?php
declare(strict_types=1);

namespace App\Services;

use Throwable;

final class View
{
    public static function render(string $template, array $data = [], ?string $layout = null): string
    {
        try {
            $content = self::capture($template, $data);
            if ($layout === null) {
                return $content;
            }
            return self::capture($layout, ['content' => $content] + $data);
        } catch (Throwable $e) {
            if (str_starts_with($template, 'errors/')) {
                error_log((string) $e);
                return '<h1>Something went wrong</h1><p>Please try again shortly.</p>';
            }
            throw $e;
        }
    }

    public static function show(string $template, array $data = [], string $layout = 'layouts/public'): void
    {
        echo self::render($template, $data, $layout);
        clear_old();
    }

    public static function capture(string $template, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require BASE_PATH . '/app/Views/' . $template . '.php';
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
