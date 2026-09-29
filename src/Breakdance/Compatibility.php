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
 *
 * Every signal below was read out of Breakdance 2.8.3's own source
 * (plugin.php, util/is-request-from-builder-iframe.php,
 * actions_filters/template_include.php), not guessed.
 */
final class Compatibility {

	public function register(): void {
		// Nothing to hook yet; detection methods below are called on demand
		// by Assets\Loader and the Element renderers. Kept as its own class
		// (rather than static free functions) so it stays easy to unit test.
	}

	/**
	 * True when Breakdance is running for this request. Its plugin file
	 * declares `__BREAKDANCE_VERSION` on load (and not at all when Breakdance
	 * is switched off for the request); `breakdance_loaded` is its own
	 * "bootstrap finished" action.
	 */
	public function isBreakdanceActive(): bool {
		return defined( '__BREAKDANCE_VERSION' ) || did_action( 'breakdance_loaded' ) > 0;
	}

	/**
	 * True when the current request renders content for a page-builder editor
	 * (Breakdance's canvas, its server-side element renders, or a WordPress
	 * admin/REST context such as the block editor), where entrance animation
	 * and scroll observers must stay off. Deliberately conservative: safe to
	 * under-animate in an edge case, never safe to animate inside a live
	 * builder canvas.
	 */
	public function isBuilderContext(): bool {
		// The builder shell: /?breakdance=builder&id=123
		if ( isset( $_GET['breakdance'] ) && 'builder' === sanitize_key( wp_unslash( $_GET['breakdance'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		// The canvas iframe. "Always added as a GET parameter to the iframe URL by builder."
		if ( ! empty( $_GET['breakdance_iframe'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		// Breakdance answers its own AJAX at *any* front-end URL (not admin-ajax.php),
		// so is_admin()/wp_doing_ajax() are false for element renders in the builder:
		// action=breakdance_server_side_render, breakdance_dynamic_data_get, …
		if ( isset( $_POST['action'] ) && 0 === strpos( sanitize_key( wp_unslash( $_POST['action'] ) ), 'breakdance_' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return true;
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
