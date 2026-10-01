<?php

namespace EVPX\Breakdance\Native;

use EVPX\Assets\Loader;
use EVPX\Elements\Element as Widget;
use EVPX\Elements\Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * What makes a class one of the EV elements in Breakdance's Add panel — the "custom widgets
 * appear in Breakdance and are editable" half of the PRD, next to the shortcode and block.
 *
 * A trait, not a base class, on purpose: Breakdance lists every *declared* subclass of its
 * Element class and instantiates each one to read its definition, so an abstract base of ours would
 * be found, instantiated and crash the builder ("Cannot instantiate abstract class").
 *
 * It adds no design or markup of its own. Controls, defaults and the rendered HTML all come from
 * the one widget behind it (EVPX\Elements\Element), so an element edited in the builder, a
 * shortcode and a block are the same component. Breakdance renders it server-side (SSR): the
 * builder posts the element's properties, we answer with the widget's HTML.
 *
 * Breakdance finds elements as declared subclasses of \Breakdance\Elements\Element whose slug is
 * their own class name in the form Namespace\Name, so the concrete classes are EVPX\Hero,
 * EVPX\Faq…, one file each in src/Breakdance/Native/elements/. They are only loaded once Breakdance
 * has declared its base class (NativeElements::declareElements()); nothing may reference them earlier.
 * The class names are stored in every page built with them: renaming one breaks those pages.
 */
trait NativeElement {

	/** The widget this element edits and renders. */
	abstract protected static function widget(): Widget;

	public static function slug() {
		return static::class;
	}

	public static function name() {
		return static::widget()->title();
	}

	/** The class Breakdance puts on the wrapper it renders around the widget. */
	public static function className() {
		return 'evpx-native-' . static::widget()->slug();
	}

	public static function category() {
		return NativeElements::CATEGORY;
	}

	public static function tag() {
		return 'div';
	}

	public static function tagOptions() {
		return array( 'div' );
	}

	public static function uiIcon() {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 9-12h-7l1-8z"/></svg>';
	}

	public static function availableIn() {
		return array( 'breakdance', 'oxygen' );
	}

	public static function order() {
		foreach ( ( new Registry() )->all() as $index => $widget ) {
			if ( $widget->slug() === static::widget()->slug() ) {
				return 1000 + $index;
			}
		}

		return 1999;
	}

	/** The widget draws everything; Breakdance only hands over its properties. */
	public static function template() {
		return '%%SSR%%';
	}

	/** Breakdance's wrapper is a plain block: full width of its Section or column, never shrink-wrapped. */
	public static function defaultCss() {
		return '.breakdance .' . static::className() . ' { display: block; width: 100%; max-width: 100%; }';
	}

	/** @return array<int, array<string, mixed>> */
	public static function contentControls() {
		return Controls::sections( static::widget() );
	}

	public static function designControls() {
		return array();
	}

	public static function settingsControls() {
		return array();
	}

	public static function defaultProperties() {
		return Controls::defaults( static::widget() );
	}

	/** @return string[] */
	public static function propertyPathsToSsrElementWhenValueChanges() {
		return Controls::paths( static::widget() );
	}

	/** @return array<int, array{accepts: string, path: string}> */
	public static function dynamicPropertyPaths() {
		return Controls::dynamicPaths( static::widget() );
	}

	public static function nestingRule() {
		return array( 'type' => 'final' );
	}

	/**
	 * The stylesheet and scripts, delivered the Breakdance way — declared here, printed by
	 * Breakdance where they belong (styles in <head>, scripts in the footer, each once per page)
	 * wherever the element sits: a page, a template, a header. Nothing is enqueued through
	 * WordPress for these, so nothing loads twice. The canvas only needs the styles: the
	 * scripts are motion and interaction, both off inside the builder.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function dependencies() {
		$urls = Loader::urls();

		return array(
			array(
				'title'             => 'EV Charging Experience: styles',
				'styles'            => array( $urls['style'] ),
				'builderCondition'  => 'return true;',
				'frontendCondition' => 'return true;',
			),
			array(
				'title'             => 'EV Charging Experience: scripts',
				'scripts'           => array( $urls['core'], $urls['gsap'], $urls['scrolltrigger'], $urls['motion'] ),
				'builderCondition'  => 'return false;',
				'frontendCondition' => 'return true;',
			),
		);
	}

	/**
	 * Breakdance calls this positionally with four arguments; only the first is used, so the others
	 * keep the parent's signature but no name of ours.
	 *
	 * @param mixed $properties_data        Breakdance's saved properties for this element.
	 * @param mixed $parent_properties_data Unused: these elements have no parent-driven state.
	 * @param bool  $is_builder             Unused: builder requests are recognised by Compatibility.
	 * @param int   $repeater_item_node_id  Unused.
	 */
	public static function ssr( $properties_data, $parent_properties_data = array(), $is_builder = false, $repeater_item_node_id = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		// Breakdance prints this element's assets (see dependencies()); WordPress must not add a second copy.
		Loader::breakdanceDelivers();

		$widget     = static::widget();
		$properties = is_array( $properties_data ) ? $properties_data : array();
		$content    = '';

		// On the front end Breakdance has already swapped dynamic-data tokens for their values by the time
		// an element renders. In the builder's server-side render it hasn't — the raw properties arrive, and
		// an element that doesn't resolve them itself shows "[breakdance_dynamic …]" in the canvas.
		if ( function_exists( '\Breakdance\isRequestFromBuilderSsr' ) && \Breakdance\isRequestFromBuilderSsr() ) {
			$properties = Controls::resolveTokens(
				$properties,
				static function ( $value ) {
					return static::dynamicValue( (string) $value );
				}
			);
		}

		$item = $widget->childWidget();
		if ( $item ) {
			foreach ( Controls::rowsFromProperties( $item, $properties ) as $row ) {
				$content .= $item->renderWithAttributes( $row, '', false );
			}
		}

		return $widget->renderWithAttributes( Controls::attsFromProperties( $widget, $properties ), $content, false );
	}

	/**
	 * The value of a dynamic-data token, resolved the way Breakdance's own server-side-rendered
	 * elements do it. A token Breakdance can't resolve stays as it was, so the editor sees what to fix.
	 */
	private static function dynamicValue( string $value ): string {
		if ( ! function_exists( '\Breakdance\DynamicData\renderDynamicShortcodes' ) ) {
			return $value;
		}

		try {
			$resolved = \Breakdance\DynamicData\renderDynamicShortcodes( $value );
		} catch ( \Throwable $e ) {
			return $value;
		}

		return is_scalar( $resolved ) ? (string) $resolved : $value;
	}
}
