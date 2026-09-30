<?php

namespace EVPX\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The built-in artwork: technical line drawings of the things the article is about (a charger, a car, the cable
 * between them), drawn as inline SVG so they need no upload, follow the design tokens, and can move.
 *
 * Each piece is a template in templates/art/. It is decoration: aria-hidden, no text a reader has to have. What
 * writing it carries is a label on a diagram, in the site's language, and it is safe to drop on a small screen.
 */
final class Art {

	/** @var int Pieces printed in this request; gives each one its own ids, so two on a page never collide. */
	private static $count = 0;

	/**
	 * The drawings a control can offer, key => label. 'none' is always first. (The hero's own drawing is not
	 * here: it is composed for the hero and is chosen by the Hero's own control.)
	 *
	 * @return array<string,string>
	 */
	public static function options(): array {
		return array(
			'none'         => __( 'None', 'ev-charging-experience' ),
			'charge-curve' => __( 'Charge curve: AC against DC', 'ev-charging-experience' ),
			'wallbox'      => __( 'AC wall box', 'ev-charging-experience' ),
			'dc-cabinet'   => __( 'DC fast charger', 'ev-charging-experience' ),
			'grid-path'    => __( 'Grid to charger', 'ev-charging-experience' ),
			'wave-ac'      => __( 'Alternating current (a wave)', 'ev-charging-experience' ),
			'wave-dc'      => __( 'Direct current (a level)', 'ev-charging-experience' ),
		);
	}

	/**
	 * A drawing on its own dark panel, the way a section or a card shows it; '' for none or a key that is not
	 * there (a stale saved value renders as nothing).
	 */
	public static function panel( string $key ): string {
		if ( 'none' === $key || ! isset( self::options()[ $key ] ) ) {
			return '';
		}

		$svg = self::render( $key );

		return '' === $svg ? '' : '<div class="evpx-artpanel" data-evpx-spot>' . $svg . '</div>';
	}

	/**
	 * The n-th drawing in the catalogue, wrapping round: what a card without a picture is given, so a row of
	 * them reads as a set and not as the same tile three times.
	 */
	public static function nth( int $index ): string {
		$keys = array_values( array_diff( array_keys( self::options() ), array( 'none' ) ) );

		return $keys[ $index % count( $keys ) ];
	}

	public static function has( string $key ): bool {
		return 1 === preg_match( '/^[a-z0-9-]+$/', $key ) && is_readable( EVPX_PATH . 'templates/art/' . $key . '.php' );
	}

	/**
	 * One piece of artwork as an <svg> string; '' for a key that is not there (a stale saved value renders as nothing).
	 *
	 * @param array<string,mixed> $vars Extra values for the template (labels).
	 */
	public static function render( string $key, array $vars = array() ): string {
		if ( ! self::has( $key ) ) {
			return '';
		}

		++self::$count;
		$uid = 'evpx-art-' . self::$count;

		extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract

		ob_start();
		include EVPX_PATH . 'templates/art/' . $key . '.php';

		return (string) ob_get_clean();
	}
}
