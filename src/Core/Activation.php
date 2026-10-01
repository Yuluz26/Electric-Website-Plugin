<?php

namespace EVPX\Core;

use EVPX\Setup\ExamplePages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activation records the installed version and queues the example articles
 * (Setup\ExamplePages makes them, as drafts, on the next admin request). It
 * must never rewrite existing posts, pages, Breakdance data, templates or
 * theme files — the plugin is a guest in the WordPress/Breakdance ecosystem,
 * and the examples are new content beside them, not a change to theirs.
 */
final class Activation {

	public static function run(): void {
		if ( false === get_option( 'evpx_version' ) ) {
			add_option( 'evpx_version', EVPX_VERSION );
		} else {
			update_option( 'evpx_version', EVPX_VERSION );
		}

		ExamplePages::queue();

		// No rewrite rules are registered by this plugin (shortcodes/blocks
		// only), so no flush_rewrite_rules() call is needed here.
	}
}
