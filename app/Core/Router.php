<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Router — small, explicit router.
 *
 * Supports static paths and `{name}` parameter segments. No regex DSL because
 * this app has under 20 routes and the simpler the router, the safer it is.
 */
final class Router
{
    /** @var array<int,array{method:string,pattern:string,handler:callable|array{0:string,1:string},middleware:array<int,callable>}> */
    private array $routes = [];

    /** @var array<string,callable> */
    private array $namedMiddleware = [];

    public function middleware(string $name, callable $handler): void
    {
        $this->namedMiddleware[$name] = $handler;
    }

    /**
     * @param callable|array{0:string,1:string} $handler
     * @param array<int,string> $middleware
     */
    public function get(string $pattern, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    /**
     * @param callable|array{0:string,1:string} $handler
     * @param array<int,string> $middleware
     */
    public function post(string $pattern, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    /**
     * @param callable|array{0:string,1:string} $handler
     * @param array<int,string> $middlewareNames
     */
    private function add(string $method, string $pattern, callable|array $handler, array $middlewareNames): void
    {
        $resolved = [];
        foreach ($middlewareNames as $name) {
            if (isset($this->namedMiddleware[$name])) {
                $resolved[] = $this->namedMiddleware[$name];
            }
        }

        $this->routes[] = [
            'method'     => $method,
            'pattern'    => $pattern,
            'handler'    => $handler,
            'middleware' => $resolved,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = $this->stripBase($path);
        $path = '/' . trim($path, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $params = $this->match($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            foreach ($route['middleware'] as $mw) {
                $mw();
            }

            $handler = $route['handler'];
            if (is_array($handler)) {
                [$class, $action] = $handler;
                $controller = new $class();
                $controller->{$action}(...array_values($params));
            } else {
                $handler(...array_values($params));
            }
            return;
        }

        http_response_code(404);
        require Paths::views('errors/404.php');
    }

    private function stripBase(string $path): string
    {
        // Under Apache with .htaccess, SCRIPT_NAME ends with "/index.php" and we
        // can derive the base path from its dirname. Under PHP's built-in server
        // (router mode), SCRIPT_NAME is the actual request URI and we must NOT
        // strip anything.
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        if (!str_ends_with($scriptName, '.php')) {
            return $path === '' ? '/' : $path;
        }

        $base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($base !== '' && $base !== '/' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        return $path === '' ? '/' : $path;
    }

    /**
     * @return array<string,string>|null
     */
    private function match(string $pattern, string $path): ?array
    {
        $patternParts = explode('/', trim($pattern, '/'));
        $pathParts    = explode('/', trim($path, '/'));

        if (count($patternParts) !== count($pathParts)) {
            return null;
        }

        $params = [];
        foreach ($patternParts as $i => $segment) {
            if ($segment !== '' && $segment[0] === '{' && str_ends_with($segment, '}')) {
                $name          = substr($segment, 1, -1);
                $params[$name] = $pathParts[$i];
                continue;
            }
            if ($segment !== $pathParts[$i]) {
                return null;
            }
        }
        return $params;
    }
}
