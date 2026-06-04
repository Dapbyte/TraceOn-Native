<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\Database;
use App\Models\CardModel;
use App\Models\MemberModel;
use App\Helpers\ActivityLogger;

class CardController extends BaseController
{
    // ─── Cross-workspace check (INV-04, RULE-06) ────────────────────────────
    private function requireCardInWorkspace(int $cardId, int $workspaceId): array
    {
        $card = CardModel::findById($cardId);
        if (!$card || (int)$card['workspace_id'] !== $workspaceId) {
            Response::error('FORBIDDEN', 'Akses ditolak', 403);
        }
        return $card;
    }

    // ─── Create card ─────────────────────────────────────────────────────────
    public function create(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $workspaceId = (int)$this->request->input('workspace_id', 0);
        $this->requireWorkspaceMember($workspaceId, 'Admin');

        $title    = trim((string)$this->request->input('title', ''));
        $deadline = trim((string)$this->request->input('deadline', ''));

        if (mb_strlen($title) < 3 || mb_strlen($title) > 100) {
            Response::error('VALIDATION_ERROR', 'Judul card harus 3-100 karakter', 422);
        }

        $deadlineVal = null;
        if ($deadline !== '') {
            $dt = \DateTime::createFromFormat('Y-m-d', $deadline);
            if (!$dt || $dt->format('Y-m-d') !== $deadline) {
                Response::error('VALIDATION_ERROR', 'Format tanggal tidak valid', 422);
            }
            $deadlineVal = $deadline;
        }

        $userId = (int)$_SESSION['user_id'];
        $cardId = CardModel::create($title, $deadlineVal, $workspaceId, $userId);

        $action = ActivityLogger::buildAction('card_create', [
            'user' => $_SESSION['user_name'],
            'card' => $title,
        ]);
        ActivityLogger::log($workspaceId, $userId, $cardId, 'card_create', null, $title, $action);

        Response::success(['card_id' => $cardId], 'Card berhasil dibuat');
    }

    // ─── Update card ─────────────────────────────────────────────────────────
    public function update(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $cardId      = (int)$this->request->input('card_id', 0);
        $workspaceId = (int)$this->request->input('workspace_id', 0);
        $this->requireWorkspaceMember($workspaceId, 'Admin');

        $card = $this->requireCardInWorkspace($cardId, $workspaceId);

        $title    = trim((string)$this->request->input('title', $card['title']));
        $deadline = $this->request->input('deadline', $card['deadline']);
        $deadline = $deadline !== null ? trim((string)$deadline) : null;

        if (mb_strlen($title) < 3 || mb_strlen($title) > 100) {
            Response::error('VALIDATION_ERROR', 'Judul card harus 3-100 karakter', 422);
        }

        $deadlineVal = null;
        if ($deadline !== null && $deadline !== '') {
            $dt = \DateTime::createFromFormat('Y-m-d', $deadline);
            if (!$dt || $dt->format('Y-m-d') !== $deadline) {
                Response::error('VALIDATION_ERROR', 'Format tanggal tidak valid', 422);
            }
            $deadlineVal = $deadline;
        }

        CardModel::update($cardId, $title, $deadlineVal);

        if ($title !== $card['title']) {
            $action = ActivityLogger::buildAction('card_edit', [
                'user' => $_SESSION['user_name'],
                'old'  => $card['title'],
                'new'  => $title,
            ]);
            ActivityLogger::log($workspaceId, (int)$_SESSION['user_id'], $cardId, 'card_edit', $card['title'], $title, $action);
        }

        Response::success(null, 'Card diperbarui');
    }

    // ─── Delete card ─────────────────────────────────────────────────────────
    public function delete(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $cardId      = (int)$this->request->input('card_id', 0);
        $workspaceId = (int)$this->request->input('workspace_id', 0);
        $this->requireWorkspaceMember($workspaceId, 'Admin');

        $card = $this->requireCardInWorkspace($cardId, $workspaceId);

        // Save title before delete for activity log (card_id in activities has no FK — survives)
        $cardTitle = $card['title'];
        $userId    = (int)$_SESSION['user_id'];

        // Log BEFORE delete so cardId still exists in cards table (not required by schema but good practice)
        $action = ActivityLogger::buildAction('card_delete', [
            'user' => $_SESSION['user_name'],
            'card' => $cardTitle,
        ]);
        ActivityLogger::log($workspaceId, $userId, $cardId, 'card_delete', $cardTitle, null, $action);

        // FK CASCADE removes todos and card_access
        CardModel::delete($cardId);

        Response::success(null, 'Card berhasil dihapus');
    }

    // ─── Grant card access ────────────────────────────────────────────────────
    public function grantAccess(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $cardId      = (int)$this->request->input('card_id', 0);
        $targetUserId = (int)$this->request->input('user_id', 0);
        $workspaceId = (int)$this->request->input('workspace_id', 0);
        $this->requireWorkspaceMember($workspaceId, 'Admin');

        $this->requireCardInWorkspace($cardId, $workspaceId);

        // Verify target is an Approved member
        $targetMembership = MemberModel::getMembership($targetUserId, $workspaceId);
        if (!$targetMembership || $targetMembership['status'] !== 'Approved') {
            Response::error('NOT_FOUND', 'Anggota tidak ditemukan atau belum disetujui', 404);
        }

        $actorId = (int)$_SESSION['user_id'];

        try {
            CardModel::insertAccess($cardId, $targetUserId, $actorId);
        } catch (\PDOException $e) {
            // UNIQUE constraint violation = duplicate grant
            if (str_contains($e->getMessage(), '1062') || str_contains($e->getMessage(), 'Duplicate')) {
                Response::error('CONFLICT', 'Anggota ini sudah memiliki akses ke card tersebut', 409);
            }
            throw $e;
        }

        $action = ActivityLogger::buildAction('access_grant', [
            'actor' => $_SESSION['user_name'],
            'card'  => CardModel::findById($cardId)['title'] ?? '',
            'user'  => $targetMembership['user_name'],
        ]);
        ActivityLogger::log($workspaceId, $actorId, $cardId, 'access_grant', null, null, $action);

        Response::success(null, 'Akses diberikan');
    }

    // ─── Revoke card access ───────────────────────────────────────────────────
    public function revokeAccess(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $cardId       = (int)$this->request->input('card_id', 0);
        $targetUserId = (int)$this->request->input('user_id', 0);
        $workspaceId  = (int)$this->request->input('workspace_id', 0);
        $this->requireWorkspaceMember($workspaceId, 'Admin');

        $card = $this->requireCardInWorkspace($cardId, $workspaceId);

        $actorId = (int)$_SESSION['user_id'];

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            CardModel::deleteAccess($cardId, $targetUserId);

            $action = ActivityLogger::buildAction('access_revoke', [
                'actor' => $_SESSION['user_name'],
                'card'  => $card['title'],
                'user'  => MemberModel::getMembership($targetUserId, $workspaceId)['user_name'] ?? 'User',
            ]);
            ActivityLogger::log($workspaceId, $actorId, $cardId, 'access_revoke', null, null, $action);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Response::error('SERVER_ERROR', 'Gagal mencabut akses', 500);
        }

        Response::success(null, 'Akses dicabut');
    }
}
