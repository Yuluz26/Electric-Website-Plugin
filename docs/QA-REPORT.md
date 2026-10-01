# QA report — v0.8.0

Everything below was run, not reasoned about. Every command is in the repo, so it can be
re-run: see "Reproducing" at the end.

## Readiness

**Release candidate — not yet "production ready".** 0.3.0 was the first release run against a real,
licensed **Breakdance 2.8.3**; 0.4.0 added the nine widgets as native Breakdance elements and drove them in the
builder. 0.6.0 is a second design release: a new design language (copper as light, one icon family, built-in
drawings in place of photographs), one new widget (the charging explorer, a tenth native element), and the
accessibility audit that came with it applied to what already existed. The whole matrix was run again against it on
Twenty Twenty-Five; the suites that depend on the theme were run again on Breakdance 2.8.3 under its own Zero theme, and
the browser suites once more with Breakdance switched off. Building it found ten more defects in the new
work (45–54 below), all fixed. One was found by an existing check that was written for something else (the 320 px
overflow sweep). Three (52–54) were found by looking at every widget at four widths after the tests were green, which
is the argument for looking. Each of these (a light that never painted, a label under the copy, a gauge faded to a
ghost, a floor that ended in a line, a unit in the wrong case, a card left alone in a row) now has a check that was
watched failing on the code it guards.

0.8.0 builds the site: ten widgets for the pages of a site (a page hero, a header with a search, a search page, a
footer, stats, services, a process rail, a projects rail, quotations, a contact form) and six draft pages made on
activation, full width in any theme on the plugin's own template. Two things in the brief could not be done as asked, and
neither is hidden: the reference site could not be opened from this environment (so the copy is original and written in the
structure a site like it would have, not mirrored), and no image host was reachable (so the pictures are drawn scenes with a
slot for a photograph, and `docs/MEDIA-BRIEF.md` is the shot list). The new browser suite found eight defects in the new
work before release (60–67 below). Four of them (60–63) were found by looking at screenshots, before any check existed for
them; four (64–67) were found by checks written for something else (the 320 px sweep, axe, the first-screen check, the
Enter key in the search). It also found that a scratch script of mine had deleted WordPress's own "Hello world" post, which
two older suites depend on, and that three of my own test edits were wrong; those are test-side, and are listed after 67 so
they are not mistaken for product bugs.

0.7.0 makes the pages: activating the plugin adds two draft example articles (a post with the article as shortcodes,
and, once Breakdance is active, the same article as a Breakdance page of native elements), so a new install has
something to open. Building it found two defects (58–59 below), and the check written for it was watched failing
against 21 deliberate faults in the code it guards (16 in the logic, 5 in what only a browser and a real admin request
show); one of the first 17 turned out to be no fault at all (WordPress already sets the author, so the line that set it
was redundant, and it was removed) and one exposed a gap (a request that finds the article made must add only the
Breakdance page), now a check of its own.

0.6.1 fixes the two weak spots that review named (the hero byline, which could leave its last item alone on a row, and the
Technical Flow, the plainest widget, which is now a rail on a dark blueprint band) and three defects the work turned up
(55–57 below), each with a check that was watched failing first.

0.5.0 was a design release: the type, the depth, the hero, the comparison, the hover states and the
motion were redone, and the whole matrix was run again against the result: WordPress on Twenty Twenty-Five, and
Breakdance 2.8.3 under its own Zero theme with the widgets in a default Section and in the full-width Section
`docs/BREAKDANCE.md` recommends. Redesigning found eleven more bugs in the plugin's own work (34–44 below), all
fixed and guarded by tests; three of them (a hero that flashed for one frame, a comparison that overflowed at
320 px, and a reading-progress bar that was a 645 px column instead of a bar across the window) were caught by
checks or captures made for other reasons.

Two limits worth reading before you rely on the design verdict. The reference site the brief compares against
could not be opened from this environment (its host is blocked); two phone screenshots of it were all there was to go
on, so "better than the reference" is a judgement made from the running pages, not a measured comparison. And the design was judged from screenshots and measured
behaviour in Chromium at 1440, 1366, 768, 390 and 320 px, not on a phone in a hand. What still stands between
this and a production label needs things this environment doesn't have: real photography, browsers other than
Chromium, and a site running your caching stack. Details under "Not verified".

## What was tested

