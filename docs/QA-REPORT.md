# QA report — v0.5.0

Everything below was run, not reasoned about. Every command is in the repo, so it can be
re-run: see "Reproducing" at the end.

## Readiness

**Release candidate — not yet "production ready".** 0.3.0 was the first release run against a real,
licensed **Breakdance 2.8.3**; 0.4.0 added the nine widgets as native Breakdance elements and drove them in the
builder. 0.5.0 is a design release: the type, the depth, the hero, the comparison, the hover states and the
motion were redone, and the whole matrix was run again against the result: WordPress on Twenty Twenty-Five, and
Breakdance 2.8.3 under its own Zero theme with the widgets in a default Section and in the full-width Section
`docs/BREAKDANCE.md` recommends. Redesigning found eleven more bugs in the plugin's own work (34–44 below), all
fixed and guarded by tests; three of them (a hero that flashed for one frame, a comparison that overflowed at
320 px, and a reading-progress bar that was a 645 px column instead of a bar across the window) were caught by
checks or captures made for other reasons.

Two limits worth reading before you rely on the design verdict. The reference site the brief compares against
could not be opened from this environment (its host is blocked), so "better than the reference" is a judgement
made from the running pages, not a measured comparison. And the design was judged from screenshots and measured
behaviour in Chromium at 1440, 1366, 768, 390 and 320 px, not on a phone in a hand. What still stands between
this and a production label needs things this environment doesn't have: real photography, browsers other than
Chromium, and a site running your caching stack. Details under "Not verified".

## What was tested

