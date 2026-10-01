<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Container for Quote children: one large quotation at a time, and a way to step through them. */
final class Quotes extends Element {

	public function slug(): string {
		return 'quotes';
	}

	public function title(): string {
		return __( 'EV Quotes', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			self::spacingControl(),
			self::anchorControl(),
			array( 'key' => 'eyebrow', 'label' => __( 'Eyebrow', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'auto', 'label' => __( 'Advance by itself (paused while hovered or focused)', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'motion', 'default' => false ),
			array( 'key' => 'animate', 'label' => __( 'Fade between quotations', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'motion', 'default' => true ),
		);
	}

	public function allowedChildren(): array {
		return array( 'evpx/quote' );
	}

	public function sampleAtts(): array {
		return array( 'eyebrow' => __( 'In their words', 'ev-charging-experience' ) );
	}

	public function render( array $atts, string $content = '' ): string {
		return $this->view(
			'quotes',
			array(
				'spacing' => $atts['spacing'],
				'anchor'  => $atts['anchor'],
				'eyebrow' => $atts['eyebrow'],
				'auto'    => $atts['auto'],
				'animate' => $atts['animate'],
				'content' => $content,
			)
		);
	}
}
