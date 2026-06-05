<?php

declare(strict_types=1);

// ─── Web Pages ─────────────────────────────────────────────────────────────
$router->get('/',                  'AuthController@redirectDashboard');
$router->get('/login',             'AuthController@showLogin');
$router->get('/register',          'AuthController@showRegister');
$router->get('/dashboard',         'WorkspaceController@dashboard');
$router->get('/workspace/{id}',    'WorkspaceController@show');
$router->get('/profile',           'ProfileController@show');

// ─── Healthcheck ───────────────────────────────────────────────────────────
$router->get('/healthz',           'HealthController@check');

// ─── Auth API ──────────────────────────────────────────────────────────────
$router->post('/api/auth/login',    'AuthController@login');
$router->post('/api/auth/register', 'AuthController@register');
$router->post('/api/auth/logout',   'AuthController@logout');

// ─── Workspace API ─────────────────────────────────────────────────────────
$router->post('/api/workspace/create',           'WorkspaceController@create');
$router->get('/api/workspace/share',             'WorkspaceController@shareCode');
$router->post('/api/workspace/regenerate-code',  'WorkspaceController@regenerateCode');
$router->post('/api/workspace/delete',           'WorkspaceController@delete');
$router->post('/api/workspace/rename',           'WorkspaceController@rename');
$router->post('/api/workspace/update-deadline',  'WorkspaceController@updateDeadline');
$router->post('/api/workspace/join-request',     'WorkspaceController@joinRequest');
$router->post('/api/workspace/approve-request',  'WorkspaceController@approveRequest');
$router->get('/api/workspace/progress',          'WorkspaceController@progressApi');
$router->get('/api/workspace/pending-count',     'WorkspaceController@pendingCountApi');

// ─── Member API ────────────────────────────────────────────────────────────
$router->post('/api/member/role-update', 'MemberController@updateRole');
$router->post('/api/member/kick',        'MemberController@kick');

// ─── Card API ──────────────────────────────────────────────────────────────
$router->post('/api/card/create',        'CardController@create');
$router->post('/api/card/update',        'CardController@update');
$router->post('/api/card/delete',        'CardController@delete');
$router->post('/api/card/access/grant',  'CardController@grantAccess');
$router->post('/api/card/access/revoke', 'CardController@revokeAccess');

// ─── Todo API ──────────────────────────────────────────────────────────────
$router->post('/api/todo/create', 'TodoController@create');
$router->post('/api/todo/update', 'TodoController@update');
$router->post('/api/todo/delete', 'TodoController@delete');

// ─── Activity API ──────────────────────────────────────────────────────────
$router->get('/api/activity/fetch',   'ActivityController@fetch');
$router->post('/api/activity/clear',  'ActivityController@clear');

// ─── Profile API ───────────────────────────────────────────────────────────
$router->post('/api/profile/update', 'ProfileController@update');
