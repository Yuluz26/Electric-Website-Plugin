# Widget & control reference

Twenty widgets you place on a page, plus eight item elements that only make sense inside their container
(Scenario Card, Decision Factor, FAQ Item, Stat, Service, Process Step, Project, Quote). The first ten are for
articles; the other ten (Page Hero, Site Header, Search, Site Footer, Stats, Services, Process, Projects, Quotes,
Contact) build the pages of a site, and are documented under "Site widgets" below.

Every element works as a shortcode (`[evpx_xxx attr="value"]`) and as a Gutenberg block
(`EV Charging Experience` category in the block inserter); the twenty widgets are also native Breakdance
elements (**EV Charging** category in the Add panel, see `docs/BREAKDANCE.md`), where the item elements
become the rows of a repeater. All of them use the exact same PHP renderer — output is identical. The keys
below are the shortcode and block attributes; a native element has a control for each of them.

Groups map to the editor organization the PRD specifies: Content, Media,
Layout, Visual, Motion, Responsive, Advanced. In Breakdance each group is a section of the element's panel.

## Spacing — every section widget

`spacing` (select: default / compact / none, layout) sets the vertical rhythm of a section widget: Section,
Comparison, Explorer, Scenario Cards, Technical Flow, Decision Factors, FAQ, Related Articles, CTA, and the site's
Stats, Services, Process, Projects, Quotes, Contact and Footer. `default` is
the full rhythm, `compact` about half, `none` removes it — use `none` inside a Breakdance Section that
already has its own padding. It sets the `--evpx-section-y` token on the widget, so
`.evpx-root { --evpx-section-y: 0; }` in your own CSS does the same. The Hero has no rhythm to set: it has its
own minimum height.

## Anchor — every section widget

`anchor` (text, advanced) is the HTML id of the widget's root, so a link elsewhere on the page can point at it:
`anchor="decision"` on the Decision Factors and `cta_url="#decision"` on the hero. Letters, digits, hyphens and
underscores only (anything else is dropped, so the id survives being pasted into a URL). It is the same in a
shortcode, a block and a native element; Breakdance's own **ID** control, which sits on its Section, is a second way
to do it there. Empty by default: no `id` is printed.

## EV Article Hero — `[evpx_hero]` / `evpx/hero`

| Key | Type | Group | Default |
|---|---|---|---|
| `category` | text | content | "EV Infrastructure" |
| `title` | text | content | "Choosing AC or DC Charging for Your Site" |
| `excerpt` | textarea | content | — |
| `author` | text | content | — |
| `date` | text | content | — |
| `reading_time` | text | content | auto-calculated when blank |
| `media` | image | media | — |
| `media_alt` | text | media | — |
| `cta_label` / `cta_url` | text / url | content | — |
| `visual_mode` | select: dark/light/auto | visual | dark |
| `animate` | toggle | motion | true |
| `title_tag` | select: h1/h2 | advanced | h1 — set h2 if your theme already prints the page title as h1 |
| `progress_bar` | toggle | motion | true — fixed top-of-viewport bar tracking scroll through the whole page |
| `artwork` | select: schematic/none | visual | schematic — the drawing shown when there is no picture |

The author, date and reading time are set as a small readout under the summary, each an icon and a caption
(Author, Published, Reading time) over its value. Without a picture the hero is a dark instrument panel: a faint
blueprint grid, corner marks, a light that follows a fine pointer, and, at the right, a drawing of a car on a
charger with a state-of-charge ring (`artwork`; `none` leaves the grid alone). With motion the drawing's lines
draw themselves in, the light comes on, a band of light crosses the car once and a pulse runs along the cable;
without motion it is finished and still. Below 64rem the drawing is a band under the copy; on a phone it is the
car and the charger alone. With a picture, the picture sits under a scrim that keeps the type above 4.5:1 and drifts
a little slower than the page as you scroll, and there is no drawing. Either way a copper line runs along the foot
of the hero and, with motion, fills once as the page arrives. `visual_mode` `auto` follows the visitor's light or
dark preference; on a light hero the drawing's line work is ink and the car stays dark.

## EV Section — `[evpx_section]` / `evpx/section`

General-purpose editorial block. `eyebrow`, `heading`, `body` (text), `figure` and `figure_label`
(text: an optional key figure set large, with a caption under it), `media`/`media_alt` (media), `layout` select
(media-right/media-left/stacked/text-only, layout), `surface` select (flat/raised/recessed, visual),
`artwork` select (media: none, or one of the drawings below, used when there is no picture),
`animate` toggle (motion).

