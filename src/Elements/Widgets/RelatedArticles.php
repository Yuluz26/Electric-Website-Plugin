<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Editorial "keep reading" row, pulled from real published posts so it
 * never ships as hand-typed placeholder links.
 */
final class RelatedArticles extends Element {

	public function slug(): string {
		return 'related';
	}

	public function title(): string {
		return __( 'EV Related Articles', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			array( 'key' => 'eyebrow', 'label' => __( 'Eyebrow', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'Keep reading', 'ev-charging-experience' ) ),
			array( 'key' => 'heading', 'label' => __( 'Heading', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'Related articles', 'ev-charging-experience' ) ),
			array(
				'key'     => 'source',
				'label'   => __( 'Which articles', 'ev-charging-experience' ),
				'type'    => 'select',
				'group'   => 'content',
				'default' => 'category',
				'options' => array(
					'category' => __( 'Same category as this page (falls back to latest)', 'ev-charging-experience' ),
					'latest'   => __( 'Latest published', 'ev-charging-experience' ),
					'manual'   => __( 'Hand-picked by ID', 'ev-charging-experience' ),
				),
			),
			array( 'key' => 'post_ids', 'label' => __( 'Post IDs, comma-separated (hand-picked only)', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'post_type', 'label' => __( 'Post type', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'advanced', 'default' => 'post' ),
			array(
				'key'     => 'count',
				'label'   => __( 'How many', 'ev-charging-experience' ),
				'type'    => 'select',
				'group'   => 'layout',
				'default' => '3',
				'options' => array(
					'2' => __( '2', 'ev-charging-experience' ),
					'3' => __( '3', 'ev-charging-experience' ),
				),
			),
			array( 'key' => 'animate', 'label' => __( 'Enable scroll reveal', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'motion', 'default' => true ),
		);
	}

	protected function sanitizeControlValue( array $control, $value ) {
		if ( 'post_type' === $control['key'] ) {
			$post_type = sanitize_key( (string) $value );
			$object    = get_post_type_object( $post_type );

			return ( $object && $object->public ) ? $post_type : 'post';
		}

		return parent::sanitizeControlValue( $control, $value );
	}

	public function render( array $atts, string $content = '' ): string {
		$posts = $this->findPosts( $atts );

		if ( empty( $posts ) ) {
			// Live visitors see nothing rather than an empty section;
			// editors get a quiet note so the widget isn't mistaken for broken.
			return current_user_can( 'edit_posts' ) ? $this->view( 'related-empty' ) : '';
		}

		return $this->view(
			'related',
			array(
				'eyebrow' => $atts['eyebrow'],
				'heading' => $atts['heading'],
				'count'   => $atts['count'],
				'animate' => $atts['animate'],
				'items'   => array_map( array( $this, 'prepareItem' ), $posts ),
			)
		);
	}

	/** @return \WP_Post[] */
	private function findPosts( array $atts ): array {
		$limit   = (int) $atts['count'];
		$current = (int) get_the_ID();

		$args = array(
			'post_type'           => $atts['post_type'],
			'post_status'         => 'publish',
			'has_password'        => false,
			'posts_per_page'      => $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'post__not_in'        => $current ? array( $current ) : array(),
		);

		if ( 'manual' === $atts['source'] ) {
			$ids = array_values( array_filter( array_map( 'absint', explode( ',', $atts['post_ids'] ) ) ) );

			if ( empty( $ids ) ) {
				return array();
			}

			$args['post__in'] = $ids;
			$args['orderby']  = 'post__in';

			return get_posts( $args );
		}

		if ( 'category' === $atts['source'] && $current && 'post' === $atts['post_type'] ) {
			$categories = wp_get_post_categories( $current );

			if ( ! empty( $categories ) ) {
				$in_category = get_posts( array_merge( $args, array( 'category__in' => $categories ) ) );

				if ( ! empty( $in_category ) ) {
					return $in_category;
				}
			}
		}

		return get_posts( $args );
	}

	/** @return array<string, string> */
	private function prepareItem( \WP_Post $post ): array {
		$categories = 'post' === $post->post_type ? get_the_category( $post->ID ) : array();

		return array(
			'url'        => get_permalink( $post ),
			'title'      => get_the_title( $post ),
			'excerpt'    => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post ) ), 22 ),
			'date'       => get_the_date( '', $post ),
			'date_iso'   => get_the_date( 'c', $post ),
			'category'   => ! empty( $categories ) ? $categories[0]->name : '',
			'image_html' => (string) get_the_post_thumbnail(
				$post,
				'medium_large',
				array(
					'class'    => 'evpx-related__image',
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			),
		);
	}
}
