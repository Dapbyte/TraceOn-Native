<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;

class ProgressCalculator
{
    public static function forCard(int $cardId): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT
               COUNT(*) AS total,
               SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS done
             FROM todos
             WHERE card_id = ?'
        );
        $stmt->execute(['done', $cardId]);
        $row = $stmt->fetch();

        if ((int)$row['total'] === 0) {
            return 0;
        }

        return (int) round(((int)$row['done'] / (int)$row['total']) * 100);
    }

    public static function forWorkspace(int $workspaceId): float
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT
               COUNT(*) AS total,
               SUM(CASE WHEN t.status = ? THEN 1 ELSE 0 END) AS done
             FROM cards c
             JOIN todos t ON t.card_id = c.id
             WHERE c.workspace_id = ?'
        );
        $stmt->execute(['done', $workspaceId]);
        $row = $stmt->fetch();

        if (!$row || (int)$row['total'] === 0) {
            return 0.0;
        }

        return round(((int)$row['done'] / (int)$row['total']) * 100, 1);
    }
}
