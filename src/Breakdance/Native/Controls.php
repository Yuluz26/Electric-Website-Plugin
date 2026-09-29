<?php

namespace EVPX\Breakdance\Native;

use EVPX\Elements\Element as Widget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Translates a widget's control schema into what Breakdance's builder expects, and Breakdance's
 * saved properties back into widget attributes. Data in, data out: it calls no Breakdance
 * function, so it behaves the same whether or not Breakdance is loaded.
 *
 * Property paths follow Breakdance's own convention, content.<section>.<control>. Every control
 * group (content, media, layout, visual, motion, responsive, advanced) becomes a section of the
 * Content tab, so the editor is organised the way the PRD asks. A container widget's items are
 * the rows of one repeater, content.items.rows.
 */
final class Controls {

	public const ITEMS_SECTION = 'items';
	public const ITEMS_CONTROL = 'rows';

	private const GROUP_ORDER = array( 'content', 'media', 'layout', 'visual', 'motion', 'responsive', 'advanced' );

	/**
	 * Sections for the builder's Content tab.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function sections( Widget $widget ): array {
		$sections = array();
		$item     = $widget->childWidget();

		foreach ( self::byGroup( $widget->controls() ) as $group => $controls ) {
			$sections[] = self::section( $group, self::groupLabel( $group ), array_map( array( self::class, 'fromSchema' ), $controls ) );

			if ( $item && 'content' === $group ) {
				$sections[] = self::itemsSection( $item );
			}
		}

		return $sections;
	}

	/**
	 * Property paths whose change must re-render the element in the builder canvas.
	 *
	 * @return string[]
	 */
	public static function paths( Widget $widget ): array {
		$paths = array();

		foreach ( $widget->controls() as $control ) {
			$paths[] = 'content.' . $control['group'] . '.' . $control['key'];
		}

		if ( $widget->childWidget() ) {
			$paths[] = 'content.' . self::ITEMS_SECTION . '.' . self::ITEMS_CONTROL;
		}

		return $paths;
	}

	/**
	 * The controls that take Breakdance dynamic data: the small button beside a field that binds it to
	 * a post title, excerpt, author, our own reading-time field, and so on. Breakdance resolves the
	 * value before the element renders, so the widget only ever sees plain text.
	 *
	 * @return array<int, array{accepts: string, path: string}>
	 */
	public static function dynamicPaths( Widget $widget ): array {
		$paths = array();

		foreach ( $widget->controls() as $control ) {
			$accepts = self::accepts( $control );
			if ( '' !== $accepts && in_array( $control['group'], array( 'content', 'media' ), true ) ) {
				$paths[] = array(
					'accepts' => $accepts,
					'path'    => 'content.' . $control['group'] . '.' . $control['key'],
				);
			}
		}

		$item = $widget->childWidget();
		if ( $item ) {
			foreach ( $item->controls() as $control ) {
				$accepts = self::accepts( $control );
				if ( '' !== $accepts ) {
					$paths[] = array(
						'accepts' => $accepts,
						'path'    => 'content.' . self::ITEMS_SECTION . '.' . self::ITEMS_CONTROL . '[].' . $control['key'],
					);
				}
			}
		}

		return $paths;
	}

	/**
	 * Replaces every string in $properties that holds a dynamic-data token ([breakdance_dynamic …]) with
	 * whatever $resolve makes of it, at any depth. Data in, data out: how a token is resolved is the
	 * caller's business.
	 *
	 * @param array<string, mixed>     $properties
	 * @param callable(string): string $resolve
	 * @return array<string, mixed>
	 */
	public static function resolveTokens( array $properties, callable $resolve ): array {
		foreach ( $properties as $key => $value ) {
			if ( is_array( $value ) ) {
				$properties[ $key ] = self::resolveTokens( $value, $resolve );
			} elseif ( is_string( $value ) && false !== strpos( $value, '[breakdance_dynamic' ) ) {
				$properties[ $key ] = $resolve( $value );
			}
		}

		return $properties;
	}

	/**
	 * What a control can be bound to. Images are left out: Breakdance's dynamic image is a URL, and
	 * the widgets take an attachment id.
	 *
	 * @param array<string, mixed> $control
	 */
	private static function accepts( array $control ): string {
		switch ( $control['type'] ) {
			case 'text':
			case 'textarea':
				return 'string';

			case 'url':
				return 'url';

			default:
				return '';
		}
	}

