import { apiGet, apiPost } from './api.js';
import { showToast } from './toast.js';

let debounceTimeout = null;
let currentOffset = 0;
const LIMIT = 50;

let filterState = {
    filter_type: [],
    date_from: null,
    date_to: null,
    user_id: null
};

export function initActivitySearch(workspaceId) {
    const searchInput = document.getElementById('activity-search-input');
    const clearBtn = document.getElementById('btn-activity-clear-search');
    const refreshBtn = document.getElementById('btn-activity-refresh');
    const loadMoreBtn = document.getElementById('btn-activity-load-more');

    // Search input with clear button toggle
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearBtn.style.display = searchInput.value.trim() ? 'flex' : 'none';
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(() => {
                fetchActivities(workspaceId, true);
            }, 500);
        });
    }

    // Clear button — clears input and restores full list
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            if (searchInput) {
                searchInput.value = '';
                clearBtn.style.display = 'none';
            }
            fetchActivities(workspaceId, true);
        });
    }

    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => fetchActivities(workspaceId, true));
    }

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', () => loadMore(workspaceId, currentOffset));
    }

    // Modal Clear Log Hook
    const btnClearLog = document.getElementById('btn-activity-clear-log');
    if (btnClearLog) {
        btnClearLog.addEventListener('click', () => {
            const modal = document.getElementById('modal-clear-log');
            if (modal) {
                modal.style.display = 'flex';
                modal.querySelector('button')?.focus();
            }
        });
    }

    document.getElementById('btn-clear-log-cancel')?.addEventListener('click', () => {
        document.getElementById('modal-clear-log').style.display = 'none';
    });

    document.getElementById('btn-clear-log-confirm')?.addEventListener('click', async () => {
        document.getElementById('modal-clear-log').style.display = 'none';
        try {
            await apiPost('/api/activity/clear', { workspace_id: workspaceId, _method: 'DELETE' });
            showToast('Log aktivitas berhasil dihapus', 'success');
            fetchActivities(workspaceId, true);
        } catch (err) {
            showToast(err.message, 'error');
        }
    });

    // Lazy load on first tab click
    const activityTabBtn = document.querySelector('.tab-btn[data-tab="activity"]');
    if (activityTabBtn && !activityTabBtn.dataset.loaded) {
        activityTabBtn.addEventListener('click', () => {
            if (!activityTabBtn.dataset.loaded) {
                fetchActivities(workspaceId, true);
                activityTabBtn.dataset.loaded = 'true';
            }
        });
    }
}

export async function loadMore(workspaceId, offset) {
    await fetchActivities(workspaceId, false, offset);
}

export async function refreshActivity(workspaceId) {
    await fetchActivities(workspaceId, true);
}

async function fetchActivities(workspaceId, replace = true, offset = 0) {
    const listContainer = document.getElementById('activity-list');
    const loadMoreBtn = document.getElementById('btn-activity-load-more');
    const searchInput = document.getElementById('activity-search-input');
    const emptyState = document.getElementById('activity-empty-state');
    const clearBtn = document.getElementById('btn-activity-clear-search');

    const query = searchInput ? searchInput.value.trim() : '';

    if (query && clearBtn) {
        clearBtn.style.display = 'flex';
    }

    if (replace && listContainer) {
        listContainer.style.opacity = '0.5';
    }

    try {
        const params = {
            workspace_id: workspaceId,
            offset: offset,
            limit: LIMIT,
            search: query,
            ...filterState
        };

        const res = await apiGet('/api/activity/fetch', params);

        if (replace && listContainer) {
            listContainer.innerHTML = '';
            listContainer.style.opacity = '1';
        }

        currentOffset = res.meta.offset + res.data.length;

        if (res.data.length === 0 && replace) {
            if (emptyState) {
                emptyState.style.display = 'flex';
                const emptyText = emptyState.querySelector('.empty-state-text');
                if (query) {
                    emptyText.textContent = `Tidak ada hasil untuk '${query}'`;
                } else {
                    emptyText.textContent = 'Belum ada aktivitas di workspace ini';
                }
            }
            if (loadMoreBtn) loadMoreBtn.style.display = 'none';
        } else {
            if (emptyState) emptyState.style.display = 'none';

            res.data.forEach(act => {
                listContainer.appendChild(createActivityRow(act));
            });

            if (loadMoreBtn) {
                loadMoreBtn.style.display = res.meta.has_more ? 'inline-flex' : 'none';
            }
        }
    } catch (err) {
        if (replace && listContainer) listContainer.style.opacity = '1';
        showToast(err.message, 'error');
    }
}

function createActivityRow(act) {
    const div = document.createElement('div');
    div.style.display = 'flex';
    div.style.gap = '12px';
    div.style.padding = '12px 0';
    div.style.borderBottom = '1px solid var(--color-border)';

    let avatarHtml = '';
    if (act.avatar_path) {
        avatarHtml = `<img src="${act.avatar_path}" alt="" style="width:32px;height:32px;border-radius:var(--radius-full);object-fit:cover;">`;
    } else {
        const initial = act.user_name ? act.user_name.charAt(0).toUpperCase() : 'S';
        avatarHtml = `<div style="width:32px;height:32px;border-radius:var(--radius-full);background:var(--color-secondary);color:#FFF;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:14px;">${initial}</div>`;
    }

    div.innerHTML = `
        ${avatarHtml}
        <div style="flex:1;">
            <p style="margin-bottom:4px;font-size:var(--text-sm);color:var(--text-primary);">
                ${escapeHtml(act.action)}
            </p>
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:12px;color:var(--text-muted);">${formatRelative(act.created_at)}</span>
                <span class="badge badge-neutral" style="font-size:10px;">${escapeHtml(act.activity_type)}</span>
            </div>
        </div>
    `;
    return div;
}

function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

export function formatRelative(isoTimestamp) {
    const date = new Date(isoTimestamp.replace(' ', 'T'));
    const now = new Date();
    const diffMs = now - date;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);

    if (diffMin < 1) return 'Baru saja';
    if (diffMin < 60) return `${diffMin} menit lalu`;
    if (diffHour < 24) return `${diffHour} jam lalu`;
    if (diffDay < 7) return `${diffDay} hari lalu`;

    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
    const d = date.getDate().toString().padStart(2, '0');
    const m = months[date.getMonth()];
    const y = date.getFullYear();
    const h = date.getHours().toString().padStart(2, '0');
    const min = date.getMinutes().toString().padStart(2, '0');

    return `${d} ${m} ${y} ${h}:${min}`;
}
