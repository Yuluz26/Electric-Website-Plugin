# Using the plugin with Breakdance

Checked against Breakdance **2.8.3**, on the front end and inside the builder (`docs/QA-REPORT.md`
says what that covered and what it didn't). Other versions are untested.

## Three ways in

| | What you get | Use it when |
|---|---|---|
| **Native elements** | Twenty EV elements in the builder's Add panel, under **EV Charging**, with real controls, a repeater for items, Dynamic Data on text fields and live canvas rendering | You build pages in Breakdance. This is the default. |
| **Shortcode element** | Breakdance's own Shortcode element with `[evpx_hero …]` etc. | You already have shortcodes (the demo article, existing content), or want the exact same markup as on a non-Breakdance page |
| **Element Studio** | A save location, "EV Charging Elements", for elements you design yourself on top of the plugin's CSS | You want a variation the controls don't offer |

All three render through the same PHP renderer, so a native element, a shortcode and a block with the same
settings produce the same markup (asserted by `tests/docker/breakdance-real-check.sh`).

## The example page

Activating the plugin with Breakdance active makes a draft page, *Choosing AC or DC Charging for Your Site
(Breakdance)*: the whole demo article as native elements, each in its own full-width, no-padding Section (the
"Recommended setup" below). Open it with **Edit in Breakdance** and change the copy, or duplicate it for the next
article. If Breakdance is activated after the plugin, the page is added the next time an admin screen loads.
`docs/INSTALLATION.md`, "The example articles", says what is made, when, and how to have it made again. It is the
same tree the suites open in the builder (`src/Breakdance/Native/Tree.php` builds it; `tests/docker/example-pages-check.sh`
checks it).

## Native elements

Open the **Add** panel and search "EV", or scroll to the **EV Charging** category:

EV Article Hero · EV Section · EV AC/DC Comparison · EV Charging Explorer · EV Scenario Cards ·
EV Technical Flow · EV Decision Factors · EV FAQ · EV Related Articles · EV CTA

and, for the pages of a site (`docs/WIDGETS.md`, "Site widgets"):

EV Page Hero · EV Site Header · EV Search · EV Site Footer · EV Stats · EV Services · EV Process · EV Projects ·
EV Quotes · EV Contact

Stats, Services, Process, Projects and Quotes hold their rows (a stat, a panel, a step, a project, a quotation)
in an **Items** repeater, like the article widgets. Three things differ from a Breakdance element of your own: a
sticky **Site Header** cannot stay at the top inside its own Section (a Section is its own containing block), so make the
Section sticky in Breakdance's settings instead and turn the widget's **Sticky** off; the **Page Hero** is an `h1`,
so a page that has it should not also show its title; and a page can use the plugin's **EV full-width page** template
(`docs/INSTALLATION.md`, "The site pages") even when Breakdance built it, because Breakdance puts what it built into the
page's content and the template prints the content.

**Controls.** Every control the widget has (`docs/WIDGETS.md`) is here, grouped into sections in the order
the PRD asks for: Content, Media, Layout, Visual, Motion, Responsive, Advanced (only the groups a widget
uses appear). Changing a control re-renders the element in the canvas with one server-side render.

**Items.** Scenario Cards, Decision Factors and FAQ hold their items in an **Items** section: a repeater
whose rows are titled by their title or question, with an "Add EV FAQ Item"-style button. There are no child
elements to drag around; reorder rows in the repeater. A row shows on the page once it has its title or question:
the empty row "Add" creates isn't rendered (it would be an empty button, and an empty entry in the FAQ's
structured data).

**Starting copy.** A new element arrives with working copy and, for containers, sample rows, so it isn't an
empty box in the canvas. Replace it.

**Dynamic Data.** Text, textarea and URL controls, including the fields inside repeater rows, take
Breakdance's dynamic-data button (hover a field to see it): post title, excerpt, author, and the plugin's own
**EV Reading Time**, which is open to everyone, Pro licence or not. On the front end Breakdance resolves the value
before the element renders; in the builder's canvas the element resolves it itself, so the canvas shows the value
and not `[breakdance_dynamic …]`. Picture controls use the media library instead.

**Spacing.** Every section element has a **Spacing** control (default / compact / none) for its vertical
rhythm. Set it to *none* inside a Breakdance Section that already has its own padding. It sets
`--evpx-section-y` on the element, so the CSS token still works for anything you write yourself.

**In the builder.** Renders are static: no entrance animation, no scroll triggers, no progress bar. Two
elements show more than the front end does, so you can edit what you can't see: the comparison shows both
panels instead of a tab bar, and every FAQ answer is open. The front end behaves as configured.

**Assets.** The stylesheet and scripts are declared as the element's Breakdance dependencies, so Breakdance
prints them, once per page, wherever the element sits: a page, a template, a header, a footer. The stylesheet
goes in `<head>`; the scripts are deferred; the builder canvas gets only the stylesheet. WordPress doesn't
queue a second copy for the shortcodes and blocks on the same page (the Breakdance-and-WordPress overlap is
covered in `docs/ARCHITECTURE.md` §6).

**Don't rename them.** A page stores its element's PHP class name (`EVPX\Hero`, `EVPX\Faq`, …). The classes
in `src/Breakdance/Native/elements/` are therefore public API: renaming one leaves every page that uses it
with an element Breakdance can no longer find.

## The Shortcode element

Add Breakdance's **Shortcode** element and paste, for example,
`[evpx_comparison ac_power_range="7–22 kW" …]`. This route has been used since 0.1.0 and needs nothing else.
Breakdance's Shortcode element is full width by default, which the widgets rely on.

## Templates, headers and footers

