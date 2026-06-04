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
        $stmt = $db->prepare('SELECT id FROM cards WHERE workspace_id = ?');
        $stmt->execute([$workspaceId]);
        $cards = $stmt->fetchAll();

        if (empty($cards)) {
            return 0.0;
        }

        $total = 0;
        foreach ($cards as $card) {
            $total += self::forCard((int)$card['id']);
        }

        return round($total / count($cards), 1);
    }
}