Without a picture the section is a split: the heading and figure on one side, the copy on the other
(`media-left` mirrors it, so consecutive sections can alternate). With a picture it is the usual
two columns, and `stacked` puts the picture above the text. When the copy runs to more than one
paragraph the first leads, set slightly larger. A chosen drawing takes the picture's place, on its own dark panel:
`charge-curve` (power against time, AC against DC), `wallbox` (an AC wall box and its wave), `dc-cabinet` (a DC
fast charger and its CCS plug), `grid-path` (pylon, transformer, switchboard, charger), `wave-ac` and `wave-dc`.
A drawing is decoration (hidden from assistive technology) and carries no claim the copy has to support. Use the key figure for one number that carries the section
("7–22 kW", "under 30 min"); leave it empty for none. It is a figure, not a claim: the section's
own copy has to support it.

## EV AC/DC Comparison — `[evpx_comparison]` / `evpx/comparison` (signature component)

`ac_title`, `ac_description`, `ac_power_range`, `ac_dwell_label`,
`ac_best_for` and the matching `dc_*` set (content). `mode` select
(toggle/side-by-side, layout). `accent_treatment` select (split/neutral,
visual). `animation_intensity` select (standard/subtle/off, motion).
`mobile_mode` select (tabs/stacked, responsive), `art` toggle (visual: a drawing under each panel's copy, a wave
for AC and a level for DC; default on).