| Layer | How | Result |
|---|---|---|
| PHP syntax | `php -l` on PHP 8.4 (plugin header promises 7.4+) | clean |
| WordPress coding standards | `phpcs` — WordPress-Core + WordPress-Extra + PHPCompatibilityWP (7.4+), security/escaping/i18n/prefix sniffs on | 0 errors, 0 warnings, with the formatting exclusions listed and justified in `phpcs.xml.dist` |
| JS syntax | `node --check` | clean |
| Stylesheet, statically | `tests/css-check.mjs`, no browser: every `--evpx-*` token that is read is defined; every selector is scoped under `.evpx-root`; no `!important` outside the reduced-motion rule; every animation is gated on the motion marker | 5/5 |
| Colour contrast | `tests/contrast-check.mjs` reads the real tokens out of `evpx.css` and asserts 15 pairings, light and dark (30 checks), including the lit and shaded faces of a raised surface and white type on the button's hover fill | all pass |
| What the widgets print | `tests/docker/widget-render-check.sh`, inside WordPress, no browser: the comparison's power scale (ranges, points, thousands separators, decimals, and six inputs that must *not* draw a ruler) and its key, the section's key figure, the hero's captions, icon and drawing, every icon (inline, decorative, one family; a name that is not there renders nothing; the words of the demo article find their icons), every drawing (decoration, unique ids, a stale key renders nothing), the scenario, flow and decision icons and their rules, Related's drawing for an article with no picture, the CTA's default, the explorer (its numbers as JSON, its answer worked out on the server, numbers held in range), the flow's look, the anchor (the id on the root of every section widget, cleaned to letters, digits, `-` and `_`, absent when empty), that typed-in text is escaped | 62/62 |
| Activation / deactivation | WP-CLI, `WP_DEBUG` + `WP_DEBUG_LOG` + `WP_DEBUG_DISPLAY` | clean; the only `debug.log` entries across activation, rendering of every widget and every suite below are core's own wordpress.org update check failing on the sandbox network |
| **Real Breakdance 2.8.3 — integration** | `tests/docker/breakdance-real-check.sh`: 24 assertions against the running plugin (save locations reach Breakdance; Dynamic Data field; reading time; the twenty native elements are declared, concrete, in their own category, with control paths that match their controls, repeaters, dynamic-data paths, the spacing and anchor attributes, toggle semantics, and the **same markup as the shortcode** for all thirteen widgets of the demo article) plus a behavioural probe: an element file saved in the plugin's Element Studio folder — declaring a class with a native element's name — must be loaded by a *fresh* PHP process | 26/26 |
| **Real Breakdance — front end and builder** | `tests/playwright/breakdance-qa.mjs`: a page designed in Breakdance from the demo article, once with Shortcode elements and once with native elements; front end (assets, typography, hover colours, reading time, interactions, motion, overflow at six widths, axe) and the builder itself: server-side renders, canvas, and, for native elements, the Add panel, selecting each element, editing a control (one render), a toggle (saved as `false`), and choosing a picture in the media library | 16/16 on the Shortcode page · 24/24 on the native page · 24/24 on the native page in full-width Sections without padding · 24/24 on the native page with pictures |
| **Builder round-trip** | `tests/docker/builder-save-check.sh`, on scratch pages it deletes: a dropdown lists the widget's options and re-renders the canvas; Save answers 200; the front end and a reopened builder show the edit; an element added from the Add panel to an empty page renders with its starting copy, brings its stylesheet into the canvas with it, and saves | 6/6 |
| **Pictures** | `tests/docker/media-pages.sh` generates six test images (GD gradients with a frame at the edges, one of them a near-white sky), imports them, and builds a shortcode post, a native page and a mixed Related row; `tests/playwright/media-qa.mjs` at 1440 and 390 px | 49/49 |
| **Templates and themes** | `tests/docker/template-check.sh`: a Breakdance footer (native CTA, and a Shortcode element) and a Single Post template, each checked with pages that have their own shortcode or native widgets, under **Twenty Twenty-Five, Breakdance's own Zero theme and a bare classic theme** | 180/180 |
| **Unrelated pages** | `tests/docker/isolation-check.sh`: three pages with no EV element (Sample Page, a post, a Breakdance page), screenshotted with the plugin active and inactive, under two themes | 12/12 |
| Browser suite, any WordPress page | `tests/playwright/qa.mjs`, 23 checks, Chromium, WordPress on the Twenty Twenty-Five block theme, with Breakdance also active. Also run, in 0.6.0 and again in 0.6.1, on plain WordPress with Breakdance deactivated (this suite and the interaction, explorer, accessibility and composition suites) and under Breakdance's Zero theme (the same five, and the four Breakdance pages); the one width assertion that presumes a block theme is skipped inside a Breakdance Section | 23/23 on both themes and without Breakdance (22 and 1 skipped under Zero) |
| Interaction, motion and hover states | `tests/playwright/interaction-qa.mjs`, Chromium: the finished state and the motion that leads to it (the hero's charge line, the flow's connectors, the comparison's range bars), the comparison thumb measured onto the active tab and moved by a click, the button's fill and arrow, the scenario card's rim and highlight following a fine pointer, the decision list's numerals, the FAQ's open state and that a row opens from its own height, the related arrow, keyboard focus rings, no seams between widgets, one shared left edge, "Follow system" following both colour schemes; a flow not yet reached put away at once, the hero drawing's motion, and its stillness for a reduced-motion visitor, and that its labels, charger and gauge lie clear of the copy at seven widths; then the same page for a reduced-motion visitor and for a touch device with no hover | 44/44 |
| **The explorer** | `tests/playwright/explorer-qa.mjs`, Chromium, plus `php tests/php/model-matrix.php`: the page before the script and after it; the script's model against the PHP one over 200 cases; the controls, from a pointer and from a keyboard; six widths (below) | 43/43 |
| **Accessibility rules axe cannot see** | `tests/playwright/a11y-qa.mjs`, Chromium: focus rings at 3:1 on every surface in both colour schemes, targets, type size, measure, icons, forced colours (below) | 12/12 |
| **Composition** | `tests/playwright/layout-qa.mjs`, Chromium: the hero's gauge is as bright as its type, no drawing paints with a gradient on an empty box, units keep their case, the floor reaches the drawing's edges, a wrapped byline is the author over the pair, the flow's rail joins its sockets and fits a narrow column, a Related row ends square (below) | 12/12 |
| Breakdance contract stub | `tests/docker/breakdance-stub.php` + `breakdance-contract-check.php` — kept for CI without a licence; the real check above is authoritative | 11/11 in 0.4.0; not re-run since. 0.6.0 adds the explorer to `src/Breakdance`, and the real-Breakdance checks above cover the same ground with the real thing |
| **Example articles** | `tests/docker/example-pages-check.sh`: the logic in WordPress (`example-pages-check.php`), then a browser as an administrator (`examples-qa.mjs`) after a scripted activation, and once more through the Plugins screen's Activate link; then a second request, a deleted example, and Breakdance arriving after the plugin. Cleans up after itself | 75/75 |
| **Site widgets, printed** | `tests/docker/site-render-check.sh`, inside WordPress, no browser: the lines parser (a `<br />` from `wpautop`, `/slug/` addresses, a figure split into number and unit, `24/7` as a phrase, the hidden fields a GET form needs), the five scenes (unique ids, decoration only), the page hero, header and its search dialog, the search page (a query, a filter, escaping, no match), the footer, stats, services, process, projects, quotes, the contact form's markup and its signed token (a changed recipient breaks it), the anchor on the new sections, the full-width template | 60/60 |
| **The contact form, over HTTP** | `tests/docker/contact-form-check.sh`, mail caught by a must-use plugin that is removed again: refused when sent at once, accepted a few seconds later and sent back to the form's own page, mailed to the widget's recipient with the sender as Reply-To, a filled trap field told it worked and nothing sent, a bad address, no name, a two-letter message, a changed recipient, garbage, an off-site return address not followed, the sixth message in an hour refused (five were mailed), the `evpx_contact_limit` filter, every outcome shown in words, markup in the outcome not printed | 23/23 |
| **The six site pages, in a browser** | `tests/docker/site-pages-check.sh` with `tests/playwright/site-qa.mjs`: the pages made as activation makes them, **once as native Breakdance elements and once as shortcodes**, published, then driven in Chromium: nothing scrolls sideways or pokes out of its widget at 320, 390, 768, 1024, 1366, 1440 and 1920 px; axe-core on every page at 1440 and 390; one h1, one main, named navigations, a skip link; no script error; the hero marked as moving, its layers sliding with the pointer and its figures on the first screen; the counters ending on their values; the services strip by pointer and by keyboard; the process rail and its counter; the projects rail (buttons, drag, position bar, first card on the page's edge); the quotations (arrows, dots, aria-live, a stable height, dots of 24 px); the header's search (Ctrl+K, `/`, Escape, live results, Enter) and the phone menu; the contact form (labels, the trap, the busy state, the outcome); the search page; then the notice's publish buttons (nonce, administrators only, the front page) | 233/233 |
| The shipped artifact | the built ZIP installed as a separate plugin directory and the suites run against *that* | 0.7.0: the integration check 26/26, the render check 62/62, the composition suite 12/12 and the example-article check 75/75 against the ZIP (128 entries, 388 KB), installed as a plugin of its own. The last is the one that needs the ZIP to carry `content/demo-article.txt`, `uninstall.php` and the new classes: a ZIP without the article would activate cleanly and make nothing. The tree it was built from differs from the committed one in this file only. The full pass (browser suite, both Breakdance pages, pictures, unrelated pages, templates) was run on 0.6.0's ZIP; the packaging script has not changed since |
| Motion | real GSAP **3.12.5** (the version the plugin loads by default), served locally because this sandbox blocks cdnjs | verified below |

### The 23 checks in `qa.mjs`

No uncaught exceptions · no third-party font requests · **no horizontal overflow at 320, 375, 390,
430, 768, 1024, 1280, 1366, 1440 and 1920 px** · no leftover `[evpx_*]` text · the bundled fonts
(Spectral, Geist, Geist Mono) actually load · heading hierarchy (≤ 1 `h1`, no skipped levels
inside widgets) · decision factors and related articles render · FAQ accordion toggles · AC/DC tabs
switch · tabs keyboard pattern (Home / Arrow keys, `aria-controls` ↔ `aria-labelledby`) · scenario
cards form clean columns, the middle one stepping down (this replaced "cards align in grid rows" when the
stagger became deliberate) · sections aren't squeezed by the theme's content width · reading-progress
bar tracks scroll · the progress bar spans the window, not the theme's content column · every scroll
reveal settles fully visible · card `:hover` lift survives its reveal · `prefers-reduced-motion` (nothing hidden, zero scroll triggers) · JavaScript disabled (FAQ
answers and both AC/DC panels readable, no dead tabs, hero visible) · builder-canvas simulation
(page inside an iframe: motion off, content visible, zero scroll triggers) · the hero never flashes
visible → hidden → visible on a slow GSAP load, tested twice: a load slower than the entrance (1.2 s) and a
load slower than the stylesheet's own failsafe that reveals the hero (2.8 s) · `axe-core` WCAG 2.0/2.1/2.2 A + AA
+ best-practice over every widget at 1280 px and 390 px: **zero violations**.

### The checks in `breakdance-qa.mjs`

Front end (both page types): every widget in the Breakdance tree renders · stylesheet is printed in
`<head>`, each asset once · no heading falls back to a host typeface · hero title stays light on the dark
hero · primary buttons keep white text, also on hover · hero reading time comes from the element tree · FAQ
opens · AC/DC tabs switch · motion runs and every reveal settles · no widget overflows its box at 320, 390,
768, 1024, 1440, 1920 px · axe-core zero violations at 1280 and 390 px · no uncaught exceptions.
Builder (both page types): every widget renders through Breakdance's server-side render (all 200, no
shortcode text) · that render is static (`data-evpx-animate="0"`, no progress bar) · the canvas shows every
widget, the hero in its own typeface, motion off, no scroll triggers · no console or page errors from this
plugin.
Builder, native page only: the Add panel lists all nine EV elements · selecting each of the nine kinds in
the canvas opens its controls · editing a control re-renders through exactly one server-side render and shows
the change · a toggle switched off is saved as an explicit `false` · the Items repeater works (a new empty row
isn't rendered, its question adds the item, its answer shows) · **choosing a picture in the builder's media
library saves a media object with an attachment id, re-renders once, and the canvas shows it described and
responsive** (skipped without the fixture images) · **dynamic data**: "Post Title" chosen on the Hero title is
saved as a token and the canvas shows the title, not the token; the plugin's own EV Reading Time field has no
Pro badge, is saved as a token, and the canvas shows the value.

Each check was written after the bug it guards was seen for real. They were also confirmed to
*fail* on the broken state: against the pre-fix stylesheet six of the sixteen fail (system-font
headings, dark hero title `rgb(17,24,39)`, blue button text `rgb(59,130,246)`, sideways scroll,
contrast, builder typeface); against the pre-fix builder detection the two builder checks fail
(`data-evpx-animate="1"`, builder flag empty); and with the recursion guard removed the
self-referencing page segfaults PHP.

### The checks in `explorer-qa.mjs`

Without a script: the explorer is there, its controls (which would do nothing) are not shown, the comparison is
shown with five chargers, the empty chart box is not shown, and the figures and the sentence are already worked
out. With one: the controls appear and the chart is drawn, the slider says its value in words, the sentence is a polite
live region, no console errors; **the figures, the sentence and the comparison the page printed before the script are
character for character what the script prints first**; the chart's box keeps its height when it is drawn (nothing
below it moves); **the script's charging model answers as the PHP one does over 200 cases** (five cars, five
chargers, eight dwell times, to 1e-9: `tests/php/model-matrix.php` prints the PHP side); a preset sets the dwell time
and presses itself; eight hours on AC 22 kW fills the battery and the sentence says so; choosing a 350 kW charger draws
its rating as a dashed line above what the car takes and a 7 kW one draws none; the chosen charger is the lit row; the
slider runs from the keyboard (Home is 15 min, End is 12 h) and its spoken value follows; the sentence waits for the last
key; a keyboard sees a 2 px ring on the presets and the chosen charger. At 1440, 1366, 1024, 768, 390 and 320 px: nothing
pokes out of the widget, the chart's labels are 12 px or more on the screen (or the chart is not shown, below 20rem),
every control is at least 44 px tall. Reduced motion: the bars do not slide.