	/**
	 * What a newly inserted element starts with: control defaults, the widget's starting copy, and
	 * sample rows for a container.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults( Widget $widget ): array {
		$values = array_merge( $widget->defaultAttributes(), $widget->sampleAtts() );
		$out    = array();

		foreach ( $widget->controls() as $control ) {
			$value = $values[ $control['key'] ] ?? '';

			// An empty default is the same as no value; `false` is left out too, since an unset
			// toggle reads as "use the default" (see attsFromProperties()).
			if ( '' === $value || 0 === $value || false === $value || null === $value ) {
				continue;
			}

			$out[ $control['group'] ][ $control['key'] ] = $value;
		}

		$item = $widget->childWidget();
		if ( $item ) {
			$rows = array();
			foreach ( $item->sampleRows() as $row ) {
				$rows[] = array_filter(
					array_merge( $item->defaultAttributes(), $row ),
					static function ( $value ) {
						return '' !== $value && null !== $value;
					}
				);
			}
			$out[ self::ITEMS_SECTION ][ self::ITEMS_CONTROL ] = $rows;
		}

		return array( 'content' => $out );
	}

	/**
	 * Breakdance's saved properties → the attributes a widget takes. A control that was never
	 * set falls back to the widget's default.
	 *
	 * @param array<string, mixed> $properties
	 * @return array<string, mixed>
	 */
	public static function attsFromProperties( Widget $widget, array $properties ): array {
		$content = isset( $properties['content'] ) && is_array( $properties['content'] ) ? $properties['content'] : array();
		$atts    = array();

		foreach ( $widget->controls() as $control ) {
			$group = isset( $content[ $control['group'] ] ) && is_array( $content[ $control['group'] ] ) ? $content[ $control['group'] ] : array();
			$atts[ $control['key'] ] = self::fromProperty( $control, $group[ $control['key'] ] ?? null );
		}

		return $atts;
	}

	/**
	 * The item rows of a container, as attribute sets for its child widget. A row that hasn't been given
	 * the value that names it — the empty row "Add" creates, an FAQ item with no question — is left out:
	 * it would render as an empty button and, for the FAQ, as an empty entry in its structured data.
	 *
	 * @param array<string, mixed> $properties
	 * @return array<int, array<string, mixed>>
	 */
	public static function rowsFromProperties( Widget $child, array $properties ): array {
		$rows = $properties['content'][ self::ITEMS_SECTION ][ self::ITEMS_CONTROL ] ?? array();
		$name = self::rowKey( $child );
		$out  = array();

		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$atts = array();
			foreach ( $child->controls() as $control ) {
				$atts[ $control['key'] ] = self::fromProperty( $control, $row[ $control['key'] ] ?? null );
			}

			if ( '' === trim( (string) ( $atts[ $name ] ?? '' ) ) ) {
				continue;
			}

			$out[] = $atts;
		}

