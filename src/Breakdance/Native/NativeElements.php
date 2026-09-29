<?php

namespace EVPX\Breakdance\Native;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Puts the EV widgets into Breakdance's Add panel as elements of their own.
 *
 * Declares the element classes when Breakdance announces itself and registers the "EV Charging"
 * category. Without Breakdance neither hook ever fires and none of this code is loaded.
 */
final class NativeElements {

	public const CATEGORY = 'evpx';

	/** One file per element in src/Breakdance/Native/elements/, named like the class it declares. */
	private const ELEMENTS = array( 'Hero', 'Section', 'Comparison', 'ScenarioCards', 'Flow', 'DecisionFactors', 'Faq', 'Related', 'Cta' );

	public function register(): void {
		// Priority 9, like the Element Studio save location: before Breakdance's own priority-10
		// work on the same action, and before anything can ask which elements exist.
		add_action( 'breakdance_loaded', array( $this, 'declareElements' ), 9 );

		// `init`, not `breakdance_loaded`: the label is translated, and translations aren't
		// available before `init`.
		add_action( 'init', array( $this, 'registerCategory' ), 5 );
	}

	public function declareElements(): void {
		if ( ! class_exists( '\Breakdance\Elements\Element' ) ) {
			return;
		}

		// Breakdance builds its element list from *declared* classes, so an autoloader that only
		// loads on first use would never be asked. Declare them now, explicitly.
		foreach ( self::ELEMENTS as $name ) {
			require_once EVPX_PATH . 'src/Breakdance/Native/elements/' . $name . '.php';
		}
	}

	public function registerCategory(): void {
		if ( ! function_exists( '\Breakdance\Elements\registerCategory' ) ) {
			return;
		}

		\Breakdance\Elements\registerCategory( self::CATEGORY, __( 'EV Charging', 'ev-charging-experience' ) );
	}
}
