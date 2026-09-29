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
			return 'EVPXStudio' === ( $l['namespace'] ?? '' );
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

$check(
	'field is open to everyone: not Pro-only, so no Pro badge and it can be chosen without a Breakdance Pro licence',
	1 === count( $listed ) && false === $listed[0]['proOnly'],
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


// --------------------------------------------------------- native Breakdance elements
// The EV widgets as elements of their own in the builder's Add panel. Everything about them is
// derived from the widgets, so these checks are about the seams: what Breakdance is told, and that
// what it gets back is the same component the shortcode renders.
$names    = array( 'Hero', 'Section', 'Comparison', 'ScenarioCards', 'Flow', 'DecisionFactors', 'Faq', 'Related', 'Cta' );
$declared = array_filter(
	$names,
	static function ( $n ) {
		return class_exists( 'EVPX\\' . $n, false );
	}
);
$check( 'all nine native elements are declared', 9 === count( $declared ), implode( ',', array_diff( $names, $declared ) ) );

$evpx_classes = array_values(
	array_filter(
		\Breakdance\Elements\get_element_classnames(),
		static function ( $c ) {
			return 0 === strpos( $c, 'EVPX\\' );
		}
	)
);
$bad = array_filter(
	$evpx_classes,
	static function ( $c ) {
		return ( new ReflectionClass( $c ) )->isAbstract();
	}
);
// Breakdance instantiates every declared Element subclass to read its definition; an abstract
// class of ours in that list crashes the builder ("Cannot instantiate abstract class").
$check( 'Breakdance sees only concrete, instantiable EVPX element classes (no abstract base)', 9 === count( $evpx_classes ) && empty( $bad ), implode( ',', $evpx_classes ) );

$defs = array();
foreach ( \Breakdance\Elements\get_elements_for_builder() as $d ) {
	if ( 0 === strpos( $d['slug'], 'EVPX\\' ) ) {
		$defs[ $d['slug'] ] = $d;
	}
}
$check( 'the builder is given all nine, in the EV Charging category, in this plugin\'s order', 9 === count( $defs ) && 1 === count( array_unique( array_column( $defs, 'category' ) ) ) && 'evpx' === reset( $defs )['category'] );

$categories = array_column( \Breakdance\Elements\get_element_categories(), 'label', 'slug' );
$check( 'the EV Charging category is registered', 'EV Charging' === ( $categories['evpx'] ?? '' ), wp_json_encode( $categories ) );

// Every path that re-renders the canvas must be a real control, and every control a path.
$path_problems = array();
$leaf_paths    = static function ( array $controls, string $prefix ) use ( &$leaf_paths ) {
	$out = array();
	foreach ( $controls as $c ) {
		$path = $prefix . '.' . $c['slug'];
		if ( ! empty( $c['children'] ) && 'repeater' !== ( $c['options']['type'] ?? '' ) ) {
			$out = array_merge( $out, $leaf_paths( $c['children'], $path ) );
		} else {
			$out[] = $path;
		}
	}
	return $out;
};
foreach ( $defs as $slug => $d ) {
	$real  = $leaf_paths( $d['controls']['contentSections'], 'content' );
	$named = $d['propertyPathsToSsrElementWhenValueChanges'];
	if ( array_diff( $real, $named ) || array_diff( $named, $real ) ) {
		$path_problems[] = $slug . ': ' . implode( ',', array_merge( array_diff( $real, $named ), array_diff( $named, $real ) ) );
	}
}
$check( 'every control re-renders the canvas when changed (paths match the controls exactly)', empty( $path_problems ), implode( ' | ', $path_problems ) );

$repeater_problems = array();
foreach ( array( 'EVPX\\Faq' => 'question', 'EVPX\\ScenarioCards' => 'title', 'EVPX\\DecisionFactors' => 'title' ) as $slug => $key ) {
	$items = array_values( array_filter( $defs[ $slug ]['controls']['contentSections'], static function ( $s ) { return 'items' === $s['slug']; } ) );
	$rep   = $items[0]['children'][0] ?? array();
	$keys  = array_column( $rep['children'] ?? array(), 'slug' );
	if ( 'repeater' !== ( $rep['options']['type'] ?? '' ) || ( $rep['options']['repeaterOptions']['titleTemplate'] ?? '' ) !== '{' . $key . '}' || ! in_array( $key, $keys, true ) ) {
		$repeater_problems[] = $slug;
	}
}
$check( 'container elements edit their items as a repeater, titled by the item\'s first text field', empty( $repeater_problems ), implode( ',', $repeater_problems ) );

// Dynamic data: a Hero's title can be bound to the post title, an FAQ row's question to a field.
$dyn_hero = array_column( $defs['EVPX\\Hero']['dynamicPropertyPaths'], 'accepts', 'path' );
$dyn_faq  = array_column( $defs['EVPX\\Faq']['dynamicPropertyPaths'], 'accepts', 'path' );
$dyn_cta  = array_column( $defs['EVPX\\Cta']['dynamicPropertyPaths'], 'accepts', 'path' );
$check(
	'text, textarea and URL controls accept Breakdance dynamic data (string / url), rows included; nothing else does',
	'string' === ( $dyn_hero['content.content.title'] ?? '' ) && 'url' === ( $dyn_hero['content.content.cta_url'] ?? '' )
		&& 'string' === ( $dyn_faq['content.items.rows[].question'] ?? '' ) && ! isset( $dyn_hero['content.media.media'], $dyn_hero['content.motion.animate'] )
		&& 'url' === ( $dyn_cta['content.content.button_url'] ?? '' ),
	wp_json_encode( array_keys( $dyn_hero ) )
);

// The "Vertical spacing" control reaches the markup of every section widget.
$spaced = array();
foreach ( array( 'Section', 'Comparison', 'ScenarioCards', 'Flow', 'DecisionFactors', 'Faq', 'Related', 'Cta' ) as $n ) {
	$c    = 'EVPX\\' . $n;
	$none = $c::ssr( array( 'content' => array( 'layout' => array( 'spacing' => 'none' ) ) ), array(), false );
	$def  = $c::ssr( array(), array(), false );
	if ( false === strpos( $none, 'data-evpx-spacing="none"' ) || false === strpos( $def, 'data-evpx-spacing="default"' ) ) {
		if ( 'Related' !== $n || '' !== trim( $def ) ) { // Related renders nothing when there is nothing to list.
			$spaced[] = $n;
		}
	}
}
$check( 'every section element carries data-evpx-spacing (default, and none when set)', empty( $spaced ), implode( ',', $spaced ) );

// The empty row "Add" creates, and a row with an answer but no question, must not reach the page: an empty
// button on the front end, an empty entry in the FAQ's structured data.
$rows  = array(
	array(
		'question' => 'Kept',
		'answer'   => 'Yes.',
	),
	array(
		'question' => '',
		'answer'   => '',
	),
	array( 'answer' => 'An answer nobody asked for' ),
);
$blank = \EVPX\Faq::ssr( array( 'content' => array( 'items' => array( 'rows' => $rows ) ) ), array(), false );
$check(
	'an item row without its name (the empty row "Add" creates) is not rendered',
	1 === substr_count( $blank, 'data-evpx-question=' ) && false !== strpos( $blank, 'Kept' ) && false === strpos( $blank, 'nobody asked' ),
	(string) substr_count( $blank, 'data-evpx-question=' )
);

// One schema, one render path: a native element must produce exactly what the shortcode does.
require '/tmp/native-helpers.php';
$nodes = evpx_test_native_nodes( (string) file_get_contents( '/tmp/demo-article.txt' ) );
$norm  = static function ( $html ) {
	// Random ids differ per render; whitespace between tags differs because the shortcode source has
	// newlines between nested tags and the repeater has none. Neither is a difference in the component.
	return preg_replace( array( '/evpx-(?:cmp|faq)-[0-9a-f]{8}/', '/>\s+</' ), array( 'evpx-id', '><' ), (string) $html );
};
$diffs = array();
foreach ( $nodes as $i => $node ) {
	$class = $node['class'];
	$a     = $norm( do_shortcode( $node['shortcode'] ) );
	$b     = $norm( $class::ssr( $node['properties'], array(), false ) );
	if ( $a !== $b ) {
		$at      = strspn( $a ^ $b, "\0" );
		$diffs[] = $class . ' @' . $at . ' «' . substr( $a, max( 0, $at - 20 ), 60 ) . '» vs «' . substr( $b, max( 0, $at - 20 ), 60 ) . '»';
	}
}
$check( 'a native element renders the same markup as its shortcode (' . count( $nodes ) . ' widgets of the demo article)', count( $nodes ) >= 10 && empty( $diffs ), implode( ' | ', array_slice( $diffs, 0, 2 ) ) );

// A never-touched toggle means "default"; an explicit false must stay false (motion off).
$off = \EVPX\Hero::ssr( array( 'content' => array( 'motion' => array( 'animate' => false, 'progress_bar' => false ) ) ), array(), false );
$on  = \EVPX\Hero::ssr( array(), array(), false );
$check( 'toggles: unset uses the default (on), an explicit false turns it off', false !== strpos( $on, 'data-evpx-animate="1"' ) && false !== strpos( $on, 'evpx-progress' ) && false !== strpos( $off, 'data-evpx-animate="0"' ) && false === strpos( $off, 'evpx-progress' ) );

// Dynamic data on the front end: Breakdance swaps a token for its value before the element renders, and
// the native element shows the value. (The builder canvas is different — see NativeElement::ssr() — and is
// covered by tests/playwright/breakdance-qa.mjs.)
$bound_id = wp_insert_post(
	array(
		'post_title'   => 'EVPX check: dynamic data',
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => '',
	)
);
$bound_tree = evpx_test_tree(
	array(
		array(
			'shortcode'  => '',
			'class'      => 'EVPX\\Hero',
			'properties' => array(
				'content' => array(
					'content' => array(
						'title'        => "[breakdance_dynamic field='post_title']",
						'reading_time' => "[breakdance_dynamic field='evpx_reading_time']",
					),
				),
			),
		),
	),
	true
);
\Breakdance\Data\set_meta( $bound_id, '_breakdance_data', array( 'tree_json_string' => wp_json_encode( $bound_tree ) ) );
$GLOBALS['post'] = get_post( $bound_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
setup_postdata( $GLOBALS['post'] );
$bound_html = \Breakdance\Data\get_tree_as_html( $bound_id );
wp_delete_post( $bound_id, true );
wp_reset_postdata();
$check(
	'dynamic data: a native element on a Breakdance page shows the value, not the token',
	false !== strpos( $bound_html, 'EVPX check: dynamic data' ) && false === strpos( $bound_html, '[breakdance_dynamic' ) && preg_match( '/\d+ min read/', $bound_html ),
	substr( wp_strip_all_tags( $bound_html ), 0, 160 )
);

echo $failures ? "\n$failures real-Breakdance check(s) failed.\n" : "\nReal Breakdance integration: all checks passed.\n";
exit( $failures ? 1 : 0 );
