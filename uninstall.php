<?php
/**
 * Runs when the plugin is deleted from the Plugins screen (not when it is deactivated).
 *
 * Removes what the plugin stored: the installed version, and the record of which example articles it
 * made. The articles themselves stay. By now they are the site's content, and may have been edited.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$evpx_sites = is_multisite() ? get_sites( array( 'fields' => 'ids' ) ) : array( 0 );

foreach ( $evpx_sites as $evpx_site ) {
	if ( $evpx_site ) {
		switch_to_blog( $evpx_site );
	}

	delete_option( 'evpx_version' );
	delete_option( 'evpx_examples' );
	delete_transient( 'evpx_examples_notice' );

	if ( $evpx_site ) {
		restore_current_blog();
	}
}
