<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;
use EVPX\Support\Lines;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The results page: a search box and what it finds among the site's published pages and articles. */
final class Search extends Element {

	public function slug(): string {
		return 'search';
	}

	public function title(): string {
		return __( 'EV Search Results', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			self::spacingControl(),
			self::anchorControl(),
			array( 'key' => 'heading', 'label' => __( 'Heading', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'Search', 'ev-charging-experience' ) ),
			array( 'key' => 'placeholder', 'label' => __( 'Search box hint', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'What are you looking for?', 'ev-charging-experience' ) ),
			array( 'key' => 'suggestions', 'label' => __( 'Suggestions before a search, one per line as "label | address"', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => "DC fast charging | /services/\nRecent projects | /projects/\nTalk to us | /contact/" ),
			array( 'key' => 'per_page', 'label' => __( 'Results per page', 'ev-charging-experience' ), 'type' => 'number', 'group' => 'layout', 'default' => 8 ),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		// A search is a read: the address carries it, so a result can be linked to, and no nonce belongs on it.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$query = '';
		foreach ( array( 'q', 's' ) as $key ) {
			if ( isset( $_GET[ $key ] ) && is_scalar( $_GET[ $key ] ) ) {
				$query = trim( sanitize_text_field( wp_unslash( (string) $_GET[ $key ] ) ) );
				break;
			}
		}
		$type  = isset( $_GET['type'] ) && in_array( $_GET['type'], array( 'page', 'post' ), true ) ? (string) $_GET['type'] : '';
		$paged = isset( $_GET['pg'] ) ? max( 1, (int) $_GET['pg'] ) : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$found   = null;
		$results = array();

		if ( '' !== $query ) {
			$found = new \WP_Query(
				array(
					's'                   => $query,
					'post_type'           => '' === $type ? array( 'page', 'post' ) : $type,
					'post_status'         => 'publish',
					'has_password'        => false,
					'posts_per_page'      => max( 1, min( 24, (int) $atts['per_page'] ) ),
					'paged'               => $paged,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => false,
				)
			);

			foreach ( $found->posts as $post ) {
				$results[] = array(
					'title'   => get_the_title( $post ),
					'url'     => (string) get_permalink( $post ),
					'kind'    => 'page' === $post->post_type ? __( 'Page', 'ev-charging-experience' ) : __( 'Article', 'ev-charging-experience' ),
					'excerpt' => wp_trim_words( wp_strip_all_tags( has_excerpt( $post ) ? $post->post_excerpt : strip_shortcodes( $post->post_content ) ), 26 ),
				);
			}
		}

		$suggestions = array();
		foreach ( Lines::pairs( $atts['suggestions'], 6 ) as $pair ) {
			$suggestions[] = array( $pair[0], Lines::url( $pair[1] ) );
		}

		$base   = remove_query_arg( array( 'pg', 'type', 's' ) );
		$target = Lines::target( remove_query_arg( array( 'q', 's', 'pg', 'type' ), $base ) );

		return $this->view(
			'search',
			array(
				'spacing'     => $atts['spacing'],
				'anchor'      => $atts['anchor'],
				'heading'     => $atts['heading'],
				'placeholder' => $atts['placeholder'],
				'query'       => $query,
				'type'        => $type,
				'results'     => $results,
				'total'       => $found ? (int) $found->found_posts : 0,
				'searched'    => null !== $found,
				'pages'       => $found ? (int) $found->max_num_pages : 0,
				'paged'       => $paged,
				'suggestions' => $suggestions,
				'action'      => $target['action'],
				'hidden'      => $target['fields'],
				'base'        => $base,
			)
		);
	}
}
