import { appNotify, notifyAfterReload } from './feedback';

export function initializeQuickCustomer() {
    const dialog = document.getElementById('quickCustomerModal');
    const form = document.getElementById('quickCustomerForm');
    if (!dialog || !form) return;
    const button = document.getElementById('qc_submit_btn');
    const summary = document.getElementById('quickCustomerError');
    const fields = [...form.querySelectorAll('input:not([type="hidden"])')];
    let saving = false;
    const clearFieldError = field => {
        field.removeAttribute('aria-invalid');
        const message = document.getElementById(`${field.id}_error`);
        message.textContent = '';
        message.hidden = true;
    };
    const clearErrors = () => {
        summary.hidden = true;
        summary.textContent = '';
        fields.forEach(clearFieldError);
    };
    const showErrors = (errors, fallback = 'Check the highlighted fields and try again.') => {
        clearErrors();
        let firstInvalid;
        Object.entries(errors).forEach(([name, messages]) => {
            const field = fields.find(input => input.name === name);
            if (!field) return;
            field.setAttribute('aria-invalid', 'true');
            const message = document.getElementById(`${field.id}_error`);
            message.textContent = Array.isArray(messages) ? messages.join(' ') : String(messages);
            message.hidden = false;
            firstInvalid ??= field;
        });
        summary.textContent = fallback;
        summary.hidden = false;
        if (dialog.open) (firstInvalid || summary).focus();
    };
    fields.forEach(field => field.addEventListener('input', () => {
        clearFieldError(field);
        if (!fields.some(input => input.hasAttribute('aria-invalid'))) summary.hidden = true;
    }));

    window.openQuickCustomerModal = () => {
        if (dialog.open) return;
        dialog.showModal();
        (fields.find(field => field.hasAttribute('aria-invalid')) || (!summary.hidden ? summary : document.getElementById('qc_name'))).focus();
    };
    window.closeQuickCustomerModal = () => dialog.close();
    dialog.addEventListener('click', event => {
        if (event.target === dialog) dialog.close();
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (saving) return;
        clearErrors();
        const errors = {};
        fields.forEach(field => {
            if (!field.checkValidity()) {
                errors[field.name] = field.validity.valueMissing
                    ? 'Enter the customer or organization name.'
                    : field.type === 'email' ? 'Enter a valid email address.' : field.validationMessage;
            }
        });
        if (!document.getElementById('qc_name').value.trim()) errors.name = 'Enter the customer or organization name.';
        if (Object.keys(errors).length) {
            showErrors(errors);
            return;
        }
        saving = true;
        button.disabled = true;
        button.textContent = 'Saving…';
        form.setAttribute('aria-busy', 'true');
        const payload = new FormData(form);
        fields.forEach(field => { field.readOnly = true; });
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: payload,
            });
            const data = await response.json().catch(() => null);
            if (response.ok && data?.success && data.customer) {
                const customer = data.customer;
                const select = document.querySelector('select[name="customer_id"]');
                if (select && !select.disabled) {
                    const option = new Option(`${customer.name}${customer.company_name ? ` (${customer.company_name})` : ''} [${customer.customer_code}]`, customer.id, false, true);
                    option.dataset.balance = customer.balance ?? '0';
                    select.append(option);
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }
                dialog.close();
                form.reset();
                clearErrors();
                const notice = { type: 'success', title: 'Customer added', message: `${customer.name} · ${customer.customer_code}` };
                if (location.pathname === new URL(form.dataset.customerListUrl).pathname) {
                    notifyAfterReload(notice);
                    location.reload();
                } else {
                    appNotify(notice);
                }
            } else if (response.status === 422 && data?.errors) {
                showErrors(data.errors);
                if (!dialog.open) appNotify({ type: 'error', title: 'Customer not saved', message: 'Open the customer form to check the highlighted fields.' });
            } else {
                const message = response.status === 419
                    ? 'Your session expired. Refresh the page before saving again.'
                    : 'We could not save the customer. Please try again.';
                showErrors({}, message);
                if (!dialog.open) appNotify({ type: 'error', title: 'Customer not saved', message });
            }
        } catch {
            const message = 'Unable to connect. Check your connection before trying again.';
            showErrors({}, message);
            if (!dialog.open) appNotify({ type: 'error', title: 'Customer not saved', message });
        } finally {
            saving = false;
            fields.forEach(field => { field.readOnly = false; });
            button.disabled = false;
            button.textContent = 'Save customer';
            form.removeAttribute('aria-busy');
        }
    });
}