### The checks in `a11y-qa.mjs`

The rules axe cannot see. A keyboard walks the page in both colour schemes and every ring it meets is 2 px and 3:1
against the surface it is drawn on (the surface, not the control: the ring sits outside it), on the hero, the dark CTA,
the explorer and the light widgets alike; every control is at least 24 × 24 px (WCAG 2.2, 2.5.8; inline links in a
sentence exempt) and the comparison's tabs are 44 px; nothing meant to be read is set below 12 px; running text keeps to
80 characters a line; every icon on the page is decorative, in `currentColor`, from one family (one `viewBox`) and comes
in few sizes; and, emulating forced colours, cards, the comparison's plate, the flow's nodes and the explorer's panel have
an edge, the explorer's bars are a system colour, the selected tab is outlined and the hero's decoration is gone.

### The checks in `layout-qa.mjs`

What a screenshot review found and no earlier check could have. The hero's state-of-charge figure is screenshotted at 820
px (the band under the copy) and 1440 (beside it), and its brightest pixel must be as bright as its type (190 of 255 or
more; it measures 233–236). Every shape in every drawing that is painted from a gradient in bounding-box units is
measured, with each comparison tab open in turn, and none may sit in a box with no height or width. Every unit in every
drawing (kW, kWh, Hz) must keep its case. The floor, horizon and glow of the hero's drawing must reach both edges of the
drawing's box at 390, 700, 820 and 1000 px and its right edge at 1440 and 1920. A wrapped hero byline must be the author
over the date and the reading time, never a lone item at the end, at 1920, 1440, 1100, 1024, 900, 390 and 320 px. The
flow's rail, a groove and the light in it, is measured from the pseudo-elements that draw it against the sockets it should
join: it starts at one socket's centre, ends at the next one's and runs through their centres, across at 1440, 1100 and
768 px (read at rest, since motion holds the steps 14 px low until the flow is seen) and down at 390, 320 and when the
flow is set vertical; and put in a 220 px column (a phone inside a builder Section) nothing in it may poke out of its
widget. A Related row built from the widget's own markup for one, two and three articles must end square:
at 820 px two rows for three articles, the third across the whole row with its picture beside its text; at 1366 one row of
three; at 390 a card to a row.

Each was watched failing. Against the 0.5.0 code four of the first nine fail (the gauge at 820 px, the units, the Related
row twice). The rest by putting the fault back: the old 40-unit extents fail at 700 px (23 px short on each side); a line
stroked with a bounding-box gradient, added to a drawing, is reported; the old flat byline fails at 1100 and 1024 px; and the
flow's vertical rail, shortened by half a socket, fails at 390 and 320 px by 28 px; and the narrow-column check fails with
the column's three safeguards taken out (removing only one does not: they overlap).

### The checks in `media-qa.mjs`

For the shortcode page and the native page, each at 1440 and 390 px: the hero picture loads, is described and
has a `srcset` · fills its box without letterboxing · both section pictures load, are described and keep their
proportions · all six scenario icons load · the CTA picture loads and fills its box · the Related row is a full
row of same-sized pictures · (390 px) a wrapped hero meta line clips the separator that would start a row ·
nothing overflows · no console errors and no failed requests. Then, on the mixed page: a Related row where one
article has no featured image gives it a drawing, so no tile is blank beside a photograph. Then, on the bright page — a hero and a CTA over a
near-white sky, the worst case for white type — each line of type (eyebrow, title, excerpt, meta, body) is
measured against the brightest 5% of the picture behind it, at 1280 and 390 px: 4.5:1, or 3:1 for large type.
With the 0.4.0 template and stylesheet the mixed-row check and both meta checks fail, and ten of the
fourteen legibility checks fail (the eyebrow at 1.2–1.5:1, the title at 1.6–1.9:1).

60. **The services panel's words sat under its picture on a phone.** In the accordion layout the scene (an absolutely
    positioned layer) painted over the open row's summary and points, which were static; the link, which is positioned,
    showed, so the row looked like a picture with a button on it. Found by looking at a 390 px screenshot after the layout
    checks were green: nothing measured what was on top. The body is positioned and above the picture now. *Guard: the
    browser checks that the element at the centre of the open panel's summary is the summary, on a wide screen and on a
    phone, once its transition has finished (a text still fading in is painted above what is behind it, which is how the
    first version of the check passed on the broken code). Watched failing with the body left unpositioned.*
61. **The projects rail opened already scrolled, and its first card sat 24 px from the window's edge instead of the page's
    own left edge.** The snap used `scroll-margin` on each card; the rail needed `scroll-padding` equal to its own padding,
    or the first card snaps 128 px along. *Guard: "the rail starts at 0 and the first card lines up with the page's left
    edge". Watched failing with the rail snapping by `scroll-margin`: it opened 128 px along, the first card at 24 px
    where 152 was wanted.*
62. **Only the first quotation could ever be shown on its own.** The state that stacks them in one place was written as a
    descendant (`.evpx-root .evpx-quotes[data-evpx-ready]`) of an element that is both: the section carries both classes
    and the attribute. All three quotations printed one under another with the buttons beside them. Found in the first
    screenshot. *Guard: exactly one quotation is visible (computed `visibility` and `opacity`, not the class the script
    sets, which was right all along), its dot is current, the height does not change between them. The first version
    of the check read the class and passed on the broken code; it was watched failing, three shown at once, only after it
    read what the browser paints.*
63. **A phrase counted as a number.** `24/7` was split into the number 24 and the unit "/7" and counted up to 24;
    `150 kW` wrapped onto two lines in a four-column row. A value with a second number is a phrase now, and a unit is set
    small beside its number. *Guard: the parser's checks, and the counters ending on their real values.*
64. **Three grids pushed a phone's page sideways** (the search page by 83 px). A grid with no explicit track is as wide as
    its widest child's minimum, and a search field's is about 200 px. The tracks are `minmax(0, 1fr)` now. *Guard: the 320
    and 390 px sweep over all six pages, which caught it.*
65. **Two accessibility defects in the header, both caught by axe.** The search button had no name on a phone (its label
    is hidden there; it has an `aria-label` now), and the translucent bar the page scrolls under failed 4.5:1 for its links
    over a light section (six nodes; at 90% opacity they pass). *Guard: axe over every page at 1440 and 390 px.*
66. **A GET search form forgot the page it was posting to.** On plain permalinks the search page's address is
    `?page_id=7`, and a browser replaces an action's query with the form's own fields, so Enter landed on the home page. The
    address's query goes in as hidden fields now (`Lines::target()`), in the header's dialog and on the search page itself.
    *Guard: the browser's Enter test, and render checks for both forms.*
67. **A hero outside a theme's reset ran off the first screen.** The widget's own box was not `border-box` (the reset
    covered its descendants), so on the full-width template the hero's padding was added to its minimum height and its
    figures fell below a 900 px window. *Guard: the figures are on the first screen at 1440 × 900, on the shortcode pages
    too.*

