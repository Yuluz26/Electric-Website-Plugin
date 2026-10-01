<?php
/**
 * A page designed in Breakdance that holds no EV element at all, for tests/docker/isolation-check.sh:
 * activating the plugin must not change how a page like this looks. Removes an earlier copy first.
 * Prints the page id.
 *
 *   wp eval-file tests/docker/isolation-fixture.php
 *
 * Needs the real Breakdance plugin (see tests/docker/setup.sh, EVPX_BREAKDANCE_ZIP).
 */

if ( ! function_exists( '\Breakdance\Data\set_meta' ) ) {
	fwrite( STDERR, "Breakdance is not active.\n" );
	exit( 1 );
}

foreach ( get_posts(
	array(
		'post_type'   => 'page',
		'post_status' => 'any',
		'numberposts' => -1,
		'fields'      => 'ids',
		'meta_key'    => '_evpx_isolation_fixture', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value'  => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	)
) as $old_id ) {
	wp_delete_post( $old_id, true );
}

$text = static function ( int $id, string $copy ): array {
	return array(
		'id'       => $id,
		'data'     => array(
			'type'       => 'EssentialElements\\Text',
			'properties' => array( 'content' => array( 'content' => array( 'text' => $copy ) ) ),
		),
		'children' => array(),
	);
};

$tree = array(
	'root'        => array(
		'id'       => 1,
		'data'     => array(
			'type'       => 'root',
			'properties' => null,
		),
		'children' => array(
			array(
				'id'       => 2,
				'data'     => array(
					'type'       => 'EssentialElements\\Section',
					'properties' => null,
				),
				'children' => array(
					$text( 3, 'A page designed in Breakdance without any EV element on it.' ),
					$text( 4, 'It should look exactly the same whether or not the EV Charging Experience plugin is active.' ),
				),
			),
		),
	),
	'_nextNodeId' => 5,
	'status'      => 'exported',
);

$id = wp_insert_post(
	array(
		'post_title'   => 'Isolation check (a Breakdance page without EV elements)',
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => '',
	)
);
\Breakdance\Data\set_meta( $id, '_breakdance_data', array( 'tree_json_string' => wp_json_encode( $tree ) ) );
update_post_meta( $id, '_evpx_isolation_fixture', '1' );

echo $id . "\n";
