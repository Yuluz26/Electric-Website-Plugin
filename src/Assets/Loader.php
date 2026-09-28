<?php

namespace EVPX\Assets;

use EVPX\Breakdance\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conditional asset loading. Assets never load site-wide — only on
 * requests that actually render an EV element.
 *
 * The tricky part: Breakdance-built pages usually keep the real content in
 * Breakdance's own postmeta tree, not `post_content`, so a plain
 * `has_shortcode( $post->post_content )` check misses them. Fast path below
 * handles ordinary WP content (cheap, correct, prints in <head>). The
 * fallback guarantees correctness for everything else (Breakdance-rendered
 * shortcodes included) by checking whether any EV element actually fired
 * during this request, and — only if the fast path missed it — enqueuing
 * late in the footer rather than silently shipping a page with missing
 * styles/motion. Stability over micro-optimization, per the project SOP.
 */
final class Loader {

	private static bool $active = false;

	public const CSS_HANDLE     = 'evpx-styles';
	public const JS_HANDLE      = 'evpx-script';
	public const GSAP_HANDLE    = 'evpx-gsap';
	public const ST_HANDLE      = 'evpx-gsap-scrolltrigger';
	public const MOTION_HANDLE  = 'evpx-motion';

	private const GSAP_VERSION = '3.12.5';

	public static function markActive(): void {
		self::$active = true;
	}

	public static function isActive(): bool {
		return self::$active;
	}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'registerAssets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybeEnqueueEarly' ), 20 );
		add_action( 'wp_footer', array( $this, 'maybeEnqueueLate' ), 1 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueueBlockEditorAssets' ) );
	}

	public function registerAssets(): void {
		wp_register_style(
			self::CSS_HANDLE,
			EVPX_URL . 'assets/css/evpx.css',
			array(),
			EVPX_VERSION
		);

		// GSAP is not bundled (its licence restricts redistribution inside
		// products like page-builder add-ons), so it loads from cdnjs by
		// default. Sites that need it self-hosted (strict CSP, privacy policy,
		// offline) can point these filters at their own copy.
		$gsap_src = apply_filters(
			'evpx_gsap_src',
			'https://cdnjs.cloudflare.com/ajax/libs/gsap/' . self::GSAP_VERSION . '/gsap.min.js'
		);
		$st_src = apply_filters(
			'evpx_scrolltrigger_src',
			'https://cdnjs.cloudflare.com/ajax/libs/gsap/' . self::GSAP_VERSION . '/ScrollTrigger.min.js'
		);

		wp_register_script( self::GSAP_HANDLE, $gsap_src, array(), self::GSAP_VERSION, true );
		wp_register_script( self::ST_HANDLE, $st_src, array( self::GSAP_HANDLE ), self::GSAP_VERSION, true );

		wp_register_script(
			self::JS_HANDLE,
			EVPX_URL . 'assets/js/evpx.js',
			array(),
			EVPX_VERSION,
			true
		);

		wp_register_script(
			self::MOTION_HANDLE,
			EVPX_URL . 'assets/js/motion.js',
			array( self::JS_HANDLE, self::GSAP_HANDLE, self::ST_HANDLE ),
			EVPX_VERSION,
			true
		);

		wp_localize_script(
			self::JS_HANDLE,
			'EVPX_CONFIG',
			array(
				'builderContext' => ( new Compatibility() )->isBuilderContext(),
			)
		);
	}

	public function maybeEnqueueEarly(): void {
		if ( is_singular() && $this->singularLikelyHasElements( get_queried_object_id() ) ) {
			$this->enqueueAll();
		}
	}

	public function maybeEnqueueLate(): void {
		if ( ! self::$active ) {
			return;
		}

		if ( ! wp_style_is( self::CSS_HANDLE, 'enqueued' ) ) {
			$this->enqueueAll();
			// <head> already closed for this request — force the stylesheet
			// tags out now instead of silently shipping unstyled markup.
			wp_print_styles( array( self::CSS_HANDLE ) );
		}
	}

	public function enqueueBlockEditorAssets(): void {
		$this->enqueueAll();

		wp_enqueue_script(
			'evpx-block-editor',
			EVPX_URL . 'assets/js/block-editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
			EVPX_VERSION,
			true
		);

		wp_localize_script(
			'evpx-block-editor',
			'EVPX_BLOCKS',
			array( 'elements' => ( new \EVPX\Elements\Registry() )->editorSchema() )
		);
	}

	private function enqueueAll(): void {
		wp_enqueue_style( self::CSS_HANDLE );
		wp_enqueue_script( self::JS_HANDLE );
		wp_enqueue_script( self::GSAP_HANDLE );
		wp_enqueue_script( self::ST_HANDLE );
		wp_enqueue_script( self::MOTION_HANDLE );
	}

	private function singularLikelyHasElements( int $post_id ): bool {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		foreach ( ( new \EVPX\Elements\Registry() )->all() as $element ) {
			if ( has_shortcode( (string) $post->post_content, $element->shortcodeTag() ) ) {
				return true;
			}

			if ( function_exists( 'has_block' ) && has_block( $element->blockName(), $post ) ) {
				return true;
			}
		}

		return false;
	}
}
