<?php
/**
 * The example articles the plugin makes when it is activated (src/Setup/ExamplePages.php), checked in
 * WordPress without a browser: what activation does and does not do, what gets made, that it is made once, that
 * it cannot repeat or spread, and that the Breakdance version says what the shortcode version says. Run through
 * tests/docker/example-pages-check.sh, which adds the parts that need a real admin request. Needs the real
 * Breakdance (tests/docker/setup.sh). Cleans up after itself. Exits non-zero on any failure.
 */

use EVPX\Breakdance\Native\Controls;
use EVPX\Breakdance\Native\NativeElements;
use EVPX\Breakdance\Native\Tree;
use EVPX\Setup\ExamplePages;

$failures = 0;
$check    = static function ( $label, $ok, $detail = '' ) use ( &$failures ) {
	echo ( $ok ? 'PASS' : 'FAIL' ) . ' — ' . $label . ( ! $ok && $detail ? " ($detail)" : '' ) . "\n";
	if ( ! $ok ) {
		++$failures;
	}
};

if ( ! function_exists( '\Breakdance\Data\set_meta' ) ) {
	fwrite( STDERR, "Breakdance is not active.\n" );
	exit( 1 );
}

global $wpdb;

$article_file = EVPX_PATH . 'content/demo-article.txt';
$article      = (string) file_get_contents( $article_file );
$max_id       = static function () use ( $wpdb ) {
	return (int) $wpdb->get_var( "SELECT MAX(ID) FROM {$wpdb->posts}" );
};
$fingerprint  = static function ( int $up_to ) use ( $wpdb ) {
	return (string) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(CRC32(CONCAT(ID, post_modified_gmt, post_status, post_content))) FROM {$wpdb->posts} WHERE ID <= %d", $up_to ) );
};
$ids          = static function ( $state ) {
	// The article, the Breakdance page, then the six site pages.
	return array_merge( array( $state['article'] ?? 0, $state['breakdance'] ?? 0 ), array_values( (array) ( $state['site_pages'] ?? array() ) ) );
};
$made         = array();
$cleanup      = static function () use ( &$made ) {
	foreach ( $made as $id ) {
		if ( $id ) {
			wp_delete_post( (int) $id, true );
		}
	}
	$made = array();
	delete_option( ExamplePages::OPTION );
	delete_transient( ExamplePages::NOTICE );
};

wp_set_current_user( 1 );
$cleanup();
$before_max = $max_id();
$before     = $fingerprint( $before_max );
$examples   = new ExamplePages();

// ------------------------------------------------------------------ activation only queues
ExamplePages::queue();
$check( 'activation queues the examples and makes nothing', array( 'pending' => true ) === get_option( ExamplePages::OPTION ) && $before_max === $max_id(), wp_json_encode( get_option( ExamplePages::OPTION ) ) );

// ------------------------------------------------------------------ who and where
foreach ( array( 'subscriber' => 'a subscriber', 'editor' => 'an editor' ) as $role => $who ) {
	$other = wp_insert_user(
		array(
			'user_login' => 'evpx_examples_' . $role,
			'user_pass'  => wp_generate_password(),
			'user_email' => "evpx-examples-{$role}@example.test",
			'role'       => $role,
		)
	);
	wp_set_current_user( $other );
	$examples->createPending();
	$check( "{$who} triggers nothing (it takes an administrator to add content on the plugin’s behalf)", $before_max === $max_id() && array( 'pending' => true ) === get_option( ExamplePages::OPTION ) );
	wp_set_current_user( 1 );
	wp_delete_user( $other );
}

add_filter( 'wp_doing_ajax', '__return_true' );
$examples->createPending();
remove_filter( 'wp_doing_ajax', '__return_true' );
$check( 'an ajax request (the heartbeat, say) triggers nothing', $before_max === $max_id() && array( 'pending' => true ) === get_option( ExamplePages::OPTION ) );

// ------------------------------------------------------------------ what gets made
$examples->createPending();
$state = get_option( ExamplePages::OPTION );
$made  = $ids( $state );
$check( 'the next admin request makes both, and records their ids', is_array( $state ) && ! empty( $state['article'] ) && ! empty( $state['breakdance'] ) && false === $state['pending'], wp_json_encode( $state ) );

