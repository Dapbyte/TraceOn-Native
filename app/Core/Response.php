<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    public static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(mixed $data = null, string $message = '', int $status = 200): never
    {
        $payload = ['success' => true];
        if ($data !== null)      $payload['data']    = $data;
        if ($message !== '')     $payload['message'] = $message;
        self::json($payload, $status);
    }

    public static function error(string $code, string $message, int $status): never
    {
        self::json([
            'success' => false,
            'error'   => $code,
            'message' => $message,
        ], $status);
    }

    public static function redirect(string $path, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $path);
        exit;
    }

    public static function notFound(bool $isApi = false): never
    {
        if ($isApi) {
            self::error('NOT_FOUND', 'Resource tidak ditemukan', 404);
        }
        http_response_code(404);
        echo '<h1>404 — Halaman tidak ditemukan</h1>';
        exit;
    }
}
