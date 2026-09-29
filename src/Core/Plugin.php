<?php

namespace EVPX\Core;

use EVPX\Admin\Notices;
use EVPX\Breakdance\Compatibility;
use EVPX\Breakdance\DynamicData;
use EVPX\Breakdance\ElementStudioBridge;
use EVPX\Breakdance\Native\NativeElements;
use EVPX\Assets\Loader;
use EVPX\Elements\Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central bootstrap. Wires subsystems together; contains no business logic
 * of its own so each subsystem stays independently testable.
 */
final class Plugin {

	private static ?Plugin $instance = null;

	private bool $booted = false;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}

	public function boot(): void {
		if ( $this->booted ) {
			return; // Idempotent: never double-register hooks.
		}
		$this->booted = true;

		// `init`, not now: this runs at include time, before pluggable functions
		// and the user's locale exist. Priority 1 so every later `init` callback
		// (block and shortcode registration) already has its translations.
		add_action( 'init', array( $this, 'loadTextdomain' ), 1 );

		( new Compatibility() )->register();
		( new ElementStudioBridge() )->register();
		( new NativeElements() )->register();
		( new DynamicData() )->register();
		( new Loader() )->register();
		( new Registry() )->register();

		if ( is_admin() ) {
			( new Notices() )->register();
		}
	}

	public function loadTextdomain(): void {
		load_plugin_textdomain( 'ev-charging-experience', false, dirname( EVPX_BASENAME ) . '/languages' );
	}
}
