# PHASE-2 — Workspace & Membership (STEP-23..27)
**Prerequisites:** PHASE-1 complete.
**Purpose:** Workspace CRUD, invite codes, join/approve/reject/role/kick. Sidebar SSR.
**After PHASE-2:** Full social fabric exists; cards/todos do not yet.

---

## STEP-23 — WorkspaceModel
**Objective:** Implement `App\Models\WorkspaceModel`.
**Outputs:** Methods —
- `findById(int $id): ?array`
- `findByInviteCode(string $code): ?array`
- `insert(string $name, ?string $deadline, string $inviteCode, int $ownerId): int`
- `rename(int $id, string $name): void`
- `updateDeadline(int $id, ?string $deadline): void`
- `updateInviteCode(int $id, string $code): void`
- `delete(int $id): void`
- `listOwned(int $userId): array`
- `listJoined(int $userId): array`  ← workspaces where user is Member/Admin (Approved)
- `getInviteCode(int $id): ?string`
- `generateUniqueInviteCode(): string` ← strtoupper(bin2hex(random_bytes(4))); max 3 retries with usleep(1000); throws on exhaust
**Validation:** Unit tests covering insert, rename, deadline update, list (owned vs joined), invite-code collision-retry.

## STEP-24 — WorkspaceController (STEP-26 adds join/approve)
**Objective:** Workspace lifecycle endpoints (no membership ops yet).
**Outputs:** Methods —
- `dashboard()`: requireAuth; fetch listOwned + listJoined; render pages/dashboard.php
- `show(int $id)`: requireAuth + requireWorkspaceMember($id); fetch workspace, members (for Anggota tab scaffold), progress; render pages/workspace.php
- `create()`: requireAuth + CSRF validate; validate name (1-100, trim); transaction: generateUniqueInviteCode() + INSERT workspaces + INSERT workspace_members (Owner, Approved, approved_at=NOW()); return {success:true,data:{id:N,invite_code:"XXXXXXXX"}}
- `shareCode()`: requireAuth + requireWorkspaceMember($id, 'Admin'); GET only; return {invite_code}
- `regenerateCode()`: requireAuth + requireWorkspaceMember($id, 'Owner'); CSRF; transaction: generateUniqueInviteCode() + UPDATE workspaces SET invite_code + UPDATE workspace_members SET status='Rejected' WHERE status='Pending' + ActivityLogger::log('invite_regenerate'); return {new_invite_code}
- `rename()`: Owner only; validate 3-100 chars trim; UPDATE workspaces; log 'workspace_rename'; return {message}
- `updateDeadline()`: Owner only; validate DATE via DateTime::createFromFormat('Y-m-d'); UPDATE workspaces; return {message}
- `delete()`: Owner only; CSRF; validate name_confirm server-side === workspace.name; transaction: DELETE FROM workspaces (FK CASCADE); return {success:true,redirect:"/dashboard"}
**Validation:** PHASE-2 DoD items 1, 2, 7, 8.

## STEP-25 — Sidebar SSR
**Objective:** Render sidebar on every authenticated page.
**Outputs:**
- `app/Views/partials/sidebar.php`: TraceOn logo, "+ Workspace Baru" button, "+ Join Workspace" button, accordion "Dibagikan (N)" (listJoined), accordion "Workspace (N)" (listOwned), profile footer (avatar, name, profile button)
- `public/js/modules/sidebar.js`: `initSidebar()` — load accordion state from sessionStorage (key: 'traceon_accordion_state'), toggle collapse/expand (260px → 72px, text opacity transition), mobile hamburger → overlay sidebar
- Sidebar collapse CSS: `transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1)` on sidebar; `transition: opacity 0.15s ease-out` on text/labels
**Validation:** Sidebar shows correct counts after create/join; hamburger overlay works on mobile (320px test); accordion state persists across page navigations (sessionStorage).

