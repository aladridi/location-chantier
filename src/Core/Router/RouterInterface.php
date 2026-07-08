<?php
namespace App\Core\Router;

interface RouterInterface
{
    public function addRoute(string $path, string $controller, string $method, string $httpMethod = 'GET'): void;
    public function match(string $uri, string $httpMethod): ?Route;
}