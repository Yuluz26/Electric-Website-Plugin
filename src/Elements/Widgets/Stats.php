<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Container for Stat children: figures that count up, each with a meter that fills. */
final class Stats extends Element {

	public function slug(): string {
		return 'stats';
	}

	public function title(): string {
		return __( 'EV Stats', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			self::spacingControl(),
			self::anchorControl(),
			array( 'key' => 'eyebrow', 'label' => __( 'Eyebrow', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'heading', 'label' => __( 'Heading', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'intro', 'label' => __( 'Introduction', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array(
				'key'     => 'variant',
				'label'   => __( 'Look', 'ev-charging-experience' ),
				'type'    => 'select',
				'group'   => 'visual',
				'default' => 'dark',
				'options' => array(
					'dark'  => __( 'Dark (blueprint band)', 'ev-charging-experience' ),
					'light' => __( 'Light', 'ev-charging-experience' ),
				),
			),
			array( 'key' => 'animate', 'label' => __( 'Count up and fill when seen', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'motion', 'default' => true ),
		);
	}

	public function allowedChildren(): array {
		return array( 'evpx/stat' );
	}

	public function sampleAtts(): array {
		return array(
			'eyebrow' => __( 'In numbers', 'ev-charging-experience' ),
			'heading' => __( 'What we run', 'ev-charging-experience' ),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		return $this->view(
			'stats',
			array(
				'spacing' => $atts['spacing'],
				'anchor'  => $atts['anchor'],
				'eyebrow' => $atts['eyebrow'],
				'heading' => $atts['heading'],
				'intro'   => $atts['intro'],
				'variant' => $atts['variant'],
				'animate' => $atts['animate'],
				'content' => $content,
			)
		);
	}
}
