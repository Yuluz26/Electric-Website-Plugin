<?php

namespace EVPX\Support;

use EVPX\Assets\Loader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ReadingTime {

	private const WORDS_PER_MINUTE = 200;

	/**
	 * Estimate reading time from plain text or HTML. Always returns at
	 * least 1 minute so the label never reads "0 min read".
	 */
	public static function estimateMinutes( string $content ): int {
		$word_count = str_word_count( wp_strip_all_tags( $content ) );

		return max( 1, (int) ceil( $word_count / self::WORDS_PER_MINUTE ) );
	}

	public static function label( string $content ): string {
		$minutes = self::estimateMinutes( $content );

		return sprintf(
			/* translators: %d: number of minutes */
			_n( '%d min read', '%d min read', $minutes, 'ev-charging-experience' ),
			$minutes
		);
	}

	/**
	 * The label for what a visitor actually reads on this post.
	 *
	 * Rendering the content to count it can evaluate this label again (a page
	 * whose hero or Dynamic Data field shows its own reading time), so a post
	 * that is already being measured returns '' instead of recursing. The
	 * result is kept for the rest of the request, so several uses cost one render.
	 */
	public static function labelForPost( \WP_Post $post ): string {
		static $measuring = array();
		static $known     = array();

		if ( isset( $known[ $post->ID ] ) ) {
			return $known[ $post->ID ];
		}

		if ( isset( $measuring[ $post->ID ] ) ) {
			return '';
		}

		$measuring[ $post->ID ] = true;

		try {
			$visible = Loader::quietly(
				static function () use ( $post ) {
					return self::visibleContent( $post );
				}
			);
		} finally {
			unset( $measuring[ $post->ID ] );
		}

		$known[ $post->ID ] = self::label( $visible );

		return $known[ $post->ID ];
	}

	/**
	 * What a visitor is shown for this post.
	 *
	 * - A page designed in Breakdance has an empty post_content; its words live
	 *   in the element tree, which Breakdance's own Yoast and Rank Math
	 *   integrations read with get_tree_as_html().
	 * - The EV widgets keep their prose in shortcode/block attributes, so counting
	 *   the raw text would count attribute names and miss the copy. Render them —
	 *   but only when they are in the post, so other plugins' shortcodes are never
	 *   run just to count words.
	 */
	private static function visibleContent( \WP_Post $post ): string {
		if ( function_exists( '\Breakdance\Admin\get_mode' ) && function_exists( '\Breakdance\Data\get_tree_as_html' )
			&& 'breakdance' === \Breakdance\Admin\get_mode( $post->ID ) ) {
			return (string) \Breakdance\Data\get_tree_as_html( $post->ID );
		}

		$content = (string) $post->post_content;

		if ( false === strpos( $content, '[evpx_' ) && false === strpos( $content, 'wp:evpx/' ) ) {
			return $content;
		}

		return do_shortcode( do_blocks( $content ) );
	}
}
