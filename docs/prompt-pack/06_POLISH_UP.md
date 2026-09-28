# POLISH UP — Final Premium Pass

## Role

Act as a world-class digital art director + senior frontend engineer performing a final quality pass.

Do not redesign randomly.

First inspect the current implementation and identify the highest-impact weaknesses.

## Goal

Move the website from:
“technically complete”

to:
“premium, cinematic, intentional and memorable.”

The result must feel like a carefully art-directed EV technology publication/brand experience.

## First step: audit

Audit:
- typography
- spacing
- composition
- hierarchy
- image quality
- section rhythm
- contrast
- neumorphism quality
- motion
- mobile
- accessibility
- Breakdance editing experience
- performance
- CSS collisions
- JS errors

Create a prioritized list:
P0 = broken
P1 = clearly weak
P2 = polish
P3 = optional

Fix P0/P1 before P2/P3.

## Anti-AI-slop audit

Remove or redesign anything that looks like:
- generic AI landing page
- repeated rounded cards
- random gradients
- meaningless decorative blobs
- fake futuristic copy
- excessive glassmorphism
- overused glowing cyan
- generic robot/EV stock visuals
- unnecessary 3D
- excessive icon usage
- “Unlock the future” type filler language
- repetitive section patterns

Every visual element must have a reason.

## Cinematic pass

Improve:
- hero composition
- image cropping
- image transitions
- depth
- pacing
- whitespace
- section transitions

Use GSAP for:
- restrained hero reveal
- image clipping
- subtle depth
- technical diagram sequencing
- comparison transition
- CTA reveal

Do not turn the page into an animation showcase.

## Neumorphism pass

Check whether the surfaces feel:
- tactile
- premium
- subtle
- readable

Fix:
- excessive shadows
- weak contrast
- muddy surfaces
- repetitive card shapes

Introduce flat editorial surfaces where neumorphism adds no value.

## Typography pass

Check:
- display scale
- line length
- paragraph measure
- heading rhythm
- numerals
- metadata
- labels

Make sure the page can be scanned quickly.

## Content pass

The article should answer:
1. What is AC charging?
2. What is DC charging?
3. Why are they different?
4. What affects charging speed?
5. What does the site actually need?
6. How does dwell time affect the decision?
7. What infrastructure is involved?
8. When does AC make sense?
9. When does DC make sense?
10. When does a mixed approach make sense?

Rephrase copy where needed so it feels human and authoritative.

Never add unsupported technical claims.

## Interaction pass

Check:
- buttons
- hover
- focus
- comparison interaction
- accordion
- progress
- scrolling
- touch interactions

Remove any interaction that does not improve understanding.

## Mobile pass

Do a dedicated mobile art direction pass.

Check:
- 320px
- 375px
- 390px
- 430px

Ensure:
- no horizontal overflow
- no clipped headings
- no tiny text
- no oversized hero
- no unusable comparison
- no broken sticky elements
- no excessive animation

## Accessibility pass

Verify:
- keyboard
- focus
- reduced motion
- contrast
- semantic headings
- aria states
- alt text
- button/link semantics

## Breakdance pass

Open the page in Breakdance and verify:
- each custom widget is easy to find
- labels are understandable
- controls are organized
- changes preview correctly
- builder remains responsive
- widget output does not depend on fragile DOM selectors
- existing Breakdance elements are untouched

## Performance pass

Remove:
- unnecessary assets
- duplicate scripts
- duplicate fonts
- unused CSS
- unused JS
- excessive observers

Verify:
- GSAP loads conditionally
- media is optimized
- animations do not cause layout thrashing

## Final acceptance

The final page should be:
- visually stronger than the reference
- original in execution
- premium
- cinematic
- technically credible
- accessible
- fast
- responsive
- Breakdance-editable
- safe for existing WordPress pages

## Final deliverables

Return:
1. final audit
2. fixes made
3. remaining known limitations
4. QA results
5. final plugin ZIP readiness
6. short instructions for the site owner to edit the widgets in Breakdance

Do not declare “production ready” unless the checks actually pass.
