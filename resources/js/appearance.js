const appearanceStorageKey = 'qualities:appearance';

export function initializeAppearance() {
    const root = document.documentElement;
    const choices = [...document.querySelectorAll('[data-appearance-choice]')];
    if (!choices.length || root.dataset.appearanceInitialized) return;
    root.dataset.appearanceInitialized = 'true';

    const update = appearance => {
        const next = appearance === 'previous' ? 'previous' : 'clean';
        const changed = root.dataset.appearance !== next;
        root.dataset.appearance = next;
        choices.forEach(choice => choice.setAttribute('aria-pressed', String(choice.dataset.appearanceChoice === next)));
        document.querySelectorAll('[data-appearance-status]').forEach(status => {
            status.textContent = next === 'clean' ? 'Clean style is active.' : 'Previous style is active.';
        });
        if (changed) document.dispatchEvent(new CustomEvent('qualities:appearance-change', { detail: { appearance: next } }));
    };

    update(root.dataset.appearance);
    choices.forEach(choice => choice.addEventListener('click', () => {
        const next = choice.dataset.appearanceChoice;
        update(next);
        try { localStorage.setItem(appearanceStorageKey, next); } catch { /* The switch remains usable for this page. */ }
    }));
    window.addEventListener('storage', event => {
        if (event.key === appearanceStorageKey || event.key === null) update(event.newValue);
    });
}
