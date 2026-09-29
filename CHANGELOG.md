# Changelog

## 0.4.0

The nine widgets are now native Breakdance elements. Running them against a real Breakdance with its own
Zero theme, its templates, pictures, dynamic-data picker and repeater turned up ten more bugs, all fixed and
now guarded by tests (`docs/QA-REPORT.md`, bugs 24–33).

**Added — native Breakdance elements**
- The nine widgets appear in Breakdance's Add panel under **EV Charging**, with controls in sections
  (Content, Media, Layout, Visual, Motion, Responsive, Advanced), an **Items** repeater for the FAQ, Scenario
  Cards and Decision Factors, Breakdance's dynamic-data button on text and URL fields (including the plugin's
  own **EV Reading Time**), the media library for pictures, and live canvas rendering. Same renderer as the
  shortcodes and blocks, so the markup is identical. This is what the PRD's "custom widgets appear in
  Breakdance and can be edited visually" asks for; until now it was only met through Breakdance's Shortcode
  element, which still works.
- In the builder the comparison shows both panels and every FAQ answer is open, so both can be edited.
- A **Spacing** control (default / compact / none) on the eight section widgets, also available as the
  `spacing` shortcode/block attribute.
- Each native element brings its stylesheet and scripts as Breakdance dependencies: printed once per page,
  in `<head>` for the stylesheet, wherever the element sits (a page, a template, a header, a footer).
- Starting copy and sample rows, so a new element isn't an empty box. Translatable.

**Fixed**
- Widgets collapsed to zero width in a post's content under Breakdance's Zero theme and default Single Post
  template: a size container has no intrinsic width, and Breakdance's Rich Text element only grows to its
  content. Two zero-specificity rules make the widget and the box around it fill their container.
- Related Articles drew blank tiles next to real photographs when only some articles had a featured image.
  Pictures now show only when every listed article has one.
- The hero's meta line left a separator dangling at the end of a wrapped line; the separators are hairlines
  that fall outside the box, and are clipped, where a line starts.
- A page mixing shortcodes or blocks with a native element in a Breakdance template loaded the stylesheet and
  GSAP twice, and a widget in a footer or template got its stylesheet after the content even on block themes.
  One flag decides who delivers, the assets are found before `<head>` when the body has rendered first, and on
  a classic theme the already-queued scripts are withdrawn.
- White type over a bright picture was unreadable: the hero's scrim faded to nothing at the top, where the
  eyebrow and the first line of the title sit (1.5:1 and 1.9:1 over an overcast sky). Hero and CTA now share
  one `--evpx-scrim` token (0.6), and their eyebrows are no longer dimmed. Measured worst case is 5.0:1 or better.
- Dynamic data showed as raw text in the builder canvas: choosing "Post Title" on a native element saved the
  token correctly and the front end resolved it, but Breakdance's server-side render hands an element the
  unresolved token. Native elements now resolve it themselves, the way Breakdance's own Google Map does.
- The empty row the Items repeater's "Add" button creates was rendered: an empty FAQ button, and an empty entry in
  the FAQ's structured data. A row now shows once it has its title (the question, for an FAQ item).
- The **EV Reading Time** dynamic field was "Pro only" — Breakdance's default for a field that doesn't say
  otherwise: it carried a Pro badge and could not be chosen without a Breakdance Pro licence. It is open to everyone.
- The Element Studio save location shared the PHP namespace `EVPX` with the native elements: an element named
  "Hero" saved in Element Studio would have been a fatal "cannot redeclare class". It is now `EVPXStudio`.

**Changed**
- `docs/BREAKDANCE-ELEMENT-STUDIO-BRIDGE.md` is now `docs/BREAKDANCE.md`, and covers all of it.
- `Related Articles`: see above; nothing else changes for existing shortcodes and blocks.

**Tests**
- `breakdance-real-check.sh`: 22 checks (native elements' contract, dynamic-data paths, toggles, same markup as
  the shortcode, Element Studio namespace next to a same-named class).
- `breakdance-qa.mjs` on a native page also drives the builder: Add panel, selecting each element, editing,
  toggling, and choosing a picture in the media library.
- New: `builder-save-check.sh` (choose a dropdown option, edit, Save, front end, reopen the builder; add an
  element from the Add panel to an empty page and save it) and `isolation-check.sh` (three pages without an EV
  element are pixel-identical with the plugin active and inactive, under two themes).
- New: `media-pages.sh` + `media-qa.mjs` (generated pictures: crop, alt, srcset, related row, wrapped meta,
  contrast of hero and CTA type over a near-white picture),
  `template-check.sh` + `template-qa.mjs` (footers and a Single Post template under a block theme, Breakdance's
  Zero theme and a bare classic theme).

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
