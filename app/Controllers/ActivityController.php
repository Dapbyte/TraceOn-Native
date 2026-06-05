<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\ActivityModel;
use App\Helpers\ActivityLogger;
use App\Core\Database;

class ActivityController extends BaseController
{
    public function fetch(): void
    {
        $this->requireAuth();
        
        $workspaceId = (int)$this->request->query('workspace_id', 0);
        if ($workspaceId === 0) {
            Response::error('VALIDATION_ERROR', 'workspace_id diperlukan', 422);
        }
        
        $this->requireWorkspaceMember($workspaceId, 'Member');
        
        $offset = max(0, (int)$this->request->query('offset', 0));
        $limit = max(1, min(100, (int)$this->request->query('limit', 50)));
        $search = trim((string)$this->request->query('search', ''));
        
        $filters = [];
        if ($search !== '') {
            $filters['search'] = $search;
        }
        
        $types = $this->request->query('filter_type');
        if (!empty($types)) {
            $filters['type'] = is_string($types) ? explode(',', $types) : (array)$types;
        }
        
        $dateFrom = $this->request->query('date_from');
        if (!empty($dateFrom)) {
            $filters['date_from'] = (string)$dateFrom;
        }
        
        $dateTo = $this->request->query('date_to');
        if (!empty($dateTo)) {
            $filters['date_to'] = (string)$dateTo;
        }
        
        $userIdFilter = (int)$this->request->query('user_id', 0);
        if ($userIdFilter > 0) {
            $filters['user_id'] = $userIdFilter;
        }

        if (empty($dateFrom) && empty($dateTo)) {
             $filters['default_last_7_days'] = true;
        }

        $activities = ActivityModel::listFiltered($workspaceId, $filters, $limit, $offset);
        $totalCount = ActivityModel::count($workspaceId, $search, $filters);
        
        $hasMore = ($offset + count($activities)) < $totalCount;
        
        Response::json([
            'success' => true,
            'data' => $activities,
            'meta' => [
                'offset' => $offset,
                'limit' => $limit,
                'has_more' => $hasMore,
                'total' => $totalCount
            ]
        ]);
    }

    public function clear(): void
    {
        $this->requireAuth();
        $this->requireCsrf();
        
        $workspaceId = (int)$this->request->input('workspace_id', 0);
        if ($workspaceId === 0) {
            Response::error('VALIDATION_ERROR', 'workspace_id diperlukan', 422);
        }
        
        // Owner ONLY
        $this->requireWorkspaceMember($workspaceId, 'Owner');
        
        $db = Database::getInstance();
        $db->beginTransaction();
        
        try {
            ActivityModel::deleteAll($workspaceId);
            
            $actionText = ActivityLogger::buildAction('log_clear', [
                'actor' => $_SESSION['user_name']
            ]);
            
            ActivityLogger::log($workspaceId, (int)$_SESSION['user_id'], null, 'log_clear', null, null, $actionText);
            
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Response::error('SERVER_ERROR', 'Gagal menghapus log aktivitas', 500);
        }
        
        Response::success(null, 'Log aktivitas berhasil dihapus');
    }
}