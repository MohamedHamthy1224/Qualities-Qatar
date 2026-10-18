import test from 'node:test';
import assert from 'node:assert/strict';
import {initializeProductionDonut} from '../../resources/js/production-donut.js';

class Element {
    constructor(dataset = {}) { this.dataset = dataset; this.hidden = false; this.attributes = {}; this.textContent = ''; }
    setAttribute(name, value) { this.attributes[name] = value; }
    removeAttribute(name) { delete this.attributes[name]; }
    closest(selector) {
        if (selector === '[data-donut-series]' && this.dataset.donutSeries) return this;
        if (selector === '[data-donut-style-choice]' && this.dataset.donutStyleChoice) return this;
        return null;
    }
}

function fixture({preference, blockedStorage = false, empty = false} = {}) {
    const interactive = new Element({donutStyleChoice: 'interactive'});
    const original = new Element({donutStyleChoice: 'original'});
    const views = ['interactive', 'original'].map(donutView => new Element({donutView}));
    const ongoing = new Element({donutSeries: 'ongoing', donutLabel: 'Ongoing', donutCount: '13', donutPercent: '92.9'});
    const completed = new Element({donutSeries: 'completed', donutLabel: 'Production complete', donutCount: '1', donutPercent: '7.1'});
    ongoing.href = '/production?status=ongoing'; completed.href = '/production?status=completed';
    const tooltip = new Element(); tooltip.id = 'production-donut-tooltip';
    const label = new Element(); const count = new Element(); const percent = new Element(); const hint = new Element(); const controls = new Element();
    const one = new Map([
        ['[data-donut-style-controls]', controls], ['[data-donut-tooltip]', empty ? null : tooltip],
        ['[data-donut-hint]', hint], ['[data-donut-tooltip-label]', label],
        ['[data-donut-tooltip-count]', count], ['[data-donut-tooltip-percent]', percent],
    ]);
    const listeners = new Map();
    const root = new Element();
    root.querySelector = selector => one.get(selector);
    root.querySelectorAll = selector => ({
        '[data-donut-style-choice]': [interactive, original], '[data-donut-view]': views,
        '[data-donut-series]': empty ? [] : [ongoing, completed],
    })[selector] ?? [];
    root.addEventListener = (type, listener) => { if (!listeners.has(type)) listeners.set(type, new Set()); listeners.get(type).add(listener); };
    root.removeEventListener = (type, listener) => listeners.get(type)?.delete(listener);
    let prevented = false;
    const emit = (type, detail = {}) => {
        const event = {target: root, preventDefault() { prevented = true; }, ...detail};
        [...(listeners.get(type) ?? [])].forEach(listener => listener(event));
        windowListeners.get(type)?.(event);
        return event;
    };
    const saved = new Map(preference ? [['qualities:production-overview-style', preference]] : []);
    const windowListeners = new Map();
    const window = {
        localStorage: {getItem: key => { if (blockedStorage) throw new Error('Storage blocked'); return saved.get(key); }, setItem: (key, value) => { if (blockedStorage) throw new Error('Storage blocked'); saved.set(key, value); }},
        addEventListener: (type, listener) => windowListeners.set(type, listener),
        removeEventListener: type => windowListeners.delete(type),
    };
    const document = {querySelectorAll: () => [root]};
    const controller = initializeProductionDonut(document, window)[0];
    return {root, document, window, controller, views, ongoing, completed, interactive, original, tooltip, label, count, percent, hint, controls, emit, saved, windowListeners, prevented: () => prevented};
}

test('hover and keyboard expose the supplied values without changing counts or percentage data', () => {
    const f = fixture();
    const before = JSON.stringify([f.ongoing.dataset, f.completed.dataset]);
    f.emit('pointerover', {target: f.ongoing, pointerType: 'mouse'});
    assert.equal(f.root.dataset.activeSeries, 'ongoing');
    assert.equal(f.label.textContent, 'Ongoing');
    assert.equal(f.count.textContent, '13 jobs');
    assert.equal(f.percent.textContent, '92.9% of jobs');
    assert.equal(f.tooltip.hidden, false);
    f.emit('focusin', {target: f.completed});
    assert.equal(f.root.dataset.activeSeries, 'completed');
    assert.equal(f.count.textContent, '1 job');
    assert.equal(f.percent.textContent, '7.1% of jobs');
    assert.equal(JSON.stringify([f.ongoing.dataset, f.completed.dataset]), before);
});

