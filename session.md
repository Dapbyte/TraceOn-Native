# TraceOn — Session Execution Plan

Detailed per-session breakdown. Each session = one focused chunk. Load only listed inputs to minimize tokens.

---

## Session 0 — Reference Kernel Extraction ✅ DONE

**Status:** Complete (already executed).

**What was done:**
- Read `DESIGN.md`, `prompt-traceOn.md`, `detail-plan.md` (4639 lines, 438 KB).
- Extracted canonical reference into `.session-context/` directory.
- Wrote 10 files totaling ~68 KB (87% reduction vs source).

**Files produced:**
```
.session-context/
├── context-kernel.md       Global canon, schema, routes, INV/RULE, auth matrix, STEP index, endpoint checklist
├── design-tokens.css       Verbatim tokens.css ready-to-paste
├── activity_templates.php  All 17 templates ready-to-paste PHP array
├── sec-checklist.md        SEC-01..25 deploy gate
└── phase-cards/
    ├── PHASE-0.md          STEP-01..16 detail + DoD
    ├── PHASE-1.md          STEP-17..22 detail + DoD
    ├── PHASE-2.md          STEP-23..27 detail + DoD
    ├── PHASE-3.md          STEP-28..30 detail + DoD
    ├── PHASE-4.md          STEP-31..33 detail + DoD
    └── PHASE-5.md          STEP-34 detail + DoD
```

**No code written this session. Pure reference compression.**

---

## Session 1 — PHASE-0 Bootstrap (STEP-01..16)

**Goal:** Empty app boots. No features yet. Security headers active. All 7 DB tables exist. Design tokens live.

**Load (only these):**
- `.session-context/context-kernel.md`
- `.session-context/phase-cards/PHASE-0.md`
- `.session-context/design-tokens.css`
- `.session-context/activity_templates.php`
- `.session-context/sec-checklist.md`

**Do NOT load:** `detail-plan.md`, `prompt-traceOn.md`, `DESIGN.md`.

### What I will do (in order)

1. **STEP-01 Create folder skeleton.**
   - Make dirs: `/app/{Core,Controllers,Models,Views/{layouts,partials,pages},Helpers,Config}`, `/public/{css,js/modules,uploads/avatars}`, `/config/`, `/migrations/`.
   - Create empty `routes.php`.
   - Write `.gitignore` blocking `.env`, `vendor/`, `/public/uploads/*` content.
   - Add `.gitkeep` in `uploads/avatars/`.

2. **STEP-02 Composer setup.**
   - Write `composer.json` with PSR-4 `App\\` → `app/` and require `vlucas/phpdotenv ^5.5`.
   - Run `composer install`.
   - Verify only one runtime dep.

3. **STEP-03 Env + constants.**
   - Write `.env.example` template (all keys from kernel).
   - Copy to `.env` with dev values.
   - Write `app/Config/constants.php` sourcing from `$_ENV`.

4. **STEP-04 DB schema migration.**
   - Write `migrations/0001_init.sql` — all 7 CREATE TABLE statements verbatim from kernel.
   - Apply: `mysql -u root traceon < migrations/0001_init.sql`.
   - Verify with `SHOW CREATE TABLE` per table.

5. **STEP-05 Front controller + .htaccess.**
   - Write `public/index.php`: version guard ≥8.3.0, autoload, dotenv load, router stub.
   - Write `public/.htaccess`: rewrite non-file/non-dir to `index.php`.
   - Write `public/uploads/avatars/.htaccess`: `php_flag engine off`, `Options -ExecCGI`.

6. **STEP-06 Database singleton.**
   - Write `app/Core/Database.php` PDO singleton (ERRMODE_EXCEPTION, EMULATE_PREPARES=false, PERSISTENT=true, FETCH_ASSOC).

7. **STEP-07 Core services.**
   - Write `app/Core/Request.php` (path, method, _method override, JSON body, IP via REMOTE_ADDR only).
   - Write `app/Core/Response.php` (`json($data, $status)`, `redirect($path)`).
   - Write `app/Core/Session.php` (hardened ini: HttpOnly, Secure prod, SameSite=Strict, gc_maxlifetime; `start()`, `destroy()`, `regenerate()`, `checkIdle()`).
   - Write `app/Core/CsrfManager.php` (`generate()` idempotent, `validate()` with hash_equals, `rotate()` for login/logout only).