		return $out;
	}

	/**
	 * The control that names a row: a title-like one if the item has it, else its first text field. It
	 * titles the row in the builder's list, and a row without it isn't rendered.
	 */
	public static function rowKey( Widget $item ): string {
		$controls = $item->controls();
		$keys     = array_column( $controls, 'key' );

		foreach ( array( 'title', 'question', 'heading' ) as $preferred ) {
			if ( in_array( $preferred, $keys, true ) ) {
				return $preferred;
			}
		}

		foreach ( $controls as $control ) {
			if ( 'text' === $control['type'] ) {
				return $control['key'];
			}
		}

		return 'title';
	}

	/**
	 * @param array<string, mixed> $control
	 * @param mixed                $value
	 * @return mixed
	 */
	private static function fromProperty( array $control, $value ) {
		$default = $control['default'] ?? '';

		if ( null === $value ) {
			return $default;
		}

		switch ( $control['type'] ) {
			case 'toggle':
				return filter_var( $value, FILTER_VALIDATE_BOOLEAN );

			case 'image':
				// Breakdance's media control stores an object; its attachment id is what we keep.
				if ( is_array( $value ) ) {
					$value = $value['id'] ?? ( $value['media']['id'] ?? 0 );
				}

				return absint( $value );

			case 'number':
				return is_numeric( $value ) ? $value + 0 : $default;

			default:
				return is_scalar( $value ) ? (string) $value : $default;
		}
	}

	/**
	 * @param array<string, mixed> $control
	 * @return array<string, mixed>
	 */
	private static function fromSchema( array $control ): array {
		switch ( $control['type'] ) {
			case 'textarea':
				$options = array(
					'type'        => 'text',
					'layout'      => 'vertical',
					'textOptions' => array( 'multiline' => true ),
				);
				break;

			case 'richtext':
				$options = array(
					'type'   => 'richtext',
					'layout' => 'vertical',
				);
				break;

			case 'url':
				$options = array(
					'type'        => 'text',
					'layout'      => 'vertical',
					'placeholder' => 'https://',
				);
				break;

			case 'image':
				$options = array(
					'type'         => 'wpmedia',
					'layout'       => 'vertical',
					'mediaOptions' => array(
						'acceptedFileTypes' => array( 'image' ),
						'multiple'          => false,
					),
				);
				break;

			case 'toggle':
				$options = array(
					'type'   => 'toggle',
					'layout' => 'inline',
				);
				break;

			case 'select':
				$items = array();
				foreach ( $control['options'] ?? array() as $value => $label ) {
					$items[] = array(
						'value' => (string) $value,
						'text'  => (string) $label,
					);
				}
				$options = array(
					'type'   => 'dropdown',
					'layout' => 'vertical',
					'items'  => $items,
				);
				break;

			case 'number':
				$options = array(
					'type'   => 'number',
					'layout' => 'inline',
				);
				break;

			default:
				$options = array(
					'type'   => 'text',
					'layout' => 'vertical',
				);
		}

		return self::make( $control['key'], (string) $control['label'], $options );
	}

	private static function itemsSection( Widget $item ): array {
		$first = self::rowKey( $item );

		$repeater = self::make(
			self::ITEMS_CONTROL,
			$item->title(),
			array(
				'type'            => 'repeater',
				'layout'          => 'vertical',
				'repeaterOptions' => array(
					'titleTemplate' => '{' . $first . '}',
					'defaultTitle'  => $item->title(),
					/* translators: %s: the kind of item, e.g. "EV FAQ Item" */
					'buttonName'    => sprintf( __( 'Add %s', 'ev-charging-experience' ), $item->title() ),
				),
			),
			array_map( array( self::class, 'fromSchema' ), $item->controls() )
		);

		return self::section( self::ITEMS_SECTION, __( 'Items', 'ev-charging-experience' ), array( $repeater ) );
	}

	/**
	 * @param array<int, array<string, mixed>> $controls
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	private static function byGroup( array $controls ): array {
		$grouped = array();

		foreach ( $controls as $control ) {
			$grouped[ $control['group'] ][] = $control;
		}

		uksort(
			$grouped,
			static function ( $a, $b ) {
				$pa = array_search( $a, self::GROUP_ORDER, true );
				$pb = array_search( $b, self::GROUP_ORDER, true );

				return ( false === $pa ? 99 : $pa ) <=> ( false === $pb ? 99 : $pb );
			}
		);

		return $grouped;
	}

	private static function groupLabel( string $group ): string {
		$labels = array(
			'content'    => __( 'Content', 'ev-charging-experience' ),
			'media'      => __( 'Media', 'ev-charging-experience' ),
			'layout'     => __( 'Layout', 'ev-charging-experience' ),
			'visual'     => __( 'Visual', 'ev-charging-experience' ),
			'motion'     => __( 'Motion', 'ev-charging-experience' ),
			'responsive' => __( 'Responsive', 'ev-charging-experience' ),
			'advanced'   => __( 'Advanced', 'ev-charging-experience' ),
		);

		return $labels[ $group ] ?? ucfirst( $group );
	}

	/**
	 * @param array<int, array<string, mixed>> $children
	 * @return array<string, mixed>
	 */
	private static function section( string $slug, string $label, array $children ): array {
		return self::make(
			$slug,
			$label,
			array(
				'type'   => 'section',
				'layout' => 'vertical',
			),
			$children
		);
	}

	/**
	 * The shape \Breakdance\Elements\c() returns, built here so this class needs no Breakdance function.
	 *
	 * @param array<string, mixed>             $options
	 * @param array<int, array<string, mixed>> $children
	 * @return array<string, mixed>
	 */
	private static function make( string $slug, string $label, array $options, array $children = array() ): array {
		return array(
			'slug'               => $slug,
			'label'              => $label,
			'options'            => $options,
			'enableMediaQueries' => false,
			'enableHover'        => false,
			'children'           => $children,
			'keywords'           => array(),
		);
	}
}
