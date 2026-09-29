<?php
/**
 * Creates a page designed in Breakdance from docs/demo-article.txt: one Breakdance
 * Section per top-level EV shortcode, each holding Breakdance's own Shortcode
 * element — the way a site builder would place these widgets. Prints the page id.
 *
 *   wp eval-file tests/docker/breakdance-page.php /tmp/demo-article.txt
 *
 * Needs the real Breakdance plugin (see tests/docker/setup.sh, EVPX_BREAKDANCE_ZIP).
 */

if ( ! function_exists( '\Breakdance\Data\set_meta' ) ) {
	fwrite( STDERR, "Breakdance is not active.\n" );
	exit( 1 );
}

$file    = $args[0] ?? '/tmp/demo-article.txt';
$article = (string) file_get_contents( $file );

// Top-level shortcodes only: get_shortcode_regex() matches an enclosing tag with its
// children as one unit, so nested items stay inside their container.
$tags = array();
foreach ( ( new EVPX\Elements\Registry() )->all() as $element ) {
	$tags[] = $element->shortcodeTag();
}
preg_match_all( '/' . get_shortcode_regex( $tags ) . '/s', $article, $matches, PREG_SET_ORDER );

$next_id  = 2;
$sections = array();
foreach ( $matches as $match ) {
	$section_id = $next_id++;
	$element_id = $next_id++;
	$sections[] = array(
		'id'       => $section_id,
		'data'     => array(
			'type'       => 'EssentialElements\\Section',
			'properties' => null,
		),
		'children' => array(
			array(
				'id'       => $element_id,
				'data'     => array(
					'type'       => 'EssentialElements\\Shortcode',
					'properties' => array( 'content' => array( 'shortcode' => array( 'full_shortcode' => $match[0] ) ) ),
				),
				'children' => array(),
			),
		),
	);
}

// The builder validates what it opens: an "exported" tree carries _nextNodeId and status
// beside the root, and nodes without settings have null (not empty-array) properties.
$tree = array(
	'root'        => array(
		'id'       => 1,
		'data'     => array(
			'type'       => 'root',
			'properties' => null,
		),
		'children' => $sections,
	),
	'_nextNodeId' => $next_id,
	'status'      => 'exported',
);

$post_id = wp_insert_post(
	array(
		'post_title'   => 'Choosing AC or DC Charging (built in Breakdance)',
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => '',
	)
);
\Breakdance\Data\set_meta( $post_id, '_breakdance_data', array( 'tree_json_string' => wp_json_encode( $tree ) ) );

echo $post_id . "\n";
