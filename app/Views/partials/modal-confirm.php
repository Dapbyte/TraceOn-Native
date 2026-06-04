<?php
// Destructive confirmation modal markup — JS (modal.js) controls open/close
// Used for workspace delete (type-to-confirm) and other destructive actions
// Never uses native confirm() — RULE-11
?>
<div class="modal-backdrop" id="modal-confirm-backdrop" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="modal-confirm-title">
    <div class="modal" id="modal-confirm">
        <h2 class="modal-title" id="modal-confirm-title"></h2>
        <p id="modal-confirm-body" class="text-muted" style="margin-bottom:16px;"></p>

        <!-- Type-to-confirm input (shown for destructive operations) -->
        <div id="modal-confirm-input-group" class="form-group" style="display:none;margin-bottom:16px;">
            <label class="form-label" id="modal-confirm-input-label">Ketik nama untuk konfirmasi:</label>
            <input type="text" class="form-control" id="modal-confirm-input" autocomplete="off" spellcheck="false">
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-outline" id="modal-confirm-cancel">Batal</button>
            <button type="button" class="btn btn-danger" id="modal-confirm-ok" disabled>Konfirmasi</button>
        </div>
    </div>
</div>
