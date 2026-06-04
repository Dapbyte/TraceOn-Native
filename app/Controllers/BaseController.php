<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\CsrfManager;
use App\Models\MemberModel;

abstract class BaseController
{
    public function __construct(protected Request $request) {}

    protected function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            if ($this->request->isApi()) {
                Response::error('UNAUTHENTICATED', 'Anda belum login', 401);
            }
            Response::redirect('/login');
        }

        // Idle timeout check
        if (!Session::checkIdle()) {
            if ($this->request->isApi()) {
                Response::error('SESSION_EXPIRED', 'Sesi telah berakhir. Silakan login kembali.', 401);
            }
            Response::redirect('/login?reason=expired');
        }

        // Set Cache-Control on authenticated page responses
        if (!$this->request->isApi()) {
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
        }
    }

    protected function requireCsrf(): void
    {
        $token = $this->request->input('csrf_token', '');
        if (!CsrfManager::validate((string)$token)) {
            Response::error('FORBIDDEN', 'Token keamanan tidak valid', 403);
        }
    }

    protected function requireWorkspaceMember(int $workspaceId, string $minRole = 'Member'): array
    {
        // Membership ALWAYS fetched live from DB — never from session (INV-08, RULE-05)
        // Placeholder until STEP-19 replaces with real MemberModel lookup
        $membership = $this->getMembershipLive($_SESSION['user_id'], $workspaceId);

        if (!$membership || $membership['status'] !== 'Approved') {
            Response::error('FORBIDDEN', 'Akses ditolak', 403);
        }

        $roleOrder = ['Member' => 1, 'Admin' => 2, 'Owner' => 3];
        $userLevel = $roleOrder[$membership['role']] ?? 0;
        $minLevel  = $roleOrder[$minRole] ?? 1;

        if ($userLevel < $minLevel) {
            Response::error('FORBIDDEN', 'Peran Anda tidak mencukupi', 403);
        }

        return $membership;
    }

    // Live DB fetch — never from session (INV-08, RULE-05)
    private function getMembershipLive(int $userId, int $workspaceId): ?array
    {
        return MemberModel::getMembership($userId, $workspaceId);
    }

    protected function json(array $payload, int $status = 200): never
    {
        Response::json($payload, $status);
    }

    /**
     * Render a view inside a layout.
     * $data['layout'] overrides default layout ('layouts.main' or 'layouts.auth').
     * View file captures its output into $content, then layout renders it.
     */
    protected function render(string $view, array $data = []): void
    {
        $viewsDir = __DIR__ . '/../Views/';
        $viewPath = $viewsDir . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            Response::error('SERVER_ERROR', 'View tidak ditemukan: ' . $view, 500);
        }

        // Determine layout
        $layoutKey  = $data['layout'] ?? null;
        unset($data['layout']);

        // Capture view output into $content
        extract($data, EXTR_SKIP);
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        if ($layoutKey) {
            $layoutPath = $viewsDir . str_replace('.', '/', $layoutKey) . '.php';
            if (!file_exists($layoutPath)) {
                Response::error('SERVER_ERROR', 'Layout tidak ditemukan: ' . $layoutKey, 500);
            }
            require $layoutPath;
        } else {
            echo $content;
        }
    }
}
