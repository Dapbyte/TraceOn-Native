# TRACEON — MASTER VIBECODE IMPLEMENTATION PROMPT
**Document Type:** Unified SRS + UIUX + Architecture Specification  
**Version:** V8 — Fully Revised (Post-Analysis)  
**Status:** PRODUCTION-READY FOR VIBECODE WORKFLOW  
**Stack:** PHP 8.3 Native MVC · MySQL 8+ · Vanilla JS (ES Modules) · CSS Custom Properties  

---

## SYSTEM CONTEXT

You are building **TraceOn** — a web-based workspace and task monitoring system. The system uses:
- PHP 8.3 native OOP (no framework)
- MySQL 8.0+ with InnoDB engine, accessed exclusively via PDO Prepared Statements
- Pure Vanilla JS with ES Modules (`<script type="module">`) — zero jQuery, React, Vue, Alpine
- Server-Side Rendering (SSR) via PHP for all initial page loads
- Fetch API for all async mutations
- CSS Custom Properties for design tokens — no inline styles, no hardcoded color values

Every requirement below is final, revised, and ready for direct implementation. Do not add frameworks or libraries not listed here.

---

## PART 1 — PROJECT STRUCTURE & BOOTSTRAP

### 1.1 Folder Structure (Mandatory)

```
/traceon/                        ← Project root (NEVER document root)
├── .env                         ← Env config (NEVER in /public)
├── composer.json                ← PSR-4 autoload config
├── /app/
│   ├── /Core/
│   │   ├── Database.php         ← PDO singleton
│   │   ├── Router.php           ← Front controller router
│   │   ├── Request.php          ← Request wrapper (sanitize, method, body)
│   │   ├── Session.php          ← Session management helper
│   │   ├── CsrfManager.php      ← CSRF token generation/validation
│   │   └── Response.php         ← JSON response helpers
│   ├── /Controllers/
│   │   ├── AuthController.php
│   │   ├── WorkspaceController.php
│   │   ├── CardController.php
│   │   ├── TodoController.php
│   │   ├── MemberController.php
│   │   ├── ActivityController.php
│   │   └── ProfileController.php
│   ├── /Models/
│   │   ├── UserModel.php
│   │   ├── WorkspaceModel.php
│   │   ├── CardModel.php
│   │   ├── TodoModel.php
│   │   ├── MemberModel.php
│   │   ├── ActivityModel.php
│   │   └── LoginAttemptModel.php
│   ├── /Views/
│   │   ├── /layouts/
│   │   │   ├── main.php         ← Authenticated layout (sidebar + content)
│   │   │   └── auth.php         ← Login/register layout
│   │   ├── /partials/
│   │   │   ├── sidebar.php
│   │   │   ├── toast.php
│   │   │   └── modal-confirm.php
│   │   └── /pages/
│   │       ├── dashboard.php
│   │       ├── workspace.php
│   │       ├── login.php
│   │       ├── register.php
│   │       └── profile.php
│   ├── /Helpers/
│   │   ├── ProgressCalculator.php
│   │   ├── ActivityLogger.php
│   │   └── FileUploadHelper.php
│   └── /Config/
│       ├── constants.php        ← APP constants (BCRYPT_COST, SESSION_TIMEOUT, etc.)
│       └── activity_templates.php ← Log message templates per activity_type
├── /public/                     ← DOCUMENT ROOT — Apache/Nginx points here ONLY
│   ├── index.php                ← Front controller entry point
│   ├── .htaccess                ← Rewrite all to index.php
│   ├── /css/
│   │   ├── tokens.css           ← All CSS Custom Properties
│   │   ├── base.css
│   │   ├── components.css
│   │   └── layouts.css
│   ├── /js/
│   │   ├── main.js              ← ES module entry
│   │   ├── /modules/
│   │   │   ├── toast.js
│   │   │   ├── modal.js
│   │   │   ├── sidebar.js
│   │   │   ├── card.js
│   │   │   ├── todo.js
│   │   │   └── activity.js
│   └── /uploads/
│       └── /avatars/            ← PHP execution disabled via .htaccess
└── /config/
    └── php.ini                  ← display_errors=Off, log_errors=On
```

### 1.2 .env Template (Mandatory)

```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=traceon
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

BCRYPT_COST=12
SESSION_TIMEOUT=7200
SESSION_NAME=traceon_session

APP_ENV=development
APP_URL=http://localhost
APP_DEBUG=false

UPLOAD_MAX_SIZE=2097152
UPLOAD_ALLOWED_TYPES=image/jpeg,image/png,image/webp
UPLOAD_AVATAR_DIR=/public/uploads/avatars
```

### 1.3 Front Controller & Routing

**`/public/index.php`:**
```php
<?php
if (version_compare(PHP_VERSION, '8.3.0', '<')) {
    die('TraceOn requires PHP 8.3.0 or higher. Current: ' . PHP_VERSION);
}

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$router = new App\Core\Router();
require_once __DIR__ . '/../routes.php';
$router->dispatch();
```

**`/public/.htaccess`:**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**`/routes.php`** (define all routes):
```php
// Web routes
$router->get('/',                       'AuthController@redirectDashboard');
$router->get('/login',                  'AuthController@showLogin');
$router->get('/register',               'AuthController@showRegister');
$router->get('/dashboard',              'WorkspaceController@dashboard');
$router->get('/workspace/{id}',         'WorkspaceController@show');
$router->get('/profile',                'ProfileController@show');

// API routes
$router->post('/api/auth/login',                  'AuthController@login');
$router->post('/api/auth/register',               'AuthController@register');
$router->post('/api/auth/logout',                 'AuthController@logout');
$router->post('/api/workspace/create',            'WorkspaceController@create');
$router->get('/api/workspace/share',              'WorkspaceController@shareCode');
$router->post('/api/workspace/regenerate-code',   'WorkspaceController@regenerateCode');
$router->post('/api/workspace/delete',            'WorkspaceController@delete');   // POST + _method=DELETE
$router->post('/api/workspace/rename',            'WorkspaceController@rename');   // POST + _method=PATCH
$router->post('/api/workspace/update-deadline',   'WorkspaceController@updateDeadline');
$router->post('/api/workspace/join-request',      'WorkspaceController@joinRequest');
$router->post('/api/workspace/approve-request',   'WorkspaceController@approveRequest');
$router->post('/api/member/role-update',          'MemberController@updateRole');
$router->post('/api/member/kick',                 'MemberController@kick');         // POST + _method=DELETE
$router->post('/api/card/create',                 'CardController@create');
$router->post('/api/card/update',                 'CardController@update');         // POST + _method=PATCH
$router->post('/api/card/delete',                 'CardController@delete');         // POST + _method=DELETE
$router->post('/api/card/access/grant',           'CardController@grantAccess');
$router->post('/api/card/access/revoke',          'CardController@revokeAccess');   // POST + _method=DELETE
$router->post('/api/todo/create',                 'TodoController@create');
$router->post('/api/todo/update',                 'TodoController@update');         // POST + _method=PATCH
$router->post('/api/todo/delete',                 'TodoController@delete');         // POST + _method=DELETE
$router->get('/api/activity/fetch',               'ActivityController@fetch');
$router->post('/api/activity/clear',              'ActivityController@clear');      // POST + _method=DELETE
$router->post('/api/profile/update',              'ProfileController@update');
```

> **Method Override Convention:** Since HTML forms only support GET/POST, all non-GET mutations use `POST` with a `_method` field (`DELETE`, `PATCH`). The Router reads `_method` and dispatches accordingly. Fetch API calls also use POST + `_method` for consistency and proxy compatibility.

