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

1. **Native Breakdance elements** (`src/Breakdance/Native/`): ten PHP classes, `EVPX\Hero`, `EVPX\Faq`, …,
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
│   └── Support/                    # ReadingTime, Icons (+ icons-data.php), Art, ChargingModel
├── assets/
│   ├── css/evpx.css                # tokens + every component, one file, everything under .evpx-*
│   ├── js/                         # evpx.js (vanilla core: FAQ, tabs, the explorer, art), motion.js (GSAP), block-editor.js
│   ├── fonts/                      # Spectral, Geist, Geist Mono woff2 (SIL OFL) + licence
│   └── icons/                      # the Phosphor licence (MIT); the outlines themselves are in src/Support/icons-data.php
├── templates/                      # one PHP view partial per element
│   └── art/                        # the built-in drawings: one inline SVG per file (hero-schematic, charge-curve, wallbox, …)
├── languages/                      # .pot translation template
├── tests/                          # docker/ (setup, real-Breakdance, media and template checks), playwright/ (qa, interaction-qa, explorer-qa, a11y-qa, breakdance-qa, media-qa, template-qa), php/ (the model grid), contrast-check, css-check, build-zip
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
| 4 | EV Charging Explorer | `[evpx_explorer]` | `evpx/explorer` | the one interactive: dwell time and charger against what reaches the battery; dark panel; §5.8 |
| 5 | EV Scenario Cards | `[evpx_scenarios]` | `evpx/scenarios` | container; children are `[evpx_scenario_card]`; an icon per card |
| 6 | EV Technical Flow | `[evpx_flow]` | `evpx/flow` | Grid → Site → Charger → Vehicle → Battery; an icon per step |
| 7 | EV Decision Factors | `[evpx_decision_factors]` | `evpx/decision-factors` | container; children are `[evpx_decision_factor]`; numbered flat list |
| 8 | EV FAQ | `[evpx_faq]` | `evpx/faq` | container; children are `[evpx_faq_item]`; optional FAQPage JSON-LD |
| 9 | EV Related Articles | `[evpx_related]` | `evpx/related` | lists real published posts; nothing for visitors when empty; a drawing for an article without a picture |
| 10 | EV CTA | `[evpx_cta]` | `evpx/cta` | dark / accent / media variants |

Each element declares its controls once (`Element::controls()`); defaults, sanitization, the
shortcode attribute set, the block attribute schema, the block-editor Inspector panels and the native
Breakdance element's controls are all derived from that one list. `docs/WIDGETS.md` is the human-readable
reference. The nine section widgets also share one `spacing` control (default / compact / none).

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

Avoids the cyan/blue-gradient EV cliché. Ink-graphite base, warm copper as the single
confident accent (energy/CTA), desaturated blue reserved for technical/data moments only. From 0.6.0 the copper is
also the design's *light* (§5.7): the same hue, on a dark surface, as a thin line with a soft halo.

Tokens live on `.evpx-root` (every top-level element carries it), never on `:root`, so nothing
leaks to the rest of the page. Dark values are applied by `data-evpx-theme="dark"` (or `auto`,
gated on `prefers-color-scheme`) on that same element.

```css
.evpx-root {
  /* Light surfaces */
  --evpx-surface-base: #eef0f3;
  --evpx-surface-raised-hi: #ffffff;   --evpx-surface-raised-lo: #c7ced6;
  --evpx-surface-recessed-hi: #d7dce2; --evpx-surface-recessed-lo: #ffffff;
  --evpx-surface-sheen: #f5f7f9;       /* the lit face of a raised surface ... */
  --evpx-surface-shade: #e9ecf0;       /* ... and its shaded face */
  --evpx-ink: #14171c;  --evpx-ink-muted: #4b535e;  --evpx-ink-faint: #626b76;
  --evpx-border: #d7dce2;

  /* Accents. Fill = surfaces that carry --evpx-accent-ink text (buttons, flow nodes,
     the accent CTA). Text = the same hue as text/icon/focus ring on a surface. */
  --evpx-accent: #a4531f;
  --evpx-accent-text: #a4531f;         /* dark mode: #d98a5a */
  --evpx-accent-ink: #ffffff;
  --evpx-accent-deep: #7b3a12;         /* what a button fills with on hover, still under white type */
  --evpx-technical: #4e75a6;           /* data/infra moments only: the AC range bar */
}
/* Dark: surface-base #14171c, raised #1e232a / #05070a, recessed #0a0d11 / #22282f,
   sheen #1c2128, shade #101318, ink #edeff2, muted #a7aeb6, faint #838b96, border #262c34. */
```