| Layer | How | Result |
|---|---|---|
| PHP syntax | `php -l` on PHP 8.4 (plugin header promises 7.4+) | clean |
| WordPress coding standards | `phpcs` — WordPress-Core + WordPress-Extra + PHPCompatibilityWP (7.4+), security/escaping/i18n/prefix sniffs on | 0 errors, 0 warnings, with the formatting exclusions listed and justified in `phpcs.xml.dist` |
| JS syntax | `node --check` | clean |
| Stylesheet, statically | `tests/css-check.mjs`, no browser: every `--evpx-*` token that is read is defined; every selector is scoped under `.evpx-root`; no `!important` outside the reduced-motion rule; every animation is gated on the motion marker | 5/5 |
| Colour contrast | `tests/contrast-check.mjs` reads the real tokens out of `evpx.css` and asserts 15 pairings, light and dark (30 checks), including the lit and shaded faces of a raised surface and white type on the button's hover fill | all pass |
| What the widgets print | `tests/docker/widget-render-check.sh`, inside WordPress, no browser: the comparison's power scale (ranges, points, thousands separators, decimals, and six inputs that must *not* draw a ruler), the section's key figure, the hero's captions and blueprint grid, that typed-in text is escaped | 24/24 |
| Activation / deactivation | WP-CLI, `WP_DEBUG` + `WP_DEBUG_LOG` + `WP_DEBUG_DISPLAY` | clean; the only `debug.log` entries across activation, rendering of every widget and every suite below are core's own wordpress.org update check failing on the sandbox network |
| **Real Breakdance 2.8.3 — integration** | `tests/docker/breakdance-real-check.sh`: 24 assertions against the running plugin (save locations reach Breakdance; Dynamic Data field; reading time; the nine native elements are declared, concrete, in their own category, with control paths that match their controls, repeaters, dynamic-data paths, the spacing attribute, toggle semantics, and the **same markup as the shortcode** for all twelve widgets of the demo article) plus a behavioural probe: an element file saved in the plugin's Element Studio folder — declaring a class with a native element's name — must be loaded by a *fresh* PHP process | 25/25 |
| **Real Breakdance — front end and builder** | `tests/playwright/breakdance-qa.mjs`: a page designed in Breakdance from the demo article, once with Shortcode elements and once with native elements; front end (assets, typography, hover colours, reading time, interactions, motion, overflow at six widths, axe) and the builder itself: server-side renders, canvas, and, for native elements, the Add panel, selecting each element, editing a control (one render), a toggle (saved as `false`), and choosing a picture in the media library | 16/16 on the Shortcode page · 24/24 on the native page · 24/24 on the native page in full-width Sections without padding · 24/24 on the native page with pictures |
| **Builder round-trip** | `tests/docker/builder-save-check.sh`, on scratch pages it deletes: a dropdown lists the widget's options and re-renders the canvas; Save answers 200; the front end and a reopened builder show the edit; an element added from the Add panel to an empty page renders with its starting copy, brings its stylesheet into the canvas with it, and saves | 6/6 |
| **Pictures** | `tests/docker/media-pages.sh` generates six test images (GD gradients with a frame at the edges, one of them a near-white sky), imports them, and builds a shortcode post, a native page and a mixed Related row; `tests/playwright/media-qa.mjs` at 1440 and 390 px | 49/49 |
| **Templates and themes** | `tests/docker/template-check.sh`: a Breakdance footer (native CTA, and a Shortcode element) and a Single Post template, each checked with pages that have their own shortcode or native widgets, under **Twenty Twenty-Five, Breakdance's own Zero theme and a bare classic theme** | 180/180 |
| **Unrelated pages** | `tests/docker/isolation-check.sh`: three pages with no EV element (Sample Page, a post, a Breakdance page), screenshotted with the plugin active and inactive, under two themes | 12/12 |
| Browser suite, any WordPress page | `tests/playwright/qa.mjs`, 23 checks, Chromium, WordPress on the Twenty Twenty-Five block theme, with Breakdance also active (also run with Breakdance deactivated, on plain WordPress, and under the Zero theme; the one width assertion that presumes a block theme is skipped inside a Breakdance Section) | 23/23 |
| Interaction, motion and hover states | `tests/playwright/interaction-qa.mjs`, Chromium: the finished state and the motion that leads to it (the hero's charge line, the flow's connectors, the comparison's range bars), the comparison thumb measured onto the active tab and moved by a click, the button's fill and arrow, the scenario card's rim and highlight following a fine pointer, the decision list's numerals, the FAQ's open state and that a row opens from its own height, the related arrow, keyboard focus rings, no seams between widgets, one shared left edge, "Follow system" following both colour schemes; then the same page for a reduced-motion visitor and for a touch device with no hover | 33/33 |
| Breakdance contract stub | `tests/docker/breakdance-stub.php` + `breakdance-contract-check.php` — kept for CI without a licence; the real check above is authoritative | 11/11 in 0.4.0; not re-run for 0.5.0, which changes nothing under `src/Breakdance` (the real-Breakdance checks above cover the same ground) |
| The shipped artifact | the built ZIP installed as a separate plugin directory and the suites run against *that* | all pass, same counts as the working tree: integration 25/25, browser suite 23/23, Shortcode page 16/16, native page 24/24, pictures 49/49, unrelated pages 12/12, templates 180/180 |
| Motion | real GSAP **3.12.5** (the version the plugin loads by default), served locally because this sandbox blocks cdnjs | verified below |

### The 23 checks in `qa.mjs`

No uncaught exceptions · no third-party font requests · **no horizontal overflow at 320, 375, 390,
430, 768, 1024, 1280, 1366, 1440 and 1920 px** · no leftover `[evpx_*]` text · the bundled fonts
(Spectral, Geist, Geist Mono) actually load · heading hierarchy (≤ 1 `h1`, no skipped levels
inside widgets) · decision factors and related articles render · FAQ accordion toggles · AC/DC tabs
switch · tabs keyboard pattern (Home / Arrow keys, `aria-controls` ↔ `aria-labelledby`) · scenario
cards form clean columns, the middle one stepping down (this replaced "cards align in grid rows" when the
stagger became deliberate) · sections aren't squeezed by the theme's content width · reading-progress
bar tracks scroll · the progress bar spans the window, not the theme's content column · every scroll
reveal settles fully visible · card `:hover` lift survives its reveal · `prefers-reduced-motion` (nothing hidden, zero scroll triggers) · JavaScript disabled (FAQ
answers and both AC/DC panels readable, no dead tabs, hero visible) · builder-canvas simulation
(page inside an iframe: motion off, content visible, zero scroll triggers) · the hero never flashes
visible → hidden → visible on a slow GSAP load, tested twice: a load slower than the entrance (1.2 s) and a
load slower than the stylesheet's own failsafe that reveals the hero (2.8 s) · `axe-core` WCAG 2.0/2.1/2.2 A + AA
+ best-practice over every widget at 1280 px and 390 px: **zero violations**.

### The checks in `breakdance-qa.mjs`

Front end (both page types): every widget in the Breakdance tree renders · stylesheet is printed in
`<head>`, each asset once · no heading falls back to a host typeface · hero title stays light on the dark
hero · primary buttons keep white text, also on hover · hero reading time comes from the element tree · FAQ
opens · AC/DC tabs switch · motion runs and every reveal settles · no widget overflows its box at 320, 390,
768, 1024, 1440, 1920 px · axe-core zero violations at 1280 and 390 px · no uncaught exceptions.
Builder (both page types): every widget renders through Breakdance's server-side render (all 200, no
shortcode text) · that render is static (`data-evpx-animate="0"`, no progress bar) · the canvas shows every
widget, the hero in its own typeface, motion off, no scroll triggers · no console or page errors from this
plugin.
Builder, native page only: the Add panel lists all nine EV elements · selecting each of the nine kinds in
the canvas opens its controls · editing a control re-renders through exactly one server-side render and shows
the change · a toggle switched off is saved as an explicit `false` · the Items repeater works (a new empty row
isn't rendered, its question adds the item, its answer shows) · **choosing a picture in the builder's media
library saves a media object with an attachment id, re-renders once, and the canvas shows it described and
responsive** (skipped without the fixture images) · **dynamic data**: "Post Title" chosen on the Hero title is
saved as a token and the canvas shows the title, not the token; the plugin's own EV Reading Time field has no
Pro badge, is saved as a token, and the canvas shows the value.

Each check was written after the bug it guards was seen for real. They were also confirmed to
*fail* on the broken state: against the pre-fix stylesheet six of the sixteen fail (system-font
headings, dark hero title `rgb(17,24,39)`, blue button text `rgb(59,130,246)`, sideways scroll,
contrast, builder typeface); against the pre-fix builder detection the two builder checks fail
(`data-evpx-animate="1"`, builder flag empty); and with the recursion guard removed the
self-referencing page segfaults PHP.

### The checks in `media-qa.mjs`

For the shortcode page and the native page, each at 1440 and 390 px: the hero picture loads, is described and
has a `srcset` · fills its box without letterboxing · both section pictures load, are described and keep their
proportions · all six scenario icons load · the CTA picture loads and fills its box · the Related row is a full
row of same-sized pictures · (390 px) a wrapped hero meta line clips the separator that would start a row ·
nothing overflows · no console errors and no failed requests. Then, on the mixed page: a Related row where one
article has no featured image shows no picture slots at all. Then, on the bright page — a hero and a CTA over a
near-white sky, the worst case for white type — each line of type (eyebrow, title, excerpt, meta, body) is
measured against the brightest 5% of the picture behind it, at 1280 and 390 px: 4.5:1, or 3:1 for large type.
With the previous template and stylesheet the mixed-row check and both meta checks fail, and ten of the
fourteen legibility checks fail (the eyebrow at 1.2–1.5:1, the title at 1.6–1.9:1).

### The checks in `template-check.sh`

Under each of three themes, for eight situations (twelve shortcode widgets in a post; twelve native widgets on a
Breakdance page; a native CTA, and a Shortcode-element CTA, in a Breakdance footer, each on a plain page and on
pages with their own widgets; native Hero and FAQ in a Single Post template): the expected number of widgets
render · the stylesheet and each script are delivered **exactly once** · the stylesheet is in `<head>` · the
widgets are styled in their own typeface · **no widget has collapsed to nothing** · nothing overflows · no
console errors. (On the bare classic theme the stylesheet may appear twice and a footer's may come late; the
scripts still load once.) Without the two Loader changes five of the block-theme checks fail (assets loaded
twice; a footer widget's stylesheet after the content); without the width rules all twelve widgets in a post
collapse to zero width under the Zero theme.

### Weight

Front-end critical path (stylesheet + `evpx.js` + `motion.js`): about **21 KB gzipped** (CSS 14.6, `evpx.js` 4.0,
`motion.js` 3.1), up from 13 KB in 0.4.0: the redesign is more CSS (depth, the comparison plate and ruler, the
timeline) and a little more script (the thumb, the in-view marker, the pointer highlight). Bundled fonts: 128 KB in
five files, up from 96 KB (Spectral 22 KB in each of two weights; Geist 29 KB and its italic 31 KB; Geist Mono
23 KB; woff2 is already compressed) with `font-display: swap`. A browser fetches only the faces a page uses, so a
page without italic body copy never loads the Geist italic. GSAP + ScrollTrigger come from the CDN and only on pages
with an EV element. No render-blocking third-party request is made by the plugin itself. (Lighthouse / field
performance were not measured.)

## Bugs found and fixed

**Pass 1 — first Docker + browser run**
1. Nested child shortcodes silently didn't render (`evpx_scenario-card` registered with a hyphen,
   used with an underscore) — `Element::shortcodeTag()`.
2. Hero "dark" mode was white-on-white: its `data-evpx-theme` collided with the page-wide
   dark-token selector. Renamed to `data-evpx-hero-mode`.
3. Scenario Cards grid scrambled: `wpautop` runs before `do_shortcode` and injected `<br>`/`<p>`
   between nested tags, which became grid children — `Element::stripAutopArtifacts()`, scoped to
   the container's own captured content, not a site-wide filter change.
4. Every section squeezed to the theme's ~645 px content width — WordPress's own `alignfull`
   class on every top-level wrapper.

**Pass 2 — "what's not finished?"**
5. The reading-progress bar was dead code (JS looked for a wrapper no widget rendered). Now the
   Hero's `progress_bar` toggle.
6. A false alarm worth keeping on record: the Hero's text column measured 736 px and looked like
   bug 4 again. It was its own intentional `max-width: 46rem`, confirmed with Chrome DevTools
   Protocol's `CSS.getMatchedStylesForNode`, and the assertion was fixed instead of the CSS.

**Pass 3 — real GSAP, axe-core, PHPCS, and auditing my own claims**
7. **Colour contrast failed WCAG AA and the docs said it passed.** Copper `#b9662f` was 3.7:1 as
   text and 4.2:1 behind white button text; `ARCHITECTURE.md` cited a contrast script that didn't
   exist. Split into `--evpx-accent` (fill) and `--evpx-accent-text` (lightens in dark mode),
   darkened the copper, wrote the script, and it now gates every pairing. A related catch: the
   accent CTA's eyebrow used `opacity: .85` on white-on-copper (4.43:1).
8. Tabs used `role="tabpanel"` on `<article>` (not permitted) and lacked the rest of the pattern
   (`aria-controls`, roving `tabindex`, arrow keys). The tab bar was also a set of dead buttons
   without JS.
9. With JavaScript off, FAQ answers were unreachable. `@media (scripting: none)` now falls back to
   fully readable content.
10. The hero flashed visible → hidden → visible whenever GSAP was slow to load.
11. GSAP's leftover inline `transform` killed the cards' CSS `:hover` lift after their reveal.
12. Elements already on screen at load were hidden only to fade back in.
13. Related Articles rendered a row of empty neumorphic boxes when no article had a thumbnail.
14. The demo page had two `h1`s (theme title + hero) — new Hero `title_tag` control.
15. Documentation claims that weren't true: the missing contrast script, a `FontLoader.php` that
    never existed, "three Dynamic Data fields" (there is one), wrong class names in the surface
    examples, a QA script that lived only in a session scratchpad, and a demo article that wasn't in
    the repo. All corrected; the demo is now `docs/demo-article.txt`.

**Pass 4 — a real Breakdance 2.8.3**

Found by reading Breakdance's own source and then running it; each is guarded by a test.

16. **The Element Studio save locations were never registered.** Breakdance fires
    `breakdance_loaded` from its own `plugins_loaded` callback and reads save locations at
    priority 10 on that action. WordPress loads plugins alphabetically, "breakdance" sorts before
    "ev-charging-experience", and this plugin added its hook from *its own* `plugins_loaded`
    callback — after the action had already fired. Real run: 0 of 2 locations registered, and a
    saved element in the plugin's folder was never loaded. Now the plugin boots at include time.
17. **Builder detection used values Breakdance never sends.** It looked for
    `?breakdance=edit|run`; the real signals are `?breakdance=builder`, the canvas iframe's
    `breakdance_iframe`, and Breakdance's *own* AJAX — a POST of `breakdance_*` to a front-end URL,
    where `is_admin()` and `wp_doing_ajax()` are both false. Builder renders were animated (hero
    held back, progress bar, scroll reveals). Every motion control is now switched off for them.
18. `isBreakdanceActive()` fell back to a constant, `BREAKDANCE_VERSION`, that doesn't exist
    (`__BREAKDANCE_VERSION` does).
19. **Reading time said "1 min read" on every page built in Breakdance** (Hero and Dynamic Data
    field): `post_content` is empty there. It also counted shortcode attribute *names* as words.
    Now it reads the rendered tree the way Breakdance's Yoast/Rank Math integrations do — with a
    guard, because a page that shows its own reading time would otherwise recurse (removing the
    guard segfaulted PHP).
20. **A Breakdance-built page painted unstyled first**: its stylesheet was printed after all the
    content. Detected from the element tree now; the `evpx_load_assets` filter covers widgets
    living in Breakdance headers, footers and templates.
21. **Breakdance's global element rules beat the widgets' single-class rules.**
    `.breakdance h1–h6 { color; font-family; font-size }` and `.breakdance a` are specificity
    0,1,1: the hero title rendered dark-on-dark (~1.1:1), every heading fell back to a system
    sans, primary buttons turned blue. Every rule is now scoped under `.evpx-root` (0,2,0), link
    colours are restated for `:hover`, and every heading declares its own size and colour.
22. **Layout answered to the viewport, not the box.** A widget in a 605 px column got its desktop
    layout. Layout now uses container queries on `.evpx-root` (with an `@supports` fallback to the
    single-column layout), fluid type follows the box (`cqi`), grid tracks can shrink below their
    content, and cards tighten below 30rem. The new sweep then found two more overflows in a
    172 px column — the tab bar and a text-only section — both fixed.
23. **Base `font-size` and `text-align` were inherited from the host** (22 px in Twenty
    Twenty-Five; `text-align: left` forced by Breakdance, wrong for RTL). Now set on the root.

**Pass 5 — native elements, pictures, templates and Breakdance's own theme**

Found by driving the builder, adding pictures, putting elements in templates, and running under Breakdance's
Zero theme; each is guarded by a test.

24. **Widgets collapsed to zero width in a post's content under the Zero theme.** Breakdance shows a post's
    content in its Rich Text element, which sits in a Section that is a flex column with `align-items:
    flex-start`, so it is only as wide as its content — and a size container (`container-type: inline-size`)
    has no intrinsic width. Every widget measured `width: 0` with its text sticking out of it (`overflowProbe`:
    "100–148px outside its 100–100px widget"). The plugin's main scenario — the article published as a post on a
    Breakdance site — was broken. Two zero-specificity rules: `align-self: stretch` on the widget, and
    `:where(:has(> .evpx-root)) { width: stretch }` (with the prefixed spellings) on the box that holds it.
25. **Related Articles drew blank tiles beside real photographs** when only some articles had a featured image
    (0.2.0 had handled only "none has one"). Pictures now show only when every article has one.
26. **The hero's meta line left a separator dangling** at the end of a wrapped line on a phone
    ("Editorial •" / "September 2026"). The separators are now hairlines in the gap before each item; where a
    line starts they fall outside the box and are clipped.
27. **The stylesheet and GSAP were delivered twice** on a page that mixed shortcodes or blocks with a native
    element in a Breakdance template (WordPress and Breakdance each queued them). `evpx.js` and `motion.js`
    survive a second run, but GSAP's second copy replaced the first and left the page with a different
    ScrollTrigger state. One flag (`Loader::breakdanceDelivers()`) now decides who delivers; on a classic theme
    the already-queued scripts are withdrawn. A Breakdance-side condition could not do it: Breakdance caches the
    dependencies it collects per document, so a condition that depends on the request would be frozen in the cache.
28. **A widget in a footer or template got its stylesheet after the content**, even on a block theme, where
    the body renders before `<head>` and the loader could have known. It now asks whether an element has
    already rendered.
29. **The Element Studio save location used the PHP namespace `EVPX`, the same as the native elements** (a
    latent bug the native elements introduced). An element named "Hero" saved in Element Studio would have
    declared `EVPX\Hero` twice — "Cannot declare class EVPX\Hero" and a dead site; reproduced with a probe
    file. The namespace is now `EVPXStudio`, and the probe in `breakdance-real-check.sh` declares that class name
    on purpose.

30. **White type was unreadable over a bright picture.** The hero's scrim faded to nothing at the top, where the
    eyebrow and the first line of the title sit, so over an overcast-sky test picture the eyebrow measured
    1.5:1 and the title 1.9:1 (a phone: 1.2:1 and 1.6:1); the CTA's eyebrow and body, dimmed with `opacity`, sat at
    3.9–4.3:1. Hero and CTA now share one token, `--evpx-scrim: 0.6`, the hero's gradient starts there rather than
    at 0, and the eyebrows aren't dimmed. Measured worst case (brightest 5% of the picture behind each line, at 1280
    and 390 px): 5.0:1 or better for small type, 5.6:1 or better for large.

