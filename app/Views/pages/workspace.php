<?php
/**
 * Workspace page — tabs: Dashboard (card grid), Anggota, Log Aktivitas.
 * $workspace, $membership, $members, $pendingMembers, $csrf passed from WorkspaceController.
 * Card grid and Activity Log fully wired in PHASE-3 and PHASE-5.
 */
$wsId       = (int)$workspace['id'];
$wsName     = htmlspecialchars($workspace['name'], ENT_QUOTES, 'UTF-8');
$role       = $membership['role'];
$isOwner    = $role === 'Owner';
$isAdmin    = in_array($role, ['Owner','Admin'], true);
$myUserId   = (int)$_SESSION['user_id'];
$deadline   = $workspace['deadline'] ?? null;

// Deadline warning: < 3 days
$deadlineBadgeClass = '';
if ($deadline) {
    $diff = (new DateTime($deadline))->diff(new DateTime())->days;
    $isPast = (new DateTime($deadline)) < new DateTime();
    if ($isPast || $diff < 3) $deadlineBadgeClass = 'badge-error';
    else $deadlineBadgeClass = 'badge-warning';
}
?>

<!-- Header -->
<div class="main-header">
    <button class="hamburger-btn" id="hamburger-btn" aria-label="Buka menu">
        <span class="iconify" data-icon="ph:list-bold" style="width:24px;height:24px"></span>
    </button>
    <nav class="breadcrumb" aria-label="Breadcrumb" style="flex:1;overflow:hidden;">
        <a href="/dashboard">Dashboard</a>
        <span class="breadcrumb-sep">/</span>
        <span class="breadcrumb-current"><?= $wsName ?></span>
    </nav>
    <?php if ($deadline): ?>
    <span class="badge <?= $deadlineBadgeClass ?>" style="flex-shrink:0;">
        <span class="iconify" data-icon="ph:calendar-bold" style="width:12px;height:12px;margin-right:4px;"></span>
        <?= htmlspecialchars($deadline, ENT_QUOTES, 'UTF-8') ?>
    </span>
    <?php endif; ?>
</div>

<!-- Tabs -->
<div class="tab-nav" id="workspace-tabs">
    <button class="tab-btn active" data-tab="dashboard">Dashboard</button>
    <button class="tab-btn" data-tab="members">
        Anggota
        <span style="font-size:var(--text-sm);margin-left:4px;color:var(--text-muted);">(<?= count($members) ?>)</span>
    </button>
    <button class="tab-btn" data-tab="activity">Log Aktivitas</button>
    <?php if ($isOwner): ?>
    <button class="tab-btn" data-tab="settings">Pengaturan</button>
    <?php endif; ?>
</div>

