# UI/UX Design System — Cinematic Neumorphism for EV Charging

## Role

Act as a senior digital art director, product designer, interaction designer and motion designer.

You are designing a premium EV-charging article experience inside WordPress + Breakdance, implemented through custom Breakdance-compatible plugin elements.

Reference:
https://electrick-bzt5.perfectdesign.app/news/choosing-ac-or-dc-charging-for-your-site/

Research before design. Inspect the reference page and benchmark it against premium EV/e-mobility experiences.

Use:
- GSAP
- refined neumorphism
- editorial design
- cinematic photography/video
- sophisticated typography
- controlled micro-interactions

## Design principle

The design should feel like:
“an editorial technology journal for the future of mobility”

It should NOT feel like:
- an AI-generated website
- generic SaaS
- a template marketplace demo
- a crypto landing page
- a Dribbble concept that ignores usability

## Neumorphism rules

Use neumorphism selectively.

Preferred:
- soft elevation
- subtle inset surfaces
- tactile controls
- tonal hierarchy
- layered surfaces
- restrained shadows
- quiet depth

Avoid:
- extreme blurry shadows
- low-contrast body text
- every element becoming a pill
- excessive floating cards
- identical cards repeated across every section

Combine neumorphism with:
- editorial typography
- flat image compositions
- full-bleed media
- strong negative space
- thin technical dividers
- subtle grid structures

## Suggested visual language

Create:
- primary surface
- elevated surface
- recessed surface
- accent surface
- technical data surface
- media surface

Use CSS variables/tokens so the entire system is centralized and namespaced.

Do not hard-code random colors inside individual components.

## Typography

Choose a font pairing after research.

Requirements:
- excellent readability
- strong numerals
- distinctive headings
- restrained body typography
- good mobile rendering

Typography hierarchy should clearly separate:
- eyebrow
- display headline
- section heading
- body
- metadata
- technical labels
- data values
- CTA

Avoid excessive uppercase.

## Page composition

Create a cinematic article flow:

### 01 — Opening
- subtle technical prelude
- article category
- large editorial title
- short summary
- metadata
- cinematic EV charging media
- subtle animated energy line/grid

### 02 — Context
A calm introduction that establishes why AC/DC selection matters.

### 03 — AC
A tactile technical section explaining AC charging.

### 04 — DC
A contrasting technical section explaining DC charging.

### 05 — AC vs DC
Make this the visual centerpiece.

Possible interaction:
- horizontal/vertical comparison
- animated power-flow diagram
- two-mode toggle
- synchronized visual indicators

Do not make the interaction gimmicky.

### 06 — What actually determines the choice?
Create a decision framework around:
- dwell time
- required energy
- site capacity
- turnover
- installation complexity
- operating model
- future growth

### 07 — Scenario storytelling
Use real-world scenarios:
- workplace
- hotel
- residential/apartment
- fleet depot
- retail
- highway/high-turnover site

### 08 — Infrastructure
Show the relationship between:
grid → site infrastructure → charger → vehicle → battery

Use a clean technical visual.

### 09 — FAQ
Premium accordion with accessible keyboard support.

### 10 — Closing
Strong but restrained CTA:
“Plan the charging system around how your site actually works.”

## Interaction design

GSAP:
- ScrollTrigger for section entrances
- image clip reveals
- subtle horizontal movement
- progress indicator
- technical diagram sequencing
- comparison state transition

Use motion hierarchy:
1. hero = strongest
2. comparison = medium
3. supporting sections = subtle
4. body text = mostly static

Never animate every word.

## Micro-interactions

Buttons:
- tactile hover
- subtle elevation
- controlled arrow movement

Cards:
- tiny depth shift
- no excessive tilt

Images:
- slow scale on hover where appropriate

Accordion:
- height/opacity transition
- respect reduced motion

## Media art direction

Prefer:
- real EVs
- real charging stations
- real architecture
- nighttime charging
- dawn/dusk scenes
- realistic electrical infrastructure
- close-up connector/detail shots
- cinematic parking structures
- commercial environments

Avoid:
- fake neon cyberpunk EV scenes
- impossible charger designs
- AI hands
- AI cars with malformed details
- generic “green sustainability” stock imagery

## Responsive UX

Mobile should become a deliberately composed editorial story.

Do not:
- preserve desktop side-by-side comparison when it becomes unreadable
- force horizontal overflow
- shrink display type until it becomes ordinary

Instead:
- stack content
- convert comparison to tabs/cards
- use sticky progress carefully
- maintain visual rhythm

## Breakdance editing

Each major visual section must be an independent custom element/widget.

The widget controls should make sense to a non-developer.

Example:
EV AC/DC Comparison:
- AC title
- AC description
- AC power range
- AC dwell-time label
- AC best-for
- DC title
- DC description
- DC power range
- DC dwell-time label
- DC best-for
- comparison mode
- animation intensity
- accent treatment
- mobile layout

## Design QA checklist

Check:
- hierarchy
- contrast
- spacing
- alignment
- image cropping
- animation pacing
- touch targets
- mobile readability
- reduced motion
- keyboard navigation
- builder editability

## Deliverable

Produce a complete design system specification before implementation:
- color tokens
- type scale
- spacing scale
- radii
- shadows
- surfaces
- breakpoints
- motion tokens
- component states
- interaction rules
- page section blueprint
- media direction
- accessibility rules

The implementation must look intentionally designed, not generated.
