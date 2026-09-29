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

	/**
	 * The hero's entrance is held back in CSS until motion.js takes over
	 * (data-evpx-ready). Whenever motion isn't going to run at all — builder
	 * canvas, reduced motion, GSAP missing — release it so nothing stays hidden.
	 */
	EVPX.releaseHeroes = function () {
		EVPX.each( '.evpx-hero[data-evpx-animate="1"]', document, function ( hero ) {
			hero.setAttribute( 'data-evpx-ready', '1' );
		} );
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

			var tabsWrap = comparison.querySelector( '.evpx-comparison__tabs' );
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

			if ( tabsWrap ) {
				tabsWrap.hidden = false; // rendered hidden: dead buttons without JS

				// WAI-ARIA tabs pattern: arrow keys move between tabs, Home/End jump.
				tabsWrap.addEventListener( 'keydown', function ( event ) {
					var moves = { ArrowLeft: -1, ArrowRight: 1, Home: 'first', End: 'last' };
					if ( ! ( event.key in moves ) ) {
						return;
					}

					var index = Array.prototype.indexOf.call( tabs, document.activeElement );
					if ( index < 0 ) {
						return;
					}

					event.preventDefault();
					var next;
					if ( moves[ event.key ] === 'first' ) {
						next = 0;
					} else if ( moves[ event.key ] === 'last' ) {
						next = tabs.length - 1;
					} else {
						next = ( index + moves[ event.key ] + tabs.length ) % tabs.length;
					}

					tabs[ next ].focus();
					setComparisonState( comparison, tabs, panels, tabs[ next ].getAttribute( 'data-target' ) );
				} );
			}

			// Both panels render visible server-side (no-JS = both readable,
			// stacked). Only once JS actually runs do we collapse to the
			// tabbed interaction, starting from whichever tab is marked
			// default (falls back to "ac").
			var initial = comparison.getAttribute( 'data-default' ) || 'ac';
			setComparisonState( comparison, tabs, panels, initial );
			watchThumb( comparison );
		} );
	}

	function setComparisonState( comparison, tabs, panels, target ) {
		for ( var i = 0; i < tabs.length; i++ ) {
			var isActive = tabs[ i ].getAttribute( 'data-target' ) === target;
			tabs[ i ].setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
			tabs[ i ].setAttribute( 'tabindex', isActive ? '0' : '-1' );
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

		positionThumb( comparison );

		if ( EVPX.motion && EVPX.motion.onComparisonChange ) {
			EVPX.motion.onComparisonChange( comparison, target );
		}
	}

	/**
	 * The raised thumb behind the active tab is drawn by CSS from four measurements: where the active tab
	 * is and how big. Measured again when the tabs change size (webfonts arriving, a narrower column).
	 * The track is only marked data-evpx-thumb once the numbers are in, and after a style flush, so the
	 * thumb appears in place instead of growing out of a corner.
	 */
	function positionThumb( comparison ) {
		var track = comparison.querySelector( '.evpx-comparison__tabs' );
		var active = comparison.querySelector( '.evpx-comparison__tab--active' );

		if ( ! track || ! active || track.hidden || ! active.offsetWidth ) {
			return;
		}

		track.style.setProperty( '--evpx-thumb-x', active.offsetLeft + 'px' );
		track.style.setProperty( '--evpx-thumb-y', active.offsetTop + 'px' );
		track.style.setProperty( '--evpx-thumb-w', active.offsetWidth + 'px' );
		track.style.setProperty( '--evpx-thumb-h', active.offsetHeight + 'px' );

		if ( ! track.hasAttribute( 'data-evpx-thumb' ) ) {
			void track.offsetWidth; // Flush, so the first placement isn't animated.
			track.setAttribute( 'data-evpx-thumb', '' );
		}
	}

	function watchThumb( comparison ) {
		var track = comparison.querySelector( '.evpx-comparison__tabs' );

		if ( ! track ) {
			return;
		}

		var refresh = function () {
			positionThumb( comparison );
		};

		if ( window.ResizeObserver ) {
			new window.ResizeObserver( refresh ).observe( track );
		}

		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( refresh );
		}
	}

	/* ------------------------------------------------------------------
	   Motion state. The stylesheet only moves things on a widget marked
	   data-evpx-motion="on", and only lets a block show its entrance once it
	   has been marked .evpx-in-view — so a page with no script, a reduced-motion
	   preference or the builder canvas gets the finished, static state.
	   ------------------------------------------------------------------ */
	function initMotionState( root ) {
		if ( ! EVPX.motionAllowed() ) {
			return;
		}

		EVPX.each( '.evpx-root', root, function ( widget ) {
			// A widget whose own "animation" control is off stays still, whatever the visitor's settings.
			if ( widget.getAttribute( 'data-evpx-animate' ) !== '0' ) {
				widget.setAttribute( 'data-evpx-motion', 'on' );
			}
		} );

		var targets = [];
		EVPX.each( '[data-evpx-reveal], .evpx-comparison, .evpx-flow', root, function ( el ) {
			if ( markBound( el, 'inview' ) ) {
				targets.push( el );
			}
		} );

		var show = function ( el ) {
			el.classList.add( 'evpx-in-view' );
		};

		if ( ! ( 'IntersectionObserver' in window ) ) {
			targets.forEach( show );
			return;
		}

		var observer = new window.IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						show( entry.target );
						observer.unobserve( entry.target );
					}
				} );
			},
			{ rootMargin: '0px 0px -12% 0px', threshold: 0.15 }
		);

		targets.forEach( function ( el ) {
			observer.observe( el );
		} );
	}

	/**
	 * A soft highlight that follows a fine pointer across the cards that ask for it
	 * ([data-evpx-spot]). One listener for the page; it only writes two custom properties, batched to
	 * a frame, and the stylesheet decides what they look like (and shows nothing on touch).
	 */
	function initSpotlight() {
		if ( EVPX.__spotlightBound || ! EVPX.motionAllowed() ) {
			return;
		}

		if ( ! window.matchMedia || ! window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches ) {
			return;
		}

		EVPX.__spotlightBound = true;

		var frame = 0;
		var card = null;
		var x = 0;
		var y = 0;

		document.addEventListener(
			'pointermove',
			function ( event ) {
				var found = event.target && event.target.closest ? event.target.closest( '[data-evpx-spot]' ) : null;

				if ( ! found ) {
					return;
				}

				var box = found.getBoundingClientRect();
				card = found;
				x = event.clientX - box.left;
				y = event.clientY - box.top;

				if ( ! frame ) {
					frame = window.requestAnimationFrame( function () {
						frame = 0;
						card.style.setProperty( '--evpx-mx', x + 'px' );
						card.style.setProperty( '--evpx-my', y + 'px' );
					} );
				}
			},
			{ passive: true }
		);
	}

	/* ------------------------------------------------------------------
	   Boot
	   ------------------------------------------------------------------ */
	function init( root ) {
		initFaq( root );
		initComparison( root );
		initMotionState( root );
		initSpotlight();

		if ( ! EVPX.motionAllowed() ) {
			EVPX.releaseHeroes();
		}
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
