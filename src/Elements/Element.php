<?php

namespace EVPX\Elements;

use EVPX\Assets\Loader;
use EVPX\Breakdance\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared contract for every EV element (shortcode + block, in one place,
 * from one control schema, so there is exactly one render path per widget
 * — never a separate shortcode renderer and block renderer drifting apart).
 *
 * Subclasses implement slug(), title(), controls() and render(). Everything
 * else (attribute defaults, sanitization, shortcode registration, block
 * registration) is derived from controls() here.
 *
 * Control schema (one array per control):
 *   key      string   attribute name
 *   label    string   human label (editor UI + docs)
 *   type     string   text|textarea|richtext|url|image|toggle|select|number
 *   group    string   content|media|layout|visual|motion|responsive|advanced
 *   default  mixed
 *   options  array    ['value' => 'Label', ...] — required for type=select
 */
abstract class Element {

	abstract public function slug(): string;

	abstract public function title(): string;

	/** @return array<int, array<string, mixed>> */
	abstract public function controls(): array;

	/**
	 * @param array<string, mixed> $atts Already defaulted + sanitized.
	 * @param string                $content Inner content: enclosed shortcode
	 *                                        text, or rendered inner blocks HTML.
	 */
	abstract public function render( array $atts, string $content = '' ): string;

	/**
	 * Child elements (e.g. a single FAQ item) that only make sense nested
	 * inside a container element's InnerBlocks/enclosed shortcode content.
	 * Containers override this with the block names of their allowed
	 * children; used only by the block editor to configure InnerBlocks.
	 *
	 * @return string[]
	 */
	public function allowedChildren(): array {
		return array();
	}

	public function shortcodeTag(): string {
		// Shortcode convention is underscores (evpx_faq_item); block names
		// keep the hyphenated slug (evpx/faq-item), matching each
		// ecosystem's own convention. slug() itself always stays hyphenated.
		return 'evpx_' . str_replace( '-', '_', $this->slug() );
	}

	public function blockName(): string {
		return 'evpx/' . $this->slug();
	}

	public function register(): void {
		add_shortcode( $this->shortcodeTag(), array( $this, 'renderShortcode' ) );

		add_action( 'init', array( $this, 'registerBlock' ) );
	}

	/**
	 * @param array<string, mixed>|string $atts
	 * @param string|null                 $content
	 */
	public function renderShortcode( $atts, $content = null ): string {
		$atts    = shortcode_atts( $this->defaultAttributes(), (array) $atts, $this->shortcodeTag() );
		$content = null === $content ? '' : do_shortcode( $this->stripAutopArtifacts( $content ) );

		return $this->renderWithAttributes( $atts, $content );
	}

	/**
	 * The one way in for every front end — shortcode, block and Breakdance element: default,
	 * sanitize, switch motion off for a builder, then render. Missing keys take their default.
	 *
	 * @param array<string, mixed> $raw     Attributes as the caller has them (unsanitized).
	 * @param string               $content Inner content: rendered children.
	 * @param bool                 $track   Whether this counts as "an EV element rendered" for the
	 *                                      asset fallback. A Breakdance element delivers its own
	 *                                      assets as Breakdance dependencies, so it says false.
	 */
	public function renderWithAttributes( array $raw, string $content = '', bool $track = true ): string {
		$atts = $this->withBuilderContext( $this->sanitizeAttributes( array_merge( $this->defaultAttributes(), $raw ) ) );

		if ( $track ) {
			Loader::markActive();
		}

		return $this->render( $atts, $content );
	}

	/**
	 * The widget that repeats inside this one (a FAQ's items, a card grid's cards), or null. A
	 * Breakdance element edits them as rows of one repeater instead of as nested elements.
	 */
	public function childWidget(): ?Element {
		$children = $this->allowedChildren();

		if ( empty( $children ) ) {
			return null;
		}

		foreach ( ( new Registry() )->all() as $element ) {
			if ( $element->blockName() === $children[0] ) {
				return $element;
			}
		}

		return null;
	}

	/**
	 * Starting copy for a new Breakdance element, on top of the control defaults, so a widget
	 * dragged in isn't a row of empty boxes. Shortcodes and blocks never use it.
	 *
	 * @return array<string, mixed>
	 */
	public function sampleAtts(): array {
		return array();
	}

