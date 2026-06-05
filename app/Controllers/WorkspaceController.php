<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\CsrfManager;
use App\Core\Database;
use App\Models\WorkspaceModel;
use App\Models\MemberModel;
use App\Models\CardModel;
use App\Models\TodoModel;
use App\Models\LoginAttemptModel;
use App\Helpers\ActivityLogger;
use App\Helpers\ProgressCalculator;

class WorkspaceController extends BaseController
{
    // ─── Page renders ──────────────────────────────────────────────────────

    public function dashboard(): void
    {
        $this->requireAuth();

        $userId          = (int)$_SESSION['user_id'];
        $ownedWorkspaces = WorkspaceModel::listOwned($userId);
        $joinedWorkspaces = WorkspaceModel::listJoined($userId);

        $this->render('pages.dashboard', [
            'layout'           => 'layouts.main',
            'pageTitle'        => 'Dashboard — TraceOn',
            'csrf'             => CsrfManager::generate(),
            'ownedWorkspaces'  => $ownedWorkspaces,
            'joinedWorkspaces' => $joinedWorkspaces,
        ]);
    }

    public function show(int $id): void
    {
        $this->requireAuth();
        $membership = $this->requireWorkspaceMember($id);

        $workspace = WorkspaceModel::findById($id);
        if (!$workspace) {
            Response::notFound($this->request->isApi());
        }

        $userId         = (int)$_SESSION['user_id'];
        $members        = MemberModel::listForWorkspace($id);
        $pendingMembers = MemberModel::listPendingForWorkspace($id);
        $cards          = CardModel::listForWorkspace($id);

        // Attach progress + access info to each card
        foreach ($cards as &$card) {
            $cardId             = (int)$card['id'];
            $card['progress']   = ProgressCalculator::forCard($cardId);
            $card['todos']      = TodoModel::listForCard($cardId);
            $card['access_users'] = CardModel::accessUserIds($cardId);
            $card['user_has_access'] = (
                in_array($membership['role'], ['Owner', 'Admin'], true)
                || CardModel::userHasAccess($cardId, $userId)
            );
        }
        unset($card);

        $wsProgress = ProgressCalculator::forWorkspace($id);

        $this->render('pages.workspace', [
            'layout'         => 'layouts.main',
            'pageTitle'      => htmlspecialchars($workspace['name'], ENT_QUOTES, 'UTF-8') . ' — TraceOn',
            'csrf'           => CsrfManager::generate(),
            'workspace'      => $workspace,
            'membership'     => $membership,
            'members'        => $members,
            'pendingMembers' => $pendingMembers,
            'cards'          => $cards,
            'wsProgress'     => $wsProgress,
        ]);
    }

    // ─── Workspace mutations ────────────────────────────────────────────────

