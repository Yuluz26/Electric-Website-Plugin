# Troubleshooting

**A widget's shortcode shows up as literal text (`[evpx_hero ...]`) instead
of rendering.**
The plugin isn't active, or you're looking at unsaved/preview content that
bypassed `do_shortcode`. Confirm the plugin is active in Plugins.

**Styles are missing on a page that clearly has an EV widget.**
Assets load conditionally. The stylesheet is detected from the post's content and, for a page
designed in Breakdance, from its element tree, and printed in `<head>`. A widget anywhere else (a
Breakdance header, footer or template, a widget area) is caught by a fallback that enqueues the
moment an `[evpx_*]` shortcode actually renders — reload once; if it's still missing, check your
browser console for a failed request to the plugin's own `assets/css/evpx.css` (a path or
permissions problem) — the plugin makes no other style requests.

**The page paints unstyled for a moment, then jumps into place.**
The widget lives somewhere detection can't see, so the stylesheet arrives from the footer
fallback. Load it in `<head>` for those requests:
`add_filter( 'evpx_load_assets', '__return_true' );` (the filter receives the detected boolean and
the queried post, so you can restrict it).

**A widget has huge empty space above and below it inside a Breakdance Section.**
The Section's own padding and the widget's rhythm add up. Set the Section's vertical padding to 0, or
`.evpx-root { --evpx-section-y: 0; }`.

**A theme or plugin still overrides a widget's heading or link colour.**
Widget rules are scoped under `.evpx-root` (specificity 0,2,0), which beats bare `h2`/`a` rules and
the usual `.builder h2` patterns (0,1,1). A host rule with three or more selectors can still win;
override it back with `.evpx-root .evpx-hero__title { … }` (add a class if you need to outrank it).

**GSAP animations don't play, but the page looks and works fine otherwise.**
This is by design, not a bug: if `cdnjs.cloudflare.com` is unreachable (a strict CSP, an
offline environment, an ad-blocker) the motion layer simply doesn't start and every widget
falls back to fully static, fully interactive markup — FAQ accordions and the AC/DC tabs still
work via plain JavaScript. Self-host GSAP with the `evpx_gsap_src` / `evpx_scrolltrigger_src`
filters (see `docs/INSTALLATION.md`) if you need animation guaranteed on a locked-down network.

**The hero (or a section) stays invisible.**
It shouldn't: the hero's entrance is held back in CSS only until the scripts take over, and a
CSS failsafe reveals it after ~1.6 s if they never do; it isn't held back at all for
reduced-motion visitors, with JavaScript off, or inside a builder canvas. If you see it,
check whether another plugin strips `data-*` attributes or injects a stylesheet that sets
`opacity` on `.evpx-hero *`.

**Cards stopped lifting on hover.**
Something is leaving an inline `transform` on them. The plugin's own reveal clears its
transform when it finishes; a third-party animation plugin targeting the same elements is
the usual cause.

**A Scenario Card / FAQ Item shortcode isn't rendering inside its
container.**
Check the exact tag name — container children use underscores
(`evpx_scenario_card`, `evpx_faq_item`), matching the other shortcodes'
convention, even though their block names use hyphens
(`evpx/scenario-card`) per Gutenberg's own convention.

**Text looks stacked strangely / a grid of cards renders as one column
with odd gaps.**
This was an actual bug caught during development: WordPress's `wpautop`
filter runs *before* `do_shortcode` on `the_content`, and can inject stray
`<br>`/`<p>` tags between nested shortcode lines, which then become extra
grid children. It's fixed in `Element::stripAutopArtifacts()` for every
container widget shipped in this plugin — if you see this symptom in a
*custom* container element you build yourself (via the Element Studio
bridge), route its enclosed content through the same cleanup, or avoid
blank lines between nested shortcode tags in the source.

**A page built entirely in Breakdance looks off after installing.**
It shouldn't — nothing in this plugin touches Breakdance's own CSS, templates, or element output;
it only adds new shortcodes/blocks and, separately, an Element Studio save location. If you see a
regression, please check whether it reproduces with the plugin deactivated before reporting it as
caused by this plugin.

**A widget inside a Breakdance column shows its phone layout on a wide screen.**
That's intended: widgets follow the width of the box they sit in, not the viewport. A widget in a
narrow column gets the narrow layout (single-column cards, stacked decision list, vertical flow).

**PHP warnings/notices in `wp-content/debug.log` mentioning `EVPX` or
`Breakdance\`.**
Every Breakdance API call in this plugin is guarded with
`function_exists()`/`class_exists()` before use, so it should never fatal
even without Breakdance installed. If you do see one, please capture the
exact log line — it points at a real gap in that guard.
