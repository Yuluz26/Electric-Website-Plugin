<?php
/**
 * Breakdance templates holding EV elements, for tests/docker/template-check.sh. A Breakdance footer or
 * Single Post template applies to the whole site, so each is created for one check and removed again —
 * this script removes whatever an earlier run left before it makes anything.
 *
 *   wp eval-file tests/docker/template-fixture.php footer-native|footer-shortcode|post-template|none
 *
 *   footer-native      a footer (everywhere) with the native EV CTA
 *   footer-shortcode   a footer (everywhere) with a Shortcode element holding [evpx_cta]
 *   post-template      a Single Post template with the native EV Hero and FAQ
 *   none               remove the fixtures and stop
 *
 * Needs the real Breakdance plugin (see tests/docker/setup.sh, EVPX_BREAKDANCE_ZIP). Prints
 * "<post type> <id>", or "cleared".
 */

if ( ! function_exists( '\Breakdance\Data\set_meta' ) ) {
	fwrite( STDERR, "Breakdance is not active.\n" );
	exit( 1 );
}

$mode = $args[0] ?? 'none';

foreach ( get_posts(
	array(
		'post_type'   => array( 'breakdance_footer', 'breakdance_header', 'breakdance_template' ),
		'post_status' => 'any',
		'numberposts' => -1,
		'fields'      => 'ids',
		'meta_key'    => '_evpx_template_fixture', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value'  => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	)
) as $old_id ) {
	wp_delete_post( $old_id, true );
}

if ( 'none' === $mode ) {
	echo "cleared\n";
	return;
}

/** A tree of one Section per element — what the builder saves. */
function evpx_template_tree( array $elements ): array {
	$next     = 2;
	$sections = array();

	foreach ( $elements as $element ) {
		$section_id = $next++;
		$element_id = $next++;
		$sections[] = array(
			'id'       => $section_id,
			'data'     => array(
				'type'       => 'EssentialElements\\Section',
				'properties' => null,
			),
			'children' => array(
				array(
					'id'       => $element_id,
					'data'     => $element,
					'children' => array(),
				),
			),
		);
	}

	return array(
		'root'        => array(
			'id'       => 1,
			'data'     => array(
				'type'       => 'root',
				'properties' => null,
			),
			'children' => $sections,
		),
		'_nextNodeId' => $next,
		'status'      => 'exported',
	);
}

$cta = array(
	'eyebrow'      => 'Next step',
	'title'        => 'Talk to us about your site',
	'body'         => 'A short call is usually enough to say which mix of AC and DC fits.',
	'button_label' => 'Start the conversation',
	'button_url'   => '#contact',
);

if ( 'post-template' === $mode ) {
	$post_type = 'breakdance_template';
	$settings  = array(
		'type'       => 'post',
		'ruleGroups' => array(),
		'priority'   => 100,
	);
	$tree      = evpx_template_tree(
		array(
			array(
				'type'       => 'EVPX\\Hero',
				'properties' => array(
					'content' => array(
						'content' => array(
							'title'   => 'Hero inside a Single Post template',
							'excerpt' => 'Rendered by a Breakdance template, not by the page.',
						),
						'motion'  => array( 'progress_bar' => false ),
					),
				),
			),
			array(
				'type'       => 'EVPX\\Faq',
				'properties' => array(
					'content' => array(
						'items' => array(
							'rows' => array(
								array(
									'question' => 'Is this a template?',
									'answer'   => 'Yes.',
								),
							),
						),
					),
				),
			),
		)
	);
} else {
	$post_type = 'breakdance_footer';
	$settings  = array(
		'type'       => 'everywhere',
		'ruleGroups' => array(),
		'priority'   => 100,
	);
	$element   = 'footer-native' === $mode
		? array(
			'type'       => 'EVPX\\Cta',
			'properties' => array(
				'content' => array(
					'content' => $cta,
					'visual'  => array( 'variant' => 'dark' ),
				),
			),
		)
		: array(
			'type'       => 'EssentialElements\\Shortcode',
			'properties' => array(
				'content' => array(
					'shortcode' => array(
						'full_shortcode' => '[evpx_cta eyebrow="' . $cta['eyebrow'] . '" title="' . $cta['title'] . '" body="' . $cta['body'] . '" button_label="' . $cta['button_label'] . '" button_url="' . $cta['button_url'] . '" variant="dark"]',
					),
				),
			),
		);
	$tree      = evpx_template_tree( array( $element ) );
}

$id = wp_insert_post(
	array(
		'post_title'  => 'EVPX fixture: ' . $mode,
		'post_status' => 'publish',
		'post_type'   => $post_type,
	)
);
\Breakdance\Data\set_meta( $id, '_breakdance_template_settings', wp_json_encode( $settings ) );
\Breakdance\Data\set_meta( $id, '_breakdance_data', array( 'tree_json_string' => wp_json_encode( $tree ) ) );
update_post_meta( $id, '_evpx_template_fixture', '1' );

echo $post_type . ' ' . $id . "\n";
