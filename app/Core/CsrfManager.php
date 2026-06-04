<?php

declare(strict_types=1);

namespace App\Core;

class CsrfManager
{
    // Session-scoped token. NOT rotated per request — only on login and logout.
    // Per-request rotation causes multi-tab 403 errors (INV-09, RULE-04).

    public static function generate(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validate(string $token): bool
    {
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    // Call ONLY on login and logout (privilege change)
    public static function rotate(): void
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
