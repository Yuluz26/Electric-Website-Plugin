<?php

namespace EVPX\Breakdance\Fields;

use EVPX\Support\ReadingTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Only ever loaded from DynamicData::registerFields() after confirming
 * \Breakdance\DynamicData\StringField exists — never reference this class
 * anywhere that could run before that guard.
 */
final class ReadingTimeField extends \Breakdance\DynamicData\StringField {

	public function label(): string {
		return __( 'EV Reading Time', 'ev-charging-experience' );
	}

	public function category(): string {
		return __( 'EV Charging Experience', 'ev-charging-experience' );
	}

	public function slug(): string {
		return 'evpx_reading_time';
	}

	public function handler( $attributes ): \Breakdance\DynamicData\StringData {
		$post = get_post();

		if ( ! $post ) {
			return \Breakdance\DynamicData\StringData::emptyString();
		}

		return \Breakdance\DynamicData\StringData::fromString( ReadingTime::labelForPost( $post ) );
	}
}
