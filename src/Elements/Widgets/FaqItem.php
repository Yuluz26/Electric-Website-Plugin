<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** One Q&A pair — only meaningful nested inside Faq. */
final class FaqItem extends Element {

	public function slug(): string {
		return 'faq-item';
	}

	public function title(): string {
		return __( 'EV FAQ Item', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			array( 'key' => 'question', 'label' => __( 'Question', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'answer', 'label' => __( 'Answer', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'default_open', 'label' => __( 'Open by default', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'content', 'default' => false ),
		);
	}

	public function sampleRows(): array {
		return array(
			array(
				'question' => __( 'Can I install both AC and DC charging on the same site?', 'ev-charging-experience' ),
				'answer'   => __( 'Yes, and for many mixed-use sites it is the right answer. AC covers long-dwell, everyday charging at a low cost, while a few DC ports serve anyone who needs a fast top-up.', 'ev-charging-experience' ),
			),
			array(
				'question' => __( 'Where do I edit these questions?', 'ev-charging-experience' ),
				'answer'   => __( 'In the Content tab, under the items list: add, reorder or delete rows. Replace this copy with your own.', 'ev-charging-experience' ),
			),
		);
	}

	protected function builderPreview( array $atts ): array {
		// A folded answer can't be read or edited in a canvas that has no script to unfold it.
		$atts['default_open'] = true;

		return $atts;
	}

	public function render( array $atts, string $content = '' ): string {
		return $this->view(
			'faq-item',
			array(
				'question'     => $atts['question'],
				'answer_html'  => $this->autop( $atts['answer'] ),
				'answer_plain' => wp_strip_all_tags( $atts['answer'] ),
				'default_open' => $atts['default_open'],
				'id'           => $this->uniqueId( 'evpx-faq' ),
			)
		);
	}
}
