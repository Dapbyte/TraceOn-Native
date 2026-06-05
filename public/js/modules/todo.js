import { apiPost } from './api.js';
import { showToast } from './toast.js';
import { updateProgressBar, updateWorkspaceProgress } from './card.js';

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
        if (select) {
            updateTodoStatus(select.closest('.todo-row'), select.value, select);
            return;
        }

        const prioritySelect = e.target.closest('[data-todo-priority]');
        if (prioritySelect) {
            updateTodoPriority(prioritySelect.closest('.todo-row'), prioritySelect.value, prioritySelect);
            return;
        }
    });

    // Inline title edit — save on blur or Enter
    panel.addEventListener('blur', (e) => {
        const input = e.target.closest('[data-todo-edit-title]');
        if (!input) return;
        const row = input.closest('.todo-row');
        if (!row) return;
        const todoId = parseInt(row.dataset.todoId, 10);
        const newTitle = input.value.trim();
        if (!newTitle || newTitle === input.defaultValue) return;
        saveTodoTitle(todoId, newTitle, input);
    }, true);

    panel.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter') return;
        const input = e.target.closest('[data-todo-edit-title]');
        if (!input) return;
        input.blur();
    });

    // Filter dropdowns
    const statusFilter = panel.querySelector('[data-todo-status-filter]');
    const priorityFilter = panel.querySelector('[data-todo-priority-filter]');
    if (statusFilter) {
        statusFilter.addEventListener('change', () => applyFilters(panel));
    }
    if (priorityFilter) {
        priorityFilter.addEventListener('change', () => applyFilters(panel));
    }

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

async function saveTodoTitle(todoId, newTitle, input) {
    try {
        await apiPost('/api/todo/update', {
            todo_id: todoId,
            title: newTitle,
            _method: 'PATCH',
        });
        input.defaultValue = newTitle;
        showToast('Judul todo diperbarui', 'success');
    } catch (err) {
        input.value = input.defaultValue;
        showToast(err.message, 'error');
    }
}

async function updateTodoPriority(row, priority, select) {
    if (!row || !['low', 'medium', 'high'].includes(priority)) return;
    const todoId = parseInt(row.dataset.todoId, 10);
    if (!todoId) return;

    select.disabled = true;
    try {
        await apiPost('/api/todo/update', {
            todo_id: todoId,
            priority,
            _method: 'PATCH',
        });
        row.dataset.priority = priority;
        showToast('Prioritas diperbarui', 'success');
    } catch (err) {
        select.value = row.dataset.priority || 'medium';
        showToast(err.message, 'error');
    } finally {
        select.disabled = false;
    }
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
            updateWorkspaceProgress();
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
        panel.querySelector('[data-todo-list]')?.appendChild(createTodoRow(res.data.todo_id, title, 'pending', 'medium'));
        input.value = '';
        updateProgressBar(cardId, res.data.progress_card);
        updateWorkspaceProgress();
        syncCardRatio(cardId);
        applyFilters(panel);
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
        updateWorkspaceProgress();
        syncCardRatio(cardId);
        applyFilters(row.closest('[data-todo-card-id]'));
    } catch (err) {
        if (select) select.value = previous;
        showToast(err.message, 'error');
    } finally {
        if (select) select.disabled = false;
        if (toggle) toggle.disabled = false;
    }
}

function createTodoRow(todoId, title, status, priority) {
    priority = priority || 'medium';
    const row = document.createElement('div');
    row.className = 'todo-row';
    row.dataset.todoId = String(todoId);
    row.dataset.status = status;
    row.dataset.priority = priority;

    row.innerHTML = `
        <button type="button" class="todo-checkbox" data-todo-toggle aria-label="Ubah status todo">
            <span class="iconify todo-checkmark" data-icon="ph:check-bold"></span>
        </button>
        <div class="todo-content">
            <input type="text" class="todo-title-input" value="${escapeAttr(title)}" data-todo-edit-title maxlength="255">
        </div>
        <select class="form-control todo-status-select" data-todo-status aria-label="Status todo">
            <option value="pending">Belum</option>
            <option value="in_progress">Sedang</option>
            <option value="done">Selesai</option>
        </select>
        <select class="form-control todo-priority-select" data-todo-priority aria-label="Prioritas todo">
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
        </select>
        <div class="todo-actions">
            <button type="button" class="btn btn-icon btn-outline todo-delete-btn" data-todo-delete aria-label="Hapus todo">
                <span class="iconify" data-icon="ph:trash-bold" style="width:16px;height:16px"></span>
            </button>
        </div>
    `;
    setRowStatus(row, status);
    return row;
}

function setRowStatus(row, status) {
    row.dataset.status = status;
    row.classList.toggle('is-done', status === 'done');
    row.querySelector('.todo-checkbox')?.classList.toggle('is-checked', status === 'done');

    const select = row.querySelector('[data-todo-status]');
    if (select) select.value = status;

    // Also init the priority select
    const prioritySelect = row.querySelector('[data-todo-priority]');
    if (prioritySelect && row.dataset.priority) {
        prioritySelect.value = row.dataset.priority;
    }
}

function applyFilters(panel) {
    if (!panel) return;
    const statusFilter = panel.querySelector('[data-todo-status-filter]');
    const priorityFilter = panel.querySelector('[data-todo-priority-filter]');
    const statusVal = statusFilter ? statusFilter.value : 'all';
    const priorityVal = priorityFilter ? priorityFilter.value : 'all';
    const list = panel.querySelector('[data-todo-list]');

    let visibleCount = 0;
    panel.querySelectorAll('.todo-row').forEach(row => {
        const matchStatus = statusVal === 'all' || row.dataset.status === statusVal;
        const matchPriority = priorityVal === 'all' || row.dataset.priority === priorityVal;
        const show = matchStatus && matchPriority;
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });

    // Show filter-empty state when filters hide all rows
    let emptyEl = list.querySelector('[data-todo-filter-empty]');
    const hasRows = list.querySelectorAll('.todo-row').length > 0;
    if (hasRows && visibleCount === 0) {
        if (!emptyEl) {
            emptyEl = document.createElement('div');
            emptyEl.className = 'todo-empty';
            emptyEl.dataset.todoFilterEmpty = '';
            emptyEl.innerHTML = `
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                    <span class="iconify" data-icon="ph:funnel-bold" style="width: 32px; height: 32px; color: var(--color-border);"></span>
                    <div>
                        <p style="margin: 0; font-weight: 500; color: var(--text-primary);">Tidak ada todo yang sesuai filter</p>
                        <p style="margin: 4px 0 0; font-size: 13px; color: var(--text-muted);">Sesuaikan filter atau buat todo baru.</p>
                    </div>
                </div>
            `;
            list.appendChild(emptyEl);
        }
        emptyEl.style.display = '';
    } else if (emptyEl) {
        emptyEl.style.display = 'none';
    }
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
    if (!panel || !list) return;

    // Remove existing empty states
    list.querySelectorAll('[data-todo-empty]').forEach(e => e.remove());
    list.querySelectorAll('[data-todo-filter-empty]').forEach(e => e.remove());

    if (!list.querySelector('.todo-row')) {
        const empty = document.createElement('p');
        empty.className = 'todo-empty';
        empty.dataset.todoEmpty = '';
        empty.textContent = 'Belum ada todo';
        list.appendChild(empty);
    }
}

function closePanel(panel) {
    panel.style.display = 'none';
    document.body.style.overflow = '';
    if (window.location.hash.startsWith('#card=')) {
        history.pushState(null, '', window.location.pathname + window.location.search);
    }
}

function escapeAttr(str) {
    return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
