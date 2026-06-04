# PHASE-0 — Bootstrap & Foundation (STEP-01..16)

**Purpose:** Stand up empty house. No features. Layered skeleton, DB, security gates, design tokens.
**After PHASE-0:** App boots, responds with security headers, 404 for unknown routes, all 7 DB tables created.

---

## STEP-01 — Repository and Folder Skeleton
**Objective:** Create project tree per spec §1.1.
**Outputs:**
- `/app/Core/`, `/app/Controllers/`, `/app/Models/`, `/app/Views/{layouts,partials,pages}/`, `/app/Helpers/`, `/app/Config/`
- `/public/{css,js/modules,uploads/avatars}`, `/config/`, `/migrations/`
- Empty `routes.php`. `.gitignore` excludes `.env`, `vendor/`, `/public/uploads/*` content (keep dir with `.gitkeep`)
**Validation:** Folder tree matches spec exactly; `.gitignore` blocks listed paths.

## STEP-02 — Composer Autoload and Single Dependency
**Objective:** PSR-4 autoload + only allowed runtime dep.
**Outputs:** `composer.json` with `"autoload":{"psr-4":{"App\\":"app/"}}` and `"require":{"vlucas/phpdotenv":"^5.5"}`; run `composer install`.
**Validation:** `composer show` returns exactly one runtime package. No other deps.

## STEP-03 — .env Template and Constants File
**Objective:** Encode env vars and app constants.
**Outputs:**
- `.env` template (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_CHARSET, BCRYPT_COST=12, SESSION_TIMEOUT=7200, SESSION_NAME=traceon_session, APP_ENV=development, APP_URL, APP_DEBUG=false, UPLOAD_MAX_SIZE=2097152, UPLOAD_ALLOWED_TYPES, UPLOAD_AVATAR_DIR)
- `app/Config/constants.php` sourcing from `$_ENV`
**Validation:** No literal credentials anywhere; `git status` shows `.env` ignored.

## STEP-04 — Database Schema Migration
**Objective:** Create all 7 tables from schema (see context-kernel.md schema section).
**Outputs:** `migrations/0001_init.sql` — all CREATE TABLE statements verbatim.
**Risks:** Forgetting utf8mb4; wrong FK delete behavior.
**Validation:** `SHOW CREATE TABLE` for each table matches spec character-for-character. Insert sample rows verifying CASCADE/RESTRICT/SET NULL semantics.
**Tables:** users, workspaces, workspace_members, cards, card_access, todos, activities, login_attempts.

## STEP-05 — Front Controller and .htaccess
**Objective:** Make `/public` doc root; route non-file requests through `index.php`.
**Outputs:**
- `/public/index.php`: PHP version guard (≥8.3.0), autoload require, dotenv load, router instantiate, route registration include, dispatch call
- `/public/.htaccess`: RewriteEngine On; RewriteCond !-f; RewriteCond !-d; RewriteRule ^(.*)$ index.php [QSA,L]
- `/public/uploads/avatars/.htaccess`: `php_flag engine off`, `Options -ExecCGI`, deny script handlers
**Validation:** Non-file URL hits index.php; `/public/css/tokens.css` (after STEP-13) serves CSS without entering index.php.

## STEP-06 — Core Database Singleton
**Objective:** Implement `App\Core\Database::getInstance()`.
**Outputs:** `app/Core/Database.php` with ATTR_ERRMODE=EXCEPTION, EMULATE_PREPARES=false, ATTR_PERSISTENT=true, FETCH_ASSOC.
**Validation:** getInstance() twice returns same object (===); `SELECT 1` succeeds.

## STEP-07 — Request, Response, Session, CsrfManager
**Objective:** Implement Core service classes.
**Outputs:**
- `Request.php`: path, method, _method override, JSON body, $_POST access, file access, client IP (REMOTE_ADDR only — never X-Forwarded-For)
- `Response.php`: `json($payload, $status)`, `redirect($path)`
- `Session.php`: `start()` with hardened ini (HttpOnly, Secure, SameSite=Strict, gc_maxlifetime), `destroy()`, `regenerate()`, `checkIdle($timeout)`
- `CsrfManager.php`: `generate()` idempotent within session; `validate()` with hash_equals; `rotate()` only on login/logout
**Validation:** Unit tests: generate idempotent; validate true on match, false on mismatch; rotate replaces token.

## STEP-08 — Router with Method Override
**Objective:** Implement `App\Core\Router`.
**Outputs:** `app/Core/Router.php` with `get()`, `post()`, `dispatch()`. Path patterns support `{id}` (strict regex `[0-9]+`). Dispatch reads `_method` from POST body; `{PATCH,DELETE}` → routing key. 404 returns JSON envelope for `/api/*` paths, rendered 404 page for others. Security headers emitted before dispatch.
**Validation:** Hit `/login` (stub) → 501. Hit `/api/unknown` → 404 JSON. POST with `_method=DELETE` hits DELETE-registered action.

