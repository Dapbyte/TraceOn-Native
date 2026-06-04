# PHASE-3 — Cards & Card Access (STEP-28..30)
**Prerequisites:** PHASE-2 complete.
**Purpose:** Card grid with explicit access grants. Read-only transparency for Members without access.
**After PHASE-3:** Card grid fully functional; todos do not exist yet.

---

## STEP-28 — CardModel
**Objective:** Implement `App\Models\CardModel` including access methods.
**Outputs:** Methods —
- `findById(int $id): ?array`
- `listForWorkspace(int $workspaceId): array`  ← includes todo counts for progress display
- `create(string $title, ?string $deadline, int $workspaceId, int $createdBy): int`
- `update(int $id, string $title, ?string $deadline): void`
- `delete(int $id): void`
- `insertAccess(int $cardId, int $userId, int $grantedBy): void`
- `deleteAccess(int $cardId, int $userId): void`
- `accessUserIds(int $cardId): array`  ← returns user IDs with access + their basic info
- `deleteAccessForUserInWorkspace(int $userId, int $workspaceId): void`  ← used in kick transaction
- `userHasAccess(int $cardId, int $userId): bool`
- `getWorkspaceId(int $cardId): ?int`  ← used for cross-workspace check
**Validation:** Unit tests covering CRUD + access grant/revoke + cascade behavior.

## STEP-29 — CardController
**Objective:** Card endpoints with cross-workspace check (XWS) and activity logging.
**Outputs:** Methods —
- `create()`: requireAuth + requireWorkspaceMember($wid,'Admin') + CSRF; validate title (3-100, trim), deadline (DATE or null via DateTime::createFromFormat); INSERT cards; log 'card_create'; return {success:true,data:{card_id:N}}
- `update()`: requireAuth + requireWorkspaceMember($wid,'Admin') + CSRF; XWS check: CardModel::getWorkspaceId($cardId) === authorized workspace; validate title/deadline; UPDATE; log 'card_edit'
- `delete()`: Owner/Admin; CSRF; XWS check; DELETE (FK cascade removes todos + card_access); log 'card_delete'
- `grantAccess()`: Owner/Admin; CSRF; XWS check; check user is Approved member; INSERT card_access (409 on duplicate UNIQUE); log 'access_grant'; return {success:true,message:"..."}
- `revokeAccess()`: Owner/Admin; CSRF; XWS check; transaction: DELETE card_access + log 'access_revoke'; idempotent (200 with note if already revoked)

**XWS check pattern (use in every card + todo op):**
```php
$card = CardModel::findById($cardId);
if (!$card || $card['workspace_id'] !== $workspaceId) {
    $this->json(['error'=>'FORBIDDEN'], 403); exit;
}
```
**Validation:** PHASE-3 DoD items 1–6.

## STEP-30 — Card Grid UI and Read-Only Transparency
**Objective:** Render card grid on workspace page; member avatar overflow; read-only indicator; ellipsis menu.
**Outputs:**
- Card grid in `pages/workspace.php`: responsive CSS Grid breakpoints (≥1280px: 4 col; 1024–1279: 3 col; 640–1023: 2 col; <640: 1 col; gap 24/24/16/12px)
- Card component (`.card`): BG white, border 1px solid --color-border, radius --radius-lg, shadow --shadow-sm, padding --space-4. Hover: border rgba(33,117,184,0.5), shadow --shadow-md, translateY(-2px), transition: all 0.25s ease-out
- Card content: title (--text-h3, SemiBold, ellipsis on overflow), progress bar (8px track), progress ratio text ("7/10 selesai"), deadline badge (warning style if <3 days), member avatars max 3 (24px circles) + "+N" overflow badge, ellipsis (⋮) button
- **Read-only indicator:** "Hanya Baca" badge on card — shown when user has no card_access AND role=Member. CRUD buttons absent from HTML entirely for these users.
- **Ellipsis context menu (desktop):** dropdown with: Edit Card, Delete Card, Kelola Akses
- **Ellipsis context menu (mobile):** bottom sheet (slides up from bottom, z-index above sidebar)
- `public/js/modules/card.js`: `initCardGrid()` (menu wiring, ellipsis show/hide), `updateProgressBar(cardId, progress)` (updates fill width + toggles class `.progress-complete` at 100%; used by Phase 4)
- Progress bar CSS (in components.css):
```css
.progress-track { height:8px; background:var(--color-neutral-200); border-radius:var(--radius-sm); overflow:hidden; }
.progress-bar-fill { height:100%; background:var(--color-secondary); border-radius:var(--radius-sm); transition:width 0.3s cubic-bezier(0.4,0,0.2,1),background-color 0.3s ease; }
.progress-bar-fill.progress-complete { background:var(--color-success); }
```
**Validation:**
- Member without card_access sees card but no CRUD buttons in rendered HTML; backend additionally rejects forged mutation (403)
- XWS forgery rejected with 403
- Card titles longer than column width truncate with ellipsis
- Grid degrades to 1 column at <640px

---

## PHASE-3 Definition of Done

1. Card create/update/delete restricted to Owner/Admin; Member without rights → 403 FORBIDDEN
2. XWS verified: mutate card whose workspace_id ≠ authorized workspace → 403, no rows changed
3. Read-only transparency: Member without card_access sees card (title/progress/deadline/avatars) but no CRUD buttons in HTML; backend rejects forged direct API calls
4. Grant access: 409 on duplicate; activity row written. Revoke: idempotent (200 if already revoked)
5. Card delete cascades through todos and card_access; activities.card_id rows survive (no FK, intentional)
6. Card titles overflow truncated with ellipsis; grid degrades to 1 column at <640px; responsive breakpoints correct