31. **Dynamic data showed as raw text in the builder canvas.** Choosing "Post Title" on a native element's title
    saved `[breakdance_dynamic field='post_title']` and the front end resolved it (Breakdance does that before
    an element renders), but the canvas showed the token itself: the builder's server-side render passes an
    element its raw properties. Breakdance's own Google Map resolves them in its `ssr.php` for exactly this
    reason. Native elements now do the same, only for builder renders.
32. **The EV Reading Time dynamic field was "Pro only".** Breakdance's `Field::proOnly()` defaults to `true`; the
    field carried a Pro badge and clicking it in the picker did nothing on a site without a Breakdance Pro
    licence. (Present since 0.1.0; the picker had never been opened.) It now says `false`.

33. **The empty row "Add" creates was rendered.** Clicking "Add EV FAQ Item" in the repeater put an empty
    accordion button on the page and an empty question in the FAQ's structured data (the same for a card or a
    factor with no title). A row is now rendered once it has its title (the question, for an FAQ item).

Two test-side findings from the same pass, fixed in the tests, not the plugin: the width assertion in `qa.mjs`
presumed a block theme (inside a Breakdance Section the Section sets the width, so it is skipped there), and
`media-qa.mjs` assumed a page scrolls its lazy pictures into view (Breakdance smooth-scrolls), so it now
decodes every picture instead of relying on scroll timing.

