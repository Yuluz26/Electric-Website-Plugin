<?php
/**
 * Structural checks against the REAL Breakdance plugin (no stub). Run through
 * tests/docker/breakdance-real-check.sh, which also does the behavioural probe:
 *
 *   EVPX_BREAKDANCE_ZIP=/path/to/breakdance.zip bash tests/docker/setup.sh
 *   bash tests/docker/breakdance-real-check.sh
 *
 * Exits non-zero on any failure. Everything asserted here was read out of
 * Breakdance's own source (element-studio/base.php, dynamic-data/*, util/*),
 * not out of its public docs.
 */

$failures = 0;
$check    = static function ( $label, $ok, $detail = '' ) use ( &$failures ) {
	echo ( $ok ? 'PASS' : 'FAIL' ) . ' — ' . $label . ( ! $ok && $detail ? " ($detail)" : '' ) . "\n";
	if ( ! $ok ) {
		++$failures;
	}
};

if ( ! defined( '__BREAKDANCE_VERSION' ) || isset( $GLOBALS['evpx_stub'] ) ) {
	echo "FAIL — the real Breakdance plugin is not active (or the stub is): nothing to check.\n";
	exit( 1 );
}
echo 'Breakdance ' . __BREAKDANCE_VERSION . "\n";

$check( 'breakdance_loaded has fired', did_action( 'breakdance_loaded' ) > 0 );
$check( 'Compatibility::isBreakdanceActive() sees the real plugin', ( new EVPX\Breakdance\Compatibility() )->isBreakdanceActive() );

// ------------------------------------------------- Element Studio save locations
$mine = array_values(
	array_filter(
		\Breakdance\ElementStudio\ElementStudioController::getInstance()->saveLocations,
		static function ( $l ) {
			return 'EVPX' === ( $l['namespace'] ?? '' );
		}
	)
);
$check( 'both save locations reached Breakdance (elements + presets)', 2 === count( $mine ), 'count=' . count( $mine ) );

$folder = basename( rtrim( EVPX_PATH, '/' ) );
$by     = array();
foreach ( $mine as $l ) {
	$by[ $l['type'] ] = $l;
}
$check(
	'elements location resolves the way Breakdance resolves it (WP_PLUGIN_DIR + directoryPath exists)',
	isset( $by['element'] ) && $folder . '/element-studio/elements' === $by['element']['directoryPath'] && is_dir( WP_PLUGIN_DIR . '/' . $by['element']['directoryPath'] ),
	wp_json_encode( $by['element'] ?? null )
);
$check(
	'presets location resolves the same way',
	isset( $by['preset'] ) && $folder . '/element-studio/presets' === $by['preset']['directoryPath'] && is_dir( WP_PLUGIN_DIR . '/' . $by['preset']['directoryPath'] ),
	wp_json_encode( $by['preset'] ?? null )
);
$check(
	'locations are open to every Breakdance user (onlyForAdvancedUsers=false) and shown in Element Studio',
	isset( $by['element'], $by['preset'] )
		&& false === $by['element']['onlyForAdvancedUsers'] && false === $by['preset']['onlyForAdvancedUsers']
		&& empty( $by['element']['excludeFromElementStudio'] ) && empty( $by['preset']['excludeFromElementStudio'] )
);

// ------------------------------------------------------------------ Dynamic Data
$controller = \Breakdance\DynamicData\DynamicDataController::getInstance();
$field      = $controller->getField( 'evpx_reading_time' );
$check( 'Dynamic Data field is registered with Breakdance', $field instanceof EVPX\Breakdance\Fields\ReadingTimeField );

$listed = array_values(
	array_filter(
		$controller->getAllFields(),
		static function ( $f ) {
			return 'evpx_reading_time' === $f['slug'];
		}
	)
);
$check(
	'field is listed by getAllFields() as a string field (what the builder\'s picker reads)',
	1 === count( $listed ) && array( 'string' ) === $listed[0]['returnTypes'] && '' !== $listed[0]['label'] && '' !== $listed[0]['category'],
	wp_json_encode( $listed[0] ?? null )
);

