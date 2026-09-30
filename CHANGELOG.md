# Changelog

## 0.6.0

A second design pass. Yul's brief: fitting pictures, icons used consistently, neon with neumorphism but minimal,
heroes that stop a visitor, futuristic, and a finish worth what a site of this kind costs to have made. The design
language changed (copper became light as well as colour; one icon family; drawings where there were no pictures),
one widget was added, and the audit that came with it (contrast, focus, targets, measure, type scale, forced
colours) was applied to what already existed. Ten defects in the new work were found and fixed
(`docs/QA-REPORT.md`, bugs 45-54); the last three came from looking at every widget at four widths, not from a test.

**Added**
- **EV Charging Explorer** (`[evpx_explorer]`, `evpx/explorer`, a native Breakdance element): how long a car stays,
  which charger, what reaches the battery. A dwell-time slider (15 minutes to 12 hours, bent toward the short stays)
  with presets, five chargers (AC 7 and 22 kW, DC 50, 150 and 350 kW), energy and range added, a battery bar, a chart of
  power against time with the charger's rating drawn as a line the car never reaches, the same dwell time on every
  charger, and a sentence that says what it means. The page arrives with its default answer worked out on the server
  (`EVPX\Support\ChargingModel`), so it reads with no script; the script makes it live, with the same model. The battery,
  the car's AC and DC limits and its consumption are controls, and the panel says under itself that it is illustrative.
- **One icon family**, Phosphor Regular (MIT), 53 outlines drawn inline (`EVPX\Support\Icons`), placed the same way
  everywhere: a socket for an icon that labels a block (a scenario card, a flow step), bare beside text (tabs, a decision
  factor, the byline, buttons, the FAQ knob, the explorer's charger choices). Scenario cards, decision factors and flow steps
  choose theirs from their words and offer a control to pick another (`symbol`); a flow has an icon on every step or none.
  The CSS-drawn arrow and FAQ plus are gone; every glyph is the same family.
- **Built-in drawings** (`EVPX\Support\Art`, `templates/art/`): the hero's schematic (a car on a charger, a
  state-of-charge gauge, a floor that recedes) and six panels (`charge-curve`, `wallbox`, `dc-cabinet`, `grid-path`,
  `wave-ac`, `wave-dc`), inline SVG on their own dark ground. They stand in for photographs, which this build
  environment could not fetch: a Section takes one when it has no picture (`artwork`), the Comparison shows a wave or a
  level with each panel (`art`), and a Related article with no featured image is given one (`art`). Their outlines
  draw themselves in with motion, once; a pulse runs the cable; a band of light crosses the car; without motion they
  are finished and still, and off screen nothing animates.
- **Neon as a role of the copper** (`--evpx-neon`, `--evpx-neon-line`, `--evpx-glow`): a thin line and a soft halo,
  never a fill, used where something is live or chosen. Depth stays neumorphic; the two meet in a recessed socket that
  holds a lit icon.
- **Hero v2.** A dark instrument panel: blueprint grid, corner marks, a light that follows a fine pointer, the byline
  as a readout (icon, caption, value), the drawing at the right, and the copper line along the foot with a glow.
  Below 64rem the drawing is a band under the copy; on a phone it is the car and the charger alone. `artwork`
  (schematic or none) is a new control.
- **The dark CTA is the default**, and closes the article the way the hero opens it: the grid, a copper glow and a neon
  rule along its top edge. `accent` (the flat copper slab) and `media` remain.

**Changed**
- The accent focus ring is now one token, `--evpx-focus-ring`, and a surface where copper would vanish says so (white on
  the copper CTA, neon on the hero, the dark CTA and the explorer).
- The comparison's tabs are 44px tall, carry an icon, and the selected one has a copper rule along its foot as well as
  its depth; the range bar has a key saying which bar is this panel and which outline is the other.
