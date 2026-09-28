# Troubleshooting

**A widget's shortcode shows up as literal text (`[evpx_hero ...]`) instead
of rendering.**
The plugin isn't active, or you're looking at unsaved/preview content that
bypassed `do_shortcode`. Confirm the plugin is active in Plugins.

**Styles are missing on a page that clearly has an EV widget.**
Assets load conditionally, detected from `post_content`/block presence. If
a widget is inserted through a mechanism that doesn't store the shortcode
text in `post_content` (some page builders), the plugin falls back to
enqueuing in the footer the moment any `[evpx_*]` shortcode actually
renders — reload the page once; if it's still missing, check your browser
console for a failed request to the plugin's own
`assets/css/evpx.css` (a path or permissions problem) — the plugin makes no other
style requests.

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
It shouldn't — nothing in this plugin touches Breakdance's own CSS,
templates, or element output; it only adds new shortcodes/blocks and,
separately, an Element Studio save location. If you see a regression,
please check whether it reproduces with the plugin deactivated before
reporting it as caused by this plugin.

**PHP warnings/notices in `wp-content/debug.log` mentioning `EVPX` or
`Breakdance\`.**
Every Breakdance API call in this plugin is guarded with
`function_exists()`/`class_exists()` before use, so it should never fatal
even without Breakdance installed. If you do see one, please capture the
exact log line — it points at a real gap in that guard.