Native elements work in Breakdance templates (a footer, a Single Post template) as well as pages. Shortcodes
in a Post Content element work too. Tested under Twenty Twenty-Five, Breakdance's own Zero theme and a bare
classic theme (`tests/docker/template-check.sh`). What differs:

- Under a block theme or Breakdance's own templates the body renders before `<head>` is printed, so
  the stylesheet is always in `<head>`. On a plain classic theme a footer widget added as a shortcode brings
  its stylesheet after the content (it is a footer; nobody sees it). Force `<head>` loading with
  `add_filter( 'evpx_load_assets', '__return_true' );` if that matters to you.
- A page that mixes ways of adding widgets (shortcodes or blocks in the content, a native element in a
  Breakdance footer) loads each script once everywhere. On a plain classic theme it carries the stylesheet
  twice (about 8 KB gzipped); the rules are identical, so nothing changes visually.
- Widgets sit in whatever box the host gives them and adapt to it. Inside a Breakdance Section they are as
  wide as the Section's container, not the full window.

**Recommended setup: full-width Sections with no padding.** The widgets are designed as full-bleed bands, each
with its own vertical rhythm and its own grid (a 1200px container inside the band). Put each one in a Breakdance
Section whose **Width** is *Full* and whose **Padding** is 0, and the article reads as one piece: the hero and CTA
run edge to edge, the sections sit on one grid, and nothing adds space the widget already brings. In Breakdance's
default Section (a 1120px container with about 100px of vertical padding) each widget becomes a boxed panel floating on the page
colour, with a wide gap between panels; that works, and it is what the `.breakdance-native-url` fixture page shows,
but it is not the intended look. `tests/docker/breakdance-page.sh` builds both, so you can compare them
(`.breakdance-full-url` is the recommended one).

## Element Studio

The plugin registers two Element Studio save locations, **EV Charging Elements** and **EV Charging Presets**
(`src/Breakdance/ElementStudioBridge.php`). They are for elements you build yourself: open Element Studio,
create the element, save it to the plugin's location. Element Studio writes the PHP namespace
`EVPXStudio` for them, deliberately not `EVPX`, which holds the plugin's own native elements: an element
called "Hero" saved in Element Studio would otherwise declare the same class twice and stop the site
(`tests/docker/breakdance-real-check.sh` loads exactly that).

The Element Studio interface itself was not operated in QA; that Breakdance loads an element saved in the
folder was.

Root class per widget, if you reproduce one by hand (the markup is in `templates/`, the styles in
`assets/css/evpx.css`):

| Widget | Root class |
|---|---|
| Hero | `evpx-root evpx-hero` |
| Section | `evpx-root evpx-section evpx-section--{layout}` |
| Comparison | `evpx-root evpx-comparison evpx-comparison--{accent_treatment}` |
| Scenario Cards | `evpx-root evpx-scenarios` |
| Technical Flow | `evpx-root evpx-flow evpx-flow--{direction}` |
| Decision Factors | `evpx-root evpx-decision` |
| Related Articles | `evpx-root evpx-related` |
| FAQ | `evpx-root evpx-faq` |
| CTA | `evpx-root evpx-cta evpx-cta--{variant}` |

## For developers: how the native elements are built

```text
src/Breakdance/Native/
├── NativeElements.php   registers them: declares the classes on `breakdance_loaded` (priority 9),
│                        registers the "EV Charging" category on `init`
├── NativeElement.php    a trait: everything Breakdance asks an element class for
├── Controls.php         control schema ↔ Breakdance controls and saved properties (no Breakdance calls)
└── elements/            one file per element: EVPX\Hero, EVPX\Section, EVPX\Comparison, …
```

- **Same widget, same renderer.** An element class only names its widget (`Widgets\Hero`); controls,
  defaults and markup come from `EVPX\Elements\Element`. Nothing about the design lives here.
- **A trait, not a base class.** Breakdance lists every declared subclass of its Element class and
  instantiates each one; an abstract base of ours would be found and crash the builder.
- **Loaded late on purpose.** The element files extend a Breakdance class, so they are only `require`d once
  Breakdance has announced itself. Without Breakdance none of this code runs.
- **Control mapping**, in `Controls.php`:

  | Widget control | Breakdance control | Saved as |
  |---|---|---|
  | `text` | text | string |
  | `textarea` | text, multiline | string |
  | `richtext` | richtext | string |
  | `url` | text (placeholder `https://`) | string |
  | `image` | media (`wpmedia`) | object; the plugin keeps its attachment `id` |
  | `toggle` | toggle | boolean; switched off is stored as an explicit `false` |
  | `select` | dropdown | string |
  | `number` | number | number |
  | container items | repeater at `content.items.rows` | list of rows |

- **Adding a widget.** Write the widget class and template, add it to `Registry`; add
  `src/Breakdance/Native/elements/<Name>.php` (copy any) and list it in `NativeElements::WIDGETS` (name → widget class);
  the suites read that list, so they cover it without being edited.

## Verifying it

```
EVPX_BREAKDANCE_ZIP=/path/to/breakdance.zip bash tests/docker/setup.sh   # you supply the licensed ZIP
bash tests/docker/breakdance-real-check.sh        # integration, native-element contract, Element Studio namespace
bash tests/docker/breakdance-page.sh              # two pages: Shortcode elements, native elements
node tests/playwright/breakdance-qa.mjs "$(cat tests/docker/.breakdance-native-url)"
bash tests/docker/builder-save-check.sh           # dropdown, Add panel, Save, front end, reopen the builder
bash tests/docker/template-check.sh               # footers and templates under three themes
bash tests/docker/media-pages.sh                  # generated pictures; then tests/playwright/media-qa.mjs
bash tests/docker/isolation-check.sh              # unrelated pages are pixel-identical with the plugin on and off
```

Details of each script are in its header, and in `docs/QA-REPORT.md`.
