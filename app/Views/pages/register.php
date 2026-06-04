<?php
// Register page — rendered inside layouts/auth.php
// Honeypot: #website input hidden via CSS + aria-hidden + tabindex=-1 (RULE-11 / FR-04)
?>
<div class="auth-logo">TraceOn</div>
<h2 style="text-align:center;margin-bottom:24px;font-size:var(--text-h2);">Buat akun baru</h2>

<form id="register-form" novalidate>
    <!-- Honeypot anti-spam field (hidden from humans, traps bots) -->
    <input
        type="text"
        name="website"
        id="website"
        aria-hidden="true"
        tabindex="-1"
        autocomplete="off"
        style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;"
    >

    <div class="form-group" style="margin-bottom:16px;">
        <label class="form-label" for="name">Nama Lengkap</label>
        <input
            type="text"
            id="name"
            name="name"
            class="form-control"
            placeholder="Nama Anda"
            autocomplete="name"
            maxlength="100"
            required
        >
        <span class="form-error" id="error-name" style="display:none;"></span>
    </div>

    <div class="form-group" style="margin-bottom:16px;">
        <label class="form-label" for="email">Email</label>
        <input
            type="email"
            id="email"
            name="email"
            class="form-control"
            placeholder="nama@email.com"
            autocomplete="email"
            maxlength="100"
            required
        >
        <span class="form-error" id="error-email" style="display:none;"></span>
    </div>

    <div class="form-group" style="margin-bottom:16px;">
        <label class="form-label" for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            class="form-control"
            placeholder="Min. 8 karakter, 1 huruf &amp; 1 angka"
            autocomplete="new-password"
            required
        >
        <span class="form-error" id="error-password" style="display:none;"></span>
    </div>

    <div class="form-group" style="margin-bottom:24px;">
        <label class="form-label" for="confirm_password">Konfirmasi Password</label>
        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            class="form-control"
            placeholder="Ulangi password"
            autocomplete="new-password"
            required
        >
        <span class="form-error" id="error-confirm_password" style="display:none;"></span>
    </div>

    <div id="error-general" class="form-error" style="display:none;margin-bottom:12px;"></div>

    <button type="submit" class="btn btn-primary" id="btn-register" style="width:100%;">
        <span id="btn-register-label">Daftar</span>
        <span id="btn-register-spinner" style="display:none;">
            <span class="iconify" data-icon="ph:spinner-bold" style="width:16px;height:16px;animation:spin 1s linear infinite"></span>
        </span>
    </button>
</form>

<p style="text-align:center;margin-top:20px;font-size:var(--text-sm);color:var(--text-muted);">
    Sudah punya akun?
    <a href="/login">Masuk sekarang</a>
</p>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script type="module">
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
        const inp = document.getElementById(f === 'general' ? null : f);
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
        website:          document.getElementById('website').value, // honeypot
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
</script>
