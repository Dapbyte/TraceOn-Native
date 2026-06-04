<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\CsrfManager;

class WorkspaceController extends BaseController
{
    public function dashboard(): void
    {
        $this->requireAuth();
        $this->render('pages.dashboard', [
            'layout'    => 'layouts.main',
            'pageTitle' => 'Dashboard — TraceOn',
            'csrf'      => CsrfManager::generate(),
        ]);
    }

    public function show(int $id): void
    {
        $this->requireAuth();
        // Full implementation in PHASE-2
        $this->render('pages.workspace', [
            'layout'    => 'layouts.main',
            'pageTitle' => 'Workspace — TraceOn',
            'csrf'      => CsrfManager::generate(),
            'workspace' => ['name' => 'Workspace #' . $id],
        ]);
    }

    public function __call(string $name, array $args): never
    {
        Response::error('NOT_IMPLEMENTED', 'Belum diimplementasikan', 501);
    }
}
