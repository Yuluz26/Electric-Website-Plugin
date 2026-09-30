<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;
use EVPX\Support\Art;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The signature component: AC vs DC charging comparison. */
final class Comparison extends Element {

	public function slug(): string {
		return 'comparison';
	}

	public function title(): string {
		return __( 'EV AC/DC Comparison', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			self::spacingControl(),
			self::anchorControl(),
			array( 'key' => 'ac_title', 'label' => __( 'AC title', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'AC Charging', 'ev-charging-experience' ) ),
			array( 'key' => 'ac_description', 'label' => __( 'AC description', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'ac_power_range', 'label' => __( 'AC power range', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '7–22 kW' ),
			array( 'key' => 'ac_dwell_label', 'label' => __( 'AC dwell-time label', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'Best for 2+ hour stays', 'ev-charging-experience' ) ),
			array( 'key' => 'ac_best_for', 'label' => __( 'AC best-for', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'Workplaces, hotels, residential', 'ev-charging-experience' ) ),

			array( 'key' => 'dc_title', 'label' => __( 'DC title', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'DC Fast Charging', 'ev-charging-experience' ) ),
			array( 'key' => 'dc_description', 'label' => __( 'DC description', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'dc_power_range', 'label' => __( 'DC power range', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '50–350+ kW' ),
			array( 'key' => 'dc_dwell_label', 'label' => __( 'DC dwell-time label', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'Best for under 45 minutes', 'ev-charging-experience' ) ),
			array( 'key' => 'dc_best_for', 'label' => __( 'DC best-for', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'Highway corridors, fleet depots, retail', 'ev-charging-experience' ) ),

			array(
				'key'     => 'mode',
				'label'   => __( 'Interaction mode', 'ev-charging-experience' ),
				'type'    => 'select',
				'group'   => 'layout',
				'default' => 'toggle',
				'options' => array(
					'toggle'      => __( 'Toggle (tabs)', 'ev-charging-experience' ),
					'side-by-side' => __( 'Side-by-side', 'ev-charging-experience' ),
				),
			),
			array(
				'key'     => 'accent_treatment',
				'label'   => __( 'Accent treatment', 'ev-charging-experience' ),
				'type'    => 'select',
				'group'   => 'visual',
				'default' => 'split',
				'options' => array(
					'split'   => __( 'Split accent (AC blue / DC copper)', 'ev-charging-experience' ),
					'neutral' => __( 'Neutral', 'ev-charging-experience' ),
				),
			),
			array( 'key' => 'art', 'label' => __( 'Draw the current with each panel (a wave, a level)', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'visual', 'default' => true ),
			array( 'key' => 'animation_intensity', 'label' => __( 'Animation intensity', 'ev-charging-experience' ), 'type' => 'select', 'group' => 'motion', 'default' => 'standard', 'options' => array(
				'standard' => __( 'Standard', 'ev-charging-experience' ),
				'subtle'   => __( 'Subtle', 'ev-charging-experience' ),
				'off'      => __( 'Off', 'ev-charging-experience' ),
			) ),
			array( 'key' => 'mobile_mode', 'label' => __( 'Mobile layout', 'ev-charging-experience' ), 'type' => 'select', 'group' => 'responsive', 'default' => 'tabs', 'options' => array(
				'tabs'    => __( 'Tabs', 'ev-charging-experience' ),
				'stacked' => __( 'Stacked cards', 'ev-charging-experience' ),
			) ),
		);
	}

	public function sampleAtts(): array {
		return array(
			'ac_description' => __( 'Grid power passes to the vehicle, whose onboard charger converts it to DC. Slower, simpler and cheaper to install.', 'ev-charging-experience' ),
			'dc_description' => __( 'The charger converts to DC itself and feeds the battery directly. Much faster, with heavier equipment and a bigger grid connection.', 'ev-charging-experience' ),
		);
	}

	protected function builderPreview( array $atts ): array {
		// One tab at a time would hide the panel whose text is being edited.
		$atts['mode'] = 'side-by-side';

		return $atts;
	}

	public function render( array $atts, string $content = '' ): string {
		return $this->view(
			'comparison',
			array(
				'spacing' => $atts['spacing'],
				'anchor'  => $atts['anchor'],
				'ac' => array(
					'title'       => $atts['ac_title'],
					'description' => $this->autop( $atts['ac_description'] ),
					'power_range' => $atts['ac_power_range'],
					'dwell_label' => $atts['ac_dwell_label'],
					'best_for'    => $atts['ac_best_for'],
					'art'         => $atts['art'] ? Art::panel( 'wave-ac' ) : '',
				),
				'dc' => array(
					'title'       => $atts['dc_title'],
					'description' => $this->autop( $atts['dc_description'] ),
					'power_range' => $atts['dc_power_range'],
					'dwell_label' => $atts['dc_dwell_label'],
					'best_for'    => $atts['dc_best_for'],
					'art'         => $atts['art'] ? Art::panel( 'wave-dc' ) : '',
				),
				'rulers'              => $this->rulers( $atts['ac_power_range'], $atts['dc_power_range'] ),
				'mode'                => $atts['mode'],
				'accent_treatment'    => $atts['accent_treatment'],
				'animation_intensity' => $atts['animation_intensity'],
				'mobile_mode'         => $atts['mobile_mode'],
				'id'                  => $this->uniqueId( 'evpx-cmp' ),
			)
		);
	}

	/**
	 * One shared power scale for both panels, read from the two "power range" texts, so the gap between
	 * AC and DC is drawn to scale rather than described. Each panel gets its own range, plus the other
	 * panel's range as a ghost. Null when either text isn't a kW figure the scale can be drawn from
	 * ("7–22 kW", "22 kW", "350+ kW"): the panels then simply have no ruler.
	 *
	 * @return array<string, array<string, string>>|null Keyed ac|dc: from, to, other_from, other_to (percent) and max (label).
	 */
	private function rulers( string $ac, string $dc ): ?array {
		$ac_range = self::kilowatts( $ac );
		$dc_range = self::kilowatts( $dc );

		if ( null === $ac_range || null === $dc_range ) {
			return null;
		}

		$peak  = max( $ac_range[1], $dc_range[1] );
		$step  = pow( 10, floor( log10( $peak ) ) ); // 350 reads against 100s, 22 against 10s.
		$max   = ceil( $peak / $step * 2 ) / 2 * $step; // Round up to the nearest half step: 350 stays 350, 22 becomes 25.
		$pct   = static function ( float $kw ) use ( $max ): string {
			return number_format( min( 100, $kw / $max * 100 ), 2, '.', '' ) . '%';
		};
		$label = sprintf(
			/* translators: %s: the top of the power scale, a number of kilowatts. */
			__( '%s kW', 'ev-charging-experience' ),
			number_format_i18n( $max )
		);

		return array(
			'ac' => array(
				'from'       => $pct( $ac_range[0] ),
				'to'         => $pct( $ac_range[1] ),
				'other_from' => $pct( $dc_range[0] ),
				'other_to'   => $pct( $dc_range[1] ),
				'max'        => $label,
			),
			'dc' => array(
				'from'       => $pct( $dc_range[0] ),
				'to'         => $pct( $dc_range[1] ),
				'other_from' => $pct( $ac_range[0] ),
				'other_to'   => $pct( $ac_range[1] ),
				'max'        => $label,
			),
		);
	}

	/**
	 * The kilowatts a power-range text stands for: [from, to]. One figure is a point on the scale, not a
	 * range from zero. More than two numbers ("CCS2 50-350 kW", "230 V, 7-22 kW") is ambiguous, so it
	 * is not read at all rather than read wrongly.
	 *
	 * @return array{0: float, 1: float}|null
	 */
	private static function kilowatts( string $text ): ?array {
		if ( ! preg_match( '/\bkw\b/i', $text ) ) {
			return null;
		}

		$text = (string) preg_replace( '/(?<=\d),(?=\d{3}\b)/', '', $text ); // 1,000 kW.

		if ( ! preg_match_all( '/\d+(?:\.\d+)?/', $text, $found ) || count( $found[0] ) > 2 ) {
			return null;
		}

		$from = (float) $found[0][0];
		$to   = (float) ( $found[0][1] ?? $found[0][0] );

		return ( $to >= $from && $to > 0 ) ? array( $from, $to ) : null;
	}
}