8. **STEP-08 Router.**
   - Write `app/Core/Router.php`: `get()`, `post()`, `dispatch()`. `{id}` regex `[0-9]+`. `_method` PATCH/DELETE override. 404 JSON for `/api/*`, HTML 404 for pages. Security headers emitted before dispatch.

9. **STEP-09 BaseController skeleton.**
   - Write `app/Controllers/BaseController.php`: `requireAuth()`, `requireWorkspaceMember()` (placeholder returning null until STEP-19), `json()`, `render()`.

10. **STEP-10 Security headers + bootstrap order.**
    - In Router dispatch, emit: X-Frame-Options:DENY, X-Content-Type-Options:nosniff, Referrer-Policy:strict-origin-when-cross-origin, full CSP.
    - Confirm `index.php` order: version → autoload → dotenv → session → router → routes → dispatch.

11. **STEP-11 Routes registration.**
    - Write `routes.php`: every route from kernel route table mapped to stub returning 501 NOT_IMPLEMENTED.

12. **STEP-12 Config files.**
    - Write `app/Config/constants.php` (BCRYPT_COST, SESSION_TIMEOUT, etc. from $_ENV).
    - Write `app/Config/activity_templates.php` (paste verbatim from `.session-context/activity_templates.php`).

13. **STEP-13 Design tokens CSS.**
    - Write `public/css/tokens.css` (paste verbatim from `.session-context/design-tokens.css`).

14. **STEP-14 CSS scaffolding.**
    - Write `public/css/base.css`, `components.css`, `layouts.css` with section header comments only.
    - Link all three from layouts `<head>` (added in PHASE-1).

15. **STEP-15 api.js fetch wrapper.**
    - Write `public/js/modules/api.js`: `apiPost(path, body)`, `apiGet(path, params)` reading CSRF meta tag, attaching `_method`, parsing envelope, throwing structured errors, NETWORK_ERROR fallback.

16. **STEP-16 Healthcheck.**
    - Add route `/healthz` returning 200 text/plain `OK`.

### Validation gate (PHASE-0 DoD, all must pass)
- `php -v` ≥ 8.3.0
- `composer show` → 1 runtime package
- `/` returns HTML with 4 security headers
- Unknown URL → 404 from Router
- 7 tables exist with correct ENGINE/charset/FK/UNIQUE/INDEX
- `Database::getInstance()` returns same PDO across calls
- Session cookies carry HttpOnly + SameSite=Strict
- `tokens.css` complete; no hex outside it
- `/healthz` 200 within 100ms

### Hand-off
End with commit: `PHASE-0: bootstrap complete (STEP-01..16)`.

---

## Session 2 — PHASE-1 Auth + Profile (STEP-17..22)

**Goal:** User register, login (rate-limited), logout, profile + avatar.

**Load:** `context-kernel.md` + `phase-cards/PHASE-1.md`. Repo state from Session 1.

### What I will do

1. **STEP-17 UserModel.**
   - Methods: `findById`, `findByEmail`, `existsByEmail`, `create`, `updateName`, `updateAvatar`. All PDO prepared.

2. **STEP-18 LoginAttemptModel.**
   - Methods: `findActiveBlock($ip)`, `registerFailure($ip, $type)`, `reset($ip)`, `purgeExpiredBlocks()`.
   - Threshold: 5 fails / 15min for login; 3 fails / 30s for join (RULE-33).

3. **STEP-19 MemberModel + complete BaseController.**
   - Methods: `getMembership`, `isApproved`, `createPending`, `reopenRequest`, `approve`, `reject`, `delete`, `updateRole`, `rejectAllPending`, `listForWorkspace`.
   - Replace BaseController placeholder with real live-DB getMembership lookup.

