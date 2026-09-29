<?php

namespace EVPX\Admin;

use EVPX\Breakdance\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Purely informational. The plugin works without Breakdance (shortcodes and
 * blocks render regardless) — this just helps the site owner understand
 * where the Breakdance-specific bridge features kick in.
 */
final class Notices {

	public function register(): void {
		add_action( 'admin_notices', array( $this, 'maybeShowBreakdanceHint' ) );
	}

	public function maybeShowBreakdanceHint(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'plugins' !== $screen->id ) {
			return;
		}

		if ( ( new Compatibility() )->isBreakdanceActive() ) {
			return;
		}

		printf(
			'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
			wp_kses(
				sprintf(
					/* translators: %s: plugin name */
					__( '<strong>%s</strong> is active and its shortcodes/blocks work on any WordPress site. Breakdance was not detected — install it to also get the EV elements in its Add panel (see the plugin\'s docs/BREAKDANCE.md).', 'ev-charging-experience' ),
					esc_html__( 'EV Charging Experience', 'ev-charging-experience' )
				),
				array( 'strong' => array() )
			)
		);
	}
}
