# Troubleshooting

**A widget's shortcode shows up as literal text (`[evpx_hero ...]`) instead
of rendering.**
The plugin isn't active, or you're looking at unsaved/preview content that
bypassed `do_shortcode`. Confirm the plugin is active in Plugins.

**Styles are missing on a page that clearly has an EV widget.**
Assets load conditionally. A native Breakdance element brings its stylesheet through Breakdance (view the
source: `assets/css/evpx.css?bd_ver=…`). For shortcodes and blocks the stylesheet is detected from the post's
content and, for a page designed in Breakdance, from its element tree, and printed in `<head>`; a widget
anywhere else (a Breakdance header, footer or template, a widget area) is caught by a fallback that enqueues
the moment an `[evpx_*]` shortcode actually renders — reload once; if it's still missing, check your browser
console for a failed request to the plugin's own `assets/css/evpx.css` (a path or permissions problem) — the
plugin makes no other style requests.

**The page paints unstyled for a moment, then jumps into place.**
Only for a shortcode in a footer or header on a plain classic theme, where `<head>` is printed before the body
renders and detection can't see it: the stylesheet arrives from the footer fallback. Load it in `<head>` for
those requests: `add_filter( 'evpx_load_assets', '__return_true' );` (the filter receives the detected boolean
and the queried post, so you can restrict it). Native elements, and any theme that renders the body first
(block themes, Breakdance's Zero theme and templates), don't show this.

**I don't see the example articles after activating.**
They are made on the next admin screen, not during activation itself, as drafts, and only when an administrator loads
it (an editor's visit does nothing). Look under Posts and Pages for *Choosing AC or DC Charging for Your Site* (draft). If Breakdance was not
active when the plugin was, only the post exists until Breakdance is activated and an admin screen loads. They are made once: if you deleted them, or
activated before this version, run `wp option delete evpx_examples` and activate again (or deactivate and
activate). `wp option get evpx_examples` shows what was recorded: `article` and `breakdance` hold their ids, `0`
means that one failed and was not retried, and `pending: true` means the Breakdance page is waiting for Breakdance.

**The example page shows the title twice.**
The theme prints the page's title, and the hero repeats it. On the Breakdance example the hero is already an `h2`
under any theme but Breakdance's Zero theme; in your own pages set the hero's **Title tag** to `h2` (Advanced), or
leave the title off in the theme or the page template.

**The EV elements aren't in Breakdance's Add panel.**
Search for "EV" in the panel, or look for the **EV Charging** category. If it isn't there: Breakdance must be
active (the elements are only declared once it is), and the plugin file must be loaded — a cache or
"must-use" loader that includes it late, after Breakdance has finished starting, hides them. Look in
`wp-content/debug.log` for a PHP fatal mentioning `EVPX`.

**A page built with the EV elements shows an unknown element after an update.**
A page stores its elements' PHP class names (`EVPX\Hero`, `EVPX\Faq`, …). If a class was renamed or the plugin
was deactivated, Breakdance can no longer find it. Reactivate the plugin; the classes are never renamed.

**A widget is invisible, or its text sticks out of nothing.**
The widget has collapsed to zero width. That was a bug — a size container has no intrinsic width, and Breakdance's
Rich Text element (which shows a post's content) is only as wide as its content — and 0.4.0 fixes it with two
zero-specificity rules. If you still see it, some other rule wins: inspect `.evpx-root` and its parent, and give
the parent `width: 100%`, or the widget `align-self: stretch` if the parent is a flex column.

**The stylesheet or GSAP is loaded twice.**
It can happen on a plain classic theme when the page mixes ways of adding widgets — shortcodes or blocks in the
content and a native element in a Breakdance header, footer or template. The scripts load once; the stylesheet
may appear twice (about 8 KB gzipped, identical rules). Block themes and Breakdance's templates never do this.

**A widget has huge empty space above and below it inside a Breakdance Section.**
The Section's own padding and the widget's rhythm add up. Set the element's **Spacing** control to *none*, set the
Section's vertical padding to 0, or `.evpx-root { --evpx-section-y: 0; }`.

**Related Articles shows no pictures although some articles have a featured image.**
Pictures appear only when every listed article has one; a row of photographs with a blank tile among them looked
broken. Give the missing article a featured image, or pick articles that all have one (source: hand-picked).

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
it only adds its own elements, shortcodes and blocks and, separately, an Element Studio save location. If you
see a regression, please check whether it reproduces with the plugin deactivated before reporting it as
caused by this plugin.

**The six site pages (Home, About, …) were not made, or are not public.**
They are made on the same admin screen as the examples, as drafts, by an administrator, once (`wp option get
evpx_examples` shows `site` and the six ids under `site_pages`). The notice has **Publish all six**; or publish them
from Pages. To have them made again, delete the six pages, `wp option delete evpx_examples`, and activate again. If a page
already had one of the addresses (`/about/`, `/contact/`, …) the new one was given `-2`, and the header's `/about/`
link goes to the page that owns the plain address.

**A site page shows my theme's header and title as well as its own.**
Page attributes → Template should say *EV full-width page (no theme header or footer)*. A page made by the plugin has it;
a page you made yourself does not until you choose it. A block theme that adds its header to every page ignores no
template: if the template is chosen and the theme still shows a header, something is replacing the template after the
plugin (a `template_include` filter at a higher priority than 99).

**The header's Ctrl/Cmd+K search finds nothing.**
It uses WordPress's own REST search (`/wp-json/wp/v2/search`). A security plugin that blocks the REST API for visitors,
or a host that blocks `/wp-json/`, stops it; Enter still opens the results page, which does not use REST. Set the search
page in the header's **Search results page** control, and `/search/` is the page the plugin made.

**The contact form says "could not be sent from here".**
`wp_mail()` returned false: the server cannot send mail. Install an SMTP plugin, or send a test with `wp eval 'var_dump( wp_mail(
"you@example.com", "test", "test" ) );'`. The visitor is told to email instead; nothing is lost silently. A message that
is refused ("something is missing", "open for too long", "a lot of messages") never reached `wp_mail()`: those are the form's own checks
(`docs/WIDGETS.md`, EV Contact). If several people share one address (an office network), the limit of five an hour is per
address; raise it with `add_filter( 'evpx_contact_limit', fn() => 20 );`.

**A sticky Site Header does not stay at the top inside Breakdance.**
Each Breakdance Section is its own containing block, so a `position: sticky` bar cannot outlast its Section. Make the Section
sticky in Breakdance and turn the widget's *Sticky* off (`docs/BREAKDANCE.md`).

**A widget inside a Breakdance column shows its phone layout on a wide screen.**
That's intended: widgets follow the width of the box they sit in, not the viewport. A widget in a
narrow column gets the narrow layout (single-column cards, stacked decision list, vertical flow).

**PHP warnings/notices in `wp-content/debug.log` mentioning `EVPX` or
`Breakdance\`.**
Every Breakdance API call in this plugin is guarded with
`function_exists()`/`class_exists()` before use, so it should never fatal
even without Breakdance installed. If you do see one, please capture the
exact log line — it points at a real gap in that guard.
