<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class MemberModel
{
    public static function findOne(int $id): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM workspace_members WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // Live DB fetch — NEVER call this from session cache (INV-08, RULE-05)
    public static function getMembership(int $userId, int $workspaceId): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT wm.*, u.name AS user_name, u.email AS user_email, u.avatar_path
             FROM workspace_members wm
             JOIN users u ON u.id = wm.user_id
             WHERE wm.user_id = ? AND wm.workspace_id = ?'
        );
        $stmt->execute([$userId, $workspaceId]);
        return $stmt->fetch() ?: null;
    }

    public static function isApproved(int $userId, int $workspaceId): bool
    {
        $m = self::getMembership($userId, $workspaceId);
        return $m !== null && $m['status'] === 'Approved';
    }

    public static function createPending(int $workspaceId, int $userId, string $role = 'Member'): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO workspace_members (workspace_id, user_id, role, status) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$workspaceId, $userId, $role, 'Pending']);
        return (int)$db->lastInsertId();
    }

    public static function createApproved(int $workspaceId, int $userId, string $role = 'Owner'): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO workspace_members (workspace_id, user_id, role, status, approved_at)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$workspaceId, $userId, $role, 'Approved']);
        return (int)$db->lastInsertId();
    }

    // Re-open a previously rejected request (set back to Pending)
    public static function reopenRequest(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "UPDATE workspace_members SET status = 'Pending', requested_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    public static function approve(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "UPDATE workspace_members SET status = 'Approved', approved_at = NOW() WHERE id = ? AND status = 'Pending'"
        );
        $stmt->execute([$id]);
    }

    public static function reject(int $id): void
    {
        // No delete — preserve audit trail (status=Rejected)
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "UPDATE workspace_members SET status = 'Rejected' WHERE id = ? AND status = 'Pending'"
        );
        $stmt->execute([$id]);
    }

    public static function delete(int $workspaceId, int $userId): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'DELETE FROM workspace_members WHERE workspace_id = ? AND user_id = ?'
        );
        $stmt->execute([$workspaceId, $userId]);
    }

    public static function updateRole(int $id, string $role): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE workspace_members SET role = ? WHERE id = ?');
        $stmt->execute([$role, $id]);
    }

    public static function rejectAllPending(int $workspaceId): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "UPDATE workspace_members SET status = 'Rejected'
             WHERE workspace_id = ? AND status = 'Pending'"
        );
        $stmt->execute([$workspaceId]);
        return (int)$stmt->rowCount();
    }

    public static function listForWorkspace(int $workspaceId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT wm.*, u.name AS user_name, u.email AS user_email, u.avatar_path
             FROM workspace_members wm
             JOIN users u ON u.id = wm.user_id
             WHERE wm.workspace_id = ?
             ORDER BY FIELD(wm.role, \'Owner\', \'Admin\', \'Member\'), u.name ASC'
        );
        $stmt->execute([$workspaceId]);
        return $stmt->fetchAll();
    }

    public static function listPendingForWorkspace(int $workspaceId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT wm.*, u.name AS user_name, u.email AS user_email, u.avatar_path
             FROM workspace_members wm
             JOIN users u ON u.id = wm.user_id
             WHERE wm.workspace_id = ? AND wm.status = 'Pending'
             ORDER BY wm.requested_at ASC"
        );
        $stmt->execute([$workspaceId]);
        return $stmt->fetchAll();
    }
}
