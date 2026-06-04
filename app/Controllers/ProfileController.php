<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\CsrfManager;
use App\Models\UserModel;
use App\Helpers\FileUploadHelper;

class ProfileController extends BaseController
{
    public function show(): void
    {
        $this->requireAuth();

        $user = UserModel::findById((int)$_SESSION['user_id']);
        if (!$user) {
            Response::redirect('/login');
        }

        $this->render('pages.profile', [
            'layout'    => 'layouts.main',
            'pageTitle' => 'Profil — TraceOn',
            'csrf'      => CsrfManager::generate(),
            'user'      => $user,
        ]);
    }

    public function update(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $userId = (int)$_SESSION['user_id'];
        $user   = UserModel::findById($userId);
        if (!$user) {
            Response::error('NOT_FOUND', 'User tidak ditemukan', 404);
        }

        $name      = trim((string)$this->request->input('name', ''));
        $avatarFile = $this->request->file('avatar');

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Nama tidak boleh kosong';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'Nama maksimal 100 karakter';
        }

        if (!empty($errors)) {
            Response::json(['success' => false, 'error' => 'VALIDATION_ERROR', 'errors' => $errors], 422);
        }

        $newAvatarPath = null;

        // Handle avatar upload if provided
        if ($avatarFile && $avatarFile['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $newAvatarPath = FileUploadHelper::saveAvatar($avatarFile);
                // Delete old avatar after successful save
                FileUploadHelper::deleteAvatar($user['avatar_path']);
            } catch (\RuntimeException $e) {
                Response::json([
                    'success' => false,
                    'error'   => 'UPLOAD_ERROR',
                    'message' => $e->getMessage(),
                ], $e->getCode() ?: 422);
            }
        }

        // Update DB
        UserModel::updateName($userId, $name);
        if ($newAvatarPath !== null) {
            UserModel::updateAvatar($userId, $newAvatarPath);
        }

        // Sync session
        $_SESSION['user_name']   = $name;
        if ($newAvatarPath !== null) {
            $_SESSION['user_avatar'] = $newAvatarPath;
        }

        $responseData = ['message' => 'Profil diperbarui'];
        if ($newAvatarPath !== null) {
            $responseData['avatar_path'] = $newAvatarPath;
        }

        Response::success($responseData, 'Profil diperbarui');
    }
}
