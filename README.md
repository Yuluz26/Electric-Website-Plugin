# EV Charging Experience

A WordPress plugin that adds a premium, neumorphic, editorial set of
EV-charging article components — built to extend Breakdance without ever
overriding it, and to work as plain WordPress shortcodes/blocks with no
page builder at all.

Built from the staged prompt pack in `docs/prompt-pack/` (PRD → UI/UX →
SOP → MVP → Production → Polish). Full architecture, design tokens and
element inventory: `docs/ARCHITECTURE.md`.

## What's in the box

Nine widgets — **EV Article Hero**, **EV Section**, **EV AC/DC Comparison**
(signature component), **EV Scenario Cards**, **EV Technical Flow**, **EV Decision
Factors**, **EV FAQ**, **EV Related Articles**, **EV CTA** — plus the three item elements
that nest inside the container widgets. Each is a shortcode *and* a Gutenberg block, same
renderer, same output. Full control reference: `docs/WIDGETS.md`.

A complete demo article — "Choosing AC or DC Charging for Your Site," original copy
grounded in independently-verified AC/DC charging facts, not copied from any reference — is
in `docs/demo-article.txt` and is the end-to-end QA fixture (`docs/QA-REPORT.md`). It ships
without photography; `docs/MEDIA-BRIEF.md` is the shot list.

## Quick start

```
wp plugin activate ev-charging-experience
```

Then either drop `[evpx_hero]` (etc.) into any post/page content, or
search "EV" in the block inserter. Full setup, including the Breakdance
Element Studio bridge for native drag-in controls: `docs/INSTALLATION.md`.

## Documentation

| Doc | Covers |
|---|---|
| `docs/ARCHITECTURE.md` | Requirements, architecture, design tokens, risks |
| `docs/INSTALLATION.md` | Install, Breakdance setup, compatibility |
| `docs/WIDGETS.md` | Every widget's controls |
| `docs/BREAKDANCE-ELEMENT-STUDIO-BRIDGE.md` | Building native Breakdance elements |
| `docs/TROUBLESHOOTING.md` | Common issues |
| `docs/QA-REPORT.md` | What was tested, bugs found and fixed, what's unverified |
| `docs/MEDIA-BRIEF.md` | Photography shot list, sourcing and alt-text rules |
| `docs/PACKAGING.md` | Building a distributable ZIP |
| `CHANGELOG.md` | Version history |

## Development

No build step, no runtime dependencies. PHP is autoloaded (PSR-4, `EVPX\` → `src/`) by a
hand-rolled autoloader, so a plain ZIP upload always works. CSS and JS are hand-authored.

```
bash tests/docker/setup.sh                      # WordPress + MySQL in Docker, plugin active, demo imported
node tests/playwright/qa.mjs "$(cat tests/docker/.demo-url)"   # 21 browser checks: axe-core, a 10-width overflow sweep, motion, no-JS
node tests/contrast-check.mjs                   # WCAG pairings, read from the real tokens
composer install && composer lint               # WordPress coding standards (see phpcs.xml.dist)
bash tests/build-zip.sh                         # dist/ev-charging-experience.zip

# Against a real Breakdance (you supply the licensed ZIP; it is never committed):
EVPX_BREAKDANCE_ZIP=/path/to/breakdance.zip bash tests/docker/setup.sh
bash tests/docker/breakdance-real-check.sh      # integration: save locations, Dynamic Data, reading time
bash tests/docker/breakdance-page.sh            # a page designed in Breakdance from the demo article
node tests/playwright/breakdance-qa.mjs "$(cat tests/docker/.breakdance-url)"   # 16 checks, front end + builder
```

The browser scripts need `playwright` (and optionally `axe-core`) installed in
`tests/playwright/`; if your network blocks cdnjs, run `setup.sh` with `EVPX_GSAP_DIR` so GSAP
is served locally — the header of each script explains the details.

## Non-negotiables this plugin follows

Namespaced everywhere (`EVPX` in PHP, `.evpx-*` in CSS, `evpx_*`/`evpx/*`
for shortcodes/blocks). No bare-element or global CSS selectors, and every rule scoped
under `.evpx-root` so a host's `h2`/`a` rules can't restyle a widget. Layout follows the widget's
own box (container queries), not the viewport. No
Breakdance core files, templates, or global styles touched. Every
Breakdance API call guarded with `function_exists()`/`class_exists()` —
the plugin never fatals with Breakdance absent. Assets load only on pages
that actually use an EV element. Fonts are bundled, so a page view contacts no font host.
Motion is progressive enhancement — every widget is complete and interactive with GSAP
absent, with JavaScript off, and under `prefers-reduced-motion`.
