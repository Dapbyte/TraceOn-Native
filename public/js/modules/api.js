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
            throw { code: data.error ?? 'SERVER_ERROR', message: data.message ?? 'Terjadi kesalahan.', status: res.status };
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
    const url = Object.keys(params).length
        ? path + '?' + new URLSearchParams(params).toString()
        : path;

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
            throw { code: data.error ?? 'SERVER_ERROR', message: data.message ?? 'Terjadi kesalahan.', status: res.status };
        }

        return data;
    } catch (err) {
        if (err.code) throw err;
        throw { code: 'NETWORK_ERROR', message: 'Koneksi terputus. Coba refresh halaman.', status: 0 };
    }
}