Test-side, in the same pass, so they are not mistaken for plugin bugs: a scratch script of mine seeded the examples'
option with a default id of 1 and a later clean-up deleted WordPress's "Hello world" post, which the template and media
suites need (restored, and the media fixtures rebuilt); the examples check's PHP half had a closure that lost a variable
and aborted half-way, leaving a page made under a forced theme to be inspected later (every "failure" after it was
that); and `ExamplePages` kept the pages of an earlier call on the object, so a call that made none still returned
the earlier home page (fixed, and the failure test now covers it). A full Chromium also asks for `/favicon.ico`, which a
site on plain permalinks does not serve, and reported the 404 as a console error on every page; the QA site has a 1 × 1
icon now (`setup.sh`). The contact-form submit check aborted the request and raced the error page; it answers 204.

### The checks in `example-pages-check.sh`

Three layers, because the feature has three: the logic, a real admin request, and the order things happen in.

**The logic**, in WordPress with the real Breakdance (`example-pages-check.php`): activation queues the examples and
makes nothing · a subscriber, an editor and an ajax request trigger nothing · the next request makes both, records their
ids and clears `pending` · a post and a page · both drafts · owned by the administrator · the post is the article
byte for byte · the page has no `post_content` of its own · the titles tell the two apart · nothing that existed before
was touched (a fingerprint of every earlier post) · a notice is queued, once · the page is a Breakdance document (a
Section per element, each holding one of the plugin's native elements, in the article's order) · every Section is full
width with no padding · the hero is an `h1` under Zero and an `h2` under any other theme (the theme is forced, three ways) ·
Related lists the latest posts · the hero's target exists · the FAQ, scenario and decision rows arrive as repeater rows ·
Breakdance reads the page's words · every element carries the attributes its shortcode carries, item rows included ·
the element-to-widget map agrees with the element files · with the article already made, a later request adds only the
Breakdance page · a second request makes nothing · activating again does not queue again · a deleted example stays
deleted · a fault is recorded and not retried, and the request carries on · the status filter can make them private, and
anything else it returns is a draft · deleting the plugin removes the version, the record and the notice, and leaves the
articles · the check leaves the site as it found it.

**A real admin request** (`examples-qa.mjs`, Chromium, logged in): after a scripted activation the first screen shows the
notice, says they are drafts, has a Preview link for each, an Edit for the article and **Edit in Breakdance** (the builder) for
the page, and shows it once. Then the same through the Plugins screen's own Activate link. Both examples are opened as
an administrator: each renders its widgets, has exactly one `h1`, has the hero's `#decision` target once, and throws no
exception; the two are the same widgets in the same order; logged out, both are 404 (drafts); the Breakdance page
overflows nowhere at 320, 390, 768, 1024 and 1440 px and reads its reading time from the element tree.

**The order** (the shell script): a second request and a re-activation make nothing, a trashed example is not made again;
with Breakdance switched off only the post is made, the notice says the Breakdance page will follow, and its links are
plain edit links; when Breakdance is switched on the next request adds the page, and the notice links to the builder.

The page the plugin makes was also put through the whole of `breakdance-qa.mjs`, front end and the real builder: made
by activation, published for the run and pointed at, it passed 24/24 (the builder's server-side renders, the canvas,
the Add panel, selecting each element, editing a control, a toggle, a picture). The converter behind it is the
plugin's own now (`Native\Tree`), and the matrix's other Breakdance pages (`breakdance-page.sh`, `media-pages.sh`, the
builder round-trip) are built with it too.

Each guard was watched failing: 16 faults put into the logic (no capability check, an ajax request not skipped, activation
re-queuing, recorded kinds made again, a failure not recorded first, the hero always an `h1`, Related not switched, an
unvalidated status, an uninstall that keeps the record, repeater rows dropped, the widget map crossed, the article
altered, published drafts, a page with content, Sections not full width, the anchor dropped from the article) and 5 in the
browser and order layers (the anchor id not printed, the hero always an `h1`, Breakdance assumed present, a notice never
consumed, no builder link), each reported by the check that names it.

### The checks in `template-check.sh`

Under each of three themes, for eight situations (thirteen shortcode widgets in a post; thirteen native widgets on a
Breakdance page; a native CTA, and a Shortcode-element CTA, in a Breakdance footer, each on a plain page and on
pages with their own widgets; native Hero and FAQ in a Single Post template): the expected number of widgets
render · the stylesheet and each script are delivered **exactly once** · the stylesheet is in `<head>` · the
widgets are styled in their own typeface · **no widget has collapsed to nothing** · nothing overflows · no
console errors. (On the bare classic theme the stylesheet may appear twice and a footer's may come late; the
scripts still load once.) Without the two Loader changes five of the block-theme checks fail (assets loaded
twice; a footer widget's stylesheet after the content); without the width rules all twelve widgets in a post
collapse to zero width under the Zero theme. (The demo article has thirteen widgets since the explorer joined it; the
expected counts in `tests/docker/template-check.sh` were updated, and the overflow probe now looks past the hero
drawing, which bleeds off the right edge on purpose and is clipped by the hero.)

### Weight

Front-end critical path (stylesheet + `evpx.js` + `motion.js`): about **35 KB gzipped** (CSS 22.3, `evpx.js` 9.2,
`motion.js` 3.1), up from 21 KB in 0.5.0 and 13 KB in 0.4.0. 0.6.0's share is the explorer (its layout, and about 5 KB
of script for the model, the controls and the chart), the drawings' styling and motion, the icon and forced-colour
rules. The icon outlines and the drawings are not in the stylesheet: they are inline SVG in the page's HTML, where a
drawing costs 1.2 to 2.8 KB gzipped each time it is used, and the demo article's drawings and 28 icons, which repeat,
compress well. Nothing is requested for them. Bundled fonts: 128 KB in
five files, up from 96 KB in 0.4.0 (unchanged in 0.6.0; Spectral 22 KB in each of two weights; Geist 29 KB and its italic 31 KB; Geist Mono
23 KB; woff2 is already compressed) with `font-display: swap`. A browser fetches only the faces a page uses, so a
page without italic body copy never loads the Geist italic. GSAP + ScrollTrigger come from the CDN and only on pages
with an EV element. No render-blocking third-party request is made by the plugin itself. (Lighthouse / field
performance were not measured.)

## Bugs found and fixed

**Pass 1 — first Docker + browser run**
1. Nested child shortcodes silently didn't render (`evpx_scenario-card` registered with a hyphen,
   used with an underscore) — `Element::shortcodeTag()`.
2. Hero "dark" mode was white-on-white: its `data-evpx-theme` collided with the page-wide
   dark-token selector. Renamed to `data-evpx-hero-mode`.
3. Scenario Cards grid scrambled: `wpautop` runs before `do_shortcode` and injected `<br>`/`<p>`
   between nested tags, which became grid children — `Element::stripAutopArtifacts()`, scoped to
   the container's own captured content, not a site-wide filter change.
4. Every section squeezed to the theme's ~645 px content width — WordPress's own `alignfull`
   class on every top-level wrapper.

**Pass 2 — "what's not finished?"**
5. The reading-progress bar was dead code (JS looked for a wrapper no widget rendered). Now the
   Hero's `progress_bar` toggle.
6. A false alarm worth keeping on record: the Hero's text column measured 736 px and looked like
   bug 4 again. It was its own intentional `max-width: 46rem`, confirmed with Chrome DevTools
   Protocol's `CSS.getMatchedStylesForNode`, and the assertion was fixed instead of the CSS.

**Pass 3 — real GSAP, axe-core, PHPCS, and auditing my own claims**
7. **Colour contrast failed WCAG AA and the docs said it passed.** Copper `#b9662f` was 3.7:1 as
   text and 4.2:1 behind white button text; `ARCHITECTURE.md` cited a contrast script that didn't
   exist. Split into `--evpx-accent` (fill) and `--evpx-accent-text` (lightens in dark mode),
   darkened the copper, wrote the script, and it now gates every pairing. A related catch: the
   accent CTA's eyebrow used `opacity: .85` on white-on-copper (4.43:1).
8. Tabs used `role="tabpanel"` on `<article>` (not permitted) and lacked the rest of the pattern
   (`aria-controls`, roving `tabindex`, arrow keys). The tab bar was also a set of dead buttons
   without JS.
9. With JavaScript off, FAQ answers were unreachable. `@media (scripting: none)` now falls back to
   fully readable content.
