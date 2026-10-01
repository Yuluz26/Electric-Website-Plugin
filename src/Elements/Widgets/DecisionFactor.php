<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;
use EVPX\Support\Icons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** One factor — only meaningful nested inside DecisionFactors. */
final class DecisionFactor extends Element {

	public function slug(): string {
		return 'decision-factor';
	}

	public function title(): string {
		return __( 'EV Decision Factor', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			array( 'key' => 'title', 'label' => __( 'Factor', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'description', 'label' => __( 'Description', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'question', 'label' => __( 'Question to ask', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'symbol', 'label' => __( 'Icon', 'ev-charging-experience' ), 'type' => 'select', 'group' => 'visual', 'default' => 'auto', 'options' => Icons::options() ),
		);
	}

	public function sampleRows(): array {
		return array(
			array(
				'title'       => __( 'Dwell time', 'ev-charging-experience' ),
				'description' => __( 'How long a vehicle realistically stays on site. It caps how much energy a session can deliver.', 'ev-charging-experience' ),
				'question'    => __( 'How long does a typical vehicle stay, and does that change by time of day?', 'ev-charging-experience' ),
			),
			array(
				'title'       => __( 'Site capacity', 'ev-charging-experience' ),
				'description' => __( 'The electrical supply available at the site, and what it would take to increase it.', 'ev-charging-experience' ),
				'question'    => __( 'What spare capacity does the site connection have today?', 'ev-charging-experience' ),
			),
			array(
				'title'       => __( 'Future growth', 'ev-charging-experience' ),
				'description' => __( 'Reserving capacity and running conduit now is usually cheaper than reopening the ground later.', 'ev-charging-experience' ),
				'question'    => __( 'What would we need if the number of EV drivers on site doubled?', 'ev-charging-experience' ),
			),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		return $this->view(
			'decision-factor',
			array(
				'title'       => $atts['title'],
				'description' => $this->autop( $atts['description'] ),
				'question'    => $atts['question'],
				'symbol'      => Icons::resolve( (string) $atts['symbol'], $atts['title'] ),
			)
		);
	}
}