<!-- ─── Tab: Dashboard (card grid) ────────────────────────────────────────── -->
<div id="tab-dashboard" class="tab-content page-content">

    <?php if ($isAdmin): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-6);flex-wrap:wrap;gap:12px;">
        <div>
            <span style="font-size:var(--text-sm);color:var(--text-muted);">
                Progress workspace:
                <strong style="color:var(--color-secondary);"><?= $wsProgress ?? 0 ?>%</strong>
            </span>
        </div>
        <button type="button" class="btn btn-primary" id="btn-new-card">
            <span class="iconify" data-icon="ph:plus-bold" style="width:16px;height:16px"></span>
            Tambah Card
        </button>
    </div>
    <?php endif; ?>

    <?php if (empty($cards)): ?>
    <div class="empty-state" style="min-height:40vh;">
        <span class="iconify" data-icon="ph:squares-four-bold" style="width:48px;height:48px;color:var(--color-border)"></span>
        <p class="empty-state-text">Belum ada card di workspace ini</p>
        <?php if ($isAdmin): ?>
        <button type="button" class="btn btn-primary btn-sm" id="empty-new-card">
            <span class="iconify" data-icon="ph:plus-bold" style="width:14px;height:14px"></span>
            Tambah Card
        </button>
        <?php endif; ?>
    </div>
    <?php else: ?>

    <div class="card-grid" id="card-grid">
    <?php foreach ($cards as $c):
        $cId         = (int)$c['id'];
        $cTitle      = htmlspecialchars($c['title'],    ENT_QUOTES, 'UTF-8');
        $cDeadline   = $c['deadline'] ?? null;
        $cProgress   = (int)($c['progress'] ?? 0);
        $cTotal      = (int)($c['total_todos'] ?? 0);
        $cDone       = (int)($c['done_todos'] ?? 0);
        $cHasAccess  = (bool)($c['user_has_access'] ?? false);
        $cAccessUsers = $c['access_users'] ?? [];

        // Deadline badge
        $dBadge = '';
        if ($cDeadline) {
            $dDiff   = (new DateTime($cDeadline))->diff(new DateTime());
            $dIsPast = (new DateTime($cDeadline)) < new DateTime();
            $dDays   = $dDiff->days;
            $dBadge  = ($dIsPast || $dDays < 3) ? 'badge-error' : 'badge-warning';
        }
    ?>
    <div class="card-wrapper" style="position:relative;">
        <div class="card"
             data-card-id="<?= $cId ?>"
             data-deadline="<?= htmlspecialchars($cDeadline ?? '', ENT_QUOTES, 'UTF-8') ?>"
             style="cursor:pointer;">

            <!-- Card header -->
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--space-3);gap:8px;">
                <h3 class="card-title" style="flex:1;"><?= $cTitle ?></h3>

                <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                    <?php if (!$cHasAccess): ?>
                    <span class="badge badge-readonly">Hanya Baca</span>
                    <?php endif; ?>

                    <?php if ($isAdmin): ?>
                    <button
                        type="button"
                        class="card-ellipsis-btn"
                        data-card-id="<?= $cId ?>"
                        data-card-title="<?= $cTitle ?>"
                        data-is-admin="true"
                        aria-label="Opsi card"
                        style="width:28px;height:28px;border-radius:var(--radius-sm);background:transparent;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--text-muted);opacity:0;transition:opacity 0.2s;"
                    >
                        <span class="iconify" data-icon="ph:dots-three-vertical-bold" style="width:16px;height:16px"></span>
                    </button>

                    <!-- Desktop dropdown -->
                    <div class="card-dropdown"
                         style="display:none;position:absolute;top:44px;right:8px;background:var(--color-background);border:1px solid var(--color-border);border-radius:var(--radius-md);box-shadow:var(--shadow-lg);min-width:160px;z-index:50;overflow:hidden;">
                        <button type="button"
                                onclick="handleCardAction('edit', <?= $cId ?>, '<?= addslashes($c['title']) ?>')"
                                style="width:100%;padding:10px 16px;text-align:left;background:none;border:none;cursor:pointer;font-size:var(--text-sm);display:flex;align-items:center;gap:8px;">
                            <span class="iconify" data-icon="ph:pencil-bold" style="width:14px;height:14px"></span>Edit Card
                        </button>
                        <button type="button"
                                onclick="handleCardAction('access', <?= $cId ?>, '<?= addslashes($c['title']) ?>')"
                                style="width:100%;padding:10px 16px;text-align:left;background:none;border:none;cursor:pointer;font-size:var(--text-sm);display:flex;align-items:center;gap:8px;">
                            <span class="iconify" data-icon="ph:users-bold" style="width:14px;height:14px"></span>Kelola Akses
                        </button>
                        <hr style="margin:0;border-color:var(--color-border);">
                        <button type="button"
                                onclick="handleCardAction('delete', <?= $cId ?>, '<?= addslashes($c['title']) ?>')"
                                style="width:100%;padding:10px 16px;text-align:left;background:none;border:none;cursor:pointer;font-size:var(--text-sm);color:var(--color-error);display:flex;align-items:center;gap:8px;">
                            <span class="iconify" data-icon="ph:trash-bold" style="width:14px;height:14px"></span>Hapus Card
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Progress bar -->
            <div class="progress-track" style="margin-bottom:var(--space-2);">
                <div class="progress-bar-fill <?= $cProgress >= 100 ? 'progress-complete' : '' ?>"
                     data-card-id="<?= $cId ?>"
                     style="width:<?= $cProgress ?>%;">
                </div>
            </div>
            <p class="progress-ratio text-sm text-muted" data-card-id="<?= $cId ?>" style="margin-bottom:var(--space-3);">
                <?= $cDone ?>/<?= $cTotal ?> selesai
            </p>

            <!-- Deadline badge -->
            <?php if ($cDeadline): ?>
            <div style="margin-bottom:var(--space-3);">
                <span class="badge <?= $dBadge ?>" style="font-size:11px;">
                    <span class="iconify" data-icon="ph:calendar-bold" style="width:11px;height:11px;margin-right:3px;"></span>
                    <?= htmlspecialchars($cDeadline, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <?php endif; ?>

            <!-- Access user avatars (max 3 + overflow) -->
            <?php if (!empty($cAccessUsers)): ?>
            <div style="display:flex;align-items:center;gap:-4px;">
                <?php foreach (array_slice($cAccessUsers, 0, 3) as $au): ?>
                <div title="<?= htmlspecialchars($au['name'], ENT_QUOTES, 'UTF-8') ?>"
                     style="width:24px;height:24px;border-radius:var(--radius-full);border:2px solid var(--color-background);overflow:hidden;background:var(--color-secondary);display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:700;margin-right:-4px;">
                    <?php if ($au['avatar_path']): ?>
                        <img src="<?= htmlspecialchars($au['avatar_path'], ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <?= mb_strtoupper(mb_substr($au['name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php if (count($cAccessUsers) > 3): ?>
                <span class="badge badge-neutral" style="font-size:10px;padding:2px 6px;margin-left:4px;">
                    +<?= count($cAccessUsers) - 3 ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div><!-- .card -->
    </div><!-- .card-wrapper -->

    <div class="modal-backdrop card-detail-backdrop"
         id="card-detail-<?= $cId ?>"
         data-todo-card-id="<?= $cId ?>"
         data-can-edit="<?= $cHasAccess ? 'true' : 'false' ?>"
         data-workspace-url="/workspace/<?= $wsId ?>"
         style="display:none;"
         role="dialog"
         aria-modal="true"
         aria-labelledby="card-detail-title-<?= $cId ?>">
        <div class="modal modal-fullscreen card-detail-modal">
            <div class="card-detail-header">
                <div class="card-detail-heading">
                    <h2 class="card-detail-title" id="card-detail-title-<?= $cId ?>"><?= $cTitle ?></h2>
                    <?php if (!$cHasAccess): ?>
                    <span class="badge badge-readonly">Hanya Baca</span>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-icon btn-outline card-detail-close" data-card-detail-close aria-label="Tutup detail card">
                    <span class="iconify" data-icon="ph:x-bold" style="width:18px;height:18px"></span>
                </button>
            </div>

            <div class="card-detail-progress">
                <div class="progress-track">
                    <div class="progress-bar-fill <?= $cProgress >= 100 ? 'progress-complete' : '' ?>"
                         data-card-id="<?= $cId ?>"
                         style="width:<?= $cProgress ?>%;"></div>
                </div>
                <p class="progress-ratio text-sm text-muted" data-card-id="<?= $cId ?>">
                    <?= $cDone ?>/<?= $cTotal ?> selesai
                </p>
            </div>

            <div class="todo-filter-group" role="group" aria-label="Filter todo">
                <button type="button" class="todo-filter active" data-todo-filter="all">Semua</button>
                <button type="button" class="todo-filter" data-todo-filter="done">Selesai</button>
                <button type="button" class="todo-filter" data-todo-filter="in_progress">Dalam Proses</button>
                <button type="button" class="todo-filter" data-todo-filter="pending">Belum</button>
            </div>

            <div class="todo-list" data-todo-list>
                <?php if (empty($c['todos'])): ?>
                <p class="todo-empty" data-todo-empty>Belum ada todo di card ini</p>
                <?php else: ?>
                <?php foreach ($c['todos'] as $todo):
                    $todoId = (int)$todo['id'];
                    $todoTitle = htmlspecialchars($todo['title'], ENT_QUOTES, 'UTF-8');
                    $todoStatus = $todo['status'];
                    $todoDone = $todoStatus === 'done';
                    $todoInProgress = $todoStatus === 'in_progress';
                    $todoLabel = $todoStatus === 'done' ? 'Selesai' : ($todoInProgress ? 'Sedang' : 'Belum');
                ?>
                <div class="todo-row <?= $todoDone ? 'is-done' : '' ?>" data-todo-id="<?= $todoId ?>" data-status="<?= htmlspecialchars($todoStatus, ENT_QUOTES, 'UTF-8') ?>">
                    <?php if ($cHasAccess): ?>
                    <button type="button" class="todo-checkbox <?= $todoDone ? 'is-checked' : '' ?>" data-todo-toggle aria-label="Ubah status todo">
                        <span class="iconify todo-checkmark" data-icon="ph:check-bold"></span>
                    </button>
                    <?php else: ?>
                    <span class="todo-checkbox <?= $todoDone ? 'is-checked' : '' ?>" aria-hidden="true">
                        <span class="iconify todo-checkmark" data-icon="ph:check-bold"></span>
                    </span>
                    <?php endif; ?>

                    <div class="todo-content">
                        <p class="todo-title"><?= $todoTitle ?></p>
                        <?php if ($todoInProgress): ?>
                        <span class="badge badge-warning todo-status-badge">Sedang</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($cHasAccess): ?>
                    <select class="form-control todo-status-select" data-todo-status aria-label="Status todo">
                        <option value="pending" <?= $todoStatus === 'pending' ? 'selected' : '' ?>>Belum</option>
                        <option value="in_progress" <?= $todoStatus === 'in_progress' ? 'selected' : '' ?>>Sedang</option>
                        <option value="done" <?= $todoStatus === 'done' ? 'selected' : '' ?>>Selesai</option>
                    </select>
                    <div class="todo-actions">
                        <button type="button" class="btn btn-icon btn-outline todo-delete-btn" data-todo-delete aria-label="Hapus todo">
                            <span class="iconify" data-icon="ph:trash-bold" style="width:16px;height:16px"></span>
                        </button>
                    </div>
                    <?php else: ?>
                    <span class="badge <?= $todoDone ? 'badge-success' : ($todoInProgress ? 'badge-warning' : 'badge-neutral') ?>">
                        <?= $todoLabel ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($cHasAccess): ?>
            <form class="todo-create-form" data-todo-form>
                <input type="text" class="form-control" data-todo-title maxlength="255" placeholder="Tambah todo baru" required>
                <button type="submit" class="btn btn-primary" data-todo-submit>Simpan</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div><!-- .card-grid -->
    <?php endif; ?>
</div>

<!-- ─── Tab: Anggota ──────────────────────────────────────────────────────── -->
<div id="tab-members" class="tab-content page-content" style="display:none;">

    <?php if (!empty($pendingMembers)): ?>
    <div class="card" style="margin-bottom:var(--space-6);border-color:rgba(180,83,9,0.3);">
        <h3 style="font-size:var(--text-h3);margin-bottom:var(--space-4);">
            Menunggu Persetujuan
            <span class="badge badge-warning" style="margin-left:8px;"><?= count($pendingMembers) ?></span>
        </h3>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach ($pendingMembers as $pm): ?>
            <?php $pmId = (int)$pm['id']; $pmName = htmlspecialchars($pm['user_name'], ENT_QUOTES, 'UTF-8'); ?>
            <div style="display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid var(--color-border);" data-request-id="<?= $pmId ?>">
                <div style="width:32px;height:32px;border-radius:var(--radius-full);background:var(--color-secondary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;flex-shrink:0;">
                    <?= mb_strtoupper(mb_substr($pm['user_name'], 0, 1)) ?>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:500;"><?= $pmName ?></div>
                    <div style="font-size:var(--text-sm);color:var(--text-muted);"><?= htmlspecialchars($pm['user_email'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <?php if ($isAdmin): ?>
                <button type="button" class="btn btn-sm" data-action="approve" data-request-id="<?= $pmId ?>"
                        style="background:var(--color-success);color:#fff;border:none;">Setujui</button>
                <button type="button" class="btn btn-sm btn-outline" data-action="reject" data-request-id="<?= $pmId ?>">Tolak</button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <?php if ($isAdmin): ?>
    <p class="text-muted" style="margin-bottom:var(--space-6);font-size:var(--text-sm);">Tidak ada permohonan yang menunggu.</p>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Members table -->
    <div class="card">
        <h3 style="font-size:var(--text-h3);margin-bottom:var(--space-4);">Anggota (<?= count($members) ?>)</h3>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="font-size:var(--text-sm);color:var(--text-muted);border-bottom:1px solid var(--color-border);">
                        <th style="text-align:left;padding:8px 12px;font-weight:500;">Anggota</th>
                        <th style="text-align:left;padding:8px 12px;font-weight:500;">Role</th>
                        <th style="text-align:left;padding:8px 12px;font-weight:500;">Status</th>
                        <th style="text-align:right;padding:8px 12px;font-weight:500;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($members as $m): ?>
                <?php
                $mId       = (int)$m['id'];
                $mUserId   = (int)$m['user_id'];
                $mName     = htmlspecialchars($m['user_name'], ENT_QUOTES, 'UTF-8');
                $mRole     = $m['role'];
                $mStatus   = $m['status'];
                $isMe      = ($mUserId === $myUserId);
                $isMOwner  = ($mRole === 'Owner');
                ?>
                <tr style="border-bottom:1px solid var(--color-border);" data-member-id="<?= $mId ?>" data-user-id="<?= $mUserId ?>">
                    <td style="padding:12px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <?php if ($m['avatar_path']): ?>
                            <img src="<?= htmlspecialchars($m['avatar_path'], ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:32px;height:32px;border-radius:var(--radius-full);object-fit:cover;flex-shrink:0;">
                            <?php else: ?>
                            <div style="width:32px;height:32px;border-radius:var(--radius-full);background:var(--color-secondary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;flex-shrink:0;">
                                <?= mb_strtoupper(mb_substr($m['user_name'], 0, 1)) ?>
                            </div>
                            <?php endif; ?>
                            <div>
                                <div style="font-weight:500;"><?= $mName ?></div>
                                <div style="font-size:var(--text-sm);color:var(--text-muted);"><?= htmlspecialchars($m['user_email'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:12px;">
                        <?php if ($isAdmin && !$isMe && !$isMOwner && $mStatus === 'Approved'): ?>
                        <select class="form-control" style="width:auto;padding:4px 8px;height:32px;"
                                data-action="role-change" data-member-id="<?= $mId ?>" data-user-id="<?= $mUserId ?>">
                            <option value="Member" <?= $mRole === 'Member' ? 'selected' : '' ?>>Member</option>
                            <option value="Admin"  <?= $mRole === 'Admin'  ? 'selected' : '' ?>>Admin</option>
                        </select>
                        <?php else: ?>
                        <span class="badge <?= $isMOwner ? 'badge-info' : ($mRole==='Admin' ? 'badge-warning' : 'badge-neutral') ?>">
                            <?= htmlspecialchars($mRole, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px;">
                        <span class="badge <?= $mStatus==='Approved' ? 'badge-success' : 'badge-neutral' ?>">
                            <?= htmlspecialchars($mStatus, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td style="padding:12px;text-align:right;">
                        <?php if ($isMe): ?>
                        <span style="font-size:var(--text-sm);color:var(--text-muted);">Kamu</span>
                        <?php elseif ($isMOwner): ?>
                        <span style="font-size:var(--text-sm);color:var(--text-muted);">—</span>
                        <?php elseif ($isAdmin && $mStatus === 'Approved'): ?>
                        <button type="button" class="btn btn-danger btn-sm"
                                data-action="kick" data-member-id="<?= $mId ?>" data-user-id="<?= $mUserId ?>" data-name="<?= $mName ?>">
                            Keluarkan
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Invite code display (Admin+) -->
    <div class="card" style="margin-top:var(--space-6);">
        <h3 style="font-size:var(--text-h3);margin-bottom:var(--space-4);">Kode Undangan</h3>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <input type="text" id="invite-code-display" class="form-control"
                   style="max-width:240px;font-family:monospace;letter-spacing:3px;font-size:18px;font-weight:600;"
                   value="••••••••" readonly>
            <button type="button" class="btn btn-outline btn-sm" id="btn-show-code">Tampilkan</button>
            <button type="button" class="btn btn-outline btn-sm" id="btn-copy-code" style="display:none;">
                <span class="iconify" data-icon="ph:copy-bold" style="width:14px;height:14px"></span>
                Salin
            </button>
            <?php if ($isOwner): ?>
            <button type="button" class="btn btn-outline btn-sm" id="btn-regen-code" style="color:var(--color-warning);border-color:var(--color-warning);">
                Regenerate Code
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ─── Tab: Log Aktivitas ────────────────────────────────────────────────── -->
<div id="tab-activity" class="tab-content page-content" style="display:none;">
    <!-- Fully implemented in PHASE-5 STEP-34 -->
    <div class="empty-state">
        <span class="iconify" data-icon="ph:list-bullets-bold" style="width:48px;height:48px;color:var(--color-border)"></span>
        <p class="empty-state-text">Log aktivitas akan tersedia setelah PHASE-5</p>
    </div>
</div>

<!-- ─── Tab: Pengaturan (Owner only) ─────────────────────────────────────── -->
<?php if ($isOwner): ?>
<div id="tab-settings" class="tab-content page-content" style="display:none;">

    <!-- Rename -->
    <div class="card" style="margin-bottom:var(--space-6);">
        <h3 style="font-size:var(--text-h3);margin-bottom:var(--space-4);">Nama Workspace</h3>
        <form id="form-rename" novalidate style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="flex:1;min-width:200px;">
                <label class="form-label" for="rename-input">Nama baru</label>
                <input type="text" id="rename-input" class="form-control"
                       value="<?= $wsName ?>" maxlength="100">
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="height:40px;">Simpan</button>
        </form>
    </div>

    <!-- Deadline -->
    <div class="card" style="margin-bottom:var(--space-6);">
        <h3 style="font-size:var(--text-h3);margin-bottom:var(--space-4);">Deadline Workspace</h3>
        <form id="form-deadline" novalidate style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="flex:1;min-width:200px;">
                <label class="form-label" for="deadline-input">Tanggal deadline</label>
                <input type="date" id="deadline-input" class="form-control"
                       value="<?= htmlspecialchars($deadline ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="height:40px;">Simpan</button>
        </form>
    </div>

    <!-- Danger zone -->
    <div class="danger-zone">
        <h3 class="danger-zone-title">
            <span class="iconify" data-icon="ph:warning-bold" style="width:16px;height:16px;margin-right:4px;"></span>
            Zona Berbahaya
        </h3>
        <p style="font-size:var(--text-sm);color:var(--text-muted);margin-bottom:var(--space-4);">
            Menghapus workspace bersifat permanen dan tidak dapat dibatalkan.
            Semua card, todo, dan log aktivitas akan dihapus.
        </p>
        <button type="button" class="btn btn-danger btn-sm" id="btn-delete-workspace">
            <span class="iconify" data-icon="ph:trash-bold" style="width:14px;height:14px"></span>
            Hapus Workspace Permanen
        </button>
    </div>
</div>
<?php endif; ?>

<!-- Modal: Confirm Delete Workspace -->
<div class="modal-backdrop" id="modal-delete-ws" style="display:none;" role="dialog" aria-modal="true">
    <div class="modal">
        <h2 class="modal-title" style="color:var(--color-error);">Hapus Workspace</h2>
        <p style="color:var(--text-muted);margin-bottom:16px;">
            Tindakan ini <strong>tidak dapat dibatalkan</strong>. Ketik nama workspace untuk konfirmasi:
            <br><strong><?= $wsName ?></strong>
        </p>
        <div class="form-group" style="margin-bottom:24px;">
            <input type="text" id="delete-confirm-input" class="form-control"
                   placeholder="Ketik nama workspace..." autocomplete="off">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" id="btn-delete-cancel">Batal</button>
            <button type="button" class="btn btn-danger" id="btn-delete-confirm" disabled>Hapus Permanen</button>
        </div>
    </div>
</div>

<!-- Modal: Confirm Kick -->
<div class="modal-backdrop" id="modal-kick" style="display:none;" role="dialog" aria-modal="true">
    <div class="modal">
        <h2 class="modal-title">Keluarkan Anggota</h2>
        <p id="kick-modal-body" style="color:var(--text-muted);margin-bottom:24px;"></p>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" id="btn-kick-cancel">Batal</button>
            <button type="button" class="btn btn-danger" id="btn-kick-confirm">Keluarkan</button>
        </div>
    </div>
</div>

<!-- Modal: Confirm Regenerate Code -->
<div class="modal-backdrop" id="modal-regen" style="display:none;" role="dialog" aria-modal="true">
    <div class="modal">
        <h2 class="modal-title">Regenerate Kode Undangan?</h2>
        <p style="color:var(--text-muted);margin-bottom:24px;">
            Kode lama akan langsung tidak berlaku dan semua permohonan pending akan ditolak.
        </p>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" id="btn-regen-cancel">Batal</button>
            <button type="button" class="btn btn-danger" id="btn-regen-confirm">Ya, Regenerate</button>
        </div>
    </div>
</div>

<!-- ─── Card Modals ───────────────────────────────────────────────────────── -->

<!-- Modal: New Card -->
<div class="modal-backdrop" id="modal-new-card" style="display:none;" role="dialog" aria-modal="true">
    <div class="modal">
        <h2 class="modal-title">Tambah Card Baru</h2>
        <form id="form-new-card" novalidate>
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label" for="new-card-title">Judul Card</label>
                <input type="text" id="new-card-title" class="form-control" placeholder="Contoh: Desain Antarmuka" maxlength="100" required>
            </div>
            <div class="form-group" style="margin-bottom:24px;">
                <label class="form-label" for="new-card-deadline">Deadline <span style="color:var(--text-muted);font-weight:400;">(opsional)</span></label>
                <input type="date" id="new-card-deadline" class="form-control">
            </div>
            <div class="form-error" id="error-card-general" style="display:none;margin-bottom:12px;"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-new-card').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary" id="btn-card-submit">Buat Card</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Card -->
<div class="modal-backdrop" id="modal-edit-card" style="display:none;" role="dialog" aria-modal="true">
    <div class="modal">
        <h2 class="modal-title">Edit Card</h2>
        <form id="form-edit-card" novalidate>
            <input type="hidden" id="edit-card-id">
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label" for="edit-card-title">Judul Card</label>
                <input type="text" id="edit-card-title" class="form-control" maxlength="100" required>
            </div>
            <div class="form-group" style="margin-bottom:24px;">
                <label class="form-label" for="edit-card-deadline">Deadline</label>
                <input type="date" id="edit-card-deadline" class="form-control">
            </div>
            <div class="form-error" id="error-edit-card" style="display:none;margin-bottom:12px;"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-edit-card').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary" id="btn-edit-card-submit">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Delete Card -->
<div class="modal-backdrop" id="modal-delete-card" style="display:none;" role="dialog" aria-modal="true">
    <div class="modal">
        <h2 class="modal-title" style="color:var(--color-error);">Hapus Card</h2>
        <p style="color:var(--text-muted);margin-bottom:24px;">
            Hapus card <strong id="delete-card-name"></strong>? Semua todo di dalamnya akan ikut terhapus. Tindakan ini tidak dapat dibatalkan.
        </p>
        <input type="hidden" id="delete-card-id">
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" id="btn-delete-card-cancel">Batal</button>
            <button type="button" class="btn btn-danger" id="btn-delete-card-confirm">Hapus</button>
        </div>
    </div>
</div>

<!-- Modal: Card Access -->
<div class="modal-backdrop" id="modal-card-access" style="display:none;" role="dialog" aria-modal="true">
    <div class="modal">
        <h2 class="modal-title">Kelola Akses Card</h2>
        <input type="hidden" id="access-card-id">
        <div id="access-member-list" style="margin-bottom:16px;max-height:300px;overflow-y:auto;">
            <?php foreach ($members as $m):
                if ($m['status'] !== 'Approved' || in_array($m['role'], ['Owner','Admin'])) continue;
                $mUserId = (int)$m['user_id'];
                $mName   = htmlspecialchars($m['user_name'], ENT_QUOTES, 'UTF-8');
            ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--color-border);" data-access-user-id="<?= $mUserId ?>">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:32px;height:32px;border-radius:var(--radius-full);background:var(--color-secondary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;">
                        <?= mb_strtoupper(mb_substr($m['user_name'], 0, 1)) ?>
                    </div>
                    <span style="font-size:var(--text-sm);"><?= $mName ?></span>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="button" class="btn btn-sm btn-primary" onclick="grantAccess(this, <?= $mUserId ?>)">Beri Akses</button>
                    <button type="button" class="btn btn-sm btn-outline" style="color:var(--color-error);border-color:var(--color-error);" onclick="revokeAccess(this, <?= $mUserId ?>)">Cabut</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="document.getElementById('modal-card-access').style.display='none'">Selesai</button>
        </div>
    </div>
</div>

<!-- Mobile bottom sheet context menu -->
<div id="card-sheet-backdrop" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:290;"></div>
<div id="card-bottom-sheet" style="position:fixed;bottom:0;left:0;right:0;background:var(--color-background);border-radius:var(--radius-lg) var(--radius-lg) 0 0;padding:var(--space-4);z-index:300;transform:translateY(100%);transition:transform 0.3s cubic-bezier(0.16,1,0.3,1);max-height:80vh;overflow-y:auto;">
    <div style="width:40px;height:4px;background:var(--color-border);border-radius:var(--radius-full);margin:0 auto var(--space-4);"></div>
    <p id="sheet-card-title" style="font-weight:600;margin-bottom:var(--space-4);font-family:var(--font-heading);"></p>
    <div id="sheet-actions" style="display:flex;flex-direction:column;gap:4px;"></div>
</div>

<style>
#card-sheet-backdrop.visible  { display:block; }
#card-bottom-sheet.open       { transform:translateY(0); }
.sheet-action {
    display:flex;align-items:center;gap:12px;padding:14px 12px;
    border-radius:var(--radius-md);background:none;border:none;cursor:pointer;
    font-size:var(--text-body);font-weight:500;color:var(--text-primary);
    transition:background-color 0.2s;
}
.sheet-action:hover { background:var(--color-surface); }
.sheet-action-danger { color:var(--color-error); }
</style>

<script type="module">
import { apiPost, apiGet } from '/js/modules/api.js';
import { showToast }       from '/js/modules/toast.js';

const WS_ID   = <?= $wsId ?>;
const WS_NAME = <?= json_encode($workspace['name']) ?>;

// ─── Tabs ────────────────────────────────────────────────────────────────────
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).style.display = 'block';
    });
});

// ─── Modal helpers ────────────────────────────────────────────────────────────
function openModal(id) {
    const m = document.getElementById(id);
    if (m) { m.style.display = 'flex'; const f = m.querySelector('input,button'); f?.focus(); }
}
function closeModal(id) {
    const m = document.getElementById(id);
    if (m) m.style.display = 'none';
}
document.querySelectorAll('.modal-backdrop').forEach(b => {
    b.addEventListener('click', e => { if (e.target === b) b.style.display = 'none'; });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-backdrop').forEach(m => m.style.display = 'none');
});

// ─── Invite code show/copy/regen ──────────────────────────────────────────────
let codeRevealed = false;
document.getElementById('btn-show-code')?.addEventListener('click', async () => {
    if (codeRevealed) return;
    try {
        const res = await apiGet('/api/workspace/share', { workspace_id: WS_ID });
        const inp = document.getElementById('invite-code-display');
        inp.value = res.data.invite_code;
        codeRevealed = true;
        document.getElementById('btn-copy-code').style.display = 'inline-flex';
    } catch (err) {
        showToast(err.message, 'error');
    }
});

document.getElementById('btn-copy-code')?.addEventListener('click', async () => {
    const val = document.getElementById('invite-code-display').value;
    try { await navigator.clipboard.writeText(val); showToast('Kode disalin!', 'success'); }
    catch { showToast('Gagal menyalin', 'error'); }
});

document.getElementById('btn-regen-code')?.addEventListener('click', () => openModal('modal-regen'));
document.getElementById('btn-regen-cancel')?.addEventListener('click', () => closeModal('modal-regen'));
document.getElementById('btn-regen-confirm')?.addEventListener('click', async () => {
    closeModal('modal-regen');
    try {
        const res = await apiPost('/api/workspace/regenerate-code', { workspace_id: WS_ID, _method: 'PATCH' });
        document.getElementById('invite-code-display').value = res.data.new_invite_code;
        codeRevealed = true;
        document.getElementById('btn-copy-code').style.display = 'inline-flex';
        showToast('Kode undangan diperbarui', 'success');
    } catch (err) { showToast(err.message, 'error'); }
});

// ─── Approve / Reject pending ─────────────────────────────────────────────────
document.querySelectorAll('[data-action="approve"],[data-action="reject"]').forEach(btn => {
    btn.addEventListener('click', async () => {
        const requestId = btn.dataset.requestId;
        const action    = btn.dataset.action;
        btn.disabled = true;
        try {
            await apiPost('/api/workspace/approve-request', { request_id: requestId, action });
            const row = document.querySelector(`[data-request-id="${requestId}"]`);
            if (row) { row.style.opacity = '0'; setTimeout(() => row.remove(), 300); }
            showToast(action === 'approve' ? 'Permohonan disetujui' : 'Permohonan ditolak', 'success');
        } catch (err) { btn.disabled = false; showToast(err.message, 'error'); }
    });
});

// ─── Role change ──────────────────────────────────────────────────────────────
document.querySelectorAll('[data-action="role-change"]').forEach(sel => {
    let prevVal = sel.value;
    sel.addEventListener('change', async () => {
        const newRole = sel.value;
        const userId  = sel.dataset.userId;
        try {
            await apiPost('/api/member/role-update', {
                workspace_id: WS_ID,
                user_id:      parseInt(userId),
                role:         newRole,
            });
            prevVal = newRole;
            showToast('Role diperbarui', 'success');
        } catch (err) {
            sel.value = prevVal;
            showToast(err.message, 'error');
        }
    });
});

// ─── Kick ─────────────────────────────────────────────────────────────────────
let kickTarget = null;
document.querySelectorAll('[data-action="kick"]').forEach(btn => {
    btn.addEventListener('click', () => {
        kickTarget = { userId: btn.dataset.userId, name: btn.dataset.name };
        document.getElementById('kick-modal-body').textContent =
            `Apakah Anda yakin ingin mengeluarkan ${kickTarget.name} dari workspace ini?`;
        openModal('modal-kick');
    });
});
document.getElementById('btn-kick-cancel')?.addEventListener('click', () => closeModal('modal-kick'));
document.getElementById('btn-kick-confirm')?.addEventListener('click', async () => {
    if (!kickTarget) return;
    closeModal('modal-kick');
    try {
        await apiPost('/api/member/kick', {
            workspace_id: WS_ID,
            user_id:      parseInt(kickTarget.userId),
            _method:      'DELETE',
        });
        const row = document.querySelector(`tr[data-user-id="${kickTarget.userId}"]`);
        if (row) { row.style.opacity = '0'; setTimeout(() => row.remove(), 300); }
        showToast(kickTarget.name + ' dikeluarkan dari workspace', 'success');
        kickTarget = null;
    } catch (err) { showToast(err.message, 'error'); }
});

// ─── Rename ───────────────────────────────────────────────────────────────────
document.getElementById('form-rename')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const name = document.getElementById('rename-input').value.trim();
    try {
        await apiPost('/api/workspace/rename', { workspace_id: WS_ID, name, _method: 'PATCH' });
        showToast('Nama workspace diperbarui', 'success');
        document.title = name + ' — TraceOn';
        document.querySelector('.breadcrumb-current').textContent = name;
    } catch (err) { showToast(err.message, 'error'); }
});

