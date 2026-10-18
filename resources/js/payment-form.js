const initializedForms = new WeakSet();
const autoAllocationLabel = '-- Auto-Allocate against oldest open balance --';
const cents = value => Math.max(0, Math.round((Number(value) || 0) * 100));
const currency = value => `QAR ${(value / 100).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

/** Keep payment scope, displayed balance and input limit in sync; the server still validates allocations. */
export function initializePaymentForm(documentRef = globalThis.document, fetchRef = globalThis.fetch) {
    const form = documentRef?.getElementById('paymentForm');
    if (!form || initializedForms.has(form)) return;
    initializedForms.add(form);

    const customer = form.querySelector('#customerSelect');
    const invoice = form.querySelector('#invoiceSelect');
    const amount = form.querySelector('#amountReceived');
    const balance = form.querySelector('#customerBalanceDisplay');
    const label = form.querySelector('#paymentBalanceLabel');
    const error = form.querySelector('#invoiceLoadError');
    const submit = form.querySelector('button[type="submit"]');
    let requestId = 0;

    const updateBalance = (fillAmount = false) => {
        const selected = invoice.options[invoice.selectedIndex];
        const specific = Boolean(invoice.value && selected?.dataset.due !== undefined);
        const total = [...invoice.options].reduce((sum, option) => sum + cents(option.dataset.due), 0);
        const available = specific ? cents(selected.dataset.due) : total;
        form.dataset.customerBalance = (total / 100).toFixed(2);
        label.textContent = specific ? 'Selected invoice balance' : 'Total open invoice balance';
        balance.textContent = currency(available);
        amount.max = (available / 100).toFixed(2);
        if (fillAmount) amount.value = specific ? (available / 100).toFixed(2) : '';
    };
    const setLoading = loading => {
        invoice.disabled = loading;
        amount.disabled = loading;
        submit.disabled = loading;
        if (loading) form.setAttribute('aria-busy', 'true');
        else form.removeAttribute('aria-busy');
    };
    const option = (text, value = '') => {
        const node = documentRef.createElement('option');
        node.value = String(value);
        node.textContent = text;
        return node;
    };
    const loadCustomer = async () => {
        const currentRequest = ++requestId;
        const customerId = customer.value;
        error.classList.add('hidden');
        invoice.replaceChildren(option(autoAllocationLabel));
        amount.value = '';
        updateBalance();
        setLoading(Boolean(customerId));
        if (!customerId) return;

        try {
            const response = await fetchRef(`/api/customers/${encodeURIComponent(customerId)}/unpaid-invoices`, {headers: {'Accept': 'application/json'}});
            if (!response.ok) throw new Error('Unable to load invoices');
            const data = await response.json();
            if (currentRequest !== requestId) return;

            for (const item of data.invoices ?? []) {
                if (cents(item.balance_due) <= 0) continue;
                const date = new Date(item.invoice_date).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC'});
                const node = option(`${item.invoice_number} (Due: ${currency(cents(item.balance_due))} of ${(cents(item.grand_total) / 100).toFixed(2)}) — Date: ${date}`, item.id);
                node.dataset.due = String(item.balance_due);
                invoice.appendChild(node);
            }
            updateBalance();
            setLoading(false);
        } catch {
            if (currentRequest !== requestId) return;
            form.removeAttribute('aria-busy');
            error.textContent = 'Could not load this customer’s invoices. Choose the customer again to retry.';
            error.classList.remove('hidden');
        }
    };

    customer.addEventListener('change', loadCustomer);
    invoice.addEventListener('change', () => updateBalance(true));
    // Do not overwrite partial payments or amounts restored after validation errors.
    updateBalance();
}