10. The hero flashed visible → hidden → visible whenever GSAP was slow to load.
11. GSAP's leftover inline `transform` killed the cards' CSS `:hover` lift after their reveal.
12. Elements already on screen at load were hidden only to fade back in.
13. Related Articles rendered a row of empty neumorphic boxes when no article had a thumbnail.
14. The demo page had two `h1`s (theme title + hero) — new Hero `title_tag` control.
15. Documentation claims that weren't true: the missing contrast script, a `FontLoader.php` that
    never existed, "three Dynamic Data fields" (there is one), wrong class names in the surface
    examples, a QA script that lived only in a session scratchpad, and a demo article that wasn't in
    the repo. All corrected; the demo is now `content/demo-article.txt`.

**Pass 4 — a real Breakdance 2.8.3**

Found by reading Breakdance's own source and then running it; each is guarded by a test.

16. **The Element Studio save locations were never registered.** Breakdance fires
    `breakdance_loaded` from its own `plugins_loaded` callback and reads save locations at
    priority 10 on that action. WordPress loads plugins alphabetically, "breakdance" sorts before
    "ev-charging-experience", and this plugin added its hook from *its own* `plugins_loaded`
    callback — after the action had already fired. Real run: 0 of 2 locations registered, and a
    saved element in the plugin's folder was never loaded. Now the plugin boots at include time.
17. **Builder detection used values Breakdance never sends.** It looked for
    `?breakdance=edit|run`; the real signals are `?breakdance=builder`, the canvas iframe's
    `breakdance_iframe`, and Breakdance's *own* AJAX — a POST of `breakdance_*` to a front-end URL,
    where `is_admin()` and `wp_doing_ajax()` are both false. Builder renders were animated (hero
    held back, progress bar, scroll reveals). Every motion control is now switched off for them.
18. `isBreakdanceActive()` fell back to a constant, `BREAKDANCE_VERSION`, that doesn't exist
    (`__BREAKDANCE_VERSION` does).
19. **Reading time said "1 min read" on every page built in Breakdance** (Hero and Dynamic Data
    field): `post_content` is empty there. It also counted shortcode attribute *names* as words.
    Now it reads the rendered tree the way Breakdance's Yoast/Rank Math integrations do — with a
    guard, because a page that shows its own reading time would otherwise recurse (removing the
    guard segfaulted PHP).
20. **A Breakdance-built page painted unstyled first**: its stylesheet was printed after all the
    content. Detected from the element tree now; the `evpx_load_assets` filter covers widgets
    living in Breakdance headers, footers and templates.
21. **Breakdance's global element rules beat the widgets' single-class rules.**
    `.breakdance h1–h6 { color; font-family; font-size }` and `.breakdance a` are specificity
    0,1,1: the hero title rendered dark-on-dark (~1.1:1), every heading fell back to a system
    sans, primary buttons turned blue. Every rule is now scoped under `.evpx-root` (0,2,0), link
    colours are restated for `:hover`, and every heading declares its own size and colour.
22. **Layout answered to the viewport, not the box.** A widget in a 605 px column got its desktop
    layout. Layout now uses container queries on `.evpx-root` (with an `@supports` fallback to the
    single-column layout), fluid type follows the box (`cqi`), grid tracks can shrink below their
    content, and cards tighten below 30rem. The new sweep then found two more overflows in a
    172 px column — the tab bar and a text-only section — both fixed.
23. **Base `font-size` and `text-align` were inherited from the host** (22 px in Twenty
    Twenty-Five; `text-align: left` forced by Breakdance, wrong for RTL). Now set on the root.

**Pass 5 — native elements, pictures, templates and Breakdance's own theme**

Found by driving the builder, adding pictures, putting elements in templates, and running under Breakdance's
Zero theme; each is guarded by a test.

24. **Widgets collapsed to zero width in a post's content under the Zero theme.** Breakdance shows a post's
    content in its Rich Text element, which sits in a Section that is a flex column with `align-items:
    flex-start`, so it is only as wide as its content — and a size container (`container-type: inline-size`)
    has no intrinsic width. Every widget measured `width: 0` with its text sticking out of it (`overflowProbe`:
    "100–148px outside its 100–100px widget"). The plugin's main scenario — the article published as a post on a
    Breakdance site — was broken. Two zero-specificity rules: `align-self: stretch` on the widget, and
    `:where(:has(> .evpx-root)) { width: stretch }` (with the prefixed spellings) on the box that holds it.
25. **Related Articles drew blank tiles beside real photographs** when only some articles had a featured image
    (0.2.0 had handled only "none has one"). Pictures now show only when every article has one.
26. **The hero's meta line left a separator dangling** at the end of a wrapped line on a phone
    ("Editorial •" / "September 2026"). The separators are now hairlines in the gap before each item; where a
    line starts they fall outside the box and are clipped.
27. **The stylesheet and GSAP were delivered twice** on a page that mixed shortcodes or blocks with a native
    element in a Breakdance template (WordPress and Breakdance each queued them). `evpx.js` and `motion.js`
    survive a second run, but GSAP's second copy replaced the first and left the page with a different
    ScrollTrigger state. One flag (`Loader::breakdanceDelivers()`) now decides who delivers; on a classic theme
    the already-queued scripts are withdrawn. A Breakdance-side condition could not do it: Breakdance caches the
    dependencies it collects per document, so a condition that depends on the request would be frozen in the cache.
28. **A widget in a footer or template got its stylesheet after the content**, even on a block theme, where
    the body renders before `<head>` and the loader could have known. It now asks whether an element has
    already rendered.
29. **The Element Studio save location used the PHP namespace `EVPX`, the same as the native elements** (a
    latent bug the native elements introduced). An element named "Hero" saved in Element Studio would have
    declared `EVPX\Hero` twice — "Cannot declare class EVPX\Hero" and a dead site; reproduced with a probe
    file. The namespace is now `EVPXStudio`, and the probe in `breakdance-real-check.sh` declares that class name
    on purpose.

30. **White type was unreadable over a bright picture.** The hero's scrim faded to nothing at the top, where the
    eyebrow and the first line of the title sit, so over an overcast-sky test picture the eyebrow measured
    1.5:1 and the title 1.9:1 (a phone: 1.2:1 and 1.6:1); the CTA's eyebrow and body, dimmed with `opacity`, sat at
    3.9–4.3:1. Hero and CTA now share one token, `--evpx-scrim: 0.6`, the hero's gradient starts there rather than
    at 0, and the eyebrows aren't dimmed. Measured worst case (brightest 5% of the picture behind each line, at 1280
    and 390 px): 5.0:1 or better for small type, 5.6:1 or better for large.

31. **Dynamic data showed as raw text in the builder canvas.** Choosing "Post Title" on a native element's title
    saved `[breakdance_dynamic field='post_title']` and the front end resolved it (Breakdance does that before
    an element renders), but the canvas showed the token itself: the builder's server-side render passes an
    element its raw properties. Breakdance's own Google Map resolves them in its `ssr.php` for exactly this
    reason. Native elements now do the same, only for builder renders.
32. **The EV Reading Time dynamic field was "Pro only".** Breakdance's `Field::proOnly()` defaults to `true`; the
    field carried a Pro badge and clicking it in the picker did nothing on a site without a Breakdance Pro
    licence. (Present since 0.1.0; the picker had never been opened.) It now says `false`.

33. **The empty row "Add" creates was rendered.** Clicking "Add EV FAQ Item" in the repeater put an empty
    accordion button on the page and an empty question in the FAQ's structured data (the same for a card or a
    factor with no title). A row is now rendered once it has its title (the question, for an FAQ item).

Two test-side findings from the same pass, fixed in the tests, not the plugin: the width assertion in `qa.mjs`
presumed a block theme (inside a Breakdance Section the Section sets the width, so it is skipped there), and
`media-qa.mjs` assumed a page scrolls its lazy pictures into view (Breakdance smooth-scrolls), so it now
decodes every picture instead of relying on scroll timing.

**Overclaims corrected in this pass.** `QA-REPORT.md` said mobile was tested at "emulated viewports
(320–1920 px)" and `ARCHITECTURE.md` listed nine tested breakpoints, but the suite only
screenshotted four widths and asserted nothing about overflow. It now sweeps ten widths and fails on
sideways overflow. `ARCHITECTURE.md` also showed the motion tokens on `:root` although they live on
`.evpx-root`.

**Overclaims corrected in 0.4.0.** The Element Studio bridge doc promised a "10-minute process" per widget to
get native controls, and ended by admitting it had never been done; it also said Element Studio's format was
undocumented and therefore native elements were out of reach. Both were wrong in the way that mattered: a
Breakdance element is a plain PHP class, and the plugin now ships them. `QA-REPORT.md` and `ARCHITECTURE.md`
had said "custom widgets appear in Breakdance" met by the Shortcode element alone; the docs now say what
each route is.

