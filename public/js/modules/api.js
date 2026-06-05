/**
 * TraceOn — Global Fetch Wrapper
 * All network calls go through apiPost/apiGet. No raw fetch() elsewhere (RULE-35).
 * Injects csrf_token + _method. Parses standard envelope. Throws structured errors.
 */

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta) throw { code: 'CSRF_MISSING', message: 'Token keamanan tidak ditemukan. Muat ulang halaman.', status: 0 };
    return meta.getAttribute('content');
}

function handleSessionExpired(code) {
    if (code === 'UNAUTHENTICATED' || code === 'SESSION_EXPIRED') {
        if (!window.__sessionRedirecting) {
            window.__sessionRedirecting = true;
            window.location.href = '/login?reason=expired';
        }
        return true;
    }
    return false;
}

/**
 * POST (or method-overridden PATCH/DELETE) request.
 * @param {string} path
 * @param {object} body — include _method:'PATCH'|'DELETE' for overrides
 * @returns {Promise<object>} resolved data on success
 * @throws {{ code: string, message: string, status: number }} on error
 */
export async function apiPost(path, body = {}) {
    let csrf;
    try {
        csrf = getCsrfToken();
    } catch (e) {
        throw e;
    }

    try {
        const res = await fetch(path, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ...body, csrf_token: csrf }),
        });

        let data;
        try {
            data = await res.json();
        } catch {
            throw { code: 'PARSE_ERROR', message: 'Respons tidak valid dari server.', status: res.status };
        }

        if (!data.success) {
            const code = data.error ?? 'SERVER_ERROR';
            if (handleSessionExpired(code)) {
                throw { code, message: data.message ?? '', status: res.status };
            }
            throw { code, message: data.message ?? 'Terjadi kesalahan.', status: res.status, errors: data.errors };
        }

        return data;
    } catch (err) {
        if (err.code) throw err; // Already structured application error
        throw { code: 'NETWORK_ERROR', message: 'Koneksi terputus. Coba refresh halaman.', status: 0 };
    }
}

/**
 * GET request.
 * @param {string} path
 * @param {object} params — query string params
 * @returns {Promise<object>} resolved data on success
 */
export async function apiGet(path, params = {}) {
    const query = buildQuery(params);
    const url = query ? path + '?' + query : path;

    try {
        const res = await fetch(url, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
        });

        let data;
        try {
            data = await res.json();
        } catch {
            throw { code: 'PARSE_ERROR', message: 'Respons tidak valid dari server.', status: res.status };
        }

        if (!data.success) {
            const code = data.error ?? 'SERVER_ERROR';
            if (handleSessionExpired(code)) {
                throw { code, message: data.message ?? '', status: res.status };
            }
            throw { code, message: data.message ?? 'Terjadi kesalahan.', status: res.status };
        }

        return data;
    } catch (err) {
        if (err.code) throw err;
        throw { code: 'NETWORK_ERROR', message: 'Koneksi terputus. Coba refresh halaman.', status: 0 };
    }
}

function buildQuery(params) {
    const query = new URLSearchParams();

    for (const [key, value] of Object.entries(params || {})) {
        if (value === null || value === undefined || value === '') continue;

        if (Array.isArray(value)) {
            for (const item of value) {
                if (item === null || item === undefined || item === '') continue;
                query.append(key, String(item));
            }
            continue;
        }

        query.append(key, String(value));
    }

    return query.toString();
}
