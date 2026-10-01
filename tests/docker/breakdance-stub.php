<?php
/**
 * Contract stub for Breakdance — QA only, installed as a mu-plugin by
 * `EVPX_BREAKDANCE_STUB=1 bash tests/docker/setup.sh`.
 *
 * Breakdance is a licensed product and can't be installed in CI. This stub
 * defines just the symbols the plugin touches, with the signatures shown in
 * Breakdance's own public repositories (soflyy/breakdance-custom-elements
 * plugin.php and soflyy/breakdance-developer-docs dynamic-data/readme.md),
 * and records how they were called. tests/docker/breakdance-contract-check.php
 * then asserts the plugin drove them correctly.
 *
 * What this proves: the integration code runs without fatals when those APIs
 * exist, calls them with the documented argument shapes, and its Dynamic Data
 * field satisfies the documented base-class contract. What it can't prove:
 * that the real Breakdance runtime behaves like this stub.
 */

namespace Breakdance\Util {
	function getDirectoryPathRelativeToPluginFolder( $dir ) {
		// Assumption: returns the path from wp-content/plugins/ to $dir, no trailing slash.
		return trim( (string) preg_replace( '#^.*/wp-content/plugins/#', '', rtrim( $dir, '/' ) ), '/' );
	}
}

namespace Breakdance\ElementStudio {
	function registerSaveLocation( $path, $namespace, $type, $label, $flag ) {
		$GLOBALS['evpx_stub']['save_locations'][] = compact( 'path', 'namespace', 'type', 'label', 'flag' );
	}
}

namespace Breakdance\DynamicData {
	abstract class Field {
	}

	class StringData {
		public $value = '';

		public static function fromString( $string ) {
			$data        = new self();
			$data->value = (string) $string;
			return $data;
		}

		public static function emptyString() {
			return self::fromString( '' );
		}
	}

	abstract class StringField extends Field {
		abstract public function label();

		abstract public function category();

		abstract public function slug();

		abstract public function handler( $attributes ): StringData;
	}

	function registerField( $field ) {
		$GLOBALS['evpx_stub']['fields'][] = $field;
	}
}

namespace {
	$GLOBALS['evpx_stub'] = array(
		'save_locations' => array(),
		'fields'         => array(),
	);

	// What the real plugin's file declares as it loads.
	define( '__BREAKDANCE_VERSION', 'stub' );

	// Real order, as read from Breakdance 2.8.3's plugin.php: it fires `breakdance_loaded` from
	// its own `plugins_loaded` callback at the default priority, registered while its file loads —
	// before any plugin that sorts after "breakdance" has been included. This stub is an
	// mu-plugin, so it registers first too. (An earlier version fired the action at priority 20,
	// after this plugin booted, and so hid a bug where the plugin's hook came too late.)
	add_action(
		'plugins_loaded',
		static function () {
			do_action( 'breakdance_loaded' );
		}
	);
}