    public function create(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $name     = trim((string)$this->request->input('name', ''));
        $deadline = trim((string)$this->request->input('deadline', ''));

        if ($name === '' || mb_strlen($name) < 1 || mb_strlen($name) > 100) {
            Response::error('VALIDATION_ERROR', 'Nama workspace harus 1-100 karakter', 422);
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
        $db     = Database::getInstance();

        try {
            $code = WorkspaceModel::generateUniqueInviteCode();
        } catch (\RuntimeException $e) {
            Response::error('SERVER_ERROR', 'Gagal membuat workspace. Coba lagi.', 500);
        }

        $db->beginTransaction();
        try {
            $workspaceId = WorkspaceModel::insert($name, $deadlineVal, $code, $userId);
            MemberModel::createApproved($workspaceId, $userId, 'Owner');
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Response::error('SERVER_ERROR', 'Gagal membuat workspace', 500);
        }

        Response::success(['id' => $workspaceId, 'invite_code' => $code], 'Workspace berhasil dibuat');
    }

    public function shareCode(): void
    {
        $this->requireAuth();

        $workspaceId = (int)$this->request->query('workspace_id', 0);
        if ($workspaceId === 0) {
            Response::error('VALIDATION_ERROR', 'workspace_id diperlukan', 422);
        }

        $this->requireWorkspaceMember($workspaceId, 'Admin');

        $code = WorkspaceModel::getInviteCode($workspaceId);
        if ($code === null) {
            Response::notFound(true);
        }

        Response::success(['invite_code' => $code]);
    }

    public function regenerateCode(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $workspaceId = (int)$this->request->input('workspace_id', 0);
        $this->requireWorkspaceMember($workspaceId, 'Owner');

        $workspace = WorkspaceModel::findById($workspaceId);
        if (!$workspace) {
            Response::notFound(true);
        }

        try {
            $newCode = WorkspaceModel::generateUniqueInviteCode();
        } catch (\RuntimeException $e) {
            Response::error('SERVER_ERROR', 'Gagal membuat kode baru', 500);
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            WorkspaceModel::updateInviteCode($workspaceId, $newCode);
            MemberModel::rejectAllPending($workspaceId);

            $action = ActivityLogger::buildAction('invite_regenerate', [
                'actor' => $_SESSION['user_name'],
            ]);
            ActivityLogger::log($workspaceId, (int)$_SESSION['user_id'], null, 'invite_regenerate', null, null, $action);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Response::error('SERVER_ERROR', 'Gagal memperbarui kode undangan', 500);
        }

        Response::success(['new_invite_code' => $newCode], 'Kode undangan diperbarui');
    }

    public function rename(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $workspaceId = (int)$this->request->input('workspace_id', 0);
        $this->requireWorkspaceMember($workspaceId, 'Owner');

        $workspace = WorkspaceModel::findById($workspaceId);
        if (!$workspace) {
            Response::notFound(true);
        }

        $name = trim((string)$this->request->input('name', ''));
        if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
            Response::error('VALIDATION_ERROR', 'Nama workspace harus 3-100 karakter', 422);
        }

        $oldName = $workspace['name'];
        WorkspaceModel::rename($workspaceId, $name);

        $action = ActivityLogger::buildAction('workspace_rename', [
            'actor' => $_SESSION['user_name'],
            'old'   => $oldName,
            'new'   => $name,
        ]);
        ActivityLogger::log($workspaceId, (int)$_SESSION['user_id'], null, 'workspace_rename', $oldName, $name, $action);

        Response::success(null, 'Nama workspace diperbarui');
    }

    public function updateDeadline(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $workspaceId = (int)$this->request->input('workspace_id', 0);
        $this->requireWorkspaceMember($workspaceId, 'Owner');

        $deadline = trim((string)$this->request->input('deadline', ''));

        $deadlineVal = null;
        if ($deadline !== '') {
            $dt = \DateTime::createFromFormat('Y-m-d', $deadline);
            if (!$dt || $dt->format('Y-m-d') !== $deadline) {
                Response::error('VALIDATION_ERROR', 'Format tanggal tidak valid (YYYY-MM-DD)', 422);
            }
            $deadlineVal = $deadline;
        }

        WorkspaceModel::updateDeadline($workspaceId, $deadlineVal);
        Response::success(null, 'Deadline diperbarui');
    }

    public function delete(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $workspaceId  = (int)$this->request->input('workspace_id', 0);
        $nameConfirm  = trim((string)$this->request->input('name_confirm', ''));

        $this->requireWorkspaceMember($workspaceId, 'Owner');

        $workspace = WorkspaceModel::findById($workspaceId);
        if (!$workspace) {
            Response::notFound(true);
        }

        // Server-side name confirmation (RULE-11, spec §5.2)
        if ($nameConfirm !== $workspace['name']) {
            Response::error('VALIDATION_ERROR', 'Nama workspace tidak sesuai', 422);
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            WorkspaceModel::delete($workspaceId);  // FK CASCADE removes all children
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Response::error('SERVER_ERROR', 'Gagal menghapus workspace', 500);
        }

        Response::success(['redirect' => '/dashboard'], 'Workspace berhasil dihapus');
    }

    // ─── Membership ─────────────────────────────────────────────────────────

