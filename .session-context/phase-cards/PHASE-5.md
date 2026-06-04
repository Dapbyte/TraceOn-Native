# PHASE-5 — Activity Log & Final Hardening (STEP-34)
**Prerequisites:** PHASE-4 complete (all logged operations must exist before Activity UI finalizes).
**Purpose:** Audit trail, SSR + AJAX pagination, search, filter, clear. Final UI polish. Security checklist.
**After PHASE-5:** Deploy-ready.

---

## STEP-34 — Activity Model, Controller, UI, and Final Hardening

### ActivityModel
**Outputs:** Methods —
- `insert(int $workspaceId, ?int $userId, ?int $cardId, string $activityType, ?string $oldValue, ?string $newValue, string $actionText): void`  ← called by ActivityLogger
- `list(int $workspaceId, int $limit=50, int $offset=0): array`  ← sorted DESC by created_at
- `search(int $workspaceId, string $query, int $limit=50, int $offset=0): array`
  - If strlen($query) >= 3: use FULLTEXT MATCH(action) AGAINST(? IN BOOLEAN MODE)
  - Else: LIKE '%query%' (still parameterized — NEVER concatenate into MATCH or LIKE)
  - Always scoped WHERE workspace_id=?
- `listFiltered(int $workspaceId, array $filters, int $limit=50, int $offset=0): array`
  - filters: type[], date_from, date_to, user_id
  - Build WHERE clauses dynamically but bind all params via array; never concatenate user values
- `count(int $workspaceId, string $query='', array $filters=[]): int`  ← used for has_more calculation
- `deleteAll(int $workspaceId): int`  ← returns count of deleted rows

### ActivityLogger (complete)
```php
// app/Helpers/ActivityLogger.php
// Caller owns the transaction — ActivityLogger::log() must NOT open its own TX
class ActivityLogger {
    public static function log(int $workspaceId, ?int $userId, ?int $cardId,
                               string $activityType, ?string $oldValue, ?string $newValue,
                               string $actionText): void {
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO activities
            (workspace_id, user_id, card_id, activity_type, old_value, new_value, action)
            VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$workspaceId, $userId, $cardId, $activityType, $oldValue, $newValue, $actionText]);
    }
}
```

### ActivityController
**Outputs:**
- `fetch()`: requireAuth + requireWorkspaceMember($wid,'Member'); GET params: workspace_id, offset (default 0), limit (default 50, max 100), search, filter_type[], date_from, date_to, user_id; call appropriate model method; return:
  ```json
  {"success":true,"data":[...],"meta":{"offset":50,"limit":50,"has_more":true}}
  ```
  "Load More" uses offset pagination (append mode). Search REPLACES content (not appended) — client handles this distinction.
- `clear()`: requireAuth + requireWorkspaceMember($wid,'Owner') + CSRF; transaction: ActivityModel::deleteAll($wid) + ActivityLogger::log(..., 'log_clear', ...); return {success:true,message:"Log aktivitas berhasil dihapus"}

### Activity Tab UI (in pages/workspace.php)
**Tab:** "Log Aktivitas" — lazy loads via AJAX on first click, then caches in JS memory (same pattern as Members tab).
**Layout:** search input + "Cari" button + "Refresh" button + filter popup trigger | activity feed | "Muat Lebih Banyak" button | Owner-only "Hapus Semua Log" button
**Activity row:** user avatar (32px) | user name (bold) | action text | timestamp (relative) | activity_type badge

### activity.js
```js
export function initActivitySearch(workspaceId) {
    // debounce 500ms after last keypress
    // on fire: GET /api/activity/fetch?workspace_id=N&search=query&offset=0&limit=50
    // REPLACE current activity list (not append)
    // show empty state if data=[]: "Tidak ada hasil untuk '{query}'"
    // show clear-search link to re-fetch without search param
}

export function loadMore(workspaceId, offset) {
    // GET /api/activity/fetch?workspace_id=N&offset=N&limit=50
    // APPEND results to existing list
    // hide "Muat Lebih Banyak" if has_more=false
}

export function refreshActivity(workspaceId) {
    // GET /api/activity/fetch?workspace_id=N&offset=0&limit=50
    // REPLACES current activity list (not full page reload)
}

export function formatRelative(isoTimestamp) {
    // Exact thresholds:
    // < 1 minute: "Baru saja"
    // 1-59 minutes: "N menit lalu"
    // 1-23 hours: "N jam lalu"
    // 1-6 days: "N hari lalu"
    // >= 7 days: "DD MMM YYYY HH:mm" (absolute)
}
```