Each panel is its copy beside a raised plate that reads like a spec sheet: the power range set large, dwell
time, best use. When both power ranges are kilowatt figures ("7–22 kW", "50–350+ kW", "22 kW"), the plate also
draws the range on a bar scaled to the larger of the two, with the other panel's range outlined on the same scale,
so the difference between AC and DC is shown to scale rather than only described. A range that isn't
plainly kilowatts ("CCS2 50-350 kW", "up to 22 kW at 230 V") is not read: that panel pair simply has no
bar, and the figure still stands on its own. A small key under the bar says which is which (this panel's range, the
other's). In toggle mode a raised thumb slides between the tabs, each tab has its icon (a wave for AC, a bolt for DC)
and the selected one has a copper rule along its foot as well as its depth, so it reads without seeing shadows.

## EV Charging Explorer — `[evpx_explorer]` / `evpx/explorer`

The one interactive: how long a car stays, which charger, and what reaches the battery. A dark panel with the
controls on the left (a dwell-time slider from 15 minutes to 12 hours, bent toward the short stays where the
differences are, with five presets; and a choice of AC 7 kW, AC 22 kW, DC 50 kW, DC 150 kW, DC 350 kW) and the
readout sunk into a well on the right: energy added, range added, the battery bar (arrival charge to final charge),
a chart of power against time on the chosen charger with the dwell time marked, and one or two sentences that say what
it means ("In 2 h, a 22 kW charger adds about 22 kWh: 122 km of range. The car takes at most 11 kW, so any power above
that goes unused."). Under the controls, the same dwell time on every charger, the chosen one lit.

The page arrives with the default answer already worked out on the server, so it reads with no script (the controls,
which would do nothing, and the chart are then left out); the script makes it live. The model is the same in PHP
(`EVPX\Support\ChargingModel`) and in `assets/js/evpx.js`, and `tests/playwright/explorer-qa.mjs` holds the two to
each other over a grid of cars, chargers and dwell times. It is illustrative, and the widget says so under the panel:

- AC: the charger gives its rating, but the car's onboard charger caps what it accepts (which is why a 22 kW wall box
  so often delivers 11 kW or less).
- DC: the car accepts up to its peak in full up to half full, then less and less (35% of the peak at 80%, 10% at
  100%); the charger gives the smaller of its rating and what the car accepts, so a 350 kW charger adds nothing for a
  150 kW car, and the chart draws the charger's rating as a dashed line the curve never reaches.

| Key | Type | Group | Default |
|---|---|---|---|
| `eyebrow`, `heading`, `intro` | text / text / textarea | content | "Try it", "What does your dwell time buy?", … |
| `dwell` | select: 30 min / 1 h / 2 h / 4 h / 8 h | content | 2 h |
| `charger` | select: ac-7 / ac-22 / dc-50 / dc-150 / dc-350 | content | ac-22 |
| `battery` | number, kWh (held to 20–150) | content | 60 |
| `start_soc` | number, % (0–90) | content | 20 |
| `ac_limit` | number, kW (3–22): the car's onboard AC charger | content | 11 |
| `dc_peak` | number, kW (20–400): the car's peak DC power | content | 150 |
| `consumption` | number, kWh per 100 km (10–40) | content | 18 |
| `unit` | select: km / mi | content | km |
| `animate` | toggle | motion | true |

Numbers outside their range are held inside it; an unknown charger, dwell time or unit is the default. It is a dark
panel whatever the page around it (it sets `data-evpx-theme="dark"` itself). A box narrower than 20rem (a phone in
a builder Section with padding of its own) shows the charger choices as a list, drops the bar under each name and
leaves the chart out, since a curve that small cannot be read.

## EV Scenario Cards — `[evpx_scenarios]` / `evpx/scenarios` (container)

`eyebrow`, `heading` (content), `columns` select (2/3, layout), `animate`
toggle (motion). Holds one or more **EV Scenario Card** children. On a wide box the second column steps
down, so the grid reads as a rhythm and not a table.

### EV Scenario Card — `[evpx_scenario_card]` / `evpx/scenario-card` (child)

Only meaningful nested inside Scenario Cards. `scenario` (small label),
`title`, `description`, `symbol` (select: automatic, none, or one of the icons; visual), `icon` (an image that replaces the
icon; media), `requirement`, `recommendation`, `cta_label`/`cta_url`. The icon sits in a small recessed socket beside the
label; *automatic* chooses it from the scenario's words (workplace, hotel, residential, fleet, retail, highway…) and
falls back to a charging station. The requirement and recommendation sit in a recessed well at the foot of the card, so
cards of different lengths line up on their answers.

## EV Technical Flow — `[evpx_flow]` / `evpx/flow`

Grid → Site → Charger → Vehicle → Battery, drawn as a rail: a groove cut into the surface with the copper drawn along
it as light, and on it a socket per step holding its icon, ringed in the glow, the step's number and its name
underneath. It is stacked by default (the name beside its socket, the rail running down between them), which is also
what a box narrower than 48rem gets; from 48rem a `horizontal` flow runs across, each name centred under its socket.
The `variant` control (visual) sets the look: **dark** (the default) is the article's schematic, a dark band with the
blueprint grid and the neon as itself, like the explorer and the closing panel; **light** keeps the page's surface.
The icons are chosen from the step names; if any one step's name is not one the plugin knows, no step has an icon and
every node is its number, since a row where only some have one would read as a mistake (`icons` toggle, visual: off
gives numbers). With motion the steps arrive in turn, the light runs along the rail toward the next and each socket
lights as it is reached. `heading` (content), `step1_label` … `step5_label` (content, defaults pre-filled), `direction`
select (horizontal/vertical, layout), `compact` toggle (layout), `variant` select (dark/light, visual), `icons` toggle
(visual), `animate` toggle (motion).

## EV FAQ — `[evpx_faq]` / `evpx/faq` (container)

`eyebrow`, `heading` (content), `schema_output` toggle (advanced — emits
FAQPage JSON-LD; turn off if an SEO plugin already manages FAQ schema on
this page), `animate` toggle (motion). Holds one or more **EV FAQ Item**
children.

### EV FAQ Item — `[evpx_faq_item]` / `evpx/faq-item` (child)

`question` (text), `answer` (textarea), `default_open` (toggle). Questions are rows on hairlines,
not cards; the toggle is a small raised knob holding a plus, which turns into a minus and is pressed in while its answer is open.

## EV Decision Factors — `[evpx_decision_factors]` / `evpx/decision-factors` (container)

`eyebrow`, `heading`, `intro` (content), `animate` toggle (motion). A numbered, deliberately
flat editorial list — the numbers come from a CSS counter, so reordering children renumbers
them. Holds one or more **EV Decision Factor** children.

### EV Decision Factor — `[evpx_decision_factor]` / `evpx/decision-factor` (child)

`title` (the factor), `description`, `question` (the "Ask" line) — content; `symbol` (select: automatic, none, or one of the icons; visual),
shown small before the title, chosen from its words when automatic.

## EV Related Articles — `[evpx_related]` / `evpx/related`

`eyebrow`, `heading` (content); `source` select (category / latest / manual, content);
`post_ids` (comma-separated, manual mode only); `post_type` (advanced; only public types are
accepted, otherwise falls back to `post`); `count` select 2/3 (layout); `animate` toggle
(motion). Lists real published posts, excluding the current page and password-protected
posts. With nothing to list, visitors see nothing and editors see a one-line note. An article with no featured
image is given a drawing (a different one each), so a row never has a blank tile beside a photograph; `art` toggle (visual,
default on) — with it off, a row where any article lacks a picture shows none at all, as a text-only row. Three
to a row from 64rem, two to a row from 40rem, one below that; at two to a row an odd card left over takes the whole row,
its picture beside its text.

## EV CTA — `[evpx_cta]` / `evpx/cta`

`eyebrow`, `title`, `body`, `button_label`/`button_url` (content), `media`
(media, only used by the `media` variant), `variant` select
(dark/accent/media, visual; dark is the default). A closing statement: the headline on the left, the supporting copy and
the button on the right (stacked in a narrow box). The `dark` variant is the hero's other end: the same blueprint grid,
a copper glow and a neon rule along its top edge. `accent` is the flat copper slab.

## Site widgets

These build the pages of a site rather than an article. They are full width (`alignfull`), take their colours from
the same tokens as the article widgets, and are the widgets the six site pages are made of (see "The site pages" in
`docs/INSTALLATION.md`). A widget that shows a picture takes a **photograph** where the design calls for one
(`media`), and until it has one draws a scene of its own: a layered SVG (a night forecourt, a road at dusk, a fast
charger, pylons, a plug) that moves with the pointer and the scroll. A photograph always replaces the scene. The
scenes are `station`, `highway`, `cabinet`, `grid` and `plug`; `none` gives the blueprint grid.

A line list is one item per line, `value | label` (Stage facts, Project figures, Contact details, Header links). Keep the
`|`; anything after it is the label or the address. An address that starts with `/` is looked up as a page of this
site (`/about/`), so a link survives a change of permalink structure and a move between domains.

### EV Page Hero — `[evpx_stage]` / `evpx/stage`

The full-width opening of a page. `eyebrow`, `title`, `lede`, `cta_label`/`cta_url`, `cta2_label`/`cta2_url`
(content); `facts` (content, up to four lines of `value | label`: a figure counts up when it arrives, and `24/7` or
`2 x 150 kW`, which are phrases, do not); `media`/`media_alt` (media: a photograph, which replaces the scene);
`scene` (media); `height` select (full / tall / compact, layout; full fills the window under a header, compact is for
an inner page); `overlay` select (light / medium / deep, visual: how much a photograph is darkened behind the type);
`animate` (motion); `title_tag` select (h1 / h2, advanced: an h1 by default, because a page has one). In `title`,
`*word*` is set in the accent, in italics: `Power that arrives *first*`.

Motion, when the visitor allows it: the scene's layers slide against one another as the pointer moves and the page
scrolls; a canvas of weather runs over it (rain on the forecourt, light streaks on the road, sparks at the cabinet,
motes on the grid, orbits round the plug); the figures count up. Without a script, or with reduced motion, it is
the finished still.

### EV Site Header — `[evpx_header]` / `evpx/header`

`brand` (blank: the site's name), `brand_url`, `logo` (media, replaces the mark); `links` (one `label | address` per
line; the page you are on is marked `aria-current`); `cta_label`/`cta_url`; `search` (a search button, and Ctrl/Cmd+K
or `/` opens it), `search_url` (the search page, default `/search/`; blank uses WordPress's own `?s=`); `sticky`
(stays at the top; in a Breakdance Section, which is its own containing block, make the Section sticky instead).
The search is a `<dialog>`: a labelled field, live results from the site's own REST search, arrow keys, Enter for
the full results page, Escape to close. On a narrow screen the links are behind a menu button.

### EV Search — `[evpx_search]` / `evpx/search`

The search results page. `heading`, `placeholder`, `suggestions` (`label | address` lines shown until something is
typed, and when nothing matches), `per_page`. Reads `q` (or WordPress's `s`) from the address, lists published pages
and posts with a count and a filter (all / pages / posts), and pages through them. Everything typed is escaped.

### EV Site Footer — `[evpx_footer]` / `evpx/footer`

`brand`, `blurb`, `links`, `contact` (one per line: an email and a phone number become links, anything else is
text), `legal` (blank: the copyright line with the year and the site's name), `spacing`.

### EV Stats — `[evpx_stats]` + `[evpx_stat]` / `evpx/stats` + `evpx/stat`

Figures on hairlines, one row when the box is wide and a column when it is not. Container: `eyebrow`, `heading`,
`intro`, `variant` select (dark / light), `animate`. Each stat: `value` (`150 kW`, `99.5%`, `13 weeks`: the number counts
up and the unit is set small beside it), `label`, `note`, `fill` (0 to 100; above 0 it draws a ring meter to that
value instead of an icon), `symbol` (an icon; automatic by default). Use rings or icons on every stat in a row, not
some of them, so the figures share a line.

### EV Services — `[evpx_services]` + `[evpx_service]` / `evpx/services` + `evpx/service`

A strip of panels, one open at a time: the open one is a picture with the argument over it, the others stand as
spines (a number, an icon, the name set upright) with their picture dimmed behind. Below 56rem of width it is an accordion
of full-width rows. Container: `eyebrow`, `heading`, `intro`, `animate`. Each service: `title`, `summary`, `points`
(one per line, up to six), `link_label`/`link_url`, `symbol`, `media` (a photograph behind the panel), `scene` (behind
it until there is a photograph). Click a shut panel anywhere to open it; the arrow keys move between the headings and
Enter opens one. Without a script every panel is open and stacked.

### EV Process — `[evpx_process]` + `[evpx_process_step]` / `evpx/process` + `evpx/process-step`

The steps of a job on a rail. Container: `eyebrow`, `heading`, `intro`, `animate`. Each step: `title`, `description`,
`duration`, `symbol`. As the page is read down it the rail fills, each socket lights when it is reached, and a running
count beside the heading (`03 / 05`) follows. The heading and count stay in view on a wide screen. With reduced motion, or
without a script, the rail is whole and the count is not shown.

### EV Projects — `[evpx_projects]` + `[evpx_project]` / `evpx/projects` + `evpx/project`

Work as a rail that runs off the edge of the page. Container: `eyebrow`, `heading`, `intro`, `animate`. Each
project: `name`, `place`, `sector`, `summary`, `metrics` (up to three `value | label` lines), `url` (makes the card a
link), `media` (a photograph, 5:4), `scene` (until there is one). Scroll it, drag it with a mouse, or use the buttons
(they appear only when there is something to scroll to); a bar shows how far along you are. The rail is a labelled,
focusable region, so a keyboard scrolls it with the arrow keys.

### EV Quotes — `[evpx_quotes]` + `[evpx_quote]` / `evpx/quotes` + `evpx/quote`

One quotation at a time, large, in the display face; the others wait in the same place so the section never changes
height. Container: `eyebrow`, `auto` (turn every seven seconds, only when motion is allowed, and never while the
pointer or focus is on it), `animate`. Each quote: `quote`, `name`, `role`. Arrows, dots (each a 24px target) and
the arrow keys move between them; once a visitor does, the rotation stops for good and a screen reader is told of each
change. One quote stays as printed. Without a script they are stacked and all read.

### EV Contact — `[evpx_contact]` / `evpx/contact`

`eyebrow`, `heading`, `intro`, `details` (one `label | value` per line; an email or phone number is a link),
`topics` (the choices in "About", one per line; blank: no choice), `button_label`, `to_email` (blank: the site's
admin address), `map` (a drawing of a charging network), `animate`. The form posts to `admin-post.php`; the message is
mailed with the sender as Reply-To, and the visitor comes back to the form with the outcome in words.

It has no nonce (an anonymous form's would only expire on a cached page). What stops a bot instead: a trap field a person
never sees; a signed time (sent in under three seconds, or a week after it was printed, is refused); the recipient
signed into the same token, so a form can only mail the address its widget was given; and five messages an hour from one
address. `tests/docker/contact-form-check.sh` drives every one of these over HTTP.

## How many eyebrows?

Every widget that has an `eyebrow` renders it only when it is filled in. The small labelled rule above a
heading is the easiest thing to overuse: on a page of a dozen sections, three or four are enough, and the
demo article uses three (the hero's category, the decision framework and the FAQ). A heading that needs
no label does not get one.

## Nesting in shortcode form

```
[evpx_scenarios eyebrow="In practice" heading="How this plays out" columns="3"]
[evpx_scenario_card scenario="Workplace" title="Employees, all day" description="..." requirement="..." recommendation="..."]
[evpx_scenario_card scenario="Hotel" title="Overnight guests" description="..." requirement="..." recommendation="..."]
[/evpx_scenarios]
```

`[evpx_faq]…[/evpx_faq]` with nested `[evpx_faq_item question="…" answer="…"]` and
`[evpx_decision_factors]…[/evpx_decision_factors]` with nested `[evpx_decision_factor …]`
follow the same pattern.

In a native Breakdance element there are no child elements: the items are the rows of the **Items**
repeater, with the same fields as the item widget's controls above. A row is rendered once it has its title
(the question, for an FAQ item); the empty row the repeater's "Add" button creates is left out.

The site widgets nest the same way: `[evpx_stats]` with `[evpx_stat]`, `[evpx_services]` with `[evpx_service]`,
`[evpx_process]` with `[evpx_process_step]`, `[evpx_projects]` with `[evpx_project]`, `[evpx_quotes]` with
`[evpx_quote]`. Each row's fields are the repeater's fields in a native element.
