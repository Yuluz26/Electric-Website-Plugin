# Media brief

The demo article ships without photography. The build environment could not reach any image
host (Unsplash, Pexels, Wikimedia, Pixabay were all blocked), and the brief this plugin was
built from asks for real, licensed images — not generated ones and not placeholders. Every
widget already handles media correctly (responsive `srcset`, alt text, empty states); it just
needs the pictures. (Crop, `srcset`, alt text and the picture controls were checked with generated test
images — plain gradients, not photographs: `tests/docker/media-pages.sh`.) This is what to source.

Since 0.6.0 the page is not bare while it waits: where a widget has room for a picture and none is chosen it can show
one of the plugin's own drawings (a car on a charger, a charge curve, an AC wall box, a DC cabinet, the path from grid
to charger, a wave and a level). They are line drawings, on a dark panel, in copper light: honest about being diagrams,
and they hold their own beside photographs. A photograph, where you choose one, replaces the drawing. The rows below say
where a picture goes and what the drawing that stands in for it is.

## Shot list

| Where | Widget / control | What to look for | Crop |
|---|---|---|---|
| Opening | Hero → `media` (replaces the schematic drawing) | A real charging site at dawn, dusk or night: a charger and a parked vehicle in a commercial or architectural setting. Wide and calm. Darker pictures look richer; a bright one still works (the scrim dims it by 60%, which keeps the white type above 4.5:1 even over a near-white sky) but reads as a grey backdrop. | 16:9, at least 2000 px wide, subject in the upper two thirds |
| AC section | Section → `media` (replaces the `wallbox` drawing) | Close, unposed detail: a Type 2 connector in a vehicle inlet, a wall-mounted AC unit in a car park. | 8:5, 4:3 or 1:1 |
| DC section | Section → `media` (replaces the `dc-cabinet` drawing) | Contrast with the AC image: a high-power cabinet and cable, a row of DC bays. | same ratio as above |
| Scenario cards | Scenario Card → `icon` | Optional, and not needed: each card already has an icon from the plugin's family. If you use your own, keep them consistent (all line icons, or all tightly cropped photos — not a mix). | square |
| Closing | CTA (media variant) → `media` | A wide, quiet exterior; type sits on top of a scrim, so avoid busy detail. | 21:9 or 16:9 |
| Related articles | featured image on each post | Real photos from those articles, 3:2. An article without one is shown a drawing, so a row can mix; a row of all photographs looks best. | 3:2 |

## The site's photographs

Since 0.8.0 the six site pages have a look before any photograph exists: every hero, services panel and project card
draws one of five scenes (a night forecourt, a road at dusk, a DC cabinet, pylons at sunset, a plug in close-up), layered
and moving. A photograph, chosen in the widget's **Photograph** control, replaces the scene. The build environment
could not reach any image host, so none is bundled; this is what to shoot or license, page by page. Keep the register of
the scenes: dusk or night, one source of warm light, a lot of dark, real equipment.

| Page | Where | What to look for | Crop |
|---|---|---|---|
| Home | Page Hero → `media` | A working forecourt or depot at dusk, chargers lit, a vehicle plugged in; space on the left for the headline (the type sits there, low). The darkest, calmest frame you have. | 16:9, 2400 px wide, subject in the right half |
| Home | Services → each panel's `media` | Four pictures that answer the four names: a surveyor with a logger on a switchboard (Site design), trenching or switchgear going in (Installation), a monitoring screen or a technician at a cabinet (Operations), a row of older chargers being replaced (Upgrades). | 16:9, 1800 px wide |
| Home, Projects | Projects → each project's `media` | One real site each, from the outside, the way a driver sees it. Six cards read best when they share a time of day. | 5:4, 1400 px wide |
| About | Page Hero → `media` | The team at work on a site, not posed: two people and a cabinet, hi-vis, evening light. | 16:9 |
| Services | Page Hero → `media` | A DC cabinet and its cable, close, with depth. | 16:9 |
| Projects | Page Hero → `media` | A motorway service area or a long row of bays at dusk. | 16:9 |
| Contact | Page Hero → `media` | A connector, close: the plug and the inlet. Abstract enough that the form beside it is the subject. | 16:9 |
| Search | none | The hero is the blueprint grid. Leave it. | |

- **Type over a photograph.** The hero and the service panels put white type over the picture, on a scrim. The hero's
  **Overlay** control (light / medium / deep) sets how dark it is; start with medium and raise it if the picture is bright
  behind the headline. `tests/playwright/media-qa.mjs` measures it for the article hero; check the site's own the same way.
- **Alt text.** A hero, panel or card photograph is decoration behind text that says the same thing, so it may have an
  empty alt; the Page Hero has a `media_alt` control for the one that has something to say.
- **Weight.** Hero photographs under about 300 KB (WebP or a well-made JPEG), panel and card photographs under 150 KB.
  Six card pictures and four panels are ten requests, all lazy after the first screen.

## Rules taken from the brief

- Real EVs, real chargers, real architecture. No AI-generated imagery, no obvious composites,
  no fake product renders presented as real products, no watermarks.
- Nothing that reads as generic "green sustainability" stock or cyberpunk. (The plugin's own copper glow is drawn
  light on a dark ground, a line and a halo; a photograph should not compete with it by being neon-lit itself.)
- Verify each licence yourself before publishing. Unsplash and Pexels licences allow free
  commercial use without attribution but both have terms about redistribution; Wikimedia
  Commons files carry per-file licences (CC0 / public domain are the simple ones, CC BY-SA
  needs attribution). Keep the source URL and licence in the media item's description.
- **Alt text is content, not decoration.** Describe what the picture shows in the context of
  the article ("Two vehicles plugged into wall-mounted AC chargers in an office car park").
  Decorative repeats of the headline should have empty alt. Widgets use the attachment's alt
  text unless you fill the `media_alt` override.
- Compress before upload (WebP or well-optimised JPEG, hero under ~300 KB). WordPress
  generates the responsive sizes; the hero requests `full`, so don't upload a 6000 px original.

## Adding them

Media library → upload → in the widget, use the **Media** panel (block editor, or the Breakdance
element's Media section, which opens the media library) or pass the attachment ID (`media="123"` in a
shortcode; the ID is in the media item's URL).
