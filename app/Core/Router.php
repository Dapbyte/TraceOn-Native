<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, string $action): void
    {
        $this->routes['GET'][$path] = $action;
    }

    public function post(string $path, string $action): void
    {
        $this->routes['POST'][$path]   = $action;
        $this->routes['PATCH'][$path]  = $action;
        $this->routes['DELETE'][$path] = $action;
    }

    public function dispatch(): void
    {
        $this->sendSecurityHeaders();

        Session::start();

        $request = new Request();
        $method  = $request->getMethod();
        $path    = $request->getPath();

        // Strip query string from path
        $path = strtok($path, '?') ?: '/';

        [$controller, $actionMethod, $params] = $this->resolve($method, $path);

        if ($controller === null) {
            Response::notFound($request->isApi());
        }

        $controllerClass = 'App\\Controllers\\' . $controller;

        if (!class_exists($controllerClass)) {
            Response::notFound($request->isApi());
        }

        $instance = new $controllerClass($request);
        $instance->$actionMethod(...$params);
    }

    private function resolve(string $method, string $path): array
    {
        $routes = $this->routes[$method] ?? [];

        // Exact match first
        if (isset($routes[$path])) {
            [$controller, $action] = explode('@', $routes[$path]);
            return [$controller, $action, []];
        }

        // Pattern match: {id} → [0-9]+
        foreach ($routes as $pattern => $handler) {
            if (!str_contains($pattern, '{')) {
                continue;
            }

            $regex = preg_replace('/\{[a-z_]+\}/', '([0-9]+)', $pattern);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $path, $matches)) {
                array_shift($matches);
                [$controller, $action] = explode('@', $handler);
                return [$controller, $action, array_map('intval', $matches)];
            }
        }

        return [null, null, []];
    }

    private function sendSecurityHeaders(): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header(
            "Content-Security-Policy: default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
            "font-src 'self' https://fonts.gstatic.com; " .
            "img-src 'self' data: blob: https://api.iconify.design; " .
            "connect-src 'self' https://api.iconify.design https://api.simplesvg.com https://api.unisvg.com; " .
            "form-action 'self'; " .
            "frame-ancestors 'none'"
        );
    }
}
