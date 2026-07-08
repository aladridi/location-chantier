<?php
namespace App\Core\Router;

class Route
{
    public function __construct(
        private string $path,
        private string $controller,
        private string $method,
        private array $parameters = []
    ) {}

    public function getPath(): string { return $this->path; }
    public function getController(): string { return $this->controller; }
    public function getMethod(): string { return $this->method; }
    public function getParameters(): array { return $this->parameters; }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }
}