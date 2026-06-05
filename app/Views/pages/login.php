<?php
// Login page — rendered inside layouts/auth.php
// $csrf, $flash, $reason passed from AuthController
?>
<div class="auth-logo">TraceOn</div>

<?php if ($flash): ?>
<div class="badge badge-success" style="display:block;text-align:center;margin-bottom:16px;padding:10px;">
    <?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<?php if ($reason === 'expired'): ?>
<div class="badge badge-warning" style="display:block;text-align:center;margin-bottom:16px;padding:10px;">
    Sesi Anda telah berakhir. Silakan login kembali.
</div>
<?php endif; ?>

<h2 style="text-align:center;margin-bottom:24px;font-size:var(--text-h2);">Masuk ke akun Anda</h2>

<form id="login-form" method="post" action="/api/auth/login" novalidate>
    <div class="form-group" style="margin-bottom:16px;">
        <label class="form-label" for="email">Email</label>
        <input
            type="email"
            id="email"
            name="email"
            class="form-control"
            placeholder="nama@email.com"
            autocomplete="email"
            required
        >
        <span class="form-error" id="error-email" style="display:none;"></span>
    </div>

    <div class="form-group" style="margin-bottom:24px;">
        <label class="form-label" for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            class="form-control"
            placeholder="Password Anda"
            autocomplete="current-password"
            required
        >
        <span class="form-error" id="error-password" style="display:none;"></span>
    </div>

    <div id="error-general" class="form-error" style="display:none;margin-bottom:12px;"></div>

    <button type="submit" class="btn btn-primary" id="btn-login" style="width:100%;">
        <span id="btn-login-label">Masuk</span>
        <span id="btn-login-spinner" style="display:none;">
            <span class="iconify" data-icon="ph:spinner-bold" style="width:16px;height:16px;animation:spin 1s linear infinite"></span>
        </span>
    </button>
</form>

<p style="text-align:center;margin-top:20px;font-size:var(--text-sm);color:var(--text-muted);">
    Belum punya akun?
    <a href="/register">Daftar sekarang</a>
</p>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script type="module" src="/js/pages/login.js"></script>
