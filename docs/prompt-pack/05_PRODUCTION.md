# PRODUCTION — Harden the EV Charging WordPress + Breakdance Plugin

## Objective

Turn the MVP into a production-ready plugin suitable for installation on a real WordPress + Breakdance website.

The production version must prioritize:
- stability
- compatibility
- maintainability
- performance
- security
- accessibility
- SEO
- editor usability

## Production architecture

Review the MVP and refactor where necessary.

Requirements:
- clear namespaces
- PSR-style organization where practical
- strict separation of concerns
- no unnecessary dependencies
- clear asset lifecycle
- clear Breakdance integration boundary
- reusable components
- no duplicated rendering logic

## Breakdance safety

Use official/documented Breakdance extension capabilities.

Breakdance should remain the page builder.

The plugin must:
- add custom elements
- provide custom controls
- provide plugin-owned styling
- provide plugin-owned behavior

The plugin must NOT:
- modify Breakdance core
- override Breakdance files
- redefine Breakdance global CSS
- hijack Breakdance templates
- replace standard Breakdance functionality
- inject broad CSS into the entire website

If a workaround is required, document it and keep it narrowly scoped.

## Existing page protection

Activation must not rewrite:
- posts
- pages
- Breakdance data
- templates
- global styles
- theme files

Do not run migrations automatically on activation unless absolutely necessary.

If database migrations are required:
- version them
- make them idempotent
- provide rollback strategy
- never destroy user content

## Security

Follow WordPress security practices:
- capability checks
- nonce verification
- sanitization
- escaping
- safe URL handling
- safe HTML handling
- prepared SQL where SQL is necessary
- no arbitrary code execution
- no untrusted remote code execution
- safe admin settings

Never trust Breakdance field input simply because it originates from the builder.

## Asset security

Do not dynamically execute remote JavaScript from arbitrary URLs.

Remote media URLs must be treated as content, not executable code.

For fonts/media:
- use legitimate providers
- use documented URLs
- provide fallbacks
- avoid privacy-invasive third-party calls where possible

## Performance

Implement:
- conditional CSS
- conditional JS
- defer where appropriate
- lazy media
- responsive images
- no oversized assets
- optimized SVGs
- no unnecessary polyfills
- minimal DOM depth

GSAP:
- load only when needed
- use ScrollTrigger only where needed
- disable/reduce animations under reduced motion
- avoid excessive simultaneous timelines

## SEO

Ensure:
- semantic `<article>`
- correct headings
- canonical compatibility
- social metadata compatibility
- no duplicated schema
- valid FAQ structured data only where appropriate
- accessible links
- descriptive media metadata

Do not become an SEO plugin.

## Caching/minification compatibility

Test with common optimization behavior:
- concatenation
- minification
- defer
- cache
- CDN
- CSS optimization

Avoid assumptions about script ordering.

## Breakdance builder performance

In builder:
- avoid expensive observers
- avoid continuous animation loops
- avoid auto-playing video
- avoid scroll-dependent behavior when the canvas is not actually scrolling
- ensure controls update predictably

## Accessibility

Production acceptance:
- keyboard navigation
- focus states
- accessible accordions
- reduced motion
- semantic headings
- sufficient contrast
- touch targets
- alt text
- no inaccessible hover-only content

## Browser support

Validate modern:
- Chrome
- Edge
- Firefox
- Safari
- mobile Safari
- Android Chrome

Do not add old-browser complexity without evidence it is needed.

## Error handling

Use graceful degradation.

If:
- GSAP fails → content remains visible and usable.
- media fails → fallback presentation appears.
- Breakdance integration is unavailable → plugin does not fatal-error.
- optional dependency missing → affected feature disables safely.

## Documentation

Create:
- README
- installation guide
- Breakdance setup guide
- widget documentation
- controls reference
- troubleshooting
- compatibility notes
- development guide
- changelog

## Packaging

Final ZIP should contain only production files.

Exclude:
- node_modules
- development caches
- local environment files
- secrets
- test screenshots unless intentionally included
- source maps unless useful
- unnecessary build artifacts

## Production QA matrix

Test:

### WordPress
- fresh install
- existing site
- activation
- deactivation
- update

### Breakdance
- builder
- frontend
- responsive editor
- existing Breakdance page
- custom element editing

### Frontend
- desktop
- tablet
- mobile
- reduced motion
- keyboard

### Performance
- initial load
- image loading
- JS execution
- CSS footprint

### Failure
- JS disabled
- GSAP unavailable
- missing media
- missing optional dependency

## Definition of production-ready

The plugin is production-ready only when it is:
- installable
- stable
- isolated
- editable
- accessible
- responsive
- performant
- documented
- recoverable
- safe to deactivate
