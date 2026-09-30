<?php

namespace EVPX\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The icon family every widget draws from: Phosphor Icons, Regular weight, inline SVG in currentColor.
 *
 * Inline, not a sprite or a font, so an icon works in a shortcode, a block, a Breakdance canvas and a feed alike,
 * takes the colour of the text around it, and cannot fail to load. The outlines are in icons-data.php.
 * Sizing belongs to the stylesheet (`--evpx-icon-size`); the width and height attributes are only the size an
 * unstyled copy falls back to.
 */
final class Icons {

	/** @var array<string,string>|null name => path data */
	private static $paths = null;

	/** @return array<string,string> */
	private static function paths(): array {
		if ( null === self::$paths ) {
			self::$paths = require __DIR__ . '/icons-data.php';
		}

		return self::$paths;
	}

	/**
	 * The icons an editor can pick for a card, a step or a factor, name => label. The rest of the family is
	 * interface (arrows, plus and minus, a tick) and is not offered.
	 *
	 * @return array<string,string>
	 */
	public static function choices(): array {
		return array(
			'lightning'          => __( 'Lightning', 'ev-charging-experience' ),
			'plug-charging'      => __( 'Plug', 'ev-charging-experience' ),
			'charging-station'   => __( 'Charging station', 'ev-charging-experience' ),
			'car-profile'        => __( 'Car', 'ev-charging-experience' ),
			'battery-charging'   => __( 'Battery charging', 'ev-charging-experience' ),
			'battery-medium'     => __( 'Battery', 'ev-charging-experience' ),
			'gauge'              => __( 'Gauge', 'ev-charging-experience' ),
			'clock'              => __( 'Clock', 'ev-charging-experience' ),
			'timer'              => __( 'Timer', 'ev-charging-experience' ),
			'arrows-left-right'  => __( 'Turnover', 'ev-charging-experience' ),
			'wave-sine'          => __( 'Alternating current', 'ev-charging-experience' ),
			'circuitry'          => __( 'Circuitry', 'ev-charging-experience' ),
			'cpu'                => __( 'Electronics', 'ev-charging-experience' ),
			'network'            => __( 'Network', 'ev-charging-experience' ),
			'flow-arrow'         => __( 'Flow', 'ev-charging-experience' ),
			'wrench'             => __( 'Installation', 'ev-charging-experience' ),
			'coins'              => __( 'Cost', 'ev-charging-experience' ),
			'chart-line-up'      => __( 'Growth', 'ev-charging-experience' ),
			'sliders-horizontal' => __( 'Settings', 'ev-charging-experience' ),
			'seal-check'         => __( 'Assurance', 'ev-charging-experience' ),
			'leaf'               => __( 'Sustainability', 'ev-charging-experience' ),
			'sun'                => __( 'Solar', 'ev-charging-experience' ),
			'moon'               => __( 'Overnight', 'ev-charging-experience' ),
			'buildings'          => __( 'Workplace', 'ev-charging-experience' ),
			'bed'                => __( 'Hotel', 'ev-charging-experience' ),
			'house-line'         => __( 'Home', 'ev-charging-experience' ),
			'storefront'         => __( 'Retail', 'ev-charging-experience' ),
			'truck'              => __( 'Fleet', 'ev-charging-experience' ),
			'road-horizon'       => __( 'Highway', 'ev-charging-experience' ),
			'garage'             => __( 'Garage', 'ev-charging-experience' ),
			'map-pin'            => __( 'Location', 'ev-charging-experience' ),
			'factory'            => __( 'Industry', 'ev-charging-experience' ),
		);
	}

	/**
	 * What a control that picks an icon offers: leave it to the plugin, none, or one of the choices.
	 *
	 * @return array<string,string>
	 */
	public static function options(): array {
		return array_merge(
			array(
				'auto' => __( 'Automatic', 'ev-charging-experience' ),
				'none' => __( 'None', 'ev-charging-experience' ),
			),
			self::choices()
		);
	}

