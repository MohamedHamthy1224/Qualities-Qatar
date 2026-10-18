# Using the installed Codex design skills

Installed on 17 October 2026 for this Windows user, across projects:

- UI/UX Pro Max: `C:\Users\Hp\.agents\skills\ui-ux-pro-max\SKILL.md`
- Framer Motion: `C:\Users\Hp\.agents\skills\framer-motion\SKILL.md`

The UI/UX Pro Max installer also includes its companion design, brand, design-system, ui-styling, banner-design, and slides skills.

Start using the skills in your next Codex message. If they do not appear in the skill picker or Codex does not recognize them, restart Codex and reopen the project.

## Qatar project: UI/UX improvements

This project uses Laravel 12, Blade, Tailwind CSS 4, Vite, and plain JavaScript. UI/UX Pro Max supports Laravel. Name the page and the improvements you want, while keeping the existing stack and business behavior explicit.

Copy this into the Qatar project's Codex chat:

```text
Use $ui-ux-pro-max to improve my Qatar ERP Operations Dashboard.
Improve metric cards, Counter Billing shortcuts, spacing, typography,
contrast, and mobile layout. Keep the existing navy/gold brand and
Laravel Blade + Tailwind stack. Preserve business logic, amounts,
routes, forms, and PDF/print layouts. Implement and verify the changes.
```

For a focused fix:

```text
Use $ui-ux-pro-max to improve the mobile layout of the quotation form.
Keep entered values, calculations, validation, and submission behavior.
Improve field grouping, labels, spacing, and accessible error feedback.
Check the layout at phone and desktop widths.
```

Treat database search results as recommendations. Check that framework advice applies to Laravel 12, and preserve the existing brand rather than applying a generated palette without review.

## Qatar project: animation

The Framer Motion skill contains React/Next.js animation guidance. Installing it adds instructions to Codex; adding animations to an app is a separate task. Qatar currently has no React or Motion dependency.

For the current Blade app, request the motion principles adapted to CSS and plain JavaScript:

```text
Read $framer-motion for motion principles, then adapt the relevant ideas
to my Production Pipeline board using CSS and plain JavaScript.
Add subtle drag feedback, successful stage-update feedback, and loading
states. Preserve keyboard access, filters, stage updates, and drag/drop
behavior. Respect prefers-reduced-motion. Keep the existing Blade stack.
```

For a React or Next.js project, open that project in Codex and use:

```text
Use $framer-motion to add subtle section reveals and button feedback.
Inspect the existing animation dependencies and use a compatible Motion
package. Respect reduced motion, keep content accessible, and verify
the production build.
```

## Local Python runtime

Python 3.14.6 is installed. The WindowsApps `python` alias could not launch inside this Codex sandbox; the executable below worked. If a future skill invocation has the same issue, use this installed runtime rather than reinstalling Python.

Verified Laravel search command (PowerShell):

```powershell
& 'C:\Users\Hp\AppData\Local\Python\pythoncore-3.14-64\python.exe' -B 'C:\Users\Hp\.agents\skills\ui-ux-pro-max\scripts\search.py' 'form validation' --stack laravel -n 1
```

Installation checks passed: both skill manifests exist; UI/UX Pro Max's Laravel search and design-system generator execute successfully. The generator smoke check did not persist a design system or apply its recommendations to the app.

## Sources

- [UI/UX Pro Max](https://github.com/nextlevelbuilder/ui-ux-pro-max-skill) — installed with published `ui-ux-pro-max-cli` version 2.15.0, `init --ai codex --global`.
- [Framer Motion skill](https://github.com/Schoepplake/framer-motion-skill/tree/main/skills/framer-motion) — installed using Codex's skill-installer.
- [OpenAI skills documentation](https://learn.chatgpt.com/docs/build-skills).
