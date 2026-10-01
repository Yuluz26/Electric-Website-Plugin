# SOP — Development Workflow for the EV Charging WordPress + Breakdance Plugin

## Role

Act as a senior WordPress plugin engineering lead.

Follow this SOP throughout the project.

## Existing project tools — MUST USE

The project already has these tools/skills installed:

```bash
npm install -g ui-ux-pro-max-cli
uipro init --ai claude
npx skills add https://github.com/Leonxlnx/taste-skill
npx skills@latest add emilkowalski/skills
npx impeccable install
```

Do NOT reinstall them unless the environment proves they are missing.

Before making design or implementation decisions, inspect and use the relevant capabilities from:
- ui-ux-pro-max
- Taste Skill
- emilkowalski skills
- Impeccable

Do not invent a parallel design system when an installed skill already provides a useful pattern.

## Phase 1 — Inspect first

Before changing code:
1. inspect the complete repository
2. inspect WordPress plugin structure
3. inspect package files
4. inspect existing Breakdance-related integration
5. inspect existing CSS/JS loading
6. inspect PHP namespaces
7. inspect current WordPress environment
8. inspect Breakdance version if available
9. inspect existing theme
10. inspect current page/template structure

Never overwrite files blindly.

## Phase 2 — Research

Inspect:
- reference article
- premium EV charging websites
- current Breakdance developer documentation
- relevant WordPress plugin APIs
- current GSAP usage patterns

Document what is being borrowed conceptually.

Do not copy:
- layout
- code
- brand identity
- exact copy
- copyrighted media

## Phase 3 — Architecture

Use a namespaced architecture.

Suggested structure:

```text
plugin-root/
├── plugin-main.php
├── src/
│   ├── Core/
│   ├── Breakdance/
│   ├── Elements/
│   ├── Assets/
│   ├── Admin/
│   └── Support/
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── icons/
├── templates/
├── languages/
├── tests/
├── package.json
└── README.md
```

Adapt to the existing project instead of forcing this exact tree.

## Phase 4 — Breakdance integration

Prefer official/documented mechanisms.

Use Breakdance Element Studio/custom element mechanisms where suitable.

Rules:
- unique element slugs
- unique class names
- unique PHP namespace
- unique CSS prefix
- unique JS namespace
- no modification of Breakdance core
- no overriding Breakdance's existing widget CSS
- no global `.bde-*` selectors
- no theme-level hacks unless explicitly required
- no replacing existing Breakdance elements

The plugin should add capabilities to Breakdance, not replace Breakdance.

## Phase 5 — Asset loading

Use conditional loading.

Only load:
- plugin CSS when an EV plugin element exists
- GSAP when motion-enabled plugin content exists
- page-specific media when needed

Do not load GSAP on every WordPress page by default.

Do not duplicate GSAP if the environment already exposes a compatible GSAP instance.

Use a safe dependency strategy.

## Phase 6 — Builder mode

The plugin must detect builder/editor context where appropriate.

Builder requirements:
- elements render inside Breakdance
- no animation loop that makes editing difficult
- no forced scroll behavior
- no autoplay-heavy media
- no expensive observers running unnecessarily
- controls remain editable

## Phase 7 — CSS isolation

All custom CSS must be scoped.

Example concept:

```css
.evpx-article {}
.evpx-article .evpx-hero {}
.evpx-article .evpx-comparison {}
```

Do NOT write global rules like:

```css
h1 {}
img {}
section {}
.container {}
.button {}
```

Do not use generic class names that could collide.

## Phase 8 — JS isolation

Use a namespace:

```js
window.EVPremium = window.EVPremium || {};
```

Use:
- defensive initialization
- feature detection
- cleanup
- idempotent initialization
- no duplicate event listeners
- no global variable pollution

GSAP/ScrollTrigger:
- register safely
- scope selectors
- kill/revert when needed
- do not initialize duplicate timelines

## Phase 9 — Accessibility

Every component must be tested for:
- keyboard
- focus
- screen-reader labels
- contrast
- reduced motion
- semantic HTML
- accordion state
- buttons vs links
- image alt text

## Phase 10 — Responsive testing

Test:
- 320
- 375
- 390
- 430
- 768
- 1024
- 1280
- 1440
- 1920

Test real interactions, not just screenshots.

## Phase 11 — Compatibility testing

Verify:
- plugin activation
- plugin deactivation
- Breakdance editor opening
- existing Breakdance pages
- unrelated WordPress pages
- caching
- minification
- CSS optimization
- JS optimization
- mobile menu
- forms if present
- SEO plugin coexistence

## Phase 12 — Failure handling

Never hide errors silently.

Use:
- WordPress debug logging
- clear PHP guards
- dependency checks
- graceful frontend fallbacks

Do not show raw technical errors to normal visitors.

## Phase 13 — Git discipline

Before major changes:
- commit
- make one logical change
- test
- commit

Avoid giant unreviewable commits.

## Phase 14 — Final QA

Run:
- PHP syntax checks
- JavaScript lint/build checks if configured
- asset build
- browser console check
- responsive test
- accessibility check
- Breakdance builder test
- activation/deactivation test

## SOP rule

If a design idea conflicts with stability, preserve stability.

If an animation conflicts with accessibility, reduce the animation.

If a custom widget conflicts with Breakdance, redesign the integration rather than overriding Breakdance.

The plugin must be the guest inside the WordPress/Breakdance ecosystem, not the owner of it.