if ( $field ) {
	// The widgets keep their copy in attributes. 397 words of body + the 1-word heading
	// = 398 words = 2 minutes; counting the raw text instead adds the shortcode's own
	// tag and attribute names (5 words) and tips it to 3.
	$words     = trim( str_repeat( 'word ', 397 ) );
	$shortcode = '[evpx_section heading="Probe" body="' . $words . '"]';

	// A normal post using an EV widget.
	$plain_id        = wp_insert_post(
		array(
			'post_title'   => 'EVPX check: plain',
			'post_status'  => 'publish',
			'post_content' => $shortcode,
		)
	);
	$GLOBALS['post'] = get_post( $plain_id );
	setup_postdata( $GLOBALS['post'] );
	$plain = $field->handler( array() )->value;
	wp_delete_post( $plain_id, true );
	$check( 'reading time counts the rendered copy, not the shortcode markup (398 words → 2 min)', '2 min read' === $plain, $plain );

	// A page designed in Breakdance: post_content is empty, the words live in the
	// element tree. Breakdance's own SEO integrations read it with get_tree_as_html().
	$tree = array(
		'root' => array(
			'id'       => 1,
			'data'     => array(
				'type'       => 'root',
				'properties' => array(),
			),
			'children' => array(
				array(
					'id'       => 2,
					'data'     => array(
						'type'       => 'EssentialElements\\Section',
						'properties' => array(),
					),
					'children' => array(
						array(
							'id'       => 3,
							'data'     => array(
								'type'       => 'EssentialElements\\Shortcode',
								'properties' => array( 'content' => array( 'shortcode' => array( 'full_shortcode' => $shortcode ) ) ),
							),
							'children' => array(),
						),
					),
				),
			),
		),
	);
	$bd_id = wp_insert_post(
		array(
			'post_title'   => 'EVPX check: Breakdance',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		)
	);
	\Breakdance\Data\set_meta( $bd_id, '_breakdance_data', array( 'tree_json_string' => wp_json_encode( $tree ) ) );
	$GLOBALS['post'] = get_post( $bd_id );
	setup_postdata( $GLOBALS['post'] );
	$bd_value = $field->handler( array() )->value;
	$check( 'reading time works on a page built in Breakdance (words are in the tree, not post_content)', '2 min read' === $bd_value, $bd_value );

	wp_delete_post( $bd_id, true );

	// A page that shows its own reading time: rendering its tree evaluates the field,
	// and the field reads the tree. That must terminate (Breakdance's dynamic-data
	// token is a [breakdance_dynamic field="…"] shortcode inside a property string).
	$self_tree = $tree;
	$self_tree['root']['children'][0]['children'][0]['data']['properties']['content']['shortcode']['full_shortcode'] =
		'Reading time: [breakdance_dynamic field="evpx_reading_time"] ' . $shortcode;
	$self_id = wp_insert_post(
		array(
			'post_title'  => 'EVPX check: self-referencing',
			'post_status' => 'publish',
			'post_type'   => 'page',
		)
	);
	\Breakdance\Data\set_meta( $self_id, '_breakdance_data', array( 'tree_json_string' => wp_json_encode( $self_tree ) ) );
	$GLOBALS['post'] = get_post( $self_id );
	setup_postdata( $GLOBALS['post'] );
	$self_text = wp_strip_all_tags( \Breakdance\Data\get_tree_as_html( $self_id ) );
	wp_delete_post( $self_id, true );
	$check(
		'a page that displays its own reading time renders without recursing',
		1 === preg_match( '/Reading time:\s*\d+ min read/', $self_text ),
		substr( trim( preg_replace( '/\s+/', ' ', $self_text ) ), 0, 80 )
	);
}

echo $failures ? "\n$failures real-Breakdance check(s) failed.\n" : "\nReal Breakdance integration: all checks passed.\n";
exit( $failures ? 1 : 0 );
