<?php
// Dashboard page stub — fully implemented in PHASE-2
// $csrf, $ownedWorkspaces, $joinedWorkspaces, $user passed from WorkspaceController
?>
<div class="main-header">
    <button class="hamburger-btn" id="hamburger-btn" aria-label="Buka menu">
        <span class="iconify" data-icon="ph:list-bold" style="width:24px;height:24px"></span>
    </button>
    <h1 style="font-size:var(--text-h2);font-weight:600;">Dashboard</h1>
</div>
<div class="page-content">
    <p class="text-muted">Selamat datang, <?= htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>!</p>
    <!-- Workspace list rendered in PHASE-2 -->
</div>