**Pass 6 — a design pass (0.5.0)**

Found by auditing the running pages, then by drawing the new design and measuring it. Each is guarded by a test that
was run against the broken state and seen to fail. 34–41 were in 0.4.0 as released; 42–44 were introduced by the
redesign itself and caught before it shipped.

34. **Two tokens were read and never defined.** FAQ questions had no vertical padding and the Flow heading touched
    its steps, because the stylesheet used `var(--evpx-space-5)` and `var(--evpx-space-10)`. An undefined custom
    property makes the declaration invalid at computed-value time and the property silently falls back, so no
    browser complains. `tests/css-check.mjs` now fails on any token that is read and not defined; against 0.4.0's
    stylesheet it fails with exactly those two.
35. **A stripe of page colour showed between two widgets** in a block theme: the theme's block gap puts a margin
    on every child of a post's content, and a widget is one. A widget now brings its own rhythm and takes none.
36. **The Hero and the FAQ sat off the grid.** Both were centred, narrow columns with left-aligned text, so their
    text began well to the right of every section under them. They now share one container; the interaction suite
    measures that the hero, the first section and the FAQ intro start on the same left edge.
37. **A mouse click drew the host theme's `:focus` outline** (Twenty Twenty-Five's black box around an accordion
    header or a tab after it was clicked). Cleared for pointer focus; a keyboard still gets the copper ring, and both
    are checked.
38. **The comparison's captions could wrap** ("Best for" over two lines) when the value beside it was long: the
    caption was allowed to shrink. It keeps its width now and the value wraps instead.
39. **The Hero's "Follow system" never followed the system.** The page-wide theme switch had a
    `prefers-color-scheme` rule; the Hero's own mode (split out in bug 2) did not, so "auto" was always dark.
    Checked with the browser's colour scheme set to each.
40. **"Media above text" put the media below the text.** The template prints the text first and the stacked layout
    had no order. The check measures the media's bottom edge against the text's top.
41. **The reading-progress bar was a 645 px column, not a bar across the window** (since 0.2.0, on any constrained
    block theme). The bar is `position: fixed` with `left: 0; right: 0`, but a constrained-layout theme caps and centres
    every child of the post content, so on Twenty Twenty-Five it sat 398 px in and 645 px wide. Nothing had measured
    its box, only that its fill grew. Found by accident, as a copper line running through a heading in a tall-element
    screenshot, where a fixed element is painted mid-page. `width: 100%; max-width: none` fixes it without `!important`
    (auto margins split no free space), and `qa.mjs` now asserts the track is as wide as the window.
42. **The new comparison plate overflowed a 320 px page.** The value column could not shrink below its longest word
    ("Highway corridors, fleet depots, retail"), so it pushed the plate past its box. It was the existing overflow
    sweep on the Breakdance page that showed it, not a check written for it; a value now wraps, and a row stacks its
    caption above its value in a narrow plate.
43. **The hero could flash for one frame, and a late GSAP replayed its entrance.** The new entrance is a timeline,
    and in GSAP a tween positioned later in a timeline does not apply its starting state at once, so on the frame
    where the hold-back was released the hero could paint, vanish and come back. Separately, if GSAP arrived after the
    stylesheet's own failsafe (1.6 s) had already revealed the hero, the entrance played again over a hero that was
    already there. The starting state is now set explicitly and the late case is guarded. Found by recording frames;
    `qa.mjs` now loads GSAP late twice, at 1.2 s and at 2.8 s, and the check fails without the fix.
44. **A FAQ row jumped when it opened and again when it closed.** The panel animated its height but not the padding
    under the answer, so the row gained that padding (24 px) in the frame the click landed and lost it in the frame the
    panel was hidden. The padding now travels with the height, and toggling again cancels a running animation instead of
    stacking a second one. `interaction-qa.mjs` reads the row's height synchronously after the click; with the padding
    left out it reads 113 px against 89 px.

**Pass 7 — a second design pass (0.6.0)**

The pass began with an audit of the running 0.5.0 pages against a UI/UX rule set (the `ui-ux-pro-max` skill in
`.claude/skills/`; its dataset text is a recommendation, and the repository's own rules, the brief and the tokens
were kept over it wherever they differed). Eight findings against what already existed, all fixed and, where a
check can see them, guarded by `a11y-qa.mjs`: **(a)** the keyboard ring was one colour on every surface, and copper
on a copper CTA is invisible (now `--evpx-focus-ring`, set per surface); **(b)** the comparison's selected tab
was told apart by depth alone, was 42 px tall and set no `touch-action` (a copper rule along its foot, 44 px,
`manipulation`); **(c)** nothing handled forced colours, where every shadow the design is made of disappears
(§18 of the stylesheet); **(d)** the FAQ answer ran to about 80 characters a line (it keeps the reading measure);
**(e)** three rules set text at 11 px (all 12 px or more; a check walks every text node); **(f)** the font sizes were
literals scattered through 24 rules (all on the nine-step scale); **(g)** the comparison's bar and outline were
told apart by colour and outline alone (a key, hidden from assistive technology, as the bar is); **(h)** scroll
reveals travelled 28 px, 90 ms apart (18 px, 60 ms).

Then the seven defects in the new work:

45. **The hero drawing's neon horizon painted nothing.** It was a horizontal `<path>` stroked with a gradient in
    object-bounding-box units; a horizontal line has no height, so per the SVG spec the gradient is not applied and
    the stroke is not painted. Found by looking at the render: there was no line. It is now a 1.5-unit rect filled
    with the gradient. *Guard: `layout-qa.mjs` fails any shape painted with a bounding-box gradient that sits in a box with
    no height or width (it was watched catching one injected into a drawing).*
46. **The band of light that crosses the car was invisible.** The rule that hides the still drawing's moving light
    said `fill: none` on the class, and CSS beats the shape's `fill` presentation attribute, so the gradient never
    applied. Found in a filmstrip of the entrance. *Guard: `interaction-qa.mjs` asserts that the scan band's computed
    fill is the gradient (`url(...)`), and that it animates.*
47. **A label in the hero drawing ran under the copy at every desktop width.** "CCS2 · 150 kW" was end-anchored to
    the left of the charging port, which put it across the last line of the summary at 1440 px (and the charger 7 px
    from the copy column). The label now sits above the car, the charger moved right, and the copy gives up width
    beside the drawing (`min(34rem, 46cqi)`). *Guard: `interaction-qa.mjs` measures the drawing's labels, charger and
    gauge against the copy's boxes at seven widths; it fails at the five desktop ones with the original label
    placement restored, which is how it was checked.*
48. **In the tablet band, the drawing's floor, horizon and glow ended in vertical edges.** They were drawn 40 units
    past the drawing's box, which is enough on desktop (the hero clips there) and not when the drawing is centred in a
    band wider than itself. They now extend 400 units and fade before the edge. *Guard: `layout-qa.mjs` measures how far the floor, horizon and glow
    fall short of the drawing's box at six widths; with the old 40-unit extents it fails at 700 px by 23 px a side.*
49. **The explorer's chart labels were 9 px.** Text inside an SVG scales with the drawing; at the chart's real
    width (about 480 px of a 640-unit drawing) a 12-unit label is 9 px. The script now tells the chart its scale and
    sets the labels to 12.5 px on the screen, and draws fewer time ticks when it is small. The same fix is applied to
    every drawing's labels. *Guard: `explorer-qa.mjs` measures the labels at six widths.*
50. **The explorer overflowed a 220 px box** (a phone inside a Breakdance Section with 50 px padding of its own).
    The comparison rows' two fixed columns and their gaps added up to 180 px, wider than the space, and a grid track
    sized to its content pushed the whole column out. Every single-column grid in the explorer is now
    `minmax(0, 1fr)`, and below 20rem the charger choices become a list, each row's bar drops under its name, the
    figures stack and the chart is left out. Found by the existing 320 px overflow sweep on the Breakdance media page,
    not by a check written for the explorer. *Guard: that sweep, and `explorer-qa.mjs` at 320 px.*
51. **The explorer's sentence could break between "122" and "km".** The figure and its unit were separate words in
    the translated sentence. They are now one value joined with a no-break space, on the server and in the script
    alike. *Guard: `widget-render-check.php`.*

