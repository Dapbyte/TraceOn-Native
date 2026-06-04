# TraceOn — Context Kernel
> Load this file instead of source docs. ~40KB vs 510KB source.
> Source authority: `prompt-traceOn.md` (hard facts: schema, routes, tokens); `detail-plan.md` (decisions, INVs, RULEs).

---

## GLOBAL CANON

**Stack (frozen):** PHP 8.3 native OOP MVC · MySQL 8 InnoDB · PDO prepared statements ONLY · Vanilla JS ES Modules · CSS Custom Properties · SSR initial loads + Fetch API mutations. Only runtime Composer dep: `vlucas/phpdotenv ^5.5`. `phpunit` dev-only.

**Layering (one-way, top→down only):**
```
public/index.php → App\Core\Router → App\Controllers\* → App\Models\* / App\Helpers\* → App\Core\Database → MySQL
```
Cross-cutting Core services (Request, Response, Session, CsrfManager) used BY Controllers only.
Views rendered BY Controllers; read pre-fetched data ONLY. Views NEVER query DB.

**Forbidden:**
- Models referencing Controllers
- Views calling Models for fetching / querying DB
- SQL outside `Models/` + `Helpers/`
- String-concatenated SQL
- New frameworks/libraries beyond `vlucas/phpdotenv`

**Authorization canon:**
- Membership + role fetched LIVE from DB per request, NEVER from `$_SESSION`
- `workspace_members.status = 'Approved'` required for any workspace access
- Todo CRUD: Owner OR Admin OR `card_access` row exists for that card
- Cross-workspace check on EVERY card/todo op: `card.workspace_id == authorized workspace`
- CSRF: session-scoped, rotated ONLY on login and logout
- Method override: mutations = `POST` + `_method=PATCH|DELETE`

**Canonical vocabulary:**
- Roles (descending privilege): Owner > Admin > Member
- Membership status: Pending | Approved | Rejected
- Todo status: pending | in_progress | done
- Tables: `users`, `workspaces`, `workspace_members`, `cards`, `card_access`, `todos`, `activities`, `login_attempts`

---

## ROUTE TABLE (routes.php — full list)

```php
// Web pages
$router->get('/',                       'AuthController@redirectDashboard');
$router->get('/login',                  'AuthController@showLogin');
$router->get('/register',               'AuthController@showRegister');
$router->get('/dashboard',              'WorkspaceController@dashboard');
$router->get('/workspace/{id}',         'WorkspaceController@show');
$router->get('/profile',                'ProfileController@show');

// API mutations
$router->post('/api/auth/login',                  'AuthController@login');
$router->post('/api/auth/register',               'AuthController@register');
$router->post('/api/auth/logout',                 'AuthController@logout');
$router->post('/api/workspace/create',            'WorkspaceController@create');
$router->get('/api/workspace/share',              'WorkspaceController@shareCode');
$router->post('/api/workspace/regenerate-code',   'WorkspaceController@regenerateCode');
$router->post('/api/workspace/delete',            'WorkspaceController@delete');       // _method=DELETE
$router->post('/api/workspace/rename',            'WorkspaceController@rename');       // _method=PATCH
$router->post('/api/workspace/update-deadline',   'WorkspaceController@updateDeadline');
$router->post('/api/workspace/join-request',      'WorkspaceController@joinRequest');
$router->post('/api/workspace/approve-request',   'WorkspaceController@approveRequest');
$router->post('/api/member/role-update',          'MemberController@updateRole');
$router->post('/api/member/kick',                 'MemberController@kick');             // _method=DELETE
$router->post('/api/card/create',                 'CardController@create');
$router->post('/api/card/update',                 'CardController@update');             // _method=PATCH
$router->post('/api/card/delete',                 'CardController@delete');             // _method=DELETE
$router->post('/api/card/access/grant',           'CardController@grantAccess');
$router->post('/api/card/access/revoke',          'CardController@revokeAccess');       // _method=DELETE
$router->post('/api/todo/create',                 'TodoController@create');
$router->post('/api/todo/update',                 'TodoController@update');             // _method=PATCH
$router->post('/api/todo/delete',                 'TodoController@delete');             // _method=DELETE
$router->get('/api/activity/fetch',               'ActivityController@fetch');
$router->post('/api/activity/clear',              'ActivityController@clear');          // _method=DELETE
$router->post('/api/profile/update',              'ProfileController@update');
```