Every pairing the components use is asserted by `node tests/contrast-check.mjs`, which reads
the tokens straight out of `assets/css/evpx.css`: body 6.8:1, small labels 4.7:1, accent text
4.8:1 on the light surface (6.6:1 in dark), white-on-accent 5.5:1, white on the deeper hover fill
8.6:1. The lit and shaded faces of a raised surface are checked too, since cards and the comparison
plate carry text on them. (An earlier copper,
`#b9662f`, was documented here as passing. It did not — 3.7:1 as text, 4.2:1 as a button —
and was darkened once the check existed. `axe-core` then confirmed zero violations across
the widgets; see `docs/QA-REPORT.md`.)

### 5.2 Surfaces (the neumorphism formula)

The depth is built once, from the surface colours, so dark mode only swaps colours:

```css
.evpx-root {
  /* elev = raised off the surface (control, card, the comparison plate); well = pressed into it */
  --evpx-elev-1: 3px 4px 9px -2px var(--evpx-surface-raised-lo), -3px -3px 8px var(--evpx-surface-raised-hi);
  --evpx-elev-2: 6px 8px 18px -4px  …lo, -6px -6px 14px …hi;
  --evpx-elev-3: 12px 16px 30px -12px …lo, -8px -8px 20px -6px …hi;
  --evpx-well-1: inset 2px 2px 5px …recessed-hi, inset -2px -2px 5px …recessed-lo;
  --evpx-well-2: inset 4px 4px 9px …recessed-hi, inset -4px -4px 9px …recessed-lo;
}
.evpx-root .evpx-surface--raised {
  background: linear-gradient(145deg, var(--evpx-surface-sheen), var(--evpx-surface-shade));
  border-radius: var(--evpx-radius-lg);
  box-shadow: var(--evpx-elev-3);
}
.evpx-root .evpx-surface--recessed { background: var(--evpx-surface-base); border-radius: var(--evpx-radius-md); box-shadow: var(--evpx-well-2); }
.evpx-root .evpx-surface--flat { background: transparent; box-shadow: none; border-radius: 0; }
/* plus .evpx-surface--raised-sm (cards; elev-2) and .evpx-surface--accent; every rule is
   scoped under .evpx-root, see §5.6 */
```

Four surfaces total: `raised` (cards, the comparison plate), `recessed` (the well inside a scenario card, the
tab track), `accent` (copper fill, flat — never neumorphic, needs full contrast), `flat` (editorial text
blocks and media — most of the page; neumorphism is used *selectively*, per the UX spec). Layering is
the point: a raised card holds a recessed well, a raised plate holds a range bar, a recessed track holds a
raised thumb. Where a control sits on a photograph (the related-article arrow) it gets a plain drop shadow:
the white half of a neumorphic shadow reads as a glow over a dark picture.

### 5.3 Typography

Three voices, each with one job:

