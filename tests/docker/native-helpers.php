<?php
/**
 * Test helper: turns docs/demo-article.txt into the same article expressed as native Breakdance
 * elements, so a shortcode page and a native page can be compared and both opened in the builder.
 * Required by breakdance-page.php, breakdance-real-check.php and media-pages.php (needs WordPress
 * and the plugin).
 *
 * For every top-level EV shortcode it returns the shortcode text, the widget behind it, the native
 * element class that stands for it and the properties Breakdance would save for it — the inverse of
 * EVPX\Breakdance\Native\Controls::attsFromProperties(). evpx_test_shortcode() goes the other way,
 * so a node whose properties were edited (an image added, say) can be turned back into a shortcode.
 */

if ( ! function_exists( 'evpx_test_native_nodes' ) ) {

	/** @return array<int, array{shortcode: string, widget: object, class: string, properties: array<string, mixed>}> */
	function evpx_test_native_nodes( string $article ): array {
		$by_tag = array();
		foreach ( array( 'Hero', 'Section', 'Comparison', 'ScenarioCards', 'Flow', 'DecisionFactors', 'Faq', 'Related', 'Cta' ) as $name ) {
			$class  = 'EVPX\\' . $name;
			$method = new ReflectionMethod( $class, 'widget' );
			$method->setAccessible( true );
			$widget                        = $method->invoke( null );
			$by_tag[ $widget->shortcodeTag() ] = array( $class, $widget );
		}

		preg_match_all( '/' . get_shortcode_regex( array_keys( $by_tag ) ) . '/s', $article, $matches, PREG_SET_ORDER );

		$nodes = array();
		foreach ( $matches as $match ) {
			list( $class, $widget ) = $by_tag[ $match[2] ];

			$nodes[] = array(
				'shortcode'  => $match[0],
				'widget'     => $widget,
				'class'      => $class,
				'properties' => evpx_test_properties( $widget, (array) shortcode_parse_atts( $match[3] ), (string) $match[5] ),
			);
		}

		return $nodes;
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

	/** @return array<string, mixed> */
	function evpx_test_properties( $widget, array $atts, string $enclosed ): array {
		$content = evpx_test_group_values( $widget, $atts );

		$child = $widget->childWidget();
		if ( $child && '' !== trim( $enclosed ) ) {
			preg_match_all( '/' . get_shortcode_regex( array( $child->shortcodeTag() ) ) . '/s', $enclosed, $rows, PREG_SET_ORDER );

			$out = array();
			foreach ( $rows as $row ) {
				$flat = array();
				foreach ( evpx_test_group_values( $child, (array) shortcode_parse_atts( $row[3] ) ) as $group ) {
					$flat = array_merge( $flat, $group );
				}
				$out[] = $flat;
			}
			$content['items']['rows'] = $out;
		}

		return array( 'content' => $content );
	}

	/** @return array<string, array<string, mixed>> */
	function evpx_test_group_values( $widget, array $atts ): array {
		$groups = array();

		foreach ( $widget->controls() as $control ) {
			if ( ! array_key_exists( $control['key'], $atts ) || 'image' === $control['type'] ) {
				continue;
			}

			$value = $atts[ $control['key'] ];
			if ( 'toggle' === $control['type'] ) {
				$value = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
			}

			$groups[ $control['group'] ][ $control['key'] ] = $value;
		}

		return $groups;
	}

	/** A Section that lets a widget run edge to edge and supply its own rhythm (docs/BREAKDANCE.md). */
	function evpx_test_full_width_section(): array {
		$zero = array(
			'number' => 0,
			'unit'   => 'px',
			'style'  => '0px',
		);

		return array(
			'design' => array(
				'size'    => array( 'width' => 'full' ),
				'spacing' => array(
					'padding' => array(
						'breakpoint_base' => array(
							'top'    => $zero,
							'right'  => $zero,
							'bottom' => $zero,
							'left'   => $zero,
						),
					),
				),
			),
		);
	}

	/**
	 * A Breakdance tree: one Section per element, as a builder user would place them.
	 *
	 * @param array<string, mixed>|null $section_properties Settings for every Section; null keeps Breakdance's
	 *                                                       defaults (a 1120px container with its own padding).
	 */
	function evpx_test_tree( array $nodes, bool $native, ?array $section_properties = null ): array {
		$next     = 2;
		$sections = array();

		foreach ( $nodes as $node ) {
			$section_id = $next++;
			$element_id = $next++;

			$element = $native
				? array(
					'type'       => $node['class'],
					'properties' => $node['properties'],
				)
				: array(
					'type'       => 'EssentialElements\\Shortcode',
					'properties' => array( 'content' => array( 'shortcode' => array( 'full_shortcode' => $node['shortcode'] ) ) ),
				);

			$sections[] = array(
				'id'       => $section_id,
				'data'     => array(
					'type'       => 'EssentialElements\\Section',
					'properties' => $section_properties,
				),
				'children' => array(
					array(
						'id'       => $element_id,
						'data'     => $element,
						'children' => array(),
					),
				),
			);
		}

		// The builder validates what it opens: an "exported" tree carries _nextNodeId and status
		// beside the root, and nodes without settings have null (not empty-array) properties.
		return array(
			'root'        => array(
				'id'       => 1,
				'data'     => array(
					'type'       => 'root',
					'properties' => null,
				),
				'children' => $sections,
			),
			'_nextNodeId' => $next,
			'status'      => 'exported',
		);
	}
}
