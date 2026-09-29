/**
 * EV Charging Experience — GSAP motion layer.
 *
 * Pure enhancement: every function here is optional. If this file, or
 * GSAP/ScrollTrigger, fails to load, evpx.js's vanilla handlers keep the
 * page fully functional (see the `EVPX.motion &&` guards there).
 *
 * Gating, in order, before a single animation runs:
 *   1. window.gsap must exist (CDN could fail, ad-blockers, offline CDN).
 *   2. EVPX.motionAllowed() — off in the Breakdance builder iframe, off
 *      under prefers-reduced-motion, off for any non-top-frame context.
 */
( function ( window, document ) {
	'use strict';

	var EVPX = ( window.EVPX = window.EVPX || {} );

	if ( EVPX.__motionInitialized ) {
		return;
	}
	EVPX.__motionInitialized = true;

	function release() {
		if ( EVPX.releaseHeroes ) {
			EVPX.releaseHeroes();
		}
	}

	if ( typeof window.gsap === 'undefined' ) {
		release(); // No GSAP on the page — baseline vanilla behaviour stands.
		return;
	}

	if ( ! EVPX.motionAllowed || ! EVPX.motionAllowed() ) {
		release();
		return;
	}

	var gsap = window.gsap;
	var hasScrollTrigger = typeof window.ScrollTrigger !== 'undefined';

	if ( hasScrollTrigger ) {
		gsap.registerPlugin( window.ScrollTrigger );
	}

	var triggers = [];
	function track( st ) {
		if ( st ) {
			triggers.push( st );
		}
		return st;
	}

	var EASE = 'cubic-bezier(0.16, 1, 0.3, 1)';

	/* ------------------------------------------------------------------
	   Hero reveal — strongest motion moment. The picture settles, the
	   title rises out of a mask, the rest follows in sequence. The line at
	   the foot of the hero charges in CSS (see "Motion" in evpx.css).
	   ------------------------------------------------------------------ */
	function heroReveal() {
		EVPX.each( '.evpx-hero[data-evpx-animate="1"]', document, function ( hero ) {
			var media = hero.querySelector( '.evpx-hero__media' );
			var image = hero.querySelector( '.evpx-hero__image' );
			var title = hero.querySelector( '.evpx-hero__title' );
			var rest = hero.querySelectorAll( '.evpx-hero__content > *:not(.evpx-hero__title)' );

			// GSAP can arrive after the CSS failsafe has already shown the hero (a slow CDN). Hiding it to play
			// the entrance then would blank what the visitor is already reading, so let it be.
			if ( title && ! hero.hasAttribute( 'data-evpx-ready' ) && window.getComputedStyle( title ).opacity === '1' ) {
				hero.setAttribute( 'data-evpx-ready', '1' );
				return;
			}

			// The CSS hold-back lets go the moment this function ends, so what it was holding back is hidden here,
			// now, rather than when the timeline first ticks: a tween that starts later in a timeline is not
			// guaranteed to apply its from-state at once, and the gap would show as a one-frame flash.
			gsap.set( [ media, title ].concat( Array.prototype.slice.call( rest ) ).filter( Boolean ), { autoAlpha: 0 } );

			var tl = gsap.timeline( { defaults: { ease: EASE } } );

			if ( media ) {
				tl.fromTo( media, { autoAlpha: 0 }, { autoAlpha: 1, duration: 1, clearProps: 'opacity,visibility' }, 0 );
			}
			if ( image ) {
				tl.fromTo( image, { scale: 1.08 }, { scale: 1, duration: 2.2, clearProps: 'transform' }, 0 );
			}
			if ( title ) {
				// The mask reaches below the baseline so descenders aren't cut while it opens.
				tl.fromTo(
					title,
					{ autoAlpha: 0, y: 40, clipPath: 'inset(0 0 100% 0)' },
					{ autoAlpha: 1, y: 0, clipPath: 'inset(0 0 -14% 0)', duration: 1.1, clearProps: 'transform,clipPath' },
					0.15
				);
			}
			if ( rest.length ) {
				tl.fromTo(
					rest,
					{ autoAlpha: 0, y: 18 },
					// clearProps: GSAP's leftover inline transform would otherwise
					// beat the CSS :hover transform on the CTA button.
					{ autoAlpha: 1, y: 0, duration: 0.8, stagger: 0.1, clearProps: 'transform' },
					0.4
				);
			}

			// A picture drifts slower than the page as the hero leaves (the CSS gives it the room to).
			if ( media && hasScrollTrigger ) {
				track(
					gsap.to( media, {
						yPercent: 5,
						ease: 'none',
						scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true },
					} ).scrollTrigger
				);
			}

			// The timeline has already applied its from-state inline, so the
			// CSS hold-back (see "Hero entrance" in evpx.css) can let go now.
			hero.setAttribute( 'data-evpx-ready', '1' );
		} );
	}

	/* ------------------------------------------------------------------
	   Generic scroll reveal for section-level content. Blocks that arrive
	   together (a row of cards) come in one after another, not as a slab.
	   ------------------------------------------------------------------ */
	function sectionReveals() {
		if ( ! hasScrollTrigger ) {
			return;
		}

		var fold = window.innerHeight * 0.85;
		var held = [];

		EVPX.each( '[data-evpx-reveal]', document, function ( el ) {
			// Already on screen at load: hiding it just to fade it back in
			// reads as a flicker, so leave it be. Only content that starts
			// below the fold is held back and revealed on approach.
			if ( el.getBoundingClientRect().top >= fold ) {
				held.push( el );
			}
		} );

		if ( ! held.length ) {
			return;
		}

		gsap.set( held, { autoAlpha: 0, y: 28 } );

		ScrollTrigger.batch( held, {
			start: 'top 85%',
			once: true,
			batchMax: 6,
			onEnter: function ( batch ) {
				gsap.to( batch, {
					autoAlpha: 1,
					y: 0,
					duration: 0.9,
					ease: EASE,
					stagger: 0.09,
					overwrite: true,
					clearProps: 'transform', // keep CSS :hover transforms working
				} );
			},
		} ).forEach( track );
	}

	/* ------------------------------------------------------------------
	   Reading progress bar.
	   ------------------------------------------------------------------ */
	function readingProgress() {
		if ( ! hasScrollTrigger ) {
			return;
		}

		// Tracks the whole page, not one widget's container — the Hero's
		// progress_bar toggle renders a single fixed-position bar meant to
		// reflect scroll through the entire article, however many EV
		// widgets happen to make it up.
		EVPX.each( '.evpx-progress__fill', document, function ( bar ) {
			track(
				ScrollTrigger.create( {
					trigger: document.documentElement,
					start: 'top top',
					end: 'bottom bottom',
					onUpdate: function ( self ) {
						gsap.set( bar, { scaleX: self.progress } );
					},
				} )
			);
		} );
	}

	/* ------------------------------------------------------------------
	   FAQ panel height animation — called by evpx.js on toggle.
	   ------------------------------------------------------------------ */
	EVPX.motion = EVPX.motion || {};

	EVPX.motion.animateFaqPanel = function ( panel, open ) {
		// The answer's bottom padding travels with its height, or the row would jump by that much when it starts.
		var padding = window.getComputedStyle( panel ).paddingBottom;

		gsap.killTweensOf( panel );

		if ( open ) {
			panel.hidden = false;
			gsap.fromTo(
				panel,
				{ height: 0, paddingBottom: 0, autoAlpha: 0 },
				{
					height: 'auto',
					paddingBottom: padding,
					autoAlpha: 1,
					duration: 0.4,
					ease: EASE,
					clearProps: 'height,paddingBottom',
				}
			);
		} else {
			gsap.to( panel, {
				height: 0,
				paddingBottom: 0,
				autoAlpha: 0,
				duration: 0.3,
				ease: EASE,
				onComplete: function () {
					panel.hidden = true;
					gsap.set( panel, { clearProps: 'height,paddingBottom,opacity,visibility' } );
				},
			} );
		}
	};

	EVPX.motion.onComparisonChange = function ( comparison, target ) {
		var activePanel = comparison.querySelector( '.evpx-comparison__panel--active' );
		if ( ! activePanel ) {
			return;
		}
		gsap.fromTo( activePanel, { autoAlpha: 0, x: target === 'dc' ? 16 : -16 }, { autoAlpha: 1, x: 0, duration: 0.45, ease: EASE } );
	};

	/* ------------------------------------------------------------------
	   Boot + cleanup
	   ------------------------------------------------------------------ */
	function boot() {
		heroReveal();
		sectionReveals();
		readingProgress();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	EVPX.motion.destroy = function () {
		triggers.forEach( function ( st ) {
			if ( st && st.kill ) {
				st.kill();
			}
		} );
		triggers = [];
	};

	window.addEventListener( 'pagehide', EVPX.motion.destroy );
} )( window, document );
