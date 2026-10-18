import test from 'node:test';
import assert from 'node:assert/strict';
import {initializePaymentForm} from '../../resources/js/payment-form.js';

class Field {
    constructor(value = '') {
        this.value = value;
        this.dataset = {};
        this.textContent = '';
        this.disabled = false;
        this.attributes = {};
        this.listeners = new Map();
        this.classes = new Set(['hidden']);
        this.classList = {add: name => this.classes.add(name), remove: name => this.classes.delete(name)};
    }
    addEventListener(type, handler) {
        if (!this.listeners.has(type)) this.listeners.set(type, []);
        this.listeners.get(type).push(handler);
    }
    async emit(type) { await Promise.all((this.listeners.get(type) ?? []).map(handler => handler({target: this}))); }
    setAttribute(name, value) { this.attributes[name] = value; }
    removeAttribute(name) { delete this.attributes[name]; }
}
class Select extends Field {
    constructor(options, value = '') { super(value); this.options = options; }
    get selectedIndex() { return this.options.findIndex(option => option.value === this.value); }
    replaceChildren(...options) { this.options = options; this.value = options[0]?.value ?? ''; }
    appendChild(option) { this.options.push(option); }
}
const option = (value, due) => {
    const field = new Field(value);
    if (due !== undefined) field.dataset.due = due;
    return field;
};
const deferred = () => {
    let resolve;
    const promise = new Promise(done => { resolve = done; });
    return {promise, resolve};
};
const response = invoices => ({ok: true, json: async () => ({balance: 570, invoices})});
const invoices = [
    {id: 106, invoice_number: 'INV-2026-106', balance_due: '570.00', grand_total: '570.00', invoice_date: '2026-10-17'},
    {id: 6, invoice_number: 'INV-2026-006', balance_due: '90.00', grand_total: '7090.00', invoice_date: '2026-10-14'},
];

function fixture({selected = '106', amountValue = '570.00', fetch = async () => response(invoices)} = {}) {
    const form = new Field();
    const customer = new Field('8');
    const invoice = new Select([option(''), option('106', '570.00'), option('6', '90.00')], selected);
    const amount = new Field(amountValue);
    const balance = new Field();
    const label = new Field();
    const error = new Field();
    const submit = new Field();
    const fields = new Map([
        ['#customerSelect', customer], ['#invoiceSelect', invoice], ['#amountReceived', amount],
        ['#customerBalanceDisplay', balance], ['#paymentBalanceLabel', label],
        ['#invoiceLoadError', error], ['button[type="submit"]', submit],
    ]);
    form.querySelector = selector => fields.get(selector);
    const document = {getElementById: id => id === 'paymentForm' ? form : null, createElement: () => new Field()};
    initializePaymentForm(document, fetch);
    return {form, document, customer, invoice, amount, balance, label, error, submit};
}

test('switching between invoices updates the balance card, payment amount and maximum together', async () => {
    const f = fixture();
    assert.equal(f.balance.textContent, 'QAR 570.00');
    f.invoice.value = '6';
    await f.invoice.emit('change');
    assert.equal(f.balance.textContent, 'QAR 90.00');
    assert.equal(f.amount.value, '90.00');
    assert.equal(f.amount.max, '90.00');
    assert.equal(f.label.textContent, 'Selected invoice balance');
    f.invoice.value = '106';
    await f.invoice.emit('change');
    assert.equal(f.balance.textContent, 'QAR 570.00');
    assert.equal(f.amount.value, '570.00');
    assert.equal(f.amount.max, '570.00');
});

test('auto-allocation uses both open balances and clears the previous invoice amount', async () => {
    const f = fixture({selected: '6', amountValue: '90.00'});
    f.invoice.value = '';
    await f.invoice.emit('change');
    assert.equal(f.balance.textContent, 'QAR 660.00');
    assert.equal(f.label.textContent, 'Total open invoice balance');
    assert.equal(f.amount.max, '660.00');
    assert.equal(f.form.dataset.customerBalance, '660.00');
    assert.equal(f.amount.value, '');
});

