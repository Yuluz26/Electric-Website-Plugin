<?php

namespace EVPX\Breakdance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detection helpers only. This class never hooks into Breakdance internals,
 * never touches `.bde-*` styles, and never modifies Breakdance templates —
 * it just answers "is Breakdance here" and "are we inside its builder"
 * so the rest of the plugin can behave conservatively.
 */
final class Compatibility {

	public function register(): void {
		// Nothing to hook yet; detection methods below are called on demand
		// by Assets\Loader and the Element renderers. Kept as its own class
		// (rather than static free functions) so it stays easy to unit test.
	}

	/**
	 * True once Breakdance's own bootstrap has actually run. Verified
	 * against Breakdance's official boilerplate, which fires
	 * `breakdance_loaded` before registering Element Studio save locations.
	 */
	public function isBreakdanceActive(): bool {
		if ( did_action( 'breakdance_loaded' ) > 0 ) {
			return true;
		}

		// Fallback for very early hooks (before breakdance_loaded has run
		// this request) — Breakdance defines this constant on load.
		return defined( 'BREAKDANCE_VERSION' );
	}

	/**
	 * True when the current request is rendering inside a page-builder
	 * editor context (Breakdance's canvas, or any other builder's), where
	 * autoplay/ScrollTrigger/observers must stay off. This is deliberately
	 * conservative: it is safe to under-animate in an edge case, never safe
	 * to animate inside a live builder canvas.
	 */
	public function isBuilderContext(): bool {
		if ( isset( $_GET['breakdance'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$mode = sanitize_key( wp_unslash( $_GET['breakdance'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( in_array( $mode, array( 'builder', 'edit', 'run' ), true ) ) {
				return true;
			}
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_SERVER['REQUEST_URI'] )
			&& false !== stripos( (string) $_SERVER['REQUEST_URI'], 'breakdance' ) ) {
			return true;
		}

		// Conservative safety net: only ever consider motion "on" for a
		// genuine public frontend request. Any admin/AJAX/REST context is
		// treated as a possible builder/editor and stays static.
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return true;
		}

		return false;
	}
}
