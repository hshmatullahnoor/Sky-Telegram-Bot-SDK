<?php

namespace Classes;

class Router
{
    /**
     * Registered routes grouped by HTTP method.
     * @var array<string, array<string, callable>>
     */
    private array $routes = [];

    /**
     * Named route parameters extracted from the URI.
     */
    private array $params = [];

    /**
     * Register a GET route.
     */
    public function get(string $uri, callable|array $action): self
    {
        return $this->addRoute('GET', $uri, $action);
    }

    /**
     * Register a POST route.
     */
    public function post(string $uri, callable|array $action): self
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Register a route for any HTTP method.
     */
    public function any(string $uri, callable|array $action): self
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->addRoute($method, $uri, $action);
        }
        return $this;
    }

    /**
     * Add a route to the registry.
     */
    private function addRoute(string $method, string $uri, callable|array $action): self
    {
        $uri = '/' . trim($uri, '/');
        $this->routes[$method][$uri] = $action;
        return $this;
    }

    /**
     * Resolve and dispatch the current request.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = $this->getRequestUri();

        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $pattern => $action) {
            if ($this->match($pattern, $uri)) {
                $this->callAction($action, $this->params);
                return;
            }
        }

        // No route matched
        $this->sendNotFound();
    }

    /**
     * Get the clean request URI (path only, no query string).
     */
    private function getRequestUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = '/' . trim($uri, '/');

        return $uri;
    }

    /**
     * Match a route pattern against a URI.
     * Supports {param} placeholders and {param:regex} patterns.
     */
    private function match(string $pattern, string $uri): bool
    {
        // Convert route pattern to regex
        $regex = preg_replace_callback('/\{(\w+)(?::([^}]+))?\}/', function ($m) {
            $name  = $m[1];
            $constraint = $m[2] ?? '[^/]+';
            return '(?P<' . $name . '>' . $constraint . ')';
        }, $pattern);

        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            // Extract named parameters only
            $this->params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            return true;
        }

        return false;
    }

    /**
     * Call the matched route action.
     */
    private function callAction(callable|array $action, array $params): void
    {
        if (is_array($action) && count($action) === 2) {
            [$class, $method] = $action;

            if (is_string($class)) {
                $class = new $class();
            }

            call_user_func_array([$class, $method], $params);
            return;
        }

        call_user_func_array($action, $params);
    }

    /**
     * Send a 404 response.
     */
    private function sendNotFound(): void
    {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Not Found']);
    }

    /**
     * Get extracted route parameters.
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
