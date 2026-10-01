# EV Charging Experience

A WordPress plugin that adds a premium, neumorphic, editorial set of
EV-charging components for articles and for whole sites — native elements in Breakdance's Add panel, built to extend
Breakdance without ever overriding it, and plain WordPress shortcodes/blocks that work with no
page builder at all.

Built from the staged prompt pack in `docs/prompt-pack/` (PRD → UI/UX →
SOP → MVP → Production → Polish). Full architecture, design tokens and
element inventory: `docs/ARCHITECTURE.md`.

## What's in the box

Ten widgets — **EV Article Hero**, **EV Section**, **EV AC/DC Comparison**
(signature component), **EV Charging Explorer** (the one interactive: how long a car stays, which charger,
what reaches the battery), **EV Scenario Cards**, **EV Technical Flow**, **EV Decision
Factors**, **EV FAQ**, **EV Related Articles**, **EV CTA** — plus the three item elements
that nest inside the container widgets. Each is a shortcode, a Gutenberg block *and* a native
Breakdance element (category **EV Charging**), same renderer, same output. Full control
reference: `docs/WIDGETS.md`.

Ten more build the pages of a site: **EV Page Hero** (a full-width, cinematic opening: a photograph, or one of five
layered SVG scenes that move with the pointer and the scroll, with a canvas of weather over them), **EV Site Header**
(with a search that opens on Ctrl/Cmd+K and lists results as you type), **EV Search** (the results page), **EV Site
Footer**, **EV Stats** (counters and ring meters), **EV Services** (a strip of panels, one open at a time),
**EV Process** (a rail that fills as you read down it), **EV Projects** (a rail you can drag), **EV Quotes**, and
**EV Contact** (a form with a signed token, a trap field and a rate limit). Every one has a scene or a drawing until
it has a photograph, so a page has a look before the pictures exist.

The look is dark instrument panels and light editorial pages, with copper drawn as light (a thin line and a
soft halo, never a fill) and neumorphic depth used where something is pressed or raised. Two things carry it:
one icon family (Phosphor Regular, MIT, inline SVG) and a small set of built-in drawings (a car on a charger,
a charge curve, an AC wall box, a DC cabinet, the path from grid to charger, a wave and a level) that stand in
for photographs until you have them. Both are drawn in the page, need no upload, and follow the design tokens.
`docs/ARCHITECTURE.md` §5 has the design language; `docs/MEDIA-BRIEF.md` is what to shoot when you do want photographs.

A complete demo article — "Choosing AC or DC Charging for Your Site," original copy
grounded in independently-verified AC/DC charging facts, not copied from any reference — is
in `content/demo-article.txt` and is the end-to-end QA fixture (`docs/QA-REPORT.md`). It ships
without photography; `docs/MEDIA-BRIEF.md` is the shot list.

**Activating the plugin makes it for you:** draft examples, so a new install has something to open instead
of an empty Pages list. One is a post with the article as shortcodes; one, made once Breakdance is
active, is a page with the same article as native elements in full-width Sections, ready for the builder; and six
are the pages of a site (Home, About, Services, Projects, Contact, Search), full width in any theme on the plugin's own
**EV full-width page** template. Nothing is public until you publish (the notice has a button for it), and nothing that
already exists is touched (`docs/INSTALLATION.md`).

## Quick start

```
wp plugin activate ev-charging-experience
```

Then open the examples it adds under Posts and Pages (drafts; the notice after activation links
to them, and has a button that publishes the six site pages). Or search "EV" in Breakdance's Add panel, drop `[evpx_hero]` (etc.) into any post/page
content, or search "EV" in the block inserter. Full setup: `docs/INSTALLATION.md`; everything about
Breakdance: `docs/BREAKDANCE.md`.

## Documentation