	/**
	 * The icon that fits a piece of copy, by its words; the first row that matches wins, so the more specific
	 * ones come first. English only: for any other language nothing matches and the caller's fallback is used,
	 * which is why every control that calls this also lets the editor pick.
	 */
	public static function guess( string $text, string $fallback = '' ): string {
		static $rows = array(
			'/\b(infrastructure|switchgear|transformer|cabling|conduit|electrical)\b/i' => 'circuitry',
			'/\b(workplace|office|employee|campus|corporate)\b/i'                        => 'buildings',
			'/\b(hotel|guest|hospitality|resort)\b/i'                                    => 'bed',
			'/\b(residential|apartment|condo|home|housing|tenant)\b/i'                   => 'house-line',
			'/\b(fleet|depot|logistic|delivery|truck|van)\b/i'                           => 'truck',
			'/\b(retail|shop|shopper|store|mall|supermarket|restaurant)\b/i'             => 'storefront',
			'/\b(highway|corridor|motorway|trip|road|travel)\b/i'                        => 'road-horizon',
			'/\b(parking|garage|car park)\b/i'                                           => 'garage',
			'/\b(dwell|duration|hours?|minutes?|wait|stay)\b/i'                          => 'clock',
			'/\b(turnover|throughput|sessions?|volume)\b/i'                              => 'arrows-left-right',
			'/\b(capacity|load|demand|supply)\b/i'                                       => 'gauge',
			'/\b(energy|kwh|range|battery|storage|state of charge)\b/i'                  => 'battery-charging',
			'/\b(install|installation|complexity|civil|construction|maintenance)\b/i'    => 'wrench',
			'/\b(operating|operator|model|cost|price|pricing|billing|tariff|revenue|pay)\b/i' => 'coins',
			'/\b(future|growth|scal\w*|expan\w*|roadmap)\b/i'                            => 'chart-line-up',
			'/\b(charger|charging station|wallbox|pedestal|dispenser|station)\b/i'       => 'charging-station',
			'/\b(vehicle|car|driver|ev)\b/i'                                             => 'car-profile',
			'/\b(grid|utility|mains|power)\b/i'                                          => 'lightning',
		);

		foreach ( $rows as $pattern => $icon ) {
			if ( 1 === preg_match( $pattern, $text ) ) {
				return $icon;
			}
		}

		return $fallback;
	}

	/**
	 * The icon name a control's value comes to: 'none' is nothing, 'auto' is a guess from the copy (or the
	 * fallback), a name in the family is itself, and anything else (a stale value) is nothing.
	 */
	public static function resolve( string $symbol, string $text, string $fallback = '' ): string {
		if ( 'auto' === $symbol || '' === $symbol ) {
			return self::guess( $text, $fallback );
		}

		return self::has( $symbol ) ? $symbol : '';
	}

	/**
	 * The raw outline (a `d` attribute for a 256-unit box), for artwork that draws an icon inside its own SVG. '' if unknown.
	 */
	public static function path( string $name ): string {
		return self::paths()[ $name ] ?? '';
	}

	public static function has( string $name ): bool {
		return isset( self::paths()[ $name ] );
	}

	/** @return string[] */
	public static function names(): array {
		return array_keys( self::paths() );
	}

	/**
	 * The icon as an <svg> string; '' for a name that is not in the family, so a stale saved value renders as
	 * nothing rather than as a broken box. Decorative by design (aria-hidden): an icon here always sits beside
	 * text that says the same thing.
	 */
	public static function svg( string $name, string $extra_class = '' ): string {
		$paths = self::paths();

		if ( ! isset( $paths[ $name ] ) ) {
			return '';
		}

		$class = 'evpx-icon evpx-icon--' . sanitize_html_class( $name );

		if ( '' !== $extra_class ) {
			$class .= ' ' . implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $extra_class ) ) );
		}

		return sprintf(
			'<svg class="%s" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false"><path d="%s"/></svg>',
			esc_attr( $class ),
			esc_attr( $paths[ $name ] )
		);
	}
}
