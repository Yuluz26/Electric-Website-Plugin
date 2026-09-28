# Widget & control reference

Every widget works two ways: as a shortcode (`[evpx_xxx attr="value"]`) and
as a Gutenberg block (`EV Charging Experience` category in the block
inserter). Both use the exact same PHP renderer — output is identical.

Groups map to the editor organization the PRD specifies: Content, Media,
Layout, Visual, Motion, Responsive, Advanced.

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

`[evpx_faq]…[/evpx_faq]` with nested `[evpx_faq_item question="…" answer="…"]`
follows the same pattern.
