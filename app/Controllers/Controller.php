<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\View;

abstract class Controller
{
    protected function view(string $template, array $data = [], string $layout = 'layouts/public'): void
    {
        View::show($template, $data, $layout);
    }

    protected function admin(string $template, array $data = []): void
    {
        View::show('admin/' . $template, $data, 'layouts/admin');
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    protected function notFound(): never
    {
        http_response_code(404);
        echo View::render('errors/404', [], 'layouts/public');
        exit;
    }

    protected function forbidden(): never
    {
        http_response_code(403);
        echo View::render('errors/403', [], 'layouts/admin');
        exit;
    }

    /** Return to the form with the error and the user's input preserved. */
    protected function failed(string $message, string $path): never
    {
        remember_input($_POST);
        flash('error', $message);
        redirect($path);
    }
}
