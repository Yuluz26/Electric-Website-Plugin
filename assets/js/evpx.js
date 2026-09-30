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
	   Boot
	   ------------------------------------------------------------------ */
	function init( root ) {
		initFaq( root );
		initComparison( root );
		initMotionState( root );
		initArt( root );
		initExplorer( root );
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