	/**
	 * The rows a new Breakdance element starts with. Item widgets override this.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function sampleRows(): array {
		return array();
	}

	/**
	 * Inside a page builder (Breakdance's canvas and server-side renders, the
	 * block editor) nothing may be held back for an entrance animation, and
	 * markup injected after page load never gets the JS that would release it.
	 * So every control in the `motion` group is switched off. Driven by the
	 * control schema: a new widget with a motion toggle is covered without
	 * touching this.
	 *
	 * @param array<string, mixed> $atts Already sanitized.
	 * @return array<string, mixed>
	 */
	protected function withBuilderContext( array $atts ): array {
		if ( ! ( new Compatibility() )->isBuilderContext() ) {
			return $atts;
		}

		foreach ( $this->controls() as $control ) {
			if ( 'motion' !== ( $control['group'] ?? '' ) ) {
				continue;
			}

			if ( 'toggle' === $control['type'] ) {
				$atts[ $control['key'] ] = false;
			} elseif ( 'select' === $control['type'] && isset( $control['options']['off'] ) ) {
				$atts[ $control['key'] ] = 'off';
			}
		}

		return $this->builderPreview( $atts );
	}

	/**
	 * How a widget shows itself while being edited, when what a visitor sees would hide part of
	 * what the editor is changing: a comparison that shows one tab at a time, an FAQ answer folded
	 * away. Widgets override this to show everything at once; it applies only inside a builder.
	 *
	 * @param array<string, mixed> $atts Sanitized, motion already off.
	 * @return array<string, mixed>
	 */
	protected function builderPreview( array $atts ): array {
		return $atts;
	}

	/**
	 * The vertical rhythm control shared by every full-width section widget (see --evpx-section-y in
	 * the stylesheet). Inside a Breakdance Section, which brings its own padding, "None" avoids the
	 * two adding up.
	 *
	 * @return array<string, mixed>
	 */
	protected static function spacingControl(): array {
		return array(
			'key'     => 'spacing',
			'label'   => __( 'Vertical spacing', 'ev-charging-experience' ),
			'type'    => 'select',
			'group'   => 'layout',
			'default' => 'default',
			'options' => array(
				'default' => __( 'Default', 'ev-charging-experience' ),
				'compact' => __( 'Compact', 'ev-charging-experience' ),
				'none'    => __( 'None (the container already has padding)', 'ev-charging-experience' ),
			),
		);
	}

	/**
	 * The HTML id of a section widget, so a link elsewhere on the page (a hero button, a contents list)
	 * can point at it: `anchor="decision"` and `href="#decision"`. Letters, digits, hyphens and
	 * underscores only, which is what an id needs to survive a copy-paste into a URL.
	 *
	 * @return array<string, mixed>
	 */
	protected static function anchorControl(): array {
		return array(
			'key'      => 'anchor',
			'label'    => __( 'Anchor (HTML id, for links to this section)', 'ev-charging-experience' ),
			'type'     => 'text',
			'group'    => 'advanced',
			'default'  => '',
			'sanitize' => 'sanitize_html_class',
		);
	}

	/**
	 * WordPress's default `the_content` filter chain runs wpautop() at
	 * priority 10 and do_shortcode() at priority 11 — meaning wpautop
	 * mangles a multi-line nested-shortcode block (like our container
	 * elements' enclosed children) BEFORE do_shortcode ever sees it,
	 * inserting stray `<p>`/`<br />` tags between the nested tags. Those
	 * then survive do_shortcode's substitution as literal DOM siblings of
	 * our rendered children, which is exactly the kind of stray element
	 * that scrambles a CSS grid. Scoped fix, not a global filter reorder:
	 * clean only the content this element itself captured, since it's
	 * defined to be nothing but nested EVPX shortcodes — never prose that
	 * would legitimately need wpautop's formatting.
	 */
	protected function stripAutopArtifacts( string $content ): string {
		$content = preg_replace( '#<br\s*/?>#i', "\n", $content );
		$content = preg_replace( '#</?p\s*>#i', "\n", $content );

		return $content;
	}

	public function registerBlock(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$args = array(
			'title'           => $this->title(),
			'category'        => 'ev-charging',
			'icon'            => 'electric-car', // fallback dashicon; JS registration owns the real icon.
			'attributes'      => $this->blockAttributeSchema(),
			'render_callback' => array( $this, 'renderBlock' ),
		);

		if ( ! empty( $this->allowedChildren() ) ) {
			$args['attributes']['evpxAllowedChildren'] = array(
				'type'    => 'array',
				'default' => $this->allowedChildren(),
			);
		}

		register_block_type( $this->blockName(), $args );
	}

