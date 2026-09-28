# PRD — Premium EV Charging Article / Website Plugin for WordPress + Breakdance

## Role

Act as a senior WordPress plugin architect, Breakdance Element Studio developer, UX/UI designer, motion designer and technical SEO engineer.

Build a production-minded WordPress plugin that can power a premium EV-charging website/article experience while remaining safe to install into an existing WordPress + Breakdance website.

The primary reference page is:

https://electrick-bzt5.perfectdesign.app/news/choosing-ac-or-dc-charging-for-your-site/

IMPORTANT:
- Inspect the reference URL yourself before designing/implementing.
- Treat the reference as a structural/content benchmark, not a design to copy.
- Rephrase the reference copy substantially enough to be original while preserving the factual meaning and user intent.
- Do not scrape or reproduce large sections verbatim.
- Research additional premium EV/e-mobility websites for visual and UX inspiration.
- Do not use generic AI-generated stock imagery or obviously synthetic AI visuals.

## Core objective

Create a premium, cinematic, editorial EV-charging experience that can visually outperform the reference while being implemented as a reusable WordPress plugin.

The plugin must integrate with Breakdance rather than fight it.

### Non-negotiable architecture

1. WordPress plugin architecture.
2. Breakdance-compatible custom elements/widgets.
3. Prefer Breakdance Element Studio/custom-element APIs and documented integration points.
4. The plugin must NOT override Breakdance core files, templates, global builder CSS, or existing Breakdance elements.
5. Never hijack `.bde-*` styles globally.
6. Never enqueue aggressive global CSS that changes unrelated pages.
7. Namespace every PHP class, function, hook, CSS class, JS variable and data attribute.
8. All assets must be conditionally loaded only where the plugin elements are rendered.
9. Existing pages must remain visually unchanged after plugin activation.
10. Existing Breakdance pages must continue to open and edit normally.
11. Plugin deactivation must not corrupt or rewrite existing Breakdance content.
12. Existing content should remain readable if the plugin is disabled.
13. Avoid direct DOM mutation of unrelated Breakdance elements.
14. Avoid monkey-patching Breakdance internals.
15. Use official/documented Breakdance extension mechanisms where available.

## Editing philosophy

Every major visual section of the new experience should be represented by a dedicated custom Breakdance element/widget.

Do NOT require the user to assemble the design from many standard Breakdance widgets.

Create a logical element library such as:

- EV Article Hero
- EV Article Meta
- EV Article Intro
- EV Section Header
- EV AC/DC Comparison
- EV Charging Flow
- EV Technical Explainer
- EV Decision Cards
- EV Scenario Cards
- EV Infrastructure Panel
- EV FAQ Accordion
- EV CTA
- EV Related Articles
- EV Article Progress
- EV Reading Time
- EV Media Showcase

The exact final list may change after implementation discovery, but each component must be independently editable inside Breakdance.

Each custom element should expose practical content/design controls:
- heading
- eyebrow
- body copy
- media
- icon
- CTA labels/links
- alignment
- spacing
- responsive behavior
- animation toggle
- animation intensity
- visual variant
- optional decorative elements

## Target experience

The article should feel:
- premium
- editorial
- technical
- cinematic
- modern
- calm
- confident
- highly legible
- sophisticated
- intentionally designed

Avoid:
- generic SaaS layouts
- excessive gradients
- random blobs
- excessive rounded cards
- fake 3D
- rainbow gradients
- excessive glassmorphism
- AI-looking illustrations
- stock-photo clichés
- excessive text animation
- visual noise
- unnecessary parallax everywhere

## Visual direction

Primary direction:
- refined neumorphism
- dark/light tonal depth
- soft inset/outset surfaces
- restrained shadows
- strong typography
- cinematic imagery
- editorial whitespace
- subtle technical details
- controlled motion
- premium EV/electrical aesthetic

Neumorphism must remain accessible and readable. Do not make text or controls low-contrast merely to imitate neumorphism.

Use a disciplined color system rather than inventing colors per section.

Typography should feel premium and editorial. Select fonts based on actual project research and loading/performance constraints.

## Motion

