# Installation & setup

## Requirements

- WordPress 6.0+
- PHP 7.4+ (developed and QA'd against 8.2/8.4)
- Breakdance is **optional** — every widget works as a plain WordPress
  shortcode/block with no page builder at all. Breakdance unlocks the
  Element Studio bridge (see `docs/BREAKDANCE-ELEMENT-STUDIO-BRIDGE.md`).

## Install

1. Zip the plugin folder (see `docs/PACKAGING.md` for exactly what to
   include) or use the repository directly as
   `wp-content/plugins/ev-charging-experience/`.
2. WordPress Admin → Plugins → **Activate** "EV Charging Experience".
   Activation only records the installed version in `wp_options` — it
   never touches existing posts, pages, Breakdance data, or theme files.
3. That's it. No database migration, no setup wizard, no required
   configuration.

## Using it without Breakdance

Insert any widget as a shortcode directly in the block editor, classic
editor, or any `post_content`:

```
[evpx_hero title="Choosing AC or DC Charging for Your Site"]
```

Or search for "EV" in the block inserter — all 9 blocks live under the
**EV Charging Experience** category with full Inspector Controls (organized
into Content / Media / Layout / Visual / Motion / Responsive / Advanced
panels) and a live preview.

## Using it with Breakdance

Drag a **Shortcode** element into the canvas, paste e.g. `[evpx_comparison
ac_power_range="7–22 kW" ...]`, done — this is fully supported and is the
path this plugin was QA'd against (Breakdance itself is a paid/licensed
product not available in this build's test environment; see
`docs/QA-REPORT.md` for exactly what was and wasn't verified against a
real Breakdance install).

For fully native, visually-controlled elements inside Element Studio, see
`docs/BREAKDANCE-ELEMENT-STUDIO-BRIDGE.md`.

## Compatibility notes

- **Theme**: works with classic and block (FSE) themes. Block themes that
  constrain content width (e.g. Twenty Twenty-Five) are handled — every
  top-level widget carries WordPress's own `alignfull` class so it escapes
  the theme's content-width wrapper instead of getting squeezed.
- **SEO plugins**: the FAQ widget's JSON-LD `FAQPage` schema can be
  disabled per-instance (`schema_output="false"`) if Yoast/RankMath/etc.
  already emit FAQ schema on the same page.
- **Caching/minification/CDN**: all assets are registered through
  WordPress's standard `wp_enqueue_style`/`wp_enqueue_script`, so
  concatenation/minification plugins handle them normally. GSAP and the
  fonts are loaded from cdnjs.cloudflare.com / fonts.googleapis.com by
  default — self-host them instead (swap the URLs in
  `src/Assets/Loader.php`) if your site's CSP or offline requirements need
  that.
- **Other plugins/builders**: nothing is hooked into anything global —
  no `.bde-*` selectors, no bare element selectors (`h1`, `img`,
  `.container`…), no core file overrides. Deactivating the plugin leaves
  existing content untouched (shortcodes simply stop expanding; the raw
  `[evpx_...]` text is not deleted from the database).

## Uninstalling

Deactivate normally from Plugins. There is currently no data to clean up —
the only thing the plugin writes to the database is the `evpx_version`
option, which is harmless to leave behind.
