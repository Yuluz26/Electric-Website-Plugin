# Changelog

## 0.3.0

First release checked against a real Breakdance (2.8.3), on the front end and in the builder. That
turned up bugs no stub could show; details and the tests that now guard them are in
`docs/QA-REPORT.md` (bugs 16–23).

**Fixed — Breakdance integration**
- The Element Studio save locations were never registered: Breakdance fires `breakdance_loaded` from
  its own `plugins_loaded` callback and reads save locations at priority 10 on it, and this plugin
  hooked in from *its* `plugins_loaded` callback — too late. The plugin now boots at include time.
- Builder detection looked for `?breakdance=edit|run`, which Breakdance never sends. It now
  recognises `?breakdance=builder`, the canvas iframe (`breakdance_iframe`) and Breakdance's own
  AJAX (a POST of `breakdance_*` to a front-end URL). Builder renders switch every motion control
  off, so the hero no longer arrives held back in the canvas.
- Reading time (Hero and the Dynamic Data field) said "1 min read" on every page built in
  Breakdance, and counted shortcode attribute names as words. It now reads the rendered element
  tree, and can't recurse when a page shows its own reading time.
- A page built in Breakdance printed its stylesheet after the content (flash of unstyled content).
  Detected from the element tree now; new `evpx_load_assets` filter for widgets that live in a
  Breakdance header, footer or template.
- `Compatibility::isBreakdanceActive()` fell back to a constant Breakdance doesn't define.

**Fixed — appearance inside a host**
- Breakdance's `.breakdance h1–h6 { color; font-family; font-size }` and `.breakdance a` rules
  (specificity 0,1,1) beat the widgets' single-class rules: hero title dark-on-dark, headings in a
  system sans, blue button text. Every rule is now scoped under `.evpx-root`, link colours are
  restated for `:hover`, and every heading declares its own size and colour.
- Layout answered to the viewport, so a widget in a half-width column got its desktop layout. It
  now answers to the widget's own box (container queries; single-column fallback where they're
  unsupported), fluid type follows the box, grid tracks can shrink, the tab bar wraps, cards tighten
  in narrow boxes.
- Base `font-size` and `text-align` are set on the root instead of inherited from the host.

**Added**
- `--evpx-section-y`: the vertical rhythm of every section widget, in one token (set it to `0`
  inside a Breakdance Section that already has padding).
- Real-Breakdance QA: `EVPX_BREAKDANCE_ZIP` in `tests/docker/setup.sh` (you supply the licensed ZIP;
  it is never committed), `breakdance-real-check.sh` (12 checks), `breakdance-page.sh` and
  `tests/playwright/breakdance-qa.mjs` (16 checks incl. the builder).
- `tests/playwright/qa.mjs` sweeps ten widths (320–1920 px) asserting no sideways overflow, and runs
  axe-core at 1280 and 390 px. 21 checks. Shared helpers in `tests/playwright/lib.mjs`.

**Changed**
- The plugin boots when its file loads, not on `plugins_loaded`; the text domain loads on `init`.
- The Breakdance contract stub now fires `breakdance_loaded` in the real order.

## 0.2.0

**Added**
- EV Decision Factors (+ item) — the "what determines the choice" framework as a flat, numbered
  editorial list, in place of a paragraph in a raised box.
- EV Related Articles — real published posts (same category / latest / hand-picked), stretched-link
  cards, no empty image boxes, nothing shown to visitors when there is nothing to list.
- Hero: `title_tag` (h1/h2, so a page doesn't end up with two `h1`s) and `progress_bar`.
- `evpx_gsap_src` / `evpx_scrolltrigger_src` filters to self-host GSAP.
- Bundled fonts (Fraunces + Libre Franklin variable woff2, SIL OFL) — no request to a font host.
- Translation template (`languages/ev-charging-experience.pot`), `phpcs.xml.dist`,
  `docs/demo-article.txt`, `docs/MEDIA-BRIEF.md`.
- Reproducible QA: `tests/docker/setup.sh`, a 20-check Playwright + axe-core suite, a token-driven
  contrast check, and a Breakdance contract stub (see `docs/QA-REPORT.md`).

**Fixed**
- Accent colour failed WCAG AA on every text use (3.7–4.2:1). Split into fill/text tokens and
  darkened; every pairing is now asserted by a script.
- Comparison tabs: invalid `tabpanel` role, no `aria-controls`, no keyboard navigation, dead buttons
  without JavaScript.
- With JavaScript off, FAQ answers were unreachable.
- Hero flashed visible → hidden → visible on a slow GSAP load; cards lost their `:hover` lift after
  their scroll reveal; elements already on screen were hidden just to fade in.
- CTA accent eyebrow contrast (opacity dimming).
- Documentation that claimed things the code didn't do (see `docs/QA-REPORT.md`, bug 15).

**Changed**
- Google Fonts request removed (bundled instead).
- Reading-progress bar now actually renders (it was previously wired to nothing).

## 0.1.0 — Initial MVP

- WordPress plugin bootstrap with a zero-dependency PSR-4 autoloader
  (works from a plain ZIP upload, no `composer install` required).
- Breakdance compatibility layer: Element Studio save-location
  registration, Dynamic Data field (reading time), builder-context
  detection — all guarded so the plugin never fatals without Breakdance.
- Neumorphic design token system (light/dark surfaces, copper + technical
  blue accents, Fraunces/Libre Franklin type, motion tokens) scoped
  entirely under `.evpx-*` classes.
- Conditional asset loader: CSS/GSAP/JS only load on pages that actually
  use an EV element; GSAP registered defensively against `window.gsap`
  already existing.
- 9 elements, each a shortcode + Gutenberg block from one shared control
  schema and one render path: Hero, Section, AC/DC Comparison (signature
  component), Scenario Cards + Scenario Card, Technical Flow, FAQ + FAQ
  Item, CTA.
- GSAP motion layer (hero reveal, section reveals, comparison transition,
  FAQ panel animation, reading progress) with a fully-functional vanilla-JS
  fallback baseline — motion is enhancement only, never a dependency for
  core interactivity.
- Full demo article (rephrased AC/DC charging content, not copied from any
  reference) assembled and QA'd end-to-end in a real WordPress + Docker
  environment; see `docs/QA-REPORT.md`.
- Hero's `progress_bar` toggle now renders the reading-progress bar the
  motion layer was already built to drive (previously dead code — nothing
  rendered the element it looked for).
- Reusable QA script (`tests/playwright/qa.mjs`) replacing one-off manual
  Playwright checks — 6 automated assertions plus 4-breakpoint screenshots.
