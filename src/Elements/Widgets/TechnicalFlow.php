<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;
use EVPX\Support\Icons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Grid -> Site -> Charger -> Vehicle -> Battery technical diagram. */
final class TechnicalFlow extends Element {

	public function slug(): string {
		return 'flow';
	}

	public function title(): string {
		return __( 'EV Technical Flow', 'ev-charging-experience' );
	}

	public function controls(): array {
		$defaults = array(
			__( 'Grid', 'ev-charging-experience' ),
			__( 'Site Infrastructure', 'ev-charging-experience' ),
			__( 'Charger', 'ev-charging-experience' ),
			__( 'Vehicle', 'ev-charging-experience' ),
			__( 'Battery', 'ev-charging-experience' ),
		);

		$controls = array(
			array( 'key' => 'heading', 'label' => __( 'Heading', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'How Charging Reaches the Vehicle', 'ev-charging-experience' ) ),
		);

		foreach ( $defaults as $index => $label ) {
			$n          = $index + 1;
			$controls[] = array(
				'key'     => "step{$n}_label",
				'label'   => sprintf( /* translators: %d: step number */ __( 'Step %d label', 'ev-charging-experience' ), $n ),
				'type'    => 'text',
				'group'   => 'content',
				'default' => $label,
			);
		}

		$controls[] = array(
			'key'     => 'direction',
			'label'   => __( 'Direction', 'ev-charging-experience' ),
			'type'    => 'select',
			'group'   => 'layout',
			'default' => 'horizontal',
			'options' => array(
				'horizontal' => __( 'Horizontal', 'ev-charging-experience' ),
				'vertical'   => __( 'Vertical', 'ev-charging-experience' ),
			),
		);
		$controls[] = array( 'key' => 'compact', 'label' => __( 'Compact mode', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'layout', 'default' => false );
		$controls[] = array(
			'key'     => 'variant',
			'label'   => __( 'Look', 'ev-charging-experience' ),
			'type'    => 'select',
			'group'   => 'visual',
			'default' => 'dark',
			'options' => array(
				'dark'  => __( 'Dark (blueprint band)', 'ev-charging-experience' ),
				'light' => __( 'Light', 'ev-charging-experience' ),
			),
		);
		$controls[] = array( 'key' => 'icons', 'label' => __( 'Show icons (otherwise step numbers)', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'visual', 'default' => true );
		$controls[] = array( 'key' => 'animate', 'label' => __( 'Enable sequence animation', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'motion', 'default' => true );

		$controls[] = self::spacingControl();

		return $controls;
	}

	public function render( array $atts, string $content = '' ): string {
		$steps = array();
		for ( $n = 1; $n <= 5; $n++ ) {
			$steps[] = $atts[ "step{$n}_label" ];
		}

		// One icon per step, or none at all: a row where only some steps have one would read as a mistake.
		$symbols = array_map(
			static function ( $label ) {
				return Icons::guess( (string) $label );
			},
			$steps
		);

		if ( ! $atts['icons'] || in_array( '', $symbols, true ) ) {
			$symbols = array_fill( 0, count( $steps ), '' );
		}

		return $this->view(
			'technical-flow',
			array(
				'spacing'   => $atts['spacing'],
				'heading'   => $atts['heading'],
				'steps'     => $steps,
				'symbols'   => $symbols,
				'direction' => $atts['direction'],
				'variant'   => $atts['variant'],
				'compact'   => $atts['compact'],
				'animate'   => $atts['animate'],
			)
		);
	}
}
