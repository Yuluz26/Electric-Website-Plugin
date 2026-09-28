<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;
use EVPX\Support\ReadingTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Hero extends Element {

	public function slug(): string {
		return 'hero';
	}

	public function title(): string {
		return __( 'EV Article Hero', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			array( 'key' => 'category', 'label' => __( 'Category label', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'EV Infrastructure', 'ev-charging-experience' ) ),
			array( 'key' => 'title', 'label' => __( 'Title', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'Choosing AC or DC Charging for Your Site', 'ev-charging-experience' ) ),
			array( 'key' => 'excerpt', 'label' => __( 'Dek / summary', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'author', 'label' => __( 'Author', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'date', 'label' => __( 'Published date', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'reading_time', 'label' => __( 'Reading time (blank = auto)', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'media', 'label' => __( 'Hero media', 'ev-charging-experience' ), 'type' => 'image', 'group' => 'media', 'default' => 0 ),
			array( 'key' => 'media_alt', 'label' => __( 'Media alt text override', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'media', 'default' => '' ),
			array( 'key' => 'cta_label', 'label' => __( 'CTA label', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'cta_url', 'label' => __( 'CTA URL', 'ev-charging-experience' ), 'type' => 'url', 'group' => 'content', 'default' => '' ),
			array(
				'key'     => 'visual_mode',
				'label'   => __( 'Visual mode', 'ev-charging-experience' ),
				'type'    => 'select',
				'group'   => 'visual',
				'default' => 'dark',
				'options' => array(
					'dark'  => __( 'Dark (cinematic)', 'ev-charging-experience' ),
					'light' => __( 'Light', 'ev-charging-experience' ),
					'auto'  => __( 'Follow system', 'ev-charging-experience' ),
				),
			),
			array( 'key' => 'animate', 'label' => __( 'Enable entrance animation', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'motion', 'default' => true ),
			array(
				'key'     => 'title_tag',
				'label'   => __( 'Title heading level (use h2 if your theme already prints the page title as h1)', 'ev-charging-experience' ),
				'type'    => 'select',
				'group'   => 'advanced',
				'default' => 'h1',
				'options' => array(
					'h1' => __( 'h1', 'ev-charging-experience' ),
					'h2' => __( 'h2', 'ev-charging-experience' ),
				),
			),
			array( 'key' => 'progress_bar', 'label' => __( 'Show reading progress bar', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'motion', 'default' => true ),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		$reading_time = $atts['reading_time'];

		if ( '' === $reading_time ) {
			$post         = get_post();
			$reading_time = ReadingTime::label( $post ? $post->post_content : $atts['excerpt'] );
		}

		$media_html = '';
		if ( ! empty( $atts['media'] ) ) {
			$img_attr = array( 'class' => 'evpx-hero__image' );
			if ( '' !== $atts['media_alt'] ) {
				$img_attr['alt'] = $atts['media_alt'];
			}
			$media_html = $this->image( (int) $atts['media'], 'full', $img_attr );
		}

		return $this->view(
			'hero',
			array(
				'category'     => $atts['category'],
				'title'        => $atts['title'],
				'title_tag'    => $atts['title_tag'],
				'excerpt'      => $this->autop( $atts['excerpt'] ),
				'author'       => $atts['author'],
				'date'         => $atts['date'],
				'reading_time' => $reading_time,
				'media_html'   => $media_html,
				'cta_label'    => $atts['cta_label'],
				'cta_url'      => $atts['cta_url'],
				'visual_mode'  => $atts['visual_mode'],
				'animate'      => $atts['animate'],
				'progress_bar' => $atts['progress_bar'],
			)
		);
	}
}