// ─── Deadline ─────────────────────────────────────────────────────────────────
document.getElementById('form-deadline')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const deadline = document.getElementById('deadline-input').value;
    try {
        await apiPost('/api/workspace/update-deadline', { workspace_id: WS_ID, deadline, _method: 'PATCH' });
        showToast('Deadline diperbarui', 'success');
    } catch (err) { showToast(err.message, 'error'); }
});

// ─── Delete workspace ─────────────────────────────────────────────────────────
document.getElementById('btn-delete-workspace')?.addEventListener('click', () => {
    document.getElementById('delete-confirm-input').value = '';
    document.getElementById('btn-delete-confirm').disabled = true;
    openModal('modal-delete-ws');
});
document.getElementById('btn-delete-cancel')?.addEventListener('click', () => closeModal('modal-delete-ws'));

document.getElementById('delete-confirm-input')?.addEventListener('input', (e) => {
    document.getElementById('btn-delete-confirm').disabled = e.target.value !== WS_NAME;
});

document.getElementById('btn-delete-confirm')?.addEventListener('click', async () => {
    const nameConfirm = document.getElementById('delete-confirm-input').value;
    closeModal('modal-delete-ws');
    try {
        const res = await apiPost('/api/workspace/delete', {
            workspace_id: WS_ID,
            name_confirm: nameConfirm,
            _method:      'DELETE',
        });
        window.location.href = res.data?.redirect ?? '/dashboard';
    } catch (err) { showToast(err.message, 'error'); }
});

