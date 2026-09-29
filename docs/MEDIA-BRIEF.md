# Media brief

The demo article ships without photography. The build environment could not reach any image
host (Unsplash, Pexels, Wikimedia, Pixabay were all blocked), and the brief this plugin was
built from asks for real, licensed images — not generated ones and not placeholders. Every
widget already handles media correctly (responsive `srcset`, alt text, empty states); it just
needs the pictures. (Crop, `srcset`, alt text and the picture controls were checked with generated test
images — plain gradients, not photographs: `tests/docker/media-pages.sh`.) This is what to source.

## Shot list

| Where | Widget / control | What to look for | Crop |
|---|---|---|---|
| Opening | Hero → `media` | A real charging site at dawn, dusk or night: a charger and a parked vehicle in a commercial or architectural setting. Wide and calm. Darker pictures look richer; a bright one still works (the scrim dims it by 60%, which keeps the white type above 4.5:1 even over a near-white sky) but reads as a grey backdrop. | 16:9, at least 2000 px wide, subject in the upper two thirds |
| AC section | Section → `media` | Close, unposed detail: a Type 2 connector in a vehicle inlet, a wall-mounted AC unit in a car park. | 4:3 or 1:1 |
| DC section | Section → `media` | Contrast with the AC image: a high-power cabinet and cable, a row of DC bays. | same ratio as above |
| Scenario cards | Scenario Card → `icon` | Optional. If used, small consistent line icons or tightly cropped photos — not a mix. | square |
| Closing | CTA (media variant) → `media` | A wide, quiet exterior; type sits on top of a scrim, so avoid busy detail. | 21:9 or 16:9 |
| Related articles | featured image on each post | Real photos from those articles, 4:3. Give *every* listed article one: the row shows pictures only when all of them have one. | 4:3 |

## Rules taken from the brief

- Real EVs, real chargers, real architecture. No AI-generated imagery, no obvious composites,
  no fake product renders presented as real products, no watermarks.
- Nothing that reads as generic "green sustainability" stock or neon cyberpunk.
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
