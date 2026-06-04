<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\Database;
use App\Models\MemberModel;
use App\Models\CardModel;
use App\Models\UserModel;
use App\Helpers\ActivityLogger;

class MemberController extends BaseController
{
    public function updateRole(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $workspaceId = (int)$this->request->input('workspace_id', 0);
        $targetUserId = (int)$this->request->input('user_id', 0);
        $newRole     = (string)$this->request->input('role', '');

        if (!in_array($newRole, ['Admin', 'Member'], true)) {
            Response::error('VALIDATION_ERROR', 'Role harus Admin atau Member', 422);
        }

        $actorMembership = $this->requireWorkspaceMember($workspaceId, 'Admin');
        $actorUserId     = (int)$_SESSION['user_id'];

        // Cannot change own role (RULE-10)
        if ($targetUserId === $actorUserId) {
            Response::error('FORBIDDEN', 'Anda tidak dapat mengubah role sendiri', 403);
        }

        $targetMembership = MemberModel::getMembership($targetUserId, $workspaceId);
        if (!$targetMembership || $targetMembership['status'] !== 'Approved') {
            Response::error('NOT_FOUND', 'Anggota tidak ditemukan', 404);
        }

        // Cannot touch Owner role (RULE-10, INV-03)
        if ($targetMembership['role'] === 'Owner') {
            Response::error('FORBIDDEN', 'Role Owner tidak dapat diubah', 403);
        }

        // Cannot promote to Owner
        if ($newRole === 'Owner') {
            Response::error('FORBIDDEN', 'Tidak dapat mempromosikan ke Owner', 403);
        }

        $oldRole    = $targetMembership['role'];
        $targetName = $targetMembership['user_name'] ?? 'User';

        MemberModel::updateRole((int)$targetMembership['id'], $newRole);

        $action = ActivityLogger::buildAction('role_change', [
            'actor' => $_SESSION['user_name'],
            'user'  => $targetName,
            'old'   => $oldRole,
            'new'   => $newRole,
        ]);
        ActivityLogger::log($workspaceId, $actorUserId, null, 'role_change', $oldRole, $newRole, $action);

        Response::success(null, 'Role anggota diperbarui');
    }

    public function kick(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $workspaceId  = (int)$this->request->input('workspace_id', 0);
        $targetUserId = (int)$this->request->input('user_id', 0);

        $this->requireWorkspaceMember($workspaceId, 'Admin');
        $actorUserId = (int)$_SESSION['user_id'];

        $targetMembership = MemberModel::getMembership($targetUserId, $workspaceId);
        if (!$targetMembership || $targetMembership['status'] !== 'Approved') {
            Response::error('NOT_FOUND', 'Anggota tidak ditemukan', 404);
        }

        // Cannot kick Owner (RULE-10, INV-03)
        if ($targetMembership['role'] === 'Owner') {
            Response::error('FORBIDDEN', 'Owner tidak dapat dikeluarkan', 403);
        }

        $targetName = $targetMembership['user_name'] ?? 'User';

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            // Delete membership
            MemberModel::delete($workspaceId, $targetUserId);

            // Delete card_access for this user in this workspace
            $stmt = $db->prepare(
                'DELETE ca FROM card_access ca
                 JOIN cards c ON c.id = ca.card_id
                 WHERE ca.user_id = ? AND c.workspace_id = ?'
            );
            $stmt->execute([$targetUserId, $workspaceId]);

            $action = ActivityLogger::buildAction('member_kick', [
                'actor' => $_SESSION['user_name'],
                'user'  => $targetName,
            ]);
            ActivityLogger::log($workspaceId, $actorUserId, null, 'member_kick', null, null, $action);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Response::error('SERVER_ERROR', 'Gagal mengeluarkan anggota', 500);
        }

        Response::success(null, 'Anggota berhasil dikeluarkan');
    }
}
