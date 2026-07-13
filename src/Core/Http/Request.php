<?php
namespace App\Core\Http;

class Request
{
    private array $query = [];
    private array $request = [];
    private array $server = [];
    private array $files = [];
    private array $cookies = [];

    public function __construct()
    {
        $this->query = $_GET;
        $this->request = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $this->request[$key] ?? $default;
    }

    public function getMethod(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function getUri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    public function getContent(): string|false
    {
        return file_get_contents('php://input');
    }

    public function toArray(): array
    {
        $content = $this->getContent();
        if ($content && $this->isJson()) {
            return json_decode($content, true) ?? [];
        }
        return $this->request;
    }

    public function isJson(): bool
    {
        return isset($this->server['CONTENT_TYPE'])
            && str_contains($this->server['CONTENT_TYPE'], 'application/json');
    }

    public function getHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    /**
     * ✅ Récupère les fichiers uploadés
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * ✅ Récupère un fichier spécifique
     */
    public function getFile(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * ✅ Vérifie si un fichier a été uploadé
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * ✅ Récupère les paramètres GET
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * ✅ Récupère les paramètres POST
     */
    public function getPost(): array
    {
        return $this->request;
    }
}