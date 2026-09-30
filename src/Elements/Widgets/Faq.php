<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Container for one or more FaqItem children. */
final class Faq extends Element {

	public function slug(): string {
		return 'faq';
	}

	public function title(): string {
		return __( 'EV FAQ', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			self::spacingControl(),
			self::anchorControl(),
			array( 'key' => 'eyebrow', 'label' => __( 'Eyebrow', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'FAQ', 'ev-charging-experience' ) ),
			array( 'key' => 'heading', 'label' => __( 'Heading', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'Common Questions', 'ev-charging-experience' ) ),
			array( 'key' => 'schema_output', 'label' => __( 'Output FAQPage schema (disable if an SEO plugin already handles FAQ schema)', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'advanced', 'default' => true ),
			array( 'key' => 'animate', 'label' => __( 'Enable panel animation', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'motion', 'default' => true ),
		);
	}

	public function allowedChildren(): array {
		return array( 'evpx/faq-item' );
	}

	public function render( array $atts, string $content = '' ): string {
		$schema = '';

		if ( $atts['schema_output'] ) {
			$schema = $this->buildFaqSchema( $content );
		}

		return $this->view(
			'faq',
			array(
				'spacing' => $atts['spacing'],
				'anchor'  => $atts['anchor'],
				'eyebrow' => $atts['eyebrow'],
				'heading' => $atts['heading'],
				'content' => $content,
				'schema'  => $schema,
			)
		);
	}

	/**
	 * Extract question/answer text already rendered by FaqItem children
	 * (data attributes on each .evpx-faq__item) to build FAQPage JSON-LD
	 * without re-parsing shortcode/block source.
	 */
	private function buildFaqSchema( string $rendered_children ): string {
		if ( '' === trim( $rendered_children ) ) {
			return '';
		}

		$count = preg_match_all(
			'/data-evpx-question="([^"]*)"[^>]*data-evpx-answer="([^"]*)"/',
			$rendered_children,
			$matches
		);

		if ( ! $count ) {
			return '';
		}

		$items = array();
		foreach ( $matches[1] as $index => $question ) {
			$answer = $matches[2][ $index ];
			if ( '' === trim( $question ) || '' === trim( $answer ) ) {
				continue;
			}
			$items[] = array(
				'@type'          => 'Question',
				'name'           => html_entity_decode( $question, ENT_QUOTES ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => html_entity_decode( $answer, ENT_QUOTES ),
				),
			);
		}

		if ( empty( $items ) ) {
			return '';
		}

		$schema = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $items,
		);

		// JSON_HEX_TAG/AMP: an answer containing the literal text "</script>"
		// must not be able to break out of this tag.
		$json = wp_json_encode( $schema, JSON_HEX_TAG | JSON_HEX_AMP );

		return '<script type="application/ld+json">' . $json . '</script>';
	}
}
