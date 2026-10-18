# Production overview donut

The dashboard's Production overview has an interactive SVG donut rendered in Blade. It uses the existing server-provided production counts and percentages. The Production workload chart, calculations, forms, production statuses, delivery and payment logic are unchanged. No chart library or React dependency was added.

## Colour and interaction

- Navy (`#294968` in the current Clean appearance) represents ongoing work and follows the dashboard's existing ink colour.
- Green uses the existing `--dashboard-chart-complete` token (`#279d84`) for completed production. Other application colours are unchanged.
- Both statuses also have text labels, counts and percentages. Colour is a supporting cue.
- Hover or keyboard focus highlights the matching slice and legend and displays its saved values. Escape dismisses that detail, including when keyboard focus is elsewhere. Motion respects the reduced-motion preference.
- Slice and legend links open the existing `/production?status=ongoing` or `/production?status=completed` filters. Touch users can use the labelled legend links without hover.
- Empty, all-ongoing and all-completed datasets have explicit states; zero-count slices are omitted.

## Immediate undo

Select **Original** in the chart header to show the previous chart. Select **Interactive** to restore the new chart. The choice is saved only in this browser under `qualities:production-overview-style`; it does not alter data or the application's appearance preference.

The original chart body remains in `resources/views/dashboard/partials/production-overview-original.blade.php`.

## Source rollback

Before this change, the four existing integration files were copied with SHA-256 hashes into `storage/app/private/design-backups/production-donut-20261018/`:

- `resources/views/dashboard/partials/production-charts.blade.php`
- `resources/js/app.js`
- `resources/css/app.css`
- `package.json`

For a code rollback, compare those snapshots to the current files and undo only the donut integration. Preserve any later unrelated edits. Remove the donut's added partials, stylesheet, JavaScript module and focused tests if no longer needed, then rebuild assets. Do not reset the repository or restore the database for this visual change.

## Verification

- Full PHP suite: 125 tests / 1,677 assertions passed. The donut's six feature tests cover counts, percentages, filters, original view, zero/100% datasets and unchanged saved jobs. Those six tests were rerun after the final Blade change.
- JavaScript suite: 20 tests passed, including outside-focus Escape dismissal, native touch/modified-click links, blocked storage and style undo.
- Vite production build and the new PHP test's Pint check passed.
- Isolated Chrome verification covers real slice clicks, keyboard navigation, hover without layout shift, saved style choice, reduced motion, and 320px / 390px / 768px layouts with at least 44px legend targets. It uses GET navigation and does not change business records.

Local verification screenshots and the browser report are kept privately in `storage/app/private/ui-verification/production-donut/`.