Use GSAP for meaningful motion:
- hero reveal
- scroll-linked section transitions
- progress indicator
- comparison interaction
- image reveal
- subtle depth/parallax
- FAQ motion
- CTA reveal

Motion must:
- respect `prefers-reduced-motion`
- avoid scroll-jacking
- avoid blocking interaction
- avoid layout thrashing
- use performant transforms/opacity
- clean up ScrollTrigger instances
- avoid running unnecessarily in Breakdance builder mode

## Content direction

Base article topic:
“Choosing AC or DC Charging for Your Site”

Create an improved editorial structure around:
- introduction
- what AC charging means
- what DC charging means
- why the charging experience differs
- AC vs DC comparison
- site/use-case considerations
- dwell time
- power/infrastructure
- operational considerations
- decision framework
- practical scenarios
- FAQ
- conclusion / CTA

The exact factual claims must be checked against current reputable sources during implementation.

Do not invent certifications, power ratings, statistics, customer logos, performance claims or technical standards.

## Media

Use real, high-quality, contextually appropriate media from reputable/publicly usable sources or clearly licensed sources.

Possible sources to research:
- Unsplash
- Pexels
- Wikimedia Commons
- official manufacturer media libraries where usage permits
- other legitimate image/video libraries

Do not use:
- AI-generated-looking EV imagery
- random stock images with obvious compositing
- fake product renders presented as real products
- watermarked media
- copyrighted media copied without permission

Always provide meaningful alt text and a fallback state.

## Responsive requirements

Must work at:
- desktop
- laptop
- tablet
- mobile

Design mobile intentionally rather than merely shrinking desktop.

Test:
- 320px
- 375px
- 390px
- 768px
- 1024px
- 1280px
- 1440px+

## Accessibility

Target WCAG-conscious implementation:
- semantic HTML
- keyboard navigation
- visible focus states
- sufficient contrast
- reduced motion
- proper heading hierarchy
- ARIA only when needed
- accessible accordion behavior
- meaningful link labels
- alt text
- no information conveyed only through color

## SEO

Support:
- semantic article markup
- correct heading hierarchy
- metadata compatibility
- Open Graph compatibility without overriding SEO plugins
- FAQ schema only when content genuinely represents FAQs
- no duplicate schema if another SEO plugin already manages it
- clean URLs
- performance-conscious assets

## Performance

Target:
- minimal CSS
- minimal JS
- conditional asset loading
- code splitting where useful
- lazy-load non-critical media
- preload only truly critical assets
- avoid huge libraries for tiny effects
- use GSAP only where justified
- no unnecessary frontend requests

## Admin/editor experience

The plugin should feel native to Breakdance.

Controls should be organized logically:
1. Content
2. Media
3. Layout
4. Visual
5. Motion
6. Responsive
7. Advanced

Provide useful defaults so a newly inserted element already looks premium.

## Compatibility

The plugin must coexist with:
- WordPress core
- Breakdance
- Breakdance Zero theme if present
- common SEO plugins
- caching plugins
- optimization plugins

Do not assume ownership of the whole page.

## Definition of done

The PRD is successful when:
- activating the plugin does not visually alter unrelated pages
- custom widgets appear in Breakdance
- widgets can be edited visually
- widgets do not depend on standard Breakdance widgets for their internal visual composition
- GSAP animations work on frontend
- builder editing remains stable
- responsive layouts work
- accessibility basics pass
- no global CSS collisions exist
- no console errors occur
- no PHP fatal errors occur
- the plugin can be zipped and installed on another WordPress site

## Research references

Use current research from:
- Breakdance Element Studio documentation
- Breakdance developer documentation
- ChargePoint
- Polestar
- other premium EV/e-mobility websites
- selected Awwwards/CSS Design Awards references when relevant

Do not copy their design. Extract principles:
- editorial hierarchy
- visual storytelling
- interaction quality
- restraint
- premium spacing
- typography
- motion

## Output

Before coding, produce:
1. confirmed requirements
2. architecture proposal
3. element/widget inventory
4. content model
5. asset strategy
6. animation strategy
7. compatibility strategy
8. acceptance criteria
9. risks and mitigations

Then proceed only when the architecture is coherent.