4. **STEP-20 AuthController.**
   - `showLogin`, `showRegister`, `redirectDashboard`, `login`, `register`, `logout`.
   - Login flow: IP check → lazy cleanup → password_verify → on success: reset attempts + `session_regenerate_id(true)` + set session vars + `CsrfManager::rotate()` + 200 redirect.
   - Register: validate name/email/password (min 8, ≥1 letter ≥1 digit) + confirm match + honeypot `website` field silent reject + password_hash BCRYPT cost 12.
   - Logout: unset + destroy + clear cookie + rotate CSRF + 302 to /login.
   - Idle check in BaseController::requireAuth via $_SESSION['last_activity'].

5. **STEP-21 ProfileController + FileUploadHelper.**
   - `FileUploadHelper::saveAvatar()`: error check, 2MB cap, `mime_content_type` allowlist, `getimagesize` parse, `bin2hex(random_bytes(16))` filename + verified extension, `move_uploaded_file`.
   - `FileUploadHelper::deleteAvatar()`: unlink old file.
   - `ProfileController@show` + `@update`: name validation, avatar swap, session sync.

6. **STEP-22 Auth pages + layouts.**
   - `Views/layouts/auth.php`: login/register shell + CSRF meta + Google Fonts + Iconify CDN.
   - `Views/layouts/main.php`: sidebar + content shell (sidebar partial empty until STEP-25).
   - `Views/pages/login.php`, `register.php` (with honeypot), `profile.php`.
   - JS: `toast.js`, `modal.js`, minimal `sidebar.js`. Wire apiPost for auth flows.

### Validation gate (PHASE-1 DoD)
- Register invalid email/weak pass/dup email/honeypot all return correct codes
- Login: 5 fails → 429 with 15-min countdown; success regenerates session + rotates CSRF
- Logout invalidates cookie; back-button → /login
- Idle expiry → /login?reason=expired
- Profile: avatar 2MB cap, MIME + GD double-check, random filename, old removed
- CSP + security headers on every page

### Hand-off
Commit: `PHASE-1: auth and profile complete (STEP-17..22)`.

---

## Session 3 — PHASE-2 Workspace + Membership (STEP-23..27)

**Goal:** Workspace CRUD + invite codes + join/approve/reject/role/kick + sidebar SSR.

**Load:** `context-kernel.md` + `phase-cards/PHASE-2.md`. Repo state from Session 2.

### What I will do

1. **STEP-23 WorkspaceModel.**
   - Methods: `findById`, `findByInviteCode`, `insert`, `rename`, `updateDeadline`, `updateInviteCode`, `delete`, `listOwned`, `listJoined`, `getInviteCode`, `generateUniqueInviteCode` (max 3 retries with usleep(1000), throw on exhaust).

2. **STEP-24 WorkspaceController (no membership ops yet).**
   - `dashboard`: list owned + joined, render dashboard page.
   - `show($id)`: requireAuth + requireWorkspaceMember, render workspace page (Anggota tab + scaffolding only).
   - `create`: TX: generateUniqueInviteCode + INSERT workspaces + INSERT workspace_members (Owner, Approved, approved_at=NOW()).
   - `shareCode` (Admin+): GET, return invite_code.
   - `regenerateCode` (Owner): TX: new code + UPDATE workspaces + rejectAllPending + log `invite_regenerate`.
   - `rename` (Owner): validate 3-100, UPDATE, log `workspace_rename`.
   - `updateDeadline` (Owner): DateTime validate, UPDATE.
   - `delete` (Owner): server-side name_confirm check, TX: DELETE (CASCADE handles rest).

3. **STEP-25 Sidebar SSR.**
   - `Views/partials/sidebar.php`: logo, "+ Workspace Baru", "+ Join Workspace", accordion "Dibagikan" (joined), accordion "Workspace" (owned), profile footer.
   - `js/modules/sidebar.js`: load accordion state from sessionStorage (key `traceon_accordion_state`), collapse 260px↔72px transition, mobile hamburger overlay.

4. **STEP-26 Membership endpoints.**
   - `WorkspaceController@joinRequest`: normalize code via `strtoupper(preg_replace('/[^A-Z0-9]/i','',trim($input)))`, find workspace, check existing membership (409), IP cooldown (3 fails/30s → 429), INSERT Pending, log `member_join`.
   - `WorkspaceController@approveRequest`: action=approve → TX: UPDATE Approved + approved_at + log; action=reject → UPDATE Rejected + log (no delete).
   - `MemberController@updateRole`: Owner/Admin can change Member↔Admin; no one touches Owner; no promote-to-Owner; no self-change. UPDATE + log `role_change`.
   - `MemberController@kick`: target not Owner (403); TX: DELETE workspace_members + DELETE card_access for user in workspace + log `member_kick`.

