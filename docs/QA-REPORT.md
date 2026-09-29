# QA report — v0.3.0

Everything below was run, not reasoned about. Every command is in the repo, so it can be
re-run: see "Reproducing" at the end.

## Readiness

**Release candidate — not yet "production ready".** Since 0.2.0 the plugin has been run against a
real, licensed **Breakdance 2.8.3** — on the front end and inside its builder — and that found
bugs no stub could show (bugs 16–23 below). All of it is fixed and now guarded by tests. What still
stands between this and a production label needs things this environment doesn't have: real
photography, browsers other than Chromium, and a site running your caching stack. Details under
"Not verified".

## What was tested

| Layer | How | Result |
|---|---|---|
| PHP syntax | `php -l` on PHP 8.4 (plugin header promises 7.4+) | clean |
| WordPress coding standards | `phpcs` — WordPress-Core + WordPress-Extra + PHPCompatibilityWP (7.4+), security/escaping/i18n/prefix sniffs on | 0 errors, 0 warnings, with the formatting exclusions listed and justified in `phpcs.xml.dist` |
| JS syntax | `node --check` | clean |
| Colour contrast | `tests/contrast-check.mjs` reads the real tokens out of `evpx.css` and asserts 16 pairings, light and dark | all pass |
| Activation / deactivation | WP-CLI, `WP_DEBUG` + `WP_DEBUG_LOG` + `WP_DEBUG_DISPLAY` | clean; `debug.log` empty across activation, rendering of every widget and both browser suites (the only entry ever seen was core's own wordpress.org update check failing on the sandbox network) |
| **Real Breakdance 2.8.3 — integration** | `tests/docker/breakdance-real-check.sh`: 11 assertions against the running plugin (save locations reach Breakdance, Dynamic Data field registered and listed, reading time on plain and Breakdance-built pages, no recursion) plus a behavioural probe — a throwaway element file dropped in the plugin's Element Studio folder must be loaded by a *fresh* PHP process | 12/12 |
| **Real Breakdance 2.8.3 — front end and builder** | `tests/playwright/breakdance-qa.mjs`: a page designed in Breakdance (one Section + Shortcode element per widget), 16 checks in Chromium — assets, typography, hover colours, reading time, interactions, motion, overflow at six widths, axe, then the builder itself: server-side renders, canvas, console | 16/16 |
| Browser suite, any WordPress page | `tests/playwright/qa.mjs`, 21 checks, Chromium, WordPress 6.x on the Twenty Twenty-Five block theme, with Breakdance also active | 21/21 |
| Breakdance contract stub | `tests/docker/breakdance-stub.php` + `breakdance-contract-check.php` — kept for CI without a licence; the real check above is authoritative (the stub fired `breakdance_loaded` *after* this plugin booted, the opposite of the real order, and so hid bug 16) | 11/11 |
| The shipped artifact | the built ZIP installed as a separate plugin directory and both browser suites run against *that* | see "Reproducing" |
| Motion | real GSAP **3.12.5** (the version the plugin loads by default), served locally because this sandbox blocks cdnjs | verified below |

### The 21 checks in `qa.mjs`

No uncaught exceptions · no third-party font requests · **no horizontal overflow at 320, 375, 390,
430, 768, 1024, 1280, 1366, 1440 and 1920 px** · no leftover `[evpx_*]` text · bundled fonts
actually load · decision factors and related articles render · FAQ accordion toggles · AC/DC tabs
switch · tabs keyboard pattern (Home / Arrow keys, `aria-controls` ↔ `aria-labelledby`) · scenario
cards align in grid rows · sections aren't squeezed by the theme's content width · reading-progress
bar tracks scroll · every scroll reveal settles fully visible · card `:hover` lift survives its
reveal · `prefers-reduced-motion` (nothing hidden, zero scroll triggers) · JavaScript disabled (FAQ
answers and both AC/DC panels readable, no dead tabs, hero visible) · builder-canvas simulation
(page inside an iframe: motion off, content visible, zero scroll triggers) · hero never flashes
visible → hidden → visible on a slow GSAP load · heading hierarchy (≤ 1 `h1`, no skipped levels
inside widgets) · `axe-core` WCAG 2.0/2.1/2.2 A + AA + best-practice over every widget at 1280 px
and 390 px: **zero violations**.

### The 16 checks in `breakdance-qa.mjs`

Front end: every widget in the Breakdance tree renders · stylesheet is printed in `<head>` · no
heading falls back to a host typeface · hero title stays light on the dark hero · primary buttons
keep white text, also on hover · hero reading time comes from the element tree · FAQ opens · AC/DC
tabs switch · motion runs and every reveal settles · no widget overflows its box at 320, 390, 768,
1024, 1440, 1920 px · axe-core zero violations at 1280 and 390 px · no uncaught exceptions.
Builder: every widget renders through Breakdance's server-side render (all 200, no shortcode text) ·
that render is static (`data-evpx-animate="0"`, no progress bar) · the canvas shows every widget,
the hero in its own typeface, motion off, no scroll triggers · no console or page errors from this
plugin.

Each check was written after the bug it guards was seen for real. They were also confirmed to
*fail* on the broken state: against the pre-fix stylesheet six of the sixteen fail (system-font
headings, dark hero title `rgb(17,24,39)`, blue button text `rgb(59,130,246)`, sideways scroll,
contrast, builder typeface); against the pre-fix builder detection the two builder checks fail
(`data-evpx-animate="1"`, builder flag empty); and with the recursion guard removed the
self-referencing page segfaults PHP.

### Weight

Front-end critical path (stylesheet + `evpx.js` + `motion.js`): about **11 KB gzipped** (the
stylesheet grew by ~0.9 KB when every rule was scoped under `.evpx-root`). Bundled fonts: 96 KB
(woff2 is already compressed) with `font-display: swap`. GSAP + ScrollTrigger come from the CDN and
only on pages with an EV element. No render-blocking third-party request is made by the plugin
itself. (Lighthouse / field performance were not measured.)

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

**Overclaims corrected in this pass.** `QA-REPORT.md` said mobile was tested at "emulated viewports
(320–1920 px)" and `ARCHITECTURE.md` listed nine tested breakpoints, but the suite only
screenshotted four widths and asserted nothing about overflow. It now sweeps ten widths and fails on
sideways overflow. `ARCHITECTURE.md` also showed the motion tokens on `:root` although they live on
`.evpx-root`.

## Not verified

- **Element Studio's GUI.** It was not operated. The plugin registers a real save location and
  Breakdance loads elements saved there (probe above), but creating or editing an element in the
  Element Studio interface itself has not been exercised.
- **Breakdance in other configurations.** Tested: Breakdance 2.8.3 as shipped, unlicensed, on the
  Twenty Twenty-Five block theme, widgets in Shortcode elements. Not tested: the Breakdance Zero
  theme, Breakdance header/footer/single-post *templates* holding widgets, Pro-only features, other
  Breakdance versions.
- **Photography.** The demo has none (no image host was reachable from the sandbox). Empty states
  are tested; real photographs are not. `docs/MEDIA-BRIEF.md` is the shot list.
- **Browsers other than Chromium.** Firefox, Safari and real phones were not available; mobile is
  emulated viewports. Container queries need Chrome 105 / Safari 16 / Firefox 110 or newer (older
  browsers get the single-column layout); `@media (scripting: none)` needs Chrome 120 / Firefox 113 /
  Safari 17.
- **cdnjs delivery itself.** GSAP was tested at the plugin's default version (3.12.5) but served
  locally; the CDN URL is unreachable from here.
- **Caching / minification / CDN plugins.**

## Reproducing

```
# Any WordPress page
bash tests/docker/setup.sh                     # or: EVPX_GSAP_DIR=… bash tests/docker/setup.sh
node tests/playwright/qa.mjs "$(cat tests/docker/.demo-url)"

# A real Breakdance (you supply the licensed ZIP; it is never committed)
EVPX_BREAKDANCE_ZIP=/path/to/breakdance-2.8.3.zip EVPX_GSAP_DIR=… bash tests/docker/setup.sh
bash tests/docker/breakdance-real-check.sh
bash tests/docker/breakdance-page.sh
node tests/playwright/breakdance-qa.mjs "$(cat tests/docker/.breakdance-url)"

node tests/contrast-check.mjs
composer install && composer lint
bash tests/build-zip.sh
```
