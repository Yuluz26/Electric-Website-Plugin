<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Container for Project children: cards on a rail you drag, scroll, or step through. */
final class Projects extends Element {

	public function slug(): string {
		return 'projects';
	}

	public function title(): string {
		return __( 'EV Projects', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			self::spacingControl(),
			self::anchorControl(),
			array( 'key' => 'eyebrow', 'label' => __( 'Eyebrow', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'heading', 'label' => __( 'Heading', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'intro', 'label' => __( 'Introduction', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'animate', 'label' => __( 'Enable scroll reveal', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'motion', 'default' => true ),
		);
	}

	public function allowedChildren(): array {
		return array( 'evpx/project' );
	}

	public function sampleAtts(): array {
		return array(
			'eyebrow' => __( 'Selected work', 'ev-charging-experience' ),
			'heading' => __( 'Sites we have put on the map', 'ev-charging-experience' ),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		return $this->view(
			'projects',
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
