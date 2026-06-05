<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class TodoModel
{
    private const VALID_STATUSES = ['pending', 'in_progress', 'done'];
    private const VALID_PRIORITIES = ['low', 'medium', 'high'];

    public static function findById(int $id): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM todos WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function listForCard(int $cardId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT * FROM todos WHERE card_id = ? ORDER BY created_at ASC'
        );
        $stmt->execute([$cardId]);
        return $stmt->fetchAll();
    }

    public static function create(string $title, int $cardId, int $createdBy): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO todos (card_id, title, status, created_by) VALUES (?, ?, 'pending', ?)"
        );
        $stmt->execute([$cardId, $title, $createdBy]);
        return (int)$db->lastInsertId();
    }

    public static function updateTitle(int $id, string $title): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE todos SET title = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$title, $id]);
    }

    public static function updateStatus(int $id, string $status): void
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException('Invalid todo status: ' . $status);
        }
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE todos SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public static function updatePriority(int $id, string $priority): void
    {
        if (!in_array($priority, self::VALID_PRIORITIES, true)) {
            throw new \InvalidArgumentException('Invalid todo priority: ' . $priority);
        }
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE todos SET priority = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$priority, $id]);
    }

    public static function isValidPriority(string $priority): bool
    {
        return in_array($priority, self::VALID_PRIORITIES, true);
    }

    // Hard delete — no soft delete, no deleted_at (INV-12, RULE-18)
    public static function delete(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM todos WHERE id = ?');
        $stmt->execute([$id]);
    }

    // Returns card_id for cross-workspace check
    public static function getCardId(int $todoId): ?int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT card_id FROM todos WHERE id = ?');
        $stmt->execute([$todoId]);
        $row = $stmt->fetch();
        return $row ? (int)$row['card_id'] : null;
    }

    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::VALID_STATUSES, true);
    }
}
