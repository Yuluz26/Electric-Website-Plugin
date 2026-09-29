# EV Charging Experience — Architecture & Design System

Status: MVP → Production build. Source prompts: `docs/prompt-pack/` (01–06).

## 1. Confirmed requirements

- WordPress plugin, installable on any WP + Breakdance site without visually touching unrelated pages.
- 9 content widgets (12 registered elements — three of them are nested item elements, see §3) that render a premium, neumorphic, editorial EV-charging article, available as native Breakdance elements, shortcodes and Gutenberg blocks.
- GSAP-driven motion that respects `prefers-reduced-motion`, never runs in the Breakdance builder canvas.
- Everything namespaced (`EVPX`), CSS/JS isolated, assets conditionally loaded.
- No fatal errors with or without Breakdance active; safe activate/deactivate.

## 2. Architecture proposal

### 2.1 Breakdance integration strategy — read this first

Breakdance has two extension routes: **Element Studio**, a GUI generator that writes an element's files into
a folder your plugin registers with `\Breakdance\ElementStudio\registerSaveLocation()`, and the thing its
output is in the end — a PHP class extending `\Breakdance\Elements\Element`. Neither is documented for
hand-authoring. The first releases had no licensed Breakdance to read or run, so they shipped shortcodes and
blocks plus a registered Element Studio location, and the PRD's "custom widgets appear in Breakdance and can be
edited visually" was met only through Breakdance's Shortcode element. From 0.4.0, after reading Breakdance
2.8.3's source and running the plugin against it (`docs/QA-REPORT.md`), the plugin also ships the elements
themselves. There are five integration points, all around **one renderer**:

