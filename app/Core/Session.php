<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isProduction = (APP_ENV === 'production');

        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime',  (string)SESSION_TIMEOUT);
        ini_set('session.use_strict_mode', '1');

        if ($isProduction) {
            ini_set('session.cookie_secure', '1');
        }

        session_name(SESSION_NAME);
        session_start();

        // Rotate last_activity on start
        if (!isset($_SESSION['_initiated'])) {
            $_SESSION['_initiated'] = true;
        }
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        $_SESSION = [];
        session_unset();
        session_destroy();
        session_write_close();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function checkIdle(): bool
    {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return true;
        }

        if ((time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            self::destroy();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    public static function getFlash(string $key): ?string
    {
        $msg = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $msg;
    }
}
