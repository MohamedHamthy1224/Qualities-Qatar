import test from 'node:test';
import assert from 'node:assert/strict';
import {paginationState, pageContainingRow} from '../../resources/js/table-pagination.js';

test('empty tables retain an accessible single disabled page and zero range', () => {
    const state = paginationState(0, 10, 8);
    assert.equal(state.page, 1);
    assert.equal(state.pages, 1);
    assert.equal(state.first, 0);
    assert.equal(state.last, 0);
    assert.equal(state.hasPrevious, false);
    assert.equal(state.hasNext, false);
});

test('page ranges cover every row once without losing the last partial page', () => {
    const indexes = [];
    for (let page = 1; page <= 3; page += 1) {
        const state = paginationState(23, 10, page);
        indexes.push(...Array.from({length: state.endIndex - state.startIndex}, (_, offset) => state.startIndex + offset));
        assert.equal(state.hasPrevious, page > 1);
        assert.equal(state.hasNext, page < 3);
    }
    assert.deepEqual(indexes, Array.from({length: 23}, (_, index) => index));
    assert.equal(paginationState(23, 10, 3).last, 23);
});

test('removing the final row of a page returns to the preceding populated page', () => {
    assert.equal(paginationState(21, 10, 3).page, 3);
    const state = paginationState(20, 10, 3);
    assert.equal(state.page, 2);
    assert.equal(state.first, 11);
    assert.equal(state.last, 20);
    assert.equal(state.hasNext, false);
});

test('added and invalid rows resolve to the page containing their current position', () => {
    assert.equal(pageContainingRow(0, 10), 1);
    assert.equal(pageContainingRow(9, 10), 1);
    assert.equal(pageContainingRow(10, 10), 2);
    assert.equal(pageContainingRow(49, 10), 5);
    assert.equal(pageContainingRow(50, 10), 6);
});

test('changing page size keeps the previously first visible item in the new range', () => {
    const oldPage = paginationState(61, 10, 5);
    const changed = paginationState(61, 25, pageContainingRow(oldPage.startIndex, 25));
    assert.ok(changed.startIndex <= oldPage.startIndex);
    assert.ok(changed.endIndex > oldPage.startIndex);
    assert.equal(changed.page, 2);
});

test('invalid settings cannot create negative ranges, zero sizes, or infinite pages', () => {
    for (const value of [0, -10, Infinity, NaN, 'bad', 1.5]) {
        const state = paginationState(21, value, value);
        assert.equal(state.pageSize, 10);
        assert.equal(state.page, 1);
        assert.equal(state.pages, 3);
        assert.equal(pageContainingRow(value, 0), 1);
    }
    assert.equal(paginationState(-1).total, 0);
    assert.equal(paginationState('21', '15', '2').first, 16);
});
