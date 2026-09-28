# EV Charging Experience — Architecture & Design System

Status: MVP → Production build. Source prompts: `docs/prompt-pack/` (01–06).

## 1. Confirmed requirements

- WordPress plugin, installable on any WP + Breakdance site without visually touching unrelated pages.
- 7 MVP content components (see §3) that render a premium, neumorphic, editorial EV-charging article.
- GSAP-driven motion that respects `prefers-reduced-motion`, never runs in the Breakdance builder canvas.
- Everything namespaced (`EVPX`), CSS/JS isolated, assets conditionally loaded.
- No fatal errors with or without Breakdance active; safe activate/deactivate.

## 2. Architecture proposal

### 2.1 Breakdance integration strategy — read this first

Breakdance's native "custom element" system (**Element Studio**) is a closed, GUI-only
generator: you build the element visually inside a licensed Breakdance install and it
writes PHP+Twig files into a folder your plugin registers with
`\Breakdance\ElementStudio\registerSaveLocation()`. Confirmed by cloning and reading
Breakdance's own `soflyy/breakdance-custom-elements` and `soflyy/breakdance-developer-docs`
repos — there is no documented format for hand-authoring that generated output, and no raw
"PHP element class" API the way Elementor/Gutenberg expose one.

This build environment has no licensed Breakdance instance, so Element Studio can't be
operated here. Guessing at the generated-file schema would risk exactly the silent
builder breakage the SOP forbids. Instead:

1. **Every component is a WordPress shortcode** with a full attribute-based control
   surface (content, media, layout, visual, motion — matching the PRD's control
   categories). Shortcodes render through Breakdance's native, version-proof "Shortcode"
   element — this has been stable for years and carries zero integration risk.
2. **Every component is also a server-rendered Gutenberg block** (same renderer, same
   attributes) so it can be inserted visually with a live preview, including inside
   Breakdance (which embeds WP content/blocks natively).
3. The plugin registers the real Element Studio save location on `breakdance_loaded`,
   and `docs/BREAKDANCE-ELEMENT-STUDIO-BRIDGE.md` gives exact copy/paste HTML+CSS and the
   control list for each widget, so wiring up a fully native drag-in element inside
   Element Studio on the real site is a short, mechanical task rather than a rebuild.
4. Dynamic Data fields (reading time, hero image, published date) are registered via the
   documented `\Breakdance\DynamicData\*` classes so native Breakdance elements elsewhere
   on the page can also pull EV article data.

Every Breakdance API call is guarded with `function_exists()` / `class_exists()` before
use, per Breakdance's own documented pattern — the plugin is fully inert (but still
renders shortcodes/blocks) with Breakdance deactivated.

### 2.2 Folder structure

```text
Electric-Website-Plugin/
├── ev-charging-experience.php      # main plugin bootstrap
├── composer.json                   # PSR-4 autoload: EVPX\ → src/
├── src/
│   ├── Core/                       # Plugin, Activation, Deactivation
│   ├── Breakdance/                 # Compatibility, ElementStudioBridge, DynamicData
│   ├── Elements/                   # One class per widget (shortcode + block renderer)
│   ├── Assets/                     # Conditional CSS/JS loader
│   ├── Admin/                      # Settings/help screen (production phase)
│   └── Support/                    # Sanitizers, view helpers
├── assets/
│   ├── css/                        # tokens.css, components/*.css
│   ├── js/                         # evpx.js (namespace), motion.js (GSAP)
│   └── images/
├── templates/                      # PHP view partials per element
├── tests/                          # smoke + docker QA scripts
└── docs/
```

### 2.3 Namespacing

- PHP namespace: `EVPX\*`
- CSS prefix: `.evpx-*` (never a bare tag/global selector)
- JS global: `window.EVPX`
- Shortcode prefix: `evpx_*` (e.g. `[evpx_hero]`)
- Block namespace: `evpx/*` (e.g. `evpx/hero`)
- DB options / postmeta prefix: `evpx_`

## 3. Element inventory (MVP)

