# Installation & setup

## Requirements

- WordPress 6.0+
- PHP 7.4+ (developed and QA'd against 8.2/8.4)
- Breakdance is **optional** — every widget works as a plain WordPress
  shortcode/block with no page builder at all. With Breakdance active, the ten widgets also appear
  in its Add panel as native elements (see `docs/BREAKDANCE.md`). Tested with Breakdance 2.8.3.

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

Activate the plugin next to Breakdance, open a page in the builder, click **Add** and search "EV": the ten
elements are in the **EV Charging** category. Drop one into a Section and edit it in the panel: text, pictures,
layout, visual variants, motion, and an **Items** repeater for the FAQ, the scenario cards and the decision
factors. Text fields take Breakdance's dynamic-data button, including the plugin's own **EV Reading Time**.
`docs/BREAKDANCE.md` covers all of it, and the other two routes (Breakdance's own Shortcode element with
`[evpx_comparison …]`, and Element Studio).

This has been run against a real Breakdance 2.8.3 on the front end and inside the builder (Add panel,
selecting and editing, pictures from the media library, canvas, server-side renders, console) and under
Breakdance's Zero theme, a block theme and a classic theme — `docs/QA-REPORT.md` says what that covered and
what it didn't.

Tips for a Breakdance Section that holds a widget:

- **Spacing.** Every section widget brings its own vertical rhythm (`padding-block`), on top of the
  Section's padding. Set the element's **Spacing** control to *none*, or set the Section's vertical padding to
  0. For a widget added as a shortcode, `.evpx-root { --evpx-section-y: 0; }` in the Shortcode element's
  *Advanced → Custom CSS* or your stylesheet does the same (default `6rem`).
- **Width.** Widgets fill the box they're in and adapt to it: a widget in a half-width column
  switches to its narrow layout on its own. Inside a Section they are as wide as the Section's container;
  set the container to full width for a full-bleed hero.
- **Styles in `<head>`.** Native elements bring their stylesheet through Breakdance, in `<head>`, once per
  page. For shortcodes, the stylesheet is detected from the page's content or element tree; under a block
  theme or Breakdance's own templates a widget in a header or footer is found too. Only on a plain classic
  theme can a shortcode in a footer paint unstyled for a moment; force `<head>` loading with
  `add_filter( 'evpx_load_assets', '__return_true' );` or a condition of your own.
- **Builder.** Renders for the builder are static on purpose (no entrance animation, no scroll
  triggers, no progress bar); the front end animates as configured.

## Compatibility notes

- **Theme**: works with classic and block (FSE) themes. Block themes that
  constrain content width (e.g. Twenty Twenty-Five) are handled — every
  top-level widget carries WordPress's own `alignfull` class so it escapes
  the theme's content-width wrapper instead of getting squeezed.
- **SEO plugins**: the FAQ widget's JSON-LD `FAQPage` schema can be
  disabled per-instance (`schema_output="false"`) if Yoast/RankMath/etc.
  already emit FAQ schema on the same page.
- **Caching/minification/CDN**: all assets are registered through WordPress's standard
  `wp_enqueue_style`/`wp_enqueue_script`, so concatenation/minification plugins handle them
  normally. Fonts are bundled with the plugin (SIL OFL) — a page view makes no request to a
  font host. GSAP + ScrollTrigger load from cdnjs.cloudflare.com by default; they are not
  bundled because GSAP's licence restricts redistribution inside builder add-ons. To
  self-host them (strict CSP, privacy policy, offline), point the filters at your own copies:

  ```php
  add_filter( 'evpx_gsap_src', fn() => content_url( 'uploads/gsap/gsap.min.js' ) );
  add_filter( 'evpx_scrolltrigger_src', fn() => content_url( 'uploads/gsap/ScrollTrigger.min.js' ) );
  ```

  If GSAP never loads, nothing breaks: every widget stays complete and interactive, only the
  animation is absent.
- **Other plugins/builders**: nothing is hooked into anything global —
  no `.bde-*` selectors, no bare element selectors (`h1`, `img`,
  `.container`…), no core file overrides. The other direction is defended too: every rule is
  scoped under `.evpx-root` so a host's `h2 { … }` or `a { … }` rules can't restyle a widget. To
  override a widget's look, use the same two-class selector (`.evpx-root .evpx-hero__title`). Deactivating the plugin leaves
  existing content untouched (shortcodes simply stop expanding; the raw
  `[evpx_...]` text is not deleted from the database).

## Trying the demo article

`docs/demo-article.txt` is the full "Choosing AC or DC Charging for Your Site" article as
shortcodes. To load it as a post:

```
wp post create docs/demo-article.txt --post_type=post --post_status=draft \
  --post_title="Choosing AC or DC Charging for Your Site"
```

or paste its contents into a Custom HTML block / a Breakdance Shortcode element. The hero in the
demo sets `title_tag="h2"` because a WordPress post's theme already prints the title as `h1`;
remove that attribute on a page where the hero is the only title. It has no
images — see `docs/MEDIA-BRIEF.md`. The Related Articles row lists other published posts in the
same category, so it appears empty until you have some.

## Uninstalling

Deactivate normally from Plugins. There is currently no data to clean up —
the only thing the plugin writes to the database is the `evpx_version`
option, which is harmless to leave behind.
