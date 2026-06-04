# PHASE-4 — Todos & Progress (STEP-31..33)
**Prerequisites:** PHASE-3 complete.
**Purpose:** Todo CRUD with status, live progress recalc, inline delete confirmation.
**After PHASE-4:** Per-card and per-workspace progress correct and live.

---

## STEP-31 — TodoModel and TodoController
**Objective:** Full todo CRUD with XWS, transactions, activity logging.
**Outputs:**

**TodoModel methods:**
- `findById(int $id): ?array`
- `listForCard(int $cardId): array`
- `create(string $title, int $cardId, int $createdBy): int`  ← status defaults to 'pending'
- `updateTitle(int $id, string $title): void`
- `updateStatus(int $id, string $status): void`  ← status in ['pending','in_progress','done'] — validate enum
- `delete(int $id): void`  ← hard DELETE, no soft delete
- `getCardId(int $todoId): ?int`  ← used for XWS check (get card → get workspace)

**TodoController methods:**
- `create()`: requireAuth + CSRF; XWS check (via todo's card_id → card.workspace_id); check card-access auth (Owner OR Admin OR card_access row); validate title (1-255, trim); INSERT todos; recalculate progress = ProgressCalculator::forCard($cardId); log 'todo_create'; return {success:true,data:{todo_id:N,progress_card:N}}
- `update()`: requireAuth + CSRF; XWS check; card-access auth; validate title? and/or status? (enum check); UPDATE todos; recalculate progress; log 'todo_edit' (title change) or 'todo_status' (status change); return {success:true,data:{progress_card:N}}
- `delete()`: requireAuth + CSRF; XWS check; card-access auth; hard DELETE FROM todos WHERE id=?; recalculate progress; log 'todo_delete'; return {success:true,message:"Todo berhasil dihapus",data:{progress_card:N}}

**Complete ProgressCalculator:**
```php
// forCard(int $cardId): int — 0-100
// SELECT COUNT(*) AS total, SUM(CASE WHEN status='done' THEN 1 ELSE 0 END) AS done FROM todos WHERE card_id=?
// if total===0 return 0; return (int)round((done/total)*100)

// forWorkspace(int $workspaceId): float — average of all cards
// if no cards return 0.0; sum each card's forCard(); return round(sum/count, 1)
```

**Card-access auth check pattern (use in every todo op):**
```php
$membership = MemberModel::getMembership($_SESSION['user_id'], $workspaceId); // already fetched
if ($membership['role'] === 'Owner' || $membership['role'] === 'Admin') {
    // allowed
} elseif (!CardModel::userHasAccess($cardId, $_SESSION['user_id'])) {
    $this->json(['error'=>'FORBIDDEN'], 403); exit;
}
```

**Validation:** PHASE-4 DoD items 1–3, 5, 8, 9.

## STEP-32 — Todo UI (inline delete confirm, status, filters)
**Objective:** Full todo UI in card detail view.
**Outputs:**

**Todo list inside card detail (within workspace page):**
- Card detail opens as a panel or modal within the workspace page
- URL state: on open → `history.pushState(null, '', '/workspace/{id}/card/{card_id}')`. On close → restore previous URL
- Todo item states:
  - `pending`: default text, no badge
  - `in_progress`: amber badge "Sedang"
  - `done`: strikethrough text, opacity 60%, checkbox filled --color-success + white checkmark SVG
- Custom checkbox: 18×18px, --radius-sm. Check transition: transform scale(0.1→1) 150ms
- Done transition: `transition: text-decoration 0.2s, opacity 0.25s ease-out`; text strikethrough + opacity → 0.6

**Inline delete confirmation (no native confirm() — RULE-11):**
1. User clicks trash icon → replace with `[✓ Hapus]` + `[✗ Batal]` inline on same row
2. Row BG: subtle `rgba(198,40,40,0.06)`
3. `[✓ Hapus]` → fire DELETE request
4. `[✗ Batal]` OR Escape key → revert to normal row
5. **Auto-cancel after 5000ms** if no action
6. On successful delete: `opacity: 0` transition (250ms) → `element.addEventListener('transitionend', () => element.remove(), {once:true})`

**Status `<select>`:** options pending/in_progress/done; on change → fire POST to /api/todo/update; update progress bar via card.js::updateProgressBar

**Filter buttons (client-side DOM only, no new fetch):** "Semua" / "Selesai" / "Dalam Proses" / "Belum" — toggle filter by adding/removing class on todo rows

**Enter key:** submits new-todo form (same as "Simpan" button)

**`public/js/modules/todo.js`:**
```js
export function initTodoList(cardId) { /* attach all event handlers */ }
export function handleTodoDeleteClick(todoEl, todoId) {
    // Replace trash icon with [✓ Hapus] + [✗ Batal]
    // Auto-cancel after 5000ms (store timeout ref, clear on any action)
    // Escape key listener (scoped to this todo row)
}
```

**Double-submit safety:** disable button on submit; re-enable on response (success or error).

**Validation:** PHASE-4 DoD items 4, 6, 7. Manual responsive check.

## STEP-33 — Test Harness Wiring (PHPUnit, recommended)
**Objective:** Wire PHPUnit dev-only with test database.
**Outputs:**
- `phpunit.xml` with test database config (separate from production DB)
- `tests/bootstrap.php`: fails fast unless APP_ENV=testing
- `tests/` skeleton: base test case, fixture builders for User/Workspace/Member/Card/Todo
- Unit tests for: `ProgressCalculator` (card with 0/partial/100 todos, workspace average), `CsrfManager` (generate idempotent, validate match/mismatch, rotate), `FileUploadHelper` (bad MIME, oversized, valid image), validators
**Risks:** Test DB pointed at production — mitigated by bootstrap fail-fast.
**Validation:** `vendor/bin/phpunit` green; no failing tests.

---

## PHASE-4 Definition of Done

1. Todo CRUD requires Owner OR Admin OR existing card_access row; non-eligible Members → 403
2. XWS verified for every todo endpoint (cards.workspace_id equals authorized workspace)
3. Hard delete: row removed by DELETE FROM todos; no deleted_at column; subsequent read → 404
4. Inline confirmation: trash icon swaps to [✓ Hapus] [✗ Batal]; Escape cancels; 5s without action reverts; on confirm row fades to opacity:0 and removed on transitionend. Native confirm() ABSENT (RULE-11)
5. Progress: response payload progress_card matches fresh ProgressCalculator::forCard post-mutation. Bar fills to percent; class progress-complete toggles at 100. Per-workspace average = ProgressCalculator::forWorkspace
6. Status select change fires PATCH-equivalent POST; updates progress bar without page reload
7. Filter buttons client-side only; no extra fetches
8. Enter key in new-todo input submits form (same as Simpan button)
9. Double-submit safety: button disabled during in-flight; rapid clicks do not create duplicate todos

---

## Progress Bar Implementation Reminder

```html
<!-- In card partial / card detail -->
<div class="progress-track">
  <div class="progress-bar-fill <?= $progress >= 100 ? 'progress-complete' : '' ?>"
       style="width: <?= $progress ?>%"
       data-card-id="<?= $card['id'] ?>">
  </div>
</div>
<span class="text-sm text-muted"><?= $doneTodos ?>/<?= $totalTodos ?> selesai</span>
```

```js
// card.js
export function updateProgressBar(cardId, progress) {
    const fill = document.querySelector(`[data-card-id="${cardId}"].progress-bar-fill`);
    if (!fill) return;
    fill.style.width = progress + '%';
    fill.classList.toggle('progress-complete', progress === 100);
}
```
