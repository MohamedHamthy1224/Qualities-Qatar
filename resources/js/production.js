export function initializeProductionTable() {
    const page = document.querySelector('[data-production-page]');
    if (!page) return;
    page.dataset.enhanced = 'true';
    let pending = false;
    const rows = document.getElementById('productionTableRows');
    const errorBox = document.getElementById('productionUpdateError');
    const notice = document.getElementById('productionUpdateNotice');
    const viewLinks = [...page.querySelectorAll('[data-production-view]')];

    async function saveRow(form) {
        if (pending) return;
        const field = form.dataset.productionUpdate;
        const select = form.querySelector('select');
        const previous = select.dataset.currentValue;
        if (select.value === previous) return;
        const row = form.closest('[data-order-id]');
        const orderId = row.dataset.orderId;
        const orderNumber = row.dataset.orderNumber;
        const feedback = row.querySelector('.production-row-feedback');
        const openNotes = [...rows.querySelectorAll('.production-notes[open]')].map(detail => detail.closest('[data-order-id]').dataset.orderId);
        const payload = { [field]: select.value, [`previous_${field}`]: previous };
        pending = true;
        errorBox.classList.add('hidden');
        notice.classList.add('hidden');
        feedback.textContent = 'Saving…';
        feedback.dataset.state = 'saving';
        page.setAttribute('aria-busy', 'true');
        row.setAttribute('aria-busy', 'true');
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 20000);
        const controls = [...page.querySelectorAll('select, input, button')];
        const disabledStates = controls.map(control => control.disabled);
        controls.forEach(control => { control.disabled = true; });
        viewLinks.forEach(link => link.setAttribute('aria-disabled', 'true'));

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
                signal: controller.signal,
            });
            const data = await response.json().catch(() => null);
            if (!response.ok || !data?.success) {
                const message = data?.errors?.[field]?.[0] || data?.message;
                throw new Error(message || (response.status === 419 ? 'Your session expired. Refresh the jobs and try again.' : 'Could not save this job. Refresh to check its latest status before trying again.'));
            }

            rows.innerHTML = data.rows_html;
            document.getElementById('productionPagination').innerHTML = data.pagination_html;
            page.querySelector('[data-production-total-active]').textContent = `${data.totalActive} ongoing`;
            page.querySelector('[data-production-total-completed]').textContent = `${data.totalCompleted} production complete`;
            page.querySelector('[data-production-urgent-count]').textContent = `${data.urgentCount} urgent`;
            page.querySelector('[data-production-urgent-group]').hidden = data.urgentCount === 0;
            openNotes.forEach(id => {
                const detail = document.getElementById(`production-order-${id}`)?.querySelector('.production-notes');
                if (detail) detail.open = true;
            });

            const savedRow = document.getElementById(`production-order-${orderId}`);
            if (savedRow) {
                const savedFeedback = savedRow.querySelector('.production-row-feedback');
                savedFeedback.textContent = 'Saved';
                savedFeedback.dataset.state = 'saved';
                savedRow.querySelector(`select[name="${field}"]`).focus({ preventScroll: true });
            } else {
                document.getElementById('productionTableTitle').focus({ preventScroll: true });
            }
            notice.textContent = data.message + (data.matches_filters ? '' : ' This job no longer matches the selected filters.');
            notice.classList.remove('hidden');
            if (data.page_url) {
                window.history.replaceState(null, '', data.page_url);
                errorBox.querySelector('a').href = data.page_url;
                viewLinks.forEach(link => {
                    const url = new URL(data.page_url, window.location.origin);
                    url.searchParams.set('view', link.dataset.productionView);
                    link.href = url.toString();
                });
            }
        } catch (error) {
            select.value = previous;
            const unconfirmed = error.name === 'AbortError' || error.name === 'TypeError';
            feedback.textContent = unconfirmed ? 'Save not confirmed' : 'Not saved';
            feedback.dataset.state = 'error';
            const message = error.name === 'AbortError'
                ? 'The save request timed out. Refresh to check the latest status before trying again.'
                : error.name === 'TypeError'
                    ? 'Could not reach the server. Refresh to check the latest status before trying again.'
                    : error.message || 'Could not save this job. Refresh to check its latest status before trying again.';
            errorBox.querySelector('[data-production-error-message]').textContent = `${orderNumber}: ${message}`;
            errorBox.classList.remove('hidden');
        } finally {
            window.clearTimeout(timeout);
            controls.forEach((control, index) => { control.disabled = disabledStates[index]; });
            viewLinks.forEach(link => link.removeAttribute('aria-disabled'));
            pending = false;
            page.removeAttribute('aria-busy');
            row.removeAttribute('aria-busy');
        }
    }

    page.addEventListener('change', event => {
        const form = event.target.closest('form[data-production-update]');
        if (form && event.target.matches('select')) void saveRow(form);
    });
    page.addEventListener('click', event => {
        if (pending && event.target.closest('[data-production-view]')) event.preventDefault();
    });
    page.addEventListener('submit', event => {
        if (event.target.matches('form[data-production-update]')) {
            event.preventDefault();
            void saveRow(event.target);
        }
    });
}