test('initialization preserves partial or validation-restored amounts while correcting their limits', () => {
    const partial = fixture({selected: '6', amountValue: '25.50'});
    assert.equal(partial.amount.value, '25.50');
    assert.equal(partial.balance.textContent, 'QAR 90.00');
    assert.equal(partial.amount.max, '90.00');
    const rejected = fixture({selected: '6', amountValue: '91.00'});
    assert.equal(rejected.amount.value, '91.00');
    assert.equal(rejected.amount.max, '90.00');
    const automatic = fixture({selected: '', amountValue: '125.25'});
    assert.equal(automatic.amount.value, '125.25');
    assert.equal(automatic.balance.textContent, 'QAR 660.00');
});

test('customer loading clears stale selection, blocks submission and sums eligible invoice cents', async () => {
    const pending = deferred();
    const f = fixture({fetch: () => pending.promise});
    f.customer.value = '9';
    const loaded = f.customer.emit('change');
    assert.equal(f.balance.textContent, 'QAR 0.00');
    assert.equal(f.amount.value, '');
    assert.equal(f.invoice.value, '');
    assert.equal(f.submit.disabled, true);
    pending.resolve(response([...invoices, {id: 999, balance_due: '0.00'}]));
    await loaded;
    assert.equal(f.balance.textContent, 'QAR 660.00');
    assert.equal(f.amount.max, '660.00');
    assert.equal(f.invoice.options.length, 3);
    assert.equal(f.submit.disabled, false);
    assert.equal(f.form.attributes['aria-busy'], undefined);
    f.invoice.value = '6';
    await f.invoice.emit('change');
    assert.equal(f.balance.textContent, 'QAR 90.00');
});

test('an older customer response cannot replace the latest selection or balance', async () => {
    const first = deferred();
    const second = deferred();
    let calls = 0;
    const f = fixture({fetch: () => (++calls === 1 ? first.promise : second.promise)});
    const oldLoad = f.customer.emit('change');
    f.customer.value = '9';
    const newLoad = f.customer.emit('change');
    second.resolve(response([{...invoices[0], id: 900, balance_due: '0.30', grand_total: '0.30'}]));
    await newLoad;
    first.resolve(response(invoices));
    await oldLoad;
    assert.equal(f.balance.textContent, 'QAR 0.30');
    assert.equal(f.invoice.options.length, 2);
    assert.equal(f.invoice.options[1].value, '900');
    assert.equal(f.amount.max, '0.30');
    assert.equal(f.submit.disabled, false);
});

test('a failed load is visible and cannot submit stale data; retry or clearing the customer recovers', async () => {
    let succeeds = false;
    const f = fixture({fetch: async () => { if (!succeeds) throw new Error('Offline'); return response(invoices); }});
    await f.customer.emit('change');
    assert.equal(f.error.classes.has('hidden'), false);
    assert.equal(f.submit.disabled, true);
    assert.equal(f.amount.value, '');
    succeeds = true;
    await f.customer.emit('change');
    assert.equal(f.submit.disabled, false);
    assert.equal(f.error.classes.has('hidden'), true);
    assert.equal(f.balance.textContent, 'QAR 660.00');
    f.customer.value = '';
    await f.customer.emit('change');
    assert.equal(f.balance.textContent, 'QAR 0.00');
    assert.equal(f.amount.max, '0.00');
    assert.equal(f.invoice.options.length, 1);
});

test('empty invoices and repeated initialization do not leave old balances or duplicate fetches', async () => {
    let calls = 0;
    const fetch = async () => { calls++; return response([]); };
    const f = fixture({fetch});
    initializePaymentForm(f.document, fetch);
    await f.customer.emit('change');
    assert.equal(calls, 1);
    assert.equal(f.balance.textContent, 'QAR 0.00');
    assert.equal(f.amount.max, '0.00');
    assert.equal(f.amount.value, '');
});
