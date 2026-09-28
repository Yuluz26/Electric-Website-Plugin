# QA report

## What was actually tested (not just reasoned about)

A real WordPress + MySQL environment was provisioned in Docker
(`tests/docker/docker-compose.yml`, WordPress 6.x on PHP 8.2 + MySQL 8),
the plugin was activated against it via WP-CLI, and a full demo article
(all 9 widgets, both top-level and nested) was published as a real page
and fetched over HTTP. A headless Chromium session (Playwright) then:

- Loaded the page at 1440, 1366, 768 and 390px viewports and captured
  full-page screenshots.
- Captured every `console.error` and uncaught `pageerror` during load.
- Clicked the FAQ accordion trigger and asserted `aria-expanded` and the
  panel's rendered visibility flipped correctly.
- Clicked the AC/DC comparison's DC tab and asserted `aria-selected` and
  each panel's computed `display` updated correctly.
- Inspected computed grid layout (`grid-template-columns`, each card's
  bounding box) to confirm the Scenario Cards grid actually lays out in
  clean, aligned rows.

## Results

- **Plugin activation/deactivation**: clean on both a fresh install and
  re-activation, with `WP_DEBUG` + `WP_DEBUG_LOG` + `WP_DEBUG_DISPLAY` all
  on. `debug.log` stayed empty through activation, deactivation, and a
  full render of every widget type (including empty/no-media edge cases).
- **PHP**: `php -l` clean across every file in `src/`, `templates/`, and
  the bootstrap file, on PHP 8.4.
- **JS**: `node --check` clean across `evpx.js`, `motion.js`,
  `block-editor.js`.
- **FAQ accordion & AC/DC comparison toggle**: verified interactive and
  correct via real click events, not just code review.
- **Responsive**: visually verified at 1440/1366/768/390px — no
  horizontal overflow, no clipped headings, hero and CTA scale down
  intentionally rather than merely shrinking.
- **Console/page errors**: zero JavaScript exceptions. The only
  `console.error` entries present are this *sandbox's own* network policy
  blocking outbound requests to cdnjs.cloudflare.com / fonts.googleapis.com
  — not a defect in the plugin (see `docs/TROUBLESHOOTING.md`). This also
  incidentally proved the graceful-degradation design: with GSAP and web
  fonts both unavailable, the page still rendered fully styled (system
  font fallback) and fully interactive (vanilla-JS baseline).

## Bugs found and fixed during this QA pass

1. **Nested child shortcodes silently not rendering.** `evpx_scenario_card`
   and `evpx_faq_item` were registered with hyphenated tag names
   (`evpx_scenario-card`) because `shortcodeTag()` didn't normalize a
   hyphenated slug, while every usage example used underscores. Fixed in
   `Element::shortcodeTag()`.
2. **Hero "dark" mode rendering as unreadable white-on-white.**
   `data-evpx-theme="dark"` on the Hero element collided with the
   site-wide dark-mode *token* override selector
   (`.evpx-root[data-evpx-theme='dark']`), which redefines `--evpx-ink` to
   a light value — flipping the hero's intended near-black background to
   near-white. Renamed the Hero's own attribute to `data-evpx-hero-mode`
   so the two concerns (page-wide theme tokens vs. one component's visual
   variant) can no longer collide.
3. **Scenario Cards grid rendering as a scrambled, uneven waterfall.**
   WordPress's `wpautop` filter runs before `do_shortcode` on
   `the_content` and injected `<br>`/`<p>` tags between the nested
   `[evpx_scenario_card]` lines; those stray tags became extra grid
   children and broke row alignment. Fixed narrowly in
   `Element::stripAutopArtifacts()`, scoped to only the content a
   container element itself captured — not a site-wide filter change.
4. **Every top-level section rendering at ~645px instead of full width**
   on the default WordPress "Twenty Twenty-Five" block theme, which
   constrains all direct children of the content area to its default
   content width. Fixed by adding WordPress's own standard `alignfull`
   class to every top-level widget wrapper — the theme's built-in escape
   hatch, not a custom override.

## What is explicitly NOT verified

Breakdance itself is a paid, licensed product and was not available in
this build environment, so the following are unverified by direct
testing and should be checked on a real Breakdance install:

- Rendering of `[evpx_*]` shortcodes through Breakdance's own Shortcode
  element specifically (vs. the plain WordPress template tested here).
- Element Studio's actual save-location registration
  (`registerSaveLocation()` call) — the code matches Breakdance's own
  official boilerplate exactly, but wasn't executed against a real
  Breakdance runtime.
- Breakdance builder-canvas behavior (motion suppression while editing) —
  the iframe-detection logic in `motionAllowed()` is generic and
  well-founded (every page builder's canvas is an iframe), but wasn't
  observed against Breakdance's actual canvas.
- Interaction with a caching/minification plugin, or with Breakdance Zero
  theme specifically.

None of these gaps are expected to be a problem given the integration
approach (shortcode/block via documented WordPress APIs, no Breakdance
internals touched), but "expected to work" and "verified working" are
different claims — flagging the difference honestly rather than either
inflating this report or leaving it unsaid.
