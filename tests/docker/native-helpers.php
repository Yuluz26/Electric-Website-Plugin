<?php
/**
 * Test helper: turns content/demo-article.txt into the same article expressed as native Breakdance
 * elements, so a shortcode page and a native page can be compared and both opened in the builder.
 * Required by breakdance-page.php, breakdance-real-check.php and media-pages.php (needs WordPress
 * and the plugin).
 *
 * The conversion itself is the plugin's (EVPX\Breakdance\Native\Tree, the same code that makes the example
 * page on activation), so what these suites drive is what ships. Here: the Breakdance Shortcode-element
 * variant of a tree, a node turned back into a shortcode, and a media object as the builder saves it.
 */

if ( ! function_exists( 'evpx_test_native_nodes' ) ) {

	/** @return array<int, array{shortcode: string, widget: object, class: string, properties: array<string, mixed>}> */
	function evpx_test_native_nodes( string $article ): array {
		return \EVPX\Breakdance\Native\Tree::nodes( $article );
	}

	/**
	 * The shortcode a node's properties stand for. Toggles become true/false, and a media value (an
	 * array, as Breakdance saves it) collapses to its attachment id, which is what the shortcode takes.
	 */
	function evpx_test_shortcode( array $node ): string {
		$build = static function ( string $tag, array $atts, string $enclosed = '' ): string {
			$out = '[' . $tag;
			foreach ( $atts as $key => $value ) {
				if ( is_array( $value ) ) {
					$value = (int) ( $value['id'] ?? 0 );
				} elseif ( is_bool( $value ) ) {
					$value = $value ? 'true' : 'false';
				}
				$out .= ' ' . $key . '="' . $value . '"';
			}
			$out .= ']';

			return '' === $enclosed ? $out : $out . "\n" . $enclosed . "\n[/" . $tag . ']';
		};

		$content = $node['properties']['content'] ?? array();
		$rows    = $content['items']['rows'] ?? array();
		unset( $content['items'] );

		$atts = array();
		foreach ( $content as $group ) {
			$atts = array_merge( $atts, $group );
		}

		$enclosed = '';
		$child    = $node['widget']->childWidget();
		if ( $child && $rows ) {
			$enclosed = implode( "\n", array_map( static fn( $row ) => $build( $child->shortcodeTag(), $row ), $rows ) );
		}

		return $build( $node['widget']->shortcodeTag(), $atts, $enclosed );
	}

	/**
	 * An attachment as Breakdance's media control saves it (shape captured from the real builder
	 * choosing an image in its media library): the plugin reads the id; the rest is what Breakdance's own
	 * Image element reads.
	 */
	function evpx_test_wpmedia( int $id ): array {
		$meta  = (array) wp_get_attachment_metadata( $id );
		$sizes = array();
		foreach ( (array) ( $meta['sizes'] ?? array() ) as $name => $size ) {
			$sizes[ $name ] = array(
				'url'    => wp_get_attachment_image_url( $id, $name ),
				'width'  => $size['width'],
				'height' => $size['height'],
			);
		}

		return array(
			'id'       => $id,
			'filename' => basename( (string) get_attached_file( $id ) ),
			'url'      => wp_get_attachment_url( $id ),
			'alt'      => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
			'caption'  => '',
			'mime'     => (string) get_post_mime_type( $id ),
			'type'     => 'image',
			'sizes'    => $sizes,
			'width'    => $meta['width'] ?? 0,
			'height'   => $meta['height'] ?? 0,
		);
	}

	/** A Section that lets a widget run edge to edge and supply its own rhythm (docs/BREAKDANCE.md). */
	function evpx_test_full_width_section(): array {
		return \EVPX\Breakdance\Native\Tree::fullWidthSection();
	}

	/**
	 * A Breakdance tree: one Section per element, as a builder user would place them. Each holds the
	 * native element ($native) or Breakdance's own Shortcode element with the article's shortcode.
	 *
	 * @param array<string, mixed>|null $section_properties Settings for every Section; null keeps Breakdance's
	 *                                                       defaults (a 1120px container with its own padding).
	 */
	function evpx_test_tree( array $nodes, bool $native, ?array $section_properties = null ): array {
		return \EVPX\Breakdance\Native\Tree::build(
			array_map(
				static function ( array $node ) use ( $native ): array {
					return $native
						? array(
							'type'       => $node['class'],
							'properties' => $node['properties'],
						)
						: array(
							'type'       => 'EssentialElements\\Shortcode',
							'properties' => array( 'content' => array( 'shortcode' => array( 'full_shortcode' => $node['shortcode'] ) ) ),
						);
				},
				$nodes
			),
			$section_properties
		);
	}
}
