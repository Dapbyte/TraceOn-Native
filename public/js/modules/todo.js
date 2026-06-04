import { apiPost } from './api.js';
import { showToast } from './toast.js';
import { updateProgressBar } from './card.js';

const STATUS_LABELS = {
    pending: 'Belum',
    in_progress: 'Sedang',
    done: 'Selesai',
};

export function initTodoList(cardId) {
    const panel = document.querySelector(`[data-todo-card-id="${cardId}"]`);
    if (!panel || panel.dataset.todoInitialized === 'true') return;
    panel.dataset.todoInitialized = 'true';

    panel.querySelectorAll('[data-card-detail-close]').forEach(btn => {
        btn.addEventListener('click', () => closePanel(panel));
    });

    panel.addEventListener('click', (e) => {
        if (e.target !== panel) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        closePanel(panel);
    }, true);

    panel.addEventListener('click', (e) => {
        const toggle = e.target.closest('[data-todo-toggle]');
        if (toggle) {
            updateTodoStatus(toggle.closest('.todo-row'), toggle.closest('.todo-row')?.dataset.status === 'done' ? 'pending' : 'done');
            return;
        }

        const deleteBtn = e.target.closest('[data-todo-delete]');
        if (deleteBtn) {
            handleTodoDeleteClick(deleteBtn.closest('.todo-row'), parseInt(deleteBtn.closest('.todo-row')?.dataset.todoId, 10));
        }
    });

    panel.addEventListener('change', (e) => {
        const select = e.target.closest('[data-todo-status]');
        if (!select) return;
        updateTodoStatus(select.closest('.todo-row'), select.value, select);
    });

    panel.querySelectorAll('[data-todo-filter]').forEach(btn => {
        btn.addEventListener('click', () => {
            panel.querySelectorAll('[data-todo-filter]').forEach(item => item.classList.remove('active'));
            btn.classList.add('active');
            applyFilter(panel);
        });
    });

    panel.querySelector('[data-todo-form]')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        await createTodo(panel, cardId);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape' || panel.style.display === 'none') return;
        if (panel.querySelector('.is-delete-pending')) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        closePanel(panel);
    }, true);
}

export function handleTodoDeleteClick(todoEl, todoId) {
    if (!todoEl || !todoId || todoEl.classList.contains('is-delete-pending')) return;

    const actions = todoEl.querySelector('.todo-actions');
    const original = actions ? actions.innerHTML : '';
    const deleteBtn = todoEl.querySelector('[data-todo-delete]');
    const actionHost = actions || deleteBtn?.parentElement;
    if (!actionHost) return;

    todoEl.classList.add('is-delete-pending');
    actionHost.innerHTML = `
        <button type="button" class="btn btn-danger btn-sm" data-confirm-delete>Hapus</button>
        <button type="button" class="btn btn-outline btn-sm" data-cancel-delete>Batal</button>
    `;

    let finished = false;
    const timeoutId = window.setTimeout(cancel, 5000);

    function cleanup() {
        window.clearTimeout(timeoutId);
        document.removeEventListener('keydown', onEscape, true);
    }

    function cancel() {
        if (finished) return;
        cleanup();
        todoEl.classList.remove('is-delete-pending');
        if (actions) actions.innerHTML = original;
        else actionHost.innerHTML = original;
    }

    async function confirmDelete() {
        if (finished) return;
        finished = true;
        cleanup();
        actionHost.querySelectorAll('button').forEach(btn => btn.disabled = true);

        try {
            const cardId = parseInt(todoEl.closest('[data-todo-card-id]')?.dataset.todoCardId, 10);
            const res = await apiPost('/api/todo/delete', { todo_id: todoId, _method: 'DELETE' });
            updateProgressBar(cardId, res.data.progress_card);
            todoEl.classList.add('is-removing');
            todoEl.addEventListener('transitionend', () => {
                todoEl.remove();
                syncCardRatio(cardId);
                syncEmptyState(cardId);
            }, { once: true });
            showToast('Todo berhasil dihapus', 'success');
        } catch (err) {
            finished = false;
            todoEl.classList.remove('is-delete-pending');
            if (actions) actions.innerHTML = original;
            else actionHost.innerHTML = original;
            showToast(err.message, 'error');
        }
    }

    function onEscape(e) {
        if (e.key !== 'Escape') return;
        e.preventDefault();
        e.stopImmediatePropagation();
        cancel();
    }

    document.addEventListener('keydown', onEscape, true);
    actionHost.querySelector('[data-confirm-delete]')?.addEventListener('click', confirmDelete);
    actionHost.querySelector('[data-cancel-delete]')?.addEventListener('click', cancel);
}

