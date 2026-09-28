<?php

namespace EVPX\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activation is intentionally inert beyond recording the installed version.
 * It must never rewrite posts, pages, Breakdance data, templates or theme
 * files — the plugin is a guest in the WordPress/Breakdance ecosystem.
 */
final class Activation {

	public static function run(): void {
		if ( false === get_option( 'evpx_version' ) ) {
			add_option( 'evpx_version', EVPX_VERSION );
		} else {
			update_option( 'evpx_version', EVPX_VERSION );
		}

		// No rewrite rules are registered by this plugin (shortcodes/blocks
		// only), so no flush_rewrite_rules() call is needed here.
	}
}
