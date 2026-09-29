<?php
/**
 * Creates a page designed in Breakdance from docs/demo-article.txt: one Breakdance Section per
 * top-level EV widget, each holding either Breakdance's Shortcode element (mode "shortcode") or the
 * plugin's own native element (mode "native") — the way a site builder would place them. Mode "empty"
 * makes a page with one empty Section, for adding an element from the builder's Add panel. Mode "full" is
 * "native" with every Section set to full width and no padding, the way docs/BREAKDANCE.md recommends
 * placing the widgets. Prints the page id.
 *
 *   wp eval-file tests/docker/breakdance-page.php /tmp/demo-article.txt shortcode|native|empty
 *
 * Needs the real Breakdance plugin (see tests/docker/setup.sh, EVPX_BREAKDANCE_ZIP).
 */

if ( ! function_exists( '\Breakdance\Data\set_meta' ) ) {
	fwrite( STDERR, "Breakdance is not active.\n" );
	exit( 1 );
}

require '/tmp/native-helpers.php';

$article = (string) file_get_contents( $args[0] ?? '/tmp/demo-article.txt' );
$mode    = $args[1] ?? 'shortcode';
$native  = in_array( $mode, array( 'native', 'full' ), true );

if ( 'empty' === $mode ) {
	$tree                       = evpx_test_tree( array(), true );
	$tree['root']['children'][] = array(
		'id'       => 2,
		'data'     => array(
			'type'       => 'EssentialElements\\Section',
			'properties' => null,
		),
		'children' => array(),
	);
	$tree['_nextNodeId']        = 3;
} else {
	$tree = evpx_test_tree( evpx_test_native_nodes( $article ), $native, 'full' === $mode ? evpx_test_full_width_section() : null );
}

$titles = array(
	'native' => 'Choosing AC or DC Charging (native Breakdance elements)',
	'full'   => 'Choosing AC or DC Charging (full-width Breakdance sections)',
	'empty'  => 'An empty Breakdance page (add an element from the panel)',
);

$post_id = wp_insert_post(
	array(
		'post_title'   => $titles[ $mode ] ?? 'Choosing AC or DC Charging (built in Breakdance)',
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => '',
	)
);
\Breakdance\Data\set_meta( $post_id, '_breakdance_data', array( 'tree_json_string' => wp_json_encode( $tree ) ) );

echo $post_id . "\n";
