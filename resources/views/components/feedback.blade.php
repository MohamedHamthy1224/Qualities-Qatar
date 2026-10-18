<div id="appNotifications" class="app-notifications" aria-live="polite" aria-relevant="additions" aria-label="Notifications"></div>

<dialog id="appConfirmDialog" class="app-dialog app-confirm-dialog" aria-labelledby="appConfirmTitle" aria-describedby="appConfirmMessage">
    <div class="app-confirm-content">
        <span class="app-confirm-icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 8v4m0 4h.01" stroke-linecap="round"/><circle cx="12" cy="12" r="9"/></svg>
        </span>
        <h2 id="appConfirmTitle">Confirm action</h2>
        <p id="appConfirmMessage"></p>
    </div>
    <div class="app-dialog-actions">
        <button type="button" id="appConfirmCancel" class="app-button-secondary">Cancel</button>
        <button type="button" id="appConfirmAccept" class="app-button-primary">Continue</button>
    </div>
</dialog>