$post = get_post( (int) ( $state['article'] ?? 0 ) );
$page = get_post( (int) ( $state['breakdance'] ?? 0 ) );
$check( 'the article is a post and the Breakdance version is a page', $post && 'post' === $post->post_type && $page && 'page' === $page->post_type );
$check( 'both are drafts: nothing is public until someone publishes it', $post && $page && 'draft' === $post->post_status && 'draft' === $page->post_status, ( $post->post_status ?? '?' ) . ' / ' . ( $page->post_status ?? '?' ) );
$check( 'they belong to the administrator who triggered them', $post && $page && 1 === (int) $post->post_author && 1 === (int) $page->post_author );
$check( 'the post holds the article exactly as content/demo-article.txt has it', $post && $article === $post->post_content );
$check( 'the page has no post_content of its own: its words live in the Breakdance tree', $page && '' === $page->post_content );
$check( 'the titles tell the two apart', $post && $page && 'Choosing AC or DC Charging for Your Site' === $post->post_title && 'Choosing AC or DC Charging for Your Site (Breakdance)' === $page->post_title );
$check( 'nothing that existed before was touched', $before === $fingerprint( $before_max ), 'fingerprint changed' );
$check( 'a notice is queued for the administrator, once', array_keys( (array) get_transient( ExamplePages::NOTICE ) ) === array( 'article', 'breakdance', 'site' ) );

// ------------------------------------------------------------------ the Breakdance page
$tree     = $page ? \Breakdance\Data\get_tree( $page->ID ) : null;
$sections = $tree['root']['children'] ?? array();
$elements = array_map( static fn( $section ) => $section['children'][0]['data'] ?? array(), $sections );
$types    = array_column( $elements, 'type' );
$expected = array_map( static fn( $node ) => $node['class'], Tree::nodes( $article ) );

$check( 'the page is a real Breakdance document: a Section per element, each holding one of the plugin’s native elements', $types === $expected && count( $types ) >= 12 && 'exported' === ( $tree['status'] ?? '' ), implode( ',', $types ) );
$check( 'every Section is full width with no padding (the recommended set-up)', $sections && array() === array_filter( $sections, static fn( $s ) => 'full' !== ( $s['data']['properties']['design']['size']['width'] ?? '' ) || 0 !== ( $s['data']['properties']['design']['spacing']['padding']['breakpoint_base']['top']['number'] ?? 1 ) ) );
$by_type = array();
foreach ( $elements as $element ) {
	$by_type[ $element['type'] ][] = $element['properties']['content'] ?? array();
}
$check( 'its hero is an h2 where the theme prints the page’s title as the h1 (the post’s hero is one too)', ( 'breakdance-zero' === get_template() ? 'h1' : 'h2' ) === ( $by_type['EVPX\\Hero'][0]['advanced']['title_tag'] ?? '' ) && str_contains( $article, 'title_tag="h2"' ), get_template() . ': ' . ( $by_type['EVPX\\Hero'][0]['advanced']['title_tag'] ?? '?' ) );
$check( 'its Related row lists the latest posts (a page has no category to list from)', 'latest' === ( $by_type['EVPX\\Related'][0]['content']['source'] ?? '' ) );
$check( 'the hero’s button has a target: the decision factors carry the anchor it links to', 'decision' === ( $by_type['EVPX\\DecisionFactors'][0]['advanced']['anchor'] ?? '' ) && str_contains( $article, 'cta_url="#decision"' ) );
$check( 'the FAQ, the scenario cards and the decision factors arrive as repeater rows', 4 === count( $by_type['EVPX\\Faq'][0]['items']['rows'] ?? array() ) && 6 === count( $by_type['EVPX\\ScenarioCards'][0]['items']['rows'] ?? array() ) && 7 === count( $by_type['EVPX\\DecisionFactors'][0]['items']['rows'] ?? array() ) );
$check( 'Breakdance itself reads its words (what the reading time is worked out from)', $page && str_contains( wp_strip_all_tags( \Breakdance\Data\get_tree_as_html( $page->ID ) ), 'Choosing AC or DC Charging for Your Site' ) );

