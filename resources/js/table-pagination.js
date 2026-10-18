const PAGE_SIZES = [10, 15, 25, 50];
const initializedTables = new WeakMap();
let generatedTableId = 0;

function positiveInteger(value, fallback) {
    const number = Number(value);
    return Number.isSafeInteger(number) && number > 0 ? number : fallback;
}

/** Keep an existing page valid when the number of editable rows changes. */
export function paginationState(total, pageSize = 10, page = 1) {
    const count = Number.isSafeInteger(Number(total)) && Number(total) >= 0 ? Number(total) : 0;
    const size = positiveInteger(pageSize, 10);
    const pages = Math.max(1, Math.ceil(count / size));
    const current = Math.min(positiveInteger(page, 1), pages);
    const startIndex = (current - 1) * size;
    const endIndex = Math.min(startIndex + size, count);

    return {
        total: count,
        pageSize: size,
        page: current,
        pages,
        startIndex,
        endIndex,
        first: count ? startIndex + 1 : 0,
        last: endIndex,
        hasPrevious: current > 1,
        hasNext: current < pages,
    };
}

/** Locate an added row or the first invalid field without changing row order. */
export function pageContainingRow(rowIndex, pageSize = 10) {
    const index = Number.isSafeInteger(Number(rowIndex)) && Number(rowIndex) >= 0 ? Number(rowIndex) : 0;
    return Math.floor(index / positiveInteger(pageSize, 10)) + 1;
}

function tableRows(table) {
    const rows = Array.from(table.tBodies).flatMap(body => Array.from(body.rows));
    const markedRows = rows.filter(row => row.hasAttribute('data-pagination-row'));
    const selector = table.dataset.paginationRowSelector;

    return (markedRows.length ? markedRows : rows).filter(row => {
        if (row.hasAttribute('data-pagination-empty')) return false;
        if (selector) {
            try {
                return row.matches(selector);
            } catch {
                // A malformed optional selector must not break a document form.
                return true;
            }
        }
        return true;
    });
}

function element(document, tagName, className, text) {
    const node = document.createElement(tagName);
    node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
}

