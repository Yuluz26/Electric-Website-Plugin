<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Numbered decision framework. Container for DecisionFactor children. */
final class DecisionFactors extends Element {

	public function slug(): string {
		return 'decision-factors';
	}

	public function title(): string {
		return __( 'EV Decision Factors', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			self::spacingControl(),
			array( 'key' => 'eyebrow', 'label' => __( 'Eyebrow', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'heading', 'label' => __( 'Heading', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'intro', 'label' => __( 'Intro', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'animate', 'label' => __( 'Enable scroll reveal', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'motion', 'default' => true ),
		);
	}

	public function allowedChildren(): array {
		return array( 'evpx/decision-factor' );
	}

	public function sampleAtts(): array {
		return array(
			'eyebrow' => __( 'The decision framework', 'ev-charging-experience' ),
			'heading' => __( 'What actually determines the choice', 'ev-charging-experience' ),
			'intro'   => __( 'A short lead-in to the factors below.', 'ev-charging-experience' ),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		return $this->view(
			'decision-factors',
			array(
				'spacing' => $atts['spacing'],
				'eyebrow' => $atts['eyebrow'],
				'heading' => $atts['heading'],
				'intro'   => $this->autop( $atts['intro'] ),
				'animate' => $atts['animate'],
				'content' => $content,
			)
		);
	}
}