- **Spectral** (static serif, Light 300 and Regular 400): the argument. Hero and section headings and the
  large numerals (a key figure, the comparison's power range) in Light with tight tracking; card, question,
  factor and related-article titles in Regular. How it is set lives in four tokens on `.evpx-root`
  (`--evpx-display-weight`, `--evpx-display-tracking`, `--evpx-title-weight`, `--evpx-title-tracking`), so a
  different display face is one `@font-face` and those four values, not a hunt through the rules.
- **Geist** (variable sans, upright + italic): reading and controls. Body copy, buttons, tabs, the data
  values. The italic is bundled so `<em>` in body copy is never a synthesised slant.
- **Geist Mono** (variable mono): the labels that read like a spec sheet. Eyebrows, hero captions, the
  caption over each data row, the ruler's scale, dates. Small, uppercase, spaced — and nothing
  else is uppercase.

Numerals that are compared (`7–22 kW`, `50–350+ kW`) are set lining and tabular
(`font-variant-numeric: lining-nums tabular-nums`) so digits line up between panels; Spectral's tabular
figures were measured (`1111` and `0000` set to the same width).

Self-hosted woff2, Latin subset (Spectral 22 KB + 22 KB, Geist 29 KB + 31 KB italic, Geist Mono 23 KB), bundled
in `assets/fonts/` under the SIL OFL with the licence file alongside. Declared as `EVPX Spectral` / `EVPX Geist` /
`EVPX Geist Mono` so they can never merge with a site's own copy, `font-display: swap`, with system
serif/sans/mono fallbacks. No request to a third-party font host is made (asserted by the QA suite, which also asks
each family for a loaded face).

How the display face was chosen, in the order it happened. The first draft of 0.5.0 used **Newsreader**, picked from six
pairings rendered on the real hero, comparison and decision list (Fraunces + Libre Franklin, the 0.4.0 pair, as the
control; Bodoni Moda lost its hairlines on the dark hero, Cormorant read boutique-hotel). The project's own design
notes (the Impeccable reference under `.claude/skills/`) then turned out to list Newsreader among the faces a model
reaches for by default, and ask for a reason no other face could give. There was none, so the choice was run again:
twelve off-list candidates, sans and serif, in the real hero, section, comparison, decision list, scenarios, FAQ and
CTA. Mona Sans, set light and extended, was the best-looking of the sans faces, but its licence reserves the name
"Mona", which makes a subset served under another name a licensing question this plugin should not raise; of the
licence-clean sans faces, Archivo, Anybody, Lexend Giga and Saira came out as a plain grotesque, a quirky one, an
over-stretched geometric and a sporty technical face. Libre Caslon Display and Noto Serif Display had hairlines too fine for
white type on the dark hero. **Spectral** kept what the serif was there for (a light cut that holds on the dark hero,
lining tabular numerals, an editorial voice beside Geist) and is 88 KB lighter than Newsreader's variable file.

Scale (fluid via `clamp()`, following the widget's own box in `cqi`, with a viewport `vw`
fallback where container queries are unsupported): eyebrow 12px mono, body 17px/1.65, lede
20px, section heading `clamp(2rem, 1.25rem + 2.6cqi, 3.25rem)`, closing headline up to 60px,
display `clamp(2.75rem, 1.5rem + 5.2cqi, 6rem)`. A key figure or a comparison's power range is sized to
its *own* box, not the widget's: the plate and the figure block are size containers of their own and the figure is
`clamp(1.25rem, 17cqi, 4.5rem)` of that box, so "50–350+ kW" fits a full-width plate, a half-width one in
side-by-side mode and a 220px column alike (0.5.0's first cut sized it to the widget and overflowed at 320px).

### 5.4 Spacing / radii / breakpoints

- Spacing unit 4px: tokens `--evpx-space-1` (0.25rem) … `--evpx-space-32` (8rem). Every token the stylesheet reads must
  exist: `tests/css-check.mjs` fails on one that doesn't (0.4.0 read two that were never defined)
- Radii: `--evpx-radius-sm` 6px, `--evpx-radius-md` 12px, `--evpx-radius-lg` 18px (no pills on cards/buttons)
- Darkening behind white type over a picture (hero, CTA): `--evpx-scrim` (0.6), the value at which type stays
  above 4.5:1 over a near-white photograph; `tests/playwright/media-qa.mjs` measures it
- Vertical rhythm of every section widget: `--evpx-section-y` (`clamp(3.5rem, 2rem + 4cqi, 6rem)`); the `spacing`
  control sets it to `--evpx-space-12` (compact) or `0` (none) through `data-evpx-spacing` on the widget
- Layout breakpoints are **container** thresholds, not viewport ones: 40rem (two-column grids),
  48rem (side-by-side comparison, wider container padding; below it the flow turns vertical),
  64rem (two-column section/decision layouts, three-column grids; below it the hero's drawing is a band), and a
  narrow-box tightening below 30rem. The explorer has one more, below 20rem (a phone inside a builder Section that has
  padding of its own). See §5.6.
- Widths swept by `tests/playwright/qa.mjs` (screenshot + sideways-overflow assertion): 320, 375,
  390, 430, 768, 1024, 1280, 1366, 1440, 1920. `breakdance-qa.mjs` sweeps 320, 390, 768, 1024,
  1440, 1920 on a real Breakdance page.

### 5.5 Motion and hover

```css
.evpx-root {
  --evpx-ease-premium: cubic-bezier(0.23, 1, 0.32, 1);   /* fast in, settles: for anything entering or hovering */
  --evpx-ease-inout: cubic-bezier(0.77, 0, 0.175, 1);    /* for something that travels: the comparison thumb, the charge lines */
  --evpx-dur-micro: 0.16s;   /* press */
  --evpx-dur-base: 0.32s;    /* hover */
  --evpx-dur-section: 0.7s;
  --evpx-dur-hero: 1.2s;
}
```

Motion hierarchy: hero (strongest) → comparison (medium) → supporting sections (subtle) →
body text (static). `assets/js/evpx.js` is the dependency-free core (FAQ accordion, tabs, the
comparison thumb, the in-view and motion markers, the card highlight) and works alone;
`assets/js/motion.js` adds what needs GSAP (the hero timeline, batched reveals, the reading
progress bar, the FAQ height, the panel change).

**The motion contract.** Nothing moves unless `evpx.js` has marked the widget `data-evpx-motion="on"`,
which it does only for a real front-end view with no reduced-motion preference, outside the builder
canvas, on a widget whose own animation control is not off. A block is shown its entrance once, when
it first reaches the viewport (`.evpx-in-view`). Every state a visitor can end up in *without* motion is
the finished, static one, so a page with no script, a reduced-motion visitor and the builder canvas
all see the complete design. `tests/css-check.mjs` fails on any CSS animation that is not gated on the
marker, and `tests/playwright/interaction-qa.mjs` checks the finished state for a reduced-motion visitor.

**One hover idea: the control charges.** It is the product, so it is the metaphor:

| Where | On hover / press |
|---|---|
| Buttons | A fill sweeps in from the left (deeper copper; white on the CTA), the drawn arrow travels 4px; pressing seats it (`translateY(1px) scale(.985)`) |
| Scenario cards | A soft highlight and a one-pixel copper rim follow the pointer, the card steps up one level of depth, and the recessed well eases toward flat: the recommendation comes up to meet you |
| Decision list | The row's number steps forward and its "Ask" label takes the accent |
| FAQ | The question turns copper and the knob lifts; opening presses the knob in and turns its plus into a minus |
| Related articles | The picture eases in, a raised arrow knob appears in its corner, and the title underlines itself line by line |
| Comparison | A raised thumb slides between the tabs; on a change the range bar grows to its length and the figure rises out of its line |
| Flow | The steps arrive one after another while the line between them charges toward the next |
| Hero | The title rises out of a mask, the picture settles and drifts slower than the page, a copper line at the foot fills once from the left |

Movement waits for `@media (hover: hover) and (pointer: fine)`; a phone gets the colour changes and none
of the decoration (the arrow knob is not drawn, the pointer highlight is not bound).

### 5.7 Neon, icons and drawings (0.6.0)

**Neon is copper drawn as light, not a second colour.** `--evpx-neon` (`#ff8a3d`) is the accent on a dark surface;
`--evpx-neon-line` is the accent as a line on the surface it is on (the deeper copper on a light one, so a line still
reads; the neon itself on a dark one); `--evpx-glow` and `--evpx-glow-1/-2` are its halo, quiet on light and lit on
dark. The rules that keep it minimal: it is a line of 1.5–2.5px with a halo (or a thin bar), **never a fill and never
body text**; it appears where something is *live or chosen* (the eyebrow's bolt, the line that charges along the foot
of the hero, a lit stroke in a drawing, the selected tab's rule, the chosen charger, the explorer's curve and bars, the
progress bar, the dark CTA's top edge, an icon in its socket) and nowhere else; depth stays neumorphic, and the two
meet only in the socket: an icon in a recessed well, lit in neon. Contrast still comes from real colour: the neon on
the dark surfaces is 7:1 or better, and `tests/contrast-check.mjs` reads the tokens.

**Focus** is one token, `--evpx-focus-ring`, drawn as a 2px outline offset 3px from the control. It is the accent by
default; a surface where the accent would vanish says so (the copper CTA uses white; the hero, the dark CTA and the
explorer use the neon; a CTA over a picture uses white). The explorer's slider carries its ring on the thumb.
`tests/playwright/a11y-qa.mjs` tabs through the page in both colour schemes and measures every ring at 3:1 against
the surface it is drawn on.

**One icon family.** Phosphor Icons, Regular weight (MIT; `assets/icons/LICENSE.txt`), 53 outlines in
`src/Support/icons-data.php`, drawn inline by `EVPX\Support\Icons::svg()` as `<svg … fill="currentColor"
aria-hidden="true" focusable="false">` at one of three sizes (`--evpx-icon-sm/md/lg`). Inline, not a sprite or a font:
it works in a shortcode, a block, a Breakdance canvas and a feed alike, takes the colour of the text around it, and
cannot fail to load. An icon is always beside text that says the same thing, so it is hidden from assistive technology.
Two ways an icon is placed: a **socket** (`.evpx-iconchip`, a recessed disc, for an icon that labels a block: a scenario
card, a flow step) and **bare, inline with text** (a tab, a decision factor's title, a byline item, a button's arrow, the
FAQ knob). `Icons::guess()` chooses one from a piece of copy by its words (English; anything else gets the caller's
fallback), and the controls that show icons let an editor pick one instead (`symbol`, automatic by default). A flow
either has an icon on every step or on none.

**Drawings.** `EVPX\Support\Art` renders inline SVG from `templates/art/`: the hero's schematic, and six panels
(`charge-curve`, `wallbox`, `dc-cabinet`, `grid-path`, `wave-ac`, `wave-dc`). They are how the design has pictures
before it has photographs: technical line drawings of what the article is about, in `currentColor` and the neon,
on their own dark ground (`.evpx-artpanel`, `--evpx-art-ground`), so they read on a light page too. They are decoration
(`aria-hidden`), carry only units, acronyms and a few translated words, and every gradient, mask and filter id is
unique per instance. The neon halo is one blurred group (an SVG `<filter>`, since Safari does not apply a CSS filter to
shapes inside an `<svg>`); a zero-height shape (a horizontal rule) is filled with a gradient in user space, or drawn as a
thin rect, since a gradient in object-bounding-box units does not paint on a line with no height.
A drawing is finished as printed; with motion on its outlines draw themselves in, once (`stroke-dashoffset` on a path
of `pathLength="1"`), the light comes on, a band of light crosses the car, and a pulse travels the cable, gated on the same
`data-evpx-motion="on"` marker as everything else and paused by an `IntersectionObserver` while off screen (so a drawing
below the fold draws itself in when it is reached, and nothing animates unseen). Labels are scaled so they land at 12px or
more on the screen whatever size the drawing is shown at (`evpx.js` tells each the scale).

**The hero** is the largest use. Full-bleed dark panel, a blueprint grid, corner marks, a light that follows a fine
pointer (`data-evpx-spot`), the byline as a readout (an icon and a caption over each value), and the drawing at the right
(62% of the width from 64rem, bleeding a little off the edge and fading in from the copy; below that a band under the
copy, sized by the width it has and cropped at the top to the part worth showing; on a phone, the car and the charger
alone). The copy gives up width beside the drawing (`min(34rem, 46cqi)`) so nothing is ever set over it.

**Forced colours.** The depth is drawn with shadows and a forced-colours browser draws none, so §18 of the stylesheet
gives each raised or sunken surface an edge, keeps the fills that carry meaning (bars, the selected tab, the progress
bar) as system colours, lets the drawings keep their own dark panel, and takes the hero's decoration away.

### 5.8 The explorer's model

`EVPX\Support\ChargingModel` (PHP) and the same operations in `assets/js/evpx.js`. Time is stepped a whole minute at a
time. AC: the charger gives its rating, but the car's onboard charger caps it. DC: the car accepts its peak in full up to
half full, then linearly less (35% of the peak at 80%, 10% at 100%); the charger gives the smaller of its rating and that.
Energy per minute is the smaller of the power over 60 and what is left to fill. Range is energy over consumption. The
page is server-rendered with the default answer (the same model), the script recomputes everything on every input, and
`tests/playwright/explorer-qa.mjs` compares the script's model to the PHP model over 200 cases (five cars, five chargers,
eight dwell times) to 1e-9, and the page before the script to the page after it. The slider is bent (`minutes = 15 + 705 ×
(position/100)²`, snapped to 5, 15 or 30 minutes) and the chart's time axis is a square root of time, so the first hour,
where the differences are, is not a sliver. The sentence is a polite live region that updates 350ms after the last change,
so a screen reader hears one message, not one per step.

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