**Overclaims corrected in this pass.** `QA-REPORT.md` said mobile was tested at "emulated viewports
(320–1920 px)" and `ARCHITECTURE.md` listed nine tested breakpoints, but the suite only
screenshotted four widths and asserted nothing about overflow. It now sweeps ten widths and fails on
sideways overflow. `ARCHITECTURE.md` also showed the motion tokens on `:root` although they live on
`.evpx-root`.

**Overclaims corrected in 0.4.0.** The Element Studio bridge doc promised a "10-minute process" per widget to
get native controls, and ended by admitting it had never been done; it also said Element Studio's format was
undocumented and therefore native elements were out of reach. Both were wrong in the way that mattered: a
Breakdance element is a plain PHP class, and the plugin now ships them. `QA-REPORT.md` and `ARCHITECTURE.md`
had said "custom widgets appear in Breakdance" met by the Shortcode element alone; the docs now say what
each route is.

**Pass 6 — a design pass (0.5.0)**

Found by auditing the running pages, then by drawing the new design and measuring it. Each is guarded by a test that
was run against the broken state and seen to fail. 34–41 were in 0.4.0 as released; 42–44 were introduced by the
redesign itself and caught before it shipped.

34. **Two tokens were read and never defined.** FAQ questions had no vertical padding and the Flow heading touched
    its steps, because the stylesheet used `var(--evpx-space-5)` and `var(--evpx-space-10)`. An undefined custom
    property makes the declaration invalid at computed-value time and the property silently falls back, so no
    browser complains. `tests/css-check.mjs` now fails on any token that is read and not defined; against 0.4.0's
    stylesheet it fails with exactly those two.
