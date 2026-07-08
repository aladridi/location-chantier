<?php
namespace App\Core\Router;

class Router implements RouterInterface
{
    private array $routes = [];
    private array $parameters = [];

    public function addRoute(string $path, string $controller, string $method, string $httpMethod = 'GET'): void
    {
        $this->routes[$httpMethod][] = new Route($path, $controller, $method);
    }

    public function match(string $uri, string $httpMethod): ?Route
    {
        // Supprimer les paramètres GET
        $uri = strtok($uri, '?');

        if (!isset($this->routes[$httpMethod])) {
            return null;
        }

        foreach ($this->routes[$httpMethod] as $route) {
            $pattern = $this->convertToRegex($route->getPath());

            if (preg_match($pattern, $uri, $matches)) {
                // Extraire les paramètres
                $parameters = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $route->setParameters($parameters);
                return $route;
            }
        }

        return null;
    }

    private function convertToRegex(string $path): string
    {
        // Convertir {id} en regex avec nom de capture
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
}