5. **STEP-27 Members tab + Settings panel.**
   - Members tab table: Avatar | Name | Role (dropdown) | Status (badge) | Actions. Per-role action matrix from kernel.
   - Settings panel (Owner): Rename, Deadline, Invite code (readonly + Salin + Regenerate), Danger Zone (Hapus Workspace).
   - Settings panel (Admin): Invite code (readonly + Salin only).
   - `Views/partials/modal-confirm.php`: type-to-confirm destructive modal.

### Validation gate (PHASE-2 DoD)
- Create-workspace atomic; collision retry succeeds
- Sidebar SSR correct; sessionStorage accordion persists
- Join 404/409/429 paths correct
- Approve/reject leaves row (no delete on reject)
- Role rules server-enforced (not UI only)
- Kick TX deletes membership + card_access + logs
- Regenerate TX rejects all Pending
- Workspace delete: server-side name_confirm match
- **Kick test:** kick user mid-session → next request → 403 (proves no cached membership)

### Hand-off
Commit: `PHASE-2: workspace and membership complete (STEP-23..27)`.

---

## Session 4 — PHASE-3 Cards + Card Access (STEP-28..30)

**Goal:** Card grid with explicit access grants. Read-only transparency for Members without access.

**Load:** `context-kernel.md` + `phase-cards/PHASE-3.md`. Repo state from Session 3.

### What I will do

1. **STEP-28 CardModel.**
   - Methods: `findById`, `listForWorkspace`, `create`, `update`, `delete`, `insertAccess`, `deleteAccess`, `accessUserIds`, `deleteAccessForUserInWorkspace`, `userHasAccess`, `getWorkspaceId`.

2. **STEP-29 CardController.**
   - `create` (Owner/Admin): validate title 3-100 + deadline, INSERT, log `card_create`.
   - `update` (Owner/Admin): XWS check (card.workspace_id === auth workspace), UPDATE, log `card_edit`.
   - `delete` (Owner/Admin): XWS check, DELETE (CASCADE removes todos + card_access), log `card_delete`.
   - `grantAccess` (Owner/Admin): XWS check, verify target is Approved member, INSERT card_access (409 on UNIQUE collision), log `access_grant`.
   - `revokeAccess` (Owner/Admin): XWS check, TX: DELETE card_access + log `access_revoke`; idempotent.
   - XWS pattern applied to every method.

3. **STEP-30 Card grid UI.**
   - In `pages/workspace.php`: CSS Grid breakpoints (4/3/2/1 col at 1280/1024/640/<640).
   - Card partial: title (ellipsis), progress bar (8px), "N/M selesai" ratio, deadline badge (warning if <3 days), member avatars max 3 + "+N" overflow, ellipsis (⋮).
   - Read-only indicator: "Hanya Baca" badge for Member without card_access. CRUD buttons ABSENT from HTML entirely.
   - Ellipsis menu desktop: dropdown (Edit Card, Delete Card, Kelola Akses).
   - Ellipsis menu mobile: bottom sheet.
   - `js/modules/card.js`: `initCardGrid()`, `updateProgressBar(cardId, progress)` (stub for Phase 4 usage).

### Validation gate (PHASE-3 DoD)
- Owner/Admin only for CRUD; Member without rights → 403
- XWS: forge card from another workspace → 403
- Read-only transparency: no CRUD buttons in HTML for Members without access; backend rejects forged calls
- Grant: 409 on dup; revoke idempotent
- Delete cascades todos + card_access; activities.card_id survives
- Grid degrades to 1 col at <640px

### Hand-off
Commit: `PHASE-3: cards and card access complete (STEP-28..30)`.

---

## Session 5 — PHASE-4 Todos + Progress (STEP-31..33)

**Goal:** Todo CRUD with status + live progress recalc + inline delete confirm (no native confirm()).

