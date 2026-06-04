/**
 * TraceOn — Toast Notification System
 * Bottom-right, slide-in from right. Max 5 visible. Auto-dismiss.
 * Fully implemented in PHASE-5; stub here for PHASE-0 imports.
 */

let container = null;

export function initToastContainer() {
    if (container) return;
    container = document.createElement('div');
    container.className = 'toast-container';
    container.setAttribute('aria-live', 'polite');
    container.setAttribute('aria-atomic', 'false');
    document.body.appendChild(container);
}

/**
 * @param {string} message
 * @param {'success'|'error'|'warning'|'info'} type
 */
export function showToast(message, type = 'info') {
    if (!container) initToastContainer();

    const durations = { success: 4000, info: 4000, warning: 6000, error: 8000 };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type} toast-animate-in`;
    toast.innerHTML = `
        <span class="toast-message">${escapeHtml(message)}</span>
        <button class="toast-close" aria-label="Tutup notifikasi">&times;</button>
    `;

    toast.querySelector('.toast-close').addEventListener('click', () => removeToast(toast));

    // Cap at 5 toasts
    while (container.children.length >= 5) {
        container.removeChild(container.firstChild);
    }

    container.appendChild(toast);

    setTimeout(() => removeToast(toast), durations[type] ?? 4000);
}

/**
 * @param {string} message
 * @param {Function} retryFn
 */
export function showRetryToast(message, retryFn) {
    if (!container) initToastContainer();

    const toast = document.createElement('div');
    toast.className = 'toast toast-error toast-animate-in';
    toast.innerHTML = `
        <span class="toast-message">
            ${escapeHtml(message)}
            <a href="#" class="toast-retry" style="margin-left:8px;font-weight:600;">Coba Lagi</a>
        </span>
        <button class="toast-close" aria-label="Tutup notifikasi">&times;</button>
    `;

    toast.querySelector('.toast-retry').addEventListener('click', (e) => {
        e.preventDefault();
        removeToast(toast);
        retryFn();
    });

    toast.querySelector('.toast-close').addEventListener('click', () => removeToast(toast));

    container.appendChild(toast);
    setTimeout(() => removeToast(toast), 8000);
}

function removeToast(toast) {
    if (!toast.parentNode) return;
    toast.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    setTimeout(() => toast.parentNode?.removeChild(toast), 220);
}

function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
