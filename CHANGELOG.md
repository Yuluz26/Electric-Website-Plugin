# Changelog

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
