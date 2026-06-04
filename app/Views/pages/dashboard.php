<?php
/**
 * Dashboard page — workspace list, create/join modals.
 * $ownedWorkspaces, $joinedWorkspaces, $csrf passed from WorkspaceController.
 */
$allWorkspaces = array_merge($ownedWorkspaces ?? [], $joinedWorkspaces ?? []);
$hasAny        = !empty($allWorkspaces);
?>

<div class="main-header">
    <button class="hamburger-btn" id="hamburger-btn" aria-label="Buka menu">
        <span class="iconify" data-icon="ph:list-bold" style="width:24px;height:24px"></span>
    </button>
    <h1 style="font-size:var(--text-h2);font-weight:600;flex:1;">Dashboard</h1>
    <button type="button" class="btn btn-primary btn-sm" id="header-new-ws">
        <span class="iconify" data-icon="ph:plus-bold" style="width:16px;height:16px"></span>
        <span>Workspace Baru</span>
    </button>
</div>

<div class="page-content">

<?php if (!$hasAny): ?>
    <!-- Empty state -->
    <div class="empty-state" style="min-height:60vh;">
        <span class="iconify" data-icon="ph:folders-bold" style="width:64px;height:64px;color:var(--color-border)"></span>
        <p class="empty-state-text" style="font-size:var(--text-h3);">Buat workspace pertamamu untuk mulai</p>
        <p class="empty-state-text">Atau bergabung ke workspace tim dengan kode undangan.</p>
        <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center;margin-top:8px;">
            <button type="button" class="btn btn-primary" id="empty-new-ws">
                <span class="iconify" data-icon="ph:plus-bold" style="width:16px;height:16px"></span>
                Buat Workspace
            </button>
            <button type="button" class="btn btn-outline" id="empty-join-ws">
                <span class="iconify" data-icon="ph:sign-in-bold" style="width:16px;height:16px"></span>
                Atau bergabung ke workspace tim
            </button>
        </div>
    </div>

<?php else: ?>

    <?php if (!empty($ownedWorkspaces)): ?>
    <section style="margin-bottom:var(--space-8);">
        <h2 style="font-size:var(--text-h3);margin-bottom:var(--space-4);color:var(--text-muted);">Workspace Saya</h2>
        <div class="card-grid">
            <?php foreach ($ownedWorkspaces as $ws): ?>
                <?php $wsId = (int)$ws['id']; $wsName = htmlspecialchars($ws['name'], ENT_QUOTES, 'UTF-8'); ?>
                <a href="/workspace/<?= $wsId ?>" class="card" style="text-decoration:none;display:block;cursor:pointer;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--space-3);">
                        <h3 class="card-title"><?= $wsName ?></h3>
                        <span class="badge badge-info">Owner</span>
                    </div>
                    <?php if ($ws['deadline']): ?>
                    <p style="font-size:var(--text-sm);color:var(--text-muted);margin-bottom:var(--space-2);">
                        <span class="iconify" data-icon="ph:calendar-blank-bold" style="width:12px;height:12px"></span>
                        <?= htmlspecialchars($ws['deadline'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php endif; ?>
                    <p style="font-size:var(--text-sm);color:var(--text-muted);">
                        <?= (int)$ws['member_count'] ?> anggota
                    </p>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($joinedWorkspaces)): ?>
    <section>
        <h2 style="font-size:var(--text-h3);margin-bottom:var(--space-4);color:var(--text-muted);">Dibagikan ke Saya</h2>
        <div class="card-grid">
            <?php foreach ($joinedWorkspaces as $ws): ?>
                <?php $wsId = (int)$ws['id']; $wsName = htmlspecialchars($ws['name'], ENT_QUOTES, 'UTF-8'); ?>
                <a href="/workspace/<?= $wsId ?>" class="card" style="text-decoration:none;display:block;cursor:pointer;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--space-3);">
                        <h3 class="card-title"><?= $wsName ?></h3>
                        <span class="badge badge-neutral">Member</span>
                    </div>
                    <?php if ($ws['deadline']): ?>
                    <p style="font-size:var(--text-sm);color:var(--text-muted);margin-bottom:var(--space-2);">
                        <span class="iconify" data-icon="ph:calendar-blank-bold" style="width:12px;height:12px"></span>
                        <?= htmlspecialchars($ws['deadline'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php endif; ?>
                    <p style="font-size:var(--text-sm);color:var(--text-muted);">
                        <?= (int)$ws['member_count'] ?> anggota
                    </p>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

<?php endif; ?>
</div>

<!-- Modal: New Workspace -->
<div class="modal-backdrop" id="modal-new-workspace" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="new-ws-title">
    <div class="modal">
        <h2 class="modal-title" id="new-ws-title">Buat Workspace Baru</h2>
        <form id="form-new-workspace" novalidate>
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label" for="ws-name">Nama Workspace</label>
                <input type="text" id="ws-name" class="form-control" placeholder="Contoh: Proyek Website 2025" maxlength="100" required>
                <span class="form-error" id="error-ws-name" style="display:none;"></span>
            </div>
            <div class="form-group" style="margin-bottom:24px;">
                <label class="form-label" for="ws-deadline">Deadline <span style="color:var(--text-muted);font-weight:400;">(opsional)</span></label>
                <input type="date" id="ws-deadline" class="form-control">
            </div>
            <div id="error-ws-general" class="form-error" style="display:none;margin-bottom:12px;"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" id="btn-ws-cancel">Batal</button>
                <button type="submit" class="btn btn-primary" id="btn-ws-submit">Buat Workspace</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Join Workspace -->
<div class="modal-backdrop" id="modal-join-workspace" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="join-ws-title">
    <div class="modal">
        <h2 class="modal-title" id="join-ws-title">Bergabung ke Workspace</h2>
        <form id="form-join-workspace" novalidate>
            <div class="form-group" style="margin-bottom:24px;">
                <label class="form-label" for="invite-code">Kode Undangan</label>
                <input type="text" id="invite-code" class="form-control"
                       placeholder="8 karakter kode undangan"
                       maxlength="10" autocomplete="off" spellcheck="false"
                       style="text-transform:uppercase;letter-spacing:2px;font-size:16px;">
                <span class="form-error" id="error-invite" style="display:none;"></span>
            </div>
            <div id="error-join-general" class="form-error" style="display:none;margin-bottom:12px;"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" id="btn-join-cancel">Batal</button>
                <button type="submit" class="btn btn-primary" id="btn-join-submit">Kirim Permohonan</button>
            </div>
        </form>
    </div>
</div>

<script type="module">
import { apiPost } from '/js/modules/api.js';
import { showToast } from '/js/modules/toast.js';

// ─── Modal helpers ──────────────────────────────────────────────────────────
function openModal(id) {
    const m = document.getElementById(id);
    if (m) { m.style.display = 'flex'; trapFocus(m); }
}
function closeModal(id) {
    const m = document.getElementById(id);
    if (m) m.style.display = 'none';
}

function trapFocus(modal) {
    const focusable = modal.querySelectorAll('input,button,select,textarea,[tabindex]:not([tabindex="-1"])');
    if (!focusable.length) return;
    focusable[0].focus();
    modal.addEventListener('keydown', function handler(e) {
        if (e.key !== 'Tab') return;
        const first = focusable[0], last = focusable[focusable.length - 1];
        if (e.shiftKey ? document.activeElement === first : document.activeElement === last) {
            e.preventDefault();
            (e.shiftKey ? last : first).focus();
        }
    });
}

// Close on backdrop click or Escape
document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
    backdrop.addEventListener('click', (e) => { if (e.target === backdrop) backdrop.style.display = 'none'; });
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-backdrop').forEach(m => m.style.display = 'none');
    }
});

