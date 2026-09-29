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

	/** A native element rendered on this request, so Breakdance prints the assets for it. */
	private static bool $delivered = false;

	private static int $quiet = 0;

	public const CSS_HANDLE     = 'evpx-styles';
	public const JS_HANDLE      = 'evpx-script';
	public const GSAP_HANDLE    = 'evpx-gsap';
	public const ST_HANDLE      = 'evpx-gsap-scrolltrigger';
	public const MOTION_HANDLE  = 'evpx-motion';

	private const GSAP_VERSION = '3.12.5';

	public static function markActive(): void {
		if ( 0 === self::$quiet ) {
			self::$active = true;
		}
	}

	/**
	 * A native Breakdance element rendered. Breakdance prints the stylesheet and scripts for it from
	 * the dependencies the element declares (NativeElement::dependencies()), in <head> and once per
	 * page, wherever it sits. WordPress must then not queue the same files a second time for the
	 * shortcodes and blocks on the page: two copies of GSAP is what that would mean.
	 *
	 * A dependency condition can't do this job from Breakdance's side — Breakdance caches the
	 * dependencies it collects per document, so a condition that depends on the request would be
	 * frozen in the cache.
	 */
	public static function breakdanceDelivers(): void {
		if ( 0 !== self::$quiet ) {
			return;
		}

		self::$delivered = true;

		// A classic theme prints <head> before it renders the body, so WordPress may have queued
		// the scripts already. They are only printed in the footer, so they can still be withdrawn.
		// (The stylesheet has been printed by then; a second copy of that is harmless, a second copy of GSAP is not.)
		foreach ( array( self::JS_HANDLE, self::GSAP_HANDLE, self::ST_HANDLE, self::MOTION_HANDLE ) as $handle ) {
			wp_dequeue_script( $handle );
		}
	}

	/**
	 * Run $callback without flagging the page as using EV elements. Rendering a
	 * widget only to measure its text (reading time) must not make a listing
	 * page load the widgets' CSS and JavaScript.
	 *
	 * @return mixed Whatever $callback returns.
	 */
	public static function quietly( callable $callback ) {
		++self::$quiet;

		try {
			return $callback();
		} finally {
			--self::$quiet;
		}
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

	/**
	 * Every URL the front end loads, with the GSAP filters applied. One list for both
	 * consumers: WordPress's enqueue below, and the dependencies a Breakdance element declares.
	 *
	 * GSAP is not bundled (its licence restricts redistribution inside products like page-builder
	 * add-ons), so it loads from cdnjs by default. Sites that need it self-hosted (strict CSP,
	 * privacy policy, offline) point the filters at their own copy.
	 *
	 * @return array{style: string, core: string, gsap: string, scrolltrigger: string, motion: string}
	 */
	public static function urls(): array {
		return array(
			'style'         => EVPX_URL . 'assets/css/evpx.css',
			'core'          => EVPX_URL . 'assets/js/evpx.js',
			'gsap'          => (string) apply_filters( 'evpx_gsap_src', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/' . self::GSAP_VERSION . '/gsap.min.js' ),
			'scrolltrigger' => (string) apply_filters( 'evpx_scrolltrigger_src', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/' . self::GSAP_VERSION . '/ScrollTrigger.min.js' ),
			'motion'        => EVPX_URL . 'assets/js/motion.js',
		);
	}

	public function registerAssets(): void {
		$urls = self::urls();

		wp_register_style( self::CSS_HANDLE, $urls['style'], array(), EVPX_VERSION );
		wp_register_script( self::GSAP_HANDLE, $urls['gsap'], array(), self::GSAP_VERSION, true );
		wp_register_script( self::ST_HANDLE, $urls['scrolltrigger'], array( self::GSAP_HANDLE ), self::GSAP_VERSION, true );
		wp_register_script( self::JS_HANDLE, $urls['core'], array(), EVPX_VERSION, true );
		wp_register_script( self::MOTION_HANDLE, $urls['motion'], array( self::JS_HANDLE, self::GSAP_HANDLE, self::ST_HANDLE ), EVPX_VERSION, true );

		wp_localize_script(
			self::JS_HANDLE,
			'EVPX_CONFIG',
			array(
				'builderContext' => ( new Compatibility() )->isBuilderContext(),
			)
		);
	}

	public function maybeEnqueueEarly(): void {
		$post = is_singular() ? get_post( get_queried_object_id() ) : null;

		// Block themes and Breakdance's own templates render the page body before <head> is printed,
		// so by now an element may already have rendered — in the content, a header or a footer.
		// That is exact, and covers what a look at the queried post's content can't see.
		$found = self::$active || ( $post instanceof \WP_Post && $this->postUsesElements( $post ) );

		/**
		 * Load the EV stylesheet and scripts in <head> for this request.
		 *
		 * Detected automatically for a post whose content, or Breakdance element
		 * tree, contains an EV shortcode/block. Return true to force it where the
		 * widgets live somewhere detection can't see (a Breakdance header, footer
		 * or template, a widget area), so the page never paints unstyled first.
		 *
		 * @param bool          $load Whether the assets were detected as needed.
		 * @param \WP_Post|null $post The queried post, or null on non-singular requests.
		 */
		if ( apply_filters( 'evpx_load_assets', $found, $post ) ) {
			$this->enqueueAll();
		}
	}

	public function maybeEnqueueLate(): void {
		if ( ! self::$active || self::$delivered ) {
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
		$this->queueAssets();

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

	/** The front-end enqueue: skipped once Breakdance is printing the same files for a native element. */
	private function enqueueAll(): void {
		if ( ! self::$delivered ) {
			$this->queueAssets();
		}
	}

	private function queueAssets(): void {
		wp_enqueue_style( self::CSS_HANDLE );
		wp_enqueue_script( self::JS_HANDLE );
		wp_enqueue_script( self::GSAP_HANDLE );
		wp_enqueue_script( self::ST_HANDLE );
		wp_enqueue_script( self::MOTION_HANDLE );
	}

	private function postUsesElements( \WP_Post $post ): bool {
		foreach ( ( new \EVPX\Elements\Registry() )->all() as $element ) {
			if ( has_shortcode( (string) $post->post_content, $element->shortcodeTag() ) ) {
				return true;
			}

			if ( function_exists( 'has_block' ) && has_block( $element->blockName(), $post ) ) {
				return true;
			}
		}

		return $this->breakdanceTreeUsesElements( $post->ID );
	}

	/**
	 * A page designed in Breakdance has an empty post_content: the widgets sit
	 * in Shortcode elements inside its element tree. Without this the stylesheet
	 * would only be printed by the footer fallback, after all the content, and
	 * the whole article would flash unstyled first.
	 */
	private function breakdanceTreeUsesElements( int $post_id ): bool {
		if ( ! function_exists( '\Breakdance\Data\get_tree' ) ) {
			return false;
		}

		$tree = \Breakdance\Data\get_tree( $post_id );

		return is_array( $tree ) && false !== strpos( (string) wp_json_encode( $tree ), '[evpx_' );
	}
}
