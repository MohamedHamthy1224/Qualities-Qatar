const entranceEase = 'cubic-bezier(0.22, 1, 0.36, 1)';
const pageGroups = '.ui-page, .erp-dashboard, .future-dashboard-top, .production-page, .ui-metric-grid, .dashboard-metrics, .dashboard-content-grid, .dashboard-secondary, .dashboard-charts-grid';

let currentController;

/** Small, cancellable entrances; document content is always visible without JavaScript. */
export function initializeWorkspaceMotion() {
    if (currentController) return currentController;
    const root = document.documentElement;
    const main = document.querySelector('.app-main');
    if (!main) return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const printMedia = window.matchMedia('print');
    const activeAnimations = new Set();
    const elementAnimations = new WeakMap();
    const disposers = [];
    const supportsAnimation = typeof main.animate === 'function';
    root.dataset.workspaceMotion = supportsAnimation ? 'native' : 'css';

    const isEnabled = () => root.dataset.appearance === 'clean' && !reducedMotion.matches && !printMedia.matches;
    const isVisible = element => {
        const bounds = element.getBoundingClientRect();
        return bounds.width > 0 && bounds.height > 0 && bounds.bottom > 0 && bounds.top < window.innerHeight;
    };
    const cancelAll = () => {
        activeAnimations.forEach(animation => animation.cancel());
        activeAnimations.clear();
    };
    const animate = (element, frames, options) => {
        if (!supportsAnimation || !isEnabled() || document.hidden) return;
        elementAnimations.get(element)?.cancel();
        const animation = element.animate(frames, { easing: entranceEase, fill: 'backwards', ...options });
        elementAnimations.set(element, animation);
        activeAnimations.add(animation);
        const release = () => {
            activeAnimations.delete(animation);
            if (elementAnimations.get(element) === animation) elementAnimations.delete(element);
            animation.cancel();
        };
        // Cancellation is expected when appearance, motion preference or printing changes.
        animation.finished.then(release, release);
    };
    const enterPanel = panel => animate(panel, [
        { opacity: 0, transform: 'translateY(-4px)' },
        { opacity: 1, transform: 'translateY(0)' },
    ], { duration: 180 });
    const enterDialog = dialog => animate(dialog, [
        { opacity: 0, transform: 'translateY(8px)' },
        { opacity: 1, transform: 'translateY(0)' },
    ], { duration: 220 });

    const pageSections = () => {
        const collect = (container, depth = 0) => [...container.children].flatMap(child => {
            if (child.matches('script, style, input[type="hidden"], dialog, [hidden]')) return [];
            if (depth < 4 && child.matches(pageGroups)) return collect(child, depth + 1);
            return isVisible(child) ? [child] : [];
        });
        return collect(main).slice(0, 12);
    };
    const enterPage = () => {
        if (!isEnabled()) return;
        pageSections().forEach((section, index) => animate(section, [
            { opacity: 0, transform: 'translateY(8px)' },
            { opacity: 1, transform: 'translateY(0)' },
        ], { duration: 280, delay: Math.min(index * 32, 128) }));
        // Scale the saved chart geometry only; labels and business totals never count up.
        main.querySelectorAll('.dashboard-workload-track > span').forEach((bar, index) => {
            if (!isVisible(bar)) return;
            animate(bar, [{ scale: '0 1' }, { scale: '1 1' }], {
                duration: 320, delay: Math.min(index * 24, 96),
            });
        });
    };

    document.querySelectorAll('[data-app-header] details.app-header-dropdown').forEach(details => {
        const panel = details.querySelector('.app-header-menu');
        if (!panel) return;
        const toggled = () => {
            if (details.open) enterPanel(panel);
            else elementAnimations.get(panel)?.cancel();
        };
        details.addEventListener('toggle', toggled);
        disposers.push(() => details.removeEventListener('toggle', toggled));
    });

    // Observe only the two existing dialogs. Their focus, Escape and save behavior stay native.
    document.querySelectorAll('#quickCustomerModal, #appConfirmDialog').forEach(dialog => {
        const observer = new MutationObserver(() => {
            if (dialog.open) enterDialog(dialog);
            else elementAnimations.get(dialog)?.cancel();
        });
        observer.observe(dialog, { attributes: true, attributeFilter: ['open'] });
        disposers.push(() => observer.disconnect());
    });

    // These legacy panels toggle their own hidden class. Watch only their container,
    // never their fields or table rows, and leave opening/closing behavior untouched.
    const watchVisibility = (container, panel, entrance) => {
        let visible = !container.hidden && !container.classList.contains('hidden');
        const observer = new MutationObserver(() => {
            const nextVisible = !container.hidden && !container.classList.contains('hidden');
            if (nextVisible === visible) return;
            visible = nextVisible;
            if (visible) entrance(panel);
            else elementAnimations.get(panel)?.cancel();
        });
        observer.observe(container, { attributes: true, attributeFilter: ['class', 'hidden'] });
        disposers.push(() => observer.disconnect());
    };
    const customItemModal = document.getElementById('customItemModal');
    const customItemPanel = customItemModal?.querySelector(':scope > .ui-section');
    if (customItemPanel) watchVisibility(customItemModal, customItemPanel, enterDialog);

    const customerEditForm = document.getElementById('profile-name')?.closest('form');
    const customerEdit = customerEditForm?.closest('details');
    if (customerEdit) {
        const editToggled = () => {
            if (customerEdit.open) enterPanel(customerEditForm);
            else elementAnimations.get(customerEditForm)?.cancel();
        };
        customerEdit.addEventListener('toggle', editToggled);
        disposers.push(() => customerEdit.removeEventListener('toggle', editToggled));
    }
    main.querySelectorAll('#content-invoices.history-content, #content-quotes.history-content, #content-delivery.history-content, #content-receipts.history-content').forEach(panel => {
        watchVisibility(panel, panel, enterPanel);
    });

    const appearanceChanged = () => {
        cancelAll();
        if (isEnabled()) enterPage();
    };
    const motionPreferenceChanged = () => {
        if (!isEnabled()) cancelAll();
    };
    const pageHidden = () => {
        if (document.hidden) cancelAll();
    };
    document.addEventListener('qualities:appearance-change', appearanceChanged);
    document.addEventListener('visibilitychange', pageHidden);
    reducedMotion.addEventListener('change', motionPreferenceChanged);
    printMedia.addEventListener('change', motionPreferenceChanged);
    window.addEventListener('beforeprint', cancelAll);
    window.addEventListener('pagehide', cancelAll);

    currentController = {
        destroy() {
            cancelAll();
            disposers.forEach(dispose => dispose());
            document.removeEventListener('qualities:appearance-change', appearanceChanged);
            document.removeEventListener('visibilitychange', pageHidden);
            reducedMotion.removeEventListener('change', motionPreferenceChanged);
            printMedia.removeEventListener('change', motionPreferenceChanged);
            window.removeEventListener('beforeprint', cancelAll);
            window.removeEventListener('pagehide', cancelAll);
            delete root.dataset.workspaceMotion;
            currentController = undefined;
        },
    };
    enterPage();
    return currentController;
}
