<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Container for ProcessStep children: the way of working as a timeline that fills as you scroll. */
final class Process extends Element {

	public function slug(): string {
		return 'process';
	}

	public function title(): string {
		return __( 'EV Process', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			self::spacingControl(),
			self::anchorControl(),
			array( 'key' => 'eyebrow', 'label' => __( 'Eyebrow', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'heading', 'label' => __( 'Heading', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'intro', 'label' => __( 'Introduction', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'animate', 'label' => __( 'Fill the line as the page scrolls', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'motion', 'default' => true ),
		);
	}

	public function allowedChildren(): array {
		return array( 'evpx/process-step' );
	}

	public function sampleAtts(): array {
		return array(
			'eyebrow' => __( 'How it works', 'ev-charging-experience' ),
			'heading' => __( 'One team from the first call to the first charge', 'ev-charging-experience' ),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		return $this->view(
			'process',
			array(
				'spacing' => $atts['spacing'],
				'anchor'  => $atts['anchor'],
				'eyebrow' => $atts['eyebrow'],
				'heading' => $atts['heading'],
				'intro'   => $atts['intro'],
				'animate' => $atts['animate'],
				'content' => $content,
			)
		);
	}
}
