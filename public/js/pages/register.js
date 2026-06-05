import { apiPost } from '/js/modules/api.js';

const form      = document.getElementById('register-form');
const btn       = document.getElementById('btn-register');
const btnLabel  = document.getElementById('btn-register-label');
const btnSpin   = document.getElementById('btn-register-spinner');
const errFields = ['name', 'email', 'password', 'confirm_password', 'general'];

function clearErrors() {
    errFields.forEach(f => {
        const el = document.getElementById('error-' + f);
        if (el) { el.style.display = 'none'; el.textContent = ''; }
        if (f === 'general') return;
        const inp = document.getElementById(f);
        if (inp) inp.classList.remove('is-error');
    });
}

function showFieldError(field, msg) {
    const el = document.getElementById('error-' + field);
    if (!el) return;
    el.textContent = msg;
    el.style.display = 'flex';
    const inp = document.getElementById(field);
    if (inp) inp.classList.add('is-error');
}

function setLoading(v) {
    btn.disabled = v;
    btnLabel.style.display = v ? 'none'   : 'inline';
    btnSpin.style.display  = v ? 'inline' : 'none';
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors();
    setLoading(true);

    const body = {
        name:             document.getElementById('name').value,
        email:            document.getElementById('email').value.trim(),
        password:         document.getElementById('password').value,
        confirm_password: document.getElementById('confirm_password').value,
        website:          document.getElementById('website').value,
    };

    try {
        const res = await apiPost('/api/auth/register', body);
        window.location.href = res.data?.redirect ?? '/login';
    } catch (err) {
        setLoading(false);
        if (err.code === 'VALIDATION_ERROR' && err.errors) {
            Object.entries(err.errors).forEach(([field, msg]) => showFieldError(field, msg));
        } else {
            const errGeneral = document.getElementById('error-general');
            errGeneral.textContent = err.message ?? 'Terjadi kesalahan.';
            errGeneral.style.display = 'flex';
        }
    }
});
