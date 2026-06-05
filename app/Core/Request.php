<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    private string $path;
    private string $method;
    private array  $body   = [];
    private array  $query  = [];
    private array  $files  = [];

    public function __construct()
    {
        $this->path  = '/' . ltrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
        $this->query = $_GET;
        $this->files = $_FILES;

        $rawMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if (in_array($rawMethod, ['POST', 'PATCH', 'DELETE'], true)) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (str_contains($contentType, 'application/json')) {
                $json = file_get_contents('php://input');
                $this->body = (array)json_decode($json, true);
            } else {
                $this->body = $_POST;
            }

            if ($rawMethod === 'POST') {
                // Method override: POST + _method=PATCH|DELETE
                $override = strtoupper($this->body['_method'] ?? '');
                $this->method = in_array($override, ['PATCH', 'DELETE'], true) ? $override : 'POST';
            } else {
                $this->method = $rawMethod;
            }
        } else {
            $this->method = $rawMethod;
        }
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getRealMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        return isset($this->files[$key]) ? $this->files[$key] : null;
    }

    public function ip(): string
    {
        // REMOTE_ADDR only — never trust X-Forwarded-For without explicit proxy config
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function isApi(): bool
    {
        return str_starts_with($this->path, '/api/');
    }
}
