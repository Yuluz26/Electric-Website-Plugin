# Installation & setup

## Requirements

- WordPress 6.0+
- PHP 7.4+ (developed and QA'd against 8.2/8.4)
- Breakdance is **optional** — every widget works as a plain WordPress
  shortcode/block with no page builder at all. With Breakdance active, the ten widgets also appear
  in its Add panel as native elements (see `docs/BREAKDANCE.md`). Tested with Breakdance 2.8.3.

## Install

1. WordPress Admin → Plugins → **Add New → Upload Plugin**, choose `ev-charging-experience-x.y.z.zip`
   and **Install Now**. (Building the ZIP yourself: `docs/PACKAGING.md`. Or use the repository directly as
   `wp-content/plugins/ev-charging-experience/`.)
2. **Activate** "EV Charging Experience".
   Activation records the installed version and queues two example articles (below). It
   never touches existing posts, pages, Breakdance data, or theme files.
3. That's it. No database migration, no setup wizard, no settings page, no required
   configuration.

## The example articles

The next admin screen after activation shows a notice and two new **drafts**, so a fresh install has something
to open, read and copy from:

| Example | Where | What it is |
|---|---|---|
| *Choosing AC or DC Charging for Your Site* | Posts | The article as shortcodes in the post's content (`content/demo-article.txt`). Works on any WordPress, with or without Breakdance. |
| *Choosing AC or DC Charging for Your Site (Breakdance)* | Pages | The same article as native Breakdance elements, one full-width Section each, ready to open with **Edit in Breakdance**. Made once Breakdance is active. |

- **Drafts.** Nothing is public until you publish. The notice links to Preview and to Edit (the builder, for the
  Breakdance one). The status is the `evpx_example_pages_status` filter's (`draft`, `private` or `publish`), which
  has to be in place before the first admin screen after activation: `add_filter( 'evpx_example_pages_status', fn() => 'private' );`
  in a must-use plugin or your theme's `functions.php`.
- **Made once.** The plugin keeps the two ids in the `evpx_examples` option. Deleting an example does not bring it
  back, and neither does deactivating and activating again. To have them made again, delete the option
  (`wp option delete evpx_examples`) and activate; deleting the plugin from the Plugins screen removes it for you.
- **Breakdance arriving later.** With the plugin active and Breakdance not, only the post is made, and the notice
  says the Breakdance page will follow. The next admin request after Breakdance is activated adds it.
- **Upgrading a copy that is already active.** Uploading a newer version over it does not run activation (WordPress
  skips it for an update), so a site that already had the plugin gets no examples. Deactivate and activate to get
  them. A scripted activation (`wp plugin activate`) makes them when an administrator next opens wp-admin.
- **Nothing else changes.** No existing post, page, template or setting is touched, and the examples are the only
  content the plugin ever writes. Multisite: they are made on the site where the plugin is activated.
- **The hero heading.** On the Breakdance page the hero is the page's `h1` under Breakdance's own Zero theme, which
  prints no title of its own, and an `h2` under any other theme, which prints the title as an `h1` above it (see
  "One h1 per page" below).

## Your first page in Breakdance

With the plugin and Breakdance both active:

1. **New page.** The quickest start is the Breakdance example the plugin has already made (Pages → *Choosing AC or DC
   … (Breakdance)* → **Edit in Breakdance**): change the copy, then duplicate the page for the next article. To
   build one from nothing: Pages → Add New → *Edit in Breakdance*. For an article that should list its
   category's other posts under it, use a Post instead of a Page.
2. **Prepare one Section.** Add a Section and, in its Design settings, set **Width** to *Full* and **Padding** to
   0 on every side. Duplicate it once per widget. The widgets are full-bleed bands with their own vertical
   rhythm, so this is what makes the page read as one piece (`docs/BREAKDANCE.md`, "Recommended setup").
3. **Add the elements.** Click **Add**, search "EV", and drop one element from the **EV Charging** category into
   each Section. The demo article's order is a good default: Article Hero · Section (three times) · AC/DC
   Comparison · Charging Explorer · Decision Factors · Scenario Cards · Technical Flow · Section · FAQ · CTA ·
   Related Articles.
4. **Edit in the panel.** Content, Media, Layout, Visual, Motion, Responsive and Advanced groups. Scenario
   Cards, Decision Factors and FAQ take their entries in an **Items** repeater. Hover a text field for
   Breakdance's dynamic-data button: the Hero's summary can be the post excerpt, and its reading time fills
   itself when left blank. On a Page, set Related Articles' **Source** to *Latest* or *Manual*; *Category* needs a
   Post.
5. **One h1 per page.** The Hero prints the page's `h1`. If the theme or a Breakdance template already prints the
   title, set the Hero's **Title tag** to `h2`.
6. **Save and open the page.** The builder shows a static version (no entrance animation, no scroll triggers, no
   progress bar); the motion runs on the live page.

Rather see everything working first? It is already there: the example articles above. To load the article
yourself (a Custom HTML block, or WP-CLI): "Trying the demo article", below.

## Using it without Breakdance

Insert any widget as a shortcode directly in the block editor, classic
editor, or any `post_content`:

```
[evpx_hero title="Choosing AC or DC Charging for Your Site"]
```

Or search for "EV" in the block inserter — all thirteen blocks (the ten widgets, and the three item blocks
that sit inside FAQ, Scenario Cards and Decision Factors) live under the
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

`content/demo-article.txt` is the full "Choosing AC or DC Charging for Your Site" article as
shortcodes. To load it as a post:

```
wp post create content/demo-article.txt --post_type=post --post_status=draft \
  --post_title="Choosing AC or DC Charging for Your Site"
```

or paste its contents into a Custom HTML block / a Breakdance Shortcode element. The hero in the
demo sets `title_tag="h2"` because a WordPress post's theme already prints the title as `h1`;
remove that attribute on a page where the hero is the only title. It has no
images — see `docs/MEDIA-BRIEF.md`. The Related Articles row lists other published posts in the
same category, so it appears empty until you have some.

## Uninstalling

Deactivate normally from Plugins; nothing is removed, and the example articles stay. Deleting the plugin
(Plugins → Delete, which runs `uninstall.php`) removes what it stored — the `evpx_version` and `evpx_examples`
options — and still leaves the examples: by then they are your content, and may have been edited. Delete them from
Posts and Pages if you don't want them.