// ─── Card: ellipsis button hover show ────────────────────────────────────────
document.querySelectorAll('.card-wrapper').forEach(wrapper => {
    const btn = wrapper.querySelector('.card-ellipsis-btn');
    if (!btn) return;
    wrapper.addEventListener('mouseenter', () => btn.style.opacity = '1');
    wrapper.addEventListener('mouseleave', () => btn.style.opacity = '0');
});

// ─── Card: init card grid module ─────────────────────────────────────────────
import { initCardGrid, handleCardAction, updateProgressBar } from '/js/modules/card.js';
import { initTodoList } from '/js/modules/todo.js';
initCardGrid(WS_ID, <?= $isAdmin ? 'true' : 'false' ?>);
document.querySelectorAll('[data-todo-card-id]').forEach(panel => {
    initTodoList(parseInt(panel.dataset.todoCardId, 10));
});

// Expose handleCardAction globally (used in inline onclick in PHP-rendered HTML)
window.handleCardAction = handleCardAction;
window.updateProgressBar = updateProgressBar;

// ─── Card: Create ─────────────────────────────────────────────────────────────
['btn-new-card','empty-new-card'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', () => openModal('modal-new-card'));
});

document.getElementById('form-new-card')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn  = document.getElementById('btn-card-submit');
    const errG = document.getElementById('error-card-general');
    errG.style.display = 'none';
    btn.disabled = true; btn.textContent = 'Membuat...';

    try {
        await apiPost('/api/card/create', {
            workspace_id: WS_ID,
            title:        document.getElementById('new-card-title').value,
            deadline:     document.getElementById('new-card-deadline').value,
        });
        closeModal('modal-new-card');
        showToast('Card berhasil dibuat', 'success');
        setTimeout(() => location.reload(), 600);
    } catch (err) {
        btn.disabled = false; btn.textContent = 'Buat Card';
        errG.textContent = err.message; errG.style.display = 'flex';
    }
});

