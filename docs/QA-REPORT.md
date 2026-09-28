# QA report — v0.2.0

Everything below was run, not reasoned about. Every command is in the repo, so it can be
re-run: see "Reproducing" at the end.

## Readiness

**Release candidate — not yet "production ready".** The code, accessibility, motion, security
and packaging checks pass. Three things stand between this and a production label, and they
need things this build environment didn't have: a real Breakdance install, real photography,
and browsers other than Chromium. Details under "Not verified".

## What was tested

| Layer | How | Result |
|---|---|---|
| PHP syntax | `php -l` on PHP 8.4 (plugin header promises 7.4+) | clean |
| WordPress coding standards | `phpcs` — WordPress-Core + WordPress-Extra + PHPCompatibilityWP (7.4+), security/escaping/i18n/prefix sniffs on | 0 errors, 0 warnings, with the formatting exclusions listed and justified in `phpcs.xml.dist` |
| JS syntax | `node --check` | clean |
| Colour contrast | `tests/contrast-check.mjs` reads the real tokens out of `evpx.css` and asserts 16 pairings, light and dark | all pass |
| Activation / deactivation | WP-CLI, `WP_DEBUG` + `WP_DEBUG_LOG` + `WP_DEBUG_DISPLAY` | clean; `debug.log` empty across activation, rendering of every widget, and the full browser suite (the only entry ever seen was core's own wordpress.org update check failing on the sandbox network) |
| Breakdance integration contract | `tests/docker/breakdance-stub.php` + `breakdance-contract-check.php`, 11 assertions | pass — see the caveat under "Not verified" |
| Browser suite | `tests/playwright/qa.mjs`, 20 checks, Chromium, against WordPress 6.x on the Twenty Twenty-Five block theme | 20/20 |
| The shipped artifact | the built ZIP installed as a separate plugin directory and the whole browser suite run against *that* | 20/20 |
| Motion | real GSAP **3.12.5** (the version the plugin loads by default), served locally because this sandbox blocks cdnjs | verified below |

### The 20 browser checks

No uncaught exceptions · no third-party font requests · no leftover `[evpx_*]` text · bundled fonts
actually load · decision factors and related articles render · FAQ accordion toggles · AC/DC tabs
switch · tabs keyboard pattern (Home / Arrow keys, `aria-controls` ↔ `aria-labelledby`) · scenario
cards align in grid rows · sections aren't squeezed by the theme's content width · reading-progress
bar tracks scroll · every scroll reveal settles fully visible · card `:hover` lift survives its
reveal · `prefers-reduced-motion` (nothing hidden, zero scroll triggers) · JavaScript disabled (FAQ
answers and both AC/DC panels readable, no dead tabs, hero visible) · builder-canvas simulation
(page inside an iframe: motion off, content visible, zero scroll triggers) · hero never flashes
visible → hidden → visible on a slow GSAP load · heading hierarchy (≤ 1 `h1`, no skipped levels
inside widgets) · `axe-core` WCAG 2.0/2.1/2.2 A + AA + best-practice over every widget: **zero
violations**.

Each check was written after the bug it guards was seen for real; the heading and JS-disabled
checks were also confirmed to *fail* on the broken state (two `h1`s; the pre-fix CSS).

### Weight

Front-end critical path (stylesheet + `evpx.js` + `motion.js`): **10.6 KB gzipped**. Bundled fonts:
96 KB (woff2 is already compressed) with `font-display: swap`. GSAP + ScrollTrigger come from the
CDN and only on pages with an EV element. No render-blocking third-party request is made by the
plugin itself. (Lighthouse / field performance were not measured.)

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

## Not verified

- **A real Breakdance install.** The contract check runs the plugin's Breakdance code against a stub
  built from the signatures in Breakdance's own public repos. That proves the code runs without
  fatals, calls the APIs with the documented argument shapes, and satisfies the documented
  `StringField` base-class contract. It cannot prove the real runtime matches the stub — e.g. the
  exact string `getDirectoryPathRelativeToPluginFolder()` returns is an assumption. Still to check
  on a licensed site: the `[evpx_*]` shortcodes inside Breakdance's Shortcode element, the
  "EV Charging Elements" save location appearing in Element Studio, and motion being suppressed in
  the actual builder canvas (the iframe detection is verified generically, not against Breakdance).
- **Photography.** The demo has none (no image host was reachable from the sandbox). Empty states
  are tested; real images are not. `docs/MEDIA-BRIEF.md` is the shot list.
- **Browsers other than Chromium.** Firefox, Safari, and real mobile devices were not available;
  mobile is emulated viewports (320–1920 px). `@media (scripting: none)` needs Chrome 120 / Firefox
  113 / Safari 17 or newer; older browsers simply ignore it.
- **cdnjs delivery itself.** GSAP was tested at the plugin's default version (3.12.5) but served
  locally; the CDN URL is unreachable from here.
- **Caching / minification / CDN plugins**, and the Breakdance Zero theme specifically.

## Reproducing

```
bash tests/docker/setup.sh                     # or: EVPX_GSAP_DIR=… EVPX_BREAKDANCE_STUB=1 bash tests/docker/setup.sh
node tests/playwright/qa.mjs "$(cat tests/docker/.demo-url)"
node tests/contrast-check.mjs
composer install && composer lint
bash tests/build-zip.sh
```