// ─── New Workspace ──────────────────────────────────────────────────────────
['btn-new-workspace','header-new-ws','empty-new-ws'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', () => openModal('modal-new-workspace'));
});
document.getElementById('btn-ws-cancel')?.addEventListener('click', () => closeModal('modal-new-workspace'));

document.getElementById('form-new-workspace')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn      = document.getElementById('btn-ws-submit');
    const errName  = document.getElementById('error-ws-name');
    const errGen   = document.getElementById('error-ws-general');
    errName.style.display = errGen.style.display = 'none';
    btn.disabled = true; btn.textContent = 'Membuat...';

    try {
        const res = await apiPost('/api/workspace/create', {
            name:     document.getElementById('ws-name').value,
            deadline: document.getElementById('ws-deadline').value,
        });
        window.location.href = '/workspace/' + res.data.id;
    } catch (err) {
        btn.disabled = false; btn.textContent = 'Buat Workspace';
        if (err.code === 'VALIDATION_ERROR') {
            errName.textContent = err.message; errName.style.display = 'flex';
        } else {
            errGen.textContent = err.message; errGen.style.display = 'flex';
        }
    }
});

// ─── Join Workspace ─────────────────────────────────────────────────────────
['btn-join-workspace','empty-join-ws'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', () => openModal('modal-join-workspace'));
});
document.getElementById('btn-join-cancel')?.addEventListener('click', () => closeModal('modal-join-workspace'));

// Auto-uppercase invite code
document.getElementById('invite-code')?.addEventListener('input', (e) => {
    const pos = e.target.selectionStart;
    e.target.value = e.target.value.toUpperCase();
    e.target.setSelectionRange(pos, pos);
});

document.getElementById('form-join-workspace')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn    = document.getElementById('btn-join-submit');
    const errInv = document.getElementById('error-invite');
    const errGen = document.getElementById('error-join-general');
    errInv.style.display = errGen.style.display = 'none';
    btn.disabled = true; btn.textContent = 'Mengirim...';

    try {
        await apiPost('/api/workspace/join-request', {
            invite_code: document.getElementById('invite-code').value,
        });
        closeModal('modal-join-workspace');
        showToast('Permohonan bergabung terkirim. Tunggu persetujuan owner.', 'success');
    } catch (err) {
        btn.disabled = false; btn.textContent = 'Kirim Permohonan';
        if (err.status === 404 || err.status === 409) {
            errInv.textContent = err.message; errInv.style.display = 'flex';
        } else {
            errGen.textContent = err.message; errGen.style.display = 'flex';
        }
    }
});
</script>