// ─── Card: Edit ───────────────────────────────────────────────────────────────
document.getElementById('form-edit-card')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const cardId = document.getElementById('edit-card-id').value;
    const btn    = document.getElementById('btn-edit-card-submit');
    btn.disabled = true; btn.textContent = 'Menyimpan...';
    try {
        await apiPost('/api/card/update', {
            card_id:      parseInt(cardId),
            workspace_id: WS_ID,
            title:        document.getElementById('edit-card-title').value,
            deadline:     document.getElementById('edit-card-deadline').value || null,
            _method:      'PATCH',
        });
        closeModal('modal-edit-card');
        showToast('Card diperbarui', 'success');
        setTimeout(() => location.reload(), 600);
    } catch (err) {
        btn.disabled = false; btn.textContent = 'Simpan';
        document.getElementById('error-edit-card').textContent = err.message;
        document.getElementById('error-edit-card').style.display = 'flex';
    }
});

// ─── Card: Delete ─────────────────────────────────────────────────────────────
document.getElementById('btn-delete-card-confirm')?.addEventListener('click', async () => {
    const cardId = document.getElementById('delete-card-id').value;
    closeModal('modal-delete-card');
    try {
        await apiPost('/api/card/delete', {
            card_id:      parseInt(cardId),
            workspace_id: WS_ID,
            _method:      'DELETE',
        });
        const wrapper = document.querySelector(`.card-wrapper:has([data-card-id="${cardId}"])`);
        if (wrapper) { wrapper.style.opacity = '0'; setTimeout(() => wrapper.remove(), 300); }
        showToast('Card dihapus', 'success');
    } catch (err) { showToast(err.message, 'error'); }
});
document.getElementById('btn-delete-card-cancel')?.addEventListener('click', () => closeModal('modal-delete-card'));

// ─── Card Access: grant/revoke ────────────────────────────────────────────────
window.grantAccess = async (btn, userId) => {
    const cardId = document.getElementById('access-card-id').value;
    btn.disabled = true;
    try {
        await apiPost('/api/card/access/grant', {
            card_id:      parseInt(cardId),
            workspace_id: WS_ID,
            user_id:      userId,
        });
        showToast('Akses diberikan', 'success');
    } catch (err) {
        btn.disabled = false;
        showToast(err.message, 'error');
    }
};

window.revokeAccess = async (btn, userId) => {
    const cardId = document.getElementById('access-card-id').value;
    btn.disabled = true;
    try {
        await apiPost('/api/card/access/revoke', {
            card_id:      parseInt(cardId),
            workspace_id: WS_ID,
            user_id:      userId,
            _method:      'DELETE',
        });
        showToast('Akses dicabut', 'success');
    } catch (err) {
        btn.disabled = false;
        showToast(err.message, 'error');
    }
};
</script>