// ------------------------------------------------------------------ same article, both ways
$same = true;
$why  = '';
foreach ( Tree::nodes( $article ) as $node ) {
	preg_match( '/' . get_shortcode_regex( array( $node['widget']->shortcodeTag() ) ) . '/s', $node['shortcode'], $m );
	$from_shortcode = $node['widget']->sanitizeAttributes( array_merge( $node['widget']->defaultAttributes(), (array) shortcode_parse_atts( $m[3] ) ) );
	$from_element   = $node['widget']->sanitizeAttributes( Controls::attsFromProperties( $node['widget'], $node['properties'] ) );
	if ( $from_shortcode !== $from_element ) {
		$same = false;
		$why  = $node['class'] . ': ' . wp_json_encode( array_diff_assoc( $from_shortcode, $from_element ) );
		break;
	}

	$item = $node['widget']->childWidget();
	if ( $item ) {
		preg_match_all( '/' . get_shortcode_regex( array( $item->shortcodeTag() ) ) . '/s', (string) $m[5], $rows, PREG_SET_ORDER );
		$want = array_map( static fn( $row ) => $item->sanitizeAttributes( array_merge( $item->defaultAttributes(), (array) shortcode_parse_atts( $row[3] ) ) ), $rows );
		$got  = array_map( static fn( $row ) => $item->sanitizeAttributes( $row ), Controls::rowsFromProperties( $item, $node['properties'] ) );
		if ( $want !== $got ) {
			$same = false;
			$why  = $node['class'] . ' rows';
			break;
		}
	}
}
$check( 'every element of the tree carries the attributes its shortcode carries, item rows included', $same, $why );

$mismatch = array();
foreach ( NativeElements::WIDGETS as $name => $widget_class ) {
	$class = 'EVPX\\' . $name;
	if ( ! class_exists( $class ) ) {
		$mismatch[] = $name . ' (no such element)';
		continue;
	}
	$method = new ReflectionMethod( $class, 'widget' );
	$method->setAccessible( true );
	if ( ! $method->invoke( null ) instanceof $widget_class ) {
		$mismatch[] = $name;
	}
}
$check( 'the map Tree reads (NativeElements::WIDGETS) agrees with each element class’s own widget', ! $mismatch, implode( ', ', $mismatch ) );

// ------------------------------------------------------------------ the hero’s heading follows the theme
$hero_tag = static function ( string $theme ) use ( $examples, $cleanup, &$made ) {
	$cleanup();
	ExamplePages::queue();
	$force = static fn() => $theme;
	add_filter( 'template', $force );
	$examples->createPending();
	remove_filter( 'template', $force );
	$state = get_option( ExamplePages::OPTION );
	$made  = $ids( $state );
	$tree  = \Breakdance\Data\get_tree( (int) ( $state['breakdance'] ?? 0 ) );

	return $tree['root']['children'][0]['children'][0]['data']['properties']['content']['advanced']['title_tag'] ?? '?';
};
$under_zero    = $hero_tag( 'breakdance-zero' );
$under_block   = $hero_tag( 'twentytwentyfive' );
$under_classic = $hero_tag( 'astra' );
$check( 'under Breakdance’s Zero theme, which prints no title, the hero is the page’s h1', 'h1' === $under_zero, $under_zero );
$check( 'under any other theme it is an h2, under the title the theme already prints', 'h2' === $under_block && 'h2' === $under_classic, "$under_block / $under_classic" );
$cleanup();
ExamplePages::queue();
$examples->createPending();
$state = get_option( ExamplePages::OPTION );
$made  = $ids( $state );
$post  = get_post( (int) $state['article'] );
$page  = get_post( (int) $state['breakdance'] );

// ------------------------------------------------------------------ Breakdance arriving later: only what is missing
$cleanup();
update_option( ExamplePages::OPTION, array( 'pending' => true, 'article' => (int) $post->ID, 'site' => 0 ) );
$posts_before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('post','page')" );
$examples->createPending();
$state        = get_option( ExamplePages::OPTION );
$made         = array( $state['breakdance'] ?? 0 );
$posts_after  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('post','page')" );
$check( 'with the article already made, a later request adds only the Breakdance page', ! empty( $state['breakdance'] ) && (int) $post->ID === $state['article'] && false === $state['pending'] && $posts_before + 1 === $posts_after, wp_json_encode( $state ) . " posts $posts_before → $posts_after" );
$cleanup();
ExamplePages::queue();
$examples->createPending();
$state = get_option( ExamplePages::OPTION );
$made  = $ids( $state );
$post  = get_post( (int) $state['article'] );
$page  = get_post( (int) $state['breakdance'] );

