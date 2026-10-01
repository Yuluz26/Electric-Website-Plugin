<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;
use EVPX\Support\Icons;
use EVPX\Support\Lines;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** One figure, nested inside Stats. */
final class Stat extends Element {

	public function slug(): string {
		return 'stat';
	}

	public function title(): string {
		return __( 'EV Stat', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			array( 'key' => 'value', 'label' => __( 'Figure (a number counts up; keep units as typed: 150 kW, 99.5%, 1,250+)', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'label', 'label' => __( 'Label', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'note', 'label' => __( 'Note', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'fill', 'label' => __( 'Meter: how full the ring is, 0 to 100 (0 = no ring)', 'ev-charging-experience' ), 'type' => 'number', 'group' => 'visual', 'default' => 0 ),
			array( 'key' => 'symbol', 'label' => __( 'Icon', 'ev-charging-experience' ), 'type' => 'select', 'group' => 'visual', 'default' => 'auto', 'options' => Icons::options() ),
		);
	}

	public function sampleRows(): array {
		return array(
			array( 'value' => '150 kW', 'label' => __( 'Per bay', 'ev-charging-experience' ), 'note' => __( 'DC fast, CCS2', 'ev-charging-experience' ), 'fill' => 60 ),
			array( 'value' => '99.5%', 'label' => __( 'Uptime target', 'ev-charging-experience' ), 'note' => __( 'Monitored around the clock', 'ev-charging-experience' ), 'fill' => 99 ),
			array( 'value' => '24/7', 'label' => __( 'Remote monitoring', 'ev-charging-experience' ), 'note' => __( 'With a person to call', 'ev-charging-experience' ), 'fill' => 0 ),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		return $this->view(
			'stat',
			array_merge(
				array(
					'label'  => $atts['label'],
					'note'   => $atts['note'],
					'value'  => $atts['value'],
					'fill'   => max( 0, min( 100, (float) $atts['fill'] ) ),
					'symbol' => Icons::resolve( (string) $atts['symbol'], $atts['label'], '' ),
				),
				Lines::figure( $atts['value'] )
			)
		);
	}
}
