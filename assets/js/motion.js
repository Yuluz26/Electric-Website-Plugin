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
	   Hero reveal — strongest motion moment.
	   ------------------------------------------------------------------ */
	function heroReveal() {
		EVPX.each( '.evpx-hero[data-evpx-animate="1"]', document, function ( hero ) {
			var media = hero.querySelector( '.evpx-hero__media' );
			var items = hero.querySelectorAll( '.evpx-hero__content > *' );

			var tl = gsap.timeline( { defaults: { ease: EASE } } );

			if ( media ) {
				tl.fromTo( media, { autoAlpha: 0, scale: 1.04 }, { autoAlpha: 1, scale: 1, duration: 1.2, clearProps: 'transform' }, 0 );
			}
			if ( items.length ) {
				tl.fromTo(
					items,
					{ autoAlpha: 0, y: 20 },
					// clearProps: GSAP's leftover inline transform would otherwise
					// beat the CSS :hover transform on the CTA button.
					{ autoAlpha: 1, y: 0, duration: 0.8, stagger: 0.1, clearProps: 'transform' },
					0.2
				);
			}

			// The timeline has already applied its from-state inline, so the
			// CSS hold-back (see "Hero entrance" in evpx.css) can let go now.
			hero.setAttribute( 'data-evpx-ready', '1' );
		} );
	}

	/* ------------------------------------------------------------------
	   Generic scroll reveal for section-level content.
	   ------------------------------------------------------------------ */
	function sectionReveals() {
		if ( ! hasScrollTrigger ) {
			return;
		}

		var fold = window.innerHeight * 0.85;

		EVPX.each( '[data-evpx-reveal]', document, function ( el ) {
			// Already on screen at load: hiding it just to fade it back in
			// reads as a flicker, so leave it be. Only content that starts
			// below the fold is held back and revealed on approach.
			if ( el.getBoundingClientRect().top < fold ) {
				return;
			}

			track(
				gsap.fromTo(
					el,
					{ autoAlpha: 0, y: 28 },
					{
						autoAlpha: 1,
						y: 0,
						duration: 0.9,
						ease: EASE,
						clearProps: 'transform', // keep CSS :hover transforms working
						scrollTrigger: {
							trigger: el,
							start: 'top 85%',
							once: true,
						},
					}
				).scrollTrigger
			);
		} );
	}

	/* ------------------------------------------------------------------
	   Technical flow — sequence steps in on scroll.
	   ------------------------------------------------------------------ */
	function flowSequence() {
		if ( ! hasScrollTrigger ) {
			return;
		}

		EVPX.each( '.evpx-flow[data-evpx-animate="1"]', document, function ( flow ) {
			var steps = flow.querySelectorAll( '.evpx-flow__step' );
			if ( ! steps.length ) {
				return;
			}

			track(
				gsap
					.timeline( {
						scrollTrigger: { trigger: flow, start: 'top 75%', once: true },
					} )
					.fromTo(
						steps,
						{ autoAlpha: 0, y: 16 },
						{ autoAlpha: 1, y: 0, duration: 0.5, ease: EASE, stagger: 0.12, clearProps: 'transform' }
					).scrollTrigger
			);
		} );
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
		if ( open ) {
			panel.hidden = false;
			gsap.fromTo(
				panel,
				{ height: 0, autoAlpha: 0 },
				{
					height: 'auto',
					autoAlpha: 1,
					duration: 0.4,
					ease: EASE,
				}
			);
		} else {
			gsap.to( panel, {
				height: 0,
				autoAlpha: 0,
				duration: 0.3,
				ease: EASE,
				onComplete: function () {
					panel.hidden = true;
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
		flowSequence();
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