### 1.4 Composer PSR-4 Autoload

**`composer.json`:**
```json
{
  "autoload": {
    "psr-4": {
      "App\\": "app/"
    }
  },
  "require": {
    "vlucas/phpdotenv": "^5.5"
  }
}
```

Run: `composer install` before first use.

### 1.5 Security Headers Bootstrap

Add to every PHP response in the Router dispatch or a middleware wrapper:

```php
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: blob:; connect-src 'self'; form-action 'self'; frame-ancestors 'none'");
```

---

## PART 2 — DATABASE SCHEMA

All tables use `InnoDB`. All foreign keys use `ON DELETE CASCADE` unless explicitly stated. All connections via PDO Prepared Statements only.

### SQL: Full Schema

```sql
-- ─── USERS ────────────────────────────────────────────────────────────────
CREATE TABLE users (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  name              VARCHAR(100) NOT NULL,
  email             VARCHAR(100) NOT NULL,
  password          VARCHAR(255) NOT NULL,
  avatar_path       VARCHAR(255) NULL DEFAULT NULL,
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── WORKSPACES ───────────────────────────────────────────────────────────
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

-- ─── WORKSPACE MEMBERS ────────────────────────────────────────────────────
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

-- ─── CARDS ────────────────────────────────────────────────────────────────
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

-- ─── CARD ACCESS ──────────────────────────────────────────────────────────
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

-- ─── TODOS ────────────────────────────────────────────────────────────────
-- NOTE: No soft delete. Deletion is permanent (hard delete). No deleted_at column.
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
  INDEX idx_todo_card_id          (card_id),
  INDEX idx_todo_card_status      (card_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── ACTIVITIES ───────────────────────────────────────────────────────────
CREATE TABLE activities (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  workspace_id   INT NOT NULL,
  user_id        INT NULL DEFAULT NULL,
  card_id        INT NULL DEFAULT NULL,          -- No FK: card may be hard-deleted; kept for historical context
  activity_type  VARCHAR(50) NOT NULL,
  old_value      TEXT NULL DEFAULT NULL,
  new_value      TEXT NULL DEFAULT NULL,
  action         TEXT NOT NULL,                  -- Human-readable log string, built from activity_templates
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_act_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_act_user      FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE SET NULL,
  INDEX idx_act_workspace_created (workspace_id, created_at),
  INDEX idx_act_user_id           (user_id),
  INDEX idx_act_created_at        (created_at),
  FULLTEXT INDEX ft_act_action    (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── LOGIN ATTEMPTS ───────────────────────────────────────────────────────
CREATE TABLE login_attempts (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  ip_address      VARCHAR(45) NOT NULL,
  attempt_count   SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  last_attempt_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  blocked_until   TIMESTAMP NULL DEFAULT NULL,
  INDEX idx_la_ip_address (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Schema Notes

- `todos.status` ENUM uses English values: `'pending'`, `'in_progress'`, `'done'` — consistent language throughout.
- `workspace_members.status` includes `'Rejected'` to preserve audit trail. Rejection does not delete the record.
- `workspace_members.requested_at` replaces the misleading `joined_at`. `approved_at` is set when status changes to `'Approved'`.
- `card_access.granted_by` and `granted_at` added for audit.
- `workspaces`, `cards`, `todos`, `users` all have `updated_at ON UPDATE CURRENT_TIMESTAMP`.
- `activities.card_id` has no FK intentionally — historical logs must survive card deletion.
- `login_attempts.attempt_count` is `SMALLINT UNSIGNED` (max 65535) — avoids TINYINT overflow.

---

## PART 3 — CORE PHP CLASSES

### 3.1 Database Singleton

```php
// app/Core/Database.php
class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_NAME'], $_ENV['DB_CHARSET']
            );
            self::$instance = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => true,
            ]);
        }
        return self::$instance;
    }
}
```

### 3.2 CSRF Manager (Session-Scoped, Not Per-Request)

```php
// app/Core/CsrfManager.php
class CsrfManager {
    public static function generate(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validate(string $token): bool {
        return isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    // Call only on login and logout (privilege change)
    public static function rotate(): void {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
```

> **CSRF Strategy:** Token is session-scoped (one token per session). Rotated only on login and logout. This prevents multi-tab 403 errors caused by per-request rotation, while still protecting against CSRF. Combined with `SameSite=Strict` cookies, this meets OWASP CSRF prevention standards.

### 3.3 Progress Calculator

```php
// app/Helpers/ProgressCalculator.php
class ProgressCalculator {
    /**
     * Returns integer 0-100. Never throws division by zero.
     */
    public static function forCard(int $cardId): int {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT
               COUNT(*) AS total,
               SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS done
             FROM todos
             WHERE card_id = ?'
        );
        $stmt->execute(['done', $cardId]);
        $row = $stmt->fetch();
        if ((int)$row['total'] === 0) return 0;
        return (int) round(($row['done'] / $row['total']) * 100);
    }

    /**
     * Returns float 0-100 (average of all cards in workspace).
     */
    public static function forWorkspace(int $workspaceId): float {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id FROM cards WHERE workspace_id = ?');
        $stmt->execute([$workspaceId]);
        $cards = $stmt->fetchAll();
        if (empty($cards)) return 0.0;
        $total = 0;
        foreach ($cards as $card) {
            $total += self::forCard($card['id']);
        }
        return round($total / count($cards), 1);
    }
}
```

### 3.4 Activity Logger

```php
// app/Helpers/ActivityLogger.php
// app/Config/activity_templates.php — template strings per activity_type:
// 'card_create'    => '{user} membuat card "{card}"'
// 'card_edit'      => '{user} mengubah card "{old}" menjadi "{new}"'
// 'card_delete'    => '{user} menghapus card "{card}"'
// 'todo_create'    => '{user} menambahkan todo "{todo}" ke card "{card}"'
// 'todo_edit'      => '{user} mengubah todo dari "{old}" menjadi "{new}"'
// 'todo_delete'    => '{user} menghapus todo "{todo}" dari card "{card}"'
// 'todo_status'    => '{user} mengubah status todo "{todo}" dari {old} ke {new}'
// 'member_join'    => '{user} bergabung ke workspace'
// 'member_approve' => '{actor} menyetujui permohonan {user}'
// 'member_reject'  => '{actor} menolak permohonan {user}'
// 'member_kick'    => '{actor} mengeluarkan {user} dari workspace'
// 'role_change'    => '{actor} mengubah role {user} dari {old} ke {new}'
// 'access_grant'   => '{actor} memberikan akses card "{card}" ke {user}'
// 'access_revoke'  => '{actor} mencabut akses card "{card}" dari {user}'
// 'workspace_rename' => '{actor} mengubah nama workspace dari "{old}" ke "{new}"'
// 'log_clear'      => '{actor} menghapus seluruh log aktivitas'

class ActivityLogger {
    public static function log(
        int $workspaceId,
        ?int $userId,
        ?int $cardId,
        string $activityType,
        ?string $oldValue,
        ?string $newValue,
        string $actionText
    ): void {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO activities
               (workspace_id, user_id, card_id, activity_type, old_value, new_value, action)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$workspaceId, $userId, $cardId, $activityType, $oldValue, $newValue, $actionText]);
    }
}
```

### 3.5 File Upload Helper

```php
// app/Helpers/FileUploadHelper.php
class FileUploadHelper {
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_SIZE     = 2097152; // 2MB

    public static function saveAvatar(array $file): string {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload error code: ' . $file['error'], 422);
        }
        if ($file['size'] > self::MAX_SIZE) {
            throw new \RuntimeException('Ukuran file melebihi batas 2MB', 422);
        }

