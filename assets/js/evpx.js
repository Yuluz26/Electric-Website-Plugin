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

	/* ------------------------------------------------------------------
	   Explorer — how long the car stays, which charger, what reaches the
	   battery. The model is the one in src/Support/ChargingModel.php,
	   operation for operation (the page arrives with its default answer
	   worked out by that, so the two must agree; tests/playwright/
	   explorer-qa.mjs holds them to it). Everything here is presentation
	   of it: the controls, the readout, the chart.
	   ------------------------------------------------------------------ */
	var EXPLORER_CHARGERS = { 'ac-7': [ 'ac', 7 ], 'ac-22': [ 'ac', 22 ], 'dc-50': [ 'dc', 50 ], 'dc-150': [ 'dc', 150 ], 'dc-350': [ 'dc', 350 ] };
	var EXPLORER_MAX = 720;

	function ChargingModel( cfg ) {
		this.cfg = cfg;
	}

	ChargingModel.acceptance = function ( soc ) {
		if ( soc <= 0.5 ) {
			return 1;
		}

		if ( soc <= 0.8 ) {
			return 1 - ( ( soc - 0.5 ) / 0.3 ) * 0.65;
		}

		return 0.35 - ( ( soc - 0.8 ) / 0.2 ) * 0.25;
	};

	ChargingModel.prototype.power = function ( charger, soc ) {
		var spec = EXPLORER_CHARGERS[ charger ];

		if ( spec[ 0 ] === 'ac' ) {
			return Math.min( spec[ 1 ], this.cfg.acLimit );
		}

		return Math.min( spec[ 1 ], this.cfg.dcPeak * ChargingModel.acceptance( soc ) );
	};

	ChargingModel.prototype.run = function ( charger, minutes ) {
		var soc = this.cfg.start;
		var energy = 0;
		var fullAt = null;

		for ( var m = 0; m < minutes; m++ ) {
			var added = Math.min( this.power( charger, soc ) / 60, ( 1 - soc ) * this.cfg.battery );
			energy += added;
			soc += added / this.cfg.battery;

			if ( 1 - soc < 1e-9 ) {
				fullAt = m + 1;
				break;
			}
		}

		return { energy: energy, soc: soc, fullAt: fullAt };
	};

	ChargingModel.prototype.range = function ( energy ) {
		return ( energy / this.cfg.consumption ) * 100;
	};

	ChargingModel.prototype.headroom = function () {
		return ( 1 - this.cfg.start ) * this.cfg.battery;
	};

	/** Power delivered in each minute of the longest dwell (0 once the battery is full). */
	ChargingModel.prototype.curve = function ( charger ) {
		var soc = this.cfg.start;
		var out = [];

		for ( var m = 0; m < EXPLORER_MAX; m++ ) {
			var p = 1 - soc < 1e-9 ? 0 : this.power( charger, soc );
			var added = Math.min( p / 60, ( 1 - soc ) * this.cfg.battery );
			soc += added / this.cfg.battery;
			out.push( p );
		}

		return out;
	};

	EVPX.explorerModel = function ( cfg ) {
		return new ChargingModel( cfg );
	};

	/** The slider runs 0 to 100 and bends toward the short stays, where the differences are. */
	function explorerSnap( minutes ) {
		var step = minutes < 60 ? 5 : minutes < 240 ? 15 : 30;

		return Math.max( 15, Math.min( EXPLORER_MAX, Math.round( minutes / step ) * step ) );
	}

	function explorerMinutes( pos ) {
		return explorerSnap( 15 + 705 * ( pos / 100 ) * ( pos / 100 ) );
	}

	function explorerPos( minutes ) {
		return Math.round( 100 * Math.sqrt( Math.max( 0, minutes - 15 ) / 705 ) );
	}

	function explorerTemplate( text, values ) {
		return text.replace( /\{(\w+)\}/g, function ( whole, key ) {
			return values.hasOwnProperty( key ) ? values[ key ] : whole;
		} );
	}

	/** A top for the power axis a little above the rating, in steps that keep the middle tick a whole number. */
	function explorerNiceMax( rating ) {
		var step = rating <= 10 ? 2 : rating <= 30 ? 10 : rating <= 100 ? 20 : rating <= 200 ? 50 : 100;

		return Math.ceil( ( rating * 1.12 ) / step ) * step;
	}

	var SVG_NS = 'http://www.w3.org/2000/svg';

	function svgEl( name, attrs ) {
		var el = document.createElementNS( SVG_NS, name );

		for ( var key in attrs ) {
			if ( attrs.hasOwnProperty( key ) ) {
				el.setAttribute( key, attrs[ key ] );
			}
		}

		return el;
	}

	/** Power against time on the chosen charger, the time axis bent the same way as the slider. */
	function ExplorerChart( host, timeText ) {
		var L = 60;
		var R = 624;
		var T = 34;
		var B = 226;
		var W = R - L;
		var H = B - T;
		var N = 160;
		var last = null;
		var uid = 'evpx-xc-' + Math.round( Math.random() * 1e6 );
		var clipRect = svgEl( 'rect', { x: L, y: 0, width: 0, height: 260 } );
		var defs = svgEl( 'defs', {} );
		var clip = svgEl( 'clipPath', { id: uid + '-clip' } );
		var gradient = svgEl( 'linearGradient', { id: uid + '-fill', x1: 0, y1: 0, x2: 0, y2: 1 } );
		var grid = svgEl( 'g', { 'class': 'evpx-explorer__grid' } );
		var yLabels = svgEl( 'g', { 'class': 'evpx-explorer__ticks' } );
		var xLabels = svgEl( 'g', { 'class': 'evpx-explorer__ticks' } );
		var area = svgEl( 'path', { 'class': 'evpx-explorer__area', 'clip-path': 'url(#' + uid + '-clip)', fill: 'url(#' + uid + '-fill)' } );
		var dim = svgEl( 'path', { 'class': 'evpx-explorer__curve evpx-explorer__curve--dim' } );
		var blur = svgEl( 'filter', { id: uid + '-blur', filterUnits: 'userSpaceOnUse', x: 0, y: 0, width: 640, height: 260, 'color-interpolation-filters': 'sRGB' } );
		var glow = svgEl( 'path', { 'class': 'evpx-explorer__glow', 'clip-path': 'url(#' + uid + '-clip)', filter: 'url(#' + uid + '-blur)' } );
		var lit = svgEl( 'path', { 'class': 'evpx-explorer__curve', 'clip-path': 'url(#' + uid + '-clip)' } );
		var rated = svgEl( 'path', { 'class': 'evpx-explorer__rated' } );
		var ratedText = svgEl( 'text', { 'class': 'evpx-explorer__tick evpx-explorer__tick--rated', 'text-anchor': 'end' } );
		var markLine = svgEl( 'path', { 'class': 'evpx-explorer__mark' } );
		var markDot = svgEl( 'circle', { 'class': 'evpx-explorer__dot', r: 5 } );
		var markText = svgEl( 'text', { 'class': 'evpx-explorer__tick evpx-explorer__tick--mark', 'text-anchor': 'middle', y: 14 } );
		var unit = svgEl( 'text', { 'class': 'evpx-explorer__tick', x: L, y: T - 12 } );
		var i;

		gradient.appendChild( svgEl( 'stop', { offset: 0, 'class': 'evpx-explorer__stop', 'stop-opacity': 0.34 } ) );
		gradient.appendChild( svgEl( 'stop', { offset: 1, 'class': 'evpx-explorer__stop', 'stop-opacity': 0 } ) );
		blur.appendChild( svgEl( 'feGaussianBlur', { stdDeviation: 4 } ) );
		clip.appendChild( clipRect );
		defs.appendChild( clip );
		defs.appendChild( gradient );
		defs.appendChild( blur );
		unit.textContent = 'kW';

		grid.appendChild( svgEl( 'path', { d: 'M' + L + ' ' + B + 'H' + R, 'class': 'evpx-explorer__axis' } ) );

		[ defs, grid, yLabels, xLabels, unit, area, dim, glow, lit, rated, ratedText, markLine, markDot, markText ].forEach( function ( node ) {
			host.appendChild( node );
		} );

		/**
		 * The drawing's height (its width is always 640 units): taller when it is shown small, so the curve keeps
		 * room to be read. Everything that depends on it is redrawn.
		 */
		this.resize = function ( height ) {
			B = height - 34;
			H = B - T;
			clipRect.setAttribute( 'height', height );
			host.setAttribute( 'viewBox', '0 0 640 ' + height );
			blur.setAttribute( 'height', height );
			grid.firstChild.setAttribute( 'd', 'M' + L + ' ' + B + 'H' + R );

			if ( last ) {
				this.update( last[ 0 ], last[ 1 ], last[ 2 ] );
			}
		};

		/** The time ticks that fit: a small chart drops to three. Redrawn when the drawing's size changes. */
		this.ticks = function ( scale ) {
			var list = scale < 0.6 ? [ 60, 240, 720 ] : [ 60, 120, 240, 480, 720 ];
			var k;

			while ( xLabels.firstChild ) {
				xLabels.removeChild( xLabels.firstChild );
			}

			while ( grid.childNodes.length > 1 ) {
				grid.removeChild( grid.lastChild );
			}

			for ( k = 0; k < list.length; k++ ) {
				var tx = L + W * Math.sqrt( list[ k ] / EXPLORER_MAX );
				var tick = svgEl( 'text', { 'class': 'evpx-explorer__tick', x: tx, y: B + 28, 'text-anchor': list[ k ] === EXPLORER_MAX ? 'end' : 'middle' } );
				tick.textContent = timeText( list[ k ] );
				xLabels.appendChild( tick );
				grid.appendChild( svgEl( 'path', { d: 'M' + tx + ' ' + T + 'V' + B } ) );
			}
		};

		this.update = function ( curve, charger, minutes ) {
			var rating = EXPLORER_CHARGERS[ charger ][ 1 ];

			last = [ curve, charger, minutes ];
			var ymax = explorerNiceMax( rating );
			var peak = 0;
			var d = '';
			var j;
			var levels = [ 0, ymax / 2, ymax ];

			for ( j = 0; j < curve.length; j++ ) {
				peak = Math.max( peak, curve[ j ] );
			}

			for ( j = 0; j <= N; j++ ) {
				var t = EXPLORER_MAX * ( j / N ) * ( j / N );
				var p = curve[ Math.min( EXPLORER_MAX - 1, Math.floor( t ) ) ];
				d += ( j ? 'L' : 'M' ) + ( L + ( W * j ) / N ).toFixed( 1 ) + ' ' + ( B - ( H * p ) / ymax ).toFixed( 1 );
			}

			dim.setAttribute( 'd', d );
			glow.setAttribute( 'd', d );
			lit.setAttribute( 'd', d );
			area.setAttribute( 'd', d + 'L' + R + ' ' + B + 'L' + L + ' ' + B + 'Z' );

			while ( yLabels.firstChild ) {
				yLabels.removeChild( yLabels.firstChild );
			}

			for ( j = 0; j < levels.length; j++ ) {
				var ly = B - ( H * levels[ j ] ) / ymax;
				var label = svgEl( 'text', { 'class': 'evpx-explorer__tick', x: L - 10, y: ly + 5, 'text-anchor': 'end' } );
				label.textContent = String( Math.round( levels[ j ] ) );
				yLabels.appendChild( label );

				if ( j ) {
					yLabels.appendChild( svgEl( 'path', { 'class': 'evpx-explorer__gridline', d: 'M' + L + ' ' + ly + 'H' + R } ) );
				}
			}

			// A charger rated above what the car takes: its rating, as a dashed line the curve never reaches.
			if ( rating > peak + 0.5 ) {
				var ry = B - ( H * rating ) / ymax;
				rated.setAttribute( 'd', 'M' + L + ' ' + ry + 'H' + R );
				ratedText.setAttribute( 'x', R );
				ratedText.setAttribute( 'y', ry - 8 );
				ratedText.textContent = explorerTemplate( this.ratedText, { kw: rating + '\u00a0kW' } );
				rated.removeAttribute( 'hidden' );
				ratedText.removeAttribute( 'hidden' );
			} else {
				rated.setAttribute( 'hidden', '' );
				ratedText.setAttribute( 'hidden', '' );
			}

			this.mark( curve, ymax, minutes );
		};

		this.mark = function ( curve, ymax, minutes ) {
			var mx = L + W * Math.sqrt( minutes / EXPLORER_MAX );
			var p = curve[ Math.min( EXPLORER_MAX - 1, minutes - 1 ) ];

			clipRect.setAttribute( 'width', mx - L );
			markLine.setAttribute( 'd', 'M' + mx + ' ' + ( T + 8 ) + 'V' + B );
			markDot.setAttribute( 'cx', mx );
			markDot.setAttribute( 'cy', B - ( H * p ) / ymax );
			markText.setAttribute( 'x', Math.max( L + 22, Math.min( R - 22, mx ) ) );
			markText.textContent = timeText( minutes );
		};

		this.ratedText = '{kw}';
	}

	function initExplorer( root ) {
		EVPX.each( '[data-evpx-explorer]', root, function ( widget ) {
			if ( ! markBound( widget, 'explorer' ) ) {
				return;
			}

			var cfg;
			var strings;

			try {
				cfg = JSON.parse( widget.getAttribute( 'data-evpx-explorer' ) );
				strings = JSON.parse( widget.getAttribute( 'data-evpx-strings' ) );
			} catch ( e ) {
				return;
			}

			var model = new ChargingModel( cfg );
			var lang = document.documentElement.lang || undefined;
			var range = widget.querySelector( '.evpx-explorer__range' );
			var controls = widget.querySelector( '.evpx-explorer__controls' );
			var chartHost = widget.querySelector( '.evpx-explorer__chart' );
			var verdict = widget.querySelector( '[data-out="verdict"]' );
			var perKm = cfg.unit === 'mi' ? 0.621371 : 1;
			var state = { charger: cfg.charger, minutes: cfg.dwell };
			var curves = {};
			var timer = 0;

			var number = function ( value, decimals ) {
				var digits = decimals === undefined ? ( value < 10 ? 1 : 0 ) : decimals;

				try {
					return new Intl.NumberFormat( lang, { minimumFractionDigits: digits, maximumFractionDigits: digits } ).format( value );
				} catch ( e ) {
					return value.toFixed( digits );
				}
			};

			// A figure and its unit stay on one line: a sentence never breaks between "122" and "km".
			var withUnit = function ( figure, unit ) {
				return figure + '\u00a0' + unit;
			};

			var timeText = function ( minutes ) {
				var hours = Math.floor( minutes / 60 );
				var rest = minutes % 60;

				if ( hours === 0 ) {
					return withUnit( rest, strings.min );
				}

				return withUnit( hours, strings.h ) + ( rest ? ' ' + withUnit( rest, strings.min ) : '' );
			};

			var out = function ( name, text ) {
				var el = widget.querySelector( '[data-out="' + name + '"]' );

				if ( el && el.textContent !== text ) {
					el.textContent = text;
				}
			};

			var chart = null;

			if ( chartHost ) {
				chart = new ExplorerChart( chartHost, timeText );
				chart.ratedText = strings.rated;

				// The drawing is scaled to the width it has, and its text with it. Keeping the labels at a size a
				// person can read means telling the stylesheet the scale, and drawing the fewer ticks a small one fits.
				var box = chartHost.parentNode;
				var fit = function () {
					var width = box.getBoundingClientRect().width || 640;
					var scale = width / 640;
					var height = scale < 0.6 ? 400 : 260;

					box.style.aspectRatio = '640 / ' + height;
					chartHost.style.setProperty( '--evpx-tick', ( 12.5 / scale ).toFixed( 2 ) + 'px' );
					chart.ticks( scale );
					chart.resize( height );
				};

				fit();

				if ( 'ResizeObserver' in window ) {
					new window.ResizeObserver( fit ).observe( box );
				} else {
					window.addEventListener( 'resize', fit );
				}
			}

			var curveFor = function ( charger ) {
				if ( ! curves[ charger ] ) {
					curves[ charger ] = model.curve( charger );
				}

				return curves[ charger ];
			};

			var say = function () {
				var spec = EXPLORER_CHARGERS[ state.charger ];
				var run = model.run( state.charger, state.minutes );
				var delivered = Math.min( spec[ 1 ], spec[ 0 ] === 'ac' ? cfg.acLimit : cfg.dcPeak );
				var text = explorerTemplate( strings.main, {
					time: timeText( state.minutes ),
					kw: withUnit( spec[ 1 ], 'kW' ),
					energy: withUnit( number( run.energy ), 'kWh' ),
					range: withUnit( number( model.range( run.energy ) * perKm, 0 ), cfg.unit )
				} );

				if ( run.fullAt !== null ) {
					text += ' ' + explorerTemplate( strings.full, { full: timeText( run.fullAt ) } );
				} else if ( delivered < spec[ 1 ] ) {
					text += ' ' + explorerTemplate( strings.limited, { limit: withUnit( number( delivered, 0 ), 'kW' ) } );
				}

				out( 'verdict', text );
			};

			var render = function () {
				var run = model.run( state.charger, state.minutes );

				out( 'energy', number( run.energy ) );
				out( 'range', number( model.range( run.energy ) * perKm, 0 ) );
				out( 'soc-from', String( Math.round( cfg.start * 100 ) ) );
				out( 'soc-to', String( Math.round( run.soc * 100 ) ) );

				var fill = widget.querySelector( '.evpx-explorer__track-fill' );

				if ( fill ) {
					fill.style.setProperty( '--evpx-from', cfg.start );
					fill.style.setProperty( '--evpx-fill', run.soc );
				}

				EVPX.each( '.evpx-explorer__row', widget, function ( row ) {
					var key = row.getAttribute( 'data-charger' );
					var result = model.run( key, state.minutes );
					var bar = row.querySelector( '.evpx-explorer__bar-fill' );
					var amount = row.querySelector( '.evpx-explorer__amount-value' );

					row.classList.toggle( 'evpx-explorer__row--selected', key === state.charger );
					bar.style.setProperty( '--evpx-fill', model.headroom() > 0 ? Math.min( 1, result.energy / model.headroom() ) : 0 );
					amount.textContent = number( result.energy );
				} );

				if ( chart ) {
					chart.update( curveFor( state.charger ), state.charger, state.minutes );
				}

				EVPX.each( '.evpx-explorer__preset', widget, function ( button ) {
					button.setAttribute( 'aria-pressed', Number( button.getAttribute( 'data-minutes' ) ) === state.minutes ? 'true' : 'false' );
				} );

				widget.querySelector( '.evpx-explorer__dwell' ).textContent = timeText( state.minutes );

				// The sentence waits for the pointer to settle, so a screen reader hears one message, not a dozen.
				window.clearTimeout( timer );
				timer = window.setTimeout( say, 350 );
			};

			var setMinutes = function ( minutes ) {
				state.minutes = minutes;

				if ( range ) {
					range.value = explorerPos( minutes );
					range.style.setProperty( '--evpx-pos', range.value );
				}

				render();
			};

			if ( range ) {
				range.setAttribute( 'aria-valuetext', timeText( state.minutes ) );
				range.addEventListener( 'input', function () {
					state.minutes = explorerMinutes( Number( range.value ) );
					range.style.setProperty( '--evpx-pos', range.value );
					range.setAttribute( 'aria-valuetext', timeText( state.minutes ) );
					render();
				} );
			}

			EVPX.each( '.evpx-explorer__radio', widget, function ( radio ) {
				radio.addEventListener( 'change', function () {
					if ( radio.checked ) {
						state.charger = radio.value;
						render();
					}
				} );
			} );

			EVPX.each( '.evpx-explorer__preset', widget, function ( button ) {
				button.addEventListener( 'click', function () {
					setMinutes( Number( button.getAttribute( 'data-minutes' ) ) );

					if ( range ) {
						range.setAttribute( 'aria-valuetext', timeText( state.minutes ) );
					}
				} );
			} );

			if ( controls ) {
				controls.hidden = false;
			}

			if ( chartHost ) {
				chartHost.removeAttribute( 'hidden' ); // an <svg> has no .hidden property to unset
			}

			render();
			say();
		} );
	}

	/**
	 * Artwork. A drawing is scaled to the width it is shown at, and its labels with it, so a small one would carry
	 * text too small to read; each is told the scale and sets its labels to read as 12px or more (never so large
	 * they crowd the drawing). Its drawing-in and its one endless movement (the pulse along the cable) only run
	 * while it is on screen: one below the fold draws itself in when it is reached, and none keeps animating unseen.
	 */
	function initArt( root ) {
		var fit = function ( art ) {
			var box = art.viewBox && art.viewBox.baseVal;
			var scale = art.getBoundingClientRect().width / ( box && box.width ? box.width : 640 );

			if ( scale > 0 ) {
				art.style.setProperty( '--evpx-art-label', Math.max( 12.5, Math.min( 22, 12.5 / scale ) ).toFixed( 1 ) + 'px' );
			}
		};

		var sizes = 'ResizeObserver' in window ? new window.ResizeObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				fit( entry.target );
			} );
		} ) : null;

		var seen = EVPX.motionAllowed() && 'IntersectionObserver' in window ? new window.IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					entry.target.classList.toggle( 'evpx-art--paused', ! entry.isIntersecting );
				} );
			},
			{ threshold: 0.15 }
		) : null;

		EVPX.each( '.evpx-art', root, function ( art ) {
			if ( ! markBound( art, 'art' ) ) {
				return;
			}

			fit( art );

			if ( sizes ) {
				sizes.observe( art );
			}

			if ( seen ) {
				seen.observe( art );
			}
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
	   Page hero (Stage): the scene's layers slide against each other with the pointer and the page, a canvas of
	   weather runs over it, and the figures count up when they are seen. All of it waits for motion: without it
	   the hero is the finished still, figures included.
	   ------------------------------------------------------------------ */
	function clamp01( n ) {
		return n < 0 ? 0 : n > 1 ? 1 : n;
	}

	function initStage( root ) {
		EVPX.each( '[data-evpx-stage]', root, function ( stage ) {
			if ( ! markBound( stage, 'stage' ) ) {
				return;
			}

			if ( ! EVPX.motionAllowed() || stage.getAttribute( 'data-evpx-animate' ) === '0' ) {
				return;
			}

			countUp( stage, '.evpx-stage__num' );
			stageParallax( stage );
			stageWeather( stage );
		} );
	}

	/** "1,250.5" as a counter would set it: the same decimals and grouping the editor typed. */
	function formatCount( value, sample ) {
		var decimals = sample.indexOf( '.' ) > -1 ? sample.length - sample.indexOf( '.' ) - 1 : 0;
		var text = value.toFixed( decimals );

		if ( sample.indexOf( ',' ) > -1 ) {
			var parts = text.split( '.' );
			parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, ',' );
			text = parts.join( '.' );
		}

		return text;
	}

	/** Counts every [data-count] in `scope` up from zero, once, when it comes into view; `numSelector` is the part that changes. */
	function countUp( scope, numSelector ) {
		var counters = scope.querySelectorAll( '[data-count]' );

		if ( ! counters.length || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		EVPX.each( '[data-count]', scope, function ( el ) {
			var num = el.querySelector( numSelector );
			if ( num ) {
				num.textContent = formatCount( 0, el.getAttribute( 'data-count' ) );
			}
		} );

		var watcher = new window.IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( ! entry.isIntersecting ) {
					return;
				}

				watcher.unobserve( entry.target );
				var sample = entry.target.getAttribute( 'data-count' );
				var target = parseFloat( sample.replace( /,/g, '' ) );
				var num = entry.target.querySelector( numSelector );
				var began = 0;

				if ( ! num || isNaN( target ) ) {
					return;
				}

				var step = function ( now ) {
					began = began || now;
					var t = clamp01( ( now - began ) / 1600 );
					var eased = t === 1 ? 1 : 1 - Math.pow( 2, -10 * t );
					num.textContent = formatCount( target * eased, sample );

					if ( t < 1 ) {
						window.requestAnimationFrame( step );
					}
				};

				window.requestAnimationFrame( step );
			} );
		}, { threshold: 0.6 } );

		EVPX.each( '[data-count]', scope, function ( el ) {
			watcher.observe( el );
		} );
	}

	function stageParallax( stage ) {
		var layers = stage.querySelectorAll( '.evpx-scene__layer' );
		var photo = stage.querySelector( '.evpx-stage__image' );

		if ( ! layers.length && ! photo ) {
			return;
		}

		var fine = window.matchMedia && window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches;
		var aim = { x: 0, y: 0 };
		var now = { x: 0, y: 0, s: 0 };
		var scroll = 0;
		var frame = 0;
		var visible = true;

		var apply = function () {
			frame = 0;
			now.x += ( aim.x - now.x ) * 0.08;
			now.y += ( aim.y - now.y ) * 0.08;
			now.s += ( scroll - now.s ) * 0.12;

			for ( var i = 0; i < layers.length; i++ ) {
				var d = parseFloat( layers[ i ].getAttribute( 'data-depth' ) ) || 0;
				layers[ i ].style.transform = 'translate(' + ( -now.x * d * 46 ).toFixed( 2 ) + 'px,' + ( -now.y * d * 22 + now.s * d * 90 ).toFixed( 2 ) + 'px)';
			}

			if ( Math.abs( aim.x - now.x ) > 0.002 || Math.abs( aim.y - now.y ) > 0.002 || Math.abs( scroll - now.s ) > 0.002 ) {
				frame = window.requestAnimationFrame( apply );
			}
		};

		var wake = function () {
			if ( ! frame && visible ) {
				frame = window.requestAnimationFrame( apply );
			}
		};

		if ( fine ) {
			stage.addEventListener( 'pointermove', function ( event ) {
				var box = stage.getBoundingClientRect();
				aim.x = ( event.clientX - box.left ) / box.width - 0.5;
				aim.y = ( event.clientY - box.top ) / box.height - 0.5;
				wake();
			}, { passive: true } );
		}

		window.addEventListener( 'scroll', function () {
			var box = stage.getBoundingClientRect();
			scroll = clamp01( -box.top / Math.max( 1, box.height ) );
			wake();
		}, { passive: true } );

		if ( 'IntersectionObserver' in window ) {
			new window.IntersectionObserver( function ( entries ) {
				visible = entries[ 0 ].isIntersecting;
			} ).observe( stage );
		}
	}

	/* The weather over each scene, on a canvas the size of the hero. Coordinates are the scene's own (1600 x 900,
	   scaled the way the SVG is, to cover), so a spark rises from the charger it is drawn beside. */
	function stageWeather( stage ) {
		var canvas = stage.querySelector( '.evpx-stage__fx' );
		var kind = stage.getAttribute( 'data-evpx-scene' );

		if ( ! canvas || ! canvas.getContext ) {
			return;
		}

		var ctx = canvas.getContext( '2d' );
		var w = 0;
		var h = 0;
		var k = 1;
		var ox = 0;
		var oy = 0;
		var things = [];
		var running = false;
		var last = 0;
		var visible = true;
		var rnd = Math.random;

		var pt = function ( x, y ) {
			return [ ox + x * k, oy + y * k ];
		};

		var make = {
			station: function () {
				return { x: rnd() * w * 1.2 - w * 0.1, y: rnd() * h, len: 10 + rnd() * 16, v: 700 + rnd() * 700, a: 0.1 + rnd() * 0.16 };
			},
			highway: function ( i ) {
				var lanes = [ [ 260, 900, 0 ], [ 560, 900, 0 ], [ 1250, 900, 1 ], [ 1500, 900, 1 ] ];
				var lane = lanes[ i % 4 ];
				return { t: rnd(), lane: lane, speed: 0.12 + rnd() * 0.22 };
			},
			cabinet: function () {
				return { x: 1120 + ( rnd() - 0.5 ) * 220, y: 620 + rnd() * 40, vx: ( rnd() - 0.5 ) * 30, vy: -( 30 + rnd() * 70 ), life: rnd() * 3, max: 1.6 + rnd() * 2, r: 0.8 + rnd() * 1.8 };
			},
			grid: function () {
				return { x: rnd() * 1600, y: rnd() * 800, vx: ( rnd() - 0.5 ) * 8, vy: ( rnd() - 0.5 ) * 6, r: 0.6 + rnd() * 1.4, p: rnd() * 6.28 };
			},
			plug: function () {
				return { a: rnd() * 6.28, r: 300 + rnd() * 150, w: ( rnd() < 0.5 ? -1 : 1 ) * ( 0.08 + rnd() * 0.22 ), s: 0.8 + rnd() * 2 };
			}
		};
		make.photo = make.grid;

		var size = function () {
			var box = canvas.getBoundingClientRect();
			var dpr = Math.min( window.devicePixelRatio || 1, 1.5 );
			w = Math.max( 1, Math.round( box.width ) );
			h = Math.max( 1, Math.round( box.height ) );
			canvas.width = Math.round( w * dpr );
			canvas.height = Math.round( h * dpr );
			ctx.setTransform( dpr, 0, 0, dpr, 0, 0 );
			k = Math.max( w / 1600, h / 900 );
			ox = ( w - 1600 * k ) / 2;
			oy = ( h - 900 * k ) / 2;

			var count = { station: Math.min( 200, Math.round( w * h / 9000 ) ), highway: 30, cabinet: 60, grid: 70, plug: 80, photo: 45 }[ kind ] || 0;
			var maker = make[ kind ];
			things = [];

			for ( var i = 0; maker && i < count; i++ ) {
				things.push( maker( i ) );
			}
		};

		var draw = {
			station: function ( dt ) {
				ctx.lineWidth = 1;
				for ( var i = 0; i < things.length; i++ ) {
					var d = things[ i ];
					d.y += d.v * dt;
					d.x -= d.v * dt * 0.22;
					if ( d.y > h ) {
						d.y = -d.len;
						d.x = rnd() * w * 1.2;
					}
					ctx.strokeStyle = 'rgba(190,210,255,' + d.a + ')';
					ctx.beginPath();
					ctx.moveTo( d.x, d.y );
					ctx.lineTo( d.x + d.len * 0.22, d.y - d.len );
					ctx.stroke();
				}
			},
			highway: function ( dt ) {
				var vp = pt( 900, 526 );
				for ( var i = 0; i < things.length; i++ ) {
					var d = things[ i ];
					d.t += dt * d.speed * ( 0.35 + d.t * 2.2 );
					if ( d.t > 1 ) {
						d.t = 0;
					}
					var end = pt( d.lane[ 0 ], d.lane[ 1 ] );
					var x = vp[ 0 ] + ( end[ 0 ] - vp[ 0 ] ) * d.t;
					var y = vp[ 1 ] + ( end[ 1 ] - vp[ 1 ] ) * d.t;
					var x0 = vp[ 0 ] + ( end[ 0 ] - vp[ 0 ] ) * Math.max( 0, d.t - 0.06 );
					var y0 = vp[ 1 ] + ( end[ 1 ] - vp[ 1 ] ) * Math.max( 0, d.t - 0.06 );
					ctx.strokeStyle = d.lane[ 2 ] ? 'rgba(255,138,61,' + ( 0.25 + d.t * 0.7 ) + ')' : 'rgba(255,255,255,' + ( 0.2 + d.t * 0.7 ) + ')';
					ctx.lineWidth = 0.8 + d.t * 4;
					ctx.lineCap = 'round';
					ctx.beginPath();
					ctx.moveTo( x0, y0 );
					ctx.lineTo( x, y );
					ctx.stroke();
				}
			},
			cabinet: function ( dt ) {
				ctx.globalCompositeOperation = 'lighter';
				for ( var i = 0; i < things.length; i++ ) {
					var d = things[ i ];
					d.life += dt;
					if ( d.life > d.max ) {
						Object.assign( d, make.cabinet(), { life: 0 } );
					}
					d.x += d.vx * dt;
					d.y += d.vy * dt;
					var p = pt( d.x, d.y );
					var f = 1 - d.life / d.max;
					ctx.fillStyle = 'rgba(255,138,61,' + ( f * 0.85 ).toFixed( 3 ) + ')';
					ctx.beginPath();
					ctx.arc( p[ 0 ], p[ 1 ], d.r * k * 1.4, 0, 6.283 );
					ctx.fill();
				}
				ctx.globalCompositeOperation = 'source-over';
			},
			grid: function ( dt, time ) {
				for ( var i = 0; i < things.length; i++ ) {
					var d = things[ i ];
					d.x += d.vx * dt;
					d.y += d.vy * dt;
					if ( d.x < -20 ) { d.x = 1620; } else if ( d.x > 1620 ) { d.x = -20; }
					if ( d.y < -20 ) { d.y = 820; } else if ( d.y > 820 ) { d.y = -20; }
					var p = pt( d.x, d.y );
					ctx.fillStyle = 'rgba(255,224,190,' + ( 0.18 + 0.22 * Math.sin( time * 0.0012 + d.p ) ).toFixed( 3 ) + ')';
					ctx.beginPath();
					ctx.arc( p[ 0 ], p[ 1 ], d.r * k * 1.3, 0, 6.283 );
					ctx.fill();
				}
			},
			plug: function ( dt ) {
				var c = pt( 1080, 450 );
				ctx.globalCompositeOperation = 'lighter';
				for ( var i = 0; i < things.length; i++ ) {
					var d = things[ i ];
					var a0 = d.a;
					d.a += d.w * dt;
					ctx.strokeStyle = 'rgba(255,138,61,0.42)';
					ctx.lineWidth = d.s * k;
					ctx.lineCap = 'round';
					ctx.beginPath();
					ctx.arc( c[ 0 ], c[ 1 ], d.r * k, Math.min( a0, d.a ) - Math.abs( d.w ) * 0.55, Math.max( a0, d.a ) );
					ctx.stroke();
				}
				ctx.globalCompositeOperation = 'source-over';
			}
		};
		draw.photo = draw.grid;

		var tick = function ( time ) {
			if ( ! running ) {
				return;
			}

			var dt = Math.min( 0.05, ( time - last ) / 1000 || 0.016 );
			last = time;
			ctx.clearRect( 0, 0, w, h );

			if ( draw[ kind ] ) {
				draw[ kind ]( dt, time );
			}

			window.requestAnimationFrame( tick );
		};

		var toggle = function () {
			var should = visible && ! document.hidden;
			if ( should && ! running ) {
				running = true;
				last = 0;
				window.requestAnimationFrame( tick );
			} else if ( ! should ) {
				running = false;
			}
		};

		if ( ! draw[ kind ] ) {
			return;
		}

		size();

		if ( 'ResizeObserver' in window ) {
			new window.ResizeObserver( size ).observe( canvas );
		}

		if ( 'IntersectionObserver' in window ) {
			new window.IntersectionObserver( function ( entries ) {
				visible = entries[ 0 ].isIntersecting;
				toggle();
			} ).observe( stage );
		}

		document.addEventListener( 'visibilitychange', toggle );
		toggle();
	}

	/* ------------------------------------------------------------------
	   Site header: the folded menu, and the search dialog. Ctrl/Cmd + K or / opens it, typing asks the REST search
	   route (published pages and articles, nothing else), the arrow keys move through what it finds and Enter opens
	   one. Without a script the button does nothing and the form, in the dialog, is a plain GET.
	   ------------------------------------------------------------------ */
	function initHeader( root ) {
		EVPX.each( '[data-evpx-header]', root, function ( header ) {
			if ( ! markBound( header, 'header' ) ) {
				return;
			}

			var toggle = header.querySelector( '[data-evpx-menu-toggle]' );
			var menu = header.querySelector( '[data-evpx-menu]' );
			var hint = header.querySelector( '.evpx-header__kbd' );

			// The shortcut is Cmd+K on an Apple keyboard, and the hint beside the search should say so.
			if ( hint && /Mac|iPhone|iPad/.test( window.navigator.platform || '' ) ) {
				hint.textContent = '\u2318 K';
			}

			var stick = function () {
				header.classList.toggle( 'evpx-is-stuck', window.scrollY > 8 );
			};

			window.addEventListener( 'scroll', stick, { passive: true } );
			stick();

			if ( toggle && menu ) {
				var setMenu = function ( open ) {
					toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
					menu.classList.toggle( 'evpx-is-open', open );
				};

				toggle.addEventListener( 'click', function () {
					setMenu( toggle.getAttribute( 'aria-expanded' ) !== 'true' );
				} );

				menu.addEventListener( 'click', function ( event ) {
					if ( event.target.closest && event.target.closest( 'a' ) ) {
						setMenu( false );
					}
				} );

				header.addEventListener( 'keydown', function ( event ) {
					if ( event.key === 'Escape' && toggle.getAttribute( 'aria-expanded' ) === 'true' ) {
						setMenu( false );
						toggle.focus();
					}
				} );
			}

			var box = header.querySelector( '[data-evpx-searchbox]' );

			if ( box ) {
				initSearchBox( box, header );
			}
		} );
	}

	function initSearchBox( box, header ) {
		var input = box.querySelector( '[data-evpx-search-input]' );
		var list = box.querySelector( '[data-evpx-search-results]' );
		var status = box.querySelector( '[data-evpx-search-status]' );
		var idle = status ? status.textContent : '';
		var openers = header.querySelectorAll( '[data-evpx-search-open]' );
		var closers = box.querySelectorAll( '[data-evpx-search-close]' );
		var rest = box.getAttribute( 'data-rest' );
		var timer = 0;
		var request = null;
		var opener = null;
		var active = -1;

		var isOpen = function () {
			return box.open || box.hasAttribute( 'open' );
		};

		var open = function ( from ) {
			opener = from || document.activeElement;

			if ( isOpen() ) {
				return;
			}

			if ( box.showModal ) {
				box.showModal();
			} else {
				box.setAttribute( 'open', '' );
			}

			input.focus();
			input.select();
		};

		var close = function () {
			if ( box.close ) {
				box.close();
			} else {
				box.removeAttribute( 'open' );
			}

			if ( opener && opener.focus ) {
				opener.focus();
			}
		};

		var setStatus = function ( text ) {
			if ( status ) {
				status.textContent = text;
			}
		};

		var select = function ( index ) {
			var items = list.querySelectorAll( '[role="option"]' );

			if ( ! items.length ) {
				active = -1;
				return;
			}

			active = ( index + items.length ) % items.length;

			for ( var i = 0; i < items.length; i++ ) {
				items[ i ].setAttribute( 'aria-selected', i === active ? 'true' : 'false' );
			}

			items[ active ].scrollIntoView( { block: 'nearest' } );
		};

		var show = function ( hits ) {
			list.textContent = '';
			active = -1;

			hits.forEach( function ( hit ) {
				var li = document.createElement( 'li' );
				var a = document.createElement( 'a' );
				var title = document.createElement( 'span' );
				var kind = document.createElement( 'span' );
				var tmp = document.createElement( 'textarea' );

				tmp.innerHTML = hit.title || ''; // The route sends titles HTML-encoded (&amp;, &#8217;); this decodes them as text.
				title.textContent = tmp.value;
				kind.className = 'evpx-searchbox__kind';
				kind.textContent = hit.subtype === 'page' ? ( box.getAttribute( 'data-label-page' ) || 'Page' ) : ( box.getAttribute( 'data-label-post' ) || 'Article' );
				a.className = 'evpx-searchbox__hit';
				a.href = hit.url;
				a.setAttribute( 'role', 'option' );
				a.setAttribute( 'aria-selected', 'false' );
				a.appendChild( title );
				a.appendChild( kind );
				li.setAttribute( 'role', 'presentation' );
				li.appendChild( a );
				list.appendChild( li );
			} );
		};

		var ask = function () {
			var text = input.value.trim();

			if ( request && request.abort ) {
				request.abort();
			}

			if ( text.length < 2 || ! rest || ! window.fetch ) {
				list.textContent = '';
				setStatus( idle );
				return;
			}

			setStatus( '…' );
			request = window.AbortController ? new window.AbortController() : null;

			window.fetch( rest + ( rest.indexOf( '?' ) > -1 ? '&' : '?' ) + 'search=' + encodeURIComponent( text ) + '&per_page=6&_fields=id,title,url,subtype', request ? { signal: request.signal, credentials: 'same-origin' } : { credentials: 'same-origin' } )
				.then( function ( response ) {
					if ( ! response.ok ) {
						throw new Error( 'search ' + response.status );
					}

					return response.json();
				} )
				.then( function ( hits ) {
					show( hits );
					setStatus( hits.length ? hits.length + ( hits.length === 1 ? ' result. ' : ' results. ' ) + 'Enter opens all of them.' : 'Nothing found. Enter searches the whole site.' );
				} )
				.catch( function ( error ) {
					if ( error && error.name === 'AbortError' ) {
						return;
					}

					list.textContent = '';
					setStatus( 'Live results are not available here. Enter searches the whole site.' );
				} );
		};

		for ( var i = 0; i < openers.length; i++ ) {
			openers[ i ].addEventListener( 'click', function ( event ) {
				open( event.currentTarget );
			} );
		}

		for ( var j = 0; j < closers.length; j++ ) {
			closers[ j ].addEventListener( 'click', close );
		}

		box.addEventListener( 'click', function ( event ) {
			if ( event.target === box ) {
				close(); // The backdrop is the dialog's own box.
			}
		} );

		input.addEventListener( 'input', function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( ask, 180 );
		} );

		input.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'ArrowDown' ) {
				event.preventDefault();
				select( active + 1 );
			} else if ( event.key === 'ArrowUp' ) {
				event.preventDefault();
				select( active - 1 );
			} else if ( event.key === 'Enter' && active > -1 ) {
				var chosen = list.querySelectorAll( '[role="option"]' )[ active ];

				if ( chosen ) {
					event.preventDefault();
					window.location.href = chosen.href;
				}
			}
		} );

		if ( ! EVPX.__searchKeys ) {
			EVPX.__searchKeys = true;

			document.addEventListener( 'keydown', function ( event ) {
				var target = event.target;
				var typing = target && ( target.isContentEditable || /^(input|textarea|select)$/i.test( target.tagName ) );
				var shortcut = ( event.key === 'k' || event.key === 'K' ) && ( event.ctrlKey || event.metaKey );

				if ( ! ( shortcut || ( event.key === '/' && ! typing ) ) ) {
					return;
				}

				var first = document.querySelector( '[data-evpx-searchbox]' );

				if ( first && first.__evpxOpen ) {
					event.preventDefault();
					first.__evpxOpen();
				}
			} );
		}

		box.__evpxOpen = open;
	}

	/* ------------------------------------------------------------------
	   Content widgets: stats, services, process, projects, quotes,
	   contact. What a control does (open a panel, move a rail, show the
	   next quotation) works in every view, the builder canvas included;
	   what only moves for show (counting up, the rail filling as you
	   read, the quotations turning on their own) waits for motion.
	   ------------------------------------------------------------------ */
	function pad2( n ) {
		return n < 10 ? '0' + n : String( n );
	}

	/** Gives each child an index the stylesheet can stagger by. */
	function setSteps( nodes ) {
		for ( var i = 0; i < nodes.length; i++ ) {
			nodes[ i ].style.setProperty( '--evpx-step', String( i ) );
		}
	}

	function initStats( root ) {
		EVPX.each( '.evpx-stats', root, function ( stats ) {
			if ( ! markBound( stats, 'stats' ) ) {
				return;
			}

			setSteps( stats.querySelectorAll( '.evpx-stat' ) );

			if ( EVPX.motionAllowed() && stats.getAttribute( 'data-evpx-animate' ) !== '0' ) {
				countUp( stats, '.evpx-stat__num' );
			}
		} );
	}

	function initServices( root ) {
		EVPX.each( '[data-evpx-services]', root, function ( strip ) {
			if ( ! markBound( strip, 'services' ) ) {
				return;
			}

			var items = Array.prototype.slice.call( strip.querySelectorAll( '[data-evpx-service]' ) );

			if ( ! items.length ) {
				return;
			}

			var toggles = items.map( function ( item ) {
				setSteps( item.querySelectorAll( '.evpx-service__body > *' ) );
				return item.querySelector( '[data-evpx-service-toggle]' );
			} );

			var open = function ( chosen ) {
				items.forEach( function ( item, i ) {
					var on = item === chosen;

					if ( on ) {
						item.setAttribute( 'data-open', '1' );
					} else {
						item.removeAttribute( 'data-open' );
					}

					toggles[ i ].setAttribute( 'aria-expanded', on ? 'true' : 'false' );
				} );
			};

			strip.setAttribute( 'data-evpx-ready', '1' );
			open( items[ 0 ] );

			items.forEach( function ( item, i ) {
				toggles[ i ].addEventListener( 'click', function () {
					open( item );
				} );

				// A closed panel is a target as a whole, not only its spine.
				item.addEventListener( 'click', function () {
					if ( ! item.hasAttribute( 'data-open' ) ) {
						open( item );
					}
				} );

				toggles[ i ].addEventListener( 'keydown', function ( event ) {
					var to = -1;

					if ( event.key === 'ArrowRight' || event.key === 'ArrowDown' ) {
						to = ( i + 1 ) % items.length;
					} else if ( event.key === 'ArrowLeft' || event.key === 'ArrowUp' ) {
						to = ( i - 1 + items.length ) % items.length;
					} else if ( event.key === 'Home' ) {
						to = 0;
					} else if ( event.key === 'End' ) {
						to = items.length - 1;
					}

					if ( to > -1 ) {
						event.preventDefault();
						toggles[ to ].focus();
					}
				} );
			} );
		} );
	}

	function initProcess( root ) {
		EVPX.each( '[data-evpx-process]', root, function ( section ) {
			if ( ! markBound( section, 'process' ) ) {
				return;
			}

			if ( ! EVPX.motionAllowed() || section.getAttribute( 'data-evpx-animate' ) === '0' ) {
				return;
			}

			var list = section.querySelector( '.evpx-process__steps' );
			var steps = Array.prototype.slice.call( section.querySelectorAll( '[data-evpx-step]' ) );
			var now = section.querySelector( '.evpx-process__now' );
			var of = section.querySelector( '.evpx-process__of' );

			if ( ! list || ! steps.length ) {
				return;
			}

			var nodes = steps.map( function ( step ) {
				return step.querySelector( '.evpx-step__node' );
			} );
			var visible = true;
			var frame = 0;

			var middle = function ( node ) {
				var box = node.getBoundingClientRect();
				return box.top + box.height / 2;
			};

			var update = function () {
				frame = 0;

				var line = window.innerHeight * 0.55;
				var top = middle( nodes[ 0 ] );
				var bottom = middle( nodes[ nodes.length - 1 ] );
				var current = -1;

				// The rail ends at the last socket, not at the foot of the last paragraph.
				list.style.setProperty( '--evpx-rail-end', list.getBoundingClientRect().bottom - bottom + 'px' );
				section.style.setProperty( '--evpx-progress', clamp01( ( line - top ) / Math.max( 1, bottom - top ) ).toFixed( 4 ) );

				steps.forEach( function ( step, i ) {
					var reached = middle( nodes[ i ] ) <= line;

					step.classList.toggle( 'evpx-is-reached', reached );

					if ( reached ) {
						current = i;
					}
				} );

				steps.forEach( function ( step, i ) {
					step.classList.toggle( 'evpx-is-current', i === current );
				} );

				if ( now ) {
					now.textContent = pad2( Math.max( current, 0 ) + 1 );
				}
			};

			var queue = function () {
				if ( visible && ! frame ) {
					frame = window.requestAnimationFrame( update );
				}
			};

			if ( of ) {
				of.textContent = '/ ' + pad2( steps.length );
			}

			section.setAttribute( 'data-evpx-ready', '1' );
			update();

			window.addEventListener( 'scroll', queue, { passive: true } );
			window.addEventListener( 'resize', queue );

			if ( 'IntersectionObserver' in window ) {
				new window.IntersectionObserver( function ( entries ) {
					visible = entries[ entries.length - 1 ].isIntersecting;
					queue();
				} ).observe( section );
			}
		} );
	}

	function initRail( root ) {
		EVPX.each( '[data-evpx-rail]', root, function ( rail ) {
			if ( ! markBound( rail, 'rail' ) ) {
				return;
			}

			var section = rail.closest( '.evpx-projects' ) || rail.parentNode;
			var nav = section.querySelector( '[data-evpx-rail-nav]' );
			var prev = section.querySelector( '[data-evpx-rail-prev]' );
			var next = section.querySelector( '[data-evpx-rail-next]' );
			var bar = section.querySelector( '[data-evpx-rail-bar]' );
			var cards = rail.querySelectorAll( '.evpx-project' );
			var rtl = window.getComputedStyle( rail ).direction === 'rtl';
			var sign = rtl ? -1 : 1;

			var update = function () {
				var max = rail.scrollWidth - rail.clientWidth;
				var moves = max > 4;
				var at = Math.abs( rail.scrollLeft );

				if ( moves ) {
					section.setAttribute( 'data-evpx-ready', '1' );
				} else {
					section.removeAttribute( 'data-evpx-ready' );
				}

				if ( nav ) {
					nav.hidden = ! moves;
				}

				if ( prev ) {
					prev.disabled = at <= 2;
				}

				if ( next ) {
					next.disabled = at >= max - 2;
				}

				if ( bar && moves ) {
					var size = Math.min( 1, Math.max( 0.06, rail.clientWidth / rail.scrollWidth ) );
					var pos = clamp01( at / max );

					bar.style.width = ( size * 100 ).toFixed( 2 ) + '%';
					bar.style.transform = 'translateX(' + ( sign * pos * ( 1 / size - 1 ) * 100 ).toFixed( 2 ) + '%)';
				}
			};

			var move = function ( direction ) {
				var step = cards.length > 1 ? Math.abs( cards[ 1 ].offsetLeft - cards[ 0 ].offsetLeft ) : rail.clientWidth * 0.8;

				rail.scrollBy( { left: direction * sign * step, behavior: EVPX.motionAllowed() ? 'smooth' : 'auto' } );
			};

			if ( prev ) {
				prev.addEventListener( 'click', function () {
					move( -1 );
				} );
			}

			if ( next ) {
				next.addEventListener( 'click', function () {
					move( 1 );
				} );
			}

			// A mouse can drag it; touch and trackpad already scroll it, and a pen or finger must not be caught here.
			var drag = null;

			rail.addEventListener( 'pointerdown', function ( event ) {
				if ( event.pointerType === 'mouse' && event.button === 0 ) {
					drag = { x: event.clientX, left: rail.scrollLeft, moved: false, id: event.pointerId };
				}
			} );

			rail.addEventListener( 'pointermove', function ( event ) {
				if ( ! drag ) {
					return;
				}

				var dx = event.clientX - drag.x;

				if ( ! drag.moved && Math.abs( dx ) > 5 ) {
					drag.moved = true;
					rail.classList.add( 'evpx-is-dragging' );

					try {
						rail.setPointerCapture( drag.id );
					} catch ( e ) {
						// The pointer is already gone; the drag simply ends.
					}
				}

				if ( drag.moved ) {
					rail.scrollLeft = drag.left - dx;
				}
			} );

			var release = function () {
				drag = null;
				rail.classList.remove( 'evpx-is-dragging' );
			};

			rail.addEventListener( 'pointerup', release );
			rail.addEventListener( 'pointercancel', release );

			rail.addEventListener( 'scroll', update, { passive: true } );
			window.addEventListener( 'resize', update );
			update();
		} );
	}

	function initQuotes( root ) {
		EVPX.each( '[data-evpx-quotes]', root, function ( section ) {
			if ( ! markBound( section, 'quotes' ) ) {
				return;
			}

			var quotes = Array.prototype.slice.call( section.querySelectorAll( '[data-evpx-quote]' ) );
			var stage = section.querySelector( '[data-evpx-quotes-stage]' );
			var nav = section.querySelector( '[data-evpx-quotes-nav]' );
			var dotsBox = section.querySelector( '[data-evpx-quotes-dots]' );
			var prev = section.querySelector( '[data-evpx-quotes-prev]' );
			var next = section.querySelector( '[data-evpx-quotes-next]' );

			// One quotation has nothing to turn to: it stays as printed.
			if ( quotes.length < 2 || ! stage || ! nav || ! dotsBox ) {
				return;
			}

			var index = 0;
			var timer = 0;
			var paused = false;
			var visible = true;
			var auto = section.getAttribute( 'data-evpx-auto' ) === '1' && EVPX.motionAllowed();
			var dots = quotes.map( function ( quote, i ) {
				var dot = document.createElement( 'button' );

				dot.type = 'button';
				dot.className = 'evpx-quotes__dot';
				dot.setAttribute( 'aria-label', ( i + 1 ) + ' / ' + quotes.length );
				dot.addEventListener( 'click', function () {
					show( i, true );
				} );
				dotsBox.appendChild( dot );

				return dot;
			} );

			function show( to, byHand ) {
				index = ( to + quotes.length ) % quotes.length;

				quotes.forEach( function ( quote, i ) {
					quote.classList.toggle( 'evpx-is-current', i === index );
					dots[ i ].setAttribute( 'aria-current', i === index ? 'true' : 'false' );
				} );

				if ( byHand ) {
					// Someone is reading: a screen reader announces the change, and the rotation stops for good.
					stage.setAttribute( 'aria-live', 'polite' );
					auto = false;
				}
			}

			stage.setAttribute( 'aria-live', 'off' );
			section.setAttribute( 'data-evpx-ready', '1' );
			nav.hidden = false;
			show( 0, false );

			prev.addEventListener( 'click', function () {
				show( index - 1, true );
			} );

			next.addEventListener( 'click', function () {
				show( index + 1, true );
			} );

			nav.addEventListener( 'keydown', function ( event ) {
				if ( event.key === 'ArrowLeft' || event.key === 'ArrowRight' ) {
					show( index + ( event.key === 'ArrowRight' ? 1 : -1 ), true );
				}
			} );

			if ( auto ) {
				var stop = function () {
					paused = true;
				};
				var go = function () {
					paused = false;
				};

				section.addEventListener( 'mouseenter', stop );
				section.addEventListener( 'mouseleave', go );
				section.addEventListener( 'focusin', stop );
				section.addEventListener( 'focusout', go );

				if ( 'IntersectionObserver' in window ) {
					new window.IntersectionObserver( function ( entries ) {
						visible = entries[ entries.length - 1 ].isIntersecting;
					} ).observe( section );
				}

				timer = window.setInterval( function () {
					if ( ! auto ) {
						window.clearInterval( timer );
					} else if ( visible && ! paused && ! document.hidden ) {
						show( index + 1, false );
					}
				}, 7000 );
			}
		} );
	}

	function initContact( root ) {
		EVPX.each( '[data-evpx-contact]', root, function ( form ) {
			if ( ! markBound( form, 'contact' ) ) {
				return;
			}

			var button = form.querySelector( '.evpx-contact__submit' );

			form.addEventListener( 'submit', function () {
				form.classList.add( 'evpx-is-sending' );
				form.setAttribute( 'aria-busy', 'true' );

				if ( button ) {
					button.setAttribute( 'aria-disabled', 'true' );
				}
			} );

			// Back from the confirmation with the browser's button: the form is a form again.
			window.addEventListener( 'pageshow', function () {
				form.classList.remove( 'evpx-is-sending' );
				form.removeAttribute( 'aria-busy' );

				if ( button ) {
					button.removeAttribute( 'aria-disabled' );
				}
			} );
		} );
	}

	/* ------------------------------------------------------------------
	   Boot
	   ------------------------------------------------------------------ */
	function init( root ) {
		initFaq( root );
		initComparison( root );
		initMotionState( root );
		initArt( root );
		initExplorer( root );
		initSpotlight();
		initStage( root );
		initHeader( root );
		initStats( root );
		initServices( root );
		initProcess( root );
		initRail( root );
		initQuotes( root );
		initContact( root );

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
