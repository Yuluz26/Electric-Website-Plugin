<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;

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

	public function render( array $atts, string $content = '' ): string {
		return $this->view(
			'comparison',
			array(
				'ac' => array(
					'title'       => $atts['ac_title'],
					'description' => $this->autop( $atts['ac_description'] ),
					'power_range' => $atts['ac_power_range'],
					'dwell_label' => $atts['ac_dwell_label'],
					'best_for'    => $atts['ac_best_for'],
				),
				'dc' => array(
					'title'       => $atts['dc_title'],
					'description' => $this->autop( $atts['dc_description'] ),
					'power_range' => $atts['dc_power_range'],
					'dwell_label' => $atts['dc_dwell_label'],
					'best_for'    => $atts['dc_best_for'],
				),
				'mode'                => $atts['mode'],
				'accent_treatment'    => $atts['accent_treatment'],
				'animation_intensity' => $atts['animation_intensity'],
				'mobile_mode'         => $atts['mobile_mode'],
			)
		);
	}
}
