<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class UserModel
{
    public static function findById(int $id): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT id, name, email, avatar_path, created_at, updated_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT id, name, email, password, avatar_path, created_at FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function existsByEmail(string $email): bool
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function create(string $name, string $email, string $hash): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, $hash]);
        return (int)$db->lastInsertId();
    }

    public static function updateName(int $id, string $name): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE users SET name = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$name, $id]);
    }

    public static function updateAvatar(int $id, string $path): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE users SET avatar_path = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$path, $id]);
    }
}
