const icons = {
    success: '<path d="m6 12 4 4 8-8"/>',
    error: '<path d="m8 8 8 8m0-8-8 8"/>',
    info: '<path d="M12 11v6m0-10h.01"/>',
};

export function appNotify({ type = 'info', title = 'Notice', message = '', duration } = {}) {
    const tone = Object.hasOwn(icons, type) ? type : 'info';
    document.dispatchEvent(new CustomEvent('qualities:notification', {
        detail: { type: tone, title: String(title), message: String(message), createdAt: Date.now() },
    }));
    const region = document.getElementById('appNotifications');
    if (!region) return () => {};
    const toast = document.createElement('div');
    toast.className = 'app-notification';
    toast.dataset.type = tone;
    toast.setAttribute('aria-atomic', 'true');
    const icon = document.createElement('span');
    icon.className = 'app-notification-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${icons[tone]}</svg>`;
    const content = document.createElement('div');
    content.className = 'app-notification-content';
    const heading = document.createElement('p');
    heading.className = 'app-notification-title';
    heading.textContent = title;
    const text = document.createElement('p');
    text.className = 'app-notification-message';
    text.textContent = message;
    content.append(heading, text);
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'app-notification-close';
    close.setAttribute('aria-label', 'Dismiss notification');
    close.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>';
    toast.append(icon, content, close);

    let remaining = duration ?? (tone === 'error' ? 0 : 8000);
    toast.dataset.persistent = String(remaining <= 0);
    let timer;
    let startedAt;
    let dismissed = false;
    const pause = () => {
        if (!timer) return;
        clearTimeout(timer);
        timer = null;
        remaining = Math.max(0, remaining - (Date.now() - startedAt));
    };
    const dismiss = () => {
        if (dismissed) return;
        dismissed = true;
        clearTimeout(timer);
        document.removeEventListener('visibilitychange', visibilityChanged);
        modalObserver.disconnect();
        toast.remove();
    };
    const resume = () => {
        if (dismissed || timer || remaining <= 0 || document.hidden || document.querySelector('dialog[open]') || toast.matches(':hover, :focus-within')) return;
        startedAt = Date.now();
        timer = setTimeout(dismiss, remaining);
    };
    const visibilityChanged = () => document.hidden ? pause() : resume();
    const modalObserver = new MutationObserver(() => document.querySelector('dialog[open]') ? pause() : resume());
    modalObserver.observe(document.body, { attributes: true, attributeFilter: ['open'], subtree: true });
    close.addEventListener('click', dismiss);
    toast.addEventListener('pointerenter', pause);
    toast.addEventListener('pointerleave', resume);
    toast.addEventListener('focusin', pause);
    toast.addEventListener('focusout', () => queueMicrotask(resume));
    document.addEventListener('visibilitychange', visibilityChanged);
    toast.dismissNotification = dismiss;
    while (region.children.length >= 3) {
        const oldestTransient = [...region.children].find(item => item.dataset.persistent !== 'true' && !item.contains(document.activeElement));
        if (!oldestTransient) break;
        oldestTransient.dismissNotification();
    }
    region.append(toast);
    resume();
    return dismiss;
}

// A single confirmation is active at a time; repeat clicks never submit twice.
let confirmationPending = false;
export async function appConfirm({ title = 'Confirm action', message = '', confirmLabel = 'Continue', cancelLabel = 'Cancel', destructive = false } = {}) {
    const dialog = document.getElementById('appConfirmDialog');
    if (!dialog || confirmationPending) return false;
    confirmationPending = true;
    document.getElementById('appConfirmTitle').textContent = title;
    document.getElementById('appConfirmMessage').textContent = message;
    const accept = document.getElementById('appConfirmAccept');
    const cancel = document.getElementById('appConfirmCancel');
    accept.textContent = confirmLabel;
    cancel.textContent = cancelLabel;
    accept.dataset.destructive = String(destructive);
    return new Promise(resolve => {
        let settled = false;
        const finish = result => {
            if (settled) return;
            settled = true;
            accept.removeEventListener('click', approve);
            cancel.removeEventListener('click', decline);
            dialog.removeEventListener('cancel', cancelWithEscape);
            dialog.removeEventListener('click', clickBackdrop);
            dialog.removeEventListener('close', closed);
            dialog.close();
            confirmationPending = false;
            resolve(result);
        };
        const approve = () => finish(true);
        const decline = () => finish(false);
        const cancelWithEscape = event => { event.preventDefault(); finish(false); };
        const clickBackdrop = event => { if (event.target === dialog) finish(false); };
        const closed = () => finish(false);
        accept.addEventListener('click', approve);
        cancel.addEventListener('click', decline);
        dialog.addEventListener('cancel', cancelWithEscape);
        dialog.addEventListener('click', clickBackdrop);
        dialog.addEventListener('close', closed);
        dialog.showModal();
        cancel.focus();
    });
}

const pendingNotificationKey = 'qualities:notification';
export function notifyAfterReload(notification) {
    try {
        sessionStorage.setItem(pendingNotificationKey, JSON.stringify({ ...notification, createdAt: Date.now() }));
        return true;
    } catch {
        return false;
    }
}

export function initializeFeedback() {
    window.appNotify = appNotify;
    window.appConfirm = appConfirm;
    try {
        const saved = sessionStorage.getItem(pendingNotificationKey);
        sessionStorage.removeItem(pendingNotificationKey);
        if (saved) {
            const notification = JSON.parse(saved);
            if (Date.now() - notification.createdAt < 30000) appNotify(notification);
        }
    } catch { /* Notifications remain available when browser storage is unavailable. */ }
}
