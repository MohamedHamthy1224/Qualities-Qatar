const notificationStorageKey = 'qualities:header-notifications';
const notificationTypes = new Set(['success', 'error', 'info']);

function normalizeNotification(value) {
    if (!value || typeof value !== 'object' || typeof value.title !== 'string' || typeof value.message !== 'string') return null;
    if (!Number.isFinite(value.createdAt) || value.createdAt <= 0 || Number.isNaN(new Date(value.createdAt).getTime())) return null;
    return {
        type: notificationTypes.has(value.type) ? value.type : 'info',
        title: value.title,
        message: value.message,
        createdAt: value.createdAt,
        unread: value.unread === true,
    };
}

export function initializeHeader() {
    const header = document.querySelector('[data-app-header]');
    if (!header || header.dataset.headerInitialized) return;
    header.dataset.headerInitialized = 'true';

    const clock = header.querySelector('[data-header-clock]');
    const timeZone = clock?.dataset.timezone || 'Asia/Qatar';
    const clockFormat = new Intl.DateTimeFormat('en-US', { timeZone, hour: '2-digit', minute: '2-digit', hour12: true });
    const noticeFormat = new Intl.DateTimeFormat('en-US', { timeZone, month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });
    const updateClock = () => {
        if (!clock) return;
        const now = new Date();
        clock.textContent = clockFormat.format(now);
        clock.setAttribute('datetime', now.toISOString());
    };
    updateClock();
    if (clock) setInterval(updateClock, 30000);

    const notifications = header.querySelector('[data-header-notifications]');
    const list = header.querySelector('[data-header-notification-list]');
    const empty = header.querySelector('[data-header-notification-empty]');
    const dot = header.querySelector('[data-header-notification-dot]');
    const toggle = header.querySelector('[data-header-notification-toggle]');
    const clear = header.querySelector('[data-header-notification-clear]');
    let notices = [];
    try {
        const saved = JSON.parse(sessionStorage.getItem(notificationStorageKey) || '[]');
        if (Array.isArray(saved)) notices = saved.slice(0, 15).map(normalizeNotification).filter(Boolean);
    } catch { /* Keep the bell usable when session storage is unavailable or malformed. */ }

    const save = () => {
        try { sessionStorage.setItem(notificationStorageKey, JSON.stringify(notices)); } catch { /* In-memory history still works. */ }
    };
    const render = () => {
        const unread = notices.filter(notice => notice.unread).length;
        if (dot) dot.hidden = unread === 0;
        if (toggle) toggle.setAttribute('aria-label', unread ? `Notifications, ${unread} unread` : 'Notifications');
        if (empty) empty.hidden = notices.length > 0;
        if (clear) {
            clear.hidden = notices.length === 0;
            clear.disabled = notices.length === 0;
        }
        if (!list) return;
        list.hidden = notices.length === 0;
        list.replaceChildren(...notices.map(notice => {
            const item = document.createElement('li');
            item.className = 'app-header-notice';
            item.dataset.type = notice.type;
            const title = document.createElement('strong');
            title.className = 'app-header-notice-title';
            title.textContent = notice.title;
            const message = document.createElement('p');
            message.className = 'app-header-notice-message';
            message.textContent = notice.message;
            const time = document.createElement('time');
            time.className = 'app-header-notice-time';
            time.dateTime = new Date(notice.createdAt).toISOString();
            time.textContent = noticeFormat.format(notice.createdAt);
            item.append(title, message, time);
            return item;
        }));
    };
    const markRead = () => {
        notices.forEach(notice => { notice.unread = false; });
        save();
        render();
    };
    const add = value => {
        const notice = normalizeNotification(value);
        if (!notice) return;
        notices.unshift({ ...notice, unread: !notifications?.open });
        notices = notices.slice(0, 15);
        save();
        render();
    };
    document.addEventListener('qualities:notification', event => add(event.detail));
    document.querySelectorAll('[data-header-notice]').forEach(notice => add({
        type: notice.dataset.type || 'info',
        title: notice.dataset.title || 'Notice',
        message: notice.querySelector('[data-header-notice-message]')?.textContent.trim() || '',
        createdAt: Date.now(),
    }));
    clear?.addEventListener('click', () => {
        notices = [];
        save();
        render();
    });

    const menus = [...header.querySelectorAll('[data-header-notifications], [data-header-profile]')];
    menus.forEach(menu => menu.addEventListener('toggle', () => {
        menu.querySelector('summary')?.setAttribute('aria-expanded', String(menu.open));
        if (!menu.open) return;
        menus.forEach(other => { if (other !== menu) other.open = false; });
        if (menu === notifications) markRead();
    }));
    document.addEventListener('click', event => {
        menus.forEach(menu => { if (!menu.contains(event.target)) menu.open = false; });
    });
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        const open = menus.find(menu => menu.open);
        if (!open) return;
        event.preventDefault();
        open.open = false;
        open.querySelector('summary')?.focus();
    });
    if (notifications?.open) markRead();
    render();
}
