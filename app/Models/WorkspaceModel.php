<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class WorkspaceModel
{
    public static function findById(int $id): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT w.*, u.name AS owner_name
             FROM workspaces w
             JOIN users u ON u.id = w.owner_id
             WHERE w.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByInviteCode(string $code): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM workspaces WHERE invite_code = ?');
        $stmt->execute([$code]);
        return $stmt->fetch() ?: null;
    }

    public static function insert(string $name, ?string $deadline, string $inviteCode, int $ownerId): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO workspaces (name, deadline, invite_code, owner_id) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$name, $deadline, $inviteCode, $ownerId]);
        return (int)$db->lastInsertId();
    }

    public static function rename(int $id, string $name): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE workspaces SET name = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$name, $id]);
    }

    public static function updateDeadline(int $id, ?string $deadline): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE workspaces SET deadline = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$deadline, $id]);
    }

    public static function updateInviteCode(int $id, string $code): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('UPDATE workspaces SET invite_code = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$code, $id]);
    }

    public static function delete(int $id): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM workspaces WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function listOwned(int $userId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT w.*,
                    (SELECT COUNT(*) FROM workspace_members wm WHERE wm.workspace_id = w.id AND wm.status = 'Approved') AS member_count
             FROM workspaces w
             WHERE w.owner_id = ?
             ORDER BY w.updated_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function listJoined(int $userId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT w.*,
                    (SELECT COUNT(*) FROM workspace_members wm2 WHERE wm2.workspace_id = w.id AND wm2.status = 'Approved') AS member_count
             FROM workspaces w
             JOIN workspace_members wm ON wm.workspace_id = w.id
             WHERE wm.user_id = ? AND wm.status = 'Approved' AND wm.role != 'Owner'
             ORDER BY w.updated_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function getInviteCode(int $id): ?string
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT invite_code FROM workspaces WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $row['invite_code'] : null;
    }

    /**
     * Generate unique 8-char uppercase hex invite code.
     * Max 3 retries with usleep(1000) between; throws on exhaust (RULE-31).
     */
    public static function generateUniqueInviteCode(): string
    {
        $db = Database::getInstance();

        for ($i = 0; $i < 3; $i++) {
            $code = strtoupper(bin2hex(random_bytes(4)));

            $stmt = $db->prepare('SELECT COUNT(*) FROM workspaces WHERE invite_code = ?');
            $stmt->execute([$code]);

            if ((int)$stmt->fetchColumn() === 0) {
                return $code;
            }

            usleep(1000);
        }

        throw new \RuntimeException('Gagal membuat kode undangan unik setelah 3 percobaan', 500);
    }

    /**
     * Get progress stats for workspace list display.
     * Returns ['total_cards', 'done_cards', 'progress_pct']
     */
    public static function getProgressStats(int $workspaceId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT id FROM cards WHERE workspace_id = ?');
        $stmt->execute([$workspaceId]);
        $cards = $stmt->fetchAll();

        if (empty($cards)) {
            return ['total_cards' => 0, 'done_cards' => 0, 'progress_pct' => 0.0];
        }

        $totalCards = count($cards);
        $doneCards  = 0;

        foreach ($cards as $card) {
            $s = $db->prepare(
                'SELECT COUNT(*) AS total, SUM(CASE WHEN status=? THEN 1 ELSE 0 END) AS done
                 FROM todos WHERE card_id = ?'
            );
            $s->execute(['done', $card['id']]);
            $row = $s->fetch();
            if ((int)$row['total'] > 0 && (int)$row['done'] === (int)$row['total']) {
                $doneCards++;
            }
        }

        $pct = round(($doneCards / $totalCards) * 100, 1);
        return ['total_cards' => $totalCards, 'done_cards' => $doneCards, 'progress_pct' => $pct];
    }
}
