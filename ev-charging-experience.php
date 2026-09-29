<?php
/**
 * Plugin Name:       EV Charging Experience
 * Plugin URI:        https://marcopolosupplies.com/
 * Description:       Premium, neumorphic, editorial EV-charging article components for WordPress + Breakdance. Adds capabilities to Breakdance; never overrides it.
 * Version:           0.5.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Marco Polo Supplies
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ev-charging-experience
 * Domain Path:       /languages
 */

namespace EVPX;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

// --------------------------------------------------------------------
// Constants
// --------------------------------------------------------------------

define( 'EVPX_VERSION', '0.5.0' );
define( 'EVPX_FILE', __FILE__ );
define( 'EVPX_PATH', plugin_dir_path( __FILE__ ) );
define( 'EVPX_URL', plugin_dir_url( __FILE__ ) );
define( 'EVPX_BASENAME', plugin_basename( __FILE__ ) );
define( 'EVPX_MIN_PHP', '7.4' );

// --------------------------------------------------------------------
// PSR-4 autoloader (EVPX\ -> src/). No Composer/vendor dependency so a
// plain ZIP upload always works, even if nobody runs `composer install`.
// --------------------------------------------------------------------

spl_autoload_register(
	static function ( $class_name ) {
		$prefix = __NAMESPACE__ . '\\';

		if ( strncmp( $prefix, $class_name, strlen( $prefix ) ) !== 0 ) {
			return;
		}

		$relative = substr( $class_name, strlen( $prefix ) );
		$relative = str_replace( '\\', DIRECTORY_SEPARATOR, $relative );
		$file     = EVPX_PATH . 'src' . DIRECTORY_SEPARATOR . $relative . '.php';

		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

// --------------------------------------------------------------------
// PHP version guard. Never fatal on activation for unsupported PHP.
// --------------------------------------------------------------------

if ( version_compare( PHP_VERSION, EVPX_MIN_PHP, '<' ) ) {
	add_action(
		'admin_notices',
		static function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: required PHP version, 2: current PHP version */
						__( 'EV Charging Experience requires PHP %1$s or higher. You are running %2$s. The plugin has been deactivated.', 'ev-charging-experience' ),
						EVPX_MIN_PHP,
						PHP_VERSION
					)
				)
			);
		}
	);

	add_action(
		'admin_init',
		static function () {
			if ( function_exists( 'deactivate_plugins' ) ) {
				deactivate_plugins( EVPX_BASENAME );
			}
		}
	);

	return;
}

// --------------------------------------------------------------------
// Activation / deactivation — guard-only, never touches user content.
// --------------------------------------------------------------------

register_activation_hook( EVPX_FILE, array( Core\Activation::class, 'run' ) );
register_deactivation_hook( EVPX_FILE, array( Core\Deactivation::class, 'run' ) );

// --------------------------------------------------------------------
// Boot — at include time, deliberately not on `plugins_loaded`.
//
// Breakdance fires `breakdance_loaded` from its own `plugins_loaded`
// callback and reads its Element Studio save locations at priority 10 on
// that action. WordPress loads plugins alphabetically and "breakdance"
// sorts before this plugin, so a hook added from a `plugins_loaded`
// callback here would be registered after `breakdance_loaded` has already
// fired and would never run. boot() only adds hooks and calls no other
// plugin's code, so doing it at include time is safe.
// --------------------------------------------------------------------

Core\Plugin::instance()->boot();