35. **A stripe of page colour showed between two widgets** in a block theme: the theme's block gap puts a margin
    on every child of a post's content, and a widget is one. A widget now brings its own rhythm and takes none.
36. **The Hero and the FAQ sat off the grid.** Both were centred, narrow columns with left-aligned text, so their
    text began well to the right of every section under them. They now share one container; the interaction suite
    measures that the hero, the first section and the FAQ intro start on the same left edge.
37. **A mouse click drew the host theme's `:focus` outline** (Twenty Twenty-Five's black box around an accordion
    header or a tab after it was clicked). Cleared for pointer focus; a keyboard still gets the copper ring, and both
    are checked.
38. **The comparison's captions could wrap** ("Best for" over two lines) when the value beside it was long: the
    caption was allowed to shrink. It keeps its width now and the value wraps instead.
39. **The Hero's "Follow system" never followed the system.** The page-wide theme switch had a
    `prefers-color-scheme` rule; the Hero's own mode (split out in bug 2) did not, so "auto" was always dark.
    Checked with the browser's colour scheme set to each.
40. **"Media above text" put the media below the text.** The template prints the text first and the stacked layout
    had no order. The check measures the media's bottom edge against the text's top.
41. **The reading-progress bar was a 645 px column, not a bar across the window** (since 0.2.0, on any constrained
    block theme). The bar is `position: fixed` with `left: 0; right: 0`, but a constrained-layout theme caps and centres
    every child of the post content, so on Twenty Twenty-Five it sat 398 px in and 645 px wide. Nothing had measured
    its box, only that its fill grew. Found by accident, as a copper line running through a heading in a tall-element
    screenshot, where a fixed element is painted mid-page. `width: 100%; max-width: none` fixes it without `!important`
    (auto margins split no free space), and `qa.mjs` now asserts the track is as wide as the window.
