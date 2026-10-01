<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;
use EVPX\Support\Art;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Section extends Element {

	public function slug(): string {
		return 'section';
	}

	public function title(): string {
		return __( 'EV Section', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			self::spacingControl(),
			self::anchorControl(),
			array( 'key' => 'eyebrow', 'label' => __( 'Eyebrow', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'heading', 'label' => __( 'Heading', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'body', 'label' => __( 'Body copy', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'figure', 'label' => __( 'Key figure (a number or range, optional)', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'figure_label', 'label' => __( 'Key figure caption', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'media', 'label' => __( 'Media', 'ev-charging-experience' ), 'type' => 'image', 'group' => 'media', 'default' => 0 ),
			array( 'key' => 'media_alt', 'label' => __( 'Media alt text override', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'media', 'default' => '' ),
			array( 'key' => 'artwork', 'label' => __( 'Drawing (used when there is no media)', 'ev-charging-experience' ), 'type' => 'select', 'group' => 'media', 'default' => 'none', 'options' => Art::options() ),
			array(
				'key'     => 'layout',
				'label'   => __( 'Layout', 'ev-charging-experience' ),
				'type'    => 'select',
				'group'   => 'layout',
				'default' => 'media-right',
				'options' => array(
					'media-right' => __( 'Text left, media right', 'ev-charging-experience' ),
					'media-left'  => __( 'Media left, text right', 'ev-charging-experience' ),
					'stacked'     => __( 'Media above text', 'ev-charging-experience' ),
					'text-only'   => __( 'Text only', 'ev-charging-experience' ),
				),
			),
			array(
				'key'     => 'surface',
				'label'   => __( 'Surface treatment', 'ev-charging-experience' ),
				'type'    => 'select',
				'group'   => 'visual',
				'default' => 'flat',
				'options' => array(
					'flat'      => __( 'Flat (editorial)', 'ev-charging-experience' ),
					'raised'    => __( 'Raised (neumorphic)', 'ev-charging-experience' ),
					'recessed'  => __( 'Recessed (neumorphic)', 'ev-charging-experience' ),
				),
			),
			array( 'key' => 'animate', 'label' => __( 'Enable scroll reveal', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'motion', 'default' => true ),
		);
	}

	public function sampleAtts(): array {
		return array(
			'eyebrow' => __( 'Eyebrow', 'ev-charging-experience' ),
			'heading' => __( 'A clear heading for this section', 'ev-charging-experience' ),
			'body'    => __( 'Say the point in a sentence or two. Replace this copy in the Content tab.', 'ev-charging-experience' ),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		$media_html = '';
		if ( 'text-only' !== $atts['layout'] ) {
			if ( ! empty( $atts['media'] ) ) {
				$img_attr = array( 'class' => 'evpx-section__image' );
				if ( '' !== $atts['media_alt'] ) {
					$img_attr['alt'] = $atts['media_alt'];
				}
				$media_html = $this->image( (int) $atts['media'], 'large', $img_attr );
			} else {
				// No picture chosen: the drawing, if one was.
				$media_html = Art::panel( (string) $atts['artwork'] );
			}
		}

		return $this->view(
			'section',
			array(
				'spacing'      => $atts['spacing'],
				'anchor'       => $atts['anchor'],
				'eyebrow'      => $atts['eyebrow'],
				'heading'      => $atts['heading'],
				'figure'       => $atts['figure'],
				'figure_label' => $atts['figure_label'],
				'body'         => $this->autop( $atts['body'] ),
				'media_html'   => $media_html,
				'layout'       => $atts['layout'],
				'surface'      => $atts['surface'],
				'animate'      => $atts['animate'],
			)
		);
	}
}
