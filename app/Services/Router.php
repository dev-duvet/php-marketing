<?php
declare(strict_types=1);

namespace App\Services;

final class Router
{
    private array $routes = [];

    /**
     * @param string $method GET or POST
     * @param string $pattern e.g. /work/{slug}
     * @param array{0: class-string, 1: string} $handler
     * @param string[] $roles empty = public; otherwise the user must hold one of these roles
     */
    public function add(string $method, string $pattern, array $handler, array $roles = []): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', rtrim($pattern, '/') ?: '/') . '$#';
        $this->routes[] = compact('method', 'regex', 'handler', 'roles');
    }

    public function get(string $pattern, array $handler, array $roles = []): void
    {
        $this->add('GET', $pattern, $handler, $roles);
    }

    public function post(string $pattern, array $handler, array $roles = []): void
    {
        $this->add('POST', $pattern, $handler, $roles);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rtrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/') ?: '/';
        $allowed = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            if ($route['method'] !== $method) {
                $allowed = true;
                continue;
            }

            if ($method === 'POST' && !Csrf::valid()) {
                http_response_code(419);
                echo View::render('errors/419', [], 'layouts/public');
                return;
            }

            if ($route['roles'] !== []) {
                $user = Auth::user();
                if ($user === null) {
                    $_SESSION['intended'] = $path;
                    redirect('/admin/login');
                }
                if (!in_array($user['role'], $route['roles'], true)) {
                    http_response_code(403);
                    echo View::render('errors/403', [], 'layouts/admin');
                    return;
                }
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            [$class, $action] = $route['handler'];
            (new $class())->$action(...array_values($params));
            return;
        }

        http_response_code($allowed ? 405 : 404);
        echo View::render('errors/404', [], 'layouts/public');
    }
}
