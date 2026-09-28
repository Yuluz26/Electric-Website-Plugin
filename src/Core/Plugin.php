<?php

namespace EVPX\Core;

use EVPX\Admin\Notices;
use EVPX\Breakdance\Compatibility;
use EVPX\Breakdance\DynamicData;
use EVPX\Breakdance\ElementStudioBridge;
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

		load_plugin_textdomain( 'ev-charging-experience', false, dirname( EVPX_BASENAME ) . '/languages' );

		( new Compatibility() )->register();
		( new ElementStudioBridge() )->register();
		( new DynamicData() )->register();
		( new Loader() )->register();
		( new Registry() )->register();

		if ( is_admin() ) {
			( new Notices() )->register();
		}
	}
}
