<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Container for Service children: a strip of panels, one open at a time, on a narrow box an accordion. */
final class Services extends Element {

	public function slug(): string {
		return 'services';
	}

	public function title(): string {
		return __( 'EV Services', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			self::spacingControl(),
			self::anchorControl(),
			array( 'key' => 'eyebrow', 'label' => __( 'Eyebrow', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'heading', 'label' => __( 'Heading', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'intro', 'label' => __( 'Introduction', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'animate', 'label' => __( 'Enable panel animation', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'motion', 'default' => true ),
		);
	}

	public function allowedChildren(): array {
		return array( 'evpx/service' );
	}

	public function sampleAtts(): array {
		return array(
			'eyebrow' => __( 'What we do', 'ev-charging-experience' ),
			'heading' => __( 'From the grid connection to the last bay', 'ev-charging-experience' ),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		return $this->view(
			'services',
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