42. **The new comparison plate overflowed a 320 px page.** The value column could not shrink below its longest word
    ("Highway corridors, fleet depots, retail"), so it pushed the plate past its box. It was the existing overflow
    sweep on the Breakdance page that showed it, not a check written for it; a value now wraps, and a row stacks its
    caption above its value in a narrow plate.
43. **The hero could flash for one frame, and a late GSAP replayed its entrance.** The new entrance is a timeline,
    and in GSAP a tween positioned later in a timeline does not apply its starting state at once, so on the frame
    where the hold-back was released the hero could paint, vanish and come back. Separately, if GSAP arrived after the
    stylesheet's own failsafe (1.6 s) had already revealed the hero, the entrance played again over a hero that was
    already there. The starting state is now set explicitly and the late case is guarded. Found by recording frames;
    `qa.mjs` now loads GSAP late twice, at 1.2 s and at 2.8 s, and the check fails without the fix.
44. **A FAQ row jumped when it opened and again when it closed.** The panel animated its height but not the padding
    under the answer, so the row gained that padding (24 px) in the frame the click landed and lost it in the frame the
    panel was hidden. The padding now travels with the height, and toggling again cancels a running animation instead of
    stacking a second one. `interaction-qa.mjs` reads the row's height synchronously after the click; with the padding
    left out it reads 113 px against 89 px.