52. **In the tablet band the hero's state-of-charge gauge was a ghost.** The band's top edge is softened by a mask
    that ran to 32% of the band's height; the gauge stands in the top quarter, so it was drawn at about 40% strength and
    "78%" read as grey on dark. Found by looking at the 768 px capture (the computed opacity of every element in the gauge
    was 1, because the mask is on the box that holds the drawing). The mask is 7%, enough for the cut edge and not the
    gauge. *Guard: `layout-qa.mjs` screenshots the figure and requires its brightest pixel to be as bright as its type,
    at 820 and 1440 px: 119 of 255 with the old mask, 234 with the new.*
53. **The DC cabinet's readout said "KW".** Every label in a drawing is set in capitals; the unit of the cabinet's
    display was written outside the tag that switches that off, so it read "KW" (kelvin-watts, if it were anything) and, at
    the larger label size a phone gets, sat tight under its number. It is a unit like the others now, with room. *Guard:
    `layout-qa.mjs` walks every text node in every drawing that holds kW, kWh or Hz and requires its computed
    `text-transform` to be `none`.*
54. **A Related row of three left its third card alone in the corner at a tablet width.** Two to a row from 40rem, three
    only from 64rem, so 768 px gave a row of two and a row of one, with a hole beside it. The odd card now takes the whole
    row, its picture beside its text (and a single card does the same). *Guard: `layout-qa.mjs` builds the widget's own
    markup for one, two and three articles, so the check does not depend on how many articles a site has, and measures
    the rows at 820, 1366 and 390 px.*

Test-side, in the same pass: the overflow probe now skips the inside of a drawing (its shapes may run past their
box on purpose; the widget clips them, and the drawing's own `<svg>` box is still held to the widget's) and the hero's
drawing; the a11y probe first measured a focus ring against the control's own fill instead of the surface behind it
(a primary button read 2.3:1 against itself); several assertions were rewritten because the design changed, not
because it broke (the byline's markup now has an icon in it, the arrow is an SVG, the ruler has a key, a mixed Related
row has a drawing, the demo article has thirteen widgets); and the FAQ's plus and minus are two icons now, so the check
reads their opacity.

The first run of the new composition checks under Twenty Twenty-Five crashed, not failed: the guard screenshotted the hero's
gauge where the block theme's header had pushed it below the window, and Playwright refuses a clip outside the window (the
runs under the Zero theme, which has no header, had hidden it). It scrolls the figure into view first now.

Run under Breakdance's Zero theme, which has no footer, so the flow reaches the end of a scroll sooner, two checks
failed that were racing rather than finding anything. axe measured the last step of the flow while it was still fading
in: text at partial opacity is measured at its blended colour (`#9aa0a8` on `#eef0f3`, 2.3:1) where the colour it comes
to rest on is `#626b76` (verified at 4.5:1 or more by `contrast-check.mjs`). The helper now waits for every finite
animation and transition to finish before it runs axe. And the progress-bar check read the bar 600 ms after scrolling
to the end of a page that was still loading pictures (0.92, not 0.95); it now scrolls to the end again and gives the bar
up to four seconds. A third failure was not a check at all: the first admin page after a theme is added or removed
waits on WordPress's update check, which takes about 30 s to fail where there is no route to wordpress.org, and that is
longer than the builder suites wait for a login (it had crashed the builder half of a suite in three earlier runs and
passed when the same suite was run again). `setup.sh` now has the test site refuse external requests at once.

A design-choice correction rather than a bug: the first draft of 0.5.0 used Newsreader for the headings, and the
project's own design notes list it as a face models reach for by default. The choice was re-run over twelve candidates
in the real widgets and moved to Spectral (rationale in `docs/ARCHITECTURE.md`, section 5.3). The font check in
`qa.mjs` now asks for Spectral.

Test-side, in the same pass: "scenario cards align in grid rows" contradicted the cards' deliberate stagger, so it
became "the outer two share a top edge and the middle steps down". A first full run of the matrix was polluted by
other work sharing the machine (the timing-sensitive checks in `qa.mjs` and `interaction-qa.mjs` fail when the CPU is
contended); it was thrown away and the matrix re-run on an idle machine. Run those two on an idle machine.

**Pass 8 — two weak spots (0.6.1)**

The 0.6.0 review named two things it was not happy with: the hero's byline and the Technical Flow, the plainest widget on
the page. Fixing them turned up two more defects, both in what already existed.

55. **The hero byline stranded "Reading time" alone on a second row beside the drawing.** Author, date and reading time
    are a flex strip, and beside the drawing the copy column is 544 px at most; with a long author name the three needed
    546 px at 1440, so the last wrapped under the first, and at every narrower width beside the drawing (1100, 1024) it
    did so by more. (The phone already wrapped correctly, by luck: the author, then the other two.) The date and the
    reading time are one wrapper now, so a byline that will not fit is the author over the pair. It is not a fixed
    two-row layout: a short author still lets all three sit on one line. *Guard: `layout-qa.mjs` reads the byline's rows
    at seven widths; with the old markup it fails at 1100 and 1024 px ("Author + Published / Reading time").*
56. **The flow's steps left in the wrong order when it was reached soon after the page loaded.** With motion on, each
    step is hidden until the flow is seen, by a transition whose delay is the step's number times 0.4 s, and that delay
    applied on the way out as well as the way in: the first step went at once and the fifth 1.6 s later, so a flow reached
    within two seconds of loading showed "Battery" and "Vehicle" and nothing between. Found by filming the new sequence,
    where the order was plainly backwards; the old flow had it too. The stagger is on the way in only. *Guard:
    `interaction-qa.mjs` counts the steps still showing 400 ms after load: six with the old rule, none now.*

57. **The stacked flow overflowed a 220 px column.** Stacked is the base layout, and its list was a grid with no column
    template, so its one implicit track was sized to the widest step: a socket, a gap and "Site Infrastructure" set in
    the display face need about 240 px, and a phone inside a Breakdance Section with 50 px of its own padding leaves the
    widget 220. Found by the existing 320 px overflow sweep of the Breakdance pages, not by a check written for the flow
    (the demo page has no Section around it, so its own 320 px sweep passed). The track is `minmax(0, 1fr)`, a name may
    break where it must, and below 20rem the socket, its gap and the name give up a little. *Guard: `layout-qa.mjs` puts
    the flow in a 220 px box and runs the overflow probe; it fails with all three measures taken out (`flow__num` and
    `flow__label` 150–272 px in a widget that ends at 270).*

The redesign itself: the flow is a rail (a groove, and the copper drawn along it as light) with larger sockets, on a dark
blueprint band by default and on the light surface with `variant="light"`. Its geometry is arithmetic on a row gap and a
socket size, so `layout-qa.mjs` measures each drawn rail against the sockets it should join (start, length and centring,
across at 1440, 1100 and 768 px and down at 390, 320 and when set vertical) and it was watched failing with the vertical
rail shortened. The refactor that came with it moved the dark bands' shared glow and grid into one rule each; the explorer
and the closing panel were rendered before and after and are pixel for pixel the same at 1440 and 390 px.

58. **The hero's button went nowhere on a page built with native elements.** "Jump to the decision framework" links to
    `#decision`. The shortcode article supplied that target with a hand-written `<span id="decision"></span>` above the
    decision factors; turning the same article into native elements keeps only the EV shortcodes, so the span was dropped and
    the button pointed at nothing. Every Breakdance fixture page since 0.4.0 had a dead button, and nothing looked at it.
    Found while checking the example page the plugin now makes. The decision factors carry the id (`anchor="decision"`, a
    control every section widget has now), so a shortcode, a block and a native element agree. *Guard: the example-page
    check counts `#decision` on the post and on the page (once each) and fails on both with the id left out;
    `widget-render-check.php` and `breakdance-real-check.php` check the anchor on every section widget, through a shortcode
    and through a native element.*
59. **The first version of the example page printed its title twice.** Its hero was an `h1`, on the reasoning that a page has
    no title above it. Under Twenty Twenty-Five it does: the theme prints the page's title as an `h1` above the content, so the
    page had two `h1`s and its title twice. Breakdance's own Zero theme prints none. The hero is an `h1` under Zero and an `h2`
    under any other theme now, as the post's hero already is. *Guard: the browser check's "exactly one h1" (the first run
    caught it), and a logic check that forces the theme (`breakdance-zero`, `twentytwentyfive`, `astra`) and reads the tag
    saved in the tree; with the hero always an `h1` both fail.*

