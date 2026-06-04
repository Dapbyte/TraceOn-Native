<?php
// Sidebar SSR partial — fully populated in PHASE-2 STEP-25
// Stub: renders minimal shell only
$userName   = htmlspecialchars($_SESSION['user_name']   ?? '', ENT_QUOTES, 'UTF-8');
$userAvatar = htmlspecialchars($_SESSION['user_avatar'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<aside class="sidebar" id="sidebar" role="navigation" aria-label="Navigasi utama">
    <div class="sidebar-logo">
        <span class="iconify sidebar-label" data-icon="ph:circles-four-bold" style="width:28px;height:28px;flex-shrink:0"></span>
        <span class="sidebar-label">TraceOn</span>
    </div>

    <nav class="sidebar-nav">
        <!-- Workspace buttons — wired in PHASE-2 -->
        <button class="btn btn-primary btn-sm w-full" id="btn-new-workspace" style="justify-content:flex-start;gap:8px;">
            <span class="iconify" data-icon="ph:plus-bold" style="width:16px;height:16px;flex-shrink:0"></span>
            <span class="sidebar-label">Workspace Baru</span>
        </button>
        <button class="btn btn-outline btn-sm w-full" id="btn-join-workspace" style="justify-content:flex-start;gap:8px;margin-top:8px;color:#FFFFFF;border-color:rgba(255,255,255,0.3);">
            <span class="iconify" data-icon="ph:sign-in-bold" style="width:16px;height:16px;flex-shrink:0"></span>
            <span class="sidebar-label">Join Workspace</span>
        </button>

        <!-- Workspace lists populated in PHASE-2 -->
        <div id="sidebar-workspace-lists" style="margin-top:16px;">
            <!-- Dibagikan + Workspace accordions rendered here -->
        </div>
    </nav>

    <div class="sidebar-footer">
        <?php if ($userAvatar): ?>
            <img src="<?= $userAvatar ?>" alt="Avatar" class="sidebar-avatar">
        <?php else: ?>
            <div class="sidebar-avatar" style="display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:14px;">
                <?= mb_strtoupper(mb_substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
            </div>
        <?php endif; ?>
        <span class="sidebar-username sidebar-label"><?= $userName ?></span>
        <a href="/profile" style="margin-left:auto;color:rgba(255,255,255,0.7);" title="Profil">
            <span class="iconify" data-icon="ph:gear-bold" style="width:20px;height:20px"></span>
        </a>
    </div>
</aside>