**Load:** `context-kernel.md` + `phase-cards/PHASE-4.md`. Repo state from Session 4.

### What I will do

1. **STEP-31 TodoModel + TodoController + complete ProgressCalculator.**
   - TodoModel: `findById`, `listForCard`, `create`, `updateTitle`, `updateStatus`, `delete` (hard), `getCardId`.
   - ProgressCalculator::forCard: single atomic `SELECT COUNT(*) total, SUM(CASE WHEN status='done' THEN 1 ELSE 0 END) done`; return 0 if total=0; else `round(done/total*100)`.
   - ProgressCalculator::forWorkspace: avg of forCard across cards; return 0.0 if no cards.
   - TodoController@create: XWS + card-access auth (Owner/Admin/`card_access` row), INSERT, recalc progress, log `todo_create`, return progress_card.
   - TodoController@update: validate enum status; UPDATE; recalc; log `todo_edit` or `todo_status`.
   - TodoController@delete: hard DELETE; recalc; log `todo_delete`.

2. **STEP-32 Todo UI.**
   - Todo list in card detail panel/modal (within workspace page).
   - URL state: open → `history.pushState(null,'','/workspace/{id}/card/{cid}')`; close → restore.
   - Item states: pending (default), in_progress ("Sedang" amber badge), done (strikethrough + opacity 60% + filled checkbox).
   - Custom checkbox: 18×18px, scale(0.1→1) 150ms.
   - **Inline delete confirm (RULE-11 — no native `confirm()`):** trash → `[✓ Hapus]` + `[✗ Batal]`, BG `rgba(198,40,40,0.06)`, Escape cancels, 5s auto-cancel, on confirm: `opacity:0` 250ms → remove on transitionend.
   - Status `<select>`: change → POST /api/todo/update → updateProgressBar.
   - Filter buttons "Semua/Selesai/Dalam Proses/Belum": client-side DOM only, no fetch.
   - Enter key submits new-todo form.
   - Double-submit safety: disable button during in-flight.
   - `js/modules/todo.js`: `initTodoList(cardId)`, `handleTodoDeleteClick(el, id)`.

3. **STEP-33 Test harness (optional but recommended).**
   - `phpunit.xml` with test DB config.
   - `tests/bootstrap.php` fails fast unless `APP_ENV=testing`.
   - Fixture builders for User/Workspace/Member/Card/Todo.
   - Unit tests: ProgressCalculator (0/partial/100 cases), CsrfManager (generate/validate/rotate), FileUploadHelper (bad MIME, oversize, valid), validators.

### Validation gate (PHASE-4 DoD)
- Todo CRUD: Owner/Admin/card_access only; others → 403
- XWS on every endpoint
- Hard delete: no deleted_at; subsequent read → 404
- Inline confirm: Escape cancels, 5s auto-cancel, transitionend remove. Native `confirm()` ABSENT.
- progress_card payload matches fresh `ProgressCalculator::forCard`
- Status select fires PATCH + updates bar without reload
- Filter buttons client-side only (no fetches)
- Enter submits new-todo
- Double-submit blocked

### Hand-off
Commit: `PHASE-4: todos and progress complete (STEP-31..33)`.

---

## Session 6 — PHASE-5 Activity Log + Final Hardening (STEP-34)

**Goal:** Activity log UI (SSR + Load More + debounced search + filter + Owner clear). Final UX polish. SEC-01..25 checklist pass.

**Load:** `context-kernel.md` + `phase-cards/PHASE-5.md` + `sec-checklist.md`. Repo state from Session 5.

### What I will do

1. **ActivityModel.**
   - `insert`, `list($wid, $limit=50, $offset=0)`, `search($wid, $query, $limit, $offset)` (FULLTEXT for ≥3 chars BOOLEAN MODE; LIKE fallback for shorter; both parameterized), `listFiltered($wid, $filters, $limit, $offset)`, `count`, `deleteAll($wid)`.

2. **Complete ActivityLogger.**
   - `log()` INSERT only. Caller owns transaction (NEVER opens own TX).

3. **ActivityController.**
   - `fetch`: GET, accepts workspace_id + offset + limit + search + filter_type[] + date_from + date_to + user_id. Return `{data:[], meta:{offset,limit,has_more}}`.
   - `clear` (Owner): TX: deleteAll + log `log_clear`.