## STEP-09 — BaseController Skeleton
**Objective:** Implement `App\Controllers\BaseController`.
**Outputs:** `requireAuth()`, `requireWorkspaceMember($workspaceId, $minRole='Member')` (live DB check), `json($data, $status)`, `render($view, $data)`. MemberModel can be a placeholder returning null until STEP-19 replaces it.
**Risks:** Caching membership — must NEVER happen (INV-08).
**Validation:** `requireWorkspaceMember` in placeholder controller → 403 (no membership exists). Unauthenticated request → redirect /login.

## STEP-10 — Security Headers and Bootstrap Order
**Objective:** Centralize security headers; confirm bootstrap order.
**Outputs:** Headers emitted at top of Router dispatch:
```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: blob:; connect-src 'self'; form-action 'self'; frame-ancestors 'none'
```
Bootstrap order in index.php: version guard → autoload → dotenv → session start → router instantiate → routes include → dispatch.
**Validation:** `curl -I /` shows all four headers. No "headers already sent" warning.

## STEP-11 — routes.php Full Registration (501 stubs)
**Objective:** Register every route to a stub returning 501.
**Outputs:** `/routes.php` per route table in context-kernel.md. Every action = stub returning `{"success":false,"error":"NOT_IMPLEMENTED","message":""}` with HTTP 501.
**Validation:** Every path returns 501; total count equals spec route list.

## STEP-12 — constants.php and activity_templates.php
**Objective:** Encode constants and all 17 activity templates.
**Outputs:**
- `app/Config/constants.php` (bcrypt cost, timeouts, upload limits from $_ENV)
- `app/Config/activity_templates.php` — paste verbatim from `.session-context/activity_templates.php` (all 17 keys)
**Validation:** Every `activity_type` key used by future ActivityLogger exists; all keys match `^[a-z_]+$`.

## STEP-13 — Design Token CSS
**Objective:** Implement `tokens.css` per spec §7.1.
**Outputs:** `/public/css/tokens.css` — paste verbatim from `.session-context/design-tokens.css`.
**Validation:** Grep across `/public/css/*` for `#[0-9a-fA-F]{3,6}` outside tokens.css returns zero matches.

## STEP-14 — Base, Component, Layout CSS Scaffolding
**Objective:** Set up empty CSS files with section header comments.
**Outputs:** `base.css`, `components.css`, `layouts.css` — created with section comments; linked from `<head>` of layouts.
**Validation:** Page source shows three stylesheets linked, all 200 in network panel.

## STEP-15 — Global Fetch Wrapper api.js
**Objective:** Implement canonical fetch wrapper.
**Outputs:** `/public/js/modules/api.js` exporting:
- `apiPost(path, body)`: reads `<meta name="csrf-token">`, attaches `csrf_token` + `_method`, parses envelope, throws `{code, message, status}` on failure
- `apiGet(path, params)`: GET with params, parses envelope
- NETWORK_ERROR fallback: `{code:'NETWORK_ERROR', message:'Koneksi terputus. Coba refresh halaman.', status:0}`
**Validation:** Failed mutation (CSRF stripped) → 403; api.js throws with `code='FORBIDDEN'`.

## STEP-16 — Healthcheck Endpoint
**Objective:** Provide `/healthz`.
**Outputs:** Route registered returning 200 text/plain `OK`.
**Validation:** `curl /healthz` returns 200 within 100ms.

---

## PHASE-0 Definition of Done (ALL must pass)

1. `php -v` ≥ 8.3.0 confirmed by index.php version guard
2. `composer install` clean; `composer show` returns exactly one runtime package
3. Hitting `/` returns HTML with all four security headers (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Content-Security-Policy)
4. Unknown URL returns 404 from Router (not Apache)
5. All 7 tables exist with ENGINE=InnoDB, utf8mb4 charset, FKs, UNIQUE constraints, INDEXes — verified by SHOW CREATE TABLE
6. Database::getInstance() returns same PDO across calls; ATTR_EMULATE_PREPARES=false; ATTR_ERRMODE=EXCEPTION
7. Session cookies carry HttpOnly, Secure (prod), SameSite=Strict; session name = SESSION_NAME env var
8. CSRF meta tag present on rendered pages; CsrfManager::generate() returns identical token across calls within session
9. tokens.css contains complete §7.1 variable set; no hex colors used anywhere else
10. /healthz returns 200 within 100ms
11. Static checks: no SQL in Views/Controllers/Core; no mysqli_* calls; no eval; no extract; no inline <script>/<style> blocks in views
