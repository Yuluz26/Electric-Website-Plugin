<?php

namespace EVPX\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The cinematic scenes behind a page hero (EVPX\Widgets\Stage): a night forecourt, a road, a fast charger, a line
 * of pylons, a plug in close-up. Drawn as layered inline SVG (templates/art/scene-*.php) so they need no upload,
 * follow the design tokens, and can move: evpx.js slides each layer by its own depth as the pointer or the page
 * moves, which is what makes a flat drawing read as a place. A real photograph replaces one; the scene is what
 * a page has until it gets one.
 *
 * Like Art, decoration only: aria-hidden, no text a reader needs.
 */
final class Scene {

	/** @var int Scenes printed in this request; gives each its own ids. */
	private static $count = 0;

	/** @return array<string,string> key => label; 'none' first. */
	public static function options(): array {
		return array(
			'none'    => __( 'None', 'ev-charging-experience' ),
			'station' => __( 'Night forecourt with chargers', 'ev-charging-experience' ),
			'highway' => __( 'Highway at dusk', 'ev-charging-experience' ),
			'cabinet' => __( 'DC fast charger', 'ev-charging-experience' ),
			'grid'    => __( 'Pylons and the grid', 'ev-charging-experience' ),
			'plug'    => __( 'The plug, close up', 'ev-charging-experience' ),
		);
	}

	public static function has( string $key ): bool {
		return 'none' !== $key && 1 === preg_match( '/^[a-z0-9-]+$/', $key ) && is_readable( EVPX_PATH . 'templates/art/scene-' . $key . '.php' );
	}

	/** One scene as an <svg> string; '' for none or a key that is not there. */
	public static function render( string $key ): string {
		if ( ! self::has( $key ) ) {
			return '';
		}

		++self::$count;
		$uid = 'evpx-scene-' . self::$count;

		ob_start();
		include EVPX_PATH . 'templates/art/scene-' . $key . '.php';

		return (string) ob_get_clean();
	}

	/**
	 * A repeatable pseudo-random sequence, so a scene is the same on every load (and in every test) without
	 * touching the global generator.
	 *
	 * @return callable(): float 0..1
	 */
	public static function rng( int $seed ): callable {
		return static function () use ( &$seed ) {
			$seed = ( $seed * 1103515245 + 12345 ) & 0x7fffffff;

			return $seed / 0x7fffffff;
		};
	}

	/**
	 * A row of buildings on a baseline, some of their windows lit. Returns SVG markup built from numbers only.
	 *
	 * @param string $fill   Body colour.
	 * @param int    $lit    Out of 100: how many windows are lit.
	 */
	public static function skyline( int $seed, int $x0, int $x1, int $base, int $hmin, int $hmax, string $fill, int $lit = 12 ): string {
		$r   = self::rng( $seed );
		$out = '';
		$x   = $x0;

		while ( $x < $x1 ) {
			$w = 38 + (int) ( $r() * 70 );
			$h = $hmin + (int) ( $r() * ( $hmax - $hmin ) );
			$y = $base - $h;

			$out .= sprintf( '<rect x="%d" y="%d" width="%d" height="%d" fill="%s"/>', $x, $y, $w, $h, $fill );

			for ( $wy = $y + 12; $wy < $base - 8; $wy += 14 ) {
				for ( $wx = $x + 8; $wx < $x + $w - 8; $wx += 12 ) {
					if ( $r() * 100 < $lit ) {
						$warm = $r() > 0.35;
						$out .= sprintf( '<rect class="%s" x="%d" y="%d" width="4" height="6"/>', $warm ? 'evpx-scene__win' : 'evpx-scene__win evpx-scene__win--cool', $wx, $wy );
					}
				}
			}

			$x += $w + (int) ( $r() * 6 );
		}

		return $out;
	}

	/** Small stars in a box, numbers only. */
	public static function stars( int $seed, int $count, int $w, int $h ): string {
		$r   = self::rng( $seed );
		$out = '';

		for ( $i = 0; $i < $count; $i++ ) {
			$out .= sprintf( '<circle cx="%d" cy="%d" r="%.1f" opacity="%.2f"/>', (int) ( $r() * $w ), (int) ( $r() * $h ), 0.5 + $r() * 1.1, 0.25 + $r() * 0.6 );
		}

		return $out;
	}
}
