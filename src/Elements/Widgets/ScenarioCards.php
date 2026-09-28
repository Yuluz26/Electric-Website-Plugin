<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Container for one or more ScenarioCard children. */
final class ScenarioCards extends Element {

	public function slug(): string {
		return 'scenarios';
	}

	public function title(): string {
		return __( 'EV Scenario Cards', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			array( 'key' => 'eyebrow', 'label' => __( 'Eyebrow', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'heading', 'label' => __( 'Heading', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'columns', 'label' => __( 'Columns (desktop)', 'ev-charging-experience' ), 'type' => 'select', 'group' => 'layout', 'default' => '3', 'options' => array(
				'2' => __( '2', 'ev-charging-experience' ),
				'3' => __( '3', 'ev-charging-experience' ),
			) ),
			array( 'key' => 'animate', 'label' => __( 'Enable scroll reveal', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'motion', 'default' => true ),
		);
	}

	public function allowedChildren(): array {
		return array( 'evpx/scenario-card' );
	}

	public function render( array $atts, string $content = '' ): string {
		return $this->view(
			'scenario-cards',
			array(
				'eyebrow' => $atts['eyebrow'],
				'heading' => $atts['heading'],
				'columns' => $atts['columns'],
				'animate' => $atts['animate'],
				'content' => $content,
			)
		);
	}
}
