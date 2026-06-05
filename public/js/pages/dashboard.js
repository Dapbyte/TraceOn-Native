import { apiPost } from '/js/modules/api.js';
import { showToast } from '/js/modules/toast.js';

function openModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.style.display = 'flex';
    trapFocus(m);
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

document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
    backdrop.addEventListener('click', (e) => {
        if (e.target === backdrop) backdrop.style.display = 'none';
    });
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-backdrop').forEach(m => m.style.display = 'none');
    }
});

['btn-new-workspace', 'header-new-ws', 'empty-new-ws'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', () => openModal('modal-new-workspace'));
});
document.getElementById('btn-ws-cancel')?.addEventListener('click', () => closeModal('modal-new-workspace'));

document.getElementById('form-new-workspace')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn     = document.getElementById('btn-ws-submit');
    const errName = document.getElementById('error-ws-name');
    const errGen  = document.getElementById('error-ws-general');
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

['btn-join-workspace', 'empty-join-ws'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', () => openModal('modal-join-workspace'));
});
document.getElementById('btn-join-cancel')?.addEventListener('click', () => closeModal('modal-join-workspace'));

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