Test-side, in the same pass: the check that presses **Activate** on the Plugins screen turns WordPress's own
"could not reach wordpress.org" warning off for that step (this QA site has no route out, and a warning printed first
stops the redirect that follows an activation, so the page never arrived); a logged-in page's request for the admin
bar's avatar is refused, so it cannot hold a page load open; `grep -q` on a pipe under `pipefail` reported a match as a
miss (it closes the pipe early), so the greps read a here-string; and the first draft looked for the notice on the
Plugins screen after logging in, but the login had already landed on the dashboard, which had shown and consumed it.

## Not verified

- **The example articles, in the ways not tried.** Run under Twenty Twenty-Five, plus the theme rule for the hero
  forced at the logic level for Breakdance's Zero theme and one classic theme. Not run: multisite (network activation
  makes them on the main site only, by design, unexercised), a PHP older than 8.4 (the code avoids anything after 7.4,
  which was checked by reading and PHPCompatibilityWP, not by running it), and an *upgrade in place* of an active
  0.6.x, which by WordPress's own rule does not fire the activation hook (deactivate and activate to get the examples).
  The Breakdance example on a theme that constrains content width (Twenty Twenty-Five's 645 px column) sits in that
  column; under Zero, or a Breakdance template, its Sections run edge to edge.

- **The site, in the ways not tried.** The six pages were driven in Chromium, as native Breakdance elements and as
  shortcodes, under Twenty Twenty-Five. Not run: under Breakdance's Zero theme or a classic theme (the template does not
  depend on one, but it was not exercised there), multisite, a site that already has pages at `/about/` and the rest (the
  addresses are looked up, which the render checks cover, but the header of such a site was not driven), and the builder
  canvas for the ten new elements (the Add panel lists all twenty and the elements render in it; editing each of the new ones
  in the builder was not done).
- **Mail.** The contact form was proved up to `wp_mail()` (caught by a test plugin): who it is sent to, from whom, with what.
  Whether a given host delivers it is the host's; the form tells the visitor when `wp_mail()` says no.
- **The scenes and the weather on a phone in a hand.** The pointer parallax needs a fine pointer and is withheld on touch;
  the canvas weather is measured only for not erroring. How the five scenes feel on a real phone's GPU, and what they cost on
  a low-end one, was not measured.
- **`<dialog>`, `scroll-snap` and `clip-path` in Safari and Firefox.** The header's search is a native `<dialog>` and the
  rails snap; both were seen in Chromium only.

- **The reference the design was meant to beat.** The site the brief points at could not be opened from this
  environment (its host is blocked), so it was never seen. "More beautiful than the reference" is a judgement about
  the running pages against the brief's own terms (effects, hover states, type), not a side-by-side. Send
  screenshots of it, or allow the host, and the comparison can be made properly.
- **A real phone, and touch.** Mobile is emulated viewports; the touch checks run Chromium with touch and no hover
  media, which proves the decoration and hover states are withheld, not how a thumb feels on the page.
- **Right-to-left.** The comparison scale and the flow timeline are positioned with physical left and right.
- **The fonts on a slow connection.** They are self-hosted with `font-display: swap`; the swap itself, and how
  visible it is on a throttled network, was not measured.
- **Element Studio's GUI.** It was not operated. The plugin registers real save locations and Breakdance loads
  elements saved there (probe above), but creating or editing an element in the Element Studio interface itself
  has not been exercised.
- **Breakdance in other configurations.** Tested: Breakdance 2.8.3 as shipped, without a licence key, under
  Twenty Twenty-Five, Breakdance's Zero theme and a bare classic theme; widgets as native elements and in
  Shortcode elements; a footer and a Single Post template. Not tested: a licensed Breakdance (Pro features),
  Breakdance's header templates and popups (a header goes through the same code as a footer, but wasn't
  run), Oxygen mode (the elements declare they are available there), WooCommerce, other Breakdance versions,
  the builder's responsive breakpoints (the elements declare none), right-to-left sites.
- **Photography.** Pictures were tested with generated images — gradients with a frame at the edges, including
  a near-white sky as the worst case for white type — not photographs. Crop, `srcset`, alt text, the media
  library, empty rows and legibility are covered; how real photographs look is not. `docs/MEDIA-BRIEF.md` is
  the shot list.
- **Browsers other than Chromium.** Firefox, Safari and real phones were not available; mobile is emulated
  viewports. Three things in this release lean on behaviour that differs between engines and were seen in Chromium
  only: CSS animations on the shapes an SVG `<use>` instantiates (the car's wheels, the reflection), the blurred
  halo (an SVG filter, chosen because Safari ignores a CSS one on shapes inside an `<svg>`, but not looked at in
  Safari), and `mask-image` on the hero's box. The pixel checks in `layout-qa.mjs` are Chromium's rendering. Container queries need Chrome 105 / Safari 16 / Firefox 110 or newer (older browsers get the
  single-column layout); the rule that keeps a widget from collapsing inside Breakdance's Rich Text element
  also needs `:has()` (Firefox 121); `@media (scripting: none)` needs Chrome 120 / Firefox 113 / Safari 17.
- **PHP versions.** PHP 8.2 and 8.4 ran the plugin; 7.4 (the declared minimum) was checked by syntax and
  PHPCompatibilityWP only, not executed.
- **cdnjs delivery itself.** GSAP was tested at the plugin's default version (3.12.5) but served locally; the
  CDN URL is unreachable from here.
- **Caching / minification / CDN plugins.**

## Reproducing

```
# Static, no WordPress needed
node tests/css-check.mjs
node tests/contrast-check.mjs

# Any WordPress page
bash tests/docker/setup.sh                     # or: EVPX_GSAP_DIR=… bash tests/docker/setup.sh
bash tests/docker/widget-render-check.sh
node tests/playwright/qa.mjs "$(cat tests/docker/.demo-url)"
node tests/playwright/interaction-qa.mjs "$(cat tests/docker/.demo-url)"          # run qa and this one on an idle machine
node tests/playwright/explorer-qa.mjs "$(cat tests/docker/.demo-url)"
node tests/playwright/a11y-qa.mjs "$(cat tests/docker/.demo-url)"
node tests/playwright/layout-qa.mjs "$(cat tests/docker/.demo-url)"

# A real Breakdance (you supply the licensed ZIP; it is never committed)
EVPX_BREAKDANCE_ZIP=/path/to/breakdance-2.8.3.zip EVPX_GSAP_DIR=… bash tests/docker/setup.sh
bash tests/docker/breakdance-real-check.sh
bash tests/docker/breakdance-page.sh
node tests/playwright/breakdance-qa.mjs "$(cat tests/docker/.breakdance-url)"          # Shortcode elements
node tests/playwright/breakdance-qa.mjs "$(cat tests/docker/.breakdance-native-url)"   # native elements, builder included
node tests/playwright/breakdance-qa.mjs "$(cat tests/docker/.breakdance-full-url)"     # full-width Sections, no padding
bash tests/docker/builder-save-check.sh                                                # dropdown, Add panel, Save, reopen
bash tests/docker/media-pages.sh
node tests/playwright/media-qa.mjs "$(cat tests/docker/.media-url)" "$(cat tests/docker/.media-native-url)" \
     "$(cat tests/docker/.media-mixed-url)" "$(cat tests/docker/.media-bright-url)"
bash tests/docker/template-check.sh                                                    # three themes
bash tests/docker/isolation-check.sh                                                   # two themes
bash tests/docker/example-pages-check.sh                                               # the examples made on activation
EVPX_PLUGIN_DIR=evpx-zip bash tests/docker/example-pages-check.sh                      # the same against the built ZIP, installed as evpx-zip
bash tests/docker/site-render-check.sh                                                 # what the site widgets print
bash tests/docker/contact-form-check.sh                                                # the contact form over HTTP
bash tests/docker/site-pages-check.sh                                                  # the six site pages, both ways, in a browser
EVPX_QA_ONLY=live node tests/playwright/site-qa.mjs out home=URL about=URL …            # one part of the browser suite (still, live, contact, search)

composer install && composer lint
bash tests/build-zip.sh
```

The browser scripts and the `.sh` checks need Playwright (and, for axe, `axe-core`) resolvable from
`tests/playwright/`, and `PLAYWRIGHT_CHROMIUM_PATH` if Chromium isn't where Playwright looks. The template and
isolation checks install two extra themes (the bare classic one from this repository and Breakdance's own
Zero theme, copied out of the Breakdance plugin), and the isolation check switches this plugin off and on
again; each puts everything back when it ends, also when a check fails. `setup.sh` has the test site refuse external
HTTP requests (`EVPX_ALLOW_EXTERNAL_HTTP=1` leaves them on): where wordpress.org cannot be reached, WordPress's update
check would otherwise stall the first admin page after a theme is added or removed by about 30 seconds.
