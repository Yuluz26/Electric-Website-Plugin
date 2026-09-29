# Widget & control reference

Twelve elements: nine widgets you place on a page, plus three item elements that only make
sense inside their container (Scenario Card, Decision Factor, FAQ Item).

Every element works as a shortcode (`[evpx_xxx attr="value"]`) and as a Gutenberg block
(`EV Charging Experience` category in the block inserter); the nine widgets are also native Breakdance
elements (**EV Charging** category in the Add panel, see `docs/BREAKDANCE.md`), where the item elements
become the rows of a repeater. All of them use the exact same PHP renderer — output is identical. The keys
below are the shortcode and block attributes; a native element has a control for each of them.

Groups map to the editor organization the PRD specifies: Content, Media,
Layout, Visual, Motion, Responsive, Advanced. In Breakdance each group is a section of the element's panel.

## Spacing — every section widget

`spacing` (select: default / compact / none, layout) sets the vertical rhythm of a section widget: Section,
Comparison, Scenario Cards, Technical Flow, Decision Factors, FAQ, Related Articles and CTA. `default` is
the full rhythm, `compact` about half, `none` removes it — use `none` inside a Breakdance Section that
already has its own padding. It sets the `--evpx-section-y` token on the widget, so
`.evpx-root { --evpx-section-y: 0; }` in your own CSS does the same. The Hero has no rhythm to set: it has its
own minimum height.

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

## EV Section — `[evpx_section]` / `evpx/section`

General-purpose editorial block. `eyebrow`, `heading`, `body` (text),
`media`/`media_alt` (media), `layout` select (media-right/media-left/
stacked/text-only, layout), `surface` select (flat/raised/recessed,
visual), `animate` toggle (motion).

## EV AC/DC Comparison — `[evpx_comparison]` / `evpx/comparison` (signature component)

`ac_title`, `ac_description`, `ac_power_range`, `ac_dwell_label`,
`ac_best_for` and the matching `dc_*` set (content). `mode` select
(toggle/side-by-side, layout). `accent_treatment` select (split/neutral,
visual). `animation_intensity` select (standard/subtle/off, motion).
`mobile_mode` select (tabs/stacked, responsive).

## EV Scenario Cards — `[evpx_scenarios]` / `evpx/scenarios` (container)

`eyebrow`, `heading` (content), `columns` select (2/3, layout), `animate`
toggle (motion). Holds one or more **EV Scenario Card** children.

### EV Scenario Card — `[evpx_scenario_card]` / `evpx/scenario-card` (child)

Only meaningful nested inside Scenario Cards. `scenario` (small label),
`title`, `description`, `icon` (image), `requirement`, `recommendation`,
`cta_label`/`cta_url` — all content/media.

## EV Technical Flow — `[evpx_flow]` / `evpx/flow`

Grid → Site → Charger → Vehicle → Battery diagram. `heading` (content),
`step1_label` … `step5_label` (content, defaults pre-filled), `direction`
select (horizontal/vertical, layout), `compact` toggle (layout), `animate`
toggle (motion).

## EV FAQ — `[evpx_faq]` / `evpx/faq` (container)

`eyebrow`, `heading` (content), `schema_output` toggle (advanced — emits
FAQPage JSON-LD; turn off if an SEO plugin already manages FAQ schema on
this page), `animate` toggle (motion). Holds one or more **EV FAQ Item**
children.

### EV FAQ Item — `[evpx_faq_item]` / `evpx/faq-item` (child)

`question` (text), `answer` (textarea), `default_open` (toggle).

## EV Decision Factors — `[evpx_decision_factors]` / `evpx/decision-factors` (container)

`eyebrow`, `heading`, `intro` (content), `animate` toggle (motion). A numbered, deliberately
flat editorial list — the numbers come from a CSS counter, so reordering children renumbers
them. Holds one or more **EV Decision Factor** children.

### EV Decision Factor — `[evpx_decision_factor]` / `evpx/decision-factor` (child)

`title` (the factor), `description`, `question` (the "Ask" line) — all content.

## EV Related Articles — `[evpx_related]` / `evpx/related`

`eyebrow`, `heading` (content); `source` select (category / latest / manual, content);
`post_ids` (comma-separated, manual mode only); `post_type` (advanced; only public types are
accepted, otherwise falls back to `post`); `count` select 2/3 (layout); `animate` toggle
(motion). Lists real published posts, excluding the current page and password-protected
posts. With nothing to list, visitors see nothing and editors see a one-line note. Pictures
appear only when every listed article has a featured image: otherwise the row is text only, since blank
tiles beside real photographs read as broken. Give every article a featured image and the row shows them all.

## EV CTA — `[evpx_cta]` / `evpx/cta`

`eyebrow`, `title`, `body`, `button_label`/`button_url` (content), `media`
(media, only used by the `media` variant), `variant` select
(accent/dark/media, visual).

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