// ------------------------------------------------------------------ once, and only once
$posts_now = $max_id();
$examples->createPending();
$check( 'a second admin request makes nothing', $posts_now === $max_id() );
ExamplePages::queue();
$check( 'activating again does not queue them again', false === get_option( ExamplePages::OPTION )['pending'] );
foreach ( $made as $made_id ) {
	wp_delete_post( (int) $made_id, true );
}
$examples->createPending();
ExamplePages::queue();
$check( 'an example that was deleted stays deleted', $before_max === $max_id() && ! get_post( $post->ID ) && ! get_post( $page->ID ), 'newest post is ' . $max_id() . ', was ' . $before_max );

// ------------------------------------------------------------------ a fault stays contained
$cleanup();
ExamplePages::queue();
add_filter( 'wp_insert_post_empty_content', '__return_true' );
$examples->createPending();
remove_filter( 'wp_insert_post_empty_content', '__return_true' );
$state = get_option( ExamplePages::OPTION );
$check( 'a kind that fails is recorded as failed, and the request carries on', 0 === ( $state['article'] ?? null ) && 0 === ( $state['breakdance'] ?? null ) && 0 === ( $state['site'] ?? null ) && false === $state['pending'], wp_json_encode( $state ) );
$after_failure = $max_id();
$examples->createPending();
$check( 'and it is not tried again on every admin page', $after_failure === $max_id() && false === get_option( ExamplePages::OPTION )['pending'] );

// ------------------------------------------------------------------ status is the site’s to choose
$cleanup();
ExamplePages::queue();
add_filter( 'evpx_example_pages_status', static fn() => 'private' );
$examples->createPending();
$state = get_option( ExamplePages::OPTION );
$made  = $ids( $state );
$check( 'evpx_example_pages_status can make them private', 'private' === get_post_status( $state['article'] ) && 'private' === get_post_status( $state['breakdance'] ) && 6 === count( array_filter( (array) $state['site_pages'], static fn( $id ) => 'private' === get_post_status( $id ) ) ) );
$cleanup();
ExamplePages::queue();
remove_all_filters( 'evpx_example_pages_status' );
add_filter( 'evpx_example_pages_status', static fn() => 'trash; DROP TABLE' );
$examples->createPending();
$state = get_option( ExamplePages::OPTION );
$made  = $ids( $state );
$check( 'anything else it returns is a draft', 'draft' === get_post_status( $state['article'] ) && 'draft' === get_post_status( $state['breakdance'] ) && 6 === count( array_filter( (array) $state['site_pages'], static fn( $id ) => 'draft' === get_post_status( $id ) ) ) );
remove_all_filters( 'evpx_example_pages_status' );

// ------------------------------------------------------------------ uninstall
$cleanup();
update_option( 'evpx_version', '0.0.0-test' );
ExamplePages::queue();
$examples->createPending();
$state = get_option( ExamplePages::OPTION );
$made  = $ids( $state );
set_transient( ExamplePages::NOTICE, array( 'article' => 1 ), DAY_IN_SECONDS );
define( 'WP_UNINSTALL_PLUGIN', EVPX_BASENAME );
include EVPX_PATH . 'uninstall.php';
$check( 'deleting the plugin removes what it stored: the version, the record of the examples, the notice', false === get_option( 'evpx_version' ) && false === get_option( ExamplePages::OPTION ) && false === get_transient( ExamplePages::NOTICE ) );
$check( 'and leaves the examples themselves: by then they are the site’s content', get_post( $made[0] ) && get_post( $made[1] ) );
update_option( 'evpx_version', EVPX_VERSION );

$cleanup();
$check( 'the check leaves the site as it found it', $before === $fingerprint( $before_max ) && $before_max === $max_id() );

echo $failures ? "\n{$failures} check(s) failed.\n" : "\nAll example-page checks passed.\n";
exit( $failures ? 1 : 0 );
