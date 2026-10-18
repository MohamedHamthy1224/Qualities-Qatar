import test from 'node:test';
import assert from 'node:assert/strict';
import {calculateLine, calculateTotal} from '../../resources/js/pricing.js';

test('an oversized item discount leaves setup and printing payable', () => {
    assert.deepEqual(calculateLine({quantity: 2, unitPrice: 50, lineDiscount: 150, setupCharges: 20}), {
        baseAmount: 100, lineDiscount: 100, lineTotal: 20,
    });
});

test('currency inputs round before multiplying and summing, matching saved documents', () => {
    const lineTotals = [
        calculateLine({quantity: 2, unitPrice: 50, lineDiscount: 150, setupCharges: 20}).lineTotal,
        calculateLine({quantity: 3, unitPrice: 10.005, lineDiscount: .335, printCharges: .335}).lineTotal,
        calculateLine({quantity: 2, unitPrice: 12.34}).lineTotal,
    ];
    assert.deepEqual(lineTotals, [20, 30.03, 24.68]);
    assert.deepEqual(calculateTotal({lineTotals, setupCharges: 1.005, printCharges: 2.675, discount: 4.005}), {
        subtotal: 74.71, discount: 4.01, grandTotal: 74.39,
    });
});

test('an overall discount is capped to the full amount due', () => {
    assert.deepEqual(calculateTotal({lineTotals: [10, 20], setupCharges: 5, printCharges: 2, discount: 100}), {
        subtotal: 30, discount: 37, grandTotal: 0,
    });
});

test('cent values add without binary fraction errors and empty forms total zero', () => {
    assert.equal(calculateTotal({lineTotals: [.1, .2]}).grandTotal, .3);
    assert.deepEqual(calculateTotal({lineTotals: []}), {subtotal: 0, discount: 0, grandTotal: 0});
});
