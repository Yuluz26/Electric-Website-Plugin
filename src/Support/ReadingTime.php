<?php

namespace EVPX\Support;

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
		$text       = wp_strip_all_tags( $content );
		$word_count = str_word_count( $text );

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
}