## STEP-26 — Membership Endpoints (join/approve/role/kick)
**Objective:** Full FR-09, FR-10, FR-12, FR-13, FR-14a behavior.
**Outputs in WorkspaceController:**
- `joinRequest()`: requireAuth + CSRF; normalize invite_code: strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim($input))); find workspace by invite_code (404 if not found); check existing membership (409 "Kamu sudah bergabung" if Approved; 409 "Permohonanmu sedang menunggu persetujuan" if Pending); IP cooldown check (3 failures → 429 {error:"COOLDOWN",retry_after:30}); INSERT workspace_members (role=Member, status=Pending); log 'member_join'
- `approveRequest()`: requireAuth + requireWorkspaceMember($wid,'Admin') + CSRF; action=approve: transaction UPDATE status=Approved + approved_at=NOW() + log 'member_approve'; action=reject: UPDATE status=Rejected + log 'member_reject' (NO delete)

**Outputs in MemberController:**
- `updateRole()`: requireAuth + requireWorkspaceMember($wid,'Admin') + CSRF; role change rules: Owner/Admin can change Member↔Admin; no one can change Owner; no one can promote to Owner; actor cannot change own role; UPDATE workspace_members SET role + log 'role_change'
- `kick()`: requireAuth + requireWorkspaceMember($wid,'Admin') + CSRF; target must not be Owner (403); transaction: DELETE workspace_members WHERE workspace_id=? AND user_id=? + DELETE card_access WHERE user_id=? AND card_id IN (SELECT id FROM cards WHERE workspace_id=?) + log 'member_kick'

**Validation:** PHASE-2 DoD items 3, 4, 5, 6, 9.

## STEP-27 — Members Tab + Settings Panel UI
**Objective:** Render Members tab table and Settings panel in pages/workspace.php.
**Outputs:**
- **Members tab table:** columns: Avatar (32px) | Name | Role (dropdown for Owner/Admin to change) | Status (badge) | Actions
  - Action rules per row: Owner/Admin viewing Member → "Kick" button; Owner viewing Admin → "Turunkan ke Member" + "Kick"; Owner/Admin viewing Pending → "Setujui" (green) + "Tolak" (red); own row → "Kamu" label only; Owner row → no actions
- **Settings panel** (Owner): Rename field, Deadline update, Invite code display (readonly + "Salin Code" button) + "Regenerate Code" button, Danger Zone (bg #FFEBEE, border #EF9A9A) with "Hapus Workspace Permanen"
- **Settings panel** (Admin): Display invite_code (readonly) + "Salin Code" only — no regenerate, no danger zone
- `app/Views/partials/modal-confirm.php`: destructive confirmation modal with input field; submit disabled until input === resource name (client-side); type-to-confirm pattern
**Validation:** Manual matrix walk-through: every (viewer role × target role) combination shows only allowed actions.

---

## PHASE-2 Definition of Done

1. Create-workspace: returns {id, invite_code}; inserts both workspaces and workspace_members (Owner, Approved) atomically. On collision, retry succeeds; on exhaustion rolls back → 500.
2. Sidebar lists owned and joined workspaces correctly (SSR, no AJAX). Accordion state persists in sessionStorage.
3. Join-request: normalizes input; 404 unknown code; 409 already Pending/Approved with Bahasa message; 3 failures from IP → 429 with retry_after=30.
4. Approve: transitions Pending→Approved, sets approved_at; NOT deleted. Both approve/reject log activity.
5. Role updates: Owner/Admin can change Member↔Admin; nobody can change Owner; no one can promote to Owner; actor cannot change own role — server enforces (not just UI).
6. Kick: target not Owner enforced; within one transaction deletes workspace_members + card_access + inserts activity row.
7. Regenerate: Owner only; updates invite_code + mass-rejects Pending in same transaction; emits activity.
8. Workspace delete: Owner only; requires correct name_confirm server-side; FK cascade verifiably removes children; redirects to /dashboard.
9. Membership fetched from DB on every protected request — confirm: kick user while session alive → next request from that user yields 403.
