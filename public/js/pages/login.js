import { apiPost } from '/js/modules/api.js';

const form        = document.getElementById('login-form');
const btnLogin    = document.getElementById('btn-login');
const btnLabel    = document.getElementById('btn-login-label');
const btnSpinner  = document.getElementById('btn-login-spinner');
const errGeneral  = document.getElementById('error-general');
const errEmail    = document.getElementById('error-email');
const errPassword = document.getElementById('error-password');

function clearErrors() {
    [errGeneral, errEmail, errPassword].forEach(el => {
        el.style.display = 'none';
        el.textContent = '';
    });
    document.getElementById('email').classList.remove('is-error');
    document.getElementById('password').classList.remove('is-error');
}

function setLoading(loading) {
    btnLogin.disabled = loading;
    btnLabel.style.display   = loading ? 'none'   : 'inline';
    btnSpinner.style.display = loading ? 'inline' : 'none';
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors();
    setLoading(true);

    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    try {
        const res = await apiPost('/api/auth/login', { email, password });
        window.location.href = res.data?.redirect ?? '/dashboard';
    } catch (err) {
        setLoading(false);
        if (err.code === 'INVALID_CREDENTIALS' || err.code === 'RATE_LIMITED') {
            errGeneral.textContent = err.message;
            errGeneral.style.display = 'flex';
        } else {
            errGeneral.textContent = err.message ?? 'Terjadi kesalahan. Coba lagi.';
            errGeneral.style.display = 'flex';
        }
    }
});
