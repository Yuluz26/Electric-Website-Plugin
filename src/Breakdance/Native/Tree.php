<?php

namespace EVPX\Breakdance\Native;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * An article written as EV shortcodes, laid out as a Breakdance page: one Section per element, each
 * holding the plugin's own native element, the way docs/BREAKDANCE.md recommends placing them.
 *
 * Data in, data out, like Controls: it calls no Breakdance function, so the tree can be built (and tested)
 * wherever WordPress runs. Setup\ExamplePages writes it into a page.
 */
final class Tree {

	/**
	 * The top-level EV shortcodes of an article, and what each becomes as a native element.
	 *
	 * @param array<string, array<string, mixed>> $overrides Attributes to set on every shortcode of a tag,
	 *                                                       by tag: what a page needs that a post doesn't.
	 * @return array<int, array{shortcode: string, widget: \EVPX\Elements\Element, class: string, properties: array<string, mixed>}>
	 */
	public static function nodes( string $article, array $overrides = array() ): array {
		$by_tag = array();
		foreach ( NativeElements::WIDGETS as $name => $widget_class ) {
			$widget                            = new $widget_class();
			$by_tag[ $widget->shortcodeTag() ] = array( 'EVPX\\' . $name, $widget );
		}

		preg_match_all( '/' . get_shortcode_regex( array_keys( $by_tag ) ) . '/s', $article, $matches, PREG_SET_ORDER );

		$nodes = array();
		foreach ( $matches as $match ) {
			list( $class, $widget ) = $by_tag[ $match[2] ];

			$atts = array_merge( (array) shortcode_parse_atts( $match[3] ), $overrides[ $match[2] ] ?? array() );
			$rows = array();
			$item = $widget->childWidget();

			if ( $item && '' !== trim( (string) $match[5] ) ) {
				preg_match_all( '/' . get_shortcode_regex( array( $item->shortcodeTag() ) ) . '/s', (string) $match[5], $found, PREG_SET_ORDER );

				foreach ( $found as $row ) {
					$rows[] = (array) shortcode_parse_atts( $row[3] );
				}
			}

			$nodes[] = array(
				'shortcode'  => $match[0],
				'widget'     => $widget,
				'class'      => $class,
				'properties' => Controls::propertiesFromAtts( $widget, $atts, $rows ),
			);
		}

		return $nodes;
	}

	/**
	 * The article as a Breakdance page in the recommended set-up: every element in its own Section, full
	 * width and without padding, so the widgets run edge to edge and bring their own rhythm.
	 *
	 * @param array<string, array<string, mixed>> $overrides See nodes().
	 * @return array<string, mixed>
	 */
	public static function fromArticle( string $article, array $overrides = array() ): array {
		return self::build(
			array_map(
				static function ( array $node ): array {
					return array(
						'type'       => $node['class'],
						'properties' => $node['properties'],
					);
				},
				self::nodes( $article, $overrides )
			),
			self::fullWidthSection()
		);
	}

	/**
	 * A Breakdance tree with one Section per element.
	 *
	 * @param array<int, array{type: string, properties: array<string, mixed>|null}> $elements
	 * @param array<string, mixed>|null $section Settings for every Section; null keeps Breakdance's
	 *                                           defaults (a 1120px container with its own padding).
	 * @return array<string, mixed>
	 */
	public static function build( array $elements, ?array $section = null ): array {
		$next     = 2;
		$sections = array();

		foreach ( $elements as $element ) {
			$section_id = $next++;
			$element_id = $next++;

			$sections[] = array(
				'id'       => $section_id,
				'data'     => array(
					'type'       => 'EssentialElements\\Section',
					'properties' => $section,
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

	/**
	 * A Section that lets a widget run edge to edge and supply its own rhythm.
	 *
	 * @return array<string, mixed>
	 */
	public static function fullWidthSection(): array {
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
}
