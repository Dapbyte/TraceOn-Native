<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\ActivityModel;

class ActivityLogger
{
    // RULE-17: Caller owns the transaction. ActivityLogger::log() NEVER opens its own TX.
    // All activity strings come from activity_templates.php — NEVER ad-hoc (RULE-19).

    private static array $templates = [];

    public static function log(
        int    $workspaceId,
        ?int   $userId,
        ?int   $cardId,
        string $activityType,
        ?string $oldValue,
        ?string $newValue,
        string $actionText
    ): void {
        ActivityModel::insert($workspaceId, $userId, $cardId, $activityType, $oldValue, $newValue, $actionText);
    }

    public static function buildAction(string $activityType, array $vars): string
    {
        if (empty(self::$templates)) {
            self::$templates = require __DIR__ . '/../Config/activity_templates.php';
        }

        $template = self::$templates[$activityType] ?? $activityType;

        foreach ($vars as $key => $value) {
            $template = str_replace('{' . $key . '}', (string)$value, $template);
        }

        return $template;
    }
}
