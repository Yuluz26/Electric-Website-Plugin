# EV Charging Experience — Architecture & Design System

Status: MVP → Production build. Source prompts: `docs/prompt-pack/` (01–06).

## 1. Confirmed requirements

- WordPress plugin, installable on any WP + Breakdance site without visually touching unrelated pages.
- 9 content widgets (12 registered elements — three of them are nested item elements, see §3) that render a premium, neumorphic, editorial EV-charging article.
- GSAP-driven motion that respects `prefers-reduced-motion`, never runs in the Breakdance builder canvas.
- Everything namespaced (`EVPX`), CSS/JS isolated, assets conditionally loaded.
- No fatal errors with or without Breakdance active; safe activate/deactivate.

## 2. Architecture proposal

### 2.1 Breakdance integration strategy — read this first

Breakdance's native "custom element" system (**Element Studio**) is a closed, GUI-only
generator: you build the element visually inside a licensed Breakdance install and it
writes PHP+Twig files into a folder your plugin registers with
`\Breakdance\ElementStudio\registerSaveLocation()`. Confirmed by cloning and reading
Breakdance's own `soflyy/breakdance-custom-elements` and `soflyy/breakdance-developer-docs`
repos — there is no documented format for hand-authoring that generated output, and no raw
"PHP element class" API the way Elementor/Gutenberg expose one.

This build environment has no licensed Breakdance instance, so Element Studio can't be
operated here. Guessing at the generated-file schema would risk exactly the silent
builder breakage the SOP forbids. Instead:

1. **Every component is a WordPress shortcode** with a full attribute-based control
   surface (content, media, layout, visual, motion — matching the PRD's control
   categories). Shortcodes render through Breakdance's native, version-proof "Shortcode"
   element — this has been stable for years and carries zero integration risk.
2. **Every component is also a server-rendered Gutenberg block** (same renderer, same
   attributes) so it can be inserted visually with a live preview, including inside
   Breakdance (which embeds WP content/blocks natively).
3. The plugin registers the real Element Studio save location on `breakdance_loaded`,
   and `docs/BREAKDANCE-ELEMENT-STUDIO-BRIDGE.md` gives exact copy/paste HTML+CSS and the
   control list for each widget, so wiring up a fully native drag-in element inside
   Element Studio on the real site is a short, mechanical task rather than a rebuild.
4. One Dynamic Data field (EV Reading Time) is registered via the documented
   `\Breakdance\DynamicData\*` classes so native Breakdance elements elsewhere on the
   page can also show it.

Every Breakdance API call is guarded with `function_exists()` / `class_exists()` before
use, per Breakdance's own documented pattern — the plugin is fully inert (but still
renders shortcodes/blocks) with Breakdance deactivated.

### 2.2 Folder structure

```text
Electric-Website-Plugin/
├── ev-charging-experience.php      # bootstrap + hand-rolled PSR-4 autoloader (no vendor/ needed)
├── composer.json                   # PSR-4 metadata + dev tooling only
├── phpcs.xml.dist                  # WordPress coding standards ruleset (exclusions explained inline)
├── src/
│   ├── Core/                       # Plugin, Activation, Deactivation
│   ├── Breakdance/                 # Compatibility, ElementStudioBridge, DynamicData (+ Fields/)
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
├── tests/                          # docker/setup.sh, playwright/qa.mjs, contrast-check.mjs, build-zip.sh
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
shortcode attribute set, the block attribute schema and the block-editor Inspector panels are
all derived from that one list. `docs/WIDGETS.md` is the human-readable reference.

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
.evpx-surface--raised {
  background: var(--evpx-surface-base);
  border-radius: var(--evpx-radius-lg);
  box-shadow:
    8px 8px 16px var(--evpx-surface-raised-lo),
    -8px -8px 16px var(--evpx-surface-raised-hi);
}
.evpx-surface--recessed {
  background: var(--evpx-surface-base);
  border-radius: var(--evpx-radius-md);
  box-shadow:
    inset 5px 5px 10px var(--evpx-surface-recessed-hi),
    inset -5px -5px 10px var(--evpx-surface-recessed-lo);
}
.evpx-surface--flat { background: transparent; box-shadow: none; border-radius: 0; }
/* plus .evpx-surface--raised-sm (cards, controls) and .evpx-surface--accent */
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

Scale (fluid via `clamp()`): eyebrow 13px, body 17px/1.65, section-heading
clamp(28px,4vw,40px), display clamp(40px,7vw,88px).

### 5.4 Spacing / radii / breakpoints

- Spacing unit 4px: tokens `--evpx-space-1` (0.25rem) … `--evpx-space-32` (8rem)
- Radii: `--evpx-radius-sm` 8px, `--evpx-radius-md` 16px, `--evpx-radius-lg` 24px (no pills on cards/buttons)
- Breakpoints tested: 320, 375, 390, 430, 768, 1024, 1280, 1440, 1920

### 5.5 Motion tokens

```css
:root {
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

## 6. Asset strategy

- CSS/JS enqueued only when `has_shortcode()` / block presence is detected on the
  rendered content, or a plugin block/shortcode fires during a Breakdance builder request.
- GSAP + ScrollTrigger come from cdnjs by default and are only enqueued alongside an EV
  element. They are not bundled (GSAP's licence restricts redistribution inside builder
  add-ons); `evpx_gsap_src` / `evpx_scrolltrigger_src` filters point them at a self-hosted
  copy. `motion.js` only initialises if `window.gsap` exists and does nothing otherwise,
  and the fully working baseline never depends on it.
- Nothing hides content waiting for JavaScript: the hero's hold-back has a pure-CSS failsafe
  and is skipped for reduced motion and `scripting: none`; scroll reveals only apply to
  content that starts below the fold.
- Builder-mode detection (`EVPX\Breakdance\Compatibility::isBuilderContext()`) disables
  autoplay/ScrollTrigger/observers while editing.

## 7. Compatibility strategy

- All Breakdance calls guarded by `function_exists()`/`class_exists()`.
- No filters/actions that touch `.bde-*`, Breakdance templates, or global widget CSS.
- No activation-time content rewrites; no DB migration in v1 (nothing to migrate).
- Tested against a real WordPress core (Docker) for activation/deactivation and fatal-error
  freedom; Breakdance itself is unavailable here (paid/licensed) — flagged as a follow-up
  verification step for the real site (see final report).

## 8. Acceptance criteria

Same as `docs/prompt-pack/04_MVP.md` "MVP acceptance criteria", plus: shortcode and block
output are byte-identical (single renderer, two entry points), PHP 7.4–8.4 syntax-clean,
zero JS console errors, zero PHP notices/warnings under `WP_DEBUG`.

## 9. Risks & mitigations

| Risk | Mitigation |
|---|---|
| No licensed Breakdance to verify Element Studio rendering | Shortcode/block path is the verified-safe primary delivery; bridge doc for native Element Studio |
| Reference article unreachable (egress-blocked) | Rebuilt content model from the prompt pack's own detailed structure + independently verified AC/DC facts |
| Neumorphism hurting contrast/accessibility | Decorative-shadow-only rule (§5); `tests/contrast-check.mjs` + `axe-core` in the QA suite |
| GSAP double-loading with Breakdance's own dependency system | Runtime `window.gsap` existence check; motion is a pure enhancement |
| GSAP unavailable (blocked CDN, strict CSP) | Filters to self-host; every widget stays complete and interactive without it |
| Third-party font requests / privacy | Fonts bundled (OFL); QA asserts no font-host request |
