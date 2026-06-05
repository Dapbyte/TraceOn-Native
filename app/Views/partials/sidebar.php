<?php
/**
 * Sidebar SSR partial.
 * Requires: $ownedWorkspaces, $joinedWorkspaces (passed from WorkspaceController via layout data).
 * Falls back to empty arrays if not provided (e.g. profile page).
 */
$ownedWorkspaces  = $ownedWorkspaces  ?? [];
$joinedWorkspaces = $joinedWorkspaces ?? [];
$userName         = htmlspecialchars($_SESSION['user_name']   ?? '', ENT_QUOTES, 'UTF-8');
$userAvatar       = $_SESSION['user_avatar'] ?? '';
$currentPath      = $_SERVER['REQUEST_URI'] ?? '';
?>
<aside class="sidebar" id="sidebar" role="navigation" aria-label="Navigasi utama">

    <!-- Logo -->
    <div class="sidebar-logo">
        <span class="iconify" data-icon="ph:circles-four-bold"
              style="width:28px;height:28px;flex-shrink:0;color:#4BA3E3"></span>
        <span class="sidebar-label" style="font-family:var(--font-heading);font-weight:700;">TraceOn</span>
    </div>

    <nav class="sidebar-nav">

        <!-- New Workspace -->
        <button
            type="button"
            class="btn btn-primary btn-sm"
            id="btn-new-workspace"
            style="width:100%;justify-content:flex-start;gap:8px;margin-bottom:4px;"
        >
            <span class="iconify" data-icon="ph:plus-bold" style="width:16px;height:16px;flex-shrink:0"></span>
            <span class="sidebar-label">Workspace Baru</span>
        </button>

        <!-- Join Workspace -->
        <button
            type="button"
            id="btn-join-workspace"
            class="sidebar-join-btn"
            style="width:100%;display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:var(--radius-md);background:transparent;border:1.5px solid rgba(255,255,255,0.25);color:rgba(255,255,255,0.85);cursor:pointer;font-size:var(--text-sm);font-weight:500;margin-bottom:16px;transition:background-color 0.2s;"
        >
            <span class="iconify" data-icon="ph:sign-in-bold" style="width:16px;height:16px;flex-shrink:0"></span>
            <span class="sidebar-label">Join Workspace</span>
        </button>

        <!-- Accordion: Dibagikan (Joined) -->
        <?php if (!empty($joinedWorkspaces)): ?>
        <div class="sidebar-accordion" data-accordion="joined">
            <button
                type="button"
                class="sidebar-accordion-trigger"
                aria-expanded="true"
                data-target="accordion-joined"
            >
                <span class="sidebar-label">
                    Dibagikan
                    <span style="background:rgba(255,255,255,0.15);border-radius:var(--radius-sm);padding:1px 6px;font-size:11px;margin-left:4px;"><?= count($joinedWorkspaces) ?></span>
                </span>
                <span class="iconify sidebar-accordion-arrow" data-icon="ph:caret-down-bold"
                      style="width:14px;height:14px;flex-shrink:0;transition:transform 0.2s;"></span>
            </button>
            <div class="sidebar-accordion-content open" id="accordion-joined">
                <?php foreach ($joinedWorkspaces as $ws): ?>
                    <?php
                    $wsId      = (int)$ws['id'];
                    $wsName    = htmlspecialchars($ws['name'], ENT_QUOTES, 'UTF-8');
                    $isActive  = str_contains($currentPath, '/workspace/' . $wsId);
                    ?>
                    <a
                        href="/workspace/<?= $wsId ?>"
                        class="sidebar-workspace-item<?= $isActive ? ' active' : '' ?>"
                        title="<?= $wsName ?>"
                    >
                        <span class="sidebar-label" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= $wsName ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Accordion: Workspace (Owned) -->
        <div class="sidebar-accordion" data-accordion="owned" style="margin-top:<?= empty($joinedWorkspaces) ? '0' : '8px' ?>;">
            <button
                type="button"
                class="sidebar-accordion-trigger"
                aria-expanded="true"
                data-target="accordion-owned"
            >
                <span class="sidebar-label">
                    Workspace
                    <span style="background:rgba(255,255,255,0.15);border-radius:var(--radius-sm);padding:1px 6px;font-size:11px;margin-left:4px;"><?= count($ownedWorkspaces) ?></span>
                </span>
                <span class="iconify sidebar-accordion-arrow" data-icon="ph:caret-down-bold"
                      style="width:14px;height:14px;flex-shrink:0;transition:transform 0.2s;"></span>
            </button>
            <div class="sidebar-accordion-content open" id="accordion-owned">
                <?php if (empty($ownedWorkspaces)): ?>
                    <p style="padding:8px 12px;font-size:var(--text-sm);color:rgba(255,255,255,0.4);">Belum ada workspace</p>
                <?php else: ?>
                    <?php foreach ($ownedWorkspaces as $ws): ?>
                        <?php
                        $wsId     = (int)$ws['id'];
                        $wsName   = htmlspecialchars($ws['name'], ENT_QUOTES, 'UTF-8');
                        $isActive = str_contains($currentPath, '/workspace/' . $wsId);
                        ?>
                        <a
                            href="/workspace/<?= $wsId ?>"
                            class="sidebar-workspace-item<?= $isActive ? ' active' : '' ?>"
                            title="<?= $wsName ?>"
                        >
                            <span class="sidebar-label" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= $wsName ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </nav>

    <!-- Footer: avatar + name + profile link -->
    <div class="sidebar-footer">
        <?php if ($userAvatar): ?>
            <img
                src="<?= htmlspecialchars($userAvatar, ENT_QUOTES, 'UTF-8') ?>"
                alt="Avatar <?= $userName ?>"
                class="sidebar-avatar"
            >
        <?php else: ?>
            <div class="sidebar-avatar"
                 style="display:flex;align-items:center;justify-content:center;background:var(--color-secondary);color:#fff;font-weight:700;font-size:14px;flex-shrink:0;">
                <?= mb_strtoupper(mb_substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
            </div>
        <?php endif; ?>
        <span class="sidebar-username sidebar-label"><?= $userName ?></span>
        <a href="/profile"
           style="margin-left:auto;color:rgba(255,255,255,0.6);flex-shrink:0;"
           title="Profil saya">
            <span class="iconify" data-icon="ph:gear-bold" style="width:18px;height:18px"></span>
        </a>
    </div>

</aside>
