# Qualities ERP: system readiness, design and UX audit

Date: 17 October 2026. Scope: the current Laravel application in `D:\Projects\Qatar`.

## Decision

**The technology stack fits the client's current requirements. The application is suitable for a supervised demonstration, but is not yet ready for an unconditional daily-use handover.** Complete access control, data reconciliation, correction/recovery workflows, date consistency, attachment serving and operational recovery first.

The user confirmed that the demo needs **one login account**. The final system is expected to have approximately **three users: Admin, Staff and a third role still to be confirmed**. Do not assume the third role is Accounts or Production. Define permissions from responsibilities rather than from the number of accounts.

This audit made no application-code, configuration or live business-data changes. The report and audit evidence were saved separately. Existing uncommitted implementation work was preserved.

## What was examined and what remains unverified

Examined:

- Routes, controllers, models, validation, pricing, document numbering, schema, seed data, configuration and automated tests.
- Browser review of dashboard; customers and customer history; quotation/invoice lists, creation and details; production board; delivery list, creation and details; payment list, creation and details; custom-item interaction and existing artwork display.
- Desktop 1280 x 720, tablet production at 768 x 768, and representative dashboard/production/entry forms at 320 x 568.
- Read-only MySQL reconciliation of invoice balances, customer balances, allocations and saved item arithmetic.
- Actual generated quotation, invoice, delivery and receipt PDFs, rendered for visual inspection. Additional isolated PDF edge tests are recorded below.
- Official framework/PHP support information and WCAG guidance.

This is an **expert technical and usability evaluation**, not completed empirical UX research. No staff interviews, workplace observation, NVDA session, physical-printer trial, production load test or external penetration test occurred. The research programme below specifies how to close those gaps. Browser checks used the in-app browser; cross-browser/device coverage remains a release task.

Dependency-advisory checks were attempted with Composer and npm. Both failed to reach their registries under the current network/cache restrictions. Vulnerability counts are **unavailable**, not zero. External hosting, OS backup schedules and monitoring services were not inspected.

## 1. Stack suitability and maintenance

| Area | Current implementation | Assessment |
|---|---|---|
| Backend | PHP, Laravel MVC | Appropriate for customer, quotation, invoice, production, delivery and payment transactions. |
| UI | Blade HTML, JavaScript, Tailwind CSS | Appropriate for this small internal operational application; current interaction gaps can be fixed within this architecture. |
| Data | MySQL, Eloquent, transactions and locks | Appropriate for the expected small staff group; real concurrent MySQL tests are still needed. |
| Documents | Dompdf plus shared print templates | Useful foundation; actual printers, long documents and client terminology still need sign-off. |
| Build | Vite | Appropriate asset tooling. |

Observed locked versions: Laravel 12.70.0, Tailwind 4.3.3, Vite 7.3.7, Axios 1.20.0, laravel-dompdf 3.1.2 and Dompdf 3.1.6. The inspected local PHP CLI is **8.2.12**, an old patch release; do not copy this runtime unchanged into a new production deployment.