| Doc | Covers |
|---|---|
| `docs/ARCHITECTURE.md` | Requirements, architecture, design tokens, risks |
| `docs/INSTALLATION.md` | Install, Breakdance setup, compatibility |
| `docs/WIDGETS.md` | Every widget's controls |
| `docs/BREAKDANCE.md` | The native elements, the Shortcode element, Element Studio, templates, how it's built |
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
node tests/playwright/qa.mjs "$(cat tests/docker/.demo-url)"   # browser checks: axe-core, a 10-width overflow sweep, motion, no-JS
node tests/playwright/interaction-qa.mjs "$(cat tests/docker/.demo-url)"   # hover, focus, motion on/off, the comparison thumb and range bar
node tests/playwright/explorer-qa.mjs "$(cat tests/docker/.demo-url)"      # the explorer: page before/after the script, the model against the PHP one, controls, six widths
node tests/playwright/a11y-qa.mjs "$(cat tests/docker/.demo-url)"          # focus rings on every surface in both schemes, targets, type size, measure, icons, forced colours
node tests/playwright/layout-qa.mjs "$(cat tests/docker/.demo-url)"        # composition: the hero's gauge reads, drawings paint and keep their units, the floor reaches the edges, a related row ends square
bash tests/docker/widget-render-check.sh        # what the widgets print (the power scale, the key figure, captions, icons, drawings, the explorer, escaping)
node tests/contrast-check.mjs                   # WCAG pairings, read from the real tokens
node tests/css-check.mjs                        # undefined tokens, selector scope, !important, ungated animation
composer install && composer lint               # WordPress coding standards (see phpcs.xml.dist)
bash tests/build-zip.sh                         # dist/ev-charging-experience.zip

# Against a real Breakdance (you supply the licensed ZIP; it is never committed):
EVPX_BREAKDANCE_ZIP=/path/to/breakdance.zip bash tests/docker/setup.sh
bash tests/docker/breakdance-real-check.sh      # integration: native elements, Element Studio, Dynamic Data, reading time
bash tests/docker/breakdance-page.sh            # three pages designed in Breakdance from the demo article (Shortcode / native / native in full-width Sections)
node tests/playwright/breakdance-qa.mjs "$(cat tests/docker/.breakdance-native-url)"   # front end + the builder itself
bash tests/docker/builder-save-check.sh         # the builder round-trip: dropdown, Add panel, Save, front end
bash tests/docker/media-pages.sh                # generated pictures, then: node tests/playwright/media-qa.mjs …
bash tests/docker/template-check.sh             # footers and templates under a block, the Zero and a classic theme
bash tests/docker/isolation-check.sh            # unrelated pages are pixel-identical with the plugin on and off
bash tests/docker/example-pages-check.sh        # the examples made on activation: what, once, as drafts, Breakdance arriving later
bash tests/docker/site-render-check.sh          # what the site widgets print: hero, header, search, footer, stats, services, process, projects, quotes, contact, the token
bash tests/docker/site-pages-check.sh           # the six site pages, made both ways and driven in a browser (320 to 1920px, axe, every control with pointer and keyboard)
bash tests/docker/contact-form-check.sh         # the contact form over HTTP: too fast, trap field, forged recipient, off-site return, the rate limit, what is mailed
```

The browser scripts need `playwright` (and optionally `axe-core`) installed in
`tests/playwright/`; if your network blocks cdnjs, run `setup.sh` with `EVPX_GSAP_DIR` so GSAP
is served locally — the header of each script explains the details.

## Non-negotiables this plugin follows

Icons and drawings are inline SVG in the page: no icon font, no sprite, no request, nothing to fail to load
(the icon outlines are Phosphor's, MIT, in `assets/icons/LICENSE.txt`).
Namespaced everywhere (`EVPX` in PHP, `.evpx-*` in CSS, `evpx_*`/`evpx/*`
for shortcodes/blocks, `EVPXStudio` for Element Studio). No bare-element or global CSS selectors (one
zero-specificity rule asks the box that holds a widget to fill its container; see the stylesheet header),
and every rule scoped under `.evpx-root` so a host's `h2`/`a` rules can't restyle a widget. Layout follows
the widget's own box (container queries), not the viewport. No
Breakdance core files, templates, or global styles touched. Every
Breakdance API call guarded with `function_exists()`/`class_exists()` —
the plugin never fatals with Breakdance absent. Assets load only on pages
that actually use an EV element. Fonts are bundled, so a page view contacts no font host.
Motion is progressive enhancement — every widget is complete and interactive with GSAP
absent, with JavaScript off, and under `prefers-reduced-motion`.
