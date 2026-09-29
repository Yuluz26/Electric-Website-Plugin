<?php

namespace EVPX\Breakdance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers this plugin's Element Studio save location, exactly matching
 * Breakdance's own official boilerplate (soflyy/breakdance-custom-elements):
 * https://github.com/soflyy/breakdance-custom-elements/blob/master/plugin.php
 *
 * This does not create elements by itself. The plugin's own native elements
 * (src/Breakdance/Native) don't come from Element Studio; what this does is make
 * "EV Charging Elements" available as a save target the moment Element Studio is
 * opened on a real Breakdance site, for elements a site owner builds on top of
 * the plugin's design (see `docs/BREAKDANCE.md`).
 */
final class ElementStudioBridge {

	/**
	 * The PHP namespace Element Studio gives elements saved here: it writes `namespace <this>;` and
	 * a class named after the element. Not `EVPX`: that namespace holds the plugin's own native
	 * elements (EVPX\Hero, EVPX\Faq…), so an element someone called "Hero" in Element Studio would
	 * declare the same class twice and take the site down.
	 */
	public const STUDIO_NAMESPACE = 'EVPXStudio';

	public function register(): void {
		add_action( 'breakdance_loaded', array( $this, 'registerSaveLocations' ), 9 );
	}

	public function registerSaveLocations(): void {
		if ( ! function_exists( '\Breakdance\ElementStudio\registerSaveLocation' )
			|| ! function_exists( '\Breakdance\Util\getDirectoryPathRelativeToPluginFolder' ) ) {
			return;
		}

		$base = \Breakdance\Util\getDirectoryPathRelativeToPluginFolder( EVPX_PATH );

		\Breakdance\ElementStudio\registerSaveLocation(
			$base . '/element-studio/elements',
			self::STUDIO_NAMESPACE,
			'element',
			'EV Charging Elements',
			false
		);

		\Breakdance\ElementStudio\registerSaveLocation(
			$base . '/element-studio/presets',
			self::STUDIO_NAMESPACE,
			'preset',
			'EV Charging Presets',
			false
		);
	}
}