As of this audit, Laravel 12 security support ends **24 February 2027** and PHP 8.2 security support ends **31 December 2026**. Plan a tested runtime/framework upgrade and routine dependency patching within those dates. Stack suitability does not remove the need for maintenance. Sources: [Laravel support policy](https://laravel.com/framework/docs/12.x/releases#support-policy), [PHP supported versions](https://www.php.net/supported-versions.php).

Daily document volume, simultaneous operators, storage growth, hardware and connectivity are still unknown. Three expected accounts are compatible with this architecture; capacity cannot be certified from account count alone.

## 2. What already works well

- Server-side pricing recalculates submitted items; the server does not trust a browser total.
- Payment allocation uses integer cents, validates invoice ownership, rejects overpayments and groups split allocations.
- Delivery validates item ownership and caps sizes/quantities against existing reservations.
- Quotation conversion is transactional and guarded against duplicate conversion.
- Production completion is correctly separate from delivery completion and payment settlement.
- Customer quick-add and confirmation dialogs have structured feedback, validation and focus handling.
- Production cards retain full descriptions/quantities; dropdowns provide an alternative to dragging.
- Shared visual styles, branding, document templates and preserved validation input are useful foundations.
- In the inspected responsive views, root/page horizontal overflow did not reproduce. Wide boards and tables scroll inside their own containers.

Automated verification in this audit: **53 PHP tests passed, 645 assertions; 6 JavaScript tests passed**. Two existing PDF cases were also rerun with review output enabled: **2 passed, 15 assertions**, producing fresh mixed-item, thermal and long-invoice artifacts. These are reruns, not two additional unique test cases. Tests use isolated SQLite in memory, not the live MySQL database. A passing suite is evidence for covered scenarios, not a production-readiness certificate.

## 3. Priority definitions

- **P0:** block access by untrusted users until fixed.
- **P1:** resolve before real daily-use handover.
- **P2:** usability, accessibility, consistency and maintainability improvements; address the highest-impact ones before staff pilot.
- **P3:** optimize after measuring realistic volume or observing a recurring need.

## 4. Security, data accuracy and operational findings

| ID / priority | Finding and evidence | Required outcome |
|---|---|---|
| S01 / P0 | Business pages, write endpoints, APIs and PDF routes have no login or role protection. `routes/web.php:14`, `bootstrap/app.php:13`; route inspection shows only web middleware. | One authenticated demo account initially; protect every business/API/document route. Before final rollout, implement agreed per-role permissions, logout/session expiry and authorization tests. |
| S02 / P1 | Current configuration is local development with debug enabled, and the configured base URL uses a different port from the running application. This does not mean it is publicly exposed today. | Production environment, debug off, HTTPS, secure sessions, correct URL, restricted secrets and a production web-server setup rooted at public/. Follow the [Laravel deployment guide](https://laravel.com/framework/docs/12.x/deployment). |
| S03 / P1 | No actor/reason/previous-value history for status or customer changes. `ProductionController.php:92`, `CustomerController.php:89`; generic updated_at is not a business audit trail. | Record who changed what, when, from/to and why for important actions. Preserve financial originals; surface stale concurrent edits. |
| S04 / P1 | One manual SQL snapshot exists, but scheduled/off-device backup, retention, artwork backup and a successful restore rehearsal were not evidenced. README is still generic Laravel documentation. | Automated database plus artwork backups, independent destination, protected access, retention/failure alerts and a tested restore. Agree acceptable data loss and recovery time with the client. |
| S05 / P1 | UTC is used for business-day calculations while the header explicitly shows Doha. `config/app.php:68`, `DashboardController.php:29,33`, `PaymentController.php:38`. | Use Asia/Qatar business-date boundaries/defaults consistently, while retaining UTC timestamps where appropriate. Test Doha midnight, late-night payments and year-end. |
| S06 / P1 | Existing artwork fails to load on quotation 6. The file exists in storage/app/public, but public/storage is an empty normal directory; browser reports naturalWidth=0. `quotations/show.blade.php:134,137`. | Repair attachment retrieval and verify both preview and original download after deployment and restore. |
| S07 / P1 | Artwork accepts SVG and is designed to be served directly from public storage. `StoreDocumentRequest.php:54`, `QuotationController.php:100`. | Private authorized file retrieval, clear type/size limits, safe attachment headers and a deliberate SVG rejection/sanitization policy. No exploit was attempted. |
| S08 / P1/P2 | Public product API returns complete Product models including cost_price. `QuotationController.php:72`, `Product.php:32`. | Authorize the API and serialize only fields permitted for the current role. |
| S09 / P2 | Full resource routes register quotation/invoice/delivery edit/update/destroy methods that are not implemented. `routes/web.php:21,27,30`. | Restrict routes to implemented actions and implement only the required authorized correction workflows. Missing-controller routes must not remain accidental error paths. |
| S10 / P2 | No repository-defined deployment/rollback procedure, monitored failures or support runbook; logging uses a local single file. External services remain unverified. | Log rotation, uptime/error/disk/backup monitoring, repeatable deployment and rollback, named support owner and recovery instructions. |
| S11 / P2 | Demo seeder contains hard-coded demo credentials. `DatabaseSeeder.php:27`. | Separate demo seeding from production setup; use controlled account provisioning and replace demo credentials when authentication is introduced. |

### Current data must be reconciled before handover

Read-only snapshot, in QAR:

| Measure | Amount |
|---|---:|
| Invoice grand totals | 74,580.00 |
| Receipts linked to invoices | 46,787.00 |
| Invoice outstanding | 27,793.00 |
| All receipts, including older unallocated payments | 49,777.00 |
| Older unallocated receipts | 2,990.00 |
| Displayed customer outstanding | 26,003.00 |

All current invoice paid/balance fields match their linked receipts: **zero allocation-to-invoice balance discrepancies**. The difference between customer and invoice totals has an explanation: customer balances subtract all receipts, while invoice balances subtract only allocated receipts; negative customer balances are then clipped to zero. A customer with **QAR 1,200 credit** and no invoices appears as zero. The current UI cannot clearly show or allocate those older credits.

Required result: expose **invoice outstanding, unapplied credit and net customer position** as distinct concepts inside the existing customer/payment views, then reconcile and approve opening balances. Evidence: `Customer.php:46`, `SalesInvoice.php:59`, payment routes and allocation UI. Do not fix this by silently changing historical amounts.

There are also confirmed seeded/historical inconsistencies:

- QT-2026-001: header subtotal QAR 14,500, saved item-line sum QAR 20,100.
- QT-2026-010: status Draft although linked to an invoice. The current converter correctly sets Invoiced; this does not establish a current conversion bug.
- Invoice 1 blazer line: quantity 100 x rate 175 should be QAR 17,500 with zero stored fees/discount, but saved line is QAR 11,900.
- Invoice 4 medical line: 165 x 110.30 should be QAR 18,199.50, but saved line is QAR 18,200.

These incorrect values are explicitly present in DatabaseSeeder.php (`404`, `431`, `451`, `563`, `689`). Recalculation found **2 mismatches among 10 invoice item rows**, **0 among 10 quotation item rows**. New document stores use PricingService. Separate demonstration data from business data and obtain an approved reconciliation; do not automatically rewrite issued documents.

## 5. Workflow and error-recovery improvements

| ID / priority | Gap | Improvement within the existing system |
|---|---|---|
| W01 / P1 | Save as Draft exists, but no reopen/edit handler or usable resume action. `documents/create.blade.php:365`; QuotationController has no edit/update. | Resume/edit Draft quotations. Define revisions for Sent/Approved quotations and preserve versions once issued or converted. Test reopen, edit, save and convert end to end. |
| W02 / P1 | Wrong or accidentally repeated payment has no in-app reversal. Payments only expose index/create/store/show. | Authorized reason-required void/reversal that preserves the original and recalculates allocations/balances transactionally. Define refund/cheque failure policy if actually used. |
| W03 / P1 | Pending delivery notes reserve quantities; no cancellation/replacement releases them. `DeliveryNote.php:35`, `DeliveryController.php:72,152`. | Controlled cancellation/replacement with clear reservation effects. Preserve dispatch/delivery history and require a reason for reversal. |
| W04 / P1/P2 | Long forms lack a consistent dirty-state guard and saving/retry protection. | Protect unsaved input, indicate pending/success/failure, block duplicate clicks and make retries safe server-side. Reopen a saved record after an uncertain network response before retrying payment. |
| W05 / P2 | Valid custom_size_notes can pass max:2000 validation but hit actual MySQL varchar(255). `StoreDocumentRequest.php:39`, original migration:79,123. | Align storage and validation; test 255/256/2000 characters against staging MySQL. |
| W06 / P2 | Quotation generation writes Sent although the action only saves/redirects. `QuotationController.php:126`, create form:371. | Agree whether Sent means generated or actually shared; use explicit labels/status actions. A generated PDF alone is not evidence of delivery to the customer. |
| W07 / P2 | Discount helper says fees remain payable, but document discount can reduce the full total including fees. `documents/create.blade.php:361`, `PricingService.php:150`. | Confirm the client policy, then make calculation, help text and PDFs agree. Do not change pricing policy by assumption. |
| W08 / P2 | Quote/invoice/delivery pagination drops filters; other lists preserve them. Controller paginate calls at :53/:38/:34 lack withQueryString. | Keep search/filter context on page 2, detail/back, and after actions. Label whether KPIs summarize all or filtered records. |
| W09 / P2 | Customer history counts split allocations as individual receipts; the payment register groups them. `customers/show.blade.php:128,272`, `PaymentController.php:17`. | Show one canonical payment with expandable allocations consistently. |
| W10 / P2 | Production notes and target-date changes are not editable in normal card UI. Updates overwrite without version checks. | Add concise blocker notes and reasoned due-date changes inside Update job; preserve who/when/history. Define stage movement/rework rules with the client. |
| W11 / P2 | Stage update reloads the entire board, losing later-column position, open card and focus. `production/index.blade.php:231`. | Preserve scroll/focus/context; update card/counts in place where practical, with clear saved/error feedback. |
| W12 / P2 | Payment balance temporarily becomes zero while invoices load; controls remain active. `payments/create.blade.php:163`. | Add a loading balance state, disabled dependent controls and an explicit retry; preserve the existing request-counter protection against stale responses. Preview how a payment will split before posting. |

## 6. UI design and accessibility

The visual foundation is coherent. The next iteration should improve sustained working comfort, predictability and error recovery. Compact spacing should not require staff to struggle to read operational data.

| Area | Observed issue / recommendation |
|---|---|
| Typography | Shared secondary text is 11px and body 12px; production descriptions are 11px. Validate real monitor distance and staff eyesight. As a design starting point, trial 13-14px primary operational text and 12px supporting text while retaining compact spacing. These are design targets, not WCAG minimum-font rules. |
| Contrast | Small white Record Payment text on emerald-600 is approximately 3.74:1; emerald-500 hover approximately 2.51:1. Amber-600 Awaiting approval on white is approximately 3.21:1. Darken these specific colours. Most table slate text already has a darker override. WCAG normal text target is 4.5:1; [official contrast guidance](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html). |
| Touch targets | Check close, print, row action and add/remove controls including spacing exceptions. WCAG 2.2 AA uses a 24 x 24 CSS pixel target or permitted alternatives; choose more comfortable approximately 40-44px frequent touch controls where space allows. [Official target guidance](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html). |
| Dialogs | Custom-item overlay is a div without dialog semantics/focus containment. Escape did not close it in browser testing. Use the native dialog pattern already used elsewhere, with named close, initial focus, Escape and return focus. |
| Dynamic fields | Product/specification/size/price/discount/fee inputs lack reliable accessible names. Associate each with product, size/unit and purpose; connect inline error messages. |
| Filters and statuses | Several list filters and quotation/delivery status controls have no associated labels. Placeholders/options do not replace labels. Add screen-reader labels and clarify auto-submit behaviour. |
| Error handling | Existing validation preservation is good. Add linked/focusable error summary, first-invalid focus, named dismiss buttons and consistent announcements without duplicate errors. |
| Navigation | Add skip-to-main and aria-current to main navigation. Standardize back actions and use contextual destinations for KPI links. |
| Mobile entry | Root overflow is contained, but at 320px the quotation table has about 974px content in a 238px container. Use per-item expandable editing or sticky identity/total columns and explicit scroll cues. Payment form reflows more comfortably. |
| Tablet layout | At 768px the fixed 256px sidebar leaves only 512px workspace. Consider collapsing navigation at a wider breakpoint for production/entry work after testing real devices. |
| Board layout | Full card summaries and non-drag controls are strengths. Keep stage identity, due date and priority easy to scan; avoid forcing a six-column desktop presentation on touch devices. |
| Forms | Use readable units, examples, decimal/quantity keyboards and sensible tab order. Keep totals near entry/save; preserve details when quick-adding a customer. Review 12px mobile inputs on real phones for reading/zoom behaviour. |
| Search/selection | Native whole-list customer/catalog/invoice selects work with current small data. Add accessible search when lookup volume warrants it; distinguish similar names by code and phone. Warn about likely duplicate customers with a deliberate override. |
| Empty/loading/offline | Provide no-results explanation, clear filters and useful next action; distinguish zero balance from loading/unavailable. Define what works during disconnection and how staff recover. |
| Content/trust | Counter Online is hard-coded. Delivered & Signed and PAYMENT RECEIVED VERIFIED imply checks not evidenced by those controls. Use language that reflects the actual action and verified evidence. |
| Demo separation | Hide Load Sample Items from normal operational use. Keep training/demo records visibly separate. |
| Terminology | Agree job/order/project, quotation/proposal, generated/sent, pending/approved, production complete/delivered, and paid/credit/outstanding. Pending Quotations currently includes Draft and Sent but says Awaiting approval. |
| Language/localization | Confirm English/Arabic/other language needs; test names, text direction, font rendering, phone/currency/date comprehension and Doha business dates. Arabic support has not been validated. |

## 7. Page-by-page improvement map

| Existing area | Highest-value next improvements |
|---|---|
| Login / global shell | One authenticated demo user; final permission matrix; honest connection status; accessible navigation; better tablet use of space. |
| Dashboard | Actionable urgent/overdue/ready links, correct Doha dates, clear KPI definitions and consistent balances; test whether staff can identify the next job quickly. |
| Customers | Visible credit/net balance, consistent grouped payment history, duplicate warnings, reliable search and paginated growing history. |
| Quotations | Resume drafts, controlled revisions, truthful Sent meaning, correct terms, accessible item entry and working/private artwork. |
| Invoices | Approved correction/void policy, reconciled legacy data, clear payment/delivery/production links, stable financial history. |
| Production | Fast mobile/keyboard stage change, saved focus/scroll, editable blockers and reasoned date changes, history, stale-update handling. Validate whether one stage for an entire mixed order reflects real work. |
| Delivery | Remaining/reserved quantities, cancellation/replacement, clear handover proof and status meaning, usable small-screen item entry. |
| Payments | Allocation preview, visible older credits, authorized reversal, safe retry, grouped history, loading/error feedback and reconciliation. |
| Print/PDF | Agreed company/terms data, readable brand and sizes, consistent web/PDF wording and actual printer acceptance. |

### PDF observations

Four actual generated samples each rendered as one page without obvious clipping or overlap. A fresh isolated 16-line invoice rendered across four pages; every page was visually inspected, with repeated column headers, intact rows and final totals. A fresh 80mm-format thermal sample was also rendered and inspected. Downloaded A4 PDFs already contain page numbering; it should be preserved. A4 samples are approximately 1.1MB each; investigate font/image reuse or subsetting only if download/storage measurements justify it.

The gold logo is faint against white, particularly the small tagline. Test the client's printer/grayscale output and obtain an approved print-friendly brand treatment. Small 8-10px specification text needs physical readability testing. A logo is exempt from the WCAG text contrast rule, but practical print legibility still matters.

Web quotation details hard-code 50% advance terms (`quotations/show.blade.php:110`), while shared PDF terms come from configuration and are absent from the reviewed sample. Agree terms with the client and use one source consistently; do not infer commercial/legal terms.

Test current-balance reprints versus an issued-at-time snapshot: receipt PDFs explicitly say balances are current at generation time. Confirm whether the client expects a historical receipt copy or a live statement when reprinting.

Physical A4/thermal margins, longer 10-page stress cases, exceptionally long specifications, Arabic shaping, stamp/signature workflow and multiple printer models remain client acceptance tasks.

## 8. Complete UX research programme

The following is the remaining research work, not a claim that it has been completed. Research should produce decisions tied to evidence, not just a visual redesign.

| Research area | What to do | Deliverable / decision |
|---|---|---|
| Business discovery | Interview owner plus each real responsibility group. Confirm success, approvals, exceptions, final third role and data ownership. | Agreed scope, permission matrix, terminology and business rules. |
| Contextual inquiry | Observe real counter/order intake, WhatsApp/paper handoffs, production updates, payments and dispatch, including peak periods and interruptions. | Current workflow, time/copying/rework notes, devices/network/printer constraints. |
| Research-based role profiles | Document actual goals, digital confidence, language, accessibility and device needs. Avoid invented personas. | Role/task profiles and priority jobs-to-be-done. |
| Journey and handoffs | Map enquiry -> customer -> quotation -> approval -> invoice -> production -> partial delivery -> payment -> reconciliation; include returns/corrections/delays. | Journey/service blueprint showing handoff owners, source of truth and failure recovery. |
| Task/frequency analysis | Measure frequent tasks, rare high-risk tasks, volume, interruptions and task switching. | Prioritized task list and dashboard hierarchy. |
| Information architecture | Test finding a customer, draft, urgent job, overdue invoice, receipt and undelivered quantity. Use tree/card testing only where grouping is uncertain. | Navigation and labels staff can understand without coaching. |
| Content comprehension | Ask staff to explain status, discounts, reservation, due date, credit and allocation in their own words. | Shared glossary, concise helper text and clear money/status meaning. |
| Moderated usability | Initial qualitative round with approximately 5-8 representative participants if available, covering all roles. If only three actual staff exist, include all three, then retest; this is not a statistical sample. | Observed success/failure, hesitation, errors and assistance by task. |
| Accessibility | Keyboard end to end, NVDA, focus/dialogs/errors, contrast, 200% zoom, 320px reflow, reduced motion, high contrast and target sizes. | Reproducible accessibility defects and retest evidence. |
| Responsive/device study | Test actual counter monitor, low-resolution laptop and the phones/tablets staff really use. Include long names and keyboard opening. | Device-specific task failures and appropriate layout decisions. |
| Resilience and trust | In staging, simulate slow/lost network, expired session, double submit, browser Back/refresh, multi-tab and simultaneous staff updates. | Safe retry/recovery rules, truthful feedback, zero silent financial errors. |
| Document study | Have staff and owner interpret printed documents; test recipient handover/signature, reprints, terms and printer output. | Signed-off templates and print workflow. |
| Data/privacy research | Confirm who may see costs, contacts, balances and artwork; retention/export/delete/backup responsibilities. | Least-privilege and retention decisions matched to client obligations; no legal-compliance certification is asserted. |
| Comparative review | Compare the staff's existing spreadsheet/paper/WhatsApp process and, if useful, a few relevant tools. | Identify proven conventions and measurable improvements without copying unnecessary modules. |
| Synthesis/prioritization | Group observed issues, assess severity/frequency/business impact, trace each change to a finding. | Prioritized backlog with acceptance criteria and owner. |
| Prototype/retest | Test risky changes with a small prototype, then implement in the existing screens and retest. | Evidence that the proposed fix actually helps. |
| Pilot/adoption | Small supervised rollout, parallel reconciliation, training, quick reference and support contact. Review issues after a complete working cycle. | Owner/staff acceptance, training completion and go-live decision. |
| Ongoing measurement | Agree privacy-conscious metrics and review cadence; do not record unnecessary sensitive input. | Continuous improvement based on task outcomes, not appearance alone. |

### Required usability scenarios

1. Find an existing customer with a similar name, avoid a duplicate, and update contact details.
2. Create a mixed-category quotation with garment sizes, services, setup/printing charges and discount; explain the resulting total.
3. Save a draft, leave, resume later and revise a specification.
4. Approve and convert once; identify linked quotation, invoice and production job.
5. Find the urgent/late job and change stage with mouse, keyboard and touch; add a blocker and understand downstream effects.
6. Record partial payment and a payment across multiple invoices; preview allocation; explain credit versus due.
7. Recover from wrong customer, overpayment, accidental duplicate entry, network timeout and session expiry.
8. Reserve and dispatch partial sizes, cancel a mistaken Pending note, complete handover and reprint.
9. Correct a quotation/payment/delivery mistake through the approved history-preserving procedure.
10. Verify own permissions and rejected forbidden actions; restore a test backup and confirm documents/artwork are usable.

### Proposed success criteria to agree with the client

These are targets, not measured results:

- At least 90% unassisted completion of routine tasks in the agreed test set; all financial/quantity-critical scenarios complete correctly.
- No duplicate posting, unexplained balance, lost entered work or over-dispatch in tested recovery/concurrency scenarios.
- Find an urgent/overdue job and complete a stage update in approximately 15 seconds each, subject to real baseline measurements.
- Compare task time, help requests, error rate, abandonment and rework against the current process.
- Collect a Single Ease Question after tasks and optional SUS after sessions; retain the observed causes behind any score.
- Confirm all P0/P1 issues closed, backup restore demonstrated, permissions tested and staff/printer acceptance recorded before daily handover.

## 9. Engineering verification and release plan

Existing tests cover many normal lifecycle, rounding, ownership, overpayment, over-delivery, duplicate conversion and PDF cases. Missing coverage includes MySQL-only locks, strict VARCHAR boundaries, real concurrency, permissions, reversals/cancellation, draft resume, credits, stale status updates, filtered page 2 and Doha-midnight boundaries.

Use staging MySQL with realistic but isolated data. Test two/three users simultaneously creating documents, converting the same quotation, paying the same invoice and dispatching the same remaining quantities. Measure realistic one-year/three-year history before optimizing unbounded customer/invoice selectors, entire production columns or the shared document-number lock. These are growth risks, not demonstrated present-day slowness or duplicate-number defects.

Recommended order:

1. **Controlled demo:** one login, protected routes/documents, clearly isolated demo data, working artwork, agreed status/terms wording and no operational sample loader.
2. **Daily-use foundations:** reconcile existing financial records/credits; implement draft resume, safe financial correction and delivery cancellation; fix business timezone, validation/storage mismatch and important accessibility gaps; add audit history.
3. **Operational hardening:** supported patched runtime, production configuration/HTTPS, backup plus successful restore, monitored errors, repeatable deployment/rollback and advisory checks with registry access.
4. **Staff pilot:** validate final roles and permissions, complete actual-user UX sessions, concurrency/recovery tests and physical printer checks; fix observed issues and retest.
5. **Handover:** approved opening balances, trained users, documented support/recovery and owner acceptance. Then monitor daily use and optimize measured bottlenecks.

These steps strengthen the existing system. They do not require introducing unrelated sales, HR, inventory or other business modules.

## 10. Evidence index

Important source entry points (paths below are relative to `D:\Projects\Qatar`):

- `routes/web.php`, `bootstrap/app.php`, `config/app.php`, `config/session.php`.
- `app/Http/Controllers/{Quotation,Invoice,Payment,Delivery,Production,Dashboard,Customer,Pdf}Controller.php`.
- `app/Http/Requests/StoreDocumentRequest.php`, `app/Services/PricingService.php`, `app/Services/DocumentNumber.php`.
- `app/Models/{Customer,SalesInvoice,CustomerReceipt,DeliveryNote,ProductionOrder,Product}.php`.
- `resources/views/documents/create.blade.php`, `resources/views/production/partials/job-card.blade.php`, `resources/views/layouts/app.blade.php`, the existing list/detail forms.
- `resources/css/ui.css`, `resources/css/layout.css`, `resources/css/production.css`, `resources/js/feedback.js`, `resources/js/quick-customer.js`.
- `database/seeders/DatabaseSeeder.php`, original ERP migration, `phpunit.xml`, `tests/Feature`, `tests/js`.

Private audit evidence is under `storage/framework/testing/system-audit-20261017`: browser observations, broken-artwork screenshot, the four downloaded PDFs and rendered pages. This folder contains application/customer information and should not be published as a public report asset.

Official references: [Laravel support](https://laravel.com/framework/docs/12.x/releases#support-policy), [Laravel deployment](https://laravel.com/framework/docs/12.x/deployment), [PHP support](https://www.php.net/supported-versions.php), [WCAG contrast](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html), [WCAG targets](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html).