---

## DATABASE SCHEMA (verbatim — 7 tables)

```sql
CREATE TABLE users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  email       VARCHAR(100) NOT NULL,
  password    VARCHAR(255) NOT NULL,
  avatar_path VARCHAR(255) NULL DEFAULT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE workspaces (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  deadline    DATE NULL DEFAULT NULL,
  invite_code VARCHAR(10) NOT NULL,
  owner_id    INT NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_invite_code (invite_code),
  CONSTRAINT fk_workspace_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE workspace_members (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  workspace_id  INT NOT NULL,
  user_id       INT NOT NULL,
  role          ENUM('Owner','Admin','Member') NOT NULL,
  status        ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  requested_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  approved_at   TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY uq_workspace_user (workspace_id, user_id),
  CONSTRAINT fk_wm_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_wm_user      FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE,
  INDEX idx_wm_workspace_id (workspace_id),
  INDEX idx_wm_user_id      (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cards (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  workspace_id  INT NOT NULL,
  title         VARCHAR(100) NOT NULL,
  deadline      DATE NULL DEFAULT NULL,
  created_by    INT NULL DEFAULT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_card_workspace  FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_card_creator    FOREIGN KEY (created_by)   REFERENCES users(id)      ON DELETE SET NULL,
  INDEX idx_card_workspace_id (workspace_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE card_access (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  card_id     INT NOT NULL,
  user_id     INT NOT NULL,
  granted_by  INT NULL DEFAULT NULL,
  granted_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_card_user (card_id, user_id),
  CONSTRAINT fk_ca_card       FOREIGN KEY (card_id)    REFERENCES cards(id) ON DELETE CASCADE,
  CONSTRAINT fk_ca_user       FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ca_granted_by FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_ca_card_id (card_id),
  INDEX idx_ca_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- No soft delete. Deletion is permanent (hard delete). No deleted_at column.
CREATE TABLE todos (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  card_id     INT NOT NULL,
  title       VARCHAR(255) NOT NULL,
  status      ENUM('pending','in_progress','done') NOT NULL DEFAULT 'pending',
  created_by  INT NULL DEFAULT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_todo_card    FOREIGN KEY (card_id)    REFERENCES cards(id) ON DELETE CASCADE,
  CONSTRAINT fk_todo_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_todo_card_id     (card_id),
  INDEX idx_todo_card_status (card_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE activities (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  workspace_id   INT NOT NULL,
  user_id        INT NULL DEFAULT NULL,
  card_id        INT NULL DEFAULT NULL,   -- NO FK: card may be hard-deleted; kept for historical context
  activity_type  VARCHAR(50) NOT NULL,
  old_value      TEXT NULL DEFAULT NULL,
  new_value      TEXT NULL DEFAULT NULL,
  action         TEXT NOT NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_act_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_act_user      FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE SET NULL,
  INDEX idx_act_workspace_created (workspace_id, created_at),
  INDEX idx_act_user_id           (user_id),
  INDEX idx_act_created_at        (created_at),
  FULLTEXT INDEX ft_act_action    (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE login_attempts (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  ip_address      VARCHAR(45) NOT NULL,
  attempt_count   SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  last_attempt_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  blocked_until   TIMESTAMP NULL DEFAULT NULL,
  INDEX idx_la_ip_address (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Schema notes:** `todos` has no `deleted_at`. `activities.card_id` has NO FK (intentional). `workspace_members.requested_at` not `joined_at`. `login_attempts.attempt_count` is SMALLINT UNSIGNED.

---

## ACTIVITY TEMPLATES (all 17 — activity_type → template)

```
card_create      → '{user} membuat card "{card}"'
card_edit        → '{user} mengubah card "{old}" menjadi "{new}"'
card_delete      → '{user} menghapus card "{card}"'
todo_create      → '{user} menambahkan todo "{todo}" ke card "{card}"'
todo_edit        → '{user} mengubah todo dari "{old}" menjadi "{new}"'
todo_delete      → '{user} menghapus todo "{todo}" dari card "{card}"'
todo_status      → '{user} mengubah status todo "{todo}" dari {old} ke {new}'
member_join      → '{user} bergabung ke workspace'
member_approve   → '{actor} menyetujui permohonan {user}'
member_reject    → '{actor} menolak permohonan {user}'
member_kick      → '{actor} mengeluarkan {user} dari workspace'
role_change      → '{actor} mengubah role {user} dari {old} ke {new}'
access_grant     → '{actor} memberikan akses card "{card}" ke {user}'
access_revoke    → '{actor} mencabut akses card "{card}" dari {user}'
workspace_rename → '{actor} mengubah nama workspace dari "{old}" ke "{new}"'
log_clear        → '{actor} menghapus seluruh log aktivitas'
invite_regenerate→ '{actor} memperbarui kode undangan workspace'
```

---

## TRANSACTION SCOPE TABLE (6 required operations)

| Operation | Steps (all in one TX) |
|---|---|
| Create Workspace | INSERT workspaces + INSERT workspace_members (Owner, Approved, approved_at=NOW()) |
| Delete Workspace | server-side name_confirm check → DELETE workspaces (FK CASCADE handles rest) |
| Kick Member | DELETE workspace_members + DELETE card_access (user_id, cards in workspace) + INSERT activity |
| Approve Join Request | UPDATE workspace_members status=Approved, approved_at=NOW() + INSERT activity |
| Revoke Card Access | DELETE card_access + INSERT activity |
| Regenerate Invite Code | UPDATE workspaces SET invite_code + UPDATE workspace_members SET status='Rejected' WHERE status='Pending' |

---

## API ENDPOINT TABLE

| Method | Path | Auth | Body/Params | Responses |
|---|---|---|---|---|
| POST | /api/auth/login | — | email, password, csrf_token | 200 {redirect} \| 401 \| 429 |
| POST | /api/auth/register | — | name, email, password, confirm_password, website(honeypot), csrf_token | 200 {redirect} \| 422 |
| POST | /api/auth/logout | Auth | csrf_token | 200 {redirect} |
| POST | /api/workspace/create | Auth | name, deadline?, csrf_token | 200 {id, invite_code} \| 422 |
| GET | /api/workspace/share | Owner/Admin | ?workspace_id | 200 {invite_code} \| 403 |
| POST | /api/workspace/regenerate-code | Owner | workspace_id, csrf_token | 200 {new_invite_code} \| 403 |
| POST | /api/workspace/delete | Owner | workspace_id, name_confirm, csrf_token | 200 {redirect} \| 403 |
| POST | /api/workspace/rename | Owner | workspace_id, name, csrf_token | 200 {message} \| 422\|403 |
| POST | /api/workspace/update-deadline | Owner | workspace_id, deadline, csrf_token | 200 {message} \| 422\|403 |
| POST | /api/workspace/join-request | Auth | invite_code, csrf_token | 200 {status:"pending"} \| 404\|409\|429 |
| POST | /api/workspace/approve-request | Owner/Admin | request_id, action(approve\|reject), csrf_token | 200 {message} \| 403 |
| POST | /api/member/role-update | Owner/Admin | workspace_id, user_id, role, csrf_token | 200 {message} \| 403 |
| POST | /api/member/kick | Owner/Admin | workspace_id, user_id, csrf_token | 200 {message} \| 403 |
| POST | /api/card/create | Owner/Admin | workspace_id, title, deadline?, csrf_token | 200 {card_id} \| 403\|422 |
| POST | /api/card/update | Owner/Admin | card_id, title?, deadline?, csrf_token | 200 {message} \| 403\|422 |
| POST | /api/card/delete | Owner/Admin | card_id, csrf_token | 200 {message} \| 403 |
| POST | /api/card/access/grant | Owner/Admin | card_id, user_id, csrf_token | 200 {message} \| 403\|409 |
| POST | /api/card/access/revoke | Owner/Admin | card_id, user_id, csrf_token | 200 {message} \| 403 |
| POST | /api/todo/create | Auth+CardAccess | card_id, title, csrf_token | 200 {todo_id, progress_card} \| 403\|422 |
| POST | /api/todo/update | Auth+CardAccess | todo_id, title?, status?, csrf_token | 200 {success, progress_card} \| 403 |
| POST | /api/todo/delete | Auth+CardAccess | todo_id, csrf_token | 200 {message, progress_card} \| 403\|404 |
| GET | /api/activity/fetch | Auth+Member | ?workspace_id&offset&limit&search&filter_type&date_from&date_to&user_id | 200 {data:[...], meta:{offset,limit,has_more}} |
| POST | /api/activity/clear | Owner | workspace_id, csrf_token | 200 {message} \| 403 |
| POST | /api/profile/update | Auth | name?, avatar_file?, csrf_token | 200 {message, avatar_path?} \| 422 |

**Standard envelope:**
```json
{"success": true, "data": {...}, "message": "..."}
{"success": false, "error": "ERROR_CODE", "message": "Pesan ramah untuk user"}
```

---

## AUTHORIZATION MATRIX

| Action | Owner | Admin | Member w/ card_access | Member no access |
|---|---|---|---|---|
| View workspace | ✓ | ✓ | ✓ | ✓ |
| Create/Edit/Delete card | ✓ | ✓ | ✗ | ✗ |
| Grant/Revoke card access | ✓ | ✓ | ✗ | ✗ |
| Create/Edit/Delete todo | ✓ | ✓ | ✓ | ✗ |
| View card (read-only) | ✓ | ✓ | ✓ | ✓ (UI only, no CRUD) |
| Approve/Reject join | ✓ | ✓ | ✗ | ✗ |
| Change role (Member↔Admin) | ✓ | ✓ | ✗ | ✗ |
| Kick member | ✓ | ✓ | ✗ | ✗ |
| Rename/Delete workspace | ✓ | ✗ | ✗ | ✗ |
| Regenerate invite code | ✓ | ✗ | ✗ | ✗ |
| View invite code | ✓ | ✓ | ✗ | ✗ |
| Clear activity log | ✓ | ✗ | ✗ | ✗ |

**Role rules:**
- Owner role: immutable, unkickable, cannot change own role
- No one can promote anyone to Owner
- Admin cannot change Owner's role
- Actor cannot change their own role

---

## NON-NEGOTIABLE RULES (RULE-01..35)

```
RULE-01  SQL only in app/Models/* and app/Helpers/*
RULE-02  PDO prepared statements with bound params only; no concatenation
RULE-03  Re-validate all input server-side; never trust client
RULE-04  Validate csrf_token with hash_equals on every mutation; 403 on mismatch
RULE-05  Fetch membership/role from DB per request via MemberModel::getMembership; never from $_SESSION
RULE-06  Cross-workspace check on every card/todo op: card.workspace_id must equal authorized workspace
RULE-07  workspace_members.status='Approved' required; Pending/Rejected → 403
RULE-08  Todo CRUD: Owner OR Admin OR card_access row exists
RULE-09  Every API response through App\Core\Response; standard envelope
RULE-10  Never demote/change/kick Owner; 403 if targeted
RULE-11  Never use native confirm(); use inline pattern (todo) or type-to-confirm modal (workspace delete)
RULE-12  Document root at /public only; never serve /app /config vendor .env over HTTP
RULE-13  .env outside web root, never committed; config via $_ENV only
RULE-14  No new framework, CDN-hosted JS, ORM, or library beyond vlucas/phpdotenv (phpunit dev-only)
RULE-15  Method override: _method field = PATCH or DELETE on real POST only
RULE-16  Wrap the 6 required ops in BEGIN/COMMIT with ROLLBACK on error
RULE-17  Activity INSERT inside same transaction as the op it records
RULE-18  Hard-delete todos with DELETE FROM todos WHERE id=?; no soft-delete, no deleted_at
RULE-19  All user-facing strings Bahasa Indonesia; activity strings from activity_templates.php only
RULE-20  Avatar filename: bin2hex(random_bytes(16)) + verified extension; never uniqid() or client filename
RULE-21  Disable PHP execution in public/uploads/avatars via .htaccess (php_flag engine off)
RULE-22  htmlspecialchars($v, ENT_QUOTES, 'UTF-8') on every echoed user value in Views
RULE-23  Validate avatar with mime_content_type() allowlist AND getimagesize(); reject otherwise
RULE-24  Security headers on every response: X-Frame-Options:DENY, X-Content-Type-Options:nosniff, Referrer-Policy, CSP
RULE-25  Never expose stack traces/SQL errors/.env values to client; display_errors=Off in production
RULE-26  Never log secrets, passwords, tokens, session contents
RULE-27  session_regenerate_id(true) immediately after successful login; CsrfManager::rotate() on login AND logout
RULE-28  Session cookies: HttpOnly, Secure (prod), SameSite=Strict; enforce SESSION_TIMEOUT idle check
RULE-29  Compute progress only through ProgressCalculator; return 0 when total=0; never store progress
RULE-30  Keep Controllers thin (auth/CSRF/role/validate/orchestrate); SQL stays in Models/Helpers
RULE-31  Invite code: strtoupper(bin2hex(random_bytes(4))); max 3 collision retries, usleep(1000) between; ROLLBACK on exhaust → 500
RULE-32  On regenerate invite code: auto-reject all Pending join requests in same transaction
RULE-33  Block duplicate Pending request → 409; honor IP cooldown → 429
RULE-34  Style via CSS custom properties from tokens.css only; no hardcoded hex outside tokens.css; no inline style theming
RULE-35  All network calls through api.js wrapper; no raw fetch() scattered; no inline on* HTML event handlers
```

---

## ARCHITECTURAL INVARIANTS (INV-01..20)

```
INV-01  Every API mutation response uses standard envelope {success, data?, message?} or {success:false, error, message}
INV-02  All SQL is parameterized and lives only in Models/Helpers
INV-03  Each workspace has exactly one Owner; Owner role immutable and unkickable
INV-04  A card belongs to exactly one workspace (FK NOT NULL + cross-workspace check on every op)
INV-05  The 6 required ops are atomic (BEGIN/COMMIT/ROLLBACK)
INV-06  Progress always derived from live todo counts, never persisted; no progress column
INV-07  Only status='Approved' members may access workspace data
INV-08  Membership/role never cached in session; reflect current DB state per request
INV-09  CSRF token session-scoped; changes only on login/logout; not per-request
INV-10  Dependency edges point downward only; Models/Helpers/Views never reference Controllers
INV-11  Activities append-only except Owner bulk-clear; INSERT lives inside same transaction as op
INV-12  Todos hard-deleted; no soft-delete state; schema has no deleted_at
INV-13  At most one workspace_members row per (workspace_id, user_id) — UNIQUE constraint
INV-14  At most one card_access row per (card_id, user_id) — UNIQUE constraint
INV-15  Invite codes unique — UNIQUE constraint + retry loop
INV-16  Email addresses unique — UNIQUE constraint + 422 EMAIL_TAKEN
INV-17  All user-facing text Bahasa Indonesia; activity strings from activity_templates.php
INV-18  Every echoed user value is HTML-escaped
INV-19  Historical activity survives card deletion (activities.card_id has no FK intentionally)
INV-20  Sessions regenerate ID on privilege change (login); idle timeout enforced
```

---

## FILE-BY-FILE RESPONSIBILITY (one-sentence each)

| File | Responsibility | Must NEVER do |
|---|---|---|
| public/index.php | Bootstrap: version guard, autoload, .env, session, security headers, hand to Router | Business logic, DB queries, echo HTML |
| public/.htaccess | Rewrite non-file/non-dir to index.php | Reference PHP classes, expose .env |
| app/Core/Database.php | Hold and return single shared PDO instance | Run queries itself, be called by Views |
| app/Core/Router.php | Parse path + _method, set security headers, dispatch to Controller@method | SQL, validation logic, render Views |
| app/Core/Request.php | Wrap request method/headers/body; expose _method | Trust client values as safe, query DB |
| app/Core/Response.php | Emit standard JSON envelope + correct HTTP status | Query DB, business rules, build SQL |
| app/Core/Session.php | Configure cookie flags + lifetime; start/manage session | Cache membership/role, query DB |
| app/Core/CsrfManager.php | Generate, validate (hash_equals), rotate session-scoped CSRF token | Rotate per request, query DB |
| app/Controllers/AuthController.php | Login/register/logout/dashboard redirect; rate-limit + session regen | SQL inline, cache role in session, skip session_regenerate_id(true) |
| app/Controllers/WorkspaceController.php | Workspace CRUD, share/regenerate, deadline, join/approve | SQL inline, skip Owner-only checks, skip required transactions |
| app/Controllers/CardController.php | Create/update/delete cards, grant/revoke card access | Skip cross-workspace check, skip Owner/Admin role check |
| app/Controllers/TodoController.php | Create/update/delete todos, return recalculated card progress | Skip card-access auth, skip XWS check, soft-delete todos |
| app/Controllers/MemberController.php | Update member role, kick member | Let anyone touch Owner role, kick Owner |
| app/Controllers/ActivityController.php | Fetch (paginate/search/filter) activities, clear log (Owner) | Build action strings ad hoc, allow non-Owner clear |
| app/Controllers/ProfileController.php | Show profile, update name and avatar | Trust client MIME, keep old avatar, skip double image check |
| app/Models/UserModel.php | All SQL for users (find/create/update) | Reference Controllers, raw concatenated SQL |
| app/Models/WorkspaceModel.php | All SQL for workspaces | Reference Controllers, cache role |
| app/Models/MemberModel.php | All SQL for workspace_members | Reference Controllers, store membership in session |
| app/Models/CardModel.php | All SQL for cards and card_access | Reference Controllers, skip parameter binding |
| app/Models/TodoModel.php | All SQL for todos | Soft-delete, reference Controllers, concatenate SQL |
| app/Models/ActivityModel.php | All SQL for activities (insert/paginated reads/bulk clear) | Reference Controllers, invent action text |
| app/Models/LoginAttemptModel.php | All SQL for login_attempts (check/increment/block/reset) | Reference Controllers, trust X-Forwarded-For |
| app/Helpers/ProgressCalculator.php | Compute card/workspace progress; single atomic COUNT; div-by-zero guard | Store progress, split into two queries, be called by Views |
| app/Helpers/ActivityLogger.php | Build action string from templates + INSERT activity row | Manage own transaction (caller owns it), use ad-hoc strings |
| app/Helpers/FileUploadHelper.php | Validate (MIME + getimagesize), store with random name, delete old avatar | Trust client extension/MIME, name files with uniqid() |
| app/Config/constants.php | Application constants sourced from $_ENV | Hold secrets in source, query DB |
| app/Config/activity_templates.php | Data array: activity_type → Bahasa Indonesia template string | Contain logic |
| app/Views/layouts/main.php | Authenticated shell (sidebar + content) from pre-fetched data | Query DB, call Models, echo unescaped values |
| app/Views/layouts/auth.php | Login/register shell | Query DB, echo unescaped input |
| app/Views/partials/sidebar.php | Render SSR sidebar from pre-fetched workspace lists | Fetch own data via AJAX, query DB |
| app/Views/partials/toast.php | Markup container for toasts | Query DB |
| app/Views/partials/modal-confirm.php | Markup for inline/destructive confirmation | Use native confirm(), query DB |
| app/Views/pages/dashboard.php | Render dashboard from pre-fetched data | Query DB, call Models |
| app/Views/pages/workspace.php | Render workspace tab + breadcrumb | Query DB, echo unescaped values |
| app/Views/pages/login.php | Login form with CSRF token + honeypot | Query DB |
| app/Views/pages/register.php | Register form with CSRF + honeypot | Query DB |
| app/Views/pages/profile.php | Profile form from pre-fetched user data | Query DB, expose another user's data |
| public/js/main.js | ES module entry: import and initialize page modules | Inline handlers, bypass api.js |
| public/js/modules/api.js | Fetch wrapper: inject csrf_token + _method, parse envelope, structure errors | Be bypassed by raw fetch() elsewhere |
| public/js/modules/toast.js | Show toasts and retry toasts | Perform fetches directly |
| public/js/modules/modal.js | Open/close modals, focus trap, destructive confirm | Use native confirm() |
| public/js/modules/sidebar.js | Accordion state (sessionStorage), collapse, mobile hamburger | Fetch sidebar data (it is SSR) |
| public/js/modules/card.js | Update progress bar; card menu wiring | Recompute authoritative progress client-side |
| public/js/modules/todo.js | Todo interactions + inline delete confirmation (no native confirm) | Use confirm(), skip 5s auto-cancel |
| public/js/modules/activity.js | Debounced search (500ms), Load More, AJAX refresh | Append search results instead of replacing |
| public/css/tokens.css | All CSS custom properties (colors, type, spacing, z-index, transitions) | Hold component rules, hardcode values elsewhere |
| public/css/base.css | Reset + base element styling using tokens | Hardcode colors, inline styles |
| public/css/components.css | Component styles via tokens | Hardcode colors/hex outside tokens |
| public/css/layouts.css | Sidebar/grid/responsive layout via tokens + breakpoints | Hardcode colors, inline styles |

---

## STEP INDEX (34 steps — title + prereqs only)

| STEP | Title | Prerequisites |
|---|---|---|
| STEP-01 | Repository and Folder Skeleton | Empty repo |
| STEP-02 | Composer Autoload and Single Dependency | STEP-01 |
| STEP-03 | .env Template and Constants File | STEP-01 |
| STEP-04 | Database Schema Migration | MySQL 8.0+ available; STEP-01 |
| STEP-05 | Front Controller and .htaccess | STEP-01 |
| STEP-06 | Core Database Singleton | STEP-02, STEP-03, STEP-04 |
| STEP-07 | Request, Response, Session, CsrfManager | STEP-06 |
| STEP-08 | Router with Method Override | STEP-07 |
| STEP-09 | BaseController Skeleton | STEP-07, STEP-08 (MemberModel placeholder until STEP-19) |
| STEP-10 | Security Headers and Bootstrap Order | STEP-05, STEP-08 |
| STEP-11 | routes.php Full Registration (501 stubs) | STEP-08 |
| STEP-12 | constants.php and activity_templates.php | STEP-03 |
| STEP-13 | Design Token CSS | STEP-01 |
| STEP-14 | Base, Component, Layout CSS Scaffolding | STEP-13 |
| STEP-15 | Global Fetch Wrapper api.js | STEP-07 (CSRF meta tag ready) |
| STEP-16 | Healthcheck Endpoint /healthz | STEP-08 |
| STEP-17 | UserModel | STEP-06 |
| STEP-18 | LoginAttemptModel | STEP-06 |
| STEP-19 | MemberModel (completes BaseController) | STEP-06 |
| STEP-20 | AuthController Implementation | STEP-15, STEP-17, STEP-18 |
| STEP-21 | ProfileController and Avatar Upload | STEP-20 |
| STEP-22 | Auth Pages and Layouts | STEP-13/14, STEP-20 |
| STEP-23 | WorkspaceModel | STEP-19 |
| STEP-24 | WorkspaceController | STEP-23 |
| STEP-25 | Sidebar SSR | STEP-23, STEP-22 |
| STEP-26 | Membership Endpoints (join/approve/role/kick) | STEP-24 |
| STEP-27 | Members Tab + Settings Panel UI | STEP-26 |
| STEP-28 | CardModel | STEP-23 |
| STEP-29 | CardController | STEP-28 |
| STEP-30 | Card Grid UI and Read-Only Transparency | STEP-29 |
| STEP-31 | TodoModel and TodoController | STEP-29 |
| STEP-32 | Todo UI (inline delete, status, filters) | STEP-31 |
| STEP-33 | Test Harness Wiring (PHPUnit, optional) | STEP-02 |
| STEP-34 | Activity Model, Controller, UI, Final Hardening | STEP-31 |

**Phase boundaries:**
- PHASE-0 done: STEP-01..16
- PHASE-1 done: STEP-17..22
- PHASE-2 done: STEP-23..27
- PHASE-3 done: STEP-28..30
- PHASE-4 done: STEP-31..33
- PHASE-5 done: STEP-34

---

## COMMON FAILURE MODES (quick ref)

| Failure | Fix |
|---|---|
| Division by zero in progress | ProgressCalculator returns 0 when total=0 (RULE-29) |
| Multi-tab CSRF 403 | Session-scoped token, rotate only login/logout (RULE-04) |
| Kicked user still acting | Fetch membership live from DB every request (RULE-05) |
| IDOR via foreign IDs | Cross-workspace check on every card/todo op (RULE-06) |
| SQL injection | PDO prepared statements with bound params (RULE-02) |
| Upload of disguised PHP | Double MIME + getimagesize, bin2hex name, php_flag engine off (RULE-20/21/23) |
| Missing _method dispatch | Router reads _method; api.js sends it (RULE-15) |
| N+1 progress slowness | Single atomic SELECT COUNT() per card in ProgressCalculator |
| Unescaped output XSS | htmlspecialchars on every echoed value (RULE-22) |
| Forgetting activity log | Log inside same transaction for every templated op (RULE-17) |
| Missing transaction on kick | Wrap delete-membership + delete-card_access + log in one TX (RULE-16) |
| Double-submit duplicates | Disable button (frontend) + check existing Pending/UNIQUE → 409 (RULE-33) |

---

## ENDPOINT DONE CHECKLIST (run before marking any controller action complete)

- [ ] CSRF token validated (every mutation)
- [ ] requireAuth() called first
- [ ] requireWorkspaceMember() with correct min role; status='Approved' enforced
- [ ] Membership/role read live from DB (not session)
- [ ] Todo CRUD: Owner/Admin/card_access checked additionally
- [ ] Cross-workspace check performed for every card/todo op
- [ ] All input validated/normalized server-side (types, lengths, enums, date formats, honeypot)
- [ ] Transaction wraps op if one of 6 required; ROLLBACK on error
- [ ] Activity logged inside the transaction when template exists
- [ ] Response uses standard envelope + correct HTTP status code
- [ ] Owner never demoted/changed/kicked (403 if targeted)
- [ ] Duplicate Pending/UNIQUE → 409; rate-limit → 429
