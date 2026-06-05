<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\Database;
use App\Models\TodoModel;
use App\Models\CardModel;
use App\Helpers\ProgressCalculator;
use App\Helpers\ActivityLogger;

class TodoController extends BaseController
{
    /**
     * Resolve todo's workspace + enforce XWS and card-access auth.
     * Returns [card, workspaceId] on success; exits with 403 on failure.
     */
    private function resolveAndAuthorize(int $cardId): array
    {
        $card = CardModel::findById($cardId);
        if (!$card) {
            Response::error('NOT_FOUND', 'Card tidak ditemukan', 404);
        }

        $workspaceId = (int)$card['workspace_id'];
        $membership  = $this->requireWorkspaceMember($workspaceId);
        $userId      = (int)$_SESSION['user_id'];

        // Card-access auth (RULE-08): Owner OR Admin OR has card_access row
        if (!in_array($membership['role'], ['Owner', 'Admin'], true)) {
            if (!CardModel::userHasAccess($cardId, $userId)) {
                Response::error('FORBIDDEN', 'Anda tidak memiliki akses ke card ini', 403);
            }
        }

        return [$card, $workspaceId, $membership];
    }

    // ─── Create todo ──────────────────────────────────────────────────────────
    public function create(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $cardId = (int)$this->request->input('card_id', 0);
        $title  = trim((string)$this->request->input('title', ''));

        if ($cardId === 0) {
            Response::error('VALIDATION_ERROR', 'card_id diperlukan', 422);
        }
        if ($title === '' || mb_strlen($title) > 255) {
            Response::error('VALIDATION_ERROR', 'Judul todo harus 1-255 karakter', 422);
        }

        [$card, $workspaceId] = $this->resolveAndAuthorize($cardId);
        $userId = (int)$_SESSION['user_id'];

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $todoId   = TodoModel::create($title, $cardId, $userId);
            $progress = ProgressCalculator::forCard($cardId);

            $action = ActivityLogger::buildAction('todo_create', [
                'user' => $_SESSION['user_name'],
                'todo' => $title,
                'card' => $card['title'],
            ]);
            ActivityLogger::log($workspaceId, $userId, $cardId, 'todo_create', null, $title, $action);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Response::error('SERVER_ERROR', 'Gagal menambahkan todo', 500);
        }

        Response::success(['todo_id' => $todoId, 'progress_card' => $progress], 'Todo ditambahkan');
    }

    // ─── Update todo ──────────────────────────────────────────────────────────
    public function update(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $todoId = (int)$this->request->input('todo_id', 0);
        if ($todoId === 0) {
            Response::error('VALIDATION_ERROR', 'todo_id diperlukan', 422);
        }

        $todo = TodoModel::findById($todoId);
        if (!$todo) {
            Response::error('NOT_FOUND', 'Todo tidak ditemukan', 404);
        }

        $cardId = (int)$todo['card_id'];
        [$card, $workspaceId] = $this->resolveAndAuthorize($cardId);
        $userId = (int)$_SESSION['user_id'];

        $newTitle    = $this->request->input('title');
        $newStatus   = $this->request->input('status');
        $newPriority = $this->request->input('priority');

        if ($newTitle !== null) {
            $newTitle = trim((string)$newTitle);
            if ($newTitle === '' || mb_strlen($newTitle) > 255) {
                Response::error('VALIDATION_ERROR', 'Judul todo harus 1-255 karakter', 422);
            }
        }

        if ($newStatus !== null && !TodoModel::isValidStatus((string)$newStatus)) {
            Response::error('VALIDATION_ERROR', 'Status tidak valid', 422);
        }

        if ($newPriority !== null && !TodoModel::isValidPriority((string)$newPriority)) {
            Response::error('VALIDATION_ERROR', 'Prioritas tidak valid', 422);
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            if ($newTitle !== null && $newTitle !== $todo['title']) {
                TodoModel::updateTitle($todoId, $newTitle);
                $action = ActivityLogger::buildAction('todo_edit', [
                    'user' => $_SESSION['user_name'],
                    'old'  => $todo['title'],
                    'new'  => $newTitle,
                ]);
                ActivityLogger::log($workspaceId, $userId, $cardId, 'todo_edit', $todo['title'], $newTitle, $action);
            }

            if ($newStatus !== null && $newStatus !== $todo['status']) {
                TodoModel::updateStatus($todoId, (string)$newStatus);
                $action = ActivityLogger::buildAction('todo_status', [
                    'user' => $_SESSION['user_name'],
                    'todo' => $newTitle !== null ? $newTitle : $todo['title'],
                    'old'  => $todo['status'],
                    'new'  => $newStatus,
                ]);
                ActivityLogger::log($workspaceId, $userId, $cardId, 'todo_status', $todo['status'], (string)$newStatus, $action);
            }

            if ($newPriority !== null && $newPriority !== ($todo['priority'] ?? 'medium')) {
                TodoModel::updatePriority($todoId, (string)$newPriority);
                $action = ActivityLogger::buildAction('todo_edit', [
                    'user' => $_SESSION['user_name'],
                    'todo' => $newTitle !== null ? $newTitle : $todo['title'],
                    'old'  => 'priority:' . ($todo['priority'] ?? 'medium'),
                    'new'  => 'priority:' . $newPriority,
                ]);
                ActivityLogger::log($workspaceId, $userId, $cardId, 'todo_edit', $todo['priority'] ?? 'medium', $newPriority, $action);
            }

            $progress = ProgressCalculator::forCard($cardId);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Response::error('SERVER_ERROR', 'Gagal memperbarui todo', 500);
        }

        Response::success(['progress_card' => $progress], 'Todo diperbarui');
    }

    // ─── Delete todo (hard delete — RULE-18, INV-12) ──────────────────────────
    public function delete(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $todoId = (int)$this->request->input('todo_id', 0);
        if ($todoId === 0) {
            Response::error('VALIDATION_ERROR', 'todo_id diperlukan', 422);
        }

        $todo = TodoModel::findById($todoId);
        if (!$todo) {
            Response::error('NOT_FOUND', 'Todo tidak ditemukan', 404);
        }

        $cardId = (int)$todo['card_id'];
        [$card, $workspaceId] = $this->resolveAndAuthorize($cardId);
        $userId = (int)$_SESSION['user_id'];

        $todoTitle = $todo['title'];

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            // Log before delete because the todo row is removed permanently.
            $action = ActivityLogger::buildAction('todo_delete', [
                'user' => $_SESSION['user_name'],
                'todo' => $todoTitle,
                'card' => $card['title'],
            ]);
            ActivityLogger::log($workspaceId, $userId, $cardId, 'todo_delete', $todoTitle, null, $action);

            // Hard delete - no soft delete (RULE-18)
            TodoModel::delete($todoId);

            $progress = ProgressCalculator::forCard($cardId);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Response::error('SERVER_ERROR', 'Gagal menghapus todo', 500);
        }

        Response::success(
            ['progress_card' => $progress],
            'Todo berhasil dihapus'
        );
    }
}
