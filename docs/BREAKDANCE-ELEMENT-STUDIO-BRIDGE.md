# Building native Breakdance elements with Element Studio

## Why this doc exists

Breakdance's native "custom element" system (**Element Studio**) is a closed,
GUI-only generator — you build an element visually inside a *licensed*
Breakdance install, and it writes PHP+Twig files into a folder your plugin
registers. There is no public, documented format for hand-authoring that
generated output (verified by reading Breakdance's own
`soflyy/breakdance-custom-elements` and `soflyy/breakdance-developer-docs`
repositories — the only documented extension points are Element Studio
itself, plus narrow APIs for Dynamic Data, Conditions, Animations, Hooks,
Form Actions and Reusable Dependencies).

This plugin was built without a licensed Breakdance instance available, so
Element Studio couldn't be operated to generate native elements directly.
Instead, every component ships as a **shortcode + Gutenberg block**, which
Breakdance embeds natively and safely via its built-in Shortcode element —
zero risk, zero guessing at an undocumented format.

The plugin *also* registers a real Element Studio save location (see
`src/Breakdance/ElementStudioBridge.php` — this part is 100% verified against
Breakdance's own boilerplate, not guessed). That means on your real
Breakdance site, "EV Charging Elements" already shows up as a place to save
new elements. Turning any of the 9 shortcodes into a fully native,
visually-controlled Breakdance element is then a short, mechanical task:

## The 10-minute process, per widget

1. In WordPress admin, open the page in **Breakdance's builder**.
2. Add a **Code** element (or open **Element Studio** directly from the
   Breakdance sidebar) and drop in the widget's markup below as a starting
   structure, OR simpler: add a **Shortcode** element and paste the
   shortcode (e.g. `[evpx_hero]`) — this already works with zero extra
   steps and is what we recommend unless you specifically want native
   drag-handle controls.
3. If you want native controls: open **Element Studio → New Element**,
   save it to the **"EV Charging Elements"** location this plugin
   registered, and recreate the controls listed below using Element
   Studio's control builder (Text, Textarea, Image, Toggle, Select —
   every control type below maps directly to an Element Studio control
   type of the same name).
4. Point each control's Twig variable at the same attribute name listed
   below, and use the corresponding CSS class (already fully styled by
   this plugin's `assets/css/evpx.css` — no new CSS needed, only make sure
   `evpx-styles` is enqueued, which happens automatically whenever any
   `evpx_*` shortcode/block is present on the page).

## Widget reference

For the full attribute list and defaults, see `docs/WIDGETS.md` — every
control there has a `key` (the Twig variable / shortcode attribute name),
a `type` (the Element Studio control type to use), and a `group` (which
tab to organize it under: Content, Media, Layout, Visual, Motion,
Responsive, Advanced — matching the PRD's prescribed editor organization).

Root CSS class per widget (apply to Element Studio's root element):

| Widget | Root class |
|---|---|
| Hero | `evpx-root evpx-hero` |
| Section | `evpx-root evpx-section evpx-section--{layout}` |
| Comparison | `evpx-root evpx-comparison evpx-comparison--{accent_treatment}` |
| Scenario Cards | `evpx-root evpx-scenarios` |
| Scenario Card | `evpx-scenario-card evpx-surface--raised-sm` |
| Technical Flow | `evpx-root evpx-flow evpx-flow--{direction}` |
| FAQ | `evpx-root evpx-faq` |
| FAQ Item | `evpx-faq__item evpx-surface--raised-sm` |
| CTA | `evpx-root evpx-cta evpx-cta--{variant}` |

Copy the exact inner markup structure from the matching file in
`templates/*.php` — it's plain HTML with `<?php ... ?>` swapped for Twig
`{{ }}` — and you have a pixel-identical native element.

## Why not ship guessed Element Studio files instead?

Because Element Studio's generated format isn't publicly documented, a
guess could easily be *wrong* in a way that fails silently inside the
builder — exactly the kind of Breakdance-builder bug this project was
asked to avoid. The shortcode/block path is verified working (see the
Docker QA results in the final report); this bridge gets you native
controls without that risk.
