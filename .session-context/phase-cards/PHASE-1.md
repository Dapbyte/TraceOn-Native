# PHASE-1 — Authentication & Profile (STEP-17..22)
**Prerequisites:** PHASE-0 complete.
**Purpose:** Users register, login (rate-limited), logout, manage profile + avatar.
**After PHASE-1:** Authenticated user can login, see empty dashboard, update profile/avatar, logout securely.

---

## STEP-17 — UserModel
**Objective:** Implement `App\Models\UserModel`.
**Outputs:** Methods — `findById(int $id)`, `findByEmail(string $email)`, `existsByEmail(string $email): bool`, `create(string $name, string $email, string $hash): int`, `updateName(int $id, string $name): void`, `updateAvatar(int $id, string $path): void`. All via PDO prepared statements.
**Validation:** Unit tests for each method against test database.

## STEP-18 — LoginAttemptModel
**Objective:** Implement `App\Models\LoginAttemptModel`.
**Outputs:** Methods — `findActiveBlock(string $ip): ?array`, `registerFailure(string $ip, string $type='login'): void` (increments counter; if attempt_count >= 5 → sets blocked_until = NOW() + INTERVAL 15 MINUTE for login type; 3 attempts + 30s for join type), `reset(string $ip): void`, `purgeExpiredBlocks(): void`.
**Validation:** Insert + increment + threshold crossover sets blocked_until correctly; reset deletes; purgeExpiredBlocks removes only rows with blocked_until < NOW().

## STEP-19 — MemberModel (completes BaseController)
**Objective:** Implement `App\Models\MemberModel`.
**Outputs:** Methods — `findOne(int $id): ?array`, `getMembership(int $userId, int $workspaceId): ?array`, `isApproved(int $userId, int $workspaceId): bool`, `createPending(int $workspaceId, int $userId, string $role='Member'): int`, `reopenRequest(int $id): void`, `approve(int $id): void` (sets status=Approved, approved_at=NOW()), `reject(int $id): void` (sets status=Rejected, NO delete), `delete(int $workspaceId, int $userId): void`, `updateRole(int $id, string $role): void`, `rejectAllPending(int $workspaceId): int`, `listForWorkspace(int $workspaceId): array`.
Replaces BaseController placeholder from STEP-09 with real getMembership lookup.
**Validation:** Approve transitions Pending→Approved, stamps approved_at. Reject leaves row at Rejected without delete. UNIQUE constraint enforcement on (workspace_id, user_id).

## STEP-20 — AuthController Implementation
**Objective:** Full FR-01..FR-04 behavior.
**Outputs:** Methods — `showLogin()`, `showRegister()`, `redirectDashboard()`, `login()`, `register()`, `logout()`.
**Login flow:**
1. Trim + validate (email format, password non-empty)
2. Get real IP: REMOTE_ADDR only (never X-Forwarded-For)
3. Check login_attempts WHERE ip=? AND blocked_until > NOW() → 429 with "Terlalu banyak percobaan. Coba lagi dalam N menit."
4. Lazy cleanup: DELETE FROM login_attempts WHERE blocked_until < NOW()
5. SELECT user WHERE email=? (PDO prepared)
6. password_verify() — if FAIL: increment/insert attempt; if attempt_count>=5 → set blocked_until; return 401 "INVALID_CREDENTIALS"
7. If SUCCESS: reset attempts, session_regenerate_id(true), set session (user_id, user_name, user_email, user_avatar), CsrfManager::rotate(), return 200 {redirect:"/dashboard"}

**Register flow:**
- Validate: name (non-empty, max 100), email (FILTER_VALIDATE_EMAIL, max 100, UNIQUE), password (min 8, ≥1 letter ≥1 digit), confirm_password match, honeypot field `website` (if non-empty → reject silently with 200 success to fool bots)
- Hash: password_hash($password, PASSWORD_BCRYPT, ['cost' => (int)$_ENV['BCRYPT_COST']])
- On success: redirect /login with flash "Registrasi berhasil. Silakan masuk."
- On email duplicate: 422 {error:"EMAIL_TAKEN",message:"Email sudah digunakan"}

