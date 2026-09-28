/**
 * EV Charging Experience — core runtime.
 *
 * Everything in this file works with zero dependencies (no GSAP) so the
 * page stays fully usable if motion.js or GSAP fails to load. motion.js
 * only ever *enhances* what's already interactive here — it never owns
 * baseline functionality.
 *
 * Namespaced under window.EVPX. Idempotent: safe to run this file twice
 * (e.g. a theme/optimizer plugin that inlines scripts more than once)
 * without double-binding listeners.
 */
( function ( window, document ) {
	'use strict';

	var EVPX = ( window.EVPX = window.EVPX || {} );

	if ( EVPX.__coreInitialized ) {
		return;
	}
	EVPX.__coreInitialized = true;

	EVPX.config = window.EVPX_CONFIG || { builderContext: false };

	EVPX.prefersReducedMotion = function () {
		return (
			window.matchMedia &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		);
	};

	/**
	 * True only for a genuine, non-builder, non-reduced-motion frontend
	 * view. motion.js gates every animation behind this single check.
	 */
	EVPX.motionAllowed = function () {
		if ( EVPX.config.builderContext ) {
			return false;
		}

		if ( EVPX.prefersReducedMotion() ) {
			return false;
		}

		// Generic, tool-agnostic builder-canvas signal: every page-builder
		// (Breakdance included) renders its live canvas inside an iframe.
		try {
			if ( window.self !== window.top ) {
				return false;
			}
		} catch ( e ) {
			// Cross-origin access threw => we ARE inside a foreign frame.
			return false;
		}

		return true;
	};

	/** Runs `fn` once per matched element, skipping already-bound nodes. */
	EVPX.each = function ( selector, root, fn ) {
		var scope = root || document;
		var nodes = scope.querySelectorAll( selector );
		for ( var i = 0; i < nodes.length; i++ ) {
			fn( nodes[ i ], i );
		}
	};

	function markBound( el, key ) {
		var flag = 'data-evpx-bound-' + key;
		if ( el.getAttribute( flag ) === '1' ) {
			return false;
		}
		el.setAttribute( flag, '1' );
		return true;
	}

	/* ------------------------------------------------------------------
	   FAQ accordion — accessible, works without JS motion enhancement.
	   Markup: button[aria-expanded][aria-controls] + panel[id][hidden]
	   ------------------------------------------------------------------ */
	function initFaq( root ) {
		EVPX.each( '.evpx-faq__item', root, function ( item ) {
			if ( ! markBound( item, 'faq' ) ) {
				return;
			}

			var trigger = item.querySelector( '.evpx-faq__question' );
			var panel = item.querySelector( '.evpx-faq__answer' );

			if ( ! trigger || ! panel ) {
				return;
			}

			trigger.addEventListener( 'click', function () {
				var expanded = trigger.getAttribute( 'aria-expanded' ) === 'true';
				setFaqState( item, trigger, panel, ! expanded );
			} );
		} );
	}

	function setFaqState( item, trigger, panel, open ) {
		trigger.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		item.classList.toggle( 'evpx-faq__item--open', open );

		if ( EVPX.motion && EVPX.motion.animateFaqPanel ) {
			EVPX.motion.animateFaqPanel( panel, open );
		} else {
			panel.hidden = ! open;
		}
	}

	/* ------------------------------------------------------------------
	   AC/DC comparison — toggle mode, vanilla baseline.
	   ------------------------------------------------------------------ */
	function initComparison( root ) {
		EVPX.each( '.evpx-comparison[data-mode="toggle"]', root, function ( comparison ) {
			if ( ! markBound( comparison, 'comparison' ) ) {
				return;
			}

			var tabs = comparison.querySelectorAll( '.evpx-comparison__tab' );
			var panels = {
				ac: comparison.querySelector( '.evpx-comparison__panel--ac' ),
				dc: comparison.querySelector( '.evpx-comparison__panel--dc' ),
			};

			for ( var i = 0; i < tabs.length; i++ ) {
				tabs[ i ].addEventListener( 'click', function ( event ) {
					var target = event.currentTarget.getAttribute( 'data-target' );
					setComparisonState( comparison, tabs, panels, target );
				} );
			}

			// Both panels render visible server-side (no-JS = both readable,
			// stacked). Only once JS actually runs do we collapse to the
			// tabbed interaction, starting from whichever tab is marked
			// default (falls back to "ac").
			var initial = comparison.getAttribute( 'data-default' ) || 'ac';
			setComparisonState( comparison, tabs, panels, initial );
		} );
	}

	function setComparisonState( comparison, tabs, panels, target ) {
		for ( var i = 0; i < tabs.length; i++ ) {
			var isActive = tabs[ i ].getAttribute( 'data-target' ) === target;
			tabs[ i ].setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
			tabs[ i ].classList.toggle( 'evpx-comparison__tab--active', isActive );
		}

		Object.keys( panels ).forEach( function ( key ) {
			if ( ! panels[ key ] ) {
				return;
			}
			// Class-based (not the `hidden` attribute) so the mobile
			// "stacked" layout can cleanly override visibility with normal
			// CSS specificity instead of fighting the UA [hidden] rule.
			panels[ key ].classList.toggle( 'evpx-comparison__panel--hidden', key !== target );
			panels[ key ].classList.toggle( 'evpx-comparison__panel--active', key === target );
		} );

		if ( EVPX.motion && EVPX.motion.onComparisonChange ) {
			EVPX.motion.onComparisonChange( comparison, target );
		}
	}

	/* ------------------------------------------------------------------
	   Boot
	   ------------------------------------------------------------------ */
	function init( root ) {
		initFaq( root );
		initComparison( root );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			init( document );
		} );
	} else {
		init( document );
	}

	// Re-init hook for content swapped in dynamically (AJAX, builder live
	// preview refresh) — Breakdance or any future integration can call
	// window.EVPX.init(container) after injecting new markup.
	EVPX.init = init;
} )( window, document );
