<?php

declare(strict_types=1);

namespace CUK;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private string $prefix = '';

    public function add(string $method, string $path, callable|array $handler, array $middleware = []): self
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $this->prefix . $path,
            'handler' => $handler,
            'middleware' => array_merge($this->middleware, $middleware)
        ];
        return $this;
    }

    public function get(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->add('POST', $path, $handler, $middleware);
    }

    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $previous = $this->prefix;
        $this->prefix .= $prefix;
        $this->middleware = array_merge($this->middleware, $middleware);
        $callback($this);
        $this->prefix = $previous;
        $this->middleware = array_slice($this->middleware, 0, -count($middleware));
    }

    public function addMiddleware(callable $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function dispatch(?string $method = null, ?string $uri = null): mixed
    {
        $method ??= $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri ??= parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);

                foreach ($route['middleware'] as $mw) {
                    $result = $mw();
                    if ($result === false) {
                        return false;
                    }
                }

                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$class, $method] = $handler;
                    $controller = new $class();
                    return $controller->$method(...array_values($params));
                }

                return $handler(...array_values($params));
            }
        }

        http_response_code(404);
        return 'Page non trouvée';
    }

    public function redirect(string $url, int $status = 302): void
    {
        header("Location: $url", true, $status);
        exit;
    }

    public function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function pageUrl(string $page): string
    {
        return 'index.php?page=' . $page;
    }

    public static function currentPage(): string
    {
        return $_GET['page'] ?? 'dashboard';
    }

    public static function allowedPages(): array
    {
        return ['dashboard', 'etudiants', 'notes', 'absences', 'filieres', 'disciplinarite', 'orientations', 'rapports', 'utilisateurs', 'parametres', 'messages', 'cours', 'finances', 'jury', 'portal', 'changer_mot_de_passe'];
    }

    public static function requirePage(string $page): void
    {
        $allowed = self::allowedPages();
        $page = in_array($page, $allowed, true) ? $page : 'dashboard';
        $file = __DIR__ . '/Views/' . $page . '.php';
        if (file_exists($file)) {
            Security::initSession();
            Security::requireAuth();
            Security::showSuccess();
            Security::showError();
            include $file;
        }
    }
}
