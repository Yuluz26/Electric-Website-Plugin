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
 * This does not create elements by itself — Element Studio is a licensed,
 * in-builder visual tool with no documented file format to hand-author.
 * What this DOES do is make "EV Charging Elements" available as a save
 * target the moment Element Studio is opened on a real Breakdance site, so
 * building a native element from `docs/BREAKDANCE-ELEMENT-STUDIO-BRIDGE.md`
 * takes minutes, not a rebuild.
 */
final class ElementStudioBridge {

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
			'EVPX',
			'element',
			'EV Charging Elements',
			false
		);

		\Breakdance\ElementStudio\registerSaveLocation(
			$base . '/element-studio/presets',
			'EVPX',
			'preset',
			'EV Charging Presets',
			false
		);
	}
}
