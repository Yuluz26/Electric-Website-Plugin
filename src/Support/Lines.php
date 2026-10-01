<?php

namespace EVPX\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lists typed into one text box: "Label | URL", one per line. What a header's links, a footer's columns and a
 * hero's figures are. A multi-line attribute reaches a widget with <br /> where the line breaks were (wpautop
 * has been over it), so those are read as line breaks too, and nothing typed here can carry markup through.
 */
final class Lines {

	/**
	 * @return array<int, array{0: string, 1: string}> [left, right] per non-empty line, at most $max.
	 */
	public static function pairs( string $text, int $max = 12 ): array {
		$text = (string) preg_replace( '/<br\s*\/?>/i', "\n", $text );
		$out  = array();

		$lines = preg_split( '/\R/', $text );

		foreach ( false === $lines ? array() : $lines as $line ) {
			$parts = array_map( static fn( $part ) => trim( wp_strip_all_tags( $part ) ), explode( '|', $line, 2 ) );

			if ( '' === $parts[0] ) {
				continue;
			}

			$out[] = array( $parts[0], $parts[1] ?? '' );
		}

		return array_slice( $out, 0, $max );
	}

	/**
	 * Where a typed link goes. A full URL or a #fragment is itself; "/about/" is the page with that slug if the
	 * site has one (so it works on plain permalinks too), else that path on this site.
	 */
	public static function url( string $target ): string {
		$target = trim( $target );

		if ( '' === $target || '#' === $target[0] || preg_match( '#^([a-z][a-z0-9+.-]*:|//)#i', $target ) ) {
			return esc_url( $target );
		}

		if ( 1 === preg_match( '#^/([a-z0-9-]+)/?$#i', $target, $m ) ) {
			$page = get_page_by_path( $m[1] );

			if ( $page ) {
				return esc_url( (string) get_permalink( $page ) );
			}
		}

		return esc_url( home_url( $target ) );
	}

	/**
	 * What a form that submits with GET needs from an address. The browser replaces the query string of the
	 * form's action with the form's own fields, so an address that has one (a site on plain permalinks:
	 * `?page_id=12`) would lose it, and the form would land on the home page. The query goes in as hidden fields instead.
	 *
	 * @return array{action: string, fields: array<string, string>}
	 */
	public static function target( string $url ): array {
		$fields = array();
		$query  = wp_parse_url( $url, PHP_URL_QUERY );

		if ( is_string( $query ) && '' !== $query ) {
			wp_parse_str( $query, $parsed );

			foreach ( $parsed as $name => $value ) {
				if ( is_scalar( $value ) ) {
					$fields[ (string) $name ] = (string) $value;
				}
			}
		}

		return array(
			'action' => (string) strtok( $url, '?#' ),
			'fields' => $fields,
		);
	}

		/**
	 * A figure split for a counter: "$1,250+" is a prefix, the number that counts up, and a suffix that stays.
	 *
	 * @return array{prefix: string, number: string, suffix: string}
	 */
	public static function figure( string $value ): array {
		preg_match( '/^([^0-9]*)([0-9][0-9.,]*)(.*)$/u', $value, $m );

		// "24/7", "2 x 150 kW": more than one number is a phrase, not a figure that can count up.
		if ( isset( $m[3] ) && 1 === preg_match( '/[0-9]/', $m[3] ) ) {
			return array( 'prefix' => '', 'number' => '', 'suffix' => '' );
		}

		return array(
			'prefix' => $m[1] ?? '',
			'number' => $m[2] ?? '',
			'suffix' => $m[3] ?? '',
		);
	}
}