| # | Element | Shortcode | Block | Key controls |
|---|---|---|---|---|
| 1 | EV Article Hero | `[evpx_hero]` | `evpx/hero` | category, title, excerpt, author, date, reading_time, media, cta_label, cta_url, animate |
| 2 | EV Section | `[evpx_section]` | `evpx/section` | eyebrow, heading, body, media, layout (text-left/right/stacked), surface (flat/raised/recessed) |
| 3 | EV AC/DC Comparison | `[evpx_comparison]` | `evpx/comparison` | ac_* / dc_* (title, description, power_range, dwell_label, best_for), mode (toggle/side-by-side), animation_intensity |
| 4 | EV Scenario Cards | `[evpx_scenarios]` | `evpx/scenarios` | repeater: scenario, title, description, icon, requirement, recommendation |
| 5 | EV Technical Flow | `[evpx_flow]` | `evpx/flow` | steps (Grid→Site→Charger→Vehicle→Battery), direction, compact |
| 6 | EV FAQ | `[evpx_faq]` | `evpx/faq` | repeater: question/answer, default_open, schema_output |
| 7 | EV CTA | `[evpx_cta]` | `evpx/cta` | eyebrow, title, body, button_label, button_url, variant |

Full attribute lists are enforced in code via each Element class's `sanitize_attributes()`
— this table is the contract, not the final word; see class docblocks for the authoritative
list.

## 4. Content model

Article flow (reconciles PRD content direction + UX section blueprint):

1. Hero — category, title, dek, meta, cinematic media
2. Introduction — why AC/DC selection matters
3. AC charging explained
4. DC charging explained
5. AC vs DC comparison (signature interactive section)
6. What determines the choice (dwell time, energy, capacity, turnover, install complexity, growth)
7. Scenario storytelling (workplace, hotel, residential, fleet depot, retail, highway)
8. Infrastructure (grid → site → charger → vehicle → battery)
9. FAQ
10. Closing CTA

Copy is original, rephrased from AC/DC charging fundamentals (verified against ChargePoint,
Power Sonic, EV Connect public explainers — see sources in final report), not copied from
the reference URL, which this sandbox cannot reach.

## 5. Design tokens — refined neumorphism

Hard rule carried through every component: **neumorphic shadows are decorative surface
depth only.** Text contrast, focus states, and "this is clickable" affordances never rely
on shadow alone — they use real color/border contrast. This directly answers the
WCAG 1.4.3/1.4.11 failure mode neumorphism is known for.

### 5.1 Color

Avoids the neon-cyan/blue-gradient EV cliché. Ink-graphite base, warm copper as the single
confident accent (energy/CTA), desaturated blue reserved for technical/data moments only.

```css
:root {
  /* Light mode surfaces */
  --evpx-surface-base: #EEF0F3;
  --evpx-surface-raised-hi: #FFFFFF;
  --evpx-surface-raised-lo: #C7CED6;
  --evpx-surface-recessed-hi: #D7DCE2;
  --evpx-surface-recessed-lo: #FFFFFF;
  --evpx-ink: #14171C;
  --evpx-ink-muted: #4B535E;
  --evpx-ink-faint: #7C8590;
  --evpx-border: #D7DCE2;

  /* Accents */
  --evpx-accent: #B9662F;       /* copper — CTA, active states, highlights */
  --evpx-accent-ink: #FFFFFF;    /* text on accent */
  --evpx-technical: #4E75A6;    /* desaturated blue — data/infra diagrams only */

  /* Dark mode surfaces */
  --evpx-surface-base-dark: #14171C;
  --evpx-surface-raised-hi-dark: #1E232A;
  --evpx-surface-raised-lo-dark: #05070A;
  --evpx-surface-recessed-hi-dark: #0A0D11;
  --evpx-surface-recessed-lo-dark: #22282F;
  --evpx-ink-dark: #EDEFF2;
  --evpx-ink-muted-dark: #A7AEB6;
  --evpx-ink-faint-dark: #6D7580;
  --evpx-border-dark: #262C34;
}
```

