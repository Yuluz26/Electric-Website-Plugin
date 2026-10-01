# MVP — Build the First Working Version

## Objective

Implement the smallest complete version of the premium EV article plugin that proves:

1. WordPress plugin works.
2. Breakdance integration works.
3. Custom widgets are editable in Breakdance.
4. The reference article can be rebuilt with the new visual system.
5. GSAP works without damaging Breakdance.
6. CSS/JS are isolated.
7. The page is responsive.
8. Existing pages are unaffected.

Do not start by building 30 widgets.

## MVP widget set

Build these first:

### 1. EV Article Hero
Controls:
- category
- title
- excerpt
- author/date
- reading time
- hero media
- CTA
- visual mode
- animation toggle

### 2. EV Section
Controls:
- eyebrow
- heading
- body
- media
- layout
- alignment
- visual treatment

### 3. EV AC/DC Comparison
This is the signature component.

Controls:
- AC content
- DC content
- comparison labels
- power values
- charging-speed text
- ideal-use-case
- infrastructure notes
- interaction mode
- mobile mode
- animation intensity

### 4. EV Scenario Cards
Controls:
- scenario
- title
- description
- icon/media
- key requirement
- recommended charging approach
- CTA

### 5. EV Technical Flow
Show:
Grid → Site → Charger → Vehicle → Battery

Controls:
- labels
- icons
- animation toggle
- direction
- compact/full mode

### 6. EV FAQ
Controls:
- question/answer repeater
- default open state
- icon style
- animation toggle

### 7. EV CTA
Controls:
- eyebrow
- title
- body
- button
- media
- visual variant

## MVP page structure

Build one complete article:

1. Hero
2. Introduction
3. AC explanation
4. DC explanation
5. AC/DC comparison
6. What determines the choice
7. Scenario cards
8. Technical flow
9. Infrastructure considerations
10. FAQ
11. CTA
12. Related content placeholder

## MVP visual target

The page must already feel premium.

Minimum visual features:
- refined neumorphic surfaces
- editorial typography
- cinematic hero image
- subtle technical decoration
- GSAP hero reveal
- GSAP section reveal
- comparison animation
- reading progress
- mobile adaptation

## MVP media rule

Use real media.

Before selecting an asset:
- verify source
- verify licensing/usage
- verify visual quality
- verify EV relevance
- verify it does not look AI-generated

## MVP content

Use the reference topic and structure, but rephrase the copy.

Do not copy the reference article verbatim.

Keep factual technical claims conservative and verify current details from reputable sources.

## MVP implementation order

1. plugin bootstrap
2. Breakdance compatibility layer
3. asset loader
4. design tokens
5. Hero
6. Section
7. Comparison
8. Scenario Cards
9. Technical Flow
10. FAQ
11. CTA
12. GSAP layer
13. responsive layer
14. accessibility
15. QA

## MVP acceptance criteria

PASS only if:
- plugin activates without fatal error
- Breakdance still opens
- widgets appear in Breakdance
- widgets are editable
- no global style pollution
- no console errors
- no duplicate GSAP initialization
- mobile layout is intentional
- reduced motion works
- existing pages remain unchanged
- plugin can be packaged as ZIP

## Important

Do not polish every edge case before the architecture works.

The MVP must prove the foundation first.
