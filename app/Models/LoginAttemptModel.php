<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class LoginAttemptModel
{
    // login type: 5 failures → block 15 minutes
    // join type:  3 failures → block 30 seconds
    private const THRESHOLDS = [
        'login' => ['max' => 5, 'interval' => 'INTERVAL 15 MINUTE'],
        'join'  => ['max' => 3, 'interval' => 'INTERVAL 30 SECOND'],
    ];

    public static function findActiveBlock(string $ip, string $type = 'login'): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT *, TIMESTAMPDIFF(SECOND, NOW(), blocked_until) AS seconds_remaining
             FROM login_attempts
             WHERE ip_address = ? AND type = ? AND blocked_until IS NOT NULL AND blocked_until > NOW()'
        );
        $stmt->execute([$ip, $type]);
        return $stmt->fetch() ?: null;
    }

    public static function registerFailure(string $ip, string $type = 'login'): void
    {
        $db        = Database::getInstance();
        $threshold = self::THRESHOLDS[$type] ?? self::THRESHOLDS['login'];

        // Check if row exists
        $stmt = $db->prepare(
            'SELECT id, attempt_count FROM login_attempts WHERE ip_address = ? AND type = ?'
        );
        $stmt->execute([$ip, $type]);
        $row = $stmt->fetch();

        if ($row) {
            $newCount = (int)$row['attempt_count'] + 1;

            if ($newCount >= $threshold['max']) {
                $db->prepare(
                    'UPDATE login_attempts
                     SET attempt_count = ?, last_attempt_at = NOW(), blocked_until = NOW() + ' . $threshold['interval'] . '
                     WHERE id = ?'
                )->execute([$newCount, $row['id']]);
            } else {
                $db->prepare(
                    'UPDATE login_attempts
                     SET attempt_count = ?, last_attempt_at = NOW()
                     WHERE id = ?'
                )->execute([$newCount, $row['id']]);
            }
        } else {
            $db->prepare(
                'INSERT INTO login_attempts (ip_address, type, attempt_count) VALUES (?, ?, 1)'
            )->execute([$ip, $type]);
        }
    }

    public static function reset(string $ip, string $type = 'login'): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM login_attempts WHERE ip_address = ? AND type = ?');
        $stmt->execute([$ip, $type]);
    }

    public static function purgeExpiredBlocks(): void
    {
        $db = Database::getInstance();
        $db->prepare(
            'DELETE FROM login_attempts WHERE blocked_until IS NOT NULL AND blocked_until < NOW()'
        )->execute();
    }
}