### Filter Popup
State maintained in JS object only (not persisted to localStorage/sessionStorage).
```js
let filterState = {
    types: [],        // [] means all types
    date_from: null,
    date_to: null,    // default: last 7 days applied server-side if both null
    user_id: null
};
```
Multi-select checkboxes for activity_type; date range inputs (from/to); user dropdown.

### Skeleton Loading
Apply `.skeleton-shimmer` to card grid first paint (before JS loads card data):
```css
/* Already defined in DESIGN.md / components.css */
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
Skeleton cards replace real cards during loading; replaced once data arrives.

### Empty States (per spec §6.7)
| Surface | Illustration + Text | CTA |
|---|---|---|
| Dashboard — no workspaces | "Buat workspace pertamamu untuk mulai" | "+ Buat Workspace" button |
| Workspace — no cards | "Belum ada card di workspace ini" | "+ Tambah Card" button |
| Activity log — empty | "Belum ada aktivitas di workspace ini" | — |
| Search — no match | "Tidak ada hasil untuk '{query}'" | Clear search link |
| Members — no pending | "Tidak ada permohonan yang menunggu" | — |

### Toast System
Position: `position:fixed; bottom:24px; right:24px; z-index:var(--z-toast)`
Animation: `toastSlideIn 0.35s cubic-bezier(0.16,1,0.3,1)` (from translateX(120%) to translateX(0))
Auto-dismiss: Success/Info → 4s; Warning → 6s; Error → 8s
Max 5 visible; new toast on top; old shift down.
Failed request toast: includes "Coba Lagi" link → retriggers last failed fetch.
```js
// toast.js
export function showToast(message, type='info') { /* ... */ }
export function showRetryToast(message, retryFn) { /* ... */ }
```

### Full Responsive Pass (at 320, 640, 1024, 1280, 1440px)
- Touch targets: minimum 44×44px hit area for all interactive elements
- Mobile (< 768px): sidebar hidden, hamburger shows overlay sidebar (z-index: 100)
- Card detail modal → full-screen on mobile (100vw × 100vh, border-radius: 0)
- Card context menu → bottom sheet on mobile (not dropdown)
- Virtual keyboard: on text input focus inside modal, `scrollIntoView({behavior:'smooth'})`
- `env(safe-area-inset-bottom)` padding on bottom fixed elements (iOS notch)
- Breadcrumb overflow: `overflow:hidden; white-space:nowrap; text-overflow:ellipsis`
- Card title overflow: `overflow:hidden; text-overflow:ellipsis; white-space:nowrap`

### Security Checklist Pass
Walk SEC-01..SEC-25 in `.session-context/sec-checklist.md` item by item on staging environment.
All 25 must confirm pass before deploy.

### Modal System Final Check
Z-index hierarchy: Sidebar 100 | Sticky nav 200 | Modal backdrop 300 | Modal content 400 | Toast 500
Modal animation: backdrop fadeIn 0.2s; content scale(0.95→1) + translateY(10px→0) + opacity(0→1) 0.3s cubic-bezier(0.16,1,0.3,1). No spring/bounce easing.
Close triggers: ESC key OR click backdrop. Focus trap: Tab cycles within modal only.
Mobile modals: full-screen (100vw × 100vh).

---

## PHASE-5 Definition of Done

1. SSR renders last 50 activities on workspace page load. "Muat Lebih Banyak" returns next 50; hides when has_more=false.
2. Search debounces 500ms; REPLACES server-rendered content (not appended); FULLTEXT for ≥3-char queries; LIKE fallback for shorter (both workspace-scoped, both parameterized).
3. Filter popup state in JS only; selections produce correct query params; default range last 7 days (server-side).
4. Owner-only "Clear log": deletes all rows + inserts single log_clear activity in same transaction.
5. Relative timestamp formatter exact thresholds: <1min→"Baru saja"; 1-59m→"N menit lalu"; 1-23h→"N jam lalu"; 1-6d→"N hari lalu"; ≥7d→"DD MMM YYYY HH:mm".
6. All 25 SEC-01..SEC-25 items verified pass on staging before deploy.
7. No horizontal overflow at any breakpoint; sidebar hamburger works; modals full-screen mobile; card context menu = bottom sheet mobile.
8. Manual end-to-end scenario passes on staging: register → login → create workspace → invite member → approve → create card → grant access → create todos → complete todos → verify progress 100% → check activity log → clear log → delete workspace.

---

## Activity Log SSR Pattern
```php
// In WorkspaceController@show — Activity tab initial SSR (last 50)
$activities = ActivityModel::list($workspaceId, 50, 0);
// Pass to view; view renders rows SSR
// "has_more" = count > 50 (fetch count separately)
// JS activity.js caches in memory after first AJAX load
```
