<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class CardModel
{
    public static function findById(int $id): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM cards WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function getWorkspaceId(int $cardId): ?int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT workspace_id FROM cards WHERE id = ?');
        $stmt->execute([$cardId]);
        $row = $stmt->fetch();
        return $row ? (int)$row['workspace_id'] : null;
    }

    public static function listForWorkspace(int $workspaceId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT c.*,
                    (SELECT COUNT(*) FROM todos t WHERE t.card_id = c.id) AS total_todos,
                    (SELECT COUNT(*) FROM todos t WHERE t.card_id = c.id AND t.status = ?) AS done_todos
             FROM cards c
             WHERE c.workspace_id = ?
             ORDER BY c.created_at ASC'
        );
        $stmt->execute(['done', $workspaceId]);
        return $stmt->fetchAll();
    }

    public static function create(string $title, ?string $deadline, int $workspaceId, int $createdBy): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO cards (workspace_id, title, deadline, created_by) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$workspaceId, $title, $deadline, $createdBy]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, string $title, ?string $deadline): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE cards SET title = ?, deadline = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$title, $deadline, $id]);
    }

    public static function delete(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM cards WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function insertAccess(int $cardId, int $userId, int $grantedBy): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO card_access (card_id, user_id, granted_by) VALUES (?, ?, ?)'
        );
        $stmt->execute([$cardId, $userId, $grantedBy]);
    }

    public static function deleteAccess(int $cardId, int $userId): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM card_access WHERE card_id = ? AND user_id = ?');
        $stmt->execute([$cardId, $userId]);
    }

    public static function accessUserIds(int $cardId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT ca.user_id, u.name, u.avatar_path
             FROM card_access ca
             JOIN users u ON u.id = ca.user_id
             WHERE ca.card_id = ?'
        );
        $stmt->execute([$cardId]);
        return $stmt->fetchAll();
    }

    public static function deleteAccessForUserInWorkspace(int $userId, int $workspaceId): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'DELETE ca FROM card_access ca
             JOIN cards c ON c.id = ca.card_id
             WHERE ca.user_id = ? AND c.workspace_id = ?'
        );
        $stmt->execute([$userId, $workspaceId]);
    }

    public static function userHasAccess(int $cardId, int $userId): bool
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT COUNT(*) FROM card_access WHERE card_id = ? AND user_id = ?');
        $stmt->execute([$cardId, $userId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
