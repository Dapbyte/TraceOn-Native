/**
 * TraceOn — Card Module
 * Card grid interactions, progress bar, context menu (desktop dropdown / mobile bottom sheet).
 */
import { apiPost }   from './api.js';
import { showToast } from './toast.js';

let _workspaceId = null;
let _isAdmin     = false;

export function initCardGrid(workspaceId, isAdmin) {
    _workspaceId = workspaceId;
    _isAdmin     = isAdmin;

    document.querySelectorAll('.card[data-card-id]').forEach(card => {
        card.addEventListener('click', (e) => {
            if (e.target.closest('button, a, input, select, textarea, .card-dropdown')) return;
            openCardDetail(parseInt(card.dataset.cardId, 10));
        });
    });

    // Ellipsis menu wiring
    document.querySelectorAll('.card-ellipsis-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const isMobile = window.innerWidth < 640;
            if (isMobile) {
                openBottomSheet(btn);
            } else {
                toggleDropdown(btn);
            }
        });
    });

    // Close dropdowns on outside click
    document.addEventListener('click', () => {
        document.querySelectorAll('.card-dropdown').forEach(d => d.style.display = 'none');
    });

    // Close bottom sheet on backdrop click / Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeBottomSheet();
    });
    document.getElementById('card-sheet-backdrop')?.addEventListener('click', closeBottomSheet);
}

export function updateProgressBar(cardId, progress) {
    document.querySelectorAll(`.progress-bar-fill[data-card-id="${cardId}"]`).forEach(fill => {
        fill.style.width = progress + '%';
        fill.classList.toggle('progress-complete', progress === 100);
    });
}

// ─── Dropdown (desktop) ────────────────────────────────────────────────────────
function toggleDropdown(btn) {
    const dropdown = btn.closest('.card-wrapper')?.querySelector('.card-dropdown');
    if (!dropdown) return;

    // Close all others
    document.querySelectorAll('.card-dropdown').forEach(d => {
        if (d !== dropdown) d.style.display = 'none';
    });

    const isVisible = dropdown.style.display === 'block';
    dropdown.style.display = isVisible ? 'none' : 'block';
}

// ─── Bottom sheet (mobile) ────────────────────────────────────────────────────
function openBottomSheet(btn) {
    const cardId   = btn.dataset.cardId;
    const cardTitle = btn.dataset.cardTitle;
    const isAdmin  = btn.dataset.isAdmin === 'true';

    const sheet    = document.getElementById('card-bottom-sheet');
    const backdrop = document.getElementById('card-sheet-backdrop');
    const title    = document.getElementById('sheet-card-title');

    if (!sheet) return;
    if (title) title.textContent = cardTitle;

    // Populate action buttons
    const actions = document.getElementById('sheet-actions');
    if (actions) {
        actions.innerHTML = '';
        if (isAdmin) {
            actions.innerHTML = `
                <button type="button" class="sheet-action" data-sheet-action="edit" data-card-id="${cardId}">
                    <span class="iconify" data-icon="ph:pencil-bold" style="width:18px;height:18px"></span>
                    Edit Card
                </button>
                <button type="button" class="sheet-action" data-sheet-action="access" data-card-id="${cardId}">
                    <span class="iconify" data-icon="ph:users-bold" style="width:18px;height:18px"></span>
                    Kelola Akses
                </button>
                <button type="button" class="sheet-action sheet-action-danger" data-sheet-action="delete" data-card-id="${cardId}">
                    <span class="iconify" data-icon="ph:trash-bold" style="width:18px;height:18px"></span>
                    Hapus Card
                </button>
            `;
        }

        actions.querySelectorAll('[data-sheet-action]').forEach(btn => {
            btn.addEventListener('click', () => {
                closeBottomSheet();
                handleCardAction(btn.dataset.sheetAction, parseInt(btn.dataset.cardId), cardTitle);
            });
        });
    }

    sheet.classList.add('open');
    backdrop.classList.add('visible');
    document.body.style.overflow = 'hidden';
}

function closeBottomSheet() {
    document.getElementById('card-bottom-sheet')?.classList.remove('open');
    document.getElementById('card-sheet-backdrop')?.classList.remove('visible');
    document.body.style.overflow = '';
}

// ─── Card actions ──────────────────────────────────────────────────────────────
export function handleCardAction(action, cardId, cardTitle) {
    if (action === 'edit')   openEditModal(cardId, cardTitle);
    if (action === 'delete') openDeleteModal(cardId, cardTitle);
    if (action === 'access') openAccessModal(cardId);
    if (action === 'open')   openCardDetail(cardId);
}

function openEditModal(cardId, cardTitle) {
    const modal = document.getElementById('modal-edit-card');
    if (!modal) return;
    document.getElementById('edit-card-id').value         = cardId;
    document.getElementById('edit-card-title').value      = cardTitle;
    const deadlineEl = document.getElementById('edit-card-deadline');
    const card = document.querySelector(`[data-card-id="${cardId}"]`);
    if (deadlineEl && card) deadlineEl.value = card.dataset.deadline || '';
    modal.style.display = 'flex';
    document.getElementById('edit-card-title').focus();
}

function openDeleteModal(cardId, cardTitle) {
    const modal = document.getElementById('modal-delete-card');
    if (!modal) return;
    document.getElementById('delete-card-id').value = cardId;
    document.getElementById('delete-card-name').textContent = cardTitle;
    modal.style.display = 'flex';
}

function openAccessModal(cardId) {
    const modal = document.getElementById('modal-card-access');
    if (!modal) return;
    document.getElementById('access-card-id').value = cardId;
    modal.style.display = 'flex';
    loadCardAccessUsers(cardId);
}

function loadCardAccessUsers(cardId) {
    // Access users loaded via SSR in card partial — no extra fetch needed
}

function openCardDetail(cardId) {
    const panel = document.getElementById(`card-detail-${cardId}`);
    if (panel) {
        panel.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        panel.querySelector('[data-card-detail-close]')?.focus();
        history.pushState(null, '', `/workspace/${_workspaceId}/card/${cardId}`);
    }
}
