<?php
// Profile page — rendered inside layouts/main.php
// $user, $csrf passed from ProfileController
$userName   = htmlspecialchars($user['name']        ?? '', ENT_QUOTES, 'UTF-8');
$userEmail  = htmlspecialchars($user['email']       ?? '', ENT_QUOTES, 'UTF-8');
$userAvatar = htmlspecialchars($user['avatar_path'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<div class="main-header">
    <button class="hamburger-btn" id="hamburger-btn" aria-label="Buka menu">
        <span class="iconify" data-icon="ph:list-bold" style="width:24px;height:24px"></span>
    </button>
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="/dashboard">Dashboard</a>
        <span class="breadcrumb-sep">/</span>
        <span class="breadcrumb-current">Profil</span>
    </nav>
</div>

<div class="page-content" style="max-width:600px;margin:0 auto;">
    <h1 style="margin-bottom:var(--space-6);">Pengaturan Profil</h1>

    <div class="card" style="margin-bottom:var(--space-6);">
        <h2 style="margin-bottom:var(--space-6);font-size:var(--text-h3);">Foto &amp; Nama</h2>

        <!-- Avatar preview -->
        <div style="display:flex;align-items:center;gap:var(--space-6);margin-bottom:var(--space-6);">
            <div id="avatar-preview-wrapper" style="width:80px;height:80px;border-radius:var(--radius-full);overflow:hidden;background:var(--color-secondary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <?php if ($userAvatar): ?>
                    <img id="avatar-preview" src="<?= $userAvatar ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                    <span id="avatar-initials" style="font-size:28px;font-weight:700;color:#fff;"><?= mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1)) ?></span>
                <?php endif; ?>
            </div>
            <div>
                <label for="avatar" class="btn btn-outline btn-sm" style="cursor:pointer;">
                    <span class="iconify" data-icon="ph:upload-simple-bold" style="width:16px;height:16px"></span>
                    Ganti Foto
                </label>
                <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp" style="display:none;">
                <p class="form-helper" style="margin-top:var(--space-2);">JPG, PNG, WebP. Maks 2MB.</p>
                <span class="form-error" id="error-avatar" style="display:none;"></span>
            </div>
        </div>

        <form id="profile-form" novalidate>
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label" for="name">Nama Lengkap</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    class="form-control"
                    value="<?= $userName ?>"
                    maxlength="100"
                    required
                >
                <span class="form-error" id="error-name" style="display:none;"></span>
            </div>

            <div class="form-group" style="margin-bottom:24px;">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="<?= $userEmail ?>" disabled>
                <span class="form-helper">Email tidak dapat diubah.</span>
            </div>

            <div id="error-general" class="form-error" style="display:none;margin-bottom:12px;"></div>

            <button type="submit" class="btn btn-primary" id="btn-save">
                <span id="btn-save-label">Simpan Perubahan</span>
                <span id="btn-save-spinner" style="display:none;">
                    <span class="iconify" data-icon="ph:spinner-bold" style="width:16px;height:16px;animation:spin 1s linear infinite"></span>
                </span>
            </button>
        </form>
    </div>

    <!-- Logout -->
    <div class="card">
        <h2 style="margin-bottom:var(--space-4);font-size:var(--text-h3);">Sesi</h2>
        <form id="logout-form">
            <button type="submit" class="btn btn-outline">
                <span class="iconify" data-icon="ph:sign-out-bold" style="width:16px;height:16px"></span>
                Keluar
            </button>
        </form>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script type="module">
import { apiPost } from '/js/modules/api.js';
import { showToast } from '/js/modules/toast.js';

// Avatar file preview
const avatarInput = document.getElementById('avatar');
avatarInput.addEventListener('change', () => {
    const file = avatarInput.files[0];
    if (!file) return;
    const wrapper = document.getElementById('avatar-preview-wrapper');
    const reader  = new FileReader();
    reader.onload = (e) => {
        wrapper.innerHTML = `<img id="avatar-preview" src="${e.target.result}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">`;
    };
    reader.readAsDataURL(file);
});

// Profile update
const form     = document.getElementById('profile-form');
const btn      = document.getElementById('btn-save');
const btnLabel = document.getElementById('btn-save-label');
const btnSpin  = document.getElementById('btn-save-spinner');

function setLoading(v) {
    btn.disabled = v;
    btnLabel.style.display = v ? 'none'   : 'inline';
    btnSpin.style.display  = v ? 'inline' : 'none';
}

function clearErrors() {
    ['name', 'avatar', 'general'].forEach(f => {
        const el = document.getElementById('error-' + f);
        if (el) { el.style.display = 'none'; el.textContent = ''; }
    });
    document.getElementById('name')?.classList.remove('is-error');
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors();
    setLoading(true);

    const formData = new FormData();
    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('name', document.getElementById('name').value);
    const avatarFile = document.getElementById('avatar').files[0];
    if (avatarFile) formData.append('avatar', avatarFile);

    try {
        const res = await fetch('/api/profile/update', { method: 'POST', body: formData });
        const data = await res.json();
        setLoading(false);
        if (!data.success) throw { code: data.error, message: data.message, errors: data.errors };
        showToast('Profil berhasil diperbarui', 'success');
        if (data.data?.avatar_path) {
            const wrapper = document.getElementById('avatar-preview-wrapper');
            wrapper.innerHTML = `<img src="${data.data.avatar_path}?t=${Date.now()}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">`;
        }
    } catch (err) {
        setLoading(false);
        if (err.code === 'VALIDATION_ERROR' && err.errors) {
            Object.entries(err.errors).forEach(([f, msg]) => {
                const el = document.getElementById('error-' + f);
                if (el) { el.textContent = msg; el.style.display = 'flex'; }
                document.getElementById(f)?.classList.add('is-error');
            });
        } else {
            const errG = document.getElementById('error-general');
            errG.textContent = err.message ?? 'Gagal menyimpan.';
            errG.style.display = 'flex';
        }
    }
});

// Logout
document.getElementById('logout-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
        await apiPost('/api/auth/logout', {});
        window.location.href = '/login';
    } catch {
        window.location.href = '/login';
    }
});
</script>