A design-choice correction rather than a bug: the first draft of 0.5.0 used Newsreader for the headings, and the
project's own design notes list it as a face models reach for by default. The choice was re-run over twelve candidates
in the real widgets and moved to Spectral (rationale in `docs/ARCHITECTURE.md`, section 5.3). The font check in
`qa.mjs` now asks for Spectral.

Test-side, in the same pass: "scenario cards align in grid rows" contradicted the cards' deliberate stagger, so it
became "the outer two share a top edge and the middle steps down". A first full run of the matrix was polluted by
other work sharing the machine (the timing-sensitive checks in `qa.mjs` and `interaction-qa.mjs` fail when the CPU is
contended); it was thrown away and the matrix re-run on an idle machine. Run those two on an idle machine.

## Not verified

- **The reference the design was meant to beat.** The site the brief points at could not be opened from this
  environment (its host is blocked), so it was never seen. "More beautiful than the reference" is a judgement about
  the running pages against the brief's own terms (effects, hover states, type), not a side-by-side. Send
  screenshots of it, or allow the host, and the comparison can be made properly.
- **A real phone, and touch.** Mobile is emulated viewports; the touch checks run Chromium with touch and no hover
  media, which proves the decoration and hover states are withheld, not how a thumb feels on the page.
- **Right-to-left.** The comparison scale and the flow timeline are positioned with physical left and right.
- **The fonts on a slow connection.** They are self-hosted with `font-display: swap`; the swap itself, and how
  visible it is on a throttled network, was not measured.
