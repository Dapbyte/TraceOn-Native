<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class ActivityModel
{
    private const SORT_SQL = ' ORDER BY a.created_at DESC, a.id DESC';

    public static function insert(
        int $workspaceId,
        ?int $userId,
        ?int $cardId,
        string $activityType,
        ?string $oldValue,
        ?string $newValue,
        string $actionText
    ): void {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO activities
               (workspace_id, user_id, card_id, activity_type, old_value, new_value, action)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$workspaceId, $userId, $cardId, $activityType, $oldValue, $newValue, $actionText]);
    }

    public static function list(int $workspaceId, int $limit = 50, int $offset = 0): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(self::selectSql() . ' WHERE a.workspace_id = ?' . self::SORT_SQL . ' LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $workspaceId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function search(int $workspaceId, string $query, int $limit = 50, int $offset = 0): array
    {
        $filters = ['search' => $query];
        return self::listFiltered($workspaceId, $filters, $limit, $offset);
    }

    public static function listFiltered(int $workspaceId, array $filters, int $limit = 50, int $offset = 0): array
    {
        [$where, $params] = self::buildWhere($workspaceId, $filters);
        $db = Database::getInstance();
        $stmt = $db->prepare(self::selectSql() . $where . self::SORT_SQL . ' LIMIT ? OFFSET ?');

        $position = 1;
        foreach ($params as $param) {
            $stmt->bindValue($position++, $param['value'], $param['type']);
        }
        $stmt->bindValue($position++, $limit, \PDO::PARAM_INT);
        $stmt->bindValue($position, $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function count(int $workspaceId, string $query = '', array $filters = []): int
    {
        if ($query !== '') {
            $filters['search'] = $query;
        }

        [$where, $params] = self::buildWhere($workspaceId, $filters);
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT COUNT(*) FROM activities a' . $where);

        $position = 1;
        foreach ($params as $param) {
            $stmt->bindValue($position++, $param['value'], $param['type']);
        }
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    public static function deleteAll(int $workspaceId): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM activities WHERE workspace_id = ?');
        $stmt->execute([$workspaceId]);
        return (int)$stmt->rowCount();
    }

    private static function selectSql(): string
    {
        return 'SELECT a.id, a.workspace_id, a.user_id, a.card_id, a.activity_type,
                       a.old_value, a.new_value, a.action, a.created_at,
                       COALESCE(u.name, \'Sistem\') AS user_name,
                       u.avatar_path AS avatar_path
                FROM activities a
                LEFT JOIN users u ON u.id = a.user_id';
    }

    private static function buildWhere(int $workspaceId, array $filters): array
    {
        $clauses = ['a.workspace_id = ?'];
        $params = [
            ['value' => $workspaceId, 'type' => \PDO::PARAM_INT],
        ];

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            if (mb_strlen($search) >= 3) {
                $clauses[] = 'MATCH(a.action) AGAINST(? IN BOOLEAN MODE)';
                $params[] = ['value' => self::booleanSearch($search), 'type' => \PDO::PARAM_STR];
            } else {
                $clauses[] = 'a.action LIKE ?';
                $params[] = ['value' => '%' . $search . '%', 'type' => \PDO::PARAM_STR];
            }
        }

        $types = $filters['type'] ?? [];
        if (is_string($types) && $types !== '') {
            $types = [$types];
        }
        if (is_array($types) && !empty($types)) {
            $cleanTypes = array_values(array_filter(array_map(
                static fn($type) => preg_replace('/[^a-z_]/', '', (string)$type),
                $types
            )));

            if (!empty($cleanTypes)) {
                $placeholders = implode(',', array_fill(0, count($cleanTypes), '?'));
                $clauses[] = 'a.activity_type IN (' . $placeholders . ')';
                foreach ($cleanTypes as $type) {
                    $params[] = ['value' => $type, 'type' => \PDO::PARAM_STR];
                }
            }
        }

        $dateFrom = self::validDate($filters['date_from'] ?? null);
        $dateTo = self::validDate($filters['date_to'] ?? null);

        if ($dateFrom === null && $dateTo === null && !empty($filters['default_last_7_days'])) {
            $dateFrom = (new \DateTimeImmutable('-7 days'))->format('Y-m-d');
        }

        if ($dateFrom !== null) {
            $clauses[] = 'a.created_at >= ?';
            $params[] = ['value' => $dateFrom . ' 00:00:00', 'type' => \PDO::PARAM_STR];
        }
        if ($dateTo !== null) {
            $clauses[] = 'a.created_at <= ?';
            $params[] = ['value' => $dateTo . ' 23:59:59', 'type' => \PDO::PARAM_STR];
        }

        $userId = (int)($filters['user_id'] ?? 0);
        if ($userId > 0) {
            $clauses[] = 'a.user_id = ?';
            $params[] = ['value' => $userId, 'type' => \PDO::PARAM_INT];
        }

        return [' WHERE ' . implode(' AND ', $clauses), $params];
    }

    private static function validDate(mixed $value): ?string
    {
        $date = trim((string)$value);
        if ($date === '') {
            return null;
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $dt && $dt->format('Y-m-d') === $date ? $date : null;
    }

    private static function booleanSearch(string $query): string
    {
        $terms = preg_split('/\s+/', trim($query)) ?: [];
        $terms = array_filter(array_map(
            static fn($term) => preg_replace('/[^\p{L}\p{N}_-]/u', '', $term),
            $terms
        ));

        if (empty($terms)) {
            return $query;
        }

        return implode(' ', array_map(static fn($term) => '+' . $term . '*', $terms));
    }
}