test('Escape dismisses hover content without moving focus and re-entry restores it', () => {
    const f = fixture();
    f.emit('pointerover', {target: f.completed});
    f.emit('keydown', {key: 'Escape'});
    assert.equal(f.tooltip.hidden, true);
    assert.equal(f.root.dataset.activeSeries, undefined);
    f.emit('pointerover', {target: f.completed});
    assert.equal(f.tooltip.hidden, true);
    f.emit('pointerleave');
    f.emit('pointerover', {target: f.completed});
    assert.equal(f.tooltip.hidden, false);
    f.emit('pointerleave');
    assert.equal(f.tooltip.hidden, true);
});

test('Original is an immediate persistent chart-only undo and Interactive can be restored', () => {
    const f = fixture();
    f.emit('click', {target: f.original});
    assert.equal(f.root.dataset.donutStyle, 'original');
    assert.equal(f.views[0].hidden, true);
    assert.equal(f.views[1].hidden, false);
    assert.equal(f.original.attributes['aria-pressed'], 'true');
    assert.equal(f.saved.get('qualities:production-overview-style'), 'original');
    const reloaded = fixture({preference: f.saved.get('qualities:production-overview-style')});
    assert.equal(reloaded.root.dataset.donutStyle, 'original');
    reloaded.emit('click', {target: reloaded.interactive});
    assert.equal(reloaded.views[0].hidden, false);
    assert.equal(reloaded.views[1].hidden, true);
});

test('Escape dismisses mouse hover when keyboard focus is outside the chart and cleans up on destroy', () => {
    const f = fixture();
    f.emit('pointerover', {target: f.completed, pointerType: 'mouse'});
    assert.equal(f.tooltip.hidden, false);
    f.windowListeners.get('keydown')({key: 'Escape', target: new Element()});
    assert.equal(f.tooltip.hidden, true);
    f.controller.destroy();
    assert.equal(f.windowListeners.has('keydown'), false);
});

test('Escape dismissal survives pointer departure while the same status retains keyboard focus', () => {
    const f = fixture();
    f.emit('focusin', {target: f.completed});
    f.emit('keydown', {key: 'Escape'});
    f.emit('pointerleave');
    assert.equal(f.tooltip.hidden, true);
    f.emit('focusin', {target: f.ongoing});
    assert.equal(f.tooltip.hidden, false);
    assert.equal(f.label.textContent, 'Ongoing');
});

test('touch and modified status clicks retain their native filter URLs and navigation', () => {
    const f = fixture();
    f.emit('pointerover', {target: f.completed, pointerType: 'touch'});
    assert.equal(f.tooltip.hidden, true);
    f.emit('click', {target: f.completed, ctrlKey: true});
    assert.equal(f.prevented(), false);
    assert.equal(f.completed.href, '/production?status=completed');
    assert.equal(f.ongoing.href, '/production?status=ongoing');
});

test('blocked storage and empty data keep local undo working without failures', () => {
    const f = fixture({blockedStorage: true, empty: true});
    assert.equal(f.root.dataset.donutStyle, 'interactive');
    assert.doesNotThrow(() => f.emit('click', {target: f.original}));
    assert.equal(f.views[1].hidden, false);
    assert.equal(f.controls.hidden, false);
    assert.doesNotThrow(() => f.emit('keydown', {key: 'Escape'}));
});

test('preference sync is scoped to this chart and repeat initialization does not double-bind', () => {
    const f = fixture();
    f.windowListeners.get('storage')({key: 'qualities:appearance', newValue: 'original'});
    assert.equal(f.root.dataset.donutStyle, 'interactive');
    f.windowListeners.get('storage')({key: 'qualities:production-overview-style', newValue: 'original'});
    assert.equal(f.root.dataset.donutStyle, 'original');
    assert.equal(initializeProductionDonut(f.document, f.window)[0], f.controller);
    f.controller.destroy();
    assert.equal(f.root.dataset.donutStyle, 'interactive');
    assert.equal(f.controls.hidden, true);
    f.emit('click', {target: f.original});
    assert.equal(f.root.dataset.donutStyle, 'interactive');
});
