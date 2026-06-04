<?php
// Workspace page stub — fully implemented in PHASE-2/3/4/5
// $workspace, $membership, $csrf passed from WorkspaceController
$wsName = htmlspecialchars($workspace['name'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<div class="main-header">
    <button class="hamburger-btn" id="hamburger-btn" aria-label="Buka menu">
        <span class="iconify" data-icon="ph:list-bold" style="width:24px;height:24px"></span>
    </button>
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="/dashboard">Dashboard</a>
        <span class="breadcrumb-sep">/</span>
        <span class="breadcrumb-current"><?= $wsName ?></span>
    </nav>
</div>
<div class="page-content">
    <h1 style="margin-bottom:var(--space-6);"><?= $wsName ?></h1>
    <!-- Tabs + card grid rendered in PHASE-2/3 -->
</div>
