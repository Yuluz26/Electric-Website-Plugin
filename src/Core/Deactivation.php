<?php

namespace EVPX\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivation must not corrupt or rewrite existing Breakdance content.
 * Existing pages that reference [evpx_*] shortcodes simply stop rendering
 * that markup (WordPress prints unknown shortcodes as plain text) rather
 * than losing content — nothing is deleted from post content or options.
 */
final class Deactivation {

	public static function run(): void {
		// Intentionally empty: no transients, scheduled events or rewrite
		// rules are registered by this plugin in v1, so there is nothing to
		// clean up yet. Present as an explicit no-op so future additions
		// have an obvious, already-wired place to add cleanup.
	}
}
