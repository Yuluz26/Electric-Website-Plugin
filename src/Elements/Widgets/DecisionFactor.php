<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;

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
		);
	}

	public function render( array $atts, string $content = '' ): string {
		return $this->view(
			'decision-factor',
			array(
				'title'       => $atts['title'],
				'description' => $this->autop( $atts['description'] ),
				'question'    => $atts['question'],
			)
		);
	}
}
