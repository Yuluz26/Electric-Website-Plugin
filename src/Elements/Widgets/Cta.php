<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Cta extends Element {

	public function slug(): string {
		return 'cta';
	}

	public function title(): string {
		return __( 'EV CTA', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			self::spacingControl(),
			array( 'key' => 'eyebrow', 'label' => __( 'Eyebrow', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'title', 'label' => __( 'Title', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'Plan the charging system around how your site actually works.', 'ev-charging-experience' ) ),
			array( 'key' => 'body', 'label' => __( 'Body', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'button_label', 'label' => __( 'Button label', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'Start the conversation', 'ev-charging-experience' ) ),
			array( 'key' => 'button_url', 'label' => __( 'Button URL', 'ev-charging-experience' ), 'type' => 'url', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'media', 'label' => __( 'Background media', 'ev-charging-experience' ), 'type' => 'image', 'group' => 'media', 'default' => 0 ),
			array(
				'key'     => 'variant',
				'label'   => __( 'Visual variant', 'ev-charging-experience' ),
				'type'    => 'select',
				'group'   => 'visual',
				'default' => 'accent',
				'options' => array(
					'accent' => __( 'Accent (copper)', 'ev-charging-experience' ),
					'dark'   => __( 'Dark', 'ev-charging-experience' ),
					'media'  => __( 'Media background', 'ev-charging-experience' ),
				),
			),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		$media_html = '';
		if ( ! empty( $atts['media'] ) && 'media' === $atts['variant'] ) {
			$media_html = $this->image( (int) $atts['media'], 'full', array( 'class' => 'evpx-cta__image' ) );
		}

		return $this->view(
			'cta',
			array(
				'spacing' => $atts['spacing'],
				'eyebrow'      => $atts['eyebrow'],
				'title'        => $atts['title'],
				'body'         => $this->autop( $atts['body'] ),
				'button_label' => $atts['button_label'],
				'button_url'   => $atts['button_url'],
				'media_html'   => $media_html,
				'variant'      => $atts['variant'],
			)
		);
	}
}