`--evpx-accent` (#B9662F on #EEF0F3) and `--evpx-ink` (#14171C on #EEF0F3) both clear
4.5:1 body-text contrast; verified numerically in `tests/contrast-check.mjs`, not eyeballed.

### 5.2 Surfaces (the neumorphism formula)

```css
.evpx-surface-raised {
  background: var(--evpx-surface-base);
  border-radius: var(--evpx-radius-lg);
  box-shadow:
    8px 8px 16px var(--evpx-surface-raised-lo),
    -8px -8px 16px var(--evpx-surface-raised-hi);
}
.evpx-surface-recessed {
  background: var(--evpx-surface-base);
  border-radius: var(--evpx-radius-md);
  box-shadow:
    inset 6px 6px 12px var(--evpx-surface-recessed-hi),
    inset -6px -6px 12px var(--evpx-surface-recessed-lo);
}
.evpx-surface-flat {
  background: transparent;
  box-shadow: none;
  border-radius: 0;
}
```

Four surfaces total: `raised` (cards, controls), `recessed` (technical-data panels, inputs),
`accent` (copper fill, flat — never neumorphic, needs full contrast), `flat` (editorial
text blocks/media — most of the page; neumorphism is used *selectively*, per the UX spec).

### 5.3 Typography

- Display/headings: **Fraunces** (variable serif — cinematic, editorial, distinctive without being loud)
- Body/UI/labels/data: **Libre Franklin** (sturdy grotesque, strong tabular numerals for kW/min values)
- Self-hosted woff2 where the build can fetch them; Google Fonts CSS API fallback with
  `preconnect` + `display=swap` otherwise (see `src/Assets/FontLoader.php`).

Scale (fluid via `clamp()`): eyebrow 13px, body 17px/1.65, section-heading
clamp(28px,4vw,40px), display clamp(40px,7vw,88px).

### 5.4 Spacing / radii / breakpoints

- Spacing unit 4px: tokens `--evpx-space-1` (4px) … `--evpx-space-12` (128px)
- Radii: `--evpx-radius-sm` 8px, `--evpx-radius-md` 16px, `--evpx-radius-lg` 24px (no pills on cards/buttons)
- Breakpoints tested: 320, 375, 390, 430, 768, 1024, 1280, 1440, 1920

### 5.5 Motion tokens

```css
:root {
  --evpx-ease-premium: cubic-bezier(0.16, 1, 0.3, 1);
  --evpx-dur-micro: 0.2s;
  --evpx-dur-base: 0.6s;
  --evpx-dur-section: 0.9s;
  --evpx-dur-hero: 1.2s;
}
```

Motion hierarchy: hero (strongest) → comparison (medium) → supporting sections (subtle) →
body text (static). GSAP layer detailed in `src/Assets` + `assets/js/motion.js`.

## 6. Asset strategy

- CSS/JS enqueued only when `has_shortcode()` / block presence is detected on the
  rendered content, or a plugin block/shortcode fires during a Breakdance builder request.
- GSAP + ScrollTrigger loaded from a single conditional bundle; before enqueuing, the
  runtime checks `window.gsap`/`window.ScrollTrigger` so it never double-loads if
  Breakdance's own reusable-dependency system (`%%BREAKDANCE_REUSABLE_GSAP%%`) already
  provided a copy on the page.
- Builder-mode detection (`EVPX\Breakdance\Compatibility::isBuilderContext()`) disables
  autoplay/ScrollTrigger/observers while editing.

## 7. Compatibility strategy

- All Breakdance calls guarded by `function_exists()`/`class_exists()`.
- No filters/actions that touch `.bde-*`, Breakdance templates, or global widget CSS.
- No activation-time content rewrites; no DB migration in v1 (nothing to migrate).
- Tested against a real WordPress core (Docker) for activation/deactivation and fatal-error
  freedom; Breakdance itself is unavailable here (paid/licensed) — flagged as a follow-up
  verification step for the real site (see final report).

## 8. Acceptance criteria

Same as `docs/prompt-pack/04_MVP.md` "MVP acceptance criteria", plus: shortcode and block
output are byte-identical (single renderer, two entry points), PHP 7.4–8.4 syntax-clean,
zero JS console errors, zero PHP notices/warnings under `WP_DEBUG`.

## 9. Risks & mitigations

| Risk | Mitigation |
|---|---|
| No licensed Breakdance to verify Element Studio rendering | Shortcode/block path is the verified-safe primary delivery; bridge doc for native Element Studio |
| Reference article unreachable (egress-blocked) | Rebuilt content model from the prompt pack's own detailed structure + independently verified AC/DC facts |
| Neumorphism hurting contrast/accessibility | Decorative-shadow-only rule (§5); numeric contrast check in `tests/` |
| GSAP double-loading with Breakdance's own dependency system | Runtime `window.gsap` existence check before enqueue |
| Font fetch blocked by sandbox egress | Google Fonts CSS API fallback path built in |
