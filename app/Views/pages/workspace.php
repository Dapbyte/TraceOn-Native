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
    <div style="display:flex;justify-content:flex-end;margin-bottom:var(--space-6);">
        <button type="button" class="btn btn-primary" id="btn-new-card">
            <span class="iconify" data-icon="ph:plus-bold" style="width:16px;height:16px"></span>
            Tambah Card
        </button>
    </div>
    <?php endif; ?>

    <!-- Card grid rendered in PHASE-3 -->
    <div class="card-grid" id="card-grid">
        <div class="empty-state" style="grid-column:1/-1;padding:var(--space-8) 0;">
            <span class="iconify" data-icon="ph:squares-four-bold" style="width:48px;height:48px;color:var(--color-border)"></span>
            <p class="empty-state-text">Belum ada card di workspace ini</p>
            <?php if ($isAdmin): ?>
            <button type="button" class="btn btn-primary btn-sm" id="empty-new-card">
                <span class="iconify" data-icon="ph:plus-bold" style="width:14px;height:14px"></span>
                Tambah Card
            </button>
            <?php endif; ?>
        </div>
    </div>
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
</script>