1. **Native Breakdance elements** (`src/Breakdance/Native/`): nine PHP classes, `EVPX\Hero`, `EVPX\Faq`, …,
   listed in the Add panel under **EV Charging**, with controls, an Items repeater, Dynamic Data on text fields
   and live canvas rendering. Each names its widget; controls, defaults and markup come from that widget
   (`Native\Controls` translates the one control schema to Breakdance's controls and back). They are declared
   only once Breakdance has announced itself. `docs/BREAKDANCE.md` has the details.
2. **Every component is a WordPress shortcode** with a full attribute-based control surface (content, media,
   layout, visual, motion — matching the PRD's control categories). Shortcodes render through Breakdance's
   Shortcode element, or anywhere else WordPress renders content.
3. **Every component is also a server-rendered Gutenberg block** (same renderer, same attributes) so it can be
   inserted visually with a live preview.
4. The plugin registers **Element Studio save locations** on `breakdance_loaded` (priority 9, from a hook added
   when the plugin file loads — Breakdance reads save locations at priority 10, and fires the action before any
   `plugins_loaded` callback of this plugin could add one) for elements a site owner designs on top of the
   plugin's CSS. Their PHP namespace is `EVPXStudio`, not `EVPX`, so an element someone names "Hero" can't
   redeclare a native one.
5. One **Dynamic Data** field (EV Reading Time, not Pro-only) is registered via the documented
   `\Breakdance\DynamicData\*` classes so native Breakdance elements elsewhere on the page can also show it.

Every Breakdance API call is guarded with `function_exists()` / `class_exists()` before use, per Breakdance's
own documented pattern — the plugin is fully inert (but still renders shortcodes/blocks) with Breakdance
deactivated. The native element classes extend a Breakdance class, so their files aren't loaded at all
without it.

### 2.2 Folder structure

```text
Electric-Website-Plugin/
├── ev-charging-experience.php      # bootstrap + hand-rolled PSR-4 autoloader (no vendor/ needed)
├── composer.json                   # PSR-4 metadata + dev tooling only
├── phpcs.xml.dist                  # WordPress coding standards ruleset (exclusions explained inline)
├── src/
│   ├── Core/                       # Plugin, Activation, Deactivation
│   ├── Breakdance/                 # Compatibility, ElementStudioBridge, DynamicData (+ Fields/)
│   │   └── Native/                 # NativeElements, NativeElement (trait), Controls, elements/ (one class per element)
│   ├── Elements/                   # Element (shared contract), Registry, Widgets/ (one class each)
│   ├── Assets/                     # Loader — conditional CSS/JS, GSAP source filters
│   ├── Admin/                      # Notices (informational only; no settings screen)
│   └── Support/                    # ReadingTime
├── assets/
│   ├── css/evpx.css                # tokens + every component, one file, everything under .evpx-*
│   ├── js/                         # evpx.js (vanilla core), motion.js (GSAP), block-editor.js
│   └── fonts/                      # Fraunces + Libre Franklin woff2 (SIL OFL) + licence
├── templates/                      # one PHP view partial per element
├── languages/                      # .pot translation template
├── tests/                          # docker/ (setup, real-Breakdance, media and template checks), playwright/ (qa, breakdance-qa, media-qa, template-qa), contrast-check, build-zip
└── docs/
```

### 2.3 Namespacing

- PHP namespace: `EVPX\*`
- CSS prefix: `.evpx-*` (never a bare tag/global selector)
- JS global: `window.EVPX`
- Shortcode prefix: `evpx_*` (e.g. `[evpx_hero]`)
- Block namespace: `evpx/*` (e.g. `evpx/hero`)
- DB options / postmeta prefix: `evpx_`

## 3. Element inventory

| # | Element | Shortcode | Block | Notes |
|---|---|---|---|---|
| 1 | EV Article Hero | `[evpx_hero]` | `evpx/hero` | also owns the reading-progress bar (`progress_bar`) |
| 2 | EV Section | `[evpx_section]` | `evpx/section` | general editorial block; flat/raised/recessed surface |
| 3 | EV AC/DC Comparison | `[evpx_comparison]` | `evpx/comparison` | signature component; toggle or side-by-side |
| 4 | EV Scenario Cards | `[evpx_scenarios]` | `evpx/scenarios` | container; children are `[evpx_scenario_card]` |
| 5 | EV Technical Flow | `[evpx_flow]` | `evpx/flow` | Grid → Site → Charger → Vehicle → Battery |
| 6 | EV Decision Factors | `[evpx_decision_factors]` | `evpx/decision-factors` | container; children are `[evpx_decision_factor]`; numbered flat list |
| 7 | EV FAQ | `[evpx_faq]` | `evpx/faq` | container; children are `[evpx_faq_item]`; optional FAQPage JSON-LD |
| 8 | EV Related Articles | `[evpx_related]` | `evpx/related` | lists real published posts; nothing for visitors when empty |
| 9 | EV CTA | `[evpx_cta]` | `evpx/cta` | accent / dark / media variants |

Each element declares its controls once (`Element::controls()`); defaults, sanitization, the
shortcode attribute set, the block attribute schema, the block-editor Inspector panels and the native
Breakdance element's controls are all derived from that one list. `docs/WIDGETS.md` is the human-readable
reference. The eight section widgets also share one `spacing` control (default / compact / none).

Not built, deliberately: separate Article Meta / Intro / Infrastructure Panel widgets (Hero,
Section and Technical Flow already cover them) and a Media Showcase (it would need real
photography, which this build environment could not source — see `docs/MEDIA-BRIEF.md`).

## 4. Content model

Article flow (reconciles PRD content direction + UX section blueprint):

1. Hero — category, title, dek, meta, cinematic media
2. Introduction — why AC/DC selection matters
3. AC charging explained
4. DC charging explained
5. AC vs DC comparison (signature interactive section)
6. What determines the choice — Decision Factors (dwell time, energy, capacity, turnover, install complexity, operating model, growth)
7. Scenario storytelling (workplace, hotel, residential, fleet depot, retail, highway)
8. Infrastructure (grid → site → charger → vehicle → battery)
9. FAQ
10. Closing CTA
11. Related articles

Copy is original, rephrased from AC/DC charging fundamentals (verified against ChargePoint,
Power Sonic, EV Connect public explainers — see sources in final report), not copied from
the reference URL, which this sandbox cannot reach.

## 5. Design tokens — refined neumorphism

Hard rule carried through every component: **neumorphic shadows are decorative surface
depth only.** Text contrast, focus states, and "this is clickable" affordances never rely
on shadow alone — they use real color/border contrast. This directly answers the
WCAG 1.4.3/1.4.11 failure mode neumorphism is known for.

### 5.1 Color

Avoids the neon-cyan/blue-gradient EV cliché. Ink-graphite base, warm copper as the single
confident accent (energy/CTA), desaturated blue reserved for technical/data moments only.

Tokens live on `.evpx-root` (every top-level element carries it), never on `:root`, so nothing
leaks to the rest of the page. Dark values are applied by `data-evpx-theme="dark"` (or `auto`,
gated on `prefers-color-scheme`) on that same element.

```css
.evpx-root {
  /* Light surfaces */
  --evpx-surface-base: #eef0f3;
  --evpx-surface-raised-hi: #ffffff;   --evpx-surface-raised-lo: #c7ced6;
  --evpx-surface-recessed-hi: #d7dce2; --evpx-surface-recessed-lo: #ffffff;
  --evpx-ink: #14171c;  --evpx-ink-muted: #4b535e;  --evpx-ink-faint: #626b76;
  --evpx-border: #d7dce2;

  /* Accents. Fill = surfaces that carry --evpx-accent-ink text (buttons, flow nodes,
     the accent CTA). Text = the same hue as text/icon/focus ring on a surface. */
  --evpx-accent: #a4531f;
  --evpx-accent-text: #a4531f;         /* dark mode: #d98a5a */
  --evpx-accent-ink: #ffffff;
  --evpx-technical: #4e75a6;           /* data/infra moments only */
}
/* Dark: surface-base #14171c, raised #1e232a / #05070a, recessed #0a0d11 / #22282f,
   ink #edeff2, muted #a7aeb6, faint #838b96, border #262c34. */
```

Every pairing the components use is asserted by `node tests/contrast-check.mjs`, which reads
the tokens straight out of `assets/css/evpx.css`: body 6.8:1, small labels 4.7:1, accent text
4.8:1 on the light surface (6.6:1 in dark), white-on-accent 5.5:1. (An earlier copper,
`#b9662f`, was documented here as passing. It did not — 3.7:1 as text, 4.2:1 as a button —
and was darkened once the check existed. `axe-core` then confirmed zero violations across
the widgets; see `docs/QA-REPORT.md`.)

### 5.2 Surfaces (the neumorphism formula)

```css
.evpx-root .evpx-surface--raised {
  background: var(--evpx-surface-base);
  border-radius: var(--evpx-radius-lg);
  box-shadow:
    8px 8px 16px var(--evpx-surface-raised-lo),
    -8px -8px 16px var(--evpx-surface-raised-hi);
}
.evpx-root .evpx-surface--recessed {
  background: var(--evpx-surface-base);
  border-radius: var(--evpx-radius-md);
  box-shadow:
    inset 5px 5px 10px var(--evpx-surface-recessed-hi),
    inset -5px -5px 10px var(--evpx-surface-recessed-lo);
}
.evpx-root .evpx-surface--flat { background: transparent; box-shadow: none; border-radius: 0; }
/* plus .evpx-surface--raised-sm (cards, controls) and .evpx-surface--accent; every rule is
   scoped under .evpx-root, see §5.6 */
```

Four surfaces total: `raised` (cards, controls), `recessed` (technical-data panels, inputs),
`accent` (copper fill, flat — never neumorphic, needs full contrast), `flat` (editorial
text blocks/media — most of the page; neumorphism is used *selectively*, per the UX spec).

### 5.3 Typography

- Display/headings: **Fraunces** (variable serif — cinematic, editorial, distinctive without being loud)
- Body/UI/labels/data: **Libre Franklin** (sturdy grotesque, strong tabular numerals for kW/min values)
- Self-hosted: variable woff2 (Latin subset, ~96 KB together) bundled in `assets/fonts/` under
  the SIL OFL with the licence file alongside. Declared as `EVPX Fraunces` / `EVPX Libre
  Franklin` so they can never merge with a site's own copy, `font-display: swap`, with system
  serif/sans fallbacks. No request to a third-party font host is made (asserted by the QA suite).

Scale (fluid via `clamp()`, following the widget's own box in `cqi`, with a viewport `vw`
fallback where container queries are unsupported): eyebrow 13px, body 17px/1.65, section heading
`clamp(1.75rem, 1.35rem + 2cqi, 2.5rem)`, display `clamp(2.5rem, 1.6rem + 4.5cqi, 5.5rem)`.

### 5.4 Spacing / radii / breakpoints

- Spacing unit 4px: tokens `--evpx-space-1` (0.25rem) … `--evpx-space-32` (8rem)
- Radii: `--evpx-radius-sm` 8px, `--evpx-radius-md` 16px, `--evpx-radius-lg` 24px (no pills on cards/buttons)
- Darkening behind white type over a picture (hero, CTA): `--evpx-scrim` (0.6), the value at which type stays
  above 4.5:1 over a near-white photograph; `tests/playwright/media-qa.mjs` measures it
- Vertical rhythm of every section widget: `--evpx-section-y` (default `--evpx-space-24`); the `spacing`
  control sets it to `--evpx-space-12` (compact) or `0` (none) through `data-evpx-spacing` on the widget
- Layout breakpoints are **container** thresholds, not viewport ones: 40rem (two-column grids),
  48rem (side-by-side comparison, wider container padding; below it the flow turns vertical),
  64rem (two-column section/decision layouts, three-column grids), and a narrow-box tightening
  below 30rem. See §5.6.
- Widths swept by `tests/playwright/qa.mjs` (screenshot + sideways-overflow assertion): 320, 375,
  390, 430, 768, 1024, 1280, 1366, 1440, 1920. `breakdance-qa.mjs` sweeps 320, 390, 768, 1024,
  1440, 1920 on a real Breakdance page.

### 5.5 Motion tokens

```css
.evpx-root {
  --evpx-ease-premium: cubic-bezier(0.16, 1, 0.3, 1);
  --evpx-dur-micro: 0.2s;
  --evpx-dur-base: 0.6s;
  --evpx-dur-section: 0.9s;
  --evpx-dur-hero: 1.2s;
}
```

Motion hierarchy: hero (strongest) → comparison (medium) → supporting sections (subtle) →
body text (static). Implemented in `assets/js/motion.js`; `assets/js/evpx.js` is the
dependency-free core (FAQ accordion, tabs, builder/reduced-motion detection) and works alone.

### 5.6 Living inside a host: specificity and container queries

Two things went wrong the first time the widgets met a real Breakdance page, and both are now
part of the contract (they're stated at the top of `assets/css/evpx.css`):

- **Host rules leak in.** Builders and themes style bare headings and links — Breakdance:
  `.breakdance h2 { color; font-family; font-size }`, `.breakdance a { color }`, both specificity
  0,1,1 — and a lone class (0,1,0) loses. Every rule is therefore scoped under `.evpx-root`:
  `.evpx-root .evpx-hero__title` for descendants, `.evpx-root.evpx-hero` for the widget's own
  element (0,2,0 either way, so relative specificity between our own rules is unchanged). A host's
  hover colour (`.breakdance a:hover`, 0,2,1) is answered by restating each link colour for `:hover`.
  Site owners override with the same two-class selector. Base `font-size` and `text-align` are set
  on the root, since the host's would otherwise be inherited.
- **A widget is not the viewport.** In Breakdance a widget sits in a Section, a column, or a theme's
  content wrapper. `.evpx-root` is a size container (`container-type: inline-size`, inside
  `@supports`), layout rules are `@container` rules, fluid type uses `cqi`, grid tracks are
  `minmax(0, …)` so they can shrink, and the tab bar wraps. Only preferences — colour scheme,
  reduced motion, scripting — and the hero's own minimum height (a box can't query itself) remain
  `@media`.
- **A size container has no intrinsic width.** Wherever the host shrink-wraps its children, the widget
  would collapse to nothing. Breakdance does exactly this: a Section is a flex column with
  `align-items: flex-start`, and its Rich Text element — which is how a post's content is shown in its
  default Single Post template — is a flex item only as wide as its content. Found by running the widgets in
  a post under Breakdance's own Zero theme (every widget had `width: 0`, its text overflowing a box that
  wasn't there). Two rules answer it, both inside the `@supports` block: the widget asks to be stretched in a
  flex parent (`align-self: stretch`), and the box that holds it is asked to fill its own container
  (`:where(:has(> .evpx-root)) { width: stretch }`, with the prefixed spellings). `:where()` keeps that at
  zero specificity, so any rule of the host's wins. Breakdance's own Shortcode element and the native
  elements' wrapper are already full width. `tests/docker/template-check.sh` fails on a collapsed widget.

## 6. Asset strategy

Two independent ways deliver the same files, and they must not both do it on one page:

- **Shortcodes and blocks: WordPress.** CSS/JS are enqueued in `<head>` when `has_shortcode()` / block presence
  is detected in the post, when an EV shortcode is found in the post's **Breakdance element tree** (a page
  designed in Breakdance has an empty `post_content`), or when an element has already rendered by the time
  `<head>` is printed — which is the case under block themes and Breakdance's own templates, where the body
  renders first, and so covers widgets in a header, footer or template. Anything left (a widget in a footer on a
  plain classic theme) falls back to enqueuing in the footer the moment it renders, and the `evpx_load_assets`
  filter forces `<head>` loading where the flash of unstyled content matters.
- **Native elements: Breakdance.** Each declares the stylesheet and scripts as its dependencies, and Breakdance
  prints them once per page (`?bd_ver=`), wherever the element sits — including in the builder canvas, which
  gets the stylesheet only, so an element added to an empty page is styled without a reload.
- **No second copy.** Breakdance caches the dependencies it collects per document, so a dependency *condition*
  that looks at the current request would be frozen into that cache; the dedupe is done on the WordPress side.
  When a native element renders, `Loader::breakdanceDelivers()` records it and WordPress queues nothing further.
  Under block themes and Breakdance templates the elements have rendered before `<head>`, so nothing is queued
  at all. On a plain classic theme `<head>` comes first: WordPress has queued the assets by then, so the
  scripts (which print in the footer) are withdrawn, and only the stylesheet — printed already, and harmless
  twice — can appear twice. Both files are idempotent anyway (`evpx.js` and `motion.js` guard against a second
  run).
- GSAP + ScrollTrigger come from cdnjs by default and are only loaded alongside an EV element. They are not
  bundled (GSAP's licence restricts redistribution inside builder add-ons); `evpx_gsap_src` /
  `evpx_scrolltrigger_src` filters point them at a self-hosted copy. `motion.js` only initialises if
  `window.gsap` exists and does nothing otherwise, and the fully working baseline never depends on it.
- Nothing hides content waiting for JavaScript: the hero's hold-back has a pure-CSS failsafe
  and is skipped for reduced motion and `scripting: none`; scroll reveals only apply to
  content that starts below the fold.
- Builder-mode detection (`EVPX\Breakdance\Compatibility::isBuilderContext()`) recognises
  `?breakdance=builder`, the canvas iframe (`breakdance_iframe`), Breakdance's own AJAX — a POST of
  `breakdance_*` to a front-end URL, where `is_admin()` and `wp_doing_ajax()` are false — and any
  WordPress admin/REST request. `Element::withBuilderContext()` then switches every `motion`-group
  control off for those renders, so nothing is held back for an animation that will never run.

## 7. Compatibility strategy

- All Breakdance calls guarded by `function_exists()`/`class_exists()`.
- No filters/actions that touch `.bde-*`, Breakdance templates, or global widget CSS.
- No activation-time content rewrites; no DB migration in v1 (nothing to migrate).
- Tested against a real WordPress core (Docker) for activation/deactivation and fatal-error
  freedom, and against a real Breakdance 2.8.3 (front end and builder) — see `docs/QA-REPORT.md`
  for exactly what that covered and what it didn't.
- The plugin boots when its file loads, not on `plugins_loaded`: Breakdance fires
  `breakdance_loaded` from its own `plugins_loaded` callback, and "breakdance" loads before this
  plugin, so a hook added from a later `plugins_loaded` callback would never run.
- Under a Breakdance template (a footer, a Single Post template) the plugin was run with Twenty Twenty-Five,
  Breakdance's Zero theme and a bare classic theme; `tests/docker/template-check.sh` repeats it.

## 8. Acceptance criteria

Same as `docs/prompt-pack/04_MVP.md` "MVP acceptance criteria", plus: shortcode and block
output are byte-identical, and a native Breakdance element renders the same markup (single renderer,
three entry points), PHP 7.4–8.4 syntax-clean, zero JS console errors, zero PHP notices/warnings under
`WP_DEBUG`, and — the PRD's definition of done — activating the plugin changes nothing on a page that has no
EV element (`tests/docker/isolation-check.sh`).

## 9. Risks & mitigations

| Risk | Mitigation |
|---|---|
| Assumptions about Breakdance that a stub can't check (load order, builder signals, global CSS) | Run against a real Breakdance 2.8.3: `tests/docker/breakdance-real-check.sh`, `tests/playwright/breakdance-qa.mjs`; the stub now mirrors the real load order |
| A native element's class name is stored in every page built with it | The classes in `src/Breakdance/Native/elements/` are public API and are never renamed; the Element Studio namespace is `EVPXStudio` so a user's element can't take the same name |
| Assets delivered twice (WordPress and Breakdance each queue them) | One flag, `Loader::breakdanceDelivers()`; scripts idempotent; `tests/docker/template-check.sh` counts every asset on a page under three themes |
| Widget collapsing to zero width in a shrink-wrapping host | Two zero-specificity rules (§5.6), asserted under Breakdance's Zero theme |
| Host CSS overriding widget typography, or a desktop layout in a narrow column | Rules scoped under `.evpx-root`; container queries; both asserted on a real Breakdance page (§5.6) |
| Reference article unreachable (egress-blocked) | Rebuilt content model from the prompt pack's own detailed structure + independently verified AC/DC facts |
| Neumorphism hurting contrast/accessibility | Decorative-shadow-only rule (§5); `tests/contrast-check.mjs` + `axe-core` in the QA suite |
| GSAP double-loading with Breakdance's own dependency system | Runtime `window.gsap` existence check; motion is a pure enhancement |
| GSAP unavailable (blocked CDN, strict CSP) | Filters to self-host; every widget stays complete and interactive without it |
| Third-party font requests / privacy | Fonts bundled (OFL); QA asserts no font-host request |