- **Element Studio's GUI.** It was not operated. The plugin registers real save locations and Breakdance loads
  elements saved there (probe above), but creating or editing an element in the Element Studio interface itself
  has not been exercised.
- **Breakdance in other configurations.** Tested: Breakdance 2.8.3 as shipped, without a licence key, under
  Twenty Twenty-Five, Breakdance's Zero theme and a bare classic theme; widgets as native elements and in
  Shortcode elements; a footer and a Single Post template. Not tested: a licensed Breakdance (Pro features),
  Breakdance's header templates and popups (a header goes through the same code as a footer, but wasn't
  run), Oxygen mode (the elements declare they are available there), WooCommerce, other Breakdance versions,
  the builder's responsive breakpoints (the elements declare none), right-to-left sites.
- **Photography.** Pictures were tested with generated images — gradients with a frame at the edges, including
  a near-white sky as the worst case for white type — not photographs. Crop, `srcset`, alt text, the media
  library, empty rows and legibility are covered; how real photographs look is not. `docs/MEDIA-BRIEF.md` is
  the shot list.
- **Browsers other than Chromium.** Firefox, Safari and real phones were not available; mobile is emulated
  viewports. Container queries need Chrome 105 / Safari 16 / Firefox 110 or newer (older browsers get the
  single-column layout); the rule that keeps a widget from collapsing inside Breakdance's Rich Text element
  also needs `:has()` (Firefox 121); `@media (scripting: none)` needs Chrome 120 / Firefox 113 / Safari 17.
- **PHP versions.** PHP 8.2 and 8.4 ran the plugin; 7.4 (the declared minimum) was checked by syntax and
  PHPCompatibilityWP only, not executed.
- **cdnjs delivery itself.** GSAP was tested at the plugin's default version (3.12.5) but served locally; the
  CDN URL is unreachable from here.
- **Caching / minification / CDN plugins.**

## Reproducing

```
# Static, no WordPress needed
node tests/css-check.mjs
node tests/contrast-check.mjs

# Any WordPress page
bash tests/docker/setup.sh                     # or: EVPX_GSAP_DIR=… bash tests/docker/setup.sh
bash tests/docker/widget-render-check.sh
node tests/playwright/qa.mjs "$(cat tests/docker/.demo-url)"
node tests/playwright/interaction-qa.mjs "$(cat tests/docker/.demo-url)"          # run qa and this one on an idle machine

# A real Breakdance (you supply the licensed ZIP; it is never committed)
EVPX_BREAKDANCE_ZIP=/path/to/breakdance-2.8.3.zip EVPX_GSAP_DIR=… bash tests/docker/setup.sh
bash tests/docker/breakdance-real-check.sh
bash tests/docker/breakdance-page.sh
node tests/playwright/breakdance-qa.mjs "$(cat tests/docker/.breakdance-url)"          # Shortcode elements
node tests/playwright/breakdance-qa.mjs "$(cat tests/docker/.breakdance-native-url)"   # native elements, builder included
node tests/playwright/breakdance-qa.mjs "$(cat tests/docker/.breakdance-full-url)"     # full-width Sections, no padding
bash tests/docker/builder-save-check.sh                                                # dropdown, Add panel, Save, reopen
bash tests/docker/media-pages.sh
node tests/playwright/media-qa.mjs "$(cat tests/docker/.media-url)" "$(cat tests/docker/.media-native-url)" \
     "$(cat tests/docker/.media-mixed-url)" "$(cat tests/docker/.media-bright-url)"
bash tests/docker/template-check.sh                                                    # three themes
bash tests/docker/isolation-check.sh                                                   # two themes

composer install && composer lint
bash tests/build-zip.sh
```

The browser scripts and the `.sh` checks need Playwright (and, for axe, `axe-core`) resolvable from
`tests/playwright/`, and `PLAYWRIGHT_CHROMIUM_PATH` if Chromium isn't where Playwright looks. The template and
isolation checks install two extra themes (the bare classic one from this repository and Breakdance's own
Zero theme, copied out of the Breakdance plugin), and the isolation check switches this plugin off and on
again; each puts everything back when it ends, also when a check fails.