4. **Activity Tab UI.**
   - In `pages/workspace.php`: lazy load on first click, cache in JS memory.
   - Search input + Cari + Refresh + filter popup trigger + activity feed + "Muat Lebih Banyak" + Owner-only "Hapus Semua Log".
   - Activity row: avatar 32px + name (bold) + action text + relative timestamp + activity_type badge.
   - SSR initial render: last 50 sorted DESC by created_at.

5. **activity.js.**
   - `initActivitySearch(wid)`: 500ms debounce, REPLACES content (not append), shows "Tidak ada hasil untuk '{query}'" empty state with clear-search link.
   - `loadMore(wid, offset)`: APPENDS results, hides button when `has_more=false`.
   - `refreshActivity(wid)`: AJAX fetch, REPLACES content (no full page reload).
   - `formatRelative(iso)`: exact thresholds — `<1min`→"Baru saja"; `1-59m`→"N menit lalu"; `1-23h`→"N jam lalu"; `1-6d`→"N hari lalu"; `≥7d`→"DD MMM YYYY HH:mm".

6. **Filter popup.**
   - JS-only state object (not persisted). Multi-select activity_type, date_from/to, user dropdown. Default: last 7 days, all types, all users.

7. **Skeleton + empty states.**
   - Apply `.skeleton-shimmer` to card grid first paint.
   - Empty states per spec §6.7 matrix: Dashboard (no workspaces), Workspace (no cards), Activity (empty), Search (no match), Members (no pending).

8. **Toast system.**
   - Position `fixed bottom:24px right:24px z-index:var(--z-toast)`.
   - Animation `toastSlideIn 0.35s cubic-bezier(0.16,1,0.3,1)`.
   - Auto-dismiss: Success/Info 4s, Warning 6s, Error 8s.
   - Max 5; new on top; old shift down.
   - Retry toast with "Coba Lagi" link.

9. **Full responsive pass.**
   - Test 320/640/1024/1280/1440px.
   - Touch targets ≥44×44px.
   - Mobile: sidebar hidden + hamburger overlay; card detail full-screen modal; context menu bottom sheet; `scrollIntoView` on input focus; `env(safe-area-inset-bottom)` on bottom fixed.
   - Title/breadcrumb overflow: ellipsis.

10. **Modal system final.**
    - Z-index: sidebar 100, nav 200, backdrop 300, modal 400, toast 500.
    - Backdrop fadeIn 0.2s; content scale(0.95→1) + translateY(10→0) + opacity 0.3s.
    - ESC + backdrop click close. Tab focus trap.

11. **SEC-01..25 checklist pass.**
    - Walk every item in `sec-checklist.md` on staging.
    - Fix any failure before deploy sign-off.

### Validation gate (PHASE-5 DoD)
- SSR last 50; Load More returns next 50; hides on has_more=false
- Search 500ms debounce; FULLTEXT ≥3 char; LIKE fallback; replaces content
- Filter popup state in JS; default last 7 days
- Owner-only clear: TX deletes all + inserts `log_clear`
- Relative timestamp thresholds exact
- SEC-01..25 all pass
- No horizontal overflow at any breakpoint
- Manual E2E: register → login → workspace → invite → approve → card → grant → todos → complete → progress 100% → activity log → clear → delete workspace

### Hand-off
Commit: `PHASE-5: activity log and hardening complete (STEP-34) — ready for deploy`.

---

## Cross-Session Discipline

- **No skipping STEPs.** Blocker → fix blocker; don't jump ahead.
- **No new deps.** Only `vlucas/phpdotenv` runtime + `phpunit` dev.
- **One commit per STEP** with `STEP-##` in subject; one merge per PHASE.
- **Activity log inside TX** for every templated op (RULE-17).
- **Names mandatory.** Don't rename controllers/models/helpers.
- **Validation gate is binding.** Phase N+1 starts only when phase N DoD objectively passes.
- **Per-endpoint checklist** (in kernel) on every controller action before marking done.
- **Per-change checklist** every edit (in kernel §Validation Checklist).
