<?php

namespace EVPX\Breakdance;

use EVPX\Support\ReadingTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes a couple of EV-article values as Breakdance Dynamic Data fields
 * so *other*, native Breakdance elements on the same page can pull them in
 * (e.g. an existing Breakdance Text element showing "{Reading Time}").
 *
 * Field class shape follows Breakdance's documented Dynamic Data Field API
 * (soflyy/breakdance-developer-docs, dynamic-data/readme.md) exactly.
 */
final class DynamicData {

	public function register(): void {
		add_action( 'init', array( $this, 'registerFields' ) );
	}

	public function registerFields(): void {
		if ( ! function_exists( '\Breakdance\DynamicData\registerField' )
			|| ! class_exists( '\Breakdance\DynamicData\StringField' ) ) {
			return;
		}

		\Breakdance\DynamicData\registerField( new Fields\ReadingTimeField() );
	}
}
