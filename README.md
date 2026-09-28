# EV Charging Experience

A WordPress plugin that adds a premium, neumorphic, editorial set of
EV-charging article components — built to extend Breakdance without ever
overriding it, and to work as plain WordPress shortcodes/blocks with no
page builder at all.

Built from the staged prompt pack in `docs/prompt-pack/` (PRD → UI/UX →
SOP → MVP → Production → Polish). Full architecture, design tokens and
element inventory: `docs/ARCHITECTURE.md`.

## What's in the box

9 elements — each a shortcode *and* a Gutenberg block, same renderer, same
output: **EV Article Hero**, **EV Section**, **EV AC/DC Comparison**
(signature component), **EV Scenario Cards** + **EV Scenario Card**, **EV
Technical Flow**, **EV FAQ** + **EV FAQ Item**, **EV CTA**. Full control
reference: `docs/WIDGETS.md`.

A complete demo article — "Choosing AC or DC Charging for Your Site,"
original copy grounded in independently-verified AC/DC charging facts, not
copied from any reference — is assembled from these 9 widgets and was used
as the end-to-end QA fixture (`docs/QA-REPORT.md`).

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
| `docs/PACKAGING.md` | Building a distributable ZIP |
| `CHANGELOG.md` | Version history |

## Development

No build step. PHP is autoloaded (PSR-4, `EVPX\` → `src/`) with a
hand-rolled autoloader — no `composer install` required to run the plugin.
CSS/JS are hand-authored, no bundler.

```
php -l ev-charging-experience.php src/**/*.php templates/*.php   # syntax check
docker compose -f tests/docker/docker-compose.yml up -d          # local WP QA env
```

## Non-negotiables this plugin follows

Namespaced everywhere (`EVPX` in PHP, `.evpx-*` in CSS, `evpx_*`/`evpx/*`
for shortcodes/blocks). No bare-element or global CSS selectors. No
Breakdance core files, templates, or global styles touched. Every
Breakdance API call guarded with `function_exists()`/`class_exists()` —
the plugin never fatals with Breakdance absent. Assets load only on pages
that actually use an EV element. Motion is progressive enhancement — every
widget is fully functional with GSAP absent or failed to load.