function initializeTable(table) {
    if (initializedTables.has(table)) return initializedTables.get(table);

    const document = table.ownerDocument;
    const region = table.closest('[data-paginated-table-region]') ?? table.parentElement;
    if (!region) return null;
    if (!table.id) {
        do {
            generatedTableId += 1;
            table.id = `paginated-table-${generatedTableId}`;
        } while (document.querySelectorAll(`#${table.id}`).length > 1);
    }

    const label = table.dataset.paginationLabel?.trim() || 'rows';
    const initialSize = positiveInteger(table.dataset.paginationSize, 10);
    const footer = element(document, 'nav', 'ui-pagination');
    footer.setAttribute('data-pagination-controls', table.id);
    footer.setAttribute('aria-label', `${label} pagination`);

    const summary = element(document, 'p', 'ui-pagination__summary');
    summary.setAttribute('role', 'status');
    summary.setAttribute('aria-live', 'polite');
    summary.setAttribute('aria-atomic', 'true');

    const actions = element(document, 'div', 'ui-pagination__actions');
    const perPage = element(document, 'label', 'ui-pagination__per-page', 'Rows');
    const sizeSelect = element(document, 'select', 'ui-pagination__select');
    sizeSelect.setAttribute('aria-label', `${label} per page`);
    sizeSelect.setAttribute('aria-controls', table.id);
    // No name: this display preference must never become a document form field.
    [...new Set([...PAGE_SIZES, initialSize])].sort((a, b) => a - b).forEach(size => {
        const option = element(document, 'option', '', String(size));
        option.value = String(size);
        option.selected = size === initialSize;
        sizeSelect.append(option);
    });
    perPage.append(sizeSelect);

    const previous = element(document, 'button', 'ui-pagination__button', 'Previous');
    const next = element(document, 'button', 'ui-pagination__button', 'Next');
    [previous, next].forEach(button => {
        button.type = 'button';
        button.setAttribute('aria-controls', table.id);
    });
    previous.setAttribute('aria-label', `Previous page of ${label}`);
    next.setAttribute('aria-label', `Next page of ${label}`);
    const pageLabel = element(document, 'span', 'ui-pagination__page');
    actions.append(perPage, previous, pageLabel, next);
    footer.append(summary, actions);
    region.append(footer);

    let rows = tableRows(table);
    let state = paginationState(rows.length, initialSize);
    let invalidBatch = false;
    const originallyHidden = new WeakMap();

    function render() {
        state = paginationState(rows.length, state.pageSize, state.page);
        rows.forEach((row, index) => {
            if (!originallyHidden.has(row)) originallyHidden.set(row, row.hidden);
            // Keep every row and input in the form for calculations and submission.
            row.hidden = originallyHidden.get(row) || index < state.startIndex || index >= state.endIndex;
        });
        const text = state.total
            ? `Showing ${state.first}\u2013${state.last} of ${state.total} ${label}`
            : `0 ${label}`;
        if (summary.textContent !== text) summary.textContent = text;
        pageLabel.textContent = `Page ${state.page} of ${state.pages}`;
        previous.disabled = !state.hasPrevious;
        next.disabled = !state.hasNext;
        table.dataset.paginationPage = String(state.page);
    }

    function navigate(page) {
        state = paginationState(rows.length, state.pageSize, page);
        render();
    }

    previous.addEventListener('click', () => navigate(state.page - 1));
    next.addEventListener('click', () => navigate(state.page + 1));
    sizeSelect.addEventListener('change', () => {
        const size = positiveInteger(sizeSelect.value, state.pageSize);
        state = paginationState(rows.length, size, pageContainingRow(state.startIndex, size));
        render();
    });

    function revealInvalidRow(event) {
        if (invalidBatch) return;
        const row = event.target.closest('tr');
        const index = rows.indexOf(row);
        if (index < 0) return;
        invalidBatch = true;
        navigate(pageContainingRow(index, state.pageSize));
        // Native validation fires invalid for all failing controls synchronously;
        // retain the first error's page so the browser can focus that control.
        setTimeout(() => { invalidBatch = false; }, 0);
    }
    table.addEventListener('invalid', revealInvalidRow, true);

    const MutationObserverClass = document.defaultView?.MutationObserver;
    const observer = MutationObserverClass ? new MutationObserverClass(() => {
        const previousRows = new Set(rows);
        const nextRows = tableRows(table);
        const added = nextRows.filter(row => !previousRows.has(row));
        rows = nextRows;
        if (added.length) {
            const preservesRows = rows.some(row => previousRows.has(row));
            const page = added.length === 1 && preservesRows
                ? pageContainingRow(rows.indexOf(added[0]), state.pageSize)
                : 1;
            state = paginationState(rows.length, state.pageSize, page);
        }
        observeRows();
        render();
    }) : null;

    function observeRows() {
        if (!observer) return;
        observer.disconnect();
        // Observe direct row additions only, not totals or text updates in cells.
        observer.observe(table, {childList: true});
        Array.from(table.tBodies).forEach(body => observer.observe(body, {childList: true}));
    }

    const pagination = {
        refresh() {
            rows = tableRows(table);
            observeRows();
            render();
        },
        destroy() {
            observer?.disconnect();
            table.removeEventListener('invalid', revealInvalidRow, true);
            rows.forEach(row => { row.hidden = originallyHidden.get(row) ?? false; });
            footer.remove();
            delete table.dataset.paginationPage;
            initializedTables.delete(table);
        },
    };
    initializedTables.set(table, pagination);
    observeRows();
    render();
    return pagination;
}

/** Opt in only large detail/form tables; server-paginated lists stay untouched. */
export function initializeTablePagination(root = document) {
    const tables = root.matches?.('table[data-paginate-table]')
        ? [root]
        : Array.from(root.querySelectorAll('table[data-paginate-table]'));
    return tables.map(initializeTable).filter(Boolean);
}