**Logout:** session_unset(), session_destroy(), session_write_close(), clear cookie, CsrfManager::rotate(), redirect 302 /login.

**Idle check in BaseController::requireAuth:** store $_SESSION['last_activity']; if NOW - last_activity > SESSION_TIMEOUT → destroy + redirect /login?reason=expired. Also: Cache-Control: no-store on authenticated page responses.

**Validation:** 401/422/429 flows verified. Successful login: same PDO session ID before/after? No — session_regenerate_id(true) changes it.

## STEP-21 — ProfileController and Avatar Upload
**Objective:** Implement `ProfileController@show`/`update` and complete `FileUploadHelper`.
**Outputs:**
- `FileUploadHelper::saveAvatar(array $file): string` — check error≠UPLOAD_ERR_OK, size≤2MB, mime_content_type() against allowlist [image/jpeg,image/png,image/webp], getimagesize() parse, generate bin2hex(random_bytes(16)) filename, move_uploaded_file
- `FileUploadHelper::deleteAvatar(?string $path): void` — delete old file if exists
- `ProfileController@show`: render profile with pre-fetched user data
- `ProfileController@update`: validate name (trim, max 100, non-empty); handle avatar upload if file present; update DB; update $_SESSION name/avatar; return {success:true,message:"Profil diperbarui",data:{avatar_path:"..."}}
**Risks:** Path traversal via filename — mitigated by bin2hex(random_bytes(16)) + verified extension (never use client filename).
**Validation:** PHP file disguised as JPG fails GD check → 422. Oversized file → 422. Valid image → lands in /public/uploads/avatars/ with hex name.

## STEP-22 — Auth Pages and Layouts
**Objective:** Build layouts and auth/profile views.
**Outputs:**
- `app/Views/layouts/auth.php`: login/register shell, links tokens.css/base.css/components.css, Google Fonts preconnect, Iconify CDN, CSRF meta tag
- `app/Views/layouts/main.php`: sidebar + content shell (sidebar populated in STEP-25), same head assets
- `app/Views/pages/login.php`: form POSTs via apiPost to /api/auth/login; CSRF field in meta tag; honeypot not needed on login
- `app/Views/pages/register.php`: form with honeypot `<input name="website">` hidden via `position:absolute;left:-9999px`, `aria-hidden="true"`, `tabindex="-1"`
- `app/Views/pages/profile.php`: form for name update + avatar upload
- JS wired: `toast.js`, `modal.js`, `sidebar.js` minimal init
**Head assets (in every layout):**
```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/@iconify/iconify@3/dist/iconify.min.js"></script>
<meta name="csrf-token" content="<?= htmlspecialchars(CsrfManager::generate(), ENT_QUOTES, 'UTF-8') ?>">
```
**Validation:** Pages render without console errors; CSP not violated; honeypot present and hidden accessibly.

---

## PHASE-1 Definition of Done

1. Register: invalid email/weak pass/mismatch confirm/duplicate email/honeypot all return correct error codes. Success → redirect /login with Bahasa flash.
2. Login: invalid creds → 401 generic Bahasa message. 5 failures from same IP → 429 with 15-min countdown. Success → clear attempts, regenerate session, rotate CSRF, set last_activity, redirect /dashboard.
3. Logout: session cookie invalidated, session destroyed, CSRF rotated, redirect /login. Back button → /login.
4. Idle expiry: last_activity more than SESSION_TIMEOUT seconds → redirect /login?reason=expired.
5. Profile: name trim+length validated; avatar 2MB cap, MIME allowlist, GD parse; random filename; old avatar removed on replace; sidebar reflects new name/avatar on next render.
6. CSP and security headers present on every page; no inline scripts/styles regress.
7. Manual security smoke: SQL injection on email/password rejected; CSRF-missing token rejected; honeypot blocks bot.
