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