        // Double MIME check: magic bytes + GD parse
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            throw new \RuntimeException('Format tidak diizinkan', 422);
        }
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new \RuntimeException('File bukan gambar yang valid', 422);
        }

        $ext      = match($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        };
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;  // Cryptographically random name
        $dest     = __DIR__ . '/../../public/uploads/avatars/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Gagal menyimpan file', 500);
        }

        return '/uploads/avatars/' . $filename;
    }

    public static function deleteAvatar(?string $path): void {
        if ($path === null) return;
        $full = __DIR__ . '/../../public' . $path;
        if (file_exists($full) && is_file($full)) {
            unlink($full);
        }
    }
}
```

---

## PART 4 — SESSION & AUTHENTICATION

### 4.1 Session Configuration

In `app/Core/Session.php`, call this before any session_start():

```php
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure',   '1');   // Requires HTTPS in production
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime',  (string)(int)$_ENV['SESSION_TIMEOUT']);
session_name($_ENV['SESSION_NAME'] ?? 'traceon_session');
session_start();
```

### 4.2 Login Flow

```
1. Trim + validate input (email format, password non-empty)
2. Get real IP: use REMOTE_ADDR only (do NOT trust X-Forwarded-For unless proxy is explicitly configured)
3. Check login_attempts WHERE ip_address = ? AND blocked_until IS NOT NULL AND blocked_until > NOW()
   → If blocked: return 429 {"error":"RATE_LIMITED","message":"Terlalu banyak percobaan. Coba lagi dalam N menit."}
4. "Lazy cleanup": DELETE FROM login_attempts WHERE blocked_until IS NOT NULL AND blocked_until < NOW()
5. SELECT user WHERE email = ? (PDO Prepared)
6. password_verify($input, $hash)
   → If FAIL:
       a. Check if record exists for this IP
       b. If yes: UPDATE login_attempts SET attempt_count = attempt_count + 1, last_attempt_at = NOW()
                  If attempt_count >= 5: SET blocked_until = NOW() + INTERVAL 15 MINUTE
       c. If no:  INSERT INTO login_attempts (ip_address, attempt_count) VALUES (?, 1)
       d. Return 401 {"error":"INVALID_CREDENTIALS","message":"Kredensial tidak valid"}
   → If SUCCESS:
       a. DELETE FROM login_attempts WHERE ip_address = ?  (reset counter)
       b. session_regenerate_id(true)
       c. Set session: user_id, user_name, user_email, user_role (global), user_avatar
       d. CsrfManager::rotate()
       e. Return 200 {"success":true,"redirect":"/dashboard"}
```

### 4.3 Auth Middleware Pattern

Every controller method that requires authentication must call this at the top:

```php
// In BaseController or called explicitly
protected function requireAuth(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: /login');
        exit;
    }
}

