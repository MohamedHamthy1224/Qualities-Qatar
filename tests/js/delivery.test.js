import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';

const view = readFileSync(new URL('../../resources/views/delivery/create.blade.php', import.meta.url), 'utf8');
const functions = ['readDeliveryQuantity', 'recalcDeliv'].map(name => {
    const expression = new RegExp(`    function ${name}\\([^)]*\\) \\{[\\s\\S]*?\\n    \\}`);
    return view.match(expression)[0];
}).join('\n');

function input(value) {
    return {value, setCustomValidity(message) {this.error = message;}};
}

test('delivery quantities normalize integer notation before summing and submission', () => {
    const sizes = [input('1e3'), input('002'), input('3.0')];
    const sizedQuantity = input('0');
    const flatQuantity = input('2E+2');
    const summary = {};
    const rows = [
        {dataset: {hasSizes: '1'}, querySelector: () => sizedQuantity, querySelectorAll: () => sizes},
        {dataset: {hasSizes: '0'}, querySelector: () => flatQuantity},
    ];
    const context = vm.createContext({document: {querySelectorAll: () => rows, getElementById: () => summary}});
    vm.runInContext(functions + '\nrecalcDeliv();', context);

    assert.deepEqual(sizes.map(size => size.value), ['1000', '2', '3']);
    assert.equal(Number(sizedQuantity.value), 1005);
    assert.equal(flatQuantity.value, '200');
    assert.equal(summary.textContent, 1205);
    assert.ok([...sizes, sizedQuantity, flatQuantity].every(field => field.error === ''));
});

test('fractional and unsafe delivery quantities block submission instead of truncating', () => {
    const readQuantity = vm.runInNewContext(functions + '\nreadDeliveryQuantity');
    for (const value of ['1.25', '1e-3', 'Infinity', 'invalid', '9007199254740992']) {
        const field = input(value);
        assert.equal(readQuantity(field), 0);
        assert.ok(field.error);
        assert.equal(field.value, value);
    }
});