- Every font size is a token on the nine-step scale (the rules that set a literal size were moved onto it), and nothing
  meant to be read is smaller than 12px (three rules were 11px). The FAQ answer keeps to the reading measure plus its
  padding (it ran to about 80 characters). Scroll reveals travel 18px, one after another 60ms apart (they travelled
  28px, 90ms apart).
- Related articles: an article without a picture is shown a drawing instead of switching the whole row to text.
- A **forced-colours** block: an edge where the design used a shadow, system colours for the fills that carry meaning,
  the hero's decoration taken away.
- The explorer's sentences keep a figure and its unit on one line.
- The drawing panels catch the hero's pointer light (a warm glow that follows a fine pointer, off on touch and under
  reduced motion), and a decision factor's icon lights when its row is hovered.
- A Related row of three that has to fall to two per row at a tablet width no longer leaves the third card alone in the
  corner: it takes the whole row, its picture beside its text.

**Fixed** (in the new work; bugs 45-54 in `docs/QA-REPORT.md`)
- The hero drawing's horizon painted nothing (a gradient in object-bounding-box units on a line with no height).
- The band of light crossing the car was invisible (a class that said `fill: none` beat the shape's `fill` attribute).
- A label in the hero's drawing ran under the copy at every desktop width.
- In the tablet band the drawing's floor, horizon and glow ended in vertical edges.
- The explorer's chart labels scaled down with the chart to 9px.
- The explorer overflowed a 220px box (a phone in a builder Section with its own padding), found by the 320px sweep.
- The explorer's sentence could break between "122" and "km".
- In the tablet band the hero's state-of-charge gauge was faded to a ghost by the mask meant to soften the band's cut edge.
- The DC cabinet's readout said "KW" (a capitalised label rule reached the unit) and sat tight under its number on a phone.
- A Related row of three left its third card alone in the corner at a tablet width.

**Added - tests**
- `tests/playwright/explorer-qa.mjs`: the page before the script and after it; the script's model against the PHP one
  over 200 cases (`tests/php/model-matrix.php`); the controls; six widths.
- `tests/playwright/a11y-qa.mjs`: a focus ring at 3:1 on every control it reaches, in both colour schemes; targets;
  12px; measure; icons; forced colours.
- `tests/playwright/layout-qa.mjs`: the composition checks a screenshot review turned up. The hero's gauge is as bright as
  its type beside the copy and in the band under it; no drawing paints with a gradient measured from a box with no height
  or width; a unit in a drawing keeps its case; the hero's floor, horizon and glow reach the edges of their box at six
  widths; a Related row ends square at 820, 1366 and 390 px. Each was watched failing on the code it guards.
- Render checks for icons, drawings, the explorer and the CTA's default; the interaction suite gains the hero drawing's
  motion and its stillness, and a check that its labels and charger lie clear of the copy at seven widths.

## 0.5.0

A design release. Yul's brief was that the article should look better than the reference it was benchmarked
against, in its effects, its hover states and its type. The pass started with an audit of the running pages,
which found eight defects in 0.4.0 as released; drawing the new design introduced three more, caught before
release. All eleven are fixed and guarded (`docs/QA-REPORT.md`, bugs 34-44).

**Changed - the look**
- **Type.** Spectral (headings, the large numerals), Geist (reading, controls) and Geist Mono (the labels that read
  like a spec sheet) replace Fraunces and Libre Franklin. Chosen by rendering the real widgets in twelve
  candidates side by side, after a first pick (Newsreader) turned out to be a face the project's own design notes
  list as an over-used default. Self-hosted woff2 under the SIL OFL, 127 KB in all against 0.4.0's 96 KB but 88 KB
  lighter than the first pick; Geist italic included so emphasis in body copy is never a faux slant. Numerals that
  are compared are lining and tabular. How the display face is set is four tokens (`--evpx-display-weight` and
  `-tracking`, `--evpx-title-weight` and `-tracking`), so changing it is one `@font-face` and those values.
- **Neumorphism, layered.** One depth vocabulary (`--evpx-elev-1..3`, `--evpx-well-1..2`) built from the surface
  colours, a lit and a shaded face on raised surfaces, and surfaces that hold each other: a raised card with a
  recessed well, a raised plate, a recessed track with a raised thumb. Radii tightened to 6/12/18px.
- **Hero.** Left-aligned on the same grid as every section (it was a centred block with left-aligned text),
  a display title set on two lines, a captioned spec strip for author, date and reading time, a blueprint grid
  when there is no picture, and a copper line along its foot that charges once as the page arrives.
- **Section.** Without a picture it is an editorial split (heading and key figure beside the copy, `media-left`
  mirrors it) instead of a text column beside an empty half. New optional **Key figure** and caption. The first
  paragraph of multi-paragraph copy leads. "Media above text" now actually puts the media above.
- **Comparison, the centrepiece.** A raised plate per panel with the power range set large and, when both ranges
  are kilowatt figures, drawn on a shared scale so the AC/DC gap is shown, not described. A raised thumb slides
  between the tabs.
- **Scenario cards** step down in the second column, hold their answer in a recessed well, and (with a fine pointer)
  get a highlight and a one-pixel copper rim that follow the pointer. **Flow** is a timeline that charges.
  **FAQ** is rows on hairlines with a knob that presses in. **CTA** is the headline beside what to do about it.
  **Related** shows a raised arrow knob and underlines the title line by line.
- **Hover is one idea, "the control charges"**: a button fills from the left and its (now drawn, not typed) arrow
  travels; pressing seats it. All movement waits for a fine pointer; a phone gets none of the decoration.
- Vertical rhythm follows the widget's width (`clamp(3.5rem, 2rem + 4cqi, 6rem)`), so a phone is no longer given
  desktop gaps.

**Fixed**
- FAQ questions had no vertical padding and the Flow heading touched its steps: the stylesheet read
  `--evpx-space-5` and `--evpx-space-10`, which were never defined. Defined, and `tests/css-check.mjs` now fails on
  any token that is read but not defined (it fails on 0.4.0's stylesheet, with exactly those two).
- A stripe of page colour showed between two widgets in a block theme, where the theme's block gap added a
  margin to each. A widget brings its own rhythm and no longer takes the theme's.
- The Hero and the FAQ were centred narrow columns with left-aligned text, off the grid of everything below them.
- A mouse click on a widget control drew the host theme's `:focus` outline (Twenty Twenty-Five's black box);
  a keyboard still gets the copper ring.
- A comparison's captions ("Best for") wrapped onto two lines beside a long value.
- The reading-progress bar was a 645px column in the middle of a block theme's window, not a bar across the top of
  it: the theme caps and centres every child of the post content, a fixed one included.
- The Hero's "Follow system" mode never followed the system; it was always dark.
- "Media above text" put the media below the text.
- The new comparison plate overflowed a 320px screen (its value column could not shrink); found by the existing
  overflow sweep before release.
- The hero could flash for one frame while GSAP loaded slowly, and a GSAP that arrived after the stylesheet's own
  failsafe replayed the entrance over a hero already on screen.
- A FAQ answer snapped at the end of opening and closing because its padding was not animated with its height.
- The translation template was missing the block editor's three strings ("Select image", "Replace image", "Remove");
  it is regenerated with the script strings included.

**Added - tests**
- `tests/css-check.mjs`: undefined tokens, every selector scoped under `.evpx-root`, no stray `!important`,
  every animation gated on the motion marker.
- `tests/playwright/interaction-qa.mjs`: motion when allowed, the finished state for a reduced-motion visitor and on
  touch, the comparison thumb and range bar, and the hover, press, focus and open states. Each check was watched
  failing against a deliberately broken build.
- `contrast-check.mjs` covers the raised faces and the button's hover fill.
- A third Breakdance fixture page with full-width, zero-padding Sections (the setup `docs/BREAKDANCE.md`
  recommends), which is also how the design is best seen.

**Copy**
- The demo article no longer uses em dashes, repeats "actually" in three headings or labels every section with an
  eyebrow (three remain, of twelve widgets).

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