protected function requireWorkspaceMember(int $workspaceId, string $minRole = 'Member'): array {
    $membership = MemberModel::getMembership($_SESSION['user_id'], $workspaceId);
    if (!$membership || $membership['status'] !== 'Approved') {
        $this->json(['error' => 'FORBIDDEN'], 403);
        exit;
    }
    if ($minRole === 'Admin' && !in_array($membership['role'], ['Owner', 'Admin'])) {
        $this->json(['error' => 'FORBIDDEN'], 403);
        exit;
    }
    if ($minRole === 'Owner' && $membership['role'] !== 'Owner') {
        $this->json(['error' => 'FORBIDDEN'], 403);
        exit;
    }
    return $membership;
}
```

> **Critical:** Membership status and role must ALWAYS be fetched from the database per request. Do NOT cache membership in session variables. A kicked member's session remains valid until expiry — only a live DB check catches this on every protected request.

---

## PART 5 — FUNCTIONAL REQUIREMENTS

### 5.1 Authentication

**FR-01 Login** — See Section 4.2 for complete flow.

**FR-02 Logout**
- Session destroy: `session_unset(); session_destroy(); session_write_close()`
- Clear cookie: `setcookie(session_name(), '', ['expires'=>time()-3600,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Strict'])`
- CsrfManager::rotate() (generates new token for post-logout state)
- Redirect: 302 → `/login`
- Back button after logout → protected pages must redirect to /login (session check at top of every page)

**FR-03 Session Management**
- Inactivity timeout: 2 hours (`SESSION_TIMEOUT=7200`)
- Every protected page: check `$_SESSION['user_id']` — if missing, redirect /login
- Session idle check: store `$_SESSION['last_activity']`. If `NOW - last_activity > SESSION_TIMEOUT`, destroy and redirect.

**FR-04 Register**
- Server-side validation:
  - `name`: non-empty after trim, max 100 chars
  - `email`: filter_var FILTER_VALIDATE_EMAIL, max 100 chars, UNIQUE check against DB
  - `password`: min 8 chars, must contain at least 1 letter AND 1 digit
  - `confirm_password`: must match `password`
  - Honeypot field: `website` input, hidden via CSS (`position:absolute;left:-9999px`), `aria-hidden="true"`, `tabindex="-1"` — if non-empty, reject silently
- Password hash: `password_hash($password, PASSWORD_BCRYPT, ['cost' => (int)$_ENV['BCRYPT_COST']])`
- On success: redirect /login with toast "Registrasi berhasil. Silakan masuk."
- On email duplicate: 422 `{"error":"EMAIL_TAKEN","message":"Email sudah digunakan"}`

**FR-46 Profile Management**
- Update name: trim, max 100 chars, non-empty
- Update avatar: use `FileUploadHelper::saveAvatar()`, then delete old avatar via `FileUploadHelper::deleteAvatar($oldPath)`, then UPDATE users SET avatar_path = ?, updated_at = NOW()
- MIME validation: double-check via `mime_content_type()` + `getimagesize()` (see FileUploadHelper)
- Return: `{"success":true,"message":"Profil diperbarui","data":{"avatar_path":"/uploads/avatars/..."}}`

### 5.2 Workspace & Membership

**FR-07 Create Workspace**
```
Transaction:
  1. Generate invite_code: strtoupper(bin2hex(random_bytes(4))) — 8 uppercase hex chars
     Loop max 3 attempts checking UNIQUE collision:
       SELECT COUNT(*) FROM workspaces WHERE invite_code = ?
       If collision: usleep(1000) + retry
       If 3 failures: ROLLBACK, return 500 + log error
  2. INSERT INTO workspaces (name, deadline, invite_code, owner_id)
  3. INSERT INTO workspace_members (workspace_id, user_id, role, status, approved_at)
     VALUES (new_id, session_user_id, 'Owner', 'Approved', NOW())
COMMIT
Return: 200 {"success":true,"data":{"id":N,"invite_code":"XXXXXXXX"}}
```

**FR-08 Share Workspace / Regenerate Code**
- `GET /api/workspace/share?workspace_id=N` — **requires Owner or Admin role** (enforced via `requireWorkspaceMember($id, 'Admin')`)
- Regenerate (Owner only): generate new invite_code, UPDATE workspaces SET invite_code = ?, updated_at = NOW(). **Auto-reject all Pending join requests** for this workspace: `UPDATE workspace_members SET status = 'Rejected' WHERE workspace_id = ? AND status = 'Pending'`

**FR-09 Join Request**
- Normalize input: `strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim($input)))`
- Check workspace by invite_code
- Check user not already a member (any status): if status='Approved' → 409 "Kamu sudah bergabung"; if status='Pending' → 409 "Permohonanmu sedang menunggu persetujuan"
- Cooldown: IP-based rate limiting. Use `login_attempts` table pattern (separate from login). After 3 failed invite_code attempts from same IP: block for 30 seconds (blocked_until = NOW() + INTERVAL 30 SECOND). Return countdown in error response: `{"error":"COOLDOWN","retry_after":30}`
- On success: INSERT workspace_members (workspace_id, user_id, role='Member', status='Pending')

**FR-10 Approve / Reject Join Request**
- Approve (Transaction):
  1. UPDATE workspace_members SET status = 'Approved', approved_at = NOW() WHERE id = ? AND status = 'Pending'
  2. ActivityLogger::log(... 'member_approve' ...)
  COMMIT
- Reject: UPDATE workspace_members SET status = 'Rejected' WHERE id = ? AND status = 'Pending' (no delete — keep record for audit)

**FR-12/13 Role System**
- Roles: `Owner`, `Admin`, `Member`
- Owner can promote Member → Admin, demote Admin → Member
- Admin can promote Member → Admin, demote Admin → Member (same permissions as Owner for role management, EXCEPT: cannot change Owner's role, cannot promote anyone to Owner)
- Owner role cannot be changed by anyone
- Owner cannot be kicked (backend enforces: if target is Owner → 403)
- Owner cannot change their own role

**FR-14a Kick Member (Transaction)**
```
1. Validate: actor is Owner or Admin; target is not Owner
2. BEGIN TRANSACTION
3. DELETE FROM workspace_members WHERE workspace_id = ? AND user_id = ?
4. DELETE FROM card_access WHERE user_id = ? AND card_id IN (SELECT id FROM cards WHERE workspace_id = ?)
5. ActivityLogger::log(... 'member_kick' ...)
COMMIT
```

**FR-44/45 Workspace Settings**
- Rename (Owner only): validate 3-100 chars, trim, UPDATE workspaces, log 'workspace_rename'
- Update deadline (Owner only): validate DATE format via `DateTime::createFromFormat('Y-m-d', $input)`, UPDATE workspaces
- Delete Workspace (Owner only, Transaction):
  ```
  1. Front-end: input verification (type workspace name to confirm)
  2. Back-end: verify input === workspace.name (server-side, not just client-side)
  3. BEGIN TRANSACTION — CASCADE handles all relations via FK
  4. DELETE FROM workspaces WHERE id = ? AND owner_id = ?
  COMMIT
  5. Return: 200 {"success":true,"redirect":"/dashboard"}
  ```
  - Note: Cannot delete if user would be the last to delete their only workspace AND they have no exit — this is allowed. Workspace delete is irreversible by design.
  - Note: Owner cannot delete account while owning workspaces. Account deletion (future feature) must check `SELECT COUNT(*) FROM workspaces WHERE owner_id = ?` and block if > 0.

### 5.3 Card Management

**FR-15 Create Card** — Owner/Admin only
- Input: `title` (3-100 chars, trim), `deadline` (DATE, optional — validate format)
- INSERT cards, created_by = session_user_id
- Log 'card_create'
- Return: `{"success":true,"data":{"card_id":N}}`

**FR-17 Grant/Revoke Card Access** — Owner/Admin only
- Grant: INSERT card_access (card_id, user_id, granted_by=session_user_id). Check UNIQUE before insert → 409 if duplicate.
- Revoke (Transaction): DELETE card_access WHERE card_id=? AND user_id=?; ActivityLogger::log('access_revoke')
- Cross-workspace validation: verify `cards.workspace_id = target_workspace_id` — prevent cross-workspace card access manipulation

**FR-18 Read-Only Transparency**
- Members without card_access see the card in the grid (title, progress bar, deadline badge, member avatars)
- All CRUD buttons are hidden in HTML AND blocked at backend
- Backend check: before any todo CRUD, verify: `user is Owner OR Admin OR EXISTS (SELECT 1 FROM card_access WHERE card_id=? AND user_id=?)`

**FR-19/20 Edit/Delete Card** — Owner/Admin only
- Edit: validate title (3-100), deadline (DATE or null), UPDATE cards SET title=?, deadline=?, updated_at=NOW()
- Delete: cascade handled by FK. Log 'card_delete'.

### 5.4 Todo Management

**FR-21/22 Create/Edit Todo**
- Auth check: Owner/Admin/Member-with-card_access
- Create: INSERT todos (card_id, title, status='pending', created_by). Recalculate progress. Log 'todo_create'.
- Edit title/status: UPDATE todos SET title=?, status=?, updated_at=NOW(). Recalculate progress. Log 'todo_edit' or 'todo_status'.
- Status values: `'pending'` (default), `'in_progress'`, `'done'`
- Return on status change: `{"success":true,"data":{"progress_card":N}}`

**FR-23 Delete Todo (Hard Delete)**
- **Frontend: Do NOT use native browser `confirm()`.** Use custom inline confirmation (see Section 6.4 — Inline Delete Confirmation pattern).
- Auth check: same as create/edit
- DELETE FROM todos WHERE id = ? (hard delete — no soft delete, no deleted_at column)
- Recalculate progress. Log 'todo_delete'.
- Return: `{"success":true,"message":"Todo berhasil dihapus","data":{"progress_card":N}}`
- Frontend: animate element to `opacity: 0`, then call `element.addEventListener('transitionend', () => element.remove(), {once:true})`

**FR-25/26 Status Dropdown + Filter**
- Status dropdown: `<select>` with options pending/in_progress/done. On change, fire PATCH-equivalent POST to `/api/todo/update`
- Filter buttons: "Semua" / "Selesai" / "Dalam Proses" / "Belum" — toggle filter by adding/removing class on todo rows (client-side DOM filter, no new fetch)

### 5.5 Progress Tracking

**FR-27 Card Progress**
- Formula: `done_count / total_count * 100` (anti-division-by-zero: if total=0 → return 0)
- Use `ProgressCalculator::forCard(int $cardId)` — single atomic SELECT COUNT query
- Progress bar fill color:
  - < 100%: `--color-secondary (#2175B8)`
  - = 100%: `--color-success (#2E7D32)` — add class `.progress-complete` to trigger color change
- Animation: `transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1)`

**FR-29/30 Workspace Progress**
- Use `ProgressCalculator::forWorkspace(int $workspaceId)` — average of all card progresses
- "Active card" = any non-deleted card in the workspace (no status filtering)
- Display format: "68% (5/8 card selesai)" — where "card selesai" means progress = 100%

### 5.6 Activity Log

**FR-31 Activity Recording**
- Use `ActivityLogger::log()` for every operation listed in the templates (Section 3.4)
- All action strings generated from `activity_templates.php` constants — never ad-hoc strings

**FR-32/33 Rendering & Pagination**
- Initial render: SSR by PHP (last 50 records, sorted DESC by created_at, WHERE workspace_id = ?)
- Pagination: "Load More" button triggers GET `/api/activity/fetch?workspace_id=N&offset=50&limit=50`
- Response must include: `{"success":true,"data":[...],"meta":{"offset":50,"limit":50,"has_more":true}}`
- "Load More" button hidden when `has_more = false`

**FR-36/37 Search**
- Debounce: 500ms after last keypress
- Triggers GET `/api/activity/fetch?workspace_id=N&search=query&offset=0&limit=50`
- Search **replaces** server-rendered content (not appended)
- FULLTEXT search on `activities.action` (MySQL `MATCH...AGAINST`)
- Clear button resets search, re-fetches without search param

**FR-38/39 Filter**
- Filter popup: activity_type (multi-select checkboxes), date range (from/to), user (dropdown)
- Default: last 7 days, all types, all users
- Filter state: maintained in JS object for current session (not persisted to storage)

**FR-40/41 Load More & Timestamps**
- Timestamp logic:
  - < 1 minute: "Baru saja"
  - 1-59 minutes: "N menit lalu"
  - 1-23 hours: "N jam lalu"
  - 1-6 days: "N hari lalu"
  - ≥ 7 days: "DD MMM YYYY HH:mm" (absolute)

---

## PART 6 — UI/UX SPECIFICATION

### 6.1 Layout Architecture

**Post-login layout: Split-Screen**
- Left: Persistent vertical sidebar (260px expanded, 72px mini-collapsed)
- Right: Scrollable main content area
- Sidebar collapse: `transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1)` — text fades: `transition: opacity 0.15s ease-out`
- Mobile (< 768px): sidebar hidden, hamburger button shows overlay sidebar

**Sidebar Content (top to bottom):**
1. TraceOn logo + wordmark
2. Button: "+ Workspace Baru" → opens Create Workspace Modal
3. Button: "+ Join Workspace" → opens Join Workspace Modal
4. Accordion: "Dibagikan (N)" — workspaces where user is Member/Admin
5. Accordion: "Workspace (N)" — workspaces where user is Owner
6. Footer: Avatar + Username + Profile button

**Sidebar Behavior:**
- Data rendered by PHP (SSR) on every page load — no AJAX for sidebar
- Accordion state: persisted in `sessionStorage` (key: `traceon_accordion_state`)
- Hover on workspace item: show ellipsis (⋮) icon
- On mobile: hamburger → overlay sidebar (z-index: 100, backdrop overlay behind)

**Onboarding Empty State (new users with zero workspaces):**
- Show centered illustration + "Buat workspace pertamamu untuk mulai" + CTA button "+ Buat Workspace" + secondary link "Atau bergabung ke workspace tim"

### 6.2 Workspace Internal Navigation

**Tabs: Dashboard | Anggota (N) | Log Aktivitas**
- Position: sticky top, z-index: 200
- Background: `--color-background`, border-bottom: 1.5px solid `--color-border`
- Active tab: text `--color-secondary`, weight 600, bottom indicator: 3px solid `--color-secondary`, border-radius 3px, sliding animation
- Inactive tab: text `--text-muted`, weight 500
- Tab content: PHP renders only the Dashboard tab on initial load. Members and Activity tabs lazy-load via AJAX on first click, then cache in JS memory.

**Breadcrumb:** PHP-rendered above tab bar. Format: `Dashboard / Nama Workspace`

### 6.3 Card Grid

**Grid breakpoints:**
- ≥ 1280px: 4 columns, gap: 24px
- 1024–1279px: 3 columns, gap: 24px
- 640–1023px: 2 columns, gap: 16px
- < 640px: 1 column, gap: 12px

**Card Component (default state):**
- BG: `--color-background (#FFFFFF)`
- Border: 1px solid `--color-border`
- Radius: `--radius-lg (12px)`
- Shadow: `--shadow-sm`
- Padding: 16px (`--space-md`)

**Card Component (hover state):**
- Border: 1px solid `rgba(33, 117, 184, 0.5)` — secondary at 50% opacity
- Shadow: `--shadow-md`
- Transform: `translateY(-2px)`
- Transition: `all 0.25s ease-out`

**Card content:**
- Card title: `--text-h3` (16px / SemiBold), color `--text-primary`
- Progress bar: 8px height track, fill animated. Ratio text: "7/10 selesai" below bar, `--text-sm`
- Deadline badge: `--text-sm`, background `--color-warning` at 10% opacity, text `--color-warning` — if < 3 days: bright warning badge
- Member avatars (max 3 visible): 24px circles, overflow shows "+N" badge if > 3
- Ellipsis button (⋮): visible on hover (desktop), always visible (mobile)
- Read-only indicator: small "Hanya Baca" badge, muted style — shown when user has no card_access

**Ellipsis Context Menu (desktop):** dropdown with: Edit Card, Delete Card, Kelola Akses
**Ellipsis Context Menu (mobile):** bottom sheet (slides up from bottom of viewport) with same options

**Card access avatars overflow rule:** Show max 3 avatars. If card_access count > 3, show 3 avatars + `+N` badge.

### 6.4 Todo List & Interactions

**Todo item states:**
- `pending` (default): default text color, no badge
- `in_progress`: small amber badge "Sedang"
- `done`: strikethrough text, opacity 60%, checkbox filled with `--color-success` + white checkmark SVG

**Custom checkbox:** 18×18px box, `--radius-sm`. Transition: `transform scale(0.1→1)` 150ms on check.

**Todo delete — Inline Confirmation (replaces native confirm()):**
1. User clicks trash icon → trash icon changes to: `[✓ Hapus]` + `[✗ Batal]` inline on the same row
2. Row background: subtle `rgba(198,40,40,0.06)` — visual warning
3. User clicks `[✓ Hapus]` → fires DELETE request
4. User clicks `[✗ Batal]` or presses Escape → reverts to normal row
5. Auto-cancel if no action after 5 seconds
6. On successful delete: `opacity: 0` transition (250ms), then `element.addEventListener('transitionend', () => element.remove(), {once:true})`

**Todo URL state:** On card detail open, update URL using `history.pushState(null, '', '/workspace/{id}/card/{card_id}')`. On close, restore previous URL.

**Enter key in new todo input:** submits the form (same as clicking Simpan button).

### 6.5 Modal System

**Z-index hierarchy:**
- Sidebar: 100
- Sticky nav: 200
- Modal backdrop: 300
- Modal content: 400
- Toast: 500

**Modal animation:**
- Backdrop: `fadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1)` — fade in to `rgba(0,0,0,0.5)`
- Modal content: `scaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1)` — scale(0.95→1) + translateY(10px→0) + opacity(0→1). **No spring/bounce easing.**
- Close: backdrop fade-out + scale(1→0.95) + opacity(1→0)

**Close triggers:** ESC key OR click backdrop area
**Focus trap:** Tab key cycles only within modal while open
**Modal on mobile:** Full-screen (100vw × 100vh) with bottom sheet style for confirmation modals

**Destructive confirmation modal:** Input field to type resource name. Submit button remains disabled until input === resource name (client-side AND server-side check).

### 6.6 Toast Notification System

- Position: bottom-right, `position: fixed; bottom: 24px; right: 24px`
- Animation: slide in from right — `toastSlideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1)`
- Categories + auto-dismiss:
  - Success (green): 4 seconds
  - Info (blue): 4 seconds
  - Warning (orange): 6 seconds
  - Error (red): 8 seconds
- Stacking: new toast appears on top; old toasts shift down. Max 5 visible.
- Failed request toast: includes inline "Coba Lagi" action link that retriggers the last failed fetch.

### 6.7 Empty States

| Surface | Illustration + Text | CTA |
|---|---|---|
| Dashboard — no workspaces (new user) | "Buat workspace pertamamu" | "+ Buat Workspace" button |
| Dashboard — workspace has no cards | "Belum ada card di workspace ini" | "+ Tambah Card" button |
| Activity log — empty | "Belum ada aktivitas di workspace ini" | — |
| Search result — no match | "Tidak ada hasil untuk '{query}'" | Clear search link |
| Members — no pending requests | "Tidak ada permohonan yang menunggu" | — |

### 6.8 Loading & Skeleton States

**Page load skeleton:** Card grid shows shimmer skeleton cards (same dimensions as real cards):
```css
@keyframes shimmer {
  0%   { background-position: -450px 0; }
  100% { background-position:  450px 0; }
}
.skeleton-shimmer {
  background: linear-gradient(to right, #E8EDF3 8%, #F4F7FB 18%, #E8EDF3 33%);
  background-size: 800px 104px;
  animation: shimmer 1.5s infinite linear;
}
```

**Button loading state:** spinner inside button + `disabled` attribute + `cursor: not-allowed`. Prevents double-submit. Re-enable on response (success or error).

**Activity log:** Rendered server-side on page load. "Refresh" button triggers GET `/api/activity/fetch` (AJAX — NOT full page reload), replaces rendered content.

### 6.9 Settings Panel

**Sections:**
1. **Workspace Settings** (Owner only): Rename field, Deadline update
2. **Invite Settings** (Owner only): Display current invite_code (readonly input) + "Salin Code" button (clipboard + toast) + "Regenerate Code" button (modal confirmation: "Code lama akan langsung tidak berlaku dan semua permintaan pending akan ditolak")
3. **Invite Settings** (Admin): Display invite_code (readonly) + "Salin Code" only — no regenerate
4. **Danger Zone** (Owner only): Background `#FFEBEE`, border 1px solid `#EF9A9A`. Contains "Hapus Workspace Permanen" button → opens destructive confirmation modal

### 6.10 Members Tab

**Table columns:** Avatar (32px circle) | Nama | Role (dropdown for Owner/Admin to change) | Status (Approved badge or Pending badge) | Actions

**Actions per row:**
- Owner/Admin viewing Member row: "Kick" button (red, opens confirmation modal)
- Owner viewing Admin row: "Turunkan ke Member" button + "Kick" button
- Owner/Admin viewing pending row: "Setujui" (green) + "Tolak" (red) buttons
- Viewing own row: no action buttons (grayed "Kamu" label)
- Viewing Owner row: no action buttons

---

## PART 7 — DESIGN SYSTEM

### 7.1 CSS Custom Properties (tokens.css)

```css
:root {
  /* ─── Core Blues ─── */
  --color-primary:           #1A3A5C;
  --color-secondary:         #2175B8;
  --color-accent:            #4BA3E3;  /* Decorative only — NOT for text on white */

  /* ─── Tints (hover/focus/selected states) ─── */
  --color-primary-5:         #F0F4F8;
  --color-primary-10:        #E2EAF2;
  --color-secondary-10:      #EBF4FD;
  --color-secondary-20:      #D7E9FB;

  /* ─── Semantic ─── */
  --color-success:           #2E7D32;
  --color-warning:           #B45309;   /* Softened from #E65100 */
  --color-error:             #C62828;
  --color-info:              #1565C0;

  /* ─── Surfaces ─── */
  --color-surface:           #F4F7FB;
  --color-background:        #FFFFFF;
  --color-border:            #E8EDF3;   /* Decorative dividers only */
  --color-input-border:      #CBD5E0;   /* Input borders — meets WCAG 1.4.11 */

  /* ─── Neutrals ─── */
  --color-neutral-50:        #F9FAFB;
  --color-neutral-100:       #F3F4F6;
  --color-neutral-200:       #E5E7EB;
  --color-neutral-300:       #D1D5DB;
  --color-neutral-400:       #9CA3AF;
  --color-neutral-500:       #6B7280;
  --color-neutral-600:       #4B5563;
  --color-neutral-700:       #374151;
  --color-neutral-900:       #111827;

  /* ─── Typography Colors ─── */
  --text-primary:            #1A202C;
  --text-muted:              #4A5568;
  --text-disabled:           #9CA3AF;
  --text-link:               #2175B8;   /* NOT accent — secondary passes WCAG AA on white */

  /* ─── Typography Scale ─── */
  --text-h1:                 1.5rem;    /* 24px */
  --text-h1-line:            2rem;      /* 32px */
  --text-h1-lg:              1.75rem;   /* 28px — use at ≥1440px */
  --text-h2:                 1.25rem;   /* 20px */
  --text-h2-line:            1.75rem;   /* 28px */
  --text-h3:                 1rem;      /* 16px */
  --text-h3-line:            1.5rem;    /* 24px */
  --text-body:               0.875rem;  /* 14px */
  --text-body-line:          1.25rem;   /* 20px */
  --text-button:             0.875rem;  /* 14px */
  --text-sm:                 0.8125rem; /* 13px — min for accessibility */
  --text-sm-line:            1rem;      /* 16px */

  /* ─── Font Families ─── */
  --font-heading:            'Plus Jakarta Sans', sans-serif;
  --font-body:               'Inter', sans-serif;

  /* ─── Spacing (8px base grid) ─── */
  --space-1:   4px;
  --space-2:   8px;
  --space-3:   12px;
  --space-4:   16px;
  --space-6:   24px;
  --space-8:   32px;
  --space-12:  48px;
  --space-16:  64px;

  /* ─── Border Radius ─── */
  --radius-sm:  4px;
  --radius-md:  8px;
  --radius-lg:  12px;
  --radius-xl:  16px;
  --radius-full: 9999px;

  /* ─── Shadows ─── */
  --shadow-sm:  0 1px 3px rgba(26,58,92,0.05), 0 1px 2px rgba(26,58,92,0.03);
  --shadow-md:  0 4px 6px -1px rgba(26,58,92,0.08), 0 2px 4px -1px rgba(26,58,92,0.04);
  --shadow-lg:  0 10px 15px -3px rgba(26,58,92,0.12), 0 4px 6px -2px rgba(26,58,92,0.05);

  /* ─── Z-index ─── */
  --z-sidebar:  100;
  --z-nav:      200;
  --z-backdrop: 300;
  --z-modal:    400;
  --z-toast:    500;

  /* ─── Transitions ─── */
  --transition-fast:    0.15s ease-out;
  --transition-base:    0.2s ease-out;
  --transition-slow:    0.3s cubic-bezier(0.4, 0, 0.2, 1);
  --transition-sidebar: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  --transition-toast:   0.35s cubic-bezier(0.16, 1, 0.3, 1);
  --transition-modal:   0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

/* ─── Dark Mode ─── */
@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --color-primary:      #4BA3E3;
    --color-secondary:    #5BB5F0;
    --color-surface:      #1A1F2E;
    --color-background:   #111827;
    --color-border:       #2D3748;
    --color-input-border: #4A5568;
    --text-primary:       #F9FAFB;
    --text-muted:         #9CA3AF;
    --text-link:          #5BB5F0;
  }
}
[data-theme="dark"] {
  --color-primary:      #4BA3E3;
  --color-secondary:    #5BB5F0;
  --color-surface:      #1A1F2E;
  --color-background:   #111827;
  --color-border:       #2D3748;
  --color-input-border: #4A5568;
  --text-primary:       #F9FAFB;
  --text-muted:         #9CA3AF;
  --text-link:          #5BB5F0;
}
```

### 7.2 Typography

Fonts loaded via Google Fonts with `font-display: swap`. Add to `<head>`:
```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
```

### 7.3 Icon Library

Use **Phosphor Icons** via Iconify. Import in HTML:
```html
<script src="https://cdn.jsdelivr.net/npm/@iconify/iconify@3/dist/iconify.min.js"></script>
```

Size conventions:
- Inline with text: 16px (`width="16" height="16"`)
- Button icons: 20px
- Standalone action icons: 24px
- All icon-only buttons MUST have `aria-label` attribute

Usage: `<span class="iconify" data-icon="ph:plus-bold" style="width:20px;height:20px"></span>`

### 7.4 Component States

**Primary Button (.btn-primary)**
- Default: BG `--color-secondary`, text `#FFFFFF`, border: none, radius `--radius-md`, padding `12px 20px`, font `--text-button` SemiBold, height 40px
- Hover: BG `#2E85C8` (secondary lightened 8%), transition `--transition-base`
- Focus: `box-shadow: 0 0 0 3px rgba(75,163,227,0.4)`, outline: none
- Active: BG `#1A65A0` (secondary darkened 10%), `transform: scale(0.98)`
- Disabled: BG `--color-neutral-200`, text `--text-disabled`, `cursor: not-allowed`, opacity 0.6
- Loading: spinner icon replaces label, button disabled
- Border: 1.5px solid transparent (all button variants use 1.5px for consistency)

**Ghost/Outline Button (.btn-outline)**
- Default: BG transparent, text `--text-primary`, border `1.5px solid --color-input-border`
- Hover: BG `--color-primary-5`, border `--color-secondary`, text `--color-primary`
- Focus: `box-shadow: 0 0 0 3px rgba(33,117,184,0.25)`

**Danger Button (.btn-danger)**
- Default: BG `--color-error`, text `#FFFFFF`, border `1.5px solid transparent`
- Hover: BG `#D32F2F`
- Focus: `box-shadow: 0 0 0 3px rgba(198,40,40,0.4)`

**Form Input (.form-control)**
- Default: BG `#FFFFFF`, border `1.5px solid --color-input-border`, radius `--radius-md`, padding `10px 12px`, text `--text-primary`
- Hover: border `--color-secondary`
- Focus: border `--color-accent`, `box-shadow: 0 0 0 3px rgba(75,163,227,0.2)`, outline: none
- Error: border `--color-error`, error text below at 13px `--color-error`
- Disabled: BG `--color-surface`, text `--text-muted`, `cursor: not-allowed`, opacity 0.7

**Progress Bar**
```css
.progress-track {
  height: 8px;
  background: var(--color-neutral-200);
  border-radius: var(--radius-sm);
  overflow: hidden;
}
.progress-bar-fill {
  height: 100%;
  background: var(--color-secondary);
  border-radius: var(--radius-sm);
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1),
              background-color 0.3s ease;
}
.progress-bar-fill.progress-complete {
  background: var(--color-success);
}
```

---

## PART 8 — API SPECIFICATION

All API endpoints return JSON. All mutations require `csrf_token` field. Standard response format:

```json
// Success
{"success": true, "data": {...}, "message": "..."}

// Error
{"success": false, "error": "ERROR_CODE", "message": "Pesan ramah untuk user"}
```

### Endpoint Table

| Method | Path | Auth | Body / Params | Response |
|---|---|---|---|---|
| POST | /api/auth/login | — | email, password, csrf_token | 200 {redirect} \| 401 \| 429 |
| POST | /api/auth/register | — | name, email, password, confirm_password, website(honeypot), csrf_token | 200 {redirect} \| 422 |
| POST | /api/auth/logout | Auth | csrf_token | 200 {redirect} |
| POST | /api/workspace/create | Auth | name, deadline?, csrf_token | 200 {id, invite_code} \| 422 |
| GET | /api/workspace/share | Owner/Admin | ?workspace_id | 200 {invite_code} \| 403 |
| POST | /api/workspace/regenerate-code | Owner | workspace_id, csrf_token | 200 {new_invite_code} \| 403 |
| POST | /api/workspace/delete (_method=DELETE) | Owner | workspace_id, name_confirm, csrf_token | 200 {redirect} \| 403 |
| POST | /api/workspace/rename (_method=PATCH) | Owner | workspace_id, name, csrf_token | 200 {message} \| 422 \| 403 |
| POST | /api/workspace/update-deadline (_method=PATCH) | Owner | workspace_id, deadline, csrf_token | 200 {message} \| 422 \| 403 |
| POST | /api/workspace/join-request | Auth | invite_code, csrf_token | 200 {status:"pending"} \| 404 \| 409 \| 429 |
| POST | /api/workspace/approve-request | Owner/Admin | request_id, action(approve\|reject), csrf_token | 200 {message} \| 403 |
| POST | /api/member/role-update | Owner/Admin | workspace_id, user_id, role, csrf_token | 200 {message} \| 403 |
| POST | /api/member/kick (_method=DELETE) | Owner/Admin | workspace_id, user_id, csrf_token | 200 {message} \| 403 |
| POST | /api/card/create | Owner/Admin | workspace_id, title, deadline?, csrf_token | 200 {card_id} \| 403 \| 422 |
| POST | /api/card/update (_method=PATCH) | Owner/Admin | card_id, title?, deadline?, csrf_token | 200 {message} \| 403 \| 422 |
| POST | /api/card/delete (_method=DELETE) | Owner/Admin | card_id, csrf_token | 200 {message} \| 403 |
| POST | /api/card/access/grant | Owner/Admin | card_id, user_id, csrf_token | 200 {message} \| 403 \| 409 |
| POST | /api/card/access/revoke (_method=DELETE) | Owner/Admin | card_id, user_id, csrf_token | 200 {message} \| 403 |
| POST | /api/todo/create | Auth+CardAccess | card_id, title, csrf_token | 200 {todo_id, progress_card} \| 403 \| 422 |
| POST | /api/todo/update (_method=PATCH) | Auth+CardAccess | todo_id, title?, status?, csrf_token | 200 {success, progress_card} \| 403 |
| POST | /api/todo/delete (_method=DELETE) | Auth+CardAccess | todo_id, csrf_token | 200 {message, progress_card} \| 403 \| 404 |
| GET | /api/activity/fetch | Auth+Member | ?workspace_id&offset&limit&search&filter_type&date_from&date_to&user_id | 200 {data:[...], meta:{offset,limit,has_more}} |
| POST | /api/activity/clear (_method=DELETE) | Owner | workspace_id, csrf_token | 200 {message} \| 403 |
| POST | /api/profile/update | Auth | name?, avatar_file?, csrf_token | 200 {message, avatar_path?} \| 422 |

### Cross-Workspace Validation Rule (ALL card/todo endpoints)

Before any card or todo operation, backend MUST verify:
```sql
SELECT c.workspace_id FROM cards c WHERE c.id = ?
-- Then verify: result.workspace_id == the workspace the user is authorized for
```
This prevents cross-workspace manipulation via known card/todo IDs.

---

## PART 9 — BUSINESS LOGIC & TRANSACTIONS

### Transaction Scopes

All of the following MUST be wrapped in `BEGIN / COMMIT / ROLLBACK`:

| Operation | Steps |
|---|---|
| Create Workspace | INSERT workspaces + INSERT workspace_members (Owner, Approved, approved_at=NOW()) |
| Delete Workspace | DELETE workspaces (FK CASCADE handles rest) — verify name_confirm server-side first |
| Kick Member | DELETE workspace_members + DELETE card_access (user_id, cards in workspace) + INSERT activity |
| Approve Join Request | UPDATE workspace_members status=Approved, approved_at=NOW() + INSERT activity |
| Revoke Card Access | DELETE card_access + INSERT activity |
| Regenerate Invite Code | UPDATE workspaces SET invite_code + UPDATE workspace_members SET status='Rejected' WHERE status='Pending' |

### Authorization Rules

1. `workspace_members.status = 'Approved'` is required for all workspace access (not just Pending/Rejected)
2. Todo CRUD: user must be Owner OR Admin OR in `card_access` for that card
3. Role changes: Owner/Admin can change Member↔Admin. No one can change Owner. Owner cannot change own role.
4. Kick: Owner/Admin can kick Member or Admin. No one can kick Owner.
5. `card.workspace_id` must match the authorized workspace on every card operation.
6. Invite code view: Owner/Admin only via `/api/workspace/share`

### Race Condition Mitigations

- Invite code generation: loop with `usleep(1000)` between retries, max 3
- Progress calculation: single atomic `SELECT COUNT()` query via `ProgressCalculator` — never split into two queries
- Double-submit: disabled button state (frontend) + check for duplicate pending request (backend: 409 if Pending record already exists)

---

## PART 10 — JAVASCRIPT MODULE ARCHITECTURE

All JS files use ES Modules (`<script type="module" src="/js/main.js">`).

### Module Structure

```javascript
// /public/js/modules/toast.js
export function showToast(message, type = 'info') { /* ... */ }
export function showRetryToast(message, retryFn) { /* ... */ }

// /public/js/modules/modal.js
export function openModal(id) { /* focus trap, animation */ }
export function closeModal(id) { /* ... */ }
export function openConfirmModal({ title, body, onConfirm }) { /* ... */ }

// /public/js/modules/todo.js
export function initTodoList(cardId) { /* ... */ }
// Inline delete confirmation:
export function handleTodoDeleteClick(todoEl, todoId) {
  // Replace trash icon with [✓ Hapus] + [✗ Batal] inline
  // Auto-cancel after 5000ms
}

// /public/js/modules/card.js
export function updateProgressBar(cardId, progress) {
  const fill = document.querySelector(`[data-card-id="${cardId}"] .progress-bar-fill`);
  fill.style.width = progress + '%';
  fill.classList.toggle('progress-complete', progress === 100);
}

// /public/js/modules/activity.js
export function initActivitySearch(workspaceId) { /* debounce 500ms */ }
export function loadMore(workspaceId, offset) { /* fetch, render, toggle Load More */ }
export function refreshActivity(workspaceId) { /* AJAX fetch, not full reload */ }

// /public/js/modules/sidebar.js
export function initSidebar() {
  // Load accordion state from sessionStorage
  // Toggle collapse/expand
  // Mobile hamburger handler
}
```

### Global Fetch Wrapper

```javascript
// /public/js/modules/api.js
export async function apiPost(path, body) {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  try {
    const res = await fetch(path, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ...body, csrf_token: csrf, _method: body._method })
    });
    const data = await res.json();
    if (!data.success) throw { code: data.error, message: data.message, status: res.status };
    return data;
  } catch (err) {
    if (err.code) throw err; // Application error — already structured
    throw { code: 'NETWORK_ERROR', message: 'Koneksi terputus. Coba refresh halaman.', status: 0 };
  }
}
```

All fetch calls use this wrapper. On NETWORK_ERROR: `showRetryToast(message, () => apiPost(path, body))`.

---

## PART 11 — RESPONSIVE DESIGN

### Breakpoints

```css
/* Mobile first */
/* xs: 0–319px — edge case very small devices */
/* sm: 320px–639px — mobile */
@media (min-width: 640px)  { /* tablet */ }
@media (min-width: 1024px) { /* desktop */ }
@media (min-width: 1280px) { /* large desktop — 4-col grid */ }
@media (min-width: 1440px) { /* XL desktop — larger h1 */ }
```

### Mobile-Specific Rules

- Sidebar: hidden by default, shown as overlay on hamburger click (z-index: 100). No mini-mode on mobile.
- Touch targets: minimum 44×44px hit area for all interactive elements (use padding, not just visual size)
- Card detail modal → full-screen on mobile (`width: 100vw; height: 100vh; border-radius: 0`)
- Card context menu → bottom sheet (not dropdown)
- Virtual keyboard awareness: when text input is focused inside a modal, ensure modal scrolls to show the input above keyboard. Use `scrollIntoView({behavior:'smooth'})` on focus.
- `env(safe-area-inset-bottom)` padding on bottom fixed elements (iOS notch/home bar)

### No Overflow Guarantee

- Sidebar mini + 1-column grid: content width at 320px = 320 - 0 (no sidebar on mobile) = 320px ✓
- Card with long title: `overflow: hidden; text-overflow: ellipsis; white-space: nowrap` on card title
- Avatar overflow: max 3 visible + "+N" badge
- Breadcrumb overflow: `overflow: hidden; white-space: nowrap; text-overflow: ellipsis` on path segments

---

## PART 12 — SECURITY CHECKLIST (PRE-DEPLOY)

| # | Item | Requirement |
|---|---|---|
| 1 | Document root → /public only | ✓ Required |
| 2 | .env outside /public | ✓ Required |
| 3 | HTTPS + `session.cookie_secure=1` | ✓ Required in production |
| 4 | `display_errors=Off` in production | ✓ Required |
| 5 | PDO Prepared Statements everywhere | ✓ Required — zero raw queries |
| 6 | CSRF token in all mutations | ✓ Session-scoped, rotated on login/logout |
| 7 | BCRYPT cost 12 | ✓ Via $_ENV['BCRYPT_COST'] |
| 8 | `session_regenerate_id(true)` after login | ✓ Required |
| 9 | `login_attempts` table for rate limiting | ✓ Required |
| 10 | IP-based cooldown for join request (reuses login_attempts pattern) | ✓ Required |
| 11 | Avatar: MIME check + `getimagesize()` double-check | ✓ Required |
| 12 | Avatar: `bin2hex(random_bytes(16))` filename | ✓ Required — not uniqid() |
| 13 | uploads/avatars: PHP execution disabled | ✓ `.htaccess: php_flag engine off` |
| 14 | `htmlspecialchars()` on all user output | ✓ Required |
| 15 | Division-by-zero guard in progress calc | ✓ Via ProgressCalculator |
| 16 | Transactions: workspace delete, kick, approve, revoke, regenerate-code | ✓ Required |
| 17 | UNIQUE(workspace_id, user_id) in workspace_members | ✓ In schema |
| 18 | UNIQUE(card_id, user_id) in card_access | ✓ In schema |
| 19 | `/api/workspace/share` requires Owner/Admin auth | ✓ Required |
| 20 | Cross-workspace card/todo validation | ✓ Required |
| 21 | Security headers (X-Frame, X-Content-Type, CSP, Referrer) | ✓ Required |
| 22 | Membership NEVER cached in session — always DB fetch | ✓ Required |
| 23 | Owner cannot be kicked (backend enforced) | ✓ Required |
| 24 | Workspace delete: server-side name confirmation check | ✓ Required |
| 25 | Mobile responsive test: 320px, 640px, 1024px, 1280px, 1440px | ✓ Required |

---

## PART 13 — KNOWN CONSTRAINTS & FUTURE WORK

Document these as comments in code — do not implement now:

1. **No account deletion** — Owner with active workspaces cannot be deleted. Future: transfer ownership flow or workspace batch-delete.
2. **No SSO/OAuth** — `SameSite=Strict` will break OAuth redirects if added in future. Document this constraint.
3. **No real-time updates** — Sidebar refreshes only on page navigation. If invited to workspace while viewing another, sidebar won't update until navigation. This is a known UX limitation, not a bug.
4. **No multi-server session** — Sessions are file-based. Multi-server deployment requires session storage migration to Redis or DB sessions.
5. **Activity log partitioning** — For 10,000+ workspaces with high log volume, consider `PARTITION BY RANGE` on workspace_id in future.
6. **API versioning** — All endpoints are unversioned. Document: future breaking changes require `/api/v2/...` paths.

---

*TRACEON — Implementation Prompt V8. All requirements are final, revised, and actionable. Begin Vibecode implementation directly from this document.*
