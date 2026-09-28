<?php
/**
 * Run with the stub installed:  wp eval-file tests/docker/breakdance-contract-check.php
 * Exits non-zero if the plugin's Breakdance integration doesn't match the contract in
 * breakdance-stub.php.
 */

$failures = 0;
$check    = static function ( $label, $ok, $detail = '' ) use ( &$failures ) {
	echo ( $ok ? 'PASS' : 'FAIL' ) . ' — ' . $label . ( ! $ok && $detail ? " ($detail)" : '' ) . "\n";
	if ( ! $ok ) {
		++$failures;
	}
};

$stub = $GLOBALS['evpx_stub'] ?? null;
$check( 'stub is loaded', is_array( $stub ) );

$compat = new EVPX\Breakdance\Compatibility();
$check( 'Compatibility::isBreakdanceActive() sees breakdance_loaded', $compat->isBreakdanceActive() );

$locations = $stub['save_locations'] ?? array();
$check( 'registerSaveLocation called twice (elements + presets)', 2 === count( $locations ), 'count=' . count( $locations ) );

$by_type = array();
foreach ( $locations as $l ) {
	$by_type[ $l['type'] ] = $l;
}
$check(
	'elements location: namespace EVPX, plugin-relative path, non-empty label, flag false',
	isset( $by_type['element'] )
		&& 'EVPX' === $by_type['element']['namespace']
		&& 'ev-charging-experience/element-studio/elements' === $by_type['element']['path']
		&& '' !== $by_type['element']['label']
		&& false === $by_type['element']['flag'],
	wp_json_encode( $by_type['element'] ?? null )
);
$check(
	'presets location registered',
	isset( $by_type['preset'] ) && 'ev-charging-experience/element-studio/presets' === $by_type['preset']['path']
);
$check(
	'save-location directories exist on disk',
	is_dir( EVPX_PATH . 'element-studio/elements' ) && is_dir( EVPX_PATH . 'element-studio/presets' )
);

$fields = $stub['fields'] ?? array();
$check( 'one Dynamic Data field registered', 1 === count( $fields ), 'count=' . count( $fields ) );

if ( 1 === count( $fields ) ) {
	$field = $fields[0];
	$check( 'field extends the documented StringField base', $field instanceof \Breakdance\DynamicData\StringField );
	$check( 'field slug is namespaced', 0 === strpos( $field->slug(), 'evpx_' ), $field->slug() );
	$check( 'field has label and category', '' !== $field->label() && '' !== $field->category() );

	$post_id = wp_insert_post(
		array(
			'post_title'   => 'Contract check',
			'post_status'  => 'publish',
			'post_content' => str_repeat( 'word ', 450 ),
		)
	);
	$GLOBALS['post'] = get_post( $post_id );
	setup_postdata( $GLOBALS['post'] );
	$value = $field->handler( array() )->value;
	wp_delete_post( $post_id, true );
	$check( 'field handler returns a reading time for the current post (450 words → 3 min)', '3 min read' === $value, $value );
}

echo $failures ? "\n$failures contract check(s) failed.\n" : "\nBreakdance integration contract: all checks passed.\n";
exit( $failures ? 1 : 0 );
