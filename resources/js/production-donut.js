const preferenceKey = 'qualities:production-overview-style';
const controllers = new WeakMap();

/** Enhance server-rendered links only; counts and percentages stay authoritative in Blade. */
export function initializeProductionDonut(documentRef = globalThis.document, windowRef = globalThis.window) {
    if (!documentRef) return [];

    return [...documentRef.querySelectorAll('[data-production-donut]')].map(root => {
        if (controllers.has(root)) return controllers.get(root);
        const choices = [...root.querySelectorAll('[data-donut-style-choice]')];
        const views = [...root.querySelectorAll('[data-donut-view]')];
        const links = [...root.querySelectorAll('[data-donut-series]')];
        const controls = root.querySelector('[data-donut-style-controls]');
        const tooltip = root.querySelector('[data-donut-tooltip]');
        const hint = root.querySelector('[data-donut-hint]');
        const tooltipLabel = root.querySelector('[data-donut-tooltip-label]');
        const tooltipCount = root.querySelector('[data-donut-tooltip-count]');
        const tooltipPercent = root.querySelector('[data-donut-tooltip-percent]');
        let storage;
        try { storage = windowRef?.localStorage; } catch { /* Chart also works with storage blocked. */ }
        let pointerLink = null;
        let focusLink = null;
        let source = 'pointer';
        let dismissed = false;

        const linkAt = target => {
            const link = target?.closest?.('[data-donut-series]');
            return links.includes(link) ? link : null;
        };
        const render = () => {
            const link = source === 'focus' ? focusLink ?? pointerLink : pointerLink ?? focusLink;
            const visible = !dismissed && root.dataset.donutStyle === 'interactive' && Boolean(link && tooltip);
            if (visible) {
                root.dataset.activeSeries = link.dataset.donutSeries;
                tooltipLabel.textContent = link.dataset.donutLabel;
                const count = Number(link.dataset.donutCount);
                tooltipCount.textContent = `${count.toLocaleString('en')} ${count === 1 ? 'job' : 'jobs'}`;
                tooltipPercent.textContent = `${link.dataset.donutPercent}% of jobs`;
            } else {
                delete root.dataset.activeSeries;
            }
            if (tooltip) tooltip.hidden = !visible;
            if (hint) hint.hidden = visible;
            links.forEach(item => {
                if (visible && item === link) item.setAttribute('aria-describedby', tooltip.id);
                else item.removeAttribute('aria-describedby');
            });
        };
        const setStyle = (style, persist = false) => {
            const next = style === 'original' ? 'original' : 'interactive';
            root.dataset.donutStyle = next;
            views.forEach(view => { view.hidden = view.dataset.donutView !== next; });
            choices.forEach(choice => choice.setAttribute('aria-pressed', String(choice.dataset.donutStyleChoice === next)));
            pointerLink = null;
            focusLink = null;
            dismissed = false;
            render();
            if (persist) {
                try { storage?.setItem(preferenceKey, next); } catch { /* The local undo still works. */ }
            }
        };
        const onClick = event => {
            const choice = event.target?.closest?.('[data-donut-style-choice]');
            if (choices.includes(choice)) setStyle(choice.dataset.donutStyleChoice, true);
            // Status links keep native navigation, including touch, Enter and modified clicks.
        };
        const onPointerOver = event => {
            if (event.pointerType === 'touch') return;
            const link = linkAt(event.target);
            if (!link) return;
            if (link !== pointerLink) dismissed = false;
            pointerLink = link;
            source = 'pointer';
            render();
        };
        const onPointerLeave = () => {
            pointerLink = null;
            source = 'focus';
            if (!focusLink) dismissed = false;
            render();
        };
        const onFocusIn = event => {
            focusLink = linkAt(event.target);
            if (focusLink) {
                source = 'focus';
                dismissed = false;
            }
            render();
        };
        const onFocusOut = event => {
            focusLink = linkAt(event.relatedTarget);
            if (!focusLink) source = 'pointer';
            render();
        };
        const onKeyDown = event => {
            if (event.key !== 'Escape') return;
            dismissed = true;
            render();
        };
        const onStorage = event => {
            if (event.key === preferenceKey || event.key === null) setStyle(event.newValue);
        };
        const events = [
            ['click', onClick], ['pointerover', onPointerOver], ['pointerleave', onPointerLeave],
            ['focusin', onFocusIn], ['focusout', onFocusOut],
        ];
        events.forEach(([type, handler]) => root.addEventListener(type, handler));
        windowRef?.addEventListener('keydown', onKeyDown);
        windowRef?.addEventListener('storage', onStorage);
        let savedStyle;
        try { savedStyle = storage?.getItem(preferenceKey); } catch { /* Default to the interactive chart. */ }
        setStyle(savedStyle);
        if (controls) controls.hidden = false;

        const controller = {
            destroy() {
                events.forEach(([type, handler]) => root.removeEventListener(type, handler));
                windowRef?.removeEventListener('keydown', onKeyDown);
                windowRef?.removeEventListener('storage', onStorage);
                setStyle('interactive');
                if (controls) controls.hidden = true;
                controllers.delete(root);
            },
        };
        controllers.set(root, controller);
        return controller;
    });
}
