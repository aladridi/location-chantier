<?php
namespace App\Core\Http;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private mixed $content = null;

    public function __construct(mixed $content = null, int $statusCode = 200)
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
    }

    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function json(array $data, int $statusCode = 200): self
    {
        $this->content = json_encode($data);
        $this->statusCode = $statusCode;
        $this->setHeader('Content-Type', 'application/json');
        return $this;
    }

    public function html(string $html, int $statusCode = 200): self
    {
        $this->content = $html;
        $this->statusCode = $statusCode;
        $this->setHeader('Content-Type', 'text/html');
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        echo $this->content;
    }
}