	/**
	 * @param array<string, mixed> $atts
	 * @param string                $content Server-rendered inner blocks HTML.
	 */
	public function renderBlock( array $atts, string $content = '' ): string {
		return $this->renderWithAttributes( $atts, $content );
	}

	/** @return array<string, mixed> */
	public function defaultAttributes(): array {
		$defaults = array();

		foreach ( $this->controls() as $control ) {
			$defaults[ $control['key'] ] = $control['default'] ?? '';
		}

		return $defaults;
	}

	/**
	 * @param array<string, mixed> $raw
	 * @return array<string, mixed>
	 */
	public function sanitizeAttributes( array $raw ): array {
		$clean = array();

		foreach ( $this->controls() as $control ) {
			$key   = $control['key'];
			$value = $raw[ $key ] ?? ( $control['default'] ?? '' );

			$clean[ $key ] = $this->sanitizeControlValue( $control, $value );
		}

		return $clean;
	}

	/**
	 * @param array<string, mixed> $control
	 * @param mixed                 $value
	 * @return mixed
	 */
	protected function sanitizeControlValue( array $control, $value ) {
		// A control that needs more than its type's rule names its own (the anchor's id).
		if ( isset( $control['sanitize'] ) && is_callable( $control['sanitize'] ) ) {
			return call_user_func( $control['sanitize'], (string) $value );
		}

		switch ( $control['type'] ) {
			case 'toggle':
				return filter_var( $value, FILTER_VALIDATE_BOOLEAN );

			case 'number':
				return is_numeric( $value ) ? $value + 0 : ( $control['default'] ?? 0 );

			case 'url':
				return esc_url_raw( (string) $value );

			case 'image':
				return absint( $value );

			case 'select':
				$options = array_keys( $control['options'] ?? array() );
				return in_array( $value, $options, true ) ? $value : ( $control['default'] ?? '' );

			case 'textarea':
			case 'richtext':
				return wp_kses_post( (string) $value );

			case 'text':
			default:
				return sanitize_text_field( (string) $value );
		}
	}

	/** @return array<string, array<string, mixed>> */
	protected function blockAttributeSchema(): array {
		$schema = array();

		foreach ( $this->controls() as $control ) {
			$schema[ $control['key'] ] = array(
				'type'    => $this->jsonSchemaType( $control['type'] ),
				'default' => $control['default'] ?? '',
			);
		}

		return $schema;
	}

	protected function jsonSchemaType( string $control_type ): string {
		switch ( $control_type ) {
			case 'toggle':
				return 'boolean';
			case 'number':
			case 'image':
				return 'number';
			default:
				return 'string';
		}
	}

	/**
	 * Render a PHP template partial from templates/ with $vars extracted
	 * into scope. Kept on the base class so every element shares the same
	 * view-rendering path instead of re-implementing output buffering.
	 *
	 * @param array<string, mixed> $vars
	 */
	protected function view( string $template, array $vars = array() ): string {
		$file = EVPX_PATH . 'templates/' . $template . '.php';

		if ( ! is_readable( $file ) ) {
			return '';
		}

		extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract

		ob_start();
		include $file;

		return (string) ob_get_clean();
	}

	/** Multi-paragraph body copy from a plain textarea/richtext control. */
	protected function autop( string $text ): string {
		return wpautop( $text );
	}

	/**
	 * Responsive <img> from an attachment id control value. Delegates to
	 * core so srcset/sizes come for free instead of being hand-rolled.
	 */
	protected function image( int $attachment_id, string $size = 'large', array $attr = array() ): string {
		if ( $attachment_id <= 0 ) {
			return '';
		}

		$html = wp_get_attachment_image( $attachment_id, $size, false, $attr );

		return is_string( $html ) ? $html : '';
	}

	/**
	 * Deterministic-enough unique id for accordion/aria wiring, stable for
	 * the lifetime of the request+element instance.
	 */
	protected function uniqueId( string $prefix = 'evpx' ): string {
		static $count = 0;
		++$count;

		return $prefix . '-' . substr( (string) wp_hash( $prefix . $count . microtime() ), 0, 8 );
	}
}