    public function joinRequest(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $rawCode = (string)$this->request->input('invite_code', '');
        $code    = strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim($rawCode)));
        $ip      = $this->request->ip();

        if (mb_strlen($code) !== 8) {
            Response::error('NOT_FOUND', 'Kode undangan tidak ditemukan', 404);
        }

        // IP cooldown for join attempts (3 fails / 30s)
        $block = LoginAttemptModel::findActiveBlock($ip, 'join');
        if ($block) {
            Response::error('COOLDOWN', 'Terlalu banyak percobaan. Coba lagi dalam 30 detik.', 429);
        }

        $workspace = WorkspaceModel::findByInviteCode($code);
        if (!$workspace) {
            LoginAttemptModel::registerFailure($ip, 'join');
            Response::error('NOT_FOUND', 'Kode undangan tidak ditemukan', 404);
        }

        $userId      = (int)$_SESSION['user_id'];
        $workspaceId = (int)$workspace['id'];

        // Check existing membership
        $existing = MemberModel::getMembership($userId, $workspaceId);
        if ($existing) {
            if ($existing['status'] === 'Approved') {
                Response::error('CONFLICT', 'Kamu sudah bergabung ke workspace ini', 409);
            }
            if ($existing['status'] === 'Pending') {
                Response::error('CONFLICT', 'Permohonanmu sedang menunggu persetujuan', 409);
            }
            // Rejected — allow re-request
            MemberModel::reopenRequest((int)$existing['id']);
        } else {
            MemberModel::createPending($workspaceId, $userId, 'Member');
        }

        // Reset join cooldown on success
        LoginAttemptModel::reset($ip, 'join');

        $action = ActivityLogger::buildAction('member_join', [
            'user' => $_SESSION['user_name'],
        ]);
        ActivityLogger::log($workspaceId, $userId, null, 'member_join', null, null, $action);

        Response::success(['status' => 'pending'], 'Permohonan bergabung berhasil dikirim');
    }

    public function approveRequest(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $requestId   = (int)$this->request->input('request_id', 0);
        $action      = (string)$this->request->input('action', '');

        if (!in_array($action, ['approve', 'reject'], true)) {
            Response::error('VALIDATION_ERROR', 'Action harus approve atau reject', 422);
        }

        $member = MemberModel::findOne($requestId);
        if (!$member) {
            Response::notFound(true);
        }

        $workspaceId = (int)$member['workspace_id'];
        $this->requireWorkspaceMember($workspaceId, 'Admin');

        $targetUser = \App\Models\UserModel::findById((int)$member['user_id']);
        $targetName = $targetUser ? $targetUser['name'] : 'User';

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            if ($action === 'approve') {
                MemberModel::approve($requestId);
                $logAction = ActivityLogger::buildAction('member_approve', [
                    'actor' => $_SESSION['user_name'],
                    'user'  => $targetName,
                ]);
                ActivityLogger::log($workspaceId, (int)$_SESSION['user_id'], null, 'member_approve', null, null, $logAction);
                $msg = 'Permohonan disetujui';
            } else {
                MemberModel::reject($requestId);
                $logAction = ActivityLogger::buildAction('member_reject', [
                    'actor' => $_SESSION['user_name'],
                    'user'  => $targetName,
                ]);
                ActivityLogger::log($workspaceId, (int)$_SESSION['user_id'], null, 'member_reject', null, null, $logAction);
                $msg = 'Permohonan ditolak';
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Response::error('SERVER_ERROR', 'Gagal memproses permohonan', 500);
        }

        Response::success(null, $msg);
    }

    public function progressApi(): void
    {
        $this->requireAuth();

        $workspaceId = (int)$this->request->query('workspace_id', 0);
        if ($workspaceId === 0) {
            Response::error('VALIDATION_ERROR', 'workspace_id diperlukan', 422);
        }

        $this->requireWorkspaceMember($workspaceId, 'Member');

        $progress = ProgressCalculator::forWorkspace($workspaceId);

        Response::success(['progress' => $progress]);
    }

    public function pendingCountApi(): void
    {
        $this->requireAuth();

        $workspaceId = (int)$this->request->query('workspace_id', 0);
        if ($workspaceId === 0) {
            Response::error('VALIDATION_ERROR', 'workspace_id diperlukan', 422);
        }

        $this->requireWorkspaceMember($workspaceId, 'Admin');

        $pending = \App\Models\MemberModel::listPendingForWorkspace($workspaceId);

        Response::success(['count' => count($pending)]);
    }
}