async function createTodo(panel, cardId) {
    const input = panel.querySelector('[data-todo-title]');
    const submit = panel.querySelector('[data-todo-submit]');
    const title = input?.value.trim() ?? '';
    if (!title) return;

    submit.disabled = true;
    try {
        const res = await apiPost('/api/todo/create', { card_id: cardId, title });
        panel.querySelector('[data-todo-empty]')?.remove();
        panel.querySelector('[data-todo-list]')?.appendChild(createTodoRow(res.data.todo_id, title, 'pending'));
        input.value = '';
        updateProgressBar(cardId, res.data.progress_card);
        syncCardRatio(cardId);
        applyFilter(panel);
        showToast('Todo ditambahkan', 'success');
    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        submit.disabled = false;
    }
}

async function updateTodoStatus(row, status, select = null) {
    if (!row || !['pending', 'in_progress', 'done'].includes(status)) return;
    const previous = row.dataset.status;
    if (previous === status) return;

    if (select) select.disabled = true;
    const toggle = row.querySelector('[data-todo-toggle]');
    if (toggle) toggle.disabled = true;

    try {
        const res = await apiPost('/api/todo/update', {
            todo_id: parseInt(row.dataset.todoId, 10),
            status,
            _method: 'PATCH',
        });
        setRowStatus(row, status);
        const cardId = parseInt(row.closest('[data-todo-card-id]')?.dataset.todoCardId, 10);
        updateProgressBar(cardId, res.data.progress_card);
        syncCardRatio(cardId);
        applyFilter(row.closest('[data-todo-card-id]'));
    } catch (err) {
        if (select) select.value = previous;
        showToast(err.message, 'error');
    } finally {
        if (select) select.disabled = false;
        if (toggle) toggle.disabled = false;
    }
}

function createTodoRow(todoId, title, status) {
    const row = document.createElement('div');
    row.className = 'todo-row';
    row.dataset.todoId = String(todoId);
    row.dataset.status = status;

    row.innerHTML = `
        <button type="button" class="todo-checkbox" data-todo-toggle aria-label="Ubah status todo">
            <span class="iconify todo-checkmark" data-icon="ph:check-bold"></span>
        </button>
        <div class="todo-content">
            <p class="todo-title"></p>
        </div>
        <select class="form-control todo-status-select" data-todo-status aria-label="Status todo">
            <option value="pending">Belum</option>
            <option value="in_progress">Sedang</option>
            <option value="done">Selesai</option>
        </select>
        <div class="todo-actions">
            <button type="button" class="btn btn-icon btn-outline todo-delete-btn" data-todo-delete aria-label="Hapus todo">
                <span class="iconify" data-icon="ph:trash-bold" style="width:16px;height:16px"></span>
            </button>
        </div>
    `;
    row.querySelector('.todo-title').textContent = title;
    setRowStatus(row, status);
    return row;
}

function setRowStatus(row, status) {
    row.dataset.status = status;
    row.classList.toggle('is-done', status === 'done');
    row.querySelector('.todo-checkbox')?.classList.toggle('is-checked', status === 'done');

    const select = row.querySelector('[data-todo-status]');
    if (select) select.value = status;

    row.querySelector('.todo-status-badge')?.remove();
    if (status === 'in_progress') {
        const badge = document.createElement('span');
        badge.className = 'badge badge-warning todo-status-badge';
        badge.textContent = STATUS_LABELS.in_progress;
        row.querySelector('.todo-content')?.appendChild(badge);
    }
}

function applyFilter(panel) {
    if (!panel) return;
    const active = panel.querySelector('[data-todo-filter].active')?.dataset.todoFilter ?? 'all';
    panel.querySelectorAll('.todo-row').forEach(row => {
        row.hidden = active !== 'all' && row.dataset.status !== active;
    });
}

function syncCardRatio(cardId) {
    const panel = document.querySelector(`[data-todo-card-id="${cardId}"]`);
    if (!panel) return;
    const rows = panel.querySelectorAll('.todo-row');
    const done = panel.querySelectorAll('.todo-row[data-status="done"]').length;
    document.querySelectorAll(`.progress-ratio[data-card-id="${cardId}"]`).forEach(el => {
        el.textContent = `${done}/${rows.length} selesai`;
    });
}

function syncEmptyState(cardId) {
    const panel = document.querySelector(`[data-todo-card-id="${cardId}"]`);
    const list = panel?.querySelector('[data-todo-list]');
    if (!panel || !list || list.querySelector('.todo-row')) return;

    const empty = document.createElement('p');
    empty.className = 'todo-empty';
    empty.dataset.todoEmpty = '';
    empty.textContent = 'Belum ada todo di card ini';
    list.appendChild(empty);
}

function closePanel(panel) {
    panel.style.display = 'none';
    document.body.style.overflow = '';
    const url = panel.dataset.workspaceUrl;
    if (url) history.pushState(null, '', url);
}
