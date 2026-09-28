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

	if ( typeof window.gsap === 'undefined' ) {
		return; // No GSAP on the page — baseline vanilla behaviour stands.
	}

	if ( ! EVPX.motionAllowed || ! EVPX.motionAllowed() ) {
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
			var eyebrow = hero.querySelector( '.evpx-eyebrow' );
			var title = hero.querySelector( '.evpx-hero__title' );
			var meta = hero.querySelector( '.evpx-hero__meta' );
			var media = hero.querySelector( '.evpx-hero__media' );

			var tl = gsap.timeline( { defaults: { ease: EASE } } );

			if ( media ) {
				tl.fromTo( media, { autoAlpha: 0, scale: 1.04 }, { autoAlpha: 1, scale: 1, duration: 1.2 }, 0 );
			}
			if ( eyebrow ) {
				tl.fromTo( eyebrow, { autoAlpha: 0, y: 12 }, { autoAlpha: 1, y: 0, duration: 0.6 }, 0.2 );
			}
			if ( title ) {
				tl.fromTo( title, { autoAlpha: 0, y: 24 }, { autoAlpha: 1, y: 0, duration: 0.8 }, 0.32 );
			}
			if ( meta ) {
				tl.fromTo( meta, { autoAlpha: 0, y: 12 }, { autoAlpha: 1, y: 0, duration: 0.6 }, 0.5 );
			}
		} );
	}

	/* ------------------------------------------------------------------
	   Generic scroll reveal for section-level content.
	   ------------------------------------------------------------------ */
	function sectionReveals() {
		if ( ! hasScrollTrigger ) {
			return;
		}

		EVPX.each( '[data-evpx-reveal]', document, function ( el ) {
			track(
				gsap.fromTo(
					el,
					{ autoAlpha: 0, y: 28 },
					{
						autoAlpha: 1,
						y: 0,
						duration: 0.9,
						ease: EASE,
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
						{ autoAlpha: 1, y: 0, duration: 0.5, ease: EASE, stagger: 0.12 }
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

		EVPX.each( '.evpx-article[data-evpx-progress]', document, function ( article ) {
			var bar = article.querySelector( '.evpx-progress__fill' );
			if ( ! bar ) {
				return;
			}

			track(
				ScrollTrigger.create( {
					trigger: article,
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
