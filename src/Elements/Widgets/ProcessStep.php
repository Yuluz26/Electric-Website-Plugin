<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;
use EVPX\Support\Icons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** One step of Process. */
final class ProcessStep extends Element {

	public function slug(): string {
		return 'process-step';
	}

	public function title(): string {
		return __( 'EV Process Step', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			array( 'key' => 'title', 'label' => __( 'Title', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'description', 'label' => __( 'Description', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'duration', 'label' => __( 'How long it takes', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'symbol', 'label' => __( 'Icon', 'ev-charging-experience' ), 'type' => 'select', 'group' => 'visual', 'default' => 'auto', 'options' => Icons::options() ),
		);
	}

	public function sampleRows(): array {
		return array(
			array( 'title' => __( 'Survey', 'ev-charging-experience' ), 'description' => __( 'We visit the site, read the supply and count how long vehicles stay.', 'ev-charging-experience' ), 'duration' => __( '1 to 2 weeks', 'ev-charging-experience' ) ),
			array( 'title' => __( 'Design', 'ev-charging-experience' ), 'description' => __( 'Charger mix, layout, load management and a costed plan you can decide on.', 'ev-charging-experience' ), 'duration' => __( '2 to 4 weeks', 'ev-charging-experience' ) ),
			array( 'title' => __( 'Build', 'ev-charging-experience' ), 'description' => __( 'Groundwork, switchgear and chargers, installed and tested.', 'ev-charging-experience' ), 'duration' => __( '4 to 12 weeks', 'ev-charging-experience' ) ),
			array( 'title' => __( 'Run', 'ev-charging-experience' ), 'description' => __( 'Monitoring, billing and support from the day the first car plugs in.', 'ev-charging-experience' ), 'duration' => __( 'Ongoing', 'ev-charging-experience' ) ),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		return $this->view(
			'process-step',
			array(
				'title'       => $atts['title'],
				'description' => $atts['description'],
				'duration'    => $atts['duration'],
				'symbol'      => Icons::resolve( (string) $atts['symbol'], $atts['title'] . ' ' . $atts['description'], 'circuitry' ),
			)
		);
	}
